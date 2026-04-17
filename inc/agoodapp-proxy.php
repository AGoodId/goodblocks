<?php
/**
 * AGoodApp — REST proxy for media listing.
 *
 * GET /wp-json/goodblocks/v1/agoodapp/media?page=1&limit=24
 * Requires: edit_posts capability (editor+).
 * Keeps the Bearer token server-side.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'agoodapp_register_proxy_route' );

function agoodapp_register_proxy_route(): void {
	register_rest_route( 'goodblocks/v1', '/agoodapp/media', [
		'methods'             => 'GET',
		'callback'            => 'agoodapp_proxy_media',
		'permission_callback' => fn() => current_user_can( 'edit_posts' ),
		'args'                => [
			'page'   => [
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
			],
			'limit'  => [
				'default'           => 24,
				'sanitize_callback' => 'absint',
				'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v >= 1 && (int) $v <= 100,
			],
			'search' => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
		],
	] );
}

function agoodapp_proxy_media( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$api_key  = get_option( 'agoodapp_api_key', '' );
	$org_id   = get_option( 'agoodapp_org_id', '' );
	$base_url = untrailingslashit( get_option( 'agoodapp_api_base_url', 'https://agoodsport.se' ) );

	if ( empty( $api_key ) || empty( $org_id ) ) {
		return new WP_Error(
			'agoodapp_not_configured',
			__( 'AGoodApp API key or organisation ID is not configured. Go to Settings → AGoodApp.', 'goodblocks' ),
			[ 'status' => 400 ]
		);
	}

	$query = [
		'page'  => $request->get_param( 'page' ),
		'limit' => $request->get_param( 'limit' ),
	];

	$search = $request->get_param( 'search' );
	if ( '' !== $search ) {
		$query['search'] = $search;
	}

	$url = add_query_arg(
		$query,
		$base_url . '/api/public/organizations/' . rawurlencode( $org_id ) . '/media'
	);

	$response = wp_remote_get( $url, [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Accept'        => 'application/json',
		],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'agoodapp_request_failed', $response->get_error_message(), [ 'status' => 502 ] );
	}

	$status = wp_remote_retrieve_response_code( $response );
	$body   = wp_remote_retrieve_body( $response );

	if ( 401 === $status || 403 === $status ) {
		return new WP_Error( 'agoodapp_unauthorized', __( 'Invalid AGoodApp API key.', 'goodblocks' ), [ 'status' => 401 ] );
	}

	if ( 200 !== $status ) {
		return new WP_Error(
			'agoodapp_api_error',
			/* translators: %d HTTP status code */
			sprintf( __( 'AGoodApp API returned status %d.', 'goodblocks' ), $status ),
			[ 'status' => 502 ]
		);
	}

	$data = json_decode( $body, true );

	if ( ! isset( $data['items'] ) || ! is_array( $data['items'] ) ) {
		return new WP_Error( 'agoodapp_invalid_response', __( 'Unexpected response from AGoodApp API.', 'goodblocks' ), [ 'status' => 502 ] );
	}

	// Ensure thumbnail_path and web_path are absolute URLs.
	foreach ( $data['items'] as &$item ) {
		if ( isset( $item['thumbnail_path'] ) && ! str_starts_with( $item['thumbnail_path'], 'http' ) ) {
			$item['thumbnail_path'] = $base_url . $item['thumbnail_path'];
		}
		if ( isset( $item['web_path'] ) && ! str_starts_with( $item['web_path'], 'http' ) ) {
			$item['web_path'] = $base_url . $item['web_path'];
		}
	}
	unset( $item );

	return rest_ensure_response( [
		'items'   => $data['items'],
		'total'   => $data['total'] ?? count( $data['items'] ),
		'hasMore' => $data['hasMore'] ?? false,
	] );
}
