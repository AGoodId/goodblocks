<?php

declare( strict_types=1 );

/**
 * Standalone smoke tests for GoodBlocks event recurrence helpers.
 *
 * Run with:
 *   php tests/php/events-recurrence-smoke.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

class WP_Post {
	public int $ID;
	public string $post_type;
	public string $post_status;
	public string $post_title;
	public string $post_excerpt;
	public string $post_name;

	public function __construct( array $args ) {
		$this->ID           = (int) $args['ID'];
		$this->post_type    = $args['post_type'] ?? 'goodblocks_event';
		$this->post_status  = $args['post_status'] ?? 'publish';
		$this->post_title   = $args['post_title'] ?? '';
		$this->post_excerpt = $args['post_excerpt'] ?? '';
		$this->post_name    = $args['post_name'] ?? sanitize_title( $this->post_title );
	}
}

class WP_Query {
	private array $posts = [];
	private int $index = -1;

	public function __construct( array $args = [] ) {
		$this->posts = goodblocks_test_filter_posts( $args );
	}

	public function have_posts(): bool {
		return ( $this->index + 1 ) < count( $this->posts );
	}

	public function the_post(): void {
		$this->index++;
		$GLOBALS['post'] = $this->posts[ $this->index ] ?? null;
	}
}

function add_action(): void {}
function add_filter(): void {}
function register_post_type(): void {}
function register_taxonomy(): void {}
function register_post_meta(): void {}
function current_user_can(): bool { return true; }
function __( string $text ): string { return $text; }
function esc_html__( string $text ): string { return $text; }
function esc_attr_e( string $text ): void { echo esc_attr( $text ); }
function esc_html_e( string $text ): void { echo esc_html( $text ); }
function wp_parse_args( array $args, array $defaults ): array { return array_merge( $defaults, $args ); }
function wp_reset_postdata(): void { unset( $GLOBALS['post'] ); }
function current_time(): string { return '2026-07-01 00:00:00'; }
function wp_timezone(): DateTimeZone { return new DateTimeZone( 'Europe/Stockholm' ); }
function wp_date( string $format, ?int $timestamp = null ): string {
	$timestamp = $timestamp ?? strtotime( current_time() );

	return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() )->format( $format );
}
function get_option( string $key ): string { return 'date_format' === $key ? 'Y-m-d' : 'H:i'; }
function sanitize_key( $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_title( $value ): string { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ), '-' ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function esc_url_raw( $value ): string { return filter_var( (string) $value, FILTER_SANITIZE_URL ) ?: ''; }
function absint( $value ): int { return abs( (int) $value ); }
function esc_attr( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ): string { return filter_var( (string) $value, FILTER_SANITIZE_URL ) ?: ''; }
function get_block_wrapper_attributes( array $extra = [] ): string {
	$class = trim( 'wp-block-goodblocks-event-calendar ' . ( $extra['class'] ?? '' ) );
	return 'class="' . esc_attr( $class ) . '"';
}
function wp_unique_id( string $prefix = '' ): string {
	static $id = 0;
	$id++;
	return $prefix . $id;
}
function add_query_arg( string $key, string $value ): string {
	return 'https://example.test/calendar?' . rawurlencode( $key ) . '=' . rawurlencode( $value );
}
function remove_query_arg( string $key ): string {
	return 'https://example.test/calendar';
}

function get_the_ID(): int {
	return isset( $GLOBALS['post'] ) ? (int) $GLOBALS['post']->ID : 0;
}

function get_post( int $post_id ): ?WP_Post {
	return $GLOBALS['_test_posts'][ $post_id ] ?? null;
}

function get_post_type( int $post_id = 0 ): string {
	$post = get_post( $post_id );
	return $post ? $post->post_type : '';
}

function get_post_meta( int $post_id, string $key = '', bool $single = false ) {
	if ( '' === $key ) {
		return $GLOBALS['_test_meta'][ $post_id ] ?? [];
	}

	return $GLOBALS['_test_meta'][ $post_id ][ $key ] ?? '';
}

function update_post_meta( int $post_id, string $key, $value ): void {
	$GLOBALS['_test_meta'][ $post_id ][ $key ] = $value;
}

function get_the_title( int $post_id = 0 ): string {
	$post = get_post( $post_id ?: get_the_ID() );
	return $post ? $post->post_title : '';
}

function get_permalink( int $post_id ): string {
	return 'https://example.test/events/' . $post_id;
}

function get_the_excerpt( int $post_id = 0 ): string {
	$post = get_post( $post_id ?: get_the_ID() );
	return $post ? $post->post_excerpt : '';
}

function get_page_by_path( string $slug, string $output = OBJECT, string $post_type = 'page' ): ?WP_Post {
	foreach ( $GLOBALS['_test_posts'] as $post ) {
		if ( $post_type === $post->post_type && $slug === $post->post_name ) {
			return $post;
		}
	}

	return null;
}

function get_posts( array $args = [] ): array {
	$posts = goodblocks_test_filter_posts( $args );

	if ( ( $args['fields'] ?? '' ) === 'ids' ) {
		return array_map( static fn( WP_Post $post ): int => $post->ID, $posts );
	}

	return $posts;
}

function goodblocks_test_filter_posts( array $args ): array {
	$posts = array_values( $GLOBALS['_test_posts'] ?? [] );
	$post_type = $args['post_type'] ?? '';
	$status = $args['post_status'] ?? '';

	if ( $post_type ) {
		$posts = array_values( array_filter( $posts, static fn( WP_Post $post ): bool => $post_type === $post->post_type ) );
	}

	if ( $status && 'any' !== $status ) {
		$posts = array_values( array_filter( $posts, static fn( WP_Post $post ): bool => $status === $post->post_status ) );
	}

	foreach ( $args['meta_query'] ?? [] as $query ) {
		if ( ! is_array( $query ) || empty( $query['key'] ) ) {
			continue;
		}

		$posts = array_values( array_filter( $posts, static function ( WP_Post $post ) use ( $query ): bool {
			$value = get_post_meta( $post->ID, $query['key'], true );
			if ( 'IN' === ( $query['compare'] ?? '' ) ) {
				return in_array( (int) $value, array_map( 'intval', (array) ( $query['value'] ?? [] ) ), true );
			}
			if ( '>=' === ( $query['compare'] ?? '' ) ) {
				return (string) $value >= (string) ( $query['value'] ?? '' );
			}
			if ( '<=' === ( $query['compare'] ?? '' ) ) {
				return (string) $value <= (string) ( $query['value'] ?? '' );
			}

			return (string) $value === (string) ( $query['value'] ?? '' );
		} ) );
	}

	if ( isset( $args['posts_per_page'] ) && -1 !== (int) $args['posts_per_page'] ) {
		$posts = array_slice( $posts, 0, (int) $args['posts_per_page'] );
	}

	if ( ! empty( $args['title'] ) ) {
		$posts = array_values( array_filter( $posts, static fn( WP_Post $post ): bool => $args['title'] === $post->post_title ) );
	}

	return $posts;
}

function goodblocks_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		fwrite( STDERR, 'Expected: ' . var_export( $expected, true ) . PHP_EOL );
		fwrite( STDERR, 'Actual:   ' . var_export( $actual, true ) . PHP_EOL );
		exit( 1 );
	}
}

require_once dirname( __DIR__, 2 ) . '/inc/events-cpt.php';

$GLOBALS['_test_posts'] = [
	1 => new WP_Post( [
		'ID'         => 1,
		'post_title' => 'Training',
		'post_name'  => 'training',
	] ),
	2 => new WP_Post( [
		'ID'         => 2,
		'post_title' => 'Training moved',
		'post_name'  => 'training-moved',
	] ),
];

$GLOBALS['_test_meta'] = [
	1 => [
		'_event_start'                 => '2026-07-06 18:00:00',
		'_event_end'                   => '2026-07-06 19:00:00',
		'_event_recurrence_frequency'  => 'weekly',
		'_event_recurrence_interval'   => 1,
		'_event_recurrence_weekdays'   => 'mon,wed',
		'_event_recurrence_until'      => '2026-07-20',
		'_event_recurrence_exdates'    => '2026-07-13',
		'_event_status'                => 'scheduled',
	],
	2 => [
		'_event_start'                     => '2026-07-08 20:00:00',
		'_event_end'                       => '2026-07-08 21:00:00',
		'_event_recurrence_parent'         => 1,
		'_event_recurrence_original_start' => '2026-07-08 18:00:00',
		'_event_status'                    => 'changed',
	],
];

$events = goodblocks_get_events( [
	'from'           => '2026-07-06 00:00:00',
	'to'             => '2026-07-20 23:59:59',
	'show_past'      => true,
	'posts_per_page' => 20,
] );

goodblocks_assert_same(
	[
		'2026-07-06 18:00:00:Training',
		'2026-07-08 20:00:00:Training moved',
		'2026-07-15 18:00:00:Training',
		'2026-07-20 18:00:00:Training',
	],
	array_map( static fn( array $event ): string => $event['start'] . ':' . $event['title'], $events ),
	'Weekly recurrence should expand, skip excluded dates, and replace overridden occurrence.'
);

goodblocks_assert_same( true, $events[1]['is_exception'], 'Override event should be flagged as an exception.' );
goodblocks_assert_same( 'changed', $events[1]['status'], 'Override event should keep its own status.' );
goodblocks_assert_same( '18:00 – 19:00', $events[0]['time_label'], 'Local event times should not be shifted by the site timezone.' );
goodblocks_assert_same( '', goodblocks_sanitize_event_date_only( '2026-02-31' ), 'Invalid recurrence dates should be rejected rather than normalized.' );
goodblocks_assert_same(
	false,
	goodblocks_event_occurrence_is_overridden(
		new DateTimeImmutable( '2026-07-08 20:00:00', wp_timezone() ),
		[ '2026-07-08 18:00:00' => true ]
	),
	'An override must only replace its exact original time, not every same-day occurrence.'
);
goodblocks_assert_same(
	true,
	! empty( goodblocks_get_event_override_indexes( [ 1 ], '2026-07-09 00:00:00', '2026-07-20 23:59:59' )[1]['2026-07-08 18:00:00'] ),
	'Override indexes must retain originals before the window because a multi-day occurrence can overlap its start.'
);

$GLOBALS['_test_posts'][3] = new WP_Post( [
	'ID'         => 3,
	'post_title' => 'Imported series',
] );
$GLOBALS['_test_meta'][3] = [];

goodblocks_update_event_recurrence_from_import( 3, [
	'recurrence_frequency' => 'weekly',
	'recurrence_interval'  => '2',
	'recurrence_weekdays'  => 'mon,invalid,wed',
	'recurrence_until'     => '2026-08-01',
	'recurrence_count'     => '12',
	'recurrence_exdates'   => "2026-07-22\ninvalid\n2026-07-29",
] );

goodblocks_assert_same( 'weekly', get_post_meta( 3, '_event_recurrence_frequency', true ), 'Imported frequency should be saved.' );
goodblocks_assert_same( 'mon,wed', get_post_meta( 3, '_event_recurrence_weekdays', true ), 'Imported weekdays should be sanitized.' );
goodblocks_assert_same( '2026-07-22,2026-07-29', get_post_meta( 3, '_event_recurrence_exdates', true ), 'Imported excluded dates should be sanitized.' );

$GLOBALS['_test_posts'][4] = new WP_Post( [
	'ID'         => 4,
	'post_title' => 'Imported override',
] );
$GLOBALS['_test_meta'][4] = [];

goodblocks_update_event_recurrence_from_import( 4, [
	'recurrence_frequency'       => 'weekly',
	'recurrence_parent'          => 'training',
	'recurrence_original_start'  => '2026-07-15 18:00',
] );

goodblocks_assert_same( 1, get_post_meta( 4, '_event_recurrence_parent', true ), 'Imported override should resolve parent by slug.' );
goodblocks_assert_same( '', get_post_meta( 4, '_event_recurrence_frequency', true ), 'Imported override should not also be a recurring series.' );
goodblocks_assert_same( '2026-07-15 18:00:00', get_post_meta( 4, '_event_recurrence_original_start', true ), 'Imported override original start should normalize.' );

$GLOBALS['_test_posts'][5] = new WP_Post( [
	'ID'         => 5,
	'post_title' => 'Month-end series',
] );
$GLOBALS['_test_meta'][5] = [
	'_event_start'                => '2026-01-31 18:00:00',
	'_event_recurrence_frequency' => 'monthly',
	'_event_recurrence_interval'  => 1,
	'_event_recurrence_count'     => 3,
];

$month_end_events = goodblocks_get_event_occurrences( 5, '2026-01-01 00:00:00', '2026-03-31 23:59:59' );
goodblocks_assert_same(
	[ '2026-01-31 18:00:00', '2026-02-28 18:00:00', '2026-03-31 18:00:00' ],
	array_column( $month_end_events, 'start' ),
	'Monthly recurrences should retain the original day-of-month anchor after a short month.'
);

$GLOBALS['_test_posts'][6] = new WP_Post( [
	'ID'         => 6,
	'post_title' => 'Leap-day series',
] );
$GLOBALS['_test_meta'][6] = [
	'_event_start'                => '2024-02-29 18:00:00',
	'_event_recurrence_frequency' => 'yearly',
	'_event_recurrence_interval'  => 1,
	'_event_recurrence_count'     => 5,
];

$leap_day_events = goodblocks_get_event_occurrences( 6, '2024-01-01 00:00:00', '2028-12-31 23:59:59' );
goodblocks_assert_same(
	[ '2024-02-29 18:00:00', '2025-02-28 18:00:00', '2026-02-28 18:00:00', '2027-02-28 18:00:00', '2028-02-29 18:00:00' ],
	array_column( $leap_day_events, 'start' ),
	'Yearly leap-day recurrences should return to February 29 in leap years.'
);

$_GET['goodblocks_month'] = '2026-07';
$attributes = [
	'defaultView'    => 'agenda',
	'eventsToShow'   => 20,
	'showPast'       => true,
	'showFilters'    => true,
	'showViewToggle' => true,
	'emptyText'      => 'No events',
];

ob_start();
require dirname( __DIR__, 2 ) . '/src/blocks/event-calendar/render.php';
$calendar_html = ob_get_clean();

goodblocks_assert_same( true, str_contains( $calendar_html, 'goodblocks-event-calendar' ), 'Calendar render should output the calendar wrapper.' );
goodblocks_assert_same( true, str_contains( $calendar_html, 'Training moved' ), 'Calendar render should include override event.' );
goodblocks_assert_same( true, str_contains( $calendar_html, 'Changed occurrence' ), 'Calendar render should label override events.' );
goodblocks_assert_same( true, str_contains( $calendar_html, 'data-calendar-panel="month"' ), 'Calendar render should include month panel.' );
goodblocks_assert_same( true, str_contains( $calendar_html, 'data-calendar-panel="agenda"' ), 'Calendar render should include agenda panel.' );

echo "Event recurrence smoke tests passed.\n";
