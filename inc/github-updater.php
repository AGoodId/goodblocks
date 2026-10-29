<?php
/**
 * GitHub-based plugin auto-updater.
 *
 * Checks GitHub Releases for new versions and integrates with
 * the WordPress plugin update system.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GoodBlocks_GitHub_Updater {

	private string $slug;
	private string $plugin_file;
	private string $github_repo;
	private ?object $github_response = null;

	public function __construct( string $plugin_file, string $github_repo ) {
		$this->plugin_file = $plugin_file;
		$this->slug        = plugin_basename( $plugin_file );
		$this->github_repo = $github_repo;

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'after_install' ], 10, 3 );
	}

	/**
	 * Fetch the latest release from GitHub.
	 */
	private function get_github_release(): ?object {
		if ( null !== $this->github_response ) {
			return $this->github_response;
		}

		$url      = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
		$args     = [ 'headers' => [ 'Accept' => 'application/vnd.github.v3+json' ] ];

		// Use a token if available (required for private repos).
		$token = defined( 'GOODBLOCKS_GITHUB_TOKEN' ) ? GOODBLOCKS_GITHUB_TOKEN : '';
		if ( $token ) {
			$args['headers']['Authorization'] = "token {$token}";
		}

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$this->github_response = json_decode( wp_remote_retrieve_body( $response ) );

		return $this->github_response;
	}

	/**
	 * Hook into the update check transient.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_github_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version  = ltrim( $release->tag_name, 'v' );
		$current_version = $transient->checked[ $this->slug ] ?? '0.0.0';

		if ( version_compare( $remote_version, $current_version, '>' ) ) {
			$zip_url = $this->get_zip_url( $release );

			if ( $zip_url ) {
				$transient->response[ $this->slug ] = (object) [
					'slug'        => dirname( $this->slug ),
					'plugin'      => $this->slug,
					'new_version' => $remote_version,
					'url'         => $release->html_url,
					'package'     => $zip_url,
				];
			}
		}

		return $transient;
	}

	/**
	 * Provide plugin info for the "View details" popup.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( dirname( $this->slug ) !== ( $args->slug ?? '' ) ) {
			return $result;
		}

		$release = $this->get_github_release();
		if ( ! $release ) {
			return $result;
		}

		$plugin_data = get_plugin_data( $this->plugin_file );

		return (object) [
			'name'          => $plugin_data['Name'],
			'slug'          => dirname( $this->slug ),
			'version'       => ltrim( $release->tag_name, 'v' ),
			'author'        => $plugin_data['AuthorName'],
			'homepage'      => $plugin_data['PluginURI'],
			'sections'      => [
				'description'  => $plugin_data['Description'],
				'changelog'    => nl2br( esc_html( $release->body ?? '' ) ),
			],
			'download_link' => $this->get_zip_url( $release ),
		];
	}

	/**
	 * Rename the extracted folder to match the plugin slug after install.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->slug ) {
			return $response;
		}

		global $wp_filesystem;

		$proper_destination = untrailingslashit( trailingslashit( WP_PLUGIN_DIR ) . dirname( $this->slug ) );
		$source             = untrailingslashit( (string) ( $result['destination'] ?? '' ) );

		if ( $source && ! $this->is_same_plugin_path( $source, $proper_destination ) ) {
			$relocated = $this->relocate_extracted_plugin( $wp_filesystem, $source, $proper_destination );
			if ( is_wp_error( $relocated ) ) {
				return $relocated;
			}
		}

		if ( ! is_plugin_active( $this->slug ) ) {
			activate_plugin( $this->slug );
		}

		return $response;
	}

	/**
	 * Get the zip download URL from a release.
	 * Prefers an uploaded goodblocks.zip asset; falls back to source zipball.
	 */
	private function get_zip_url( object $release ): string {
		if ( ! empty( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( 0 === strcasecmp( (string) ( $asset->name ?? '' ), 'goodblocks.zip' ) ) {
					return $this->authorized_asset_url( $asset );
				}
			}
		}

		return $release->zipball_url ?? '';
	}

	/**
	 * Return a downloadable asset URL, using a token for private repos.
	 */
	private function authorized_asset_url( object $asset ): string {
		$token = defined( 'GOODBLOCKS_GITHUB_TOKEN' ) ? GOODBLOCKS_GITHUB_TOKEN : '';
		if ( $token ) {
			return add_query_arg( 'access_token', $token, $asset->url );
		}

		return $asset->browser_download_url ?? '';
	}

	/**
	 * Move an extracted package onto the plugin slug without deleting first.
	 *
	 * @param object $wp_filesystem WordPress filesystem API.
	 * @return true|\WP_Error
	 */
	private function relocate_extracted_plugin( $wp_filesystem, string $source, string $destination ) {
		$backup = $destination . '.updating-bak';

		if ( $wp_filesystem->exists( $destination ) && ! $wp_filesystem->move( $destination, $backup ) ) {
			return new WP_Error(
				'goodblocks_updater_backup',
				'Could not back up the existing GoodBlocks plugin directory.'
			);
		}

		if ( ! $wp_filesystem->move( $source, $destination ) ) {
			if ( $wp_filesystem->exists( $backup ) ) {
				$wp_filesystem->move( $backup, $destination );
			}

			return new WP_Error(
				'goodblocks_updater_move',
				'Could not move the extracted GoodBlocks plugin into place.'
			);
		}

		if ( $wp_filesystem->exists( $backup ) ) {
			$wp_filesystem->delete( $backup, true );
		}

		return true;
	}

	/**
	 * Compare plugin paths after normalizing slashes, dots, and realpath aliases.
	 */
	private function is_same_plugin_path( string $left, string $right ): bool {
		return $this->normalize_plugin_path( $left ) === $this->normalize_plugin_path( $right );
	}

	/**
	 * Normalize a filesystem path for equality checks.
	 */
	private function normalize_plugin_path( string $path ): string {
		$path = wp_normalize_path( untrailingslashit( $path ) );
		$resolved = realpath( $path );

		if ( false !== $resolved ) {
			return wp_normalize_path( $resolved );
		}

		return $path;
	}
}
