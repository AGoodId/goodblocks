<?php
/**
 * GoodBlocks Popups — CPT + automatic wp_footer rendering.
 *
 * Create a popup under Popups in the admin menu.
 * Configure trigger, display rules, scheduling and frequency in the meta box.
 * Only the highest-priority eligible campaign is injected on a front-end page.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init',                              'goodblocks_register_popup_cpt' );
add_action( 'add_meta_boxes',                    'goodblocks_popup_add_meta_box' );
add_action( 'save_post_goodblocks_popup',        'goodblocks_popup_save_meta', 10, 2 );
add_action( 'wp_footer',                         'goodblocks_render_popups' );
add_action( 'wp_enqueue_scripts',                'goodblocks_popup_enqueue_assets' );
add_action( 'transition_post_status',            'goodblocks_maybe_flush_popup_cache', 10, 3 );
add_action( 'before_delete_post',                'goodblocks_flush_popup_cache_on_delete' );

function goodblocks_register_popup_cpt(): void {
	register_post_type( 'goodblocks_popup', [
		'labels'       => [
			'name'          => __( 'Popups', 'goodblocks' ),
			'singular_name' => __( 'Popup', 'goodblocks' ),
			'add_new_item'  => __( 'Add New Popup', 'goodblocks' ),
			'edit_item'     => __( 'Edit Popup', 'goodblocks' ),
			'menu_name'     => __( 'Popups', 'goodblocks' ),
		],
		'public'        => false,
		'show_ui'       => true,
		'show_in_menu'  => true,
		'show_in_rest'  => true,
		'supports'      => [ 'title', 'editor' ],
		'menu_icon'     => 'dashicons-megaphone',
		'menu_position' => 21,
	] );
}

function goodblocks_popup_add_meta_box(): void {
	add_meta_box(
		'goodblocks_popup_settings',
		__( 'Popup Settings', 'goodblocks' ),
		'goodblocks_popup_meta_box_render',
		'goodblocks_popup',
		'side',
		'high'
	);
}

function goodblocks_popup_meta_box_render( WP_Post $post ): void {
	wp_nonce_field( 'goodblocks_popup_settings', 'goodblocks_popup_nonce' );

	$trigger     = get_post_meta( $post->ID, '_popup_trigger', true )        ?: 'time';
	$delay       = absint( get_post_meta( $post->ID, '_popup_delay', true )  ?: 3 );
	$scroll      = absint( get_post_meta( $post->ID, '_popup_scroll_percent', true ) ?: 50 );
	$cookie_name = get_post_meta( $post->ID, '_popup_cookie_name', true )    ?: 'gb_popup_' . $post->ID;
	$cookie_days = absint( get_post_meta( $post->ID, '_popup_cookie_days', true ) ?: 7 );
	$priority    = absint( get_post_meta( $post->ID, '_popup_priority', true ) ?: 10 );
	$max_views   = absint( get_post_meta( $post->ID, '_popup_max_impressions', true ) ?: 1 );
	$device      = get_post_meta( $post->ID, '_popup_device', true ) ?: 'all';
	$display     = get_post_meta( $post->ID, '_popup_display_mode', true ) ?: 'all';
	$target_pages = goodblocks_popup_sanitize_target_pages( get_post_meta( $post->ID, '_popup_target_pages', true ) );
	$start_at    = get_post_meta( $post->ID, '_popup_start_at', true );
	$end_at      = get_post_meta( $post->ID, '_popup_end_at', true );
	$pages       = get_pages( [ 'sort_column' => 'post_title', 'sort_order' => 'ASC' ] );
	?>
	<p>
		<label for="popup_trigger"><strong><?php esc_html_e( 'Trigger', 'goodblocks' ); ?></strong></label><br>
		<select name="popup_trigger" id="popup_trigger" style="width:100%;">
			<option value="time"   <?php selected( $trigger, 'time' ); ?>><?php esc_html_e( 'After delay (seconds)', 'goodblocks' ); ?></option>
			<option value="scroll" <?php selected( $trigger, 'scroll' ); ?>><?php esc_html_e( 'After scroll (%)', 'goodblocks' ); ?></option>
			<option value="exit"   <?php selected( $trigger, 'exit' ); ?>><?php esc_html_e( 'Exit intent (desktop)', 'goodblocks' ); ?></option>
		</select>
	</p>
	<p id="popup_delay_row">
		<label for="popup_delay"><strong><?php esc_html_e( 'Delay (seconds)', 'goodblocks' ); ?></strong></label><br>
		<input type="number" name="popup_delay" id="popup_delay"
			value="<?php echo esc_attr( $delay ); ?>" min="0" max="60" style="width:100%;">
	</p>
	<p id="popup_scroll_row" style="display:none;">
		<label for="popup_scroll_percent"><strong><?php esc_html_e( 'Scroll % to trigger', 'goodblocks' ); ?></strong></label><br>
		<input type="number" name="popup_scroll_percent" id="popup_scroll_percent"
			value="<?php echo esc_attr( $scroll ); ?>" min="5" max="95" style="width:100%;">
	</p>
	<hr>
	<p>
		<label for="popup_priority"><strong><?php esc_html_e( 'Campaign priority', 'goodblocks' ); ?></strong></label><br>
		<input type="number" name="popup_priority" id="popup_priority" value="<?php echo esc_attr( $priority ); ?>" min="1" max="100" style="width:100%;">
		<span class="description"><?php esc_html_e( 'If several campaigns match, only the highest priority is shown.', 'goodblocks' ); ?></span>
	</p>
	<p>
		<label for="popup_device"><strong><?php esc_html_e( 'Show on', 'goodblocks' ); ?></strong></label><br>
		<select name="popup_device" id="popup_device" style="width:100%;">
			<option value="all" <?php selected( $device, 'all' ); ?>><?php esc_html_e( 'All devices', 'goodblocks' ); ?></option>
			<option value="desktop" <?php selected( $device, 'desktop' ); ?>><?php esc_html_e( 'Desktop only', 'goodblocks' ); ?></option>
			<option value="mobile" <?php selected( $device, 'mobile' ); ?>><?php esc_html_e( 'Mobile only', 'goodblocks' ); ?></option>
		</select>
	</p>
	<p>
		<label for="popup_display_mode"><strong><?php esc_html_e( 'Page rules', 'goodblocks' ); ?></strong></label><br>
		<select name="popup_display_mode" id="popup_display_mode" style="width:100%;">
			<option value="all" <?php selected( $display, 'all' ); ?>><?php esc_html_e( 'Every page', 'goodblocks' ); ?></option>
			<option value="include" <?php selected( $display, 'include' ); ?>><?php esc_html_e( 'Only selected pages', 'goodblocks' ); ?></option>
			<option value="exclude" <?php selected( $display, 'exclude' ); ?>><?php esc_html_e( 'Every page except selected pages', 'goodblocks' ); ?></option>
		</select>
	</p>
	<p id="popup_target_pages_row">
		<label for="popup_target_pages"><strong><?php esc_html_e( 'Selected pages', 'goodblocks' ); ?></strong></label><br>
		<select name="popup_target_pages[]" id="popup_target_pages" multiple size="7" style="width:100%;">
			<?php foreach ( $pages as $page ) : ?>
				<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( in_array( (int) $page->ID, $target_pages, true ) ); ?>><?php echo esc_html( $page->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description"><?php esc_html_e( 'Use Cmd/Ctrl-click to select more than one page.', 'goodblocks' ); ?></span>
	</p>
	<p>
		<label for="popup_start_at"><strong><?php esc_html_e( 'Start showing', 'goodblocks' ); ?></strong></label><br>
		<input type="datetime-local" name="popup_start_at" id="popup_start_at" value="<?php echo esc_attr( $start_at ); ?>" style="width:100%;">
	</p>
	<p>
		<label for="popup_end_at"><strong><?php esc_html_e( 'Stop showing', 'goodblocks' ); ?></strong></label><br>
		<input type="datetime-local" name="popup_end_at" id="popup_end_at" value="<?php echo esc_attr( $end_at ); ?>" style="width:100%;">
	</p>
	<hr>
	<p>
		<label for="popup_cookie_name"><strong><?php esc_html_e( 'Cookie name', 'goodblocks' ); ?></strong></label><br>
		<input type="text" name="popup_cookie_name" id="popup_cookie_name"
			value="<?php echo esc_attr( $cookie_name ); ?>" style="width:100%;">
		<span class="description"><?php esc_html_e( 'Must be unique per popup on this site.', 'goodblocks' ); ?></span>
	</p>
	<p>
		<label for="popup_max_impressions"><strong><?php esc_html_e( 'Maximum displays per visitor', 'goodblocks' ); ?></strong></label><br>
		<input type="number" name="popup_max_impressions" id="popup_max_impressions" value="<?php echo esc_attr( $max_views ); ?>" min="1" max="20" style="width:100%;">
		<span class="description"><?php esc_html_e( 'The frequency cookie is written when the popup is shown, not only when it is closed.', 'goodblocks' ); ?></span>
	</p>
	<p>
		<label for="popup_cookie_days"><strong><?php esc_html_e( 'Hide for (days)', 'goodblocks' ); ?></strong></label><br>
		<input type="number" name="popup_cookie_days" id="popup_cookie_days"
			value="<?php echo esc_attr( $cookie_days ); ?>" min="1" max="365" style="width:100%;">
	</p>
	<script>
	( function () {
		var trigger    = document.getElementById( 'popup_trigger' );
		var delayRow   = document.getElementById( 'popup_delay_row' );
		var scrollRow  = document.getElementById( 'popup_scroll_row' );
		var display    = document.getElementById( 'popup_display_mode' );
		var targetPagesRow = document.getElementById( 'popup_target_pages_row' );
		function toggle() {
			delayRow.style.display  = trigger.value === 'time'   ? '' : 'none';
			scrollRow.style.display = trigger.value === 'scroll' ? '' : 'none';
			targetPagesRow.style.display = display.value === 'all' ? 'none' : '';
		}
		trigger.addEventListener( 'change', toggle );
		display.addEventListener( 'change', toggle );
		toggle();
	} )();
	</script>
	<?php
}

function goodblocks_popup_save_meta( int $post_id, WP_Post $post ): void {
	if ( ! isset( $_POST['goodblocks_popup_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( $_POST['goodblocks_popup_nonce'] ), 'goodblocks_popup_settings' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$allowed_triggers = [ 'time', 'scroll', 'exit' ];
	$trigger = sanitize_key( wp_unslash( $_POST['popup_trigger'] ?? 'time' ) );

	update_post_meta( $post_id, '_popup_trigger',        in_array( $trigger, $allowed_triggers, true ) ? $trigger : 'time' );
	update_post_meta( $post_id, '_popup_delay',          absint( $_POST['popup_delay'] ?? 3 ) );
	update_post_meta( $post_id, '_popup_scroll_percent', absint( $_POST['popup_scroll_percent'] ?? 50 ) );
	update_post_meta( $post_id, '_popup_cookie_name',    sanitize_key( wp_unslash( $_POST['popup_cookie_name'] ?? ( 'gb_popup_' . $post_id ) ) ) );
	update_post_meta( $post_id, '_popup_cookie_days',    absint( $_POST['popup_cookie_days'] ?? 7 ) );
	update_post_meta( $post_id, '_popup_priority', absint( $_POST['popup_priority'] ?? 10 ) ?: 10 );
	update_post_meta( $post_id, '_popup_max_impressions', max( 1, min( 20, absint( $_POST['popup_max_impressions'] ?? 1 ) ) ) );
	$device = sanitize_key( wp_unslash( $_POST['popup_device'] ?? 'all' ) );
	update_post_meta( $post_id, '_popup_device', in_array( $device, [ 'all', 'desktop', 'mobile' ], true ) ? $device : 'all' );
	$display = sanitize_key( wp_unslash( $_POST['popup_display_mode'] ?? 'all' ) );
	update_post_meta( $post_id, '_popup_display_mode', in_array( $display, [ 'all', 'include', 'exclude' ], true ) ? $display : 'all' );
	update_post_meta( $post_id, '_popup_target_pages', goodblocks_popup_sanitize_target_pages( wp_unslash( $_POST['popup_target_pages'] ?? [] ) ) );
	update_post_meta( $post_id, '_popup_start_at', goodblocks_popup_sanitize_datetime( wp_unslash( $_POST['popup_start_at'] ?? '' ) ) );
	update_post_meta( $post_id, '_popup_end_at', goodblocks_popup_sanitize_datetime( wp_unslash( $_POST['popup_end_at'] ?? '' ) ) );
}

/** Keep only existing, positive page IDs from the campaign page rule. */
function goodblocks_popup_sanitize_target_pages( $pages ): array {
	return array_values( array_unique( array_filter( array_map( 'absint', (array) $pages ) ) ) );
}

/** Store local WordPress datetimes in the native datetime-local format. */
function goodblocks_popup_sanitize_datetime( $value ): string {
	$value = sanitize_text_field( (string) $value );
	return preg_match( '/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}$/', $value ) ? $value : '';
}

function goodblocks_render_popups(): void {
	if ( ! goodblocks_has_popups() ) {
		return;
	}

	$popups = get_posts( [
		'post_type'      => 'goodblocks_popup',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	] );

	$popups = array_values( array_filter( $popups, 'goodblocks_popup_is_eligible' ) );
	if ( empty( $popups ) ) {
		return;
	}

	usort( $popups, static function ( WP_Post $a, WP_Post $b ): int {
		return absint( get_post_meta( $b->ID, '_popup_priority', true ) ?: 10 ) <=> absint( get_post_meta( $a->ID, '_popup_priority', true ) ?: 10 );
	} );

	$popup      = $popups[0];
	$trigger    = sanitize_key( get_post_meta( $popup->ID, '_popup_trigger', true ) ?: 'time' );
	$delay      = absint( get_post_meta( $popup->ID, '_popup_delay', true ) ?: 3 );
	$scroll     = absint( get_post_meta( $popup->ID, '_popup_scroll_percent', true ) ?: 50 );
	$cookie_name = sanitize_key( get_post_meta( $popup->ID, '_popup_cookie_name', true ) ?: ( 'gb_popup_' . $popup->ID ) );
	$cookie_days = max( 1, absint( get_post_meta( $popup->ID, '_popup_cookie_days', true ) ?: 7 ) );
	$max_views  = max( 1, absint( get_post_meta( $popup->ID, '_popup_max_impressions', true ) ?: 1 ) );
	$device     = sanitize_key( get_post_meta( $popup->ID, '_popup_device', true ) ?: 'all' );
	$content    = apply_filters( 'the_content', $popup->post_content );
	?>
		<div class="wp-block-goodblocks-popup"
			style="display:none;"
			data-trigger="<?php echo esc_attr( $trigger ); ?>"
			data-delay="<?php echo esc_attr( $delay ); ?>"
			data-scroll-percent="<?php echo esc_attr( $scroll ); ?>"
			data-cookie-name="<?php echo esc_attr( $cookie_name ); ?>"
			data-cookie-days="<?php echo esc_attr( $cookie_days ); ?>"
			data-max-impressions="<?php echo esc_attr( $max_views ); ?>"
			data-device="<?php echo esc_attr( $device ); ?>"
			aria-hidden="true"
		>
			<div class="popup-backdrop" aria-hidden="true"></div>
			<div class="popup-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( get_the_title( $popup ) ); ?>" tabindex="-1">
				<button type="button" class="popup-close" aria-label="<?php esc_attr_e( 'Close', 'goodblocks' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
				<div class="popup-content">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</div>
	<?php
}

/** Decide whether a campaign applies to the current page and schedule. */
function goodblocks_popup_is_eligible( WP_Post $popup ): bool {
	$now   = wp_date( 'Y-m-d\\TH:i', current_time( 'timestamp' ) );
	$start = goodblocks_popup_sanitize_datetime( get_post_meta( $popup->ID, '_popup_start_at', true ) );
	$end   = goodblocks_popup_sanitize_datetime( get_post_meta( $popup->ID, '_popup_end_at', true ) );
	if ( ( $start && $start > $now ) || ( $end && $end < $now ) ) {
		return false;
	}

	$mode    = sanitize_key( get_post_meta( $popup->ID, '_popup_display_mode', true ) ?: 'all' );
	$targets = goodblocks_popup_sanitize_target_pages( get_post_meta( $popup->ID, '_popup_target_pages', true ) );
	$page_id = get_queried_object_id();
	if ( 'include' === $mode ) {
		return $page_id && in_array( $page_id, $targets, true );
	}

	return !( 'exclude' === $mode && $page_id && in_array( $page_id, $targets, true ) );
}

function goodblocks_popup_enqueue_assets(): void {
	if ( ! goodblocks_has_popups() ) {
		return;
	}

	$asset_file = GOODBLOCKS_DIR . 'build/blocks/popup/view.asset.php';
	$asset      = file_exists( $asset_file ) ? require $asset_file : [ 'dependencies' => [], 'version' => GOODBLOCKS_VERSION ];

	wp_enqueue_script(
		'goodblocks-popup-view',
		GOODBLOCKS_URI . 'build/blocks/popup/view.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_enqueue_style(
		'goodblocks-popup-style',
		GOODBLOCKS_URI . 'build/blocks/popup/style-index.css',
		[],
		GOODBLOCKS_VERSION
	);
}

/**
 * Whether any published popup exists (cached).
 *
 * Both the footer renderer and the asset enqueue run on every front-end page.
 * Without this, each would fire an uncached query on every request. The boolean
 * is cached until a popup is published, unpublished, trashed or deleted.
 */
function goodblocks_has_popups(): bool {
	$cached = get_transient( 'goodblocks_has_popups' );

	if ( '1' === $cached || '0' === $cached ) {
		return '1' === $cached;
	}

	$ids = get_posts( [
		'post_type'      => 'goodblocks_popup',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	] );

	$has = ! empty( $ids );
	set_transient( 'goodblocks_has_popups', $has ? '1' : '0', DAY_IN_SECONDS );

	return $has;
}

/**
 * Clear the cached popup existence flag.
 */
function goodblocks_flush_popup_cache(): void {
	delete_transient( 'goodblocks_has_popups' );
}

/**
 * Flush the popup cache when a popup changes publish status.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function goodblocks_maybe_flush_popup_cache( $new_status, $old_status, $post ): void {
	if ( isset( $post->post_type ) && 'goodblocks_popup' === $post->post_type ) {
		goodblocks_flush_popup_cache();
	}
}

/**
 * Flush the popup cache when a popup is permanently deleted.
 *
 * @param int $post_id Post ID being deleted.
 */
function goodblocks_flush_popup_cache_on_delete( $post_id ): void {
	if ( 'goodblocks_popup' === get_post_type( $post_id ) ) {
		goodblocks_flush_popup_cache();
	}
}
