<?php
/**
 * GoodBlocks RSS Feed — source registry, caching, refreshes, and editor API.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GOODBLOCKS_RSS_SOURCES_OPTION = 'goodblocks_rss_sources';
const GOODBLOCKS_RSS_CACHE_OPTION   = 'goodblocks_rss_cache';
const GOODBLOCKS_RSS_CRON_HOOK      = 'goodblocks_rss_refresh_feeds';

add_action( 'admin_init', 'goodblocks_rss_register_settings' );
add_action( 'admin_menu', 'goodblocks_rss_add_settings_page' );
add_action( 'admin_post_goodblocks_rss_refresh', 'goodblocks_rss_handle_manual_refresh' );
add_action( 'rest_api_init', 'goodblocks_rss_register_rest_routes' );
add_action( GOODBLOCKS_RSS_CRON_HOOK, 'goodblocks_rss_refresh_sources' );
add_action( 'init', 'goodblocks_rss_ensure_cron' );
add_action( 'update_option_' . GOODBLOCKS_RSS_SOURCES_OPTION, 'goodblocks_rss_sources_updated', 10, 2 );

/**
 * Register the source registry setting.
 */
function goodblocks_rss_register_settings(): void {
	register_setting(
		'goodblocks_rss_settings',
		GOODBLOCKS_RSS_SOURCES_OPTION,
		[
			'type'              => 'array',
			'default'           => [],
			'sanitize_callback' => 'goodblocks_rss_sanitize_sources',
			'show_in_rest'      => false,
		]
	);
}

/**
 * Add the RSS source registry under Settings.
 */
function goodblocks_rss_add_settings_page(): void {
	add_options_page(
		__( 'GoodBlocks RSS Feeds', 'goodblocks' ),
		__( 'RSS Feeds', 'goodblocks' ),
		'manage_options',
		'goodblocks-rss-feeds',
		'goodblocks_rss_render_settings_page'
	);
}

/**
 * Normalize and validate source rows from the settings form.
 *
 * @param mixed $value Raw option value.
 * @return array<int, array{id: string, name: string, url: string, max_items: int}>
 */
function goodblocks_rss_sanitize_sources( $value ): array {
	if ( ! is_array( $value ) ) {
		return [];
	}

	$sources   = [];
	$used_ids  = [];
	$used_urls = [];

	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$name = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
		$url  = isset( $row['url'] ) ? goodblocks_rss_normalize_public_url( wp_unslash( $row['url'] ) ) : '';

		if ( '' === $name && '' === $url ) {
			continue;
		}

		if ( '' === $name || '' === $url ) {
			add_settings_error(
				GOODBLOCKS_RSS_SOURCES_OPTION,
				'goodblocks_rss_incomplete_source',
				__( 'Each RSS source needs both a name and a public HTTP or HTTPS URL.', 'goodblocks' )
			);
			continue;
		}

		if ( isset( $used_urls[ $url ] ) ) {
			continue;
		}

		$id = isset( $row['id'] ) ? sanitize_key( wp_unslash( $row['id'] ) ) : '';
		if ( '' === $id ) {
			$id = sanitize_key( $name );
		}
		if ( '' === $id ) {
			$id = 'feed-' . substr( md5( $url ), 0, 12 );
		}

		$base_id = $id;
		$suffix  = 2;
		while ( isset( $used_ids[ $id ] ) ) {
			$id = $base_id . '-' . $suffix;
			++$suffix;
		}

		$max_items = isset( $row['max_items'] ) ? absint( $row['max_items'] ) : 20;
		$max_items = max( 1, min( 50, $max_items ) );

		$sources[] = [
			'id'        => $id,
			'name'      => $name,
			'url'       => $url,
			'max_items' => $max_items,
		];

		$used_ids[ $id ]   = true;
		$used_urls[ $url ] = true;
	}

	return $sources;
}

/**
 * Return configured sources without exposing malformed option data.
 *
 * @return array<int, array{id: string, name: string, url: string, max_items: int}>
 */
function goodblocks_rss_get_sources(): array {
	$value = get_option( GOODBLOCKS_RSS_SOURCES_OPTION, [] );

	if ( ! is_array( $value ) ) {
		return [];
	}

	$sources = [];
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) || empty( $row['id'] ) || empty( $row['name'] ) || empty( $row['url'] ) ) {
			continue;
		}

		$url = goodblocks_rss_sanitize_http_url( $row['url'] );
		if ( '' === $url ) {
			continue;
		}

		$sources[] = [
			'id'        => sanitize_key( $row['id'] ),
			'name'      => sanitize_text_field( $row['name'] ),
			'url'       => $url,
			'max_items' => max( 1, min( 50, absint( $row['max_items'] ?? 20 ) ) ),
		];
	}

	return $sources;
}

/**
 * Sanitize an HTTP(S) URL without resolving its host.
 *
 * @param mixed $url Candidate URL.
 * @return string Sanitized URL or an empty string.
 */
function goodblocks_rss_sanitize_http_url( $url ): string {
	$url = esc_url_raw( trim( (string) $url ), [ 'http', 'https' ] );
	if ( '' === $url ) {
		return '';
	}

	$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
	if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
		return '';
	}

	return $url;
}

/**
 * Validate a public HTTP(S) URL before the server requests it.
 *
 * @param mixed $url Candidate URL.
 * @return string Validated URL or an empty string.
 */
function goodblocks_rss_normalize_public_url( $url ): string {
	$url = goodblocks_rss_sanitize_http_url( $url );

	return '' !== $url && wp_http_validate_url( $url ) ? $url : '';
}

/**
 * Return a normalized cache map keyed by source ID.
 *
 * @return array<string, array{items: array, fetched_at: int, last_attempt_at: int, last_error: string}>
 */
function goodblocks_rss_get_cache(): array {
	$stored = get_option( GOODBLOCKS_RSS_CACHE_OPTION, [] );
	if ( ! is_array( $stored ) ) {
		return [];
	}

	$cache = [];
	foreach ( $stored as $source_id => $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$cache[ sanitize_key( $source_id ) ] = [
			'items'           => isset( $entry['items'] ) && is_array( $entry['items'] ) ? $entry['items'] : [],
			'fetched_at'      => absint( $entry['fetched_at'] ?? 0 ),
			'last_attempt_at' => absint( $entry['last_attempt_at'] ?? 0 ),
			'last_error'      => sanitize_text_field( $entry['last_error'] ?? '' ),
		];
	}

	return $cache;
}

/**
 * Refresh every configured source, or only selected source IDs.
 *
 * @param array<int, string>|null $source_ids Optional source IDs.
 * @return array<string, array{items: array, fetched_at: int, last_attempt_at: int, last_error: string}>
 */
function goodblocks_rss_refresh_sources( ?array $source_ids = null ): array {
	$sources = goodblocks_rss_get_sources();
	$cache   = goodblocks_rss_get_cache();
	$wanted  = null === $source_ids ? null : array_fill_keys( array_map( 'sanitize_key', $source_ids ), true );

	foreach ( $sources as $source ) {
		if ( null !== $wanted && ! isset( $wanted[ $source['id'] ] ) ) {
			continue;
		}

		$cache[ $source['id'] ] = goodblocks_rss_refresh_source( $source, $cache[ $source['id'] ] ?? [] );
		update_option( GOODBLOCKS_RSS_CACHE_OPTION, $cache, false );
	}

	return $cache;
}

/**
 * Refresh one source while preserving the last successful items on failure.
 *
 * @param array{id: string, name: string, url: string, max_items: int} $source Source configuration.
 * @param array                                                       $previous Previous cache entry.
 * @return array{items: array, fetched_at: int, last_attempt_at: int, last_error: string}
 */
function goodblocks_rss_refresh_source( array $source, array $previous ): array {
	$entry = [
		'items'           => isset( $previous['items'] ) && is_array( $previous['items'] ) ? $previous['items'] : [],
		'fetched_at'      => absint( $previous['fetched_at'] ?? 0 ),
		'last_attempt_at' => absint( $previous['last_attempt_at'] ?? 0 ),
		'last_error'      => sanitize_text_field( $previous['last_error'] ?? '' ),
	];
	$lock  = 'goodblocks_rss_lock_' . substr( md5( $source['id'] ), 0, 20 );

	if ( get_transient( $lock ) ) {
		return $entry;
	}

	$entry['last_attempt_at'] = time();
	$entry['last_error']      = '';
	set_transient( $lock, 1, 2 * MINUTE_IN_SECONDS );

	try {
		$result = goodblocks_rss_fetch_source( $source );
		if ( is_wp_error( $result ) ) {
			$entry['last_error'] = sanitize_text_field( $result->get_error_message() );
			return $entry;
		}

		$entry['items']      = $result;
		$entry['fetched_at'] = time();
		return $entry;
	} finally {
		delete_transient( $lock );
	}
}

/**
 * Download and parse a configured feed.
 *
 * @param array{id: string, name: string, url: string, max_items: int} $source Source configuration.
 * @return array|WP_Error Normalized items or an error.
 */
function goodblocks_rss_fetch_source( array $source ) {
	$url = goodblocks_rss_normalize_public_url( $source['url'] ?? '' );
	if ( '' === $url ) {
		return new WP_Error( 'goodblocks_rss_unsafe_url', __( 'The feed URL is not a public HTTP or HTTPS URL.', 'goodblocks' ) );
	}

	$response = wp_safe_remote_get(
		$url,
		[
			'timeout'             => 15,
			'redirection'         => 3,
			'limit_response_size' => 2 * MB_IN_BYTES,
			'user-agent'          => 'GoodBlocks RSS/' . ( defined( 'GOODBLOCKS_VERSION' ) ? GOODBLOCKS_VERSION : 'dev' ) . '; ' . home_url( '/' ),
			'headers'             => [
				'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.5',
			],
		]
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'goodblocks_rss_http_error',
			sprintf(
				/* translators: %d: HTTP status code. */
				__( 'The feed returned HTTP status %d.', 'goodblocks' ),
				$status
			)
		);
	}

	$body = wp_remote_retrieve_body( $response );
	if ( '' === trim( $body ) ) {
		return new WP_Error( 'goodblocks_rss_empty_response', __( 'The feed returned an empty response.', 'goodblocks' ) );
	}

	return goodblocks_rss_parse_feed( $body, $source );
}

/**
 * Parse RSS or Atom XML with WordPress' bundled SimplePie library.
 *
 * @param string                                                   $xml Raw feed XML.
 * @param array{id: string, name: string, url: string, max_items: int} $source Source configuration.
 * @return array|WP_Error Normalized items or an error.
 */
function goodblocks_rss_parse_feed( string $xml, array $source ) {
	if ( ! class_exists( 'SimplePie' ) ) {
		require_once ABSPATH . WPINC . '/class-simplepie.php';
	}

	if ( ! class_exists( 'SimplePie' ) ) {
		return new WP_Error( 'goodblocks_rss_parser_missing', __( 'The WordPress feed parser is unavailable.', 'goodblocks' ) );
	}

	$feed = new SimplePie();
	$feed->enable_cache( false );
	$feed->set_raw_data( $xml );
	$feed->set_stupidly_fast( true );
	$feed->init();

	if ( $feed->error() ) {
		return new WP_Error( 'goodblocks_rss_parse_error', sanitize_text_field( $feed->error() ) );
	}

	$items = [];
	foreach ( $feed->get_items( 0, $source['max_items'] ) as $item ) {
		$normalized = goodblocks_rss_normalize_item( $item, $source );
		if ( null !== $normalized ) {
			$items[] = $normalized;
		}
	}

	return goodblocks_rss_deduplicate_items( $items );
}

/**
 * Normalize one SimplePie item.
 *
 * @param SimplePie_Item                                           $item Feed item.
 * @param array{id: string, name: string, url: string, max_items: int} $source Source configuration.
 * @return array|null
 */
function goodblocks_rss_normalize_item( $item, array $source ): ?array {
	$link = goodblocks_rss_normalize_public_url( $item->get_permalink() );
	if ( '' === $link ) {
		return null;
	}

	$title       = sanitize_text_field( wp_strip_all_tags( (string) $item->get_title() ) );
	$description = (string) ( $item->get_description() ?: $item->get_content() );
	$guid        = sanitize_text_field( (string) $item->get_id() );
	$timestamp   = absint( $item->get_date( 'U' ) );

	if ( '' === $title ) {
		$title = $source['name'];
	}

	return [
		'id'          => '' !== $guid ? $guid : sha1( $link ),
		'title'       => $title,
		'url'         => $link,
		'excerpt'     => wp_trim_words( wp_strip_all_tags( html_entity_decode( $description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ), 32, '…' ),
		'image_url'   => goodblocks_rss_extract_image_url( $item, $description ),
		'timestamp'   => $timestamp,
		'source_id'   => $source['id'],
		'source_name' => $source['name'],
	];
}

/**
 * Find a public image URL in an enclosure or item markup.
 *
 * @param SimplePie_Item $item Feed item.
 * @param string         $html Item description/content.
 * @return string
 */
function goodblocks_rss_extract_image_url( $item, string $html ): string {
	$enclosure = $item->get_enclosure();
	if ( $enclosure ) {
		$thumbnail = method_exists( $enclosure, 'get_thumbnail' ) ? $enclosure->get_thumbnail() : '';
		$thumbnail = goodblocks_rss_normalize_public_url( $thumbnail );
		if ( '' !== $thumbnail ) {
			return $thumbnail;
		}

		$type = (string) $enclosure->get_type();
		if ( str_starts_with( strtolower( $type ), 'image/' ) ) {
			$link = goodblocks_rss_normalize_public_url( $enclosure->get_link() );
			if ( '' !== $link ) {
				return $link;
			}
		}
	}

	if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches ) ) {
		return goodblocks_rss_normalize_public_url( html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	return '';
}

/**
 * De-duplicate normalized items by GUID, falling back to the permalink.
 *
 * @param array $items Normalized feed items.
 * @return array
 */
function goodblocks_rss_deduplicate_items( array $items ): array {
	$unique = [];
	$seen   = [];

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) || empty( $item['url'] ) ) {
			continue;
		}

		$key = ! empty( $item['id'] ) ? (string) $item['id'] : (string) $item['url'];
		$key = strtolower( trim( $key ) );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$unique[]     = $item;
	}

	return $unique;
}

/**
 * Return cached items for selected sources, sorted newest first.
 *
 * @param array<int, string> $source_ids Source IDs. Empty means all configured sources.
 * @param int                $limit Maximum number of items.
 * @return array
 */
function goodblocks_rss_get_items( array $source_ids = [], int $limit = 8 ): array {
	$sources = goodblocks_rss_get_sources();
	$cache   = goodblocks_rss_get_cache();
	$wanted  = array_filter( array_map( 'sanitize_key', $source_ids ) );
	$wanted  = $wanted ? array_fill_keys( $wanted, true ) : null;
	$items   = [];

	foreach ( $sources as $source ) {
		if ( null !== $wanted && ! isset( $wanted[ $source['id'] ] ) ) {
			continue;
		}

		foreach ( $cache[ $source['id'] ]['items'] ?? [] as $item ) {
			if ( is_array( $item ) ) {
				$items[] = $item;
			}
		}
	}

	$items = goodblocks_rss_deduplicate_items( $items );
	usort(
		$items,
		static fn( array $left, array $right ): int => absint( $right['timestamp'] ?? 0 ) <=> absint( $left['timestamp'] ?? 0 )
	);

	return array_slice( $items, 0, max( 1, min( 50, $limit ) ) );
}

/**
 * Register the editor-only source list endpoint.
 */
function goodblocks_rss_register_rest_routes(): void {
	register_rest_route(
		'goodblocks/v1',
		'/rss-sources',
		[
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'goodblocks_rss_rest_sources',
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
		]
	);
}

/**
 * Return safe source metadata to the block editor.
 *
 * @return WP_REST_Response
 */
function goodblocks_rss_rest_sources(): WP_REST_Response {
	$cache = goodblocks_rss_get_cache();
	$data  = [];

	foreach ( goodblocks_rss_get_sources() as $source ) {
		$entry  = $cache[ $source['id'] ] ?? [];
		$data[] = [
			'id'          => $source['id'],
			'name'        => $source['name'],
			'item_count'  => count( $entry['items'] ?? [] ),
			'fetched_at'  => absint( $entry['fetched_at'] ?? 0 ),
			'last_error'  => sanitize_text_field( $entry['last_error'] ?? '' ),
		];
	}

	return rest_ensure_response( $data );
}

/**
 * Render the RSS source registry and cache status.
 */
function goodblocks_rss_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$sources = goodblocks_rss_get_sources();
	$cache   = goodblocks_rss_get_cache();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'GoodBlocks RSS Feeds', 'goodblocks' ); ?></h1>
		<p>
			<?php
			esc_html_e(
				'Configure trusted RSS or Atom sources here. GoodBlocks fetches them on the server and keeps the last successful result if a source is temporarily unavailable.',
				'goodblocks'
			);
			?>
		</p>

		<?php settings_errors( GOODBLOCKS_RSS_SOURCES_OPTION ); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'goodblocks_rss_settings' ); ?>
			<table class="widefat striped" id="goodblocks-rss-sources">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'goodblocks' ); ?></th>
						<th><?php esc_html_e( 'Feed URL', 'goodblocks' ); ?></th>
						<th style="width: 8rem"><?php esc_html_e( 'Items to cache', 'goodblocks' ); ?></th>
						<th style="width: 7rem"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'goodblocks' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sources as $index => $source ) : ?>
						<?php goodblocks_rss_render_source_row( $index, $source ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<button type="button" class="button" id="goodblocks-rss-add-source"><?php esc_html_e( 'Add source', 'goodblocks' ); ?></button>
			</p>
			<?php submit_button( __( 'Save sources', 'goodblocks' ) ); ?>
		</form>

		<template id="goodblocks-rss-source-template">
			<?php goodblocks_rss_render_source_row( '__INDEX__', [ 'id' => '', 'name' => '', 'url' => '', 'max_items' => 20 ] ); ?>
		</template>

		<hr />
		<h2><?php esc_html_e( 'Cache status', 'goodblocks' ); ?></h2>
		<?php if ( $sources ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Source', 'goodblocks' ); ?></th>
						<th><?php esc_html_e( 'Cached items', 'goodblocks' ); ?></th>
						<th><?php esc_html_e( 'Last successful refresh', 'goodblocks' ); ?></th>
						<th><?php esc_html_e( 'Last error', 'goodblocks' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sources as $source ) : ?>
						<?php $entry = $cache[ $source['id'] ] ?? []; ?>
						<tr>
							<td><?php echo esc_html( $source['name'] ); ?></td>
							<td><?php echo esc_html( (string) count( $entry['items'] ?? [] ) ); ?></td>
							<td>
								<?php
								echo ! empty( $entry['fetched_at'] )
									? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), absint( $entry['fetched_at'] ) ) )
									: esc_html__( 'Never', 'goodblocks' );
								?>
							</td>
							<td><?php echo ! empty( $entry['last_error'] ) ? esc_html( $entry['last_error'] ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="goodblocks_rss_refresh" />
				<?php wp_nonce_field( 'goodblocks_rss_refresh' ); ?>
				<?php submit_button( __( 'Refresh all now', 'goodblocks' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'Add and save at least one source before refreshing.', 'goodblocks' ); ?></p>
		<?php endif; ?>
	</div>
	<script>
	(() => {
		const table = document.querySelector('#goodblocks-rss-sources tbody');
		const addButton = document.querySelector('#goodblocks-rss-add-source');
		const template = document.querySelector('#goodblocks-rss-source-template');
		if (!table || !addButton || !template) return;

		const bindRemove = (root) => {
			root.querySelectorAll('.goodblocks-rss-remove-source').forEach((button) => {
				button.addEventListener('click', () => button.closest('tr')?.remove(), { once: true });
			});
		};

		bindRemove(table);
		addButton.addEventListener('click', () => {
			const index = `${Date.now()}-${table.children.length}`;
			const html = template.innerHTML.replaceAll('__INDEX__', index);
			table.insertAdjacentHTML('beforeend', html);
			bindRemove(table.lastElementChild);
		});
	})();
	</script>
	<?php
}

/**
 * Render one editable source row.
 *
 * @param int|string $index Form row index.
 * @param array      $source Source data.
 */
function goodblocks_rss_render_source_row( $index, array $source ): void {
	$name = GOODBLOCKS_RSS_SOURCES_OPTION . '[' . $index . ']';
	?>
	<tr>
		<td>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $source['id'] ?? '' ); ?>" />
			<input
				type="text"
				class="regular-text"
				required
				name="<?php echo esc_attr( $name ); ?>[name]"
				value="<?php echo esc_attr( $source['name'] ?? '' ); ?>"
			/>
		</td>
		<td>
			<input
				type="url"
				class="large-text"
				required
				placeholder="https://example.com/feed/"
				name="<?php echo esc_attr( $name ); ?>[url]"
				value="<?php echo esc_attr( $source['url'] ?? '' ); ?>"
			/>
		</td>
		<td>
			<input
				type="number"
				min="1"
				max="50"
				step="1"
				class="small-text"
				name="<?php echo esc_attr( $name ); ?>[max_items]"
				value="<?php echo esc_attr( $source['max_items'] ?? 20 ); ?>"
			/>
		</td>
		<td>
			<button type="button" class="button-link-delete goodblocks-rss-remove-source">
				<?php esc_html_e( 'Remove', 'goodblocks' ); ?>
			</button>
		</td>
	</tr>
	<?php
}

/**
 * Process an explicit administrator refresh.
 */
function goodblocks_rss_handle_manual_refresh(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to refresh these feeds.', 'goodblocks' ) );
	}

	check_admin_referer( 'goodblocks_rss_refresh' );
	goodblocks_rss_refresh_sources();
	wp_safe_redirect( admin_url( 'options-general.php?page=goodblocks-rss-feeds' ) );
	exit;
}

/**
 * Schedule hourly server-side refreshes, including after an in-place update.
 */
function goodblocks_rss_ensure_cron(): void {
	if ( ! wp_next_scheduled( GOODBLOCKS_RSS_CRON_HOOK ) ) {
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', GOODBLOCKS_RSS_CRON_HOOK );
	}
}

/**
 * Remove scheduled refreshes on plugin deactivation.
 */
function goodblocks_rss_clear_cron(): void {
	wp_clear_scheduled_hook( GOODBLOCKS_RSS_CRON_HOOK );
}

/**
 * Remove cache entries for deleted sources.
 *
 * @param mixed $old_value Previous option value.
 * @param mixed $new_value New option value.
 */
function goodblocks_rss_sources_updated( $old_value, $new_value ): void {
	unset( $old_value );
	$cache      = goodblocks_rss_get_cache();
	$source_ids = [];

	foreach ( is_array( $new_value ) ? $new_value : [] as $source ) {
		if ( is_array( $source ) && ! empty( $source['id'] ) ) {
			$source_ids[ sanitize_key( $source['id'] ) ] = true;
		}
	}

	$cache = array_intersect_key( $cache, $source_ids );
	update_option( GOODBLOCKS_RSS_CACHE_OPTION, $cache, false );

	if ( $source_ids ) {
		wp_schedule_single_event( time() + 5, GOODBLOCKS_RSS_CRON_HOOK );
	}
}
