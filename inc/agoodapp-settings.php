<?php
/**
 * AGoodApp — Settings page (Settings → AGoodApp).
 *
 * Stores: agoodapp_api_base_url, agoodapp_org_id, agoodapp_api_key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'agoodapp_add_settings_page' );
add_action( 'admin_init', 'agoodapp_register_settings' );

function agoodapp_add_settings_page(): void {
	add_options_page(
		__( 'AGoodApp', 'goodblocks' ),
		__( 'AGoodApp', 'goodblocks' ),
		'manage_options',
		'agoodapp-settings',
		'agoodapp_render_settings_page'
	);
}

function agoodapp_register_settings(): void {
	register_setting( 'agoodapp_settings', 'agoodapp_api_base_url', [
		'sanitize_callback' => 'esc_url_raw',
		'default'           => 'https://agoodsport.se',
	] );
	register_setting( 'agoodapp_settings', 'agoodapp_org_id', [
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );
	register_setting( 'agoodapp_settings', 'agoodapp_api_key', [
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );

	add_settings_section( 'agoodapp_main', '', '__return_null', 'agoodapp-settings' );

	add_settings_field(
		'agoodapp_api_base_url',
		__( 'API Base URL', 'goodblocks' ),
		'agoodapp_field_api_base_url',
		'agoodapp-settings',
		'agoodapp_main'
	);
	add_settings_field(
		'agoodapp_org_id',
		__( 'Organisation ID', 'goodblocks' ),
		'agoodapp_field_org_id',
		'agoodapp-settings',
		'agoodapp_main'
	);
	add_settings_field(
		'agoodapp_api_key',
		__( 'API Key', 'goodblocks' ),
		'agoodapp_field_api_key',
		'agoodapp-settings',
		'agoodapp_main'
	);
}

function agoodapp_field_api_base_url(): void {
	$value = get_option( 'agoodapp_api_base_url', 'https://agoodsport.se' );
	printf(
		'<input type="url" name="agoodapp_api_base_url" value="%s" class="regular-text" placeholder="https://agoodsport.se" />
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'Base URL for the AGoodApp API — no trailing slash.', 'goodblocks' )
	);
}

function agoodapp_field_org_id(): void {
	$value = get_option( 'agoodapp_org_id', '' );
	printf(
		'<input type="text" name="agoodapp_org_id" value="%s" class="regular-text" />
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'Your organisation ID in AGoodApp.', 'goodblocks' )
	);
}

function agoodapp_field_api_key(): void {
	$value = get_option( 'agoodapp_api_key', '' );
	printf(
		'<input type="password" name="agoodapp_api_key" value="%s" class="regular-text" autocomplete="new-password" />
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'Bearer token from AGoodApp Settings → API.', 'goodblocks' )
	);
}

function agoodapp_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'AGoodApp', 'goodblocks' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'agoodapp_settings' );
			do_settings_sections( 'agoodapp-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
