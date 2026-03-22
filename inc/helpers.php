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
