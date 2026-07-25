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

	foreach ( goodblocks_event_recurrence_meta_fields() as $meta_key => $type ) {
		register_post_meta( 'goodblocks_event', $meta_key, [
			'type'          => $type,
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
			'datalist'    => true,
			'description' => __( 'Use a consistent class or division name. Schedule filters use this value.', 'goodblocks' ),
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
				'scheduled'   => __( 'Scheduled', 'goodblocks' ),
				'placeholder' => __( 'Preliminary / placeholder', 'goodblocks' ),
				'changed'     => __( 'Changed', 'goodblocks' ),
				'cancelled'   => __( 'Cancelled', 'goodblocks' ),
				'live'        => __( 'Live now', 'goodblocks' ),
				'done'        => __( 'Done', 'goodblocks' ),
			],
		],
	];
}

function goodblocks_event_recurrence_meta_fields(): array {
	return [
		'_event_recurrence_frequency'      => 'string',
		'_event_recurrence_interval'       => 'integer',
		'_event_recurrence_weekdays'       => 'string',
		'_event_recurrence_until'          => 'string',
		'_event_recurrence_count'          => 'integer',
		'_event_recurrence_exdates'        => 'string',
		'_event_recurrence_parent'         => 'integer',
		'_event_recurrence_original_start' => 'string',
	];
}

function goodblocks_event_recurrence_frequency_options(): array {
	return [
		''        => __( 'Does not repeat', 'goodblocks' ),
		'daily'   => __( 'Daily', 'goodblocks' ),
		'weekly'  => __( 'Weekly', 'goodblocks' ),
		'monthly' => __( 'Monthly', 'goodblocks' ),
		'yearly'  => __( 'Yearly', 'goodblocks' ),
	];
}

function goodblocks_event_weekday_options(): array {
	return [
		'mon' => __( 'Monday', 'goodblocks' ),
		'tue' => __( 'Tuesday', 'goodblocks' ),
		'wed' => __( 'Wednesday', 'goodblocks' ),
		'thu' => __( 'Thursday', 'goodblocks' ),
		'fri' => __( 'Friday', 'goodblocks' ),
		'sat' => __( 'Saturday', 'goodblocks' ),
		'sun' => __( 'Sunday', 'goodblocks' ),
	];
}

function goodblocks_parse_event_csv_meta( string $value ): array {
	if ( '' === trim( $value ) ) {
		return [];
	}

	return array_values( array_filter( array_map( 'trim', explode( ',', $value ) ) ) );
}

function goodblocks_get_event_class_options(): array {
	$event_ids = get_posts( [
		'post_type'              => 'goodblocks_event',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
	] );

	$options = [];

	foreach ( $event_ids as $event_id ) {
		if ( 'trash' === get_post_status( $event_id ) ) {
			continue;
		}

		$class = sanitize_text_field( (string) get_post_meta( $event_id, '_event_class', true ) );

		if ( '' !== $class ) {
			$options[ strtolower( $class ) ] = $class;
		}
	}

	/**
	 * Filters class/division suggestions shown in the event editor.
	 *
	 * Use this to provide an event-specific controlled list while keeping the
	 * field editable for one-off classes.
	 *
	 * @param string[] $options Suggested class/division names.
	 */
	$options = apply_filters( 'goodblocks_event_class_options', array_values( $options ) );
	$options = array_filter( array_map( 'sanitize_text_field', (array) $options ) );
	$options = array_values( array_unique( $options ) );

	natcasesort( $options );

	return array_values( $options );
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

	add_meta_box(
		'goodblocks_event_recurrence',
		__( 'Recurrence', 'goodblocks' ),
		'goodblocks_event_recurrence_render',
		'goodblocks_event',
		'normal',
		'default'
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

	$class_options  = goodblocks_get_event_class_options();
	$class_list_id  = wp_unique_id( 'goodblocks-event-class-options-' );
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
						<?php if ( ! empty( $field['datalist'] ) && $class_options ) : ?>
							list="<?php echo esc_attr( $class_list_id ); ?>"
						<?php endif; ?>
					>
					<?php if ( '_event_class' === $meta_key && $class_options ) : ?>
						<datalist id="<?php echo esc_attr( $class_list_id ); ?>">
							<?php foreach ( $class_options as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>"></option>
							<?php endforeach; ?>
						</datalist>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( ! empty( $field['description'] ) ) : ?>
					<span style="display:block;margin-top:4px;color:#757575;font-size:12px;">
						<?php echo esc_html( $field['description'] ); ?>
					</span>
				<?php endif; ?>
			</p>
		<?php endforeach; ?>
	</div>
	<?php
}

function goodblocks_event_recurrence_render( WP_Post $post ): void {
	wp_nonce_field( 'goodblocks_event_recurrence', 'goodblocks_event_recurrence_nonce' );

	$frequency = (string) get_post_meta( $post->ID, '_event_recurrence_frequency', true );
	$interval  = max( 1, absint( get_post_meta( $post->ID, '_event_recurrence_interval', true ) ) );
	$weekdays  = goodblocks_parse_event_csv_meta( (string) get_post_meta( $post->ID, '_event_recurrence_weekdays', true ) );
	$until     = (string) get_post_meta( $post->ID, '_event_recurrence_until', true );
	$count     = absint( get_post_meta( $post->ID, '_event_recurrence_count', true ) );
	$exdates   = goodblocks_parse_event_csv_meta( (string) get_post_meta( $post->ID, '_event_recurrence_exdates', true ) );
	$parent_id = absint( get_post_meta( $post->ID, '_event_recurrence_parent', true ) );
	$original  = (string) get_post_meta( $post->ID, '_event_recurrence_original_start', true );
	$original_val = $original ? date( 'Y-m-d\TH:i', strtotime( $original ) ) : '';
	?>
	<div class="goodblocks-event-recurrence" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
		<p style="margin:0;">
			<label for="goodblocks_event_recurrence_frequency"><strong><?php esc_html_e( 'Repeat', 'goodblocks' ); ?></strong></label><br>
			<select id="goodblocks_event_recurrence_frequency" name="goodblocks_event_recurrence_frequency" style="width:100%;">
				<?php foreach ( goodblocks_event_recurrence_frequency_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $frequency, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p style="margin:0;">
			<label for="goodblocks_event_recurrence_interval"><strong><?php esc_html_e( 'Interval', 'goodblocks' ); ?></strong></label><br>
			<input type="number" min="1" max="100" id="goodblocks_event_recurrence_interval" name="goodblocks_event_recurrence_interval" style="width:100%;" value="<?php echo esc_attr( $interval ); ?>">
			<span style="display:block;margin-top:4px;color:#757575;font-size:12px;"><?php esc_html_e( 'Use 1 for every period, 2 for every other period, and so on.', 'goodblocks' ); ?></span>
		</p>

		<fieldset style="margin:0;grid-column:1/-1;">
			<legend><strong><?php esc_html_e( 'Repeat on weekdays', 'goodblocks' ); ?></strong></legend>
			<div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;">
				<?php foreach ( goodblocks_event_weekday_options() as $value => $label ) : ?>
					<label>
						<input type="checkbox" name="goodblocks_event_recurrence_weekdays[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $weekdays, true ) ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<span style="display:block;margin-top:4px;color:#757575;font-size:12px;"><?php esc_html_e( 'Used by weekly recurrence. Leave empty to repeat on the start date weekday.', 'goodblocks' ); ?></span>
		</fieldset>

		<p style="margin:0;">
			<label for="goodblocks_event_recurrence_until"><strong><?php esc_html_e( 'Repeat until', 'goodblocks' ); ?></strong></label><br>
			<input type="date" id="goodblocks_event_recurrence_until" name="goodblocks_event_recurrence_until" style="width:100%;" value="<?php echo esc_attr( $until ); ?>">
		</p>

		<p style="margin:0;">
			<label for="goodblocks_event_recurrence_count"><strong><?php esc_html_e( 'Maximum occurrences', 'goodblocks' ); ?></strong></label><br>
			<input type="number" min="0" max="1000" id="goodblocks_event_recurrence_count" name="goodblocks_event_recurrence_count" style="width:100%;" value="<?php echo esc_attr( $count ); ?>">
			<span style="display:block;margin-top:4px;color:#757575;font-size:12px;"><?php esc_html_e( 'Optional. Leave empty to use the calendar range.', 'goodblocks' ); ?></span>
		</p>

		<p style="margin:0;grid-column:1/-1;">
			<label for="goodblocks_event_recurrence_exdates"><strong><?php esc_html_e( 'Excluded dates', 'goodblocks' ); ?></strong></label><br>
			<textarea id="goodblocks_event_recurrence_exdates" name="goodblocks_event_recurrence_exdates" rows="3" style="width:100%;" placeholder="2026-12-24"><?php echo esc_textarea( implode( "\n", $exdates ) ); ?></textarea>
			<span style="display:block;margin-top:4px;color:#757575;font-size:12px;"><?php esc_html_e( 'One YYYY-MM-DD date per line.', 'goodblocks' ); ?></span>
		</p>

		<p style="margin:0;">
			<label for="goodblocks_event_recurrence_parent"><strong><?php esc_html_e( 'Override parent event ID', 'goodblocks' ); ?></strong></label><br>
			<input type="number" min="0" id="goodblocks_event_recurrence_parent" name="goodblocks_event_recurrence_parent" style="width:100%;" value="<?php echo esc_attr( $parent_id ); ?>">
			<span style="display:block;margin-top:4px;color:#757575;font-size:12px;"><?php esc_html_e( 'Optional. Use when this event replaces one occurrence in a recurring series.', 'goodblocks' ); ?></span>
		</p>

		<p style="margin:0;">
			<label for="goodblocks_event_recurrence_original_start"><strong><?php esc_html_e( 'Original occurrence start', 'goodblocks' ); ?></strong></label><br>
			<input type="datetime-local" id="goodblocks_event_recurrence_original_start" name="goodblocks_event_recurrence_original_start" style="width:100%;" value="<?php echo esc_attr( $original_val ); ?>">
			<span style="display:block;margin-top:4px;color:#757575;font-size:12px;"><?php esc_html_e( 'The original date/time this override replaces.', 'goodblocks' ); ?></span>
		</p>
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

	if ( isset( $_POST['goodblocks_event_recurrence_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['goodblocks_event_recurrence_nonce'] ), 'goodblocks_event_recurrence' ) ) {
		$frequency = isset( $_POST['goodblocks_event_recurrence_frequency'] ) ? sanitize_key( wp_unslash( $_POST['goodblocks_event_recurrence_frequency'] ) ) : '';
		if ( ! array_key_exists( $frequency, goodblocks_event_recurrence_frequency_options() ) ) {
			$frequency = '';
		}

		$interval = isset( $_POST['goodblocks_event_recurrence_interval'] ) ? absint( wp_unslash( $_POST['goodblocks_event_recurrence_interval'] ) ) : 1;
		$interval = min( 100, max( 1, $interval ) );

		$weekdays = [];
		if ( isset( $_POST['goodblocks_event_recurrence_weekdays'] ) && is_array( $_POST['goodblocks_event_recurrence_weekdays'] ) ) {
			$weekdays = goodblocks_sanitize_event_weekday_list( implode( ',', wp_unslash( $_POST['goodblocks_event_recurrence_weekdays'] ) ) );
		}

		$until = isset( $_POST['goodblocks_event_recurrence_until'] ) ? sanitize_text_field( wp_unslash( $_POST['goodblocks_event_recurrence_until'] ) ) : '';
		$until = goodblocks_sanitize_event_date_only( $until );

		$count = isset( $_POST['goodblocks_event_recurrence_count'] ) ? absint( wp_unslash( $_POST['goodblocks_event_recurrence_count'] ) ) : 0;
		$count = min( 1000, $count );

		$exdates = isset( $_POST['goodblocks_event_recurrence_exdates'] ) ? sanitize_textarea_field( wp_unslash( $_POST['goodblocks_event_recurrence_exdates'] ) ) : '';
		$exdates = goodblocks_sanitize_event_date_list( $exdates );

		update_post_meta( $post_id, '_event_recurrence_frequency', $frequency );
		update_post_meta( $post_id, '_event_recurrence_interval', $frequency ? $interval : 1 );
		update_post_meta( $post_id, '_event_recurrence_weekdays', $frequency ? implode( ',', $weekdays ) : '' );
		update_post_meta( $post_id, '_event_recurrence_until', $frequency ? $until : '' );
		update_post_meta( $post_id, '_event_recurrence_count', $frequency ? $count : 0 );
		update_post_meta( $post_id, '_event_recurrence_exdates', $frequency ? implode( ',', $exdates ) : '' );

		$parent_id = isset( $_POST['goodblocks_event_recurrence_parent'] ) ? absint( wp_unslash( $_POST['goodblocks_event_recurrence_parent'] ) ) : 0;
		if ( $parent_id === $post_id || 'goodblocks_event' !== get_post_type( $parent_id ) ) {
			$parent_id = 0;
		}

		$original = isset( $_POST['goodblocks_event_recurrence_original_start'] ) ? sanitize_text_field( wp_unslash( $_POST['goodblocks_event_recurrence_original_start'] ) ) : '';
		$original = goodblocks_normalize_event_import_date( $original );
		if ( '' === $original ) {
			$parent_id = 0;
		}

		update_post_meta( $post_id, '_event_recurrence_parent', $parent_id );
		update_post_meta( $post_id, '_event_recurrence_original_start', $parent_id ? $original : '' );
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

	$start_ts = goodblocks_event_local_timestamp( $start );
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
		$end_ts = goodblocks_event_local_timestamp( $end );
		if ( ! $end_ts || wp_date( 'Y-m-d', $start_ts ) === wp_date( 'Y-m-d', $end_ts ) ) {
			return $start_str;
		}
		return $start_str . ' – ' . wp_date( $date_fmt, $end_ts );
	}

	$start_str = wp_date( $date_fmt . ', ' . $time_fmt, $start_ts );

	if ( ! $end ) {
		return $start_str;
	}

	$end_ts = goodblocks_event_local_timestamp( $end );
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

function goodblocks_event_local_timestamp( string $raw ) {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return false;
	}

	$dt = date_create_immutable( $raw, wp_timezone() );

	return $dt ? $dt->getTimestamp() : false;
}

function goodblocks_sanitize_event_date_only( string $raw ): string {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return '';
	}

	$dt     = DateTime::createFromFormat( '!Y-m-d', $raw );
	$errors = DateTime::getLastErrors();

	if (
		! $dt ||
		( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ||
		$dt->format( 'Y-m-d' ) !== $raw
	) {
		return '';
	}

	return $raw;
}

function goodblocks_sanitize_event_date_list( string $raw ): array {
	$dates = preg_split( '/[\r\n,]+/', $raw );
	if ( ! is_array( $dates ) ) {
		return [];
	}

	$dates = array_map( 'goodblocks_sanitize_event_date_only', $dates );
	$dates = array_filter( $dates );
	$dates = array_values( array_unique( $dates ) );
	sort( $dates );

	return $dates;
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
		'category_slug'  => '',
		'from'           => '',
		'to'             => '',
		'order'          => 'ASC',
		'include_recurring' => true,
		'max_occurrences'   => 500,
		'max_source_events' => 500,
		'max_generated_events' => 5000,
	];
	$args     = wp_parse_args( $args, $defaults );
	$now      = current_time( 'mysql' );
	$from     = ! empty( $args['from'] ) ? sanitize_text_field( $args['from'] ) : '';
	$to       = ! empty( $args['to'] ) ? sanitize_text_field( $args['to'] ) : '';
	$limit    = (int) $args['posts_per_page'];

	if ( empty( $args['show_past'] ) && '' === $from ) {
		$from = $now;
	}

	$query_args = [
		'post_type'      => 'goodblocks_event',
		// Recurring events are expanded in PHP. Bound the source query so a large
		// event archive cannot cause an unbounded query on every block render.
		'posts_per_page' => ! empty( $args['include_recurring'] ) ? min( 1000, max( 1, absint( $args['max_source_events'] ) ) ) : $limit,
		'post_status'    => 'publish',
		'orderby'        => 'meta_value',
		'meta_key'       => '_event_start',
		'meta_type'      => 'DATETIME',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	];

	$meta_query = [];

	if ( empty( $args['include_recurring'] ) && empty( $args['show_past'] ) ) {
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

	if ( empty( $args['include_recurring'] ) && ! empty( $from ) ) {
		$meta_query[] = [
			'key'     => '_event_start',
			'value'   => $from,
			'compare' => '>=',
			'type'    => 'DATETIME',
		];
	}

	if ( empty( $args['include_recurring'] ) && ! empty( $to ) ) {
		$meta_query[] = [
			'key'     => '_event_start',
			'value'   => $to,
			'compare' => '<=',
			'type'    => 'DATETIME',
		];
	}

	if ( ! empty( $args['include_recurring'] ) && '' !== $from ) {
		// Keep old recurring parents eligible for virtual expansion, while avoiding
		// a source-query dominated by historic one-off events.
		$recurring_or_in_range = [
			'relation' => 'OR',
			[
				'key'     => '_event_recurrence_frequency',
				'value'   => '',
				'compare' => '!=',
			],
			[
				'relation' => 'AND',
				[
					'key'     => '_event_start',
					'value'   => $from,
					'compare' => '>=',
					'type'    => 'DATETIME',
				],
			],
		];

		if ( '' !== $to ) {
			$recurring_or_in_range[2][] = [
				'key'     => '_event_start',
				'value'   => $to,
				'compare' => '<=',
				'type'    => 'DATETIME',
			];
		}

		$meta_query[] = $recurring_or_in_range;
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

	if ( ! empty( $args['category_slug'] ) ) {
		$query_args['tax_query'] = [
			[
				'taxonomy' => 'event_category',
				'field'    => 'slug',
				'terms'    => sanitize_title( $args['category_slug'] ),
			],
		];
	}

	$query          = new WP_Query( $query_args );
	$event_post_ids = [];
	$events         = [];

	while ( $query->have_posts() ) {
		$query->the_post();
		$event_post_ids[] = get_the_ID();
	}

	wp_reset_postdata();

	$override_indexes = ! empty( $args['include_recurring'] ) ? goodblocks_get_event_override_indexes( $event_post_ids, $from, $to ) : [];
	$generated_limit  = min( 5000, max( 1, absint( $args['max_generated_events'] ) ) );

	foreach ( $event_post_ids as $event_post_id ) {
		if ( ! empty( $args['include_recurring'] ) ) {
			$remaining = $generated_limit - count( $events );
			if ( $remaining <= 0 ) {
				break;
			}

			$events = array_merge(
				$events,
				goodblocks_get_event_occurrences(
					$event_post_id,
					$from,
					$to,
					min( $remaining, absint( $args['max_occurrences'] ) ),
					$override_indexes[ $event_post_id ] ?? []
				)
			);
		} else {
			$events[] = goodblocks_get_event_data( $event_post_id );
		}
	}

	if ( ! empty( $args['include_recurring'] ) ) {
		$events = array_values( array_filter(
			$events,
			static fn( $event ) => goodblocks_event_occurrence_matches_range( $event['start'], $event['end'], $from, $to )
		) );
	}

	usort(
		$events,
		static function ( $a, $b ) {
			$a_start = strtotime( $a['start'] ) ?: 0;
			$b_start = strtotime( $b['start'] ) ?: 0;

			if ( $a_start === $b_start ) {
				return strcasecmp( $a['title'], $b['title'] );
			}

			return $a_start <=> $b_start;
		}
	);

	if ( 'DESC' === strtoupper( (string) $args['order'] ) ) {
		$events = array_reverse( $events );
	}

	if ( $limit < 0 ) {
		return $events;
	}

	return array_slice( $events, 0, absint( $limit ) );
}

function goodblocks_get_event_data( int $post_id, array $overrides = [] ): array {
	$start   = array_key_exists( 'start', $overrides ) ? (string) $overrides['start'] : (string) get_post_meta( $post_id, '_event_start', true );
	$end     = array_key_exists( 'end', $overrides ) ? (string) $overrides['end'] : (string) get_post_meta( $post_id, '_event_end', true );
	$all_day = (bool) get_post_meta( $post_id, '_event_all_day', true );
	$type    = (string) get_post_meta( $post_id, '_event_type', true );
	$status  = (string) get_post_meta( $post_id, '_event_status', true );
	$now_ts    = goodblocks_event_local_timestamp( current_time( 'mysql' ) );
	$start_ts  = $start ? goodblocks_event_local_timestamp( $start ) : false;
	$end_ts    = $end ? goodblocks_event_local_timestamp( $end ) : false;
	$frequency = (string) get_post_meta( $post_id, '_event_recurrence_frequency', true );

	return [
		'id'           => $post_id,
		'occurrence_id' => $overrides['occurrence_id'] ?? (string) $post_id,
		'title'        => get_the_title( $post_id ),
		'url'          => get_permalink( $post_id ),
		'start'        => $start,
		'end'          => $end,
		'all_day'      => $all_day,
		'date_key'     => $start_ts ? wp_date( 'Y-m-d', $start_ts ) : '',
		'date_label'   => $start_ts ? wp_date( get_option( 'date_format' ), $start_ts ) : '',
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
		'is_recurring' => '' !== $frequency,
		'recurrence'   => $frequency,
		'is_exception' => ! empty( $overrides['is_exception'] ),
		'excerpt'      => get_the_excerpt( $post_id ),
	];
}

function goodblocks_get_event_occurrences( int $post_id, string $from = '', string $to = '', int $limit = 500, ?array $overrides = null ): array {
	$start = (string) get_post_meta( $post_id, '_event_start', true );
	if ( '' === $start ) {
		return [];
	}

	$parent_id = absint( get_post_meta( $post_id, '_event_recurrence_parent', true ) );
	if ( $parent_id ) {
		return [ goodblocks_get_event_data( $post_id, [ 'is_exception' => true ] ) ];
	}

	$frequency = (string) get_post_meta( $post_id, '_event_recurrence_frequency', true );
	if ( '' === $frequency ) {
		return [ goodblocks_get_event_data( $post_id ) ];
	}

	$timezone   = wp_timezone();
	$start_dt   = date_create_immutable( $start, $timezone );
	$end        = (string) get_post_meta( $post_id, '_event_end', true );
	$end_dt     = $end ? date_create_immutable( $end, $timezone ) : false;
	$duration   = $end_dt && $start_dt ? max( 0, $end_dt->getTimestamp() - $start_dt->getTimestamp() ) : 0;
	$interval   = max( 1, absint( get_post_meta( $post_id, '_event_recurrence_interval', true ) ) );
	$until      = goodblocks_sanitize_event_date_only( (string) get_post_meta( $post_id, '_event_recurrence_until', true ) );
	$count      = absint( get_post_meta( $post_id, '_event_recurrence_count', true ) );
	$exdates    = goodblocks_parse_event_csv_meta( (string) get_post_meta( $post_id, '_event_recurrence_exdates', true ) );
	$limit      = min( 1000, max( 1, $limit ) );
	$occurrences = [];
	$overrides  = null === $overrides ? goodblocks_get_event_override_index( $post_id ) : $overrides;

	if ( ! $start_dt ) {
		return [];
	}

	$window_start = $from ? date_create_immutable( $from, $timezone ) : $start_dt;
	if ( ! $window_start ) {
		$window_start = $start_dt;
	}

	$window_end = $to ? date_create_immutable( $to, $timezone ) : false;
	if ( ! $window_end ) {
		$window_end = $until ? date_create_immutable( $until . ' 23:59:59', $timezone ) : $window_start->modify( '+1 year' );
	}

	if ( $until ) {
		$until_dt = date_create_immutable( $until . ' 23:59:59', $timezone );
		if ( $until_dt && $until_dt < $window_end ) {
			$window_end = $until_dt;
		}
	}

	if ( 'weekly' === $frequency ) {
		return goodblocks_get_weekly_event_occurrences( $post_id, $start_dt, $duration, $window_start, $window_end, $interval, $count, $exdates, $overrides, $limit );
	}

	$cursor    = $start_dt;
	$generated = 0;
	$monthly_day = (int) $start_dt->format( 'j' );

	while ( $cursor <= $window_end && count( $occurrences ) < $limit ) {
		$generated++;
		if ( $count && $generated > $count ) {
			break;
		}

		goodblocks_maybe_add_event_occurrence( $occurrences, $post_id, $cursor, $duration, $window_start, $window_end, $exdates, $overrides );

		if ( 'daily' === $frequency ) {
			$cursor = $cursor->modify( '+' . $interval . ' days' );
		} elseif ( 'monthly' === $frequency ) {
			$cursor = goodblocks_event_add_months( $cursor, $interval, $monthly_day );
		} elseif ( 'yearly' === $frequency ) {
			$cursor = goodblocks_event_add_years( $cursor, $interval, (int) $start_dt->format( 'm' ), $monthly_day );
		} else {
			break;
		}
	}

	return $occurrences;
}

/**
 * Advance a monthly recurrence while keeping its original day-of-month anchor.
 *
 * For example, a series starting on January 31 occurs on February 28 and then
 * returns to March 31 rather than drifting permanently to the 28th.
 */
function goodblocks_event_add_months( DateTimeImmutable $date, int $months, int $day_of_month ): DateTimeImmutable {
	$target = $date->modify( 'first day of +' . max( 1, $months ) . ' months' );
	$day    = min( max( 1, $day_of_month ), (int) $target->format( 't' ) );

	return $target->setDate(
		(int) $target->format( 'Y' ),
		(int) $target->format( 'n' ),
		$day
	)->setTime(
		(int) $date->format( 'H' ),
		(int) $date->format( 'i' ),
		(int) $date->format( 's' )
	);
}

/**
 * Advance a yearly recurrence while retaining its original month/day anchor.
 *
 * Leap-day series fall on February 28 in non-leap years and return to February
 * 29 in the next leap year.
 */
function goodblocks_event_add_years( DateTimeImmutable $date, int $years, int $month, int $day_of_month ): DateTimeImmutable {
	$target_year = (int) $date->format( 'Y' ) + max( 1, $years );
	$month       = min( 12, max( 1, $month ) );
	$days_in_month = (int) ( new DateTimeImmutable( sprintf( '%04d-%02d-01', $target_year, $month ), $date->getTimezone() ) )->format( 't' );
	$day = min( max( 1, $day_of_month ), $days_in_month );

	return $date->setDate( $target_year, $month, $day );
}

function goodblocks_get_weekly_event_occurrences( int $post_id, DateTimeImmutable $start_dt, int $duration, DateTimeImmutable $window_start, DateTimeImmutable $window_end, int $interval, int $count, array $exdates, array $overrides, int $limit ): array {
	$weekday_indexes = [
		'mon' => 1,
		'tue' => 2,
		'wed' => 3,
		'thu' => 4,
		'fri' => 5,
		'sat' => 6,
		'sun' => 7,
	];
	$weekdays = goodblocks_parse_event_csv_meta( (string) get_post_meta( $post_id, '_event_recurrence_weekdays', true ) );
	$weekdays = array_values( array_intersect( $weekdays, array_keys( $weekday_indexes ) ) );

	if ( ! $weekdays ) {
		$weekdays = [ array_search( (int) $start_dt->format( 'N' ), $weekday_indexes, true ) ?: 'mon' ];
	}

	usort(
		$weekdays,
		static fn( $a, $b ) => $weekday_indexes[ $a ] <=> $weekday_indexes[ $b ]
	);

	$week_start  = $start_dt->modify( 'monday this week' );
	$occurrences = [];
	$generated   = 0;

	while ( $week_start <= $window_end && count( $occurrences ) < $limit ) {
		foreach ( $weekdays as $weekday ) {
			$candidate = $week_start
				->modify( '+' . ( $weekday_indexes[ $weekday ] - 1 ) . ' days' )
				->setTime( (int) $start_dt->format( 'H' ), (int) $start_dt->format( 'i' ), (int) $start_dt->format( 's' ) );

			if ( $candidate < $start_dt ) {
				continue;
			}

			$generated++;
			if ( $count && $generated > $count ) {
				break 2;
			}

			goodblocks_maybe_add_event_occurrence( $occurrences, $post_id, $candidate, $duration, $window_start, $window_end, $exdates, $overrides );

			if ( count( $occurrences ) >= $limit ) {
				break 2;
			}
		}

		$week_start = $week_start->modify( '+' . $interval . ' weeks' );
	}

	return $occurrences;
}

function goodblocks_maybe_add_event_occurrence( array &$occurrences, int $post_id, DateTimeImmutable $start_dt, int $duration, DateTimeImmutable $window_start, DateTimeImmutable $window_end, array $exdates, array $overrides = [] ): void {
	$date_key = $start_dt->format( 'Y-m-d' );
	if ( in_array( $date_key, $exdates, true ) ) {
		return;
	}

	if ( goodblocks_event_occurrence_is_overridden( $start_dt, $overrides ) ) {
		return;
	}

	$end_dt = $duration > 0 ? $start_dt->modify( '+' . $duration . ' seconds' ) : false;
	if ( ! goodblocks_event_occurrence_matches_range(
		$start_dt->format( 'Y-m-d H:i:s' ),
		$end_dt ? $end_dt->format( 'Y-m-d H:i:s' ) : '',
		$window_start->format( 'Y-m-d H:i:s' ),
		$window_end->format( 'Y-m-d H:i:s' )
	) ) {
		return;
	}

	$occurrences[] = goodblocks_get_event_data( $post_id, [
		'start'         => $start_dt->format( 'Y-m-d H:i:s' ),
		'end'           => $end_dt ? $end_dt->format( 'Y-m-d H:i:s' ) : '',
		'occurrence_id' => $post_id . ':' . $date_key,
	] );
}

function goodblocks_get_event_override_index( int $parent_id, string $from = '', string $to = '' ): array {
	$indexes = goodblocks_get_event_override_indexes( [ $parent_id ], $from, $to );

	return $indexes[ $parent_id ] ?? [];
}

/**
 * Build override indexes for a batch of recurrence parents in one query.
 *
 * @param int[] $parent_ids Parent event IDs.
 * @return array<int, array<string, true>>
 */
function goodblocks_get_event_override_indexes( array $parent_ids, string $from = '', string $to = '' ): array {
	$parent_ids = array_values( array_unique( array_filter( array_map( 'absint', $parent_ids ) ) ) );
	if ( ! $parent_ids ) {
		return [];
	}

	$query_args = [
		'post_type'      => 'goodblocks_event',
		'post_status'    => 'publish',
		// Fetch the complete relevant set in one query. A partial index would show
		// both an original occurrence and its replacement.
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [
			'relation' => 'AND',
			[
				'key'   => '_event_recurrence_parent',
				'value' => $parent_ids,
				'compare' => 'IN',
				'type'  => 'NUMERIC',
			],
		],
	];

	if ( '' !== $to ) {
		// Do not apply a lower bound: an original multi-day occurrence may start
		// before the requested window and still overlap it.
		$query_args['meta_query'][] = [
			'key'     => '_event_recurrence_original_start',
			'value'   => $to,
			'compare' => '<=',
			'type'    => 'DATETIME',
		];
	}

	$override_ids = get_posts( $query_args );
	$indexes      = [];

	foreach ( $override_ids as $override_id ) {
		$parent_id = absint( get_post_meta( $override_id, '_event_recurrence_parent', true ) );
		if ( ! $parent_id ) {
			continue;
		}

		$original = (string) get_post_meta( $override_id, '_event_recurrence_original_start', true );
		if ( '' === $original ) {
			continue;
		}

		$timestamp = goodblocks_event_local_timestamp( $original );
		if ( ! $timestamp ) {
			continue;
		}

		$indexes[ $parent_id ][ wp_date( 'Y-m-d H:i:s', $timestamp ) ] = true;
	}

	return $indexes;
}

function goodblocks_event_occurrence_is_overridden( DateTimeImmutable $start_dt, array $overrides ): bool {
	return ! empty( $overrides[ $start_dt->format( 'Y-m-d H:i:s' ) ] );
}

function goodblocks_event_occurrence_matches_range( string $start, string $end = '', string $from = '', string $to = '' ): bool {
	$start_ts = $start ? goodblocks_event_local_timestamp( $start ) : false;
	if ( ! $start_ts ) {
		return false;
	}

	$end_ts  = $end ? goodblocks_event_local_timestamp( $end ) : $start_ts;
	$from_ts = $from ? goodblocks_event_local_timestamp( $from ) : false;
	$to_ts   = $to ? goodblocks_event_local_timestamp( $to ) : false;

	if ( $from_ts && $end_ts < $from_ts ) {
		return false;
	}

	if ( $to_ts && $start_ts > $to_ts ) {
		return false;
	}

	return true;
}

function goodblocks_format_event_time( string $start, string $end = '', bool $all_day = false ): string {
	if ( ! $start ) {
		return '';
	}

	if ( $all_day ) {
		return __( 'All day', 'goodblocks' );
	}

	$start_ts = goodblocks_event_local_timestamp( $start );
	if ( ! $start_ts ) {
		return '';
	}

	$time_fmt = get_option( 'time_format' );
	$label    = wp_date( $time_fmt, $start_ts );
	$end_ts   = $end ? goodblocks_event_local_timestamp( $end ) : false;

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

		<p><?php esc_html_e( 'Upload a CSV with headers: title,start,end,class,type,venue,stream,results,status,all_day,excerpt,recurrence_frequency,recurrence_interval,recurrence_weekdays,recurrence_until,recurrence_count,recurrence_exdates,recurrence_parent,recurrence_original_start.', 'goodblocks' ); ?></p>
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
		goodblocks_update_event_recurrence_from_import( $post_id, $data );

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

function goodblocks_update_event_recurrence_from_import( int $post_id, array $data ): void {
	$parent_id = goodblocks_resolve_event_import_parent( $data['recurrence_parent'] ?? '' );
	if ( $parent_id === $post_id ) {
		$parent_id = 0;
	}

	$original = goodblocks_normalize_event_import_date( $data['recurrence_original_start'] ?? '' );
	if ( $parent_id && '' === $original ) {
		$parent_id = 0;
	}

	$frequency = sanitize_key( $data['recurrence_frequency'] ?? '' );
	if ( ! array_key_exists( $frequency, goodblocks_event_recurrence_frequency_options() ) ) {
		$frequency = '';
	}
	if ( $parent_id ) {
		$frequency = '';
	}

	$interval = max( 1, min( 100, absint( $data['recurrence_interval'] ?? 1 ) ) );
	$count    = max( 0, min( 1000, absint( $data['recurrence_count'] ?? 0 ) ) );
	$until    = goodblocks_sanitize_event_date_only( (string) ( $data['recurrence_until'] ?? '' ) );
	$exdates  = goodblocks_sanitize_event_date_list( (string) ( $data['recurrence_exdates'] ?? '' ) );
	$weekdays = goodblocks_sanitize_event_weekday_list( (string) ( $data['recurrence_weekdays'] ?? '' ) );

	update_post_meta( $post_id, '_event_recurrence_frequency', $frequency );
	update_post_meta( $post_id, '_event_recurrence_interval', $frequency ? $interval : 1 );
	update_post_meta( $post_id, '_event_recurrence_weekdays', $frequency ? implode( ',', $weekdays ) : '' );
	update_post_meta( $post_id, '_event_recurrence_until', $frequency ? $until : '' );
	update_post_meta( $post_id, '_event_recurrence_count', $frequency ? $count : 0 );
	update_post_meta( $post_id, '_event_recurrence_exdates', $frequency ? implode( ',', $exdates ) : '' );
	update_post_meta( $post_id, '_event_recurrence_parent', $parent_id );
	update_post_meta( $post_id, '_event_recurrence_original_start', $parent_id ? $original : '' );
}

function goodblocks_sanitize_event_weekday_list( string $raw ): array {
	$weekdays = preg_split( '/[\r\n,]+/', strtolower( $raw ) );
	if ( ! is_array( $weekdays ) ) {
		return [];
	}

	$allowed = array_keys( goodblocks_event_weekday_options() );
	$weekdays = array_map( 'sanitize_key', $weekdays );
	$weekdays = array_values( array_intersect( $weekdays, $allowed ) );

	return array_values( array_unique( $weekdays ) );
}

function goodblocks_resolve_event_import_parent( string $raw ): int {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return 0;
	}

	if ( is_numeric( $raw ) ) {
		$post_id = absint( $raw );
		return 'goodblocks_event' === get_post_type( $post_id ) ? $post_id : 0;
	}

	$by_slug = get_page_by_path( sanitize_title( $raw ), OBJECT, 'goodblocks_event' );
	if ( $by_slug instanceof WP_Post ) {
		return $by_slug->ID;
	}

	$by_title = get_posts( [
		'post_type'              => 'goodblocks_event',
		'post_status'            => 'any',
		'title'                  => $raw,
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	] );

	return $by_title ? absint( $by_title[0] ) : 0;
}

function goodblocks_redirect_event_import( array $result ): void {
	set_transient( 'goodblocks_event_import_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'edit.php?post_type=goodblocks_event&page=goodblocks-event-import' ) );
	exit;
}
