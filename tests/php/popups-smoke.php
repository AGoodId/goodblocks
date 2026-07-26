<?php

declare( strict_types=1 );

/** Standalone smoke tests for GoodBlocks Campaign Popup rules. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

class WP_Post {
	public int $ID;

	public function __construct( int $id ) {
		$this->ID = $id;
	}
}

function add_action(): void {}
function add_filter(): void {}
function register_post_type(): void {}
function absint( $value ): int { return abs( (int) $value ); }
function sanitize_key( $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function get_post_meta( int $id, string $key, bool $single = false ) { return $GLOBALS['popup_test_meta'][ $id ][ $key ] ?? ''; }
function get_queried_object_id(): int { return $GLOBALS['popup_test_page']; }
function current_time( string $type ): int { return 1782900000; }
function wp_date( string $format, int $timestamp ): string { return '2026-07-01T12:00'; }

function popup_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

require_once dirname( __DIR__, 2 ) . '/inc/popup-cpt.php';

popup_assert_same( [ 2, 7 ], goodblocks_popup_sanitize_target_pages( [ '2', 0, '7', '2' ] ), 'Page IDs should be normalized and deduplicated.' );
popup_assert_same( '2026-07-01T09:30', goodblocks_popup_sanitize_datetime( '2026-07-01T09:30' ), 'Valid campaign datetime should be retained.' );
popup_assert_same( '', goodblocks_popup_sanitize_datetime( 'tomorrow' ), 'Invalid campaign datetime should fail closed.' );

$GLOBALS['popup_test_page'] = 42;
$GLOBALS['popup_test_meta'] = [
	1 => [
		'_popup_display_mode' => 'include',
		'_popup_target_pages' => [ 42 ],
		'_popup_start_at'     => '2026-07-01T10:00',
		'_popup_end_at'       => '2026-07-01T14:00',
	],
	2 => [
		'_popup_display_mode' => 'exclude',
		'_popup_target_pages' => [ 42 ],
	],
];

popup_assert_same( true, goodblocks_popup_is_eligible( new WP_Post( 1 ) ), 'Included page during the campaign window should be eligible.' );
popup_assert_same( false, goodblocks_popup_is_eligible( new WP_Post( 2 ) ), 'Excluded page should not be eligible.' );

echo "Campaign popup smoke tests passed.\n";
