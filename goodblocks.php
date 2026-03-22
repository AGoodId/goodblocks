<?php
/**
 * Plugin Name: GoodBlocks
 * Plugin URI: https://agoodsite.se
 * Description: Reusable Gutenberg blocks: Masonry Query, Search Autocomplete, Image Compare, Feature Card, Countdown, Quiz, Page List, Double Container, Media Grid, and Mailchimp Signup.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: AGoodId
 * Author URI: https://agoodid.se
 * License: GPL-2.0-or-later
 * Text Domain: goodblocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GOODBLOCKS_VERSION', '1.0.0' );
define( 'GOODBLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GOODBLOCKS_URI', plugin_dir_url( __FILE__ ) );

// Masonry Query REST API for load-more pagination.
require_once GOODBLOCKS_DIR . 'inc/masonry-rest-api.php';

/**
 * Register custom blocks.
 */
function goodblocks_register_blocks() {
	$blocks = [
		'masonry-query',
		'card-feature',
		'search-autocomplete',
		'image-compare',
		'countdown',
		'quiz',
		'page-list',
		'double-container-text',
		'media-grid',
		'media-grid-item',
		'mailchimp-signup',
	];

	foreach ( $blocks as $block ) {
		$block_path = GOODBLOCKS_DIR . 'build/blocks/' . $block;
		if ( file_exists( $block_path . '/block.json' ) ) {
			register_block_type( $block_path );
		}
	}
}
add_action( 'init', 'goodblocks_register_blocks' );

/**
 * Register block category.
 */
function goodblocks_block_category( $categories ) {
	// Only add if not already present (theme may define it).
	foreach ( $categories as $cat ) {
		if ( $cat['slug'] === 'goodblocks' ) {
			return $categories;
		}
	}

	return array_merge(
		[
			[
				'slug'  => 'goodblocks',
				'title' => 'GoodBlocks',
				'icon'  => 'star-filled',
			],
		],
		$categories
	);
}
add_filter( 'block_categories_all', 'goodblocks_block_category' );

/**
 * Pass translatable strings to block view scripts.
 */
function goodblocks_localize_scripts() {
	wp_localize_script( 'goodblocks-masonry-query-view-script', 'goodblocksMasonry', [
		'i18n' => [
			'readMore'  => __( 'Read more', 'goodblocks' ),
			'prev'      => __( 'Previous', 'goodblocks' ),
			'next'      => __( 'Next', 'goodblocks' ),
			'close'     => __( 'Close', 'goodblocks' ),
			'loadError' => __( 'Could not load more items. Try again.', 'goodblocks' ),
			'retry'     => __( 'Try again', 'goodblocks' ),
		],
	] );

	wp_localize_script( 'goodblocks-search-autocomplete-view-script', 'goodblocksSearch', [
		'i18n' => [
			'viewAll'   => __( 'View all results for "%s"', 'goodblocks' ),
			'noResults' => __( 'No results for "%s"', 'goodblocks' ),
			'error'     => __( 'An error occurred. Please try again.', 'goodblocks' ),
		],
	] );
}
add_action( 'wp_enqueue_scripts', 'goodblocks_localize_scripts', 20 );

// ── Namespace migration on activation/deactivation ──────────────────────────

/**
 * On activation: migrate block namespace from agoodsite-fse/* to goodblocks/*.
 */
function goodblocks_activate() {
	goodblocks_migrate_namespace( 'agoodsite-fse', 'goodblocks' );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'goodblocks_activate' );

/**
 * On deactivation: roll back block namespace from goodblocks/* to agoodsite-fse/*.
 */
function goodblocks_deactivate() {
	goodblocks_migrate_namespace( 'goodblocks', 'agoodsite-fse' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'goodblocks_deactivate' );

/**
 * Migrate block namespaces in all post content.
 *
 * @param string $from Old namespace prefix.
 * @param string $to   New namespace prefix.
 */
function goodblocks_migrate_namespace( string $from, string $to ): void {
	global $wpdb;

	$blocks = [
		'masonry-query',
		'card-feature',
		'search-autocomplete',
		'image-compare',
		'countdown',
		'quiz',
		'page-list',
		'double-container-text',
		'media-grid',
		'media-grid-item',
		'mailchimp-signup',
	];

	foreach ( $blocks as $block ) {
		$old = 'wp:' . $from . '/' . $block;
		$new = 'wp:' . $to . '/' . $block;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
				$old,
				$new,
				'%' . $wpdb->esc_like( $old ) . '%'
			)
		);
	}

	// Clear object cache so WP picks up the changes.
	wp_cache_flush();
}
