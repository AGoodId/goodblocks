<?php
/**
 * AGoodApp — Export media from WP library to AGoodApp.
 *
 * Adds a bulk action "Export to AGoodApp" on the media library list view.
 * Pushes each file as multipart/form-data to POST /api/upload.
 * WP reads files locally — bypasses any front-end password protection.
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
		return add_query_arg( 'agoodapp_error', 'not_configured', $redirect_url );
	}

	$uploaded   = 0;
	$duplicates = 0;
	$failed     = 0;

	foreach ( $post_ids as $attachment_id ) {
		$result = agoodapp_push_file( (int) $attachment_id, $api_key, $org_id, $base_url );

		if ( is_wp_error( $result ) ) {
			$failed++;
			continue;
		}

		$status = wp_remote_retrieve_response_code( $result );
		$body   = json_decode( wp_remote_retrieve_body( $result ), true );

		if ( ( $status === 200 || $status === 201 ) && ! empty( $body['success'] ) ) {
			if ( ! empty( $body['duplicate'] ) ) {
				$duplicates++;
			} else {
				$uploaded++;
			}
		} else {
			$failed++;
		}
	}

	return add_query_arg(
		[
			'agoodapp_uploaded'   => $uploaded,
			'agoodapp_duplicates' => $duplicates,
			'agoodapp_failed'     => $failed,
		],
		$redirect_url
	);
}

function agoodapp_push_file( int $attachment_id, string $api_key, string $org_id, string $base_url ): WP_Error|array {
	$file_path = get_attached_file( $attachment_id );

	if ( ! $file_path || ! file_exists( $file_path ) ) {
		return new WP_Error( 'file_not_found', "Attachment $attachment_id not found on disk." );
	}

	$filename  = basename( $file_path );
	$mime_type = wp_check_filetype( $filename )['type'] ?: 'application/octet-stream';
	$boundary  = wp_generate_password( 24, false );

	// Build multipart body manually — wp_remote_post doesn't support file uploads natively.
	$fields = [
		'organization_id' => $org_id,
		'wp_attachment_id' => (string) $attachment_id,
		'wp_site_url'      => get_site_url(),
	];

	$body = '';
	foreach ( $fields as $name => $value ) {
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
		$body .= $value . "\r\n";
	}
	$body .= "--{$boundary}\r\n";
	$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
	$body .= "Content-Type: {$mime_type}\r\n\r\n";
	$body .= file_get_contents( $file_path ) . "\r\n"; // phpcs:ignore WordPress.WP.AlternativeFunctions
	$body .= "--{$boundary}--\r\n";

	return wp_remote_post( $base_url . '/api/upload', [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
		],
		'body'    => $body,
		'timeout' => 60,
	] );
}

function agoodapp_import_admin_notice(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'upload' !== $screen->id ) {
		return;
	}

	if ( isset( $_GET['agoodapp_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$code     = sanitize_key( $_GET['agoodapp_error'] ); // phpcs:ignore WordPress.Security.NonceVerification
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

	if ( isset( $_GET['agoodapp_uploaded'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$uploaded   = absint( $_GET['agoodapp_uploaded'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$duplicates = absint( $_GET['agoodapp_duplicates'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$failed     = absint( $_GET['agoodapp_failed'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification

		$parts = [
			sprintf(
				_n( '%d file exported.', '%d files exported.', $uploaded, 'goodblocks' ),
				$uploaded
			),
		];
		if ( $duplicates > 0 ) {
			$parts[] = sprintf(
				_n( '%d duplicate skipped.', '%d duplicates skipped.', $duplicates, 'goodblocks' ),
				$duplicates
			);
		}
		if ( $failed > 0 ) {
			$parts[] = sprintf(
				_n( '%d file failed.', '%d files failed.', $failed, 'goodblocks' ),
				$failed
			);
		}

		$type = $failed > 0 && $uploaded === 0 ? 'notice-error' : ( $failed > 0 ? 'notice-warning' : 'notice-success' );
		printf(
			'<div class="notice %s is-dismissible"><p><strong>AGoodApp:</strong> %s</p></div>',
			esc_attr( $type ),
			esc_html( implode( ' ', $parts ) )
		);
	}
}
