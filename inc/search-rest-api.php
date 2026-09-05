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
			'lang'       => [
				'default'           => '',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			],
			'include_terms' => [
				'default'           => false,
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'taxonomies' => [
				'default'           => '',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
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
	$post_types = array_filter(
		array_map( 'sanitize_key', explode( ',', (string) $request->get_param( 'post_types' ) ) ),
		static function ( $post_type ) {
			$object = get_post_type_object( $post_type );
			return $object && ! empty( $object->public );
		}
	);
	$per_page = min( 20, max( 1, absint( $request->get_param( 'per_page' ) ) ) );

	$query_args = [
		's'              => $search,
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'orderby'        => 'relevance',
		'order'          => 'DESC',
	];

	$lang = sanitize_key( (string) $request->get_param( 'lang' ) );
	if ( $lang ) {
		// Polylang consumes this query var; on non-Polylang sites it is inert.
		$query_args['lang'] = $lang;
	}

	/**
	 * Filter the public autocomplete query before it runs.
	 *
	 * Consumers must keep the query bounded and limited to public content.
	 *
	 * @param array           $query_args WP_Query arguments.
	 * @param WP_REST_Request $request    Current request.
	 */
	$query_args = (array) apply_filters( 'goodblocks_search_query_args', $query_args, $request );
	$query_args['post_status']    = 'publish';
	$query_args['posts_per_page'] = min( 20, max( 1, absint( $query_args['posts_per_page'] ?? $per_page ) ) );
	$query_args['post_type']      = array_values( array_filter(
		(array) ( $query_args['post_type'] ?? [] ),
		static function ( $post_type ) {
			$object = get_post_type_object( $post_type );
			return $object && ! empty( $object->public );
		}
	) );
	if ( ! $query_args['post_type'] ) {
		$query_args['post_type'] = [ 'post', 'page' ];
	}
	$query      = new WP_Query( $query_args );

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

	if ( rest_sanitize_boolean( $request->get_param( 'include_terms' ) ) ) {
		$results = array_merge( $results, goodblocks_search_matching_terms( $search, $request, $per_page ) );
		$results = array_slice( $results, 0, $per_page );
	}

	/**
	 * Filter normalized autocomplete results.
	 *
	 * @param array           $results Result rows.
	 * @param WP_REST_Request $request Current request.
	 */
	$results = (array) apply_filters( 'goodblocks_search_results', $results, $request );

	return new WP_REST_Response( $results, 200 );
}

/**
 * Return matching public taxonomy terms for opt-in mixed autocomplete.
 *
 * @param string          $search   Search phrase.
 * @param WP_REST_Request $request  Current request.
 * @param int             $per_page Maximum returned terms.
 * @return array
 */
function goodblocks_search_matching_terms( string $search, WP_REST_Request $request, int $per_page ): array {
	$requested = array_filter( array_map( 'sanitize_key', explode( ',', (string) $request->get_param( 'taxonomies' ) ) ) );
	$allowed   = [];

	foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $taxonomy ) {
		if ( $requested && ! in_array( $taxonomy->name, $requested, true ) ) {
			continue;
		}
		$allowed[] = $taxonomy->name;
	}

	$allowed = (array) apply_filters( 'goodblocks_search_taxonomies', $allowed, $request );
	$allowed = array_values( array_filter(
		array_map( 'sanitize_key', $allowed ),
		static function ( $taxonomy_name ) {
			$taxonomy = get_taxonomy( $taxonomy_name );
			return $taxonomy && ! empty( $taxonomy->public );
		}
	) );
	if ( ! $allowed ) {
		return [];
	}

	$terms = get_terms( [
		'taxonomy'   => $allowed,
		'search'     => $search,
		'hide_empty' => true,
		'number'     => min( 20, max( 1, $per_page ) ),
	] );

	if ( is_wp_error( $terms ) ) {
		return [];
	}

	return array_map(
		static function ( $term ) {
			$taxonomy = get_taxonomy( $term->taxonomy );
			$url      = get_term_link( $term );
			return [
				'title'     => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
				'url'       => is_wp_error( $url ) ? '' : $url,
				'excerpt'   => wp_trim_words( $term->description, 20, '...' ),
				'thumbnail' => null,
				'type'      => $taxonomy->labels->singular_name ?? $term->taxonomy,
				'terms'     => [],
				'kind'      => 'term',
				'taxonomy'  => $term->taxonomy,
			];
		},
		$terms
	);
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

	// The "popular" list (no search term) is identical for every visitor, so it
	// can be cached. Search-specific suggestions are left uncached.
	$cache_key = '';
	if ( ! $search ) {
		$cache_key = 'goodblocks_suggestions_popular_' . $count;
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return new WP_REST_Response( $cached, 200 );
		}
	}

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

	if ( $cache_key ) {
		$ttl = (int) apply_filters( 'goodblocks_suggestions_cache_ttl', 15 * MINUTE_IN_SECONDS );
		if ( $ttl > 0 ) {
			set_transient( $cache_key, $results, $ttl );
		}
	}

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
