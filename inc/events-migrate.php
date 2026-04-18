<?php
/**
 * GoodBlocks Events — WP-CLI migration from The Events Calendar.
 *
 * Usage:
 *   wp goodblocks migrate-events           # live run
 *   wp goodblocks migrate-events --dry-run # preview only
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

WP_CLI::add_command( 'goodblocks migrate-events', 'goodblocks_cli_migrate_events' );

/**
 * Migrate tribe_events posts to goodblocks_event.
 *
 * Maps _EventStartDate → _event_start and _EventEndDate → _event_end,
 * then changes the post_type to goodblocks_event.
 *
 * ## OPTIONS
 *
 * [--dry-run]
 * : Preview changes without writing to the database.
 *
 * ## EXAMPLES
 *
 *     wp goodblocks migrate-events --dry-run
 *     wp goodblocks migrate-events
 */
function goodblocks_cli_migrate_events( array $args, array $assoc_args ): void {
	$dry_run = ! empty( $assoc_args['dry-run'] );

	if ( $dry_run ) {
		WP_CLI::log( '--- DRY RUN — no changes will be written ---' );
	}

	$query = new WP_Query( [
		'post_type'      => 'tribe_events',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	] );

	if ( ! $query->found_posts ) {
		WP_CLI::warning( 'No tribe_events posts found. Nothing to migrate.' );
		return;
	}

	WP_CLI::log( "Found {$query->found_posts} tribe_events post(s)." );

	$migrated = 0;
	$skipped  = 0;

	foreach ( $query->posts as $post_id ) {
		$title     = get_the_title( $post_id );
		$tec_start = get_post_meta( $post_id, '_EventStartDate', true );
		$tec_end   = get_post_meta( $post_id, '_EventEndDate', true );

		if ( ! $tec_start ) {
			WP_CLI::warning( "  SKIP  #{$post_id} \"{$title}\" — missing _EventStartDate." );
			$skipped++;
			continue;
		}

		WP_CLI::log( "  #{$post_id} \"{$title}\"" );
		WP_CLI::log( "    _EventStartDate = {$tec_start}  →  _event_start" );
		WP_CLI::log( "    _EventEndDate   = {$tec_end}  →  _event_end" );
		WP_CLI::log( "    post_type: tribe_events → goodblocks_event" );

		if ( ! $dry_run ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$wpdb->posts,
				[ 'post_type' => 'goodblocks_event' ],
				[ 'ID' => $post_id ],
				[ '%s' ],
				[ '%d' ]
			);

			update_post_meta( $post_id, '_event_start', $tec_start );
			update_post_meta( $post_id, '_event_end', $tec_end );
		}

		$migrated++;
	}

	WP_CLI::log( '' );

	if ( $dry_run ) {
		WP_CLI::success( "Dry run complete. Would migrate {$migrated} event(s), skip {$skipped}." );
		WP_CLI::log( 'Run without --dry-run to apply changes.' );
	} else {
		wp_cache_flush();
		WP_CLI::success( "Migrated {$migrated} event(s). Skipped: {$skipped}. Cache flushed." );
	}
}
