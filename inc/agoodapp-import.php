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
		return add_query_arg( 'agoodapp_error', rawurlencode( 'not_configured' ), $redirect_url );
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
		return add_query_arg( 'agoodapp_error', rawurlencode( 'request_failed' ), $redirect_url );
	}

	$status = wp_remote_retrieve_response_code( $response );
	$body   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $status !== 200 || ! isset( $body['imported'] ) ) {
		return add_query_arg( 'agoodapp_error', rawurlencode( 'api_error_' . $status ), $redirect_url );
	}

	return add_query_arg(
		[
			'agoodapp_imported'   => (int) $body['imported'],
			'agoodapp_duplicates' => (int) ( $body['duplicates'] ?? 0 ),
		],
		$redirect_url
	);
}

function agoodapp_import_admin_notice(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'upload' !== $screen->id ) {
		return;
	}

	if ( isset( $_GET['agoodapp_error'] ) ) {
		$code = sanitize_key( $_GET['agoodapp_error'] );
		$messages = [
			'not_configured' => __( 'AGoodApp is not configured. Go to Settings → AGoodApp.', 'goodblocks' ),
			'request_failed' => __( 'Could not reach AGoodApp API.', 'goodblocks' ),
		];
		$msg = $messages[ $code ] ?? sprintf( __( 'AGoodApp error: %s', 'goodblocks' ), $code );
		printf(
			'<div class="notice notice-error is-dismissible"><p><strong>AGoodApp:</strong> %s</p></div>',
			esc_html( $msg )
		);
		return;
	}

	if ( isset( $_GET['agoodapp_imported'] ) ) {
		$imported   = absint( $_GET['agoodapp_imported'] );
		$duplicates = absint( $_GET['agoodapp_duplicates'] ?? 0 );

		$parts = [
			sprintf(
				_n( '%d file exported.', '%d files exported.', $imported, 'goodblocks' ),
				$imported
			),
		];
		if ( $duplicates > 0 ) {
			$parts[] = sprintf(
				_n( '%d duplicate skipped.', '%d duplicates skipped.', $duplicates, 'goodblocks' ),
				$duplicates
			);
		}
		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>AGoodApp:</strong> %s</p></div>',
			esc_html( implode( ' ', $parts ) )
		);
	}
}
