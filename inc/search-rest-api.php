<?php
/**
 * Search Autocomplete Block — REST API endpoints.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register search REST routes.
 */
function goodblocks_register_search_rest_routes() {
	register_rest_route( 'goodblocks/v1', '/search', [
		'methods'             => 'GET',
		'callback'            => 'goodblocks_search_callback',
		'permission_callback' => '__return_true',
		'args'                => [
			's'          => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'post_types' => [
				'default'           => 'post,page',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'per_page'   => [
				'default'           => 5,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],
		],
	] );

	register_rest_route( 'goodblocks/v1', '/search/suggestions', [
		'methods'             => 'GET',
		'callback'            => 'goodblocks_search_suggestions_callback',
		'permission_callback' => '__return_true',
		'args'                => [
			'type'  => [
				'default'           => 'popular',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'count' => [
				'default'           => 5,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],
			's'     => [
				'default'           => '',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		],
	] );
}
add_action( 'rest_api_init', 'goodblocks_register_search_rest_routes' );

/**
 * Search callback — returns matching posts.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function goodblocks_search_callback( WP_REST_Request $request ): WP_REST_Response {
	$search     = $request->get_param( 's' );
	$post_types = array_map( 'trim', explode( ',', $request->get_param( 'post_types' ) ) );
	$per_page   = min( $request->get_param( 'per_page' ), 20 );

	$query = new WP_Query( [
		's'              => $search,
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'orderby'        => 'relevance',
		'order'          => 'DESC',
	] );

	$results = [];

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		$results[] = [
			'title'     => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
			'url'       => get_permalink(),
			'excerpt'   => wp_trim_words( get_the_excerpt(), 20, '...' ),
			'thumbnail' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: null,
			'type'      => get_post_type_object( get_post_type() )->labels->singular_name ?? get_post_type(),
			'terms'     => goodblocks_get_post_term_pills( $post_id ),
		];
	}

	wp_reset_postdata();

	return new WP_REST_Response( $results, 200 );
}

/**
 * Suggestions callback — returns popular or matching posts.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function goodblocks_search_suggestions_callback( WP_REST_Request $request ): WP_REST_Response {
	$count  = min( $request->get_param( 'count' ), 20 );
	$search = $request->get_param( 's' );

	$args = [
		'post_type'      => [ 'post', 'page' ],
		'post_status'    => 'publish',
		'posts_per_page' => $count,
		'orderby'        => 'comment_count',
		'order'          => 'DESC',
	];

	if ( $search ) {
		$args['s']       = $search;
		$args['orderby'] = 'relevance';
	}

	$query   = new WP_Query( $args );
	$results = [];

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		$results[] = [
			'title'     => html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ),
			'url'       => get_permalink(),
			'excerpt'   => wp_trim_words( get_the_excerpt(), 15, '...' ),
			'thumbnail' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: null,
			'type'      => get_post_type_object( get_post_type() )->labels->singular_name ?? get_post_type(),
			'terms'     => goodblocks_get_post_term_pills( $post_id ),
		];
	}

	wp_reset_postdata();

	return new WP_REST_Response( $results, 200 );
}

/**
 * Get taxonomy terms for a post (for pill display).
 *
 * @param int $post_id Post ID.
 * @return array
 */
function goodblocks_get_post_term_pills( int $post_id ): array {
	$taxonomies = get_object_taxonomies( get_post_type( $post_id ), 'objects' );
	$pills      = [];

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! $taxonomy->public ) {
			continue;
		}

		$terms = get_the_terms( $post_id, $taxonomy->name );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$pills[] = [
					'name' => $term->name,
					'slug' => $term->slug,
				];
			}
		}
	}

	return array_slice( $pills, 0, 3 );
}
