<?php
/**
 * AGoodApp — REST endpoint for sideloading media into WP media library.
 *
 * POST /wp-json/goodblocks/v1/agoodapp/sideload
 * Requires: upload_files capability (editor+).
 * Duplicate detection via _agoodapp_source_id post meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'agoodapp_register_sideload_route' );

function agoodapp_register_sideload_route(): void {
	register_rest_route( 'goodblocks/v1', '/agoodapp/sideload', [
		'methods'             => 'POST',
		'callback'            => 'agoodapp_sideload_media',
		'permission_callback' => fn() => current_user_can( 'upload_files' ),
		'args'                => [
			'source_id'  => [
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'url'        => [
				'required'          => true,
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => fn( $v ) => (bool) filter_var( $v, FILTER_VALIDATE_URL ),
			],
			'title'      => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'filename'   => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_file_name',
			],
			'media_type' => [
				'default'           => 'image',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => fn( $v ) => in_array( $v, [ 'image', 'video' ], true ),
			],
		],
	] );
}

function agoodapp_sideload_media( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$source_id  = $request->get_param( 'source_id' );
	$url        = $request->get_param( 'url' );
	$title      = $request->get_param( 'title' );
	$filename   = $request->get_param( 'filename' );
	$media_type = $request->get_param( 'media_type' );

	// Return existing attachment if already sideloaded.
	$existing = new WP_Query( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
		'fields'         => 'ids',
		'meta_query'     => [
			[
				'key'   => '_agoodapp_source_id',
				'value' => $source_id,
			],
		],
	] );

	if ( ! empty( $existing->posts ) ) {
		$attachment_id = $existing->posts[0];
		return rest_ensure_response( [
			'attachment_id' => $attachment_id,
			'media_type'    => $media_type,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'duplicate'     => true,
		] );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 60 );

	if ( is_wp_error( $tmp ) ) {
		return new WP_Error( 'agoodapp_download_failed', $tmp->get_error_message(), [ 'status' => 502 ] );
	}

	if ( empty( $filename ) ) {
		$filename = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	}

	$attachment_id = media_handle_sideload(
		[ 'name' => $filename, 'tmp_name' => $tmp ],
		0,
		$title ?: null
	);

	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $tmp );
		return new WP_Error( 'agoodapp_sideload_failed', $attachment_id->get_error_message(), [ 'status' => 500 ] );
	}

	update_post_meta( $attachment_id, '_agoodapp_source_id', $source_id );

	return rest_ensure_response( [
		'attachment_id' => $attachment_id,
		'media_type'    => $media_type,
		'url'           => wp_get_attachment_url( $attachment_id ),
		'duplicate'     => false,
	] );
}
