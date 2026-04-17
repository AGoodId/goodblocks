<?php
/**
 * AGoodApp — Export media from WP library to AGoodApp.
 *
 * Adds a bulk action "Export to AGoodApp" on the media library list view.
 * Sends selected attachment IDs to POST /api/organizations/{orgId}/wordpress/import.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'bulk_actions-upload', 'agoodapp_register_import_bulk_action' );
add_filter( 'handle_bulk_actions-upload', 'agoodapp_handle_import_bulk_action', 10, 3 );
add_action( 'admin_notices', 'agoodapp_import_admin_notice' );

function agoodapp_register_import_bulk_action( array $actions ): array {
	$actions['agoodapp_import'] = __( 'Export to AGoodApp', 'goodblocks' );
	return $actions;
}

function agoodapp_handle_import_bulk_action( string $redirect_url, string $action, array $post_ids ): string {
	if ( 'agoodapp_import' !== $action ) {
		return $redirect_url;
	}

	if ( ! current_user_can( 'upload_files' ) ) {
		return $redirect_url;
	}

	$api_key  = get_option( 'agoodapp_api_key', '' );
	$org_id   = get_option( 'agoodapp_org_id', '' );
	$base_url = untrailingslashit( get_option( 'agoodapp_api_base_url', 'https://agoodsport.se' ) );

	if ( empty( $api_key ) || empty( $org_id ) ) {
		set_transient( 'agoodapp_import_result_' . get_current_user_id(), [
			'error' => __( 'AGoodApp is not configured. Go to Settings → AGoodApp.', 'goodblocks' ),
		], 60 );
		return $redirect_url;
	}

	$response = wp_remote_post(
		$base_url . '/api/organizations/' . rawurlencode( $org_id ) . '/wordpress/import',
		[
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			],
			'body'    => wp_json_encode( [ 'attachment_ids' => array_map( 'intval', $post_ids ) ] ),
			'timeout' => 30,
		]
	);

	if ( is_wp_error( $response ) ) {
		set_transient( 'agoodapp_import_result_' . get_current_user_id(), [
			'error' => $response->get_error_message(),
		], 60 );
		return $redirect_url;
	}

	$status = wp_remote_retrieve_response_code( $response );
	$body   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $status !== 200 || ! isset( $body['imported'] ) ) {
		set_transient( 'agoodapp_import_result_' . get_current_user_id(), [
			'error' => sprintf(
				/* translators: %d HTTP status code */
				__( 'AGoodApp returned status %d.', 'goodblocks' ),
				$status
			),
		], 60 );
		return $redirect_url;
	}

	set_transient( 'agoodapp_import_result_' . get_current_user_id(), [
		'imported'   => (int) $body['imported'],
		'duplicates' => (int) ( $body['duplicates'] ?? 0 ),
		'skipped'    => (array) ( $body['skipped'] ?? [] ),
	], 60 );

	return $redirect_url;
}

function agoodapp_import_admin_notice(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'upload' !== $screen->id ) {
		return;
	}

	$result = get_transient( 'agoodapp_import_result_' . get_current_user_id() );
	if ( ! $result ) {
		return;
	}

	delete_transient( 'agoodapp_import_result_' . get_current_user_id() );

	if ( isset( $result['error'] ) ) {
		printf(
			'<div class="notice notice-error is-dismissible"><p><strong>AGoodApp:</strong> %s</p></div>',
			esc_html( $result['error'] )
		);
		return;
	}

	$parts = [
		sprintf(
			/* translators: %d number of files */
			_n( '%d file exported.', '%d files exported.', $result['imported'], 'goodblocks' ),
			$result['imported']
		),
	];

	if ( $result['duplicates'] > 0 ) {
		$parts[] = sprintf(
			/* translators: %d number of duplicates */
			_n( '%d duplicate skipped.', '%d duplicates skipped.', $result['duplicates'], 'goodblocks' ),
			$result['duplicates']
		);
	}

	printf(
		'<div class="notice notice-success is-dismissible"><p><strong>AGoodApp:</strong> %s</p></div>',
		esc_html( implode( ' ', $parts ) )
	);
}
