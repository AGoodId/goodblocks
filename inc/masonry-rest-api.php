<?php
/**
 * Masonry Query Block — REST API endpoint for load-more pagination.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the masonry-query load-more REST route.
 */
function goodblocks_register_masonry_rest_route() {
	register_rest_route( 'goodblocks/v1', '/masonry-query', [
		'methods'             => 'POST',
		'callback'            => 'goodblocks_masonry_load_more',
		'permission_callback' => '__return_true',
		'args'                => [
			'page'       => [
				'required'          => true,
				'validate_callback' => function ( $param ) {
					return is_numeric( $param ) && intval( $param ) > 0;
				},
				'sanitize_callback' => 'absint',
			],
			'attributes' => [
				'required' => true,
				'type'     => 'object',
			],
		],
	] );
}
add_action( 'rest_api_init', 'goodblocks_register_masonry_rest_route' );

/**
 * Handle masonry load-more request.
 *
 * Receives block attributes and page number, builds a WP_Query
 * with the appropriate offset, and returns rendered item HTML.
 *
 * @param WP_REST_Request $request The REST request.
 * @return WP_REST_Response
 */
function goodblocks_masonry_load_more( $request ) {
	$page       = $request->get_param( 'page' );
	$attributes = $request->get_param( 'attributes' );

	// Sanitize and extract attributes.
	$post_type      = sanitize_text_field( $attributes['postType'] ?? 'post' );
	$query_type     = sanitize_text_field( $attributes['queryType'] ?? 'posts' );
	$posts_per_page = absint( $attributes['postsPerPage'] ?? 12 );
	$base_offset    = absint( $attributes['offset'] ?? 0 );
	$order_by       = sanitize_key( $attributes['orderBy'] ?? 'date' );
	$order_raw      = strtoupper( $attributes['order'] ?? 'DESC' );
	$order          = in_array( $order_raw, [ 'ASC', 'DESC' ], true ) ? $order_raw : 'DESC';

	$image_source    = sanitize_key( $attributes['imageSource'] ?? 'featured' );
	$image_ratio     = sanitize_text_field( $attributes['imageRatio'] ?? 'original' );
	$click_action    = sanitize_key( $attributes['clickAction'] ?? 'link' );
	$link_target     = sanitize_text_field( $attributes['linkTarget'] ?? '_self' );
	$show_title      = ! empty( $attributes['showTitle'] );
	$show_excerpt    = ! empty( $attributes['showExcerpt'] );
	$show_category   = ! empty( $attributes['showCategory'] );
	$show_date       = ! empty( $attributes['showDate'] );
	$overlay_style   = sanitize_key( $attributes['overlayStyle'] ?? 'solid' );
	$overlay_pos     = sanitize_key( $attributes['overlayPosition'] ?? 'full' );
	$overlay_vis     = sanitize_key( $attributes['overlayVisibility'] ?? 'hover' );
	$cat_taxonomy    = sanitize_key( $attributes['categoryTaxonomy'] ?? '' );
	$filter_taxonomy = sanitize_key( $attributes['filterTaxonomy'] ?? 'category' );
	$acf_image_field = sanitize_text_field( $attributes['acfImageField'] ?? '' );

	// Determine post type from query type.
	switch ( $query_type ) {
		case 'pages':
			$post_type = 'page';
			break;
		case 'media':
			$post_type = 'attachment';
			break;
		case 'mixed':
			$post_types_arr = array_map( 'sanitize_key', (array) ( $attributes['postTypes'] ?? [ 'post' ] ) );
			$post_type      = ! empty( $post_types_arr ) ? $post_types_arr : [ 'post' ];
			break;
	}

	// Portfolio grids use original ratios.
	$post_types_arr = is_array( $post_type ) ? $post_type : [ $post_type ];
	if ( in_array( 'portfolio', $post_types_arr, true ) ) {
		$image_ratio = 'original';
	}

	// Overlay taxonomy fallback logic.
	if ( empty( $cat_taxonomy ) && in_array( 'portfolio', $post_types_arr, true ) ) {
		$cat_taxonomy = 'portfolio_client';
	}
	$cat_taxonomy = $cat_taxonomy ?: $filter_taxonomy;

	// Build query.
	$args = [
		'post_type'      => $post_type,
		'posts_per_page' => $posts_per_page,
		'offset'         => $base_offset + ( ( $page - 1 ) * $posts_per_page ),
		'orderby'        => $order_by,
		'order'          => $order,
		'post_status'    => $post_type === 'attachment' ? 'inherit' : 'publish',
	];

	if ( $post_type === 'attachment' ) {
		$args['post_mime_type'] = 'image';
	}

	// Taxonomy filters.
	if ( ! empty( $attributes['taxonomies'] ) && is_array( $attributes['taxonomies'] ) ) {
		$tax_query = [];
		foreach ( $attributes['taxonomies'] as $taxonomy => $terms ) {
			$taxonomy = sanitize_key( $taxonomy );
			if ( ! empty( $terms ) && is_array( $terms ) ) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => array_map( 'absint', $terms ),
				];
			}
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}
	}

	// Include/Exclude IDs.
	if ( ! empty( $attributes['includeIds'] ) ) {
		$args['post__in'] = array_map( 'absint', (array) $attributes['includeIds'] );
		$args['orderby']  = 'post__in';
	}
	if ( ! empty( $attributes['excludeIds'] ) ) {
		$args['post__not_in'] = array_map( 'absint', (array) $attributes['excludeIds'] );
	}

	$query = new WP_Query( $args );
	$items = [];

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id = get_the_ID();

		// Determine image.
		$image_id = null;
		switch ( $image_source ) {
			case 'featured':
				$image_id = get_post_thumbnail_id( $post_id );
				break;
			case 'first':
				$content = get_the_content();
				preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches );
				if ( ! empty( $matches[1] ) ) {
					$image_id = attachment_url_to_postid( $matches[1] );
				}
				if ( ! $image_id ) {
					$image_id = get_post_thumbnail_id( $post_id );
				}
				break;
			case 'acf':
				if ( function_exists( 'get_field' ) && $acf_image_field ) {
					$acf_value = get_field( $acf_image_field, $post_id );
					$image_id  = is_array( $acf_value ) ? ( $acf_value['ID'] ?? null ) : $acf_value;
				}
				if ( ! $image_id ) {
					$image_id = get_post_thumbnail_id( $post_id );
				}
				break;
		}

		if ( $post_type === 'attachment' ) {
			$image_id = $post_id;
		}

		if ( ! $image_id && ! empty( $attributes['fallbackImageId'] ) ) {
			$image_id = absint( $attributes['fallbackImageId'] );
		}

		$image_full  = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : null;
		$image_large = $image_id ? wp_get_attachment_image_src( $image_id, $image_ratio === 'original' ? 'full' : 'large' ) : null;
		$image_alt   = $image_id ? ( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post_id ) ) : get_the_title( $post_id );

		$hero_video_url = get_post_meta( $post_id, 'portfolio_hero_video', true );

		// Category for overlay.
		$overlay_terms = get_the_terms( $post_id, $cat_taxonomy );
		if ( ( ! $overlay_terms || is_wp_error( $overlay_terms ) ) && get_post_type( $post_id ) === 'portfolio' && $cat_taxonomy !== 'portfolio_client' ) {
			$overlay_terms = get_the_terms( $post_id, 'portfolio_client' );
		}
		$primary_cat = $overlay_terms && ! is_wp_error( $overlay_terms ) ? $overlay_terms[0] : null;

		// Filter taxonomy terms for data attributes.
		$categories = $cat_taxonomy !== $filter_taxonomy
			? get_the_terms( $post_id, $filter_taxonomy )
			: $overlay_terms;

		// Tags.
		$post_tags = get_the_terms( $post_id, 'post_tag' );
		$tag_data  = [];
		if ( $post_tags && ! is_wp_error( $post_tags ) ) {
			foreach ( $post_tags as $pt ) {
				$tag_data[] = [
					'name' => $pt->name,
					'url'  => get_term_link( $pt ),
				];
			}
		}

		// Build item HTML.
		$href = $click_action === 'link' ? get_permalink( $post_id ) : ( $image_full[0] ?? '' );
		$tag  = $click_action === 'none' ? 'div' : 'a';
		$tag  = in_array( $tag, [ 'a', 'div' ], true ) ? $tag : 'div';

		$item_classes = [ 'masonry-query__item' ];
		if ( $primary_cat ) {
			$item_classes[] = 'masonry-query__item--cat-' . $primary_cat->slug;
		}

		$cat_slugs = [];
		if ( $categories && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				$cat_slugs[] = $cat->slug;
			}
		}

		// EXIF data.
		$exif_html = '';
		if ( function_exists( 'goodblocks_get_image_exif' ) && $image_id ) {
			$exif = goodblocks_get_image_exif( $image_id );
			if ( $exif ) {
				$exif_html = ' data-exif="' . esc_attr( wp_json_encode( $exif ) ) . '"';
			}
		}

		ob_start();
		?>
		<<?php echo $tag; ?>
			class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
			<?php if ( $tag === 'a' ) : ?>
				href="<?php echo esc_url( $href ); ?>"
				<?php if ( $click_action === 'link' && $link_target === '_blank' ) : ?>
					target="_blank" rel="noopener"
				<?php endif; ?>
			<?php endif; ?>
			data-post-id="<?php echo esc_attr( $post_id ); ?>"
			data-title="<?php echo esc_attr( get_the_title() ); ?>"
			data-excerpt="<?php echo esc_attr( wp_trim_words( get_the_excerpt(), 30 ) ); ?>"
			data-permalink="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
			data-categories="<?php echo esc_attr( implode( ',', $cat_slugs ) ); ?>"
			<?php if ( ! empty( $tag_data ) ) : ?>
				data-tags="<?php echo esc_attr( wp_json_encode( $tag_data ) ); ?>"
			<?php endif; ?>
			<?php if ( $image_full ) : ?>
				data-full-src="<?php echo esc_url( $image_full[0] ); ?>"
				data-full-width="<?php echo esc_attr( $image_full[1] ); ?>"
				data-full-height="<?php echo esc_attr( $image_full[2] ); ?>"
			<?php endif; ?>
			<?php if ( $hero_video_url ) : ?>
				data-video="<?php echo esc_url( $hero_video_url ); ?>"
			<?php endif; ?>
			data-caption="<?php echo esc_attr( wp_get_attachment_caption( $image_id ) ); ?>"
			data-alt="<?php echo esc_attr( $image_alt ); ?>"
			<?php echo $exif_html; ?>
		>
			<?php if ( $image_large ) : ?>
				<div class="masonry-query__image-wrapper">
					<?php if ( $hero_video_url ) : ?>
						<video class="masonry-query__video" src="<?php echo esc_url( $hero_video_url ); ?>" muted loop playsinline preload="metadata"></video>
					<?php endif; ?>
					<img
						class="masonry-query__image"
						src="<?php echo esc_url( $image_large[0] ); ?>"
						alt="<?php echo esc_attr( $image_alt ); ?>"
						width="<?php echo esc_attr( $image_large[1] ); ?>"
						height="<?php echo esc_attr( $image_large[2] ); ?>"
						loading="lazy"
					/>
				</div>
			<?php endif; ?>

			<div class="masonry-query__overlay">
				<?php if ( ! $show_title && get_the_title() ) : ?>
					<h3 class="masonry-query__title"><?php the_title(); ?></h3>
				<?php endif; ?>
				<?php if ( ! $show_date ) : ?>
					<time class="masonry-query__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<?php endif; ?>
				<?php if ( ! $show_excerpt && get_the_excerpt() ) : ?>
					<p class="masonry-query__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! $show_category && $primary_cat ) : ?>
					<span class="masonry-query__category"><?php echo esc_html( $primary_cat->name ); ?></span>
				<?php endif; ?>
			</div>
		</<?php echo $tag; ?>>
		<?php
		$items[] = ob_get_clean();
	}

	wp_reset_postdata();

	return rest_ensure_response( [
		'items'   => $items,
		'hasMore' => $page < $query->max_num_pages,
		'total'   => $query->found_posts,
	] );
}
