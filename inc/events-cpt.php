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
add_filter( 'manage_goodblocks_event_posts_columns', 'goodblocks_event_admin_columns' );
add_action( 'manage_goodblocks_event_posts_custom_column', 'goodblocks_event_admin_column_content', 10, 2 );
add_filter( 'manage_edit-goodblocks_event_sortable_columns', 'goodblocks_event_sortable_columns' );
add_action( 'pre_get_posts', 'goodblocks_event_admin_sorting' );
add_action( 'admin_menu', 'goodblocks_event_admin_menu' );
add_action( 'admin_post_goodblocks_import_events_csv', 'goodblocks_import_events_csv' );

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

	foreach ( goodblocks_event_detail_fields() as $meta_key => $field ) {
		register_post_meta( 'goodblocks_event', $meta_key, [
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		] );
	}
}

function goodblocks_event_detail_fields(): array {
	return [
		'_event_class'   => [
			'label'       => __( 'Class / Division', 'goodblocks' ),
			'placeholder' => __( 'Senior All Girl Premier', 'goodblocks' ),
			'type'        => 'text',
		],
		'_event_type'    => [
			'label'   => __( 'Schedule type', 'goodblocks' ),
			'type'    => 'select',
			'options' => [
				''              => __( 'General event', 'goodblocks' ),
				'qualification' => __( 'Qualification', 'goodblocks' ),
				'semifinal'     => __( 'Semifinal', 'goodblocks' ),
				'final'         => __( 'Final', 'goodblocks' ),
				'award'         => __( 'Award ceremony', 'goodblocks' ),
				'training'      => __( 'Training', 'goodblocks' ),
				'other'         => __( 'Other', 'goodblocks' ),
			],
		],
		'_event_venue'   => [
			'label'       => __( 'Venue / Arena', 'goodblocks' ),
			'placeholder' => __( 'Arena A', 'goodblocks' ),
			'type'        => 'text',
		],
		'_event_stream'  => [
			'label'       => __( 'Livestream URL', 'goodblocks' ),
			'placeholder' => 'https://',
			'type'        => 'url',
		],
		'_event_results' => [
			'label'       => __( 'Results URL', 'goodblocks' ),
			'placeholder' => 'https://',
			'type'        => 'url',
		],
		'_event_status'  => [
			'label'   => __( 'Status', 'goodblocks' ),
			'type'    => 'select',
			'options' => [
				'scheduled' => __( 'Scheduled', 'goodblocks' ),
				'changed'   => __( 'Changed', 'goodblocks' ),
				'cancelled' => __( 'Cancelled', 'goodblocks' ),
				'live'      => __( 'Live now', 'goodblocks' ),
				'done'      => __( 'Done', 'goodblocks' ),
			],
		],
	];
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

	add_meta_box(
		'goodblocks_event_details',
		__( 'Schedule Details', 'goodblocks' ),
		'goodblocks_event_details_render',
		'goodblocks_event',
		'normal',
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
		<label for="goodblocks_event_end">
			<strong><?php esc_html_e( 'End', 'goodblocks' ); ?></strong>
			<span style="font-weight:400;color:#757575;margin-left:4px;"><?php esc_html_e( '(valfritt)', 'goodblocks' ); ?></span>
		</label><br>
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

function goodblocks_event_details_render( WP_Post $post ): void {
	wp_nonce_field( 'goodblocks_event_details', 'goodblocks_event_details_nonce' );
	?>
	<div class="goodblocks-event-details" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
		<?php foreach ( goodblocks_event_detail_fields() as $meta_key => $field ) : ?>
			<?php
			$value = (string) get_post_meta( $post->ID, $meta_key, true );
			$id    = 'goodblocks' . str_replace( '_event', '_event', $meta_key );
			?>
			<p style="margin:0;">
				<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $field['label'] ); ?></strong></label><br>
				<?php if ( 'select' === $field['type'] ) : ?>
					<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" style="width:100%;">
						<?php foreach ( $field['options'] as $option_value => $label ) : ?>
							<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input
						type="<?php echo esc_attr( $field['type'] ); ?>"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $id ); ?>"
						style="width:100%;"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
					>
				<?php endif; ?>
			</p>
		<?php endforeach; ?>
	</div>
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

	if ( isset( $_POST['goodblocks_event_details_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['goodblocks_event_details_nonce'] ), 'goodblocks_event_details' ) ) {
		foreach ( goodblocks_event_detail_fields() as $meta_key => $field ) {
			$field_name = 'goodblocks' . str_replace( '_event', '_event', $meta_key );
			if ( ! isset( $_POST[ $field_name ] ) ) {
				continue;
			}

			$raw = wp_unslash( $_POST[ $field_name ] );

			if ( 'url' === $field['type'] ) {
				$value = esc_url_raw( $raw );
			} elseif ( 'select' === $field['type'] ) {
				$value = sanitize_key( $raw );
				if ( ! array_key_exists( $value, $field['options'] ) ) {
					$value = array_key_first( $field['options'] );
				}
			} else {
				$value = sanitize_text_field( $raw );
			}

			update_post_meta( $post_id, $meta_key, $value );
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

function goodblocks_event_type_label( string $event_type ): string {
	$options = goodblocks_event_detail_fields()['_event_type']['options'];

	return $options[ $event_type ] ?? $event_type;
}

function goodblocks_event_status_label( string $status ): string {
	$options = goodblocks_event_detail_fields()['_event_status']['options'];

	return $options[ $status ] ?? $status;
}

function goodblocks_get_events( array $args = [] ): array {
	$defaults = [
		'posts_per_page' => 200,
		'show_past'      => false,
		'class'          => '',
		'type'           => '',
		'from'           => '',
		'to'             => '',
	];
	$args     = wp_parse_args( $args, $defaults );
	$now      = current_time( 'mysql' );

	$query_args = [
		'post_type'      => 'goodblocks_event',
		'posts_per_page' => absint( $args['posts_per_page'] ),
		'post_status'    => 'publish',
		'orderby'        => 'meta_value',
		'meta_key'       => '_event_start',
		'meta_type'      => 'DATETIME',
		'order'          => 'ASC',
	];

	$meta_query = [];

	if ( empty( $args['show_past'] ) ) {
		$meta_query[] = [
			'relation' => 'OR',
			[
				'key'     => '_event_start',
				'value'   => $now,
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
			[
				'key'     => '_event_end',
				'value'   => $now,
				'compare' => '>=',
				'type'    => 'DATETIME',
			],
		];
	}

	if ( ! empty( $args['from'] ) ) {
		$meta_query[] = [
			'key'     => '_event_start',
			'value'   => sanitize_text_field( $args['from'] ),
			'compare' => '>=',
			'type'    => 'DATETIME',
		];
	}

	if ( ! empty( $args['to'] ) ) {
		$meta_query[] = [
			'key'     => '_event_start',
			'value'   => sanitize_text_field( $args['to'] ),
			'compare' => '<=',
			'type'    => 'DATETIME',
		];
	}

	if ( ! empty( $args['class'] ) ) {
		$meta_query[] = [
			'key'     => '_event_class',
			'value'   => sanitize_text_field( $args['class'] ),
			'compare' => 'LIKE',
		];
	}

	if ( ! empty( $args['type'] ) ) {
		$meta_query[] = [
			'key'   => '_event_type',
			'value' => sanitize_key( $args['type'] ),
		];
	}

	if ( $meta_query ) {
		$query_args['meta_query'] = $meta_query;
	}

	$query  = new WP_Query( $query_args );
	$events = [];

	while ( $query->have_posts() ) {
		$query->the_post();
		$events[] = goodblocks_get_event_data( get_the_ID() );
	}

	wp_reset_postdata();

	return $events;
}

function goodblocks_get_event_data( int $post_id ): array {
	$start   = (string) get_post_meta( $post_id, '_event_start', true );
	$end     = (string) get_post_meta( $post_id, '_event_end', true );
	$all_day = (bool) get_post_meta( $post_id, '_event_all_day', true );
	$type    = (string) get_post_meta( $post_id, '_event_type', true );
	$status  = (string) get_post_meta( $post_id, '_event_status', true );
	$now_ts  = strtotime( current_time( 'mysql' ) );
	$start_ts = $start ? strtotime( $start ) : false;
	$end_ts  = $end ? strtotime( $end ) : false;

	return [
		'id'           => $post_id,
		'title'        => get_the_title( $post_id ),
		'url'          => get_permalink( $post_id ),
		'start'        => $start,
		'end'          => $end,
		'all_day'      => $all_day,
		'date_key'     => $start ? wp_date( 'Y-m-d', strtotime( $start ) ) : '',
		'date_label'   => $start ? wp_date( get_option( 'date_format' ), strtotime( $start ) ) : '',
		'time_label'   => goodblocks_format_event_time( $start, $end, $all_day ),
		'range_label'  => goodblocks_format_event_date( $start, $end, $all_day ),
		'class'        => (string) get_post_meta( $post_id, '_event_class', true ),
		'type'         => $type,
		'type_label'   => goodblocks_event_type_label( $type ),
		'venue'        => (string) get_post_meta( $post_id, '_event_venue', true ),
		'stream'       => (string) get_post_meta( $post_id, '_event_stream', true ),
		'results'      => (string) get_post_meta( $post_id, '_event_results', true ),
		'status'       => $status ?: 'scheduled',
		'status_label' => goodblocks_event_status_label( $status ?: 'scheduled' ),
		'is_current'   => $start_ts && $end_ts && $start_ts <= $now_ts && $end_ts >= $now_ts,
		'excerpt'      => get_the_excerpt( $post_id ),
	];
}

function goodblocks_format_event_time( string $start, string $end = '', bool $all_day = false ): string {
	if ( ! $start ) {
		return '';
	}

	if ( $all_day ) {
		return __( 'All day', 'goodblocks' );
	}

	$start_ts = strtotime( $start );
	if ( ! $start_ts ) {
		return '';
	}

	$time_fmt = get_option( 'time_format' );
	$label    = wp_date( $time_fmt, $start_ts );
	$end_ts   = $end ? strtotime( $end ) : false;

	if ( $end_ts && wp_date( 'Y-m-d', $start_ts ) === wp_date( 'Y-m-d', $end_ts ) ) {
		$label .= ' – ' . wp_date( $time_fmt, $end_ts );
	}

	return $label;
}

function goodblocks_event_admin_columns( array $columns ): array {
	$new_columns = [];

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'title' === $key ) {
			$new_columns['event_start'] = __( 'Start', 'goodblocks' );
			$new_columns['event_class'] = __( 'Class', 'goodblocks' );
			$new_columns['event_type']  = __( 'Type', 'goodblocks' );
			$new_columns['event_venue'] = __( 'Venue', 'goodblocks' );
			$new_columns['event_status'] = __( 'Status', 'goodblocks' );
		}
	}

	return $new_columns;
}

function goodblocks_event_admin_column_content( string $column, int $post_id ): void {
	if ( 'event_start' === $column ) {
		echo esc_html( goodblocks_format_event_date( (string) get_post_meta( $post_id, '_event_start', true ), (string) get_post_meta( $post_id, '_event_end', true ), (bool) get_post_meta( $post_id, '_event_all_day', true ) ) );
	}

	if ( 'event_class' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_event_class', true ) );
	}

	if ( 'event_type' === $column ) {
		echo esc_html( goodblocks_event_type_label( (string) get_post_meta( $post_id, '_event_type', true ) ) );
	}

	if ( 'event_venue' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_event_venue', true ) );
	}

	if ( 'event_status' === $column ) {
		echo esc_html( goodblocks_event_status_label( (string) get_post_meta( $post_id, '_event_status', true ) ?: 'scheduled' ) );
	}
}

function goodblocks_event_sortable_columns( array $columns ): array {
	$columns['event_start'] = 'event_start';

	return $columns;
}

function goodblocks_event_admin_sorting( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'goodblocks_event' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( 'event_start' !== $query->get( 'orderby' ) ) {
		return;
	}

	$query->set( 'meta_key', '_event_start' );
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'meta_type', 'DATETIME' );
}

function goodblocks_event_admin_menu(): void {
	add_submenu_page(
		'edit.php?post_type=goodblocks_event',
		__( 'Import Schedule', 'goodblocks' ),
		__( 'Import Schedule', 'goodblocks' ),
		'edit_posts',
		'goodblocks-event-import',
		'goodblocks_event_import_page'
	);
}

function goodblocks_event_import_page(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to import events.', 'goodblocks' ) );
	}

	$result = get_transient( 'goodblocks_event_import_result_' . get_current_user_id() );
	delete_transient( 'goodblocks_event_import_result_' . get_current_user_id() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import Schedule', 'goodblocks' ); ?></h1>
		<?php if ( is_array( $result ) ) : ?>
			<div class="notice notice-<?php echo empty( $result['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: imported count, 2: skipped count */
						esc_html__( 'Imported %1$d schedule row(s). Skipped %2$d row(s).', 'goodblocks' ),
						absint( $result['imported'] ?? 0 ),
						absint( $result['skipped'] ?? 0 )
					);
					?>
				</p>
				<?php if ( ! empty( $result['errors'] ) ) : ?>
					<ul>
						<?php foreach ( $result['errors'] as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Upload a CSV with headers: title,start,end,class,type,venue,stream,results,status,all_day,excerpt.', 'goodblocks' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'goodblocks_import_events_csv', 'goodblocks_import_events_csv_nonce' ); ?>
			<input type="hidden" name="action" value="goodblocks_import_events_csv">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="goodblocks_events_csv"><?php esc_html_e( 'CSV file', 'goodblocks' ); ?></label></th>
					<td><input type="file" id="goodblocks_events_csv" name="goodblocks_events_csv" accept=".csv,text/csv" required></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Import mode', 'goodblocks' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="goodblocks_publish_imported_events" value="1" checked>
							<?php esc_html_e( 'Publish imported rows immediately', 'goodblocks' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Import schedule', 'goodblocks' ) ); ?>
		</form>
	</div>
	<?php
}

function goodblocks_import_events_csv(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to import events.', 'goodblocks' ) );
	}

	if ( ! isset( $_POST['goodblocks_import_events_csv_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['goodblocks_import_events_csv_nonce'] ), 'goodblocks_import_events_csv' ) ) {
		wp_die( esc_html__( 'Invalid import request.', 'goodblocks' ) );
	}

	$result = [
		'imported' => 0,
		'skipped'  => 0,
		'errors'   => [],
	];

	if ( empty( $_FILES['goodblocks_events_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['goodblocks_events_csv']['tmp_name'] ) ) {
		$result['errors'][] = __( 'No CSV file was uploaded.', 'goodblocks' );
		goodblocks_redirect_event_import( $result );
	}

	$handle = fopen( $_FILES['goodblocks_events_csv']['tmp_name'], 'r' );
	if ( false === $handle ) {
		$result['errors'][] = __( 'Could not read the uploaded CSV file.', 'goodblocks' );
		goodblocks_redirect_event_import( $result );
	}

	$headers = fgetcsv( $handle );
	if ( ! is_array( $headers ) ) {
		$result['errors'][] = __( 'The CSV file is empty.', 'goodblocks' );
		fclose( $handle );
		goodblocks_redirect_event_import( $result );
	}

	$headers = array_map(
		static fn( $header ) => strtolower( trim( (string) $header ) ),
		$headers
	);
	$publish = ! empty( $_POST['goodblocks_publish_imported_events'] );
	$row_num = 1;

	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$row_num++;
		$row   = array_slice( array_pad( $row, count( $headers ), '' ), 0, count( $headers ) );
		$data  = array_combine( $headers, $row );
		$title = sanitize_text_field( $data['title'] ?? '' );
		$start = goodblocks_normalize_event_import_date( $data['start'] ?? '' );

		if ( ! $title || ! $start ) {
			$result['skipped']++;
			$result['errors'][] = sprintf(
				/* translators: %d: CSV row number */
				__( 'Row %d skipped: title and start are required.', 'goodblocks' ),
				$row_num
			);
			continue;
		}

		$post_id = wp_insert_post( [
			'post_type'    => 'goodblocks_event',
			'post_status'  => $publish ? 'publish' : 'draft',
			'post_title'   => $title,
			'post_excerpt' => sanitize_textarea_field( $data['excerpt'] ?? '' ),
		], true );

		if ( is_wp_error( $post_id ) ) {
			$result['skipped']++;
			$result['errors'][] = sprintf(
				/* translators: 1: CSV row number, 2: error message */
				__( 'Row %1$d skipped: %2$s', 'goodblocks' ),
				$row_num,
				$post_id->get_error_message()
			);
			continue;
		}

		$all_day = ! empty( $data['all_day'] ) && in_array( strtolower( trim( (string) $data['all_day'] ) ), [ '1', 'true', 'yes', 'ja' ], true );

		update_post_meta( $post_id, '_event_start', $start );
		update_post_meta( $post_id, '_event_end', goodblocks_normalize_event_import_date( $data['end'] ?? '' ) );
		update_post_meta( $post_id, '_event_all_day', $all_day ? '1' : '' );
		update_post_meta( $post_id, '_event_class', sanitize_text_field( $data['class'] ?? '' ) );
		update_post_meta( $post_id, '_event_type', sanitize_key( $data['type'] ?? '' ) );
		update_post_meta( $post_id, '_event_venue', sanitize_text_field( $data['venue'] ?? '' ) );
		update_post_meta( $post_id, '_event_stream', esc_url_raw( $data['stream'] ?? '' ) );
		update_post_meta( $post_id, '_event_results', esc_url_raw( $data['results'] ?? '' ) );
		update_post_meta( $post_id, '_event_status', sanitize_key( $data['status'] ?? 'scheduled' ) ?: 'scheduled' );

		$result['imported']++;
	}

	fclose( $handle );
	goodblocks_redirect_event_import( $result );
}

function goodblocks_normalize_event_import_date( string $raw ): string {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return '';
	}

	$timestamp = strtotime( $raw );
	if ( ! $timestamp ) {
		return '';
	}

	return str_contains( $raw, ':' ) ? date( 'Y-m-d H:i:s', $timestamp ) : date( 'Y-m-d', $timestamp );
}

function goodblocks_redirect_event_import( array $result ): void {
	set_transient( 'goodblocks_event_import_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'edit.php?post_type=goodblocks_event&page=goodblocks-event-import' ) );
	exit;
}
