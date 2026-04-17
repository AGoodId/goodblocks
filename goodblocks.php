<?php
/**
 * Plugin Name: GoodBlocks
 * Plugin URI: https://agoodsite.se
 * Description: Reusable Gutenberg blocks: Masonry Query, Post Grid, Search Autocomplete, Image Compare, Feature Card, Countdown, Quiz, Page List, Double Container, Media Grid, and Mailchimp Signup.
 * Version: 1.5.0
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

define( 'GOODBLOCKS_VERSION', '1.5.0' );
define( 'GOODBLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GOODBLOCKS_URI', plugin_dir_url( __FILE__ ) );

// REST API endpoints.
require_once GOODBLOCKS_DIR . 'inc/masonry-rest-api.php';
require_once GOODBLOCKS_DIR . 'inc/search-rest-api.php';

// AGoodApp Media Picker integration.
require_once GOODBLOCKS_DIR . 'inc/agoodapp-settings.php';
require_once GOODBLOCKS_DIR . 'inc/agoodapp-proxy.php';
require_once GOODBLOCKS_DIR . 'inc/agoodapp-sideload.php';
require_once GOODBLOCKS_DIR . 'inc/agoodapp-import.php';

// Helper functions (template loader, thumbnail fallback).
require_once GOODBLOCKS_DIR . 'inc/helpers.php';

// GitHub-based auto-updater.
require_once GOODBLOCKS_DIR . 'inc/github-updater.php';
new GoodBlocks_GitHub_Updater( __FILE__, 'AGoodId/goodblocks' );

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
		'post-grid',
		'hero',
		'slider',
		'slide',
		'product-carousel',
	];

	foreach ( $blocks as $block ) {
		$block_path = GOODBLOCKS_DIR . 'build/blocks/' . $block;
		if ( file_exists( $block_path . '/block.json' ) ) {
			register_block_type( $block_path );
		}
	}

	// AGoodApp Media Picker uses a separate namespace (agoodapp/media-picker).
	$agoodapp_block = GOODBLOCKS_DIR . 'build/blocks/agoodapp-media-picker';
	if ( file_exists( $agoodapp_block . '/block.json' ) ) {
		register_block_type( $agoodapp_block );
	}
}
add_action( 'init', 'goodblocks_register_blocks' );

/**
 * Run migrations on update (version check).
 */
function goodblocks_maybe_migrate() {
	$stored = get_option( 'goodblocks_version', '0' );
	if ( version_compare( $stored, GOODBLOCKS_VERSION, '>=' ) ) {
		return;
	}

	if ( version_compare( $stored, '1.1.1', '<' ) ) {
		goodblocks_migrate_namespace( 'agoodsite-fse', 'goodblocks' );
		goodblocks_migrate_agoodblocks();
	}

	update_option( 'goodblocks_version', GOODBLOCKS_VERSION );
}
add_action( 'plugins_loaded', 'goodblocks_maybe_migrate' );

/**
 * Enable categories and tags on Pages.
 *
 * Opt-in: only active when the `goodblocks_page_taxonomies` filter returns true
 * (defaults to true). Themes or other plugins can disable it with:
 *     add_filter( 'goodblocks_page_taxonomies', '__return_false' );
 */
function goodblocks_register_page_taxonomies() {
	if ( ! apply_filters( 'goodblocks_page_taxonomies', true ) ) {
		return;
	}

	register_taxonomy_for_object_type( 'category', 'page' );
	register_taxonomy_for_object_type( 'post_tag', 'page' );
}
add_action( 'init', 'goodblocks_register_page_taxonomies' );

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
	goodblocks_migrate_agoodblocks();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'goodblocks_activate' );

/**
 * On deactivation: roll back block namespace from goodblocks/* to agoodsite-fse/*.
 */
function goodblocks_deactivate() {
	goodblocks_migrate_namespace( 'goodblocks', 'agoodsite-fse' );
	goodblocks_rollback_agoodblocks();
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
		'post-grid',
		'hero',
		'slider',
		'slide',
		'product-carousel',
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

/**
 * Migrate agoodblocks namespace to goodblocks.
 *
 * Handles block name changes (e.g. post-grid-block → post-grid).
 */
function goodblocks_migrate_agoodblocks(): void {
	global $wpdb;

	$mapping = [
		'post-grid-block' => 'post-grid',
	];

	foreach ( $mapping as $old_name => $new_name ) {
		$old = 'wp:agoodblocks/' . $old_name;
		$new = 'wp:goodblocks/' . $new_name;

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

	wp_cache_flush();
}

/**
 * Roll back goodblocks namespace to agoodblocks on deactivation.
 */
function goodblocks_rollback_agoodblocks(): void {
	global $wpdb;

	$mapping = [
		'post-grid' => 'post-grid-block',
	];

	foreach ( $mapping as $old_name => $new_name ) {
		$old = 'wp:goodblocks/' . $old_name;
		$new = 'wp:agoodblocks/' . $new_name;

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

	wp_cache_flush();
}
