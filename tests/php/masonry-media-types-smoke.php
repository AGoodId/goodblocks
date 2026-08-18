<?php

declare( strict_types=1 );

/** Standalone smoke tests for masonry-query media type resolution. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

function add_action(): void {}
function add_filter(): void {}
function add_post_type_support(): void {}

/** Attachment metadata keyed by attachment ID. */
function wp_get_attachment_metadata( $post_id = 0, $unfiltered = false ) {
	return $GLOBALS['masonry_test_meta'][ (int) $post_id ] ?? false;
}

function sanitize_key( $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

/** Poster IDs keyed by attachment ID; anything absent has no poster. */
function get_post_thumbnail_id( $post = null ) {
	return $GLOBALS['masonry_test_posters'][ (int) $post ] ?? 0;
}

require_once __DIR__ . '/../../inc/helpers.php';

$failures = 0;

function masonry_assert_same( $expected, $actual, string $message ): void {
	global $failures;
	if ( $expected === $actual ) {
		echo "  ok   {$message}\n";
		return;
	}
	$failures++;
	echo "  FAIL {$message}\n";
	echo '       expected: ' . var_export( $expected, true ) . "\n";
	echo '       actual:   ' . var_export( $actual, true ) . "\n";
}

echo "goodblocks_masonry_mime_types()\n";

// Backwards compatibility: sites that never set the attribute stay image-only.
masonry_assert_same( [ 'image' ], goodblocks_masonry_mime_types( null ), 'null defaults to image only' );
masonry_assert_same( [ 'image' ], goodblocks_masonry_mime_types( [] ), 'empty array defaults to image only' );
masonry_assert_same( [ 'image' ], goodblocks_masonry_mime_types( [ 'image' ] ), 'explicit image stays image only' );

masonry_assert_same( [ 'image', 'video' ], goodblocks_masonry_mime_types( [ 'image', 'video' ] ), 'image + video passes both through' );
masonry_assert_same( [ 'video' ], goodblocks_masonry_mime_types( [ 'video' ] ), 'video only is honoured' );

// Order is normalised so the query args are stable regardless of checkbox order.
masonry_assert_same( [ 'image', 'video' ], goodblocks_masonry_mime_types( [ 'video', 'image' ] ), 'order is normalised' );

// Untrusted input arrives straight from a REST payload.
masonry_assert_same( [ 'image' ], goodblocks_masonry_mime_types( [ 'application/pdf' ] ), 'unknown type falls back to image' );
masonry_assert_same( [ 'video' ], goodblocks_masonry_mime_types( [ 'VIDEO' ] ), 'case is normalised' );
masonry_assert_same( [ 'image' ], goodblocks_masonry_mime_types( 'image' ), 'scalar is accepted' );
masonry_assert_same( [ 'image' ], goodblocks_masonry_mime_types( [ 'audio', 'text' ] ), 'all-unknown falls back to image' );

echo "\ngoodblocks_video_poster_id()\n";

$GLOBALS['masonry_test_posters'] = [ 42 => 99 ];
masonry_assert_same( 99, goodblocks_video_poster_id( 42 ), 'returns the poster set in the media library' );
masonry_assert_same( 0, goodblocks_video_poster_id( 43 ), 'returns 0 when no poster is set' );

echo "\ngoodblocks_video_duration()\n";

$GLOBALS['masonry_test_meta'] = [
	10 => [ 'length_formatted' => '0:16' ],
	11 => [ 'length' => 84 ],
	12 => [ 'length_formatted' => '' ],
];

masonry_assert_same( '0:16', goodblocks_video_duration( 10 ), 'reads length_formatted from attachment metadata' );
masonry_assert_same( '', goodblocks_video_duration( 11 ), 'formats without a duration yield empty string' );
masonry_assert_same( '', goodblocks_video_duration( 12 ), 'empty length_formatted yields empty string' );
masonry_assert_same( '', goodblocks_video_duration( 99 ), 'missing metadata yields empty string' );

echo "\n";
if ( $failures > 0 ) {
	echo "{$failures} failure(s)\n";
	exit( 1 );
}
echo "All masonry media type smoke tests passed.\n";
