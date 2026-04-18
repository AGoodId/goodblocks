<?php
/**
 * GoodBlocks Events — Custom post type, taxonomies, meta.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'goodblocks_register_event_cpt' );
add_action( 'add_meta_boxes', 'goodblocks_event_add_meta_box' );
add_action( 'save_post_goodblocks_event', 'goodblocks_event_save_meta', 10, 2 );

function goodblocks_register_event_cpt(): void {
	register_post_type( 'goodblocks_event', [
		'labels'       => [
			'name'               => __( 'Events', 'goodblocks' ),
			'singular_name'      => __( 'Event', 'goodblocks' ),
			'add_new_item'       => __( 'Add New Event', 'goodblocks' ),
			'edit_item'          => __( 'Edit Event', 'goodblocks' ),
			'new_item'           => __( 'New Event', 'goodblocks' ),
			'view_item'          => __( 'View Event', 'goodblocks' ),
			'search_items'       => __( 'Search Events', 'goodblocks' ),
			'not_found'          => __( 'No events found.', 'goodblocks' ),
			'not_found_in_trash' => __( 'No events found in Trash.', 'goodblocks' ),
			'menu_name'          => __( 'Events', 'goodblocks' ),
		],
		'public'        => true,
		'has_archive'   => true,
		'rewrite'       => [ 'slug' => 'events', 'with_front' => false ],
		'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ],
		'menu_icon'     => 'dashicons-calendar-alt',
		'show_in_rest'  => true,
		'menu_position' => 20,
	] );

	register_taxonomy( 'event_category', 'goodblocks_event', [
		'labels'            => [
			'name'          => __( 'Event Categories', 'goodblocks' ),
			'singular_name' => __( 'Event Category', 'goodblocks' ),
			'add_new_item'  => __( 'Add New Category', 'goodblocks' ),
			'edit_item'     => __( 'Edit Category', 'goodblocks' ),
		],
		'hierarchical'  => true,
		'show_in_rest'  => true,
		'rewrite'       => [ 'slug' => 'event-category' ],
		'show_ui'       => true,
		'show_in_menu'  => true,
	] );

	register_taxonomy( 'event_tag', 'goodblocks_event', [
		'labels'            => [
			'name'          => __( 'Event Tags', 'goodblocks' ),
			'singular_name' => __( 'Event Tag', 'goodblocks' ),
		],
		'hierarchical'  => false,
		'show_in_rest'  => true,
		'rewrite'       => [ 'slug' => 'event-tag' ],
		'show_ui'       => true,
	] );

	register_post_meta( 'goodblocks_event', '_event_start', [
		'type'          => 'string',
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => fn() => current_user_can( 'edit_posts' ),
	] );

	register_post_meta( 'goodblocks_event', '_event_end', [
		'type'          => 'string',
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => fn() => current_user_can( 'edit_posts' ),
	] );

	register_post_meta( 'goodblocks_event', '_event_all_day', [
		'type'          => 'boolean',
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => fn() => current_user_can( 'edit_posts' ),
	] );
}

function goodblocks_event_add_meta_box(): void {
	add_meta_box(
		'goodblocks_event_dates',
		__( 'Event Dates', 'goodblocks' ),
		'goodblocks_event_dates_render',
		'goodblocks_event',
		'side',
		'high'
	);
}

function goodblocks_event_dates_render( WP_Post $post ): void {
	wp_nonce_field( 'goodblocks_event_dates', 'goodblocks_event_dates_nonce' );

	$start   = get_post_meta( $post->ID, '_event_start', true );
	$end     = get_post_meta( $post->ID, '_event_end', true );
	$all_day = (bool) get_post_meta( $post->ID, '_event_all_day', true );

	// Use date-only format for all-day events, datetime-local otherwise.
	if ( $all_day ) {
		$start_val = $start ? date( 'Y-m-d', strtotime( $start ) ) : '';
		$end_val   = $end   ? date( 'Y-m-d', strtotime( $end ) )   : '';
		$input_type = 'date';
	} else {
		$start_val  = $start ? date( 'Y-m-d\TH:i', strtotime( $start ) ) : '';
		$end_val    = $end   ? date( 'Y-m-d\TH:i', strtotime( $end ) )   : '';
		$input_type = 'datetime-local';
	}
	?>
	<p>
		<label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
			<input type="checkbox" id="goodblocks_event_all_day" name="goodblocks_event_all_day"
				value="1" <?php checked( $all_day ); ?>>
			<strong><?php esc_html_e( 'Heldag', 'goodblocks' ); ?></strong>
		</label>
	</p>
	<p>
		<label for="goodblocks_event_start"><strong><?php esc_html_e( 'Start', 'goodblocks' ); ?></strong></label><br>
		<input type="<?php echo esc_attr( $input_type ); ?>" id="goodblocks_event_start" name="goodblocks_event_start"
			style="width:100%;" value="<?php echo esc_attr( $start_val ); ?>">
	</p>
	<p>
		<label for="goodblocks_event_end"><strong><?php esc_html_e( 'End', 'goodblocks' ); ?></strong></label><br>
		<input type="<?php echo esc_attr( $input_type ); ?>" id="goodblocks_event_end" name="goodblocks_event_end"
			style="width:100%;" value="<?php echo esc_attr( $end_val ); ?>">
	</p>
	<script>
	(function() {
		var cb = document.getElementById('goodblocks_event_all_day');
		var inputs = [
			document.getElementById('goodblocks_event_start'),
			document.getElementById('goodblocks_event_end')
		];
		cb.addEventListener('change', function() {
			var type = cb.checked ? 'date' : 'datetime-local';
			inputs.forEach(function(input) {
				// Strip time part when switching to date-only.
				if (type === 'date' && input.value.includes('T')) {
					input.value = input.value.split('T')[0];
				}
				input.type = type;
			});
		});
	})();
	</script>
	<?php
}

function goodblocks_event_save_meta( int $post_id, WP_Post $post ): void {
	if ( ! isset( $_POST['goodblocks_event_dates_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( $_POST['goodblocks_event_dates_nonce'] ), 'goodblocks_event_dates' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$all_day = ! empty( $_POST['goodblocks_event_all_day'] );
	update_post_meta( $post_id, '_event_all_day', $all_day ? '1' : '' );

	foreach ( [ 'start', 'end' ] as $key ) {
		$field = 'goodblocks_event_' . $key;
		$meta  = '_event_' . $key;

		if ( isset( $_POST[ $field ] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			if ( ! $raw ) {
				update_post_meta( $post_id, $meta, '' );
				continue;
			}
			if ( $all_day ) {
				$dt = DateTime::createFromFormat( 'Y-m-d', $raw );
				update_post_meta( $post_id, $meta, $dt ? $dt->format( 'Y-m-d' ) : '' );
			} else {
				$dt = DateTime::createFromFormat( 'Y-m-d\TH:i', $raw );
				update_post_meta( $post_id, $meta, $dt ? $dt->format( 'Y-m-d H:i:s' ) : '' );
			}
		}
	}
}

/**
 * Format event date range for display.
 *
 * Returns "Date, Time" for single-day events, "Date – End date" for multi-day.
 * For all-day events, times are omitted entirely.
 */
function goodblocks_format_event_date( string $start, string $end = '', bool $all_day = false ): string {
	if ( ! $start ) {
		return '';
	}

	$start_ts = strtotime( $start );
	if ( ! $start_ts ) {
		return '';
	}

	$date_fmt = get_option( 'date_format' );
	$time_fmt = get_option( 'time_format' );

	if ( $all_day ) {
		$start_str = wp_date( $date_fmt, $start_ts );

		if ( ! $end ) {
			return $start_str;
		}
		$end_ts = strtotime( $end );
		if ( ! $end_ts || wp_date( 'Y-m-d', $start_ts ) === wp_date( 'Y-m-d', $end_ts ) ) {
			return $start_str;
		}
		return $start_str . ' – ' . wp_date( $date_fmt, $end_ts );
	}

	$start_str = wp_date( $date_fmt . ', ' . $time_fmt, $start_ts );

	if ( ! $end ) {
		return $start_str;
	}

	$end_ts = strtotime( $end );
	if ( ! $end_ts ) {
		return $start_str;
	}

	// Same day: show "Date, StartTime – EndTime"
	if ( wp_date( 'Y-m-d', $start_ts ) === wp_date( 'Y-m-d', $end_ts ) ) {
		return wp_date( $date_fmt . ', ' . $time_fmt, $start_ts ) . ' – ' . wp_date( $time_fmt, $end_ts );
	}

	// Multi-day: show "Start date – End date"
	return wp_date( $date_fmt, $start_ts ) . ' – ' . wp_date( $date_fmt, $end_ts );
}
