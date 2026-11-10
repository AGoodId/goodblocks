<?php
/**
 * Run: php tests/php/post-grid-event-date-smoke.php /path/to/wordpress/wp-includes
 *
 * Use the real WordPress action dispatcher without a database.
 */

if ( empty( $argv[1] ) || ! is_file( $argv[1] . '/plugin.php' ) ) {
	fwrite( STDERR, "Provide the path to wp-includes.\n" );
	exit( 1 );
}

define( 'ABSPATH', dirname( $argv[1] ) . '/' );
require $argv[1] . '/plugin.php';
require __DIR__ . '/../../inc/helpers.php';

function get_the_ID() {
	return 42;
}

function get_post_type() {
	return $GLOBALS['post_type'];
}

function get_the_date() {
	return '10 November 2026';
}

function absint( $value ) {
	return abs( (int) $value );
}

function date_i18n( $format ) {
	return gmdate( $format, strtotime( '2026-11-10 UTC' ) );
}

function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function tribe_event_is_all_day( $id ) {
	return true;
}

function tribe_get_start_date( $id, $display_time, $format ) {
	return gmdate( $format, strtotime( '2026-11-13 UTC' ) );
}

function tribe_get_end_date( $id, $display_time, $format ) {
	return gmdate( $format, strtotime( $GLOBALS['end_date'] . ' UTC' ) );
}

function render_date( $layout, $post_type, $expected ) {
	$GLOBALS['post_type'] = $post_type;
	$attributes = array(
		'postType'          => $post_type,
		'showDate'          => true,
		'showFeaturedImage' => false,
		'showTitle'         => false,
		'showAuthor'        => false,
		'showExcerpt'       => false,
	);

	ob_start();
	require __DIR__ . "/../../build/blocks/post-grid/templates/{$layout}.php";
	$html = ob_get_clean();

	preg_match_all( '~<div class="post-date(?: event-post-date)?">([^<]*)</div>~', $html, $matches );
	if ( array( $expected ) !== $matches[1] ) {
		fwrite( STDERR, "$layout / $post_type: expected '$expected', got " . json_encode( $matches[1] ) . "\n" );
		exit( 1 );
	}
}

foreach ( array( 'grid', 'list' ) as $layout ) {
	$GLOBALS['end_date'] = '2026-11-13';
	render_date( $layout, 'tribe_events', '13 November' );
	$GLOBALS['end_date'] = '2027-02-28';
	render_date( $layout, 'tribe_events', '13 November-28 February 2027' );
	render_date( $layout, 'post', '10 November 2026' );

	// Themes load after plugins and may register at any hook priority.
	$theme_date = static function ( $id ) {
		if ( 42 !== $id ) {
			throw new RuntimeException( 'The action did not receive the event ID.' );
		}
		echo '2026-11-13' === $GLOBALS['end_date'] ? '13 November' : '13 November–28 February 2027';
	};
	foreach ( array( 0, 10, 20 ) as $priority ) {
		add_action( 'goodblocks_event_date_range', $theme_date, $priority );
		$GLOBALS['end_date'] = '2026-11-13';
		render_date( $layout, 'tribe_events', '13 November' );
		$GLOBALS['end_date'] = '2027-02-28';
		render_date( $layout, 'tribe_events', '13 November–28 February 2027' );
		render_date( $layout, 'post', '10 November 2026' );
		remove_action( 'goodblocks_event_date_range', $theme_date, $priority );
	}
	render_date( $layout, 'tribe_events', '13 November-28 February 2027' );
}

echo "Post Grid event date smoke tests passed.\n";
