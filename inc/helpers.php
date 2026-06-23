<?php
/**
 * GoodBlocks helper functions.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get post thumbnail or fall back to the site-wide default image.
 *
 * @param string $size Image size.
 * @param array  $attr Image attributes.
 * @return string Image HTML.
 */
function goodblocks_get_thumbnail( $size = 'large', $attr = array() ) {
	$post = get_post();

	if ( has_post_thumbnail( $post ) ) {
		return get_the_post_thumbnail( $post, $size, $attr );
	}

	$default_image = get_option( 'goodblocks_default_image', '' );

	if ( $default_image ) {
		return wp_get_attachment_image( $default_image, $size );
	}

	return '';
}

/**
 * Resolve an attachment ID from an image URL, including generated image sizes.
 *
 * @param string $url Image URL.
 * @return int Attachment ID, or 0 when not found.
 */
function goodblocks_attachment_id_from_url( string $url ): int {
	$url = esc_url_raw( $url );

	if ( ! $url ) {
		return 0;
	}

	$attachment_id = attachment_url_to_postid( $url );
	if ( $attachment_id ) {
		return absint( $attachment_id );
	}

	$uploads = wp_get_upload_dir();
	if ( empty( $uploads['baseurl'] ) ) {
		return 0;
	}

	$url_path     = (string) wp_parse_url( $url, PHP_URL_PATH );
	$baseurl_path = (string) wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );

	if ( ! $url_path || ! $baseurl_path || 0 !== strpos( $url_path, $baseurl_path ) ) {
		return 0;
	}

	$relative_path = ltrim( substr( $url_path, strlen( $baseurl_path ) ), '/' );
	$relative_path = preg_replace( '/-\d+x\d+(?=\.[a-zA-Z0-9]+$)/', '', $relative_path );
	$original_url  = trailingslashit( $uploads['baseurl'] ) . $relative_path;

	return absint( attachment_url_to_postid( $original_url ) );
}

/**
 * Get the first image attachment used in post content.
 *
 * @param int $post_id Post ID.
 * @return int Attachment ID, or 0 when not found.
 */
function goodblocks_get_first_content_image_id( int $post_id ): int {
	$content = get_post_field( 'post_content', $post_id );

	if ( ! $content ) {
		return 0;
	}

	if ( has_blocks( $content ) ) {
		$find_image_id = static function ( array $blocks ) use ( &$find_image_id ): int {
			foreach ( $blocks as $content_block ) {
				$block_name = $content_block['blockName'] ?? '';
				$attrs      = $content_block['attrs'] ?? [];

				if ( 'core/image' === $block_name && ! empty( $attrs['id'] ) ) {
					return absint( $attrs['id'] );
				}

				if ( 'core/gallery' === $block_name && ! empty( $attrs['ids'] ) && is_array( $attrs['ids'] ) ) {
					return absint( reset( $attrs['ids'] ) );
				}

				if ( ! empty( $content_block['innerBlocks'] ) ) {
					$image_id = $find_image_id( $content_block['innerBlocks'] );
					if ( $image_id ) {
						return $image_id;
					}
				}
			}

			return 0;
		};

		$image_id = $find_image_id( parse_blocks( $content ) );
		if ( $image_id ) {
			return $image_id;
		}
	}

	preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches );
	foreach ( $matches[1] ?? [] as $image_url ) {
		$image_id = goodblocks_attachment_id_from_url( $image_url );
		if ( $image_id ) {
			return $image_id;
		}
	}

	return 0;
}

/**
 * Load a block template with theme override support.
 *
 * Lookup order:
 *  1. Child theme:  goodblocks/templates/{block}/{template}.php
 *  2. Parent theme: goodblocks/templates/{block}/{template}.php
 *  3. Plugin:       src/blocks/{block}/templates/{template}.php
 *
 * @param string $block         Block slug (e.g. 'post-grid').
 * @param string $template_name Template name without .php (e.g. 'grid').
 * @param array  $attributes    Block attributes passed to the template.
 */
function goodblocks_template( string $block, string $template_name, array $attributes = array() ): void {
	$template_path = '';
	$template_file = 'goodblocks/templates/' . $block . '/' . $template_name . '.php';

	// Check child theme first.
	if ( is_child_theme() ) {
		$child_path = get_stylesheet_directory() . '/' . $template_file;
		if ( file_exists( $child_path ) ) {
			$template_path = $child_path;
		}
	}

	// Check parent theme.
	if ( ! $template_path ) {
		$parent_path = get_template_directory() . '/' . $template_file;
		if ( file_exists( $parent_path ) ) {
			$template_path = $parent_path;
		}
	}

	// Fallback to plugin template (check build first, then src).
	if ( ! $template_path ) {
		$build_path = GOODBLOCKS_DIR . 'build/blocks/' . $block . '/templates/' . $template_name . '.php';
		$src_path   = GOODBLOCKS_DIR . 'src/blocks/' . $block . '/templates/' . $template_name . '.php';

		if ( file_exists( $build_path ) ) {
			$template_path = $build_path;
		} elseif ( file_exists( $src_path ) ) {
			$template_path = $src_path;
		}
	}

	$template_path = apply_filters( 'goodblocks_template_path', $template_path, $block, $template_name, $attributes );

	if ( $template_path && file_exists( $template_path ) ) {
		( static function ( $__template_path, $attributes ) {
			include $__template_path;
		} )( $template_path, $attributes );
	}
}
