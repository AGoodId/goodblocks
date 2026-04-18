<?php
/**
 * GoodBlocks Popups — CPT + automatic wp_footer rendering.
 *
 * Create a popup under Popups in the admin menu.
 * Configure trigger, delay and cookie in the meta box.
 * All published popups are injected automatically on every front-end page.
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
		<label for="popup_cookie_name"><strong><?php esc_html_e( 'Cookie name', 'goodblocks' ); ?></strong></label><br>
		<input type="text" name="popup_cookie_name" id="popup_cookie_name"
			value="<?php echo esc_attr( $cookie_name ); ?>" style="width:100%;">
		<span class="description"><?php esc_html_e( 'Must be unique per popup on this site.', 'goodblocks' ); ?></span>
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
		function toggle() {
			delayRow.style.display  = trigger.value === 'time'   ? '' : 'none';
			scrollRow.style.display = trigger.value === 'scroll' ? '' : 'none';
		}
		trigger.addEventListener( 'change', toggle );
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
}

function goodblocks_render_popups(): void {
	$popups = get_posts( [
		'post_type'      => 'goodblocks_popup',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	] );

	if ( empty( $popups ) ) {
		return;
	}

	foreach ( $popups as $popup ) {
		$trigger     = sanitize_key( get_post_meta( $popup->ID, '_popup_trigger', true ) ?: 'time' );
		$delay       = absint( get_post_meta( $popup->ID, '_popup_delay', true ) ?: 3 );
		$scroll      = absint( get_post_meta( $popup->ID, '_popup_scroll_percent', true ) ?: 50 );
		$cookie_name = sanitize_key( get_post_meta( $popup->ID, '_popup_cookie_name', true ) ?: ( 'gb_popup_' . $popup->ID ) );
		$cookie_days = absint( get_post_meta( $popup->ID, '_popup_cookie_days', true ) ?: 7 );
		$content     = apply_filters( 'the_content', $popup->post_content );
		?>
		<div class="wp-block-goodblocks-popup"
			style="display:none;"
			data-trigger="<?php echo esc_attr( $trigger ); ?>"
			data-delay="<?php echo esc_attr( $delay ); ?>"
			data-scroll-percent="<?php echo esc_attr( $scroll ); ?>"
			data-cookie-name="<?php echo esc_attr( $cookie_name ); ?>"
			data-cookie-days="<?php echo esc_attr( $cookie_days ); ?>"
			role="dialog"
			aria-modal="true"
		>
			<div class="popup-backdrop" aria-hidden="true"></div>
			<div class="popup-modal">
				<button class="popup-close" aria-label="<?php esc_attr_e( 'Close', 'goodblocks' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
				<div class="popup-content">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</div>
		<?php
	}
}

function goodblocks_popup_enqueue_assets(): void {
	$has_popups = get_posts( [
		'post_type'      => 'goodblocks_popup',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'fields'         => 'ids',
	] );

	if ( empty( $has_popups ) ) {
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
