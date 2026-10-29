<?php

declare( strict_types=1 );

/** Smoke tests for GoodBlocks RSS source and cache normalization. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'GOODBLOCKS_DIR' ) ) {
	define( 'GOODBLOCKS_DIR', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

$GLOBALS['_goodblocks_rss_test_options'] = [];
$GLOBALS['_goodblocks_rss_test_errors']  = [];
$GLOBALS['_goodblocks_rss_test_transients'] = [];

function add_action( ...$args ): void {
	unset( $args );
}

function __( string $text ): string {
	return $text;
}

function wp_unslash( $value ) {
	return $value;
}

function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_key( $value ): string {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function absint( $value ): int {
	return abs( (int) $value );
}

function esc_url_raw( $value, array $protocols = [] ): string {
	$url = filter_var( (string) $value, FILTER_SANITIZE_URL ) ?: '';
	if ( '' === $url ) {
		return '';
	}

	$scheme = strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) );
	if ( $protocols && ! in_array( $scheme, $protocols, true ) ) {
		return '';
	}

	return $url;
}

function wp_http_validate_url( string $url ) {
	$host = strtolower( (string) parse_url( $url, PHP_URL_HOST ) );
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || in_array( $host, [ 'localhost', '127.0.0.1' ], true ) ) {
		return false;
	}

	return $url;
}

function wp_parse_url( string $url, int $component = -1 ) {
	return parse_url( $url, $component );
}

function add_settings_error( string $setting, string $code, string $message ): void {
	$GLOBALS['_goodblocks_rss_test_errors'][] = compact( 'setting', 'code', 'message' );
}

function get_option( string $name, $default = false ) {
	return $GLOBALS['_goodblocks_rss_test_options'][ $name ] ?? $default;
}

function update_option( string $name, $value, $autoload = null ): bool {
	unset( $autoload );
	$GLOBALS['_goodblocks_rss_test_options'][ $name ] = $value;
	return true;
}

function get_transient( string $name ) {
	return $GLOBALS['_goodblocks_rss_test_transients'][ $name ] ?? false;
}

function set_transient( string $name, $value, int $expiration ): bool {
	unset( $expiration );
	$GLOBALS['_goodblocks_rss_test_transients'][ $name ] = $value;
	return true;
}

function delete_transient( string $name ): bool {
	unset( $GLOBALS['_goodblocks_rss_test_transients'][ $name ] );
	return true;
}

function wp_strip_all_tags( string $value ): string {
	return strip_tags( $value );
}

function wp_trim_words( string $value, int $count, string $more = '…' ): string {
	$words = preg_split( '/\s+/', trim( $value ) ) ?: [];
	return count( $words ) > $count ? implode( ' ', array_slice( $words, 0, $count ) ) . $more : implode( ' ', $words );
}

function goodblocks_rss_test_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		fwrite( STDERR, 'Expected: ' . var_export( $expected, true ) . PHP_EOL );
		fwrite( STDERR, 'Actual:   ' . var_export( $actual, true ) . PHP_EOL );
		exit( 1 );
	}
}

final class GoodBlocks_Rss_Test_Item {
	public function get_permalink(): string {
		return 'https://example.com/news/story';
	}

	public function get_title(): string {
		return '<strong>Safe title</strong>';
	}

	public function get_description(): string {
		return '<p>Short description.</p><img src="https://cdn.example.com/story.jpg">';
	}

	public function get_content(): string {
		return '';
	}

	public function get_id(): string {
		return 'story-1';
	}

	public function get_date( string $format ): string {
		unset( $format );
		return '1700000000';
	}

	public function get_enclosure() {
		return null;
	}
}

require_once GOODBLOCKS_DIR . 'inc/rss-feed.php';

$sources = goodblocks_rss_sanitize_sources(
	[
		[
			'name'      => 'Svensk Discgolf',
			'url'       => 'https://www.svenskdiscgolf.se/nyheter/feed',
			'max_items' => 200,
		],
		[
			'name' => 'Duplicate',
			'url'  => 'https://www.svenskdiscgolf.se/nyheter/feed',
		],
		[
			'name' => 'Private network',
			'url'  => 'http://127.0.0.1/feed',
		],
	]
);

goodblocks_rss_test_assert_same( 1, count( $sources ), 'Duplicate and unsafe source URLs should be rejected.' );
goodblocks_rss_test_assert_same( 'svenskdiscgolf', $sources[0]['id'], 'A stable source ID should be generated from the name.' );
goodblocks_rss_test_assert_same( 50, $sources[0]['max_items'], 'Per-source cache limits should be clamped.' );
goodblocks_rss_test_assert_same( 1, count( $GLOBALS['_goodblocks_rss_test_errors'] ), 'The invalid source should produce a settings error.' );

$normalized = goodblocks_rss_normalize_item(
	new GoodBlocks_Rss_Test_Item(),
	[ 'id' => 'news', 'name' => 'News', 'url' => 'https://example.com/feed', 'max_items' => 20 ]
);
goodblocks_rss_test_assert_same( 'Safe title', $normalized['title'], 'Feed titles should be stripped to plain text.' );
goodblocks_rss_test_assert_same( 'https://cdn.example.com/story.jpg', $normalized['image_url'], 'A public item image should be retained.' );
goodblocks_rss_test_assert_same( 1700000000, $normalized['timestamp'], 'Feed dates should be normalized to timestamps.' );

$locked_source = [ 'id' => 'locked', 'name' => 'Locked', 'url' => 'https://example.com/feed', 'max_items' => 20 ];
$locked_entry  = [
	'items'           => [ [ 'id' => 'old', 'url' => 'https://example.com/old' ] ],
	'fetched_at'      => 100,
	'last_attempt_at' => 90,
	'last_error'      => 'Previous error',
];
$lock_key = 'goodblocks_rss_lock_' . substr( md5( 'locked' ), 0, 20 );
$GLOBALS['_goodblocks_rss_test_transients'][ $lock_key ] = 1;
goodblocks_rss_test_assert_same(
	$locked_entry,
	goodblocks_rss_refresh_source( $locked_source, $locked_entry ),
	'An overlapping refresh should leave the existing cache status untouched.'
);

$GLOBALS['_goodblocks_rss_test_options'][ GOODBLOCKS_RSS_SOURCES_OPTION ] = [
	[
		'id'        => 'disc-golf',
		'name'      => 'Disc Golf',
		'url'       => 'https://example.com/disc-golf.xml',
		'max_items' => 20,
	],
	[
		'id'        => 'ultimate',
		'name'      => 'Ultimate',
		'url'       => 'https://example.com/ultimate.xml',
		'max_items' => 20,
	],
];

$GLOBALS['_goodblocks_rss_test_options'][ GOODBLOCKS_RSS_CACHE_OPTION ] = [
	'disc-golf' => [
		'items' => [
			[ 'id' => 'shared', 'url' => 'https://example.com/shared', 'title' => 'Shared', 'timestamp' => 100 ],
			[ 'id' => 'old', 'url' => 'https://example.com/old', 'title' => 'Old', 'timestamp' => 50 ],
		],
		'fetched_at' => 100,
	],
	'ultimate' => [
		'items' => [
			[ 'id' => 'new', 'url' => 'https://example.com/new', 'title' => 'New', 'timestamp' => 200 ],
			[ 'id' => 'shared', 'url' => 'https://example.com/shared-copy', 'title' => 'Duplicate', 'timestamp' => 150 ],
		],
		'fetched_at' => 200,
	],
];

$items = goodblocks_rss_get_items( [], 10 );
goodblocks_rss_test_assert_same( [ 'New', 'Shared', 'Old' ], array_column( $items, 'title' ), 'Items should be de-duplicated and sorted newest first.' );

$ultimate_items = goodblocks_rss_get_items( [ 'ultimate' ], 10 );
goodblocks_rss_test_assert_same( [ 'New', 'Duplicate' ], array_column( $ultimate_items, 'title' ), 'Source selection should limit the returned items.' );

fwrite( STDOUT, "RSS feed smoke tests passed.\n" );
