<?php
/**
 * Masonry Query Block - Server-side Render
 *
 * @var array    $attributes Block attributes
 * @var string   $content    Block content
 * @var WP_Block $block      Block instance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extract formatted EXIF/image metadata for an attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return array|null Associative array of metadata strings, or null if none.
 */
if ( ! function_exists( 'agoodsite_fse_get_image_exif' ) ) {
	function agoodsite_fse_get_image_exif( $attachment_id ) {
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! $meta || empty( $meta['image_meta'] ) ) {
			return null;
		}

		$im   = $meta['image_meta'];
		$exif = [];

		if ( ! empty( $im['camera'] ) ) {
			$exif['camera'] = $im['camera'];
		}
		if ( ! empty( $im['focal_length'] ) ) {
			$exif['focal'] = $im['focal_length'] . 'mm';
		}
		if ( ! empty( $im['aperture'] ) ) {
			$exif['aperture'] = 'f/' . $im['aperture'];
		}
		if ( ! empty( $im['shutter_speed'] ) ) {
			$ss = floatval( $im['shutter_speed'] );
			if ( $ss > 0 && $ss < 1 ) {
				$exif['shutter'] = '1/' . round( 1 / $ss ) . 's';
			} elseif ( $ss >= 1 ) {
				$exif['shutter'] = $ss . 's';
			}
		}
		if ( ! empty( $im['iso'] ) ) {
			$exif['iso'] = 'ISO ' . $im['iso'];
		}
		if ( ! empty( $im['created_timestamp'] ) ) {
			$exif['date'] = wp_date( 'Y-m-d', $im['created_timestamp'] );
		}

		// Dimensions from the full image metadata.
		if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			$exif['dimensions'] = $meta['width'] . ' × ' . $meta['height'];
		}

		return ! empty( $exif ) ? $exif : null;
	}
}

// Read global masonry defaults from theme.json custom.masonry
$theme_defaults = wp_get_global_settings( [ 'custom', 'masonry' ] );
if ( ! is_array( $theme_defaults ) ) {
	$theme_defaults = [];
}

// Helper: get attribute with theme.json fallback, then hardcoded fallback
$attr = function ( $key, $fallback ) use ( $attributes, $theme_defaults ) {
	if ( isset( $attributes[ $key ] ) ) {
		return $attributes[ $key ];
	}
	// Map camelCase attribute names to theme.json keys
	return $theme_defaults[ $key ] ?? $fallback;
};

// Extract attributes: block → theme.json → hardcoded fallback
$query_type      = $attr( 'queryType', 'posts' );
$post_type       = $attr( 'postType', 'post' );
$posts_per_page  = $attr( 'postsPerPage', 12 );
$offset          = $attr( 'offset', 0 );
$order_by        = $attr( 'orderBy', 'date' );
$order           = $attr( 'order', 'DESC' );
$columns         = $attr( 'columns', [ 'desktop' => 3, 'tablet' => 2, 'mobile' => 1 ] );
$gap_raw         = $attr( 'gap', 'xs' );
// Backward compat: convert old preset strings to pixel values
$preset_to_px    = [ 'xs' => 4, 'sm' => 8, 'md' => 16, 'lg' => 24, 'xl' => 40 ];
$gap             = is_numeric( $gap_raw ) ? intval( $gap_raw ) : ( $preset_to_px[ $gap_raw ] ?? 16 );
$image_ratio     = $attr( 'imageRatio', 'original' );
$image_fit       = $attr( 'imageFit', 'cover' );
$image_source    = $attr( 'imageSource', 'featured' );
$acf_image_field = $attr( 'acfImageField', '' );
$show_title      = $attr( 'showTitle', true );
$show_excerpt    = $attr( 'showExcerpt', false );
$show_category   = $attr( 'showCategory', false );
$show_date       = $attr( 'showDate', false );
$overlay_style   = $attr( 'overlayStyle', 'solid' );
$overlay_pos     = $attr( 'overlayPosition', 'full' );
$overlay_vis     = $attr( 'overlayVisibility', 'hover' );
$overlay_font    = $attr( 'overlayFontFamily', 'body' );
$hover_effect    = $attr( 'hoverEffect', 'zoom' );
$border_radius   = $attr( 'borderRadius', 'none' );
$click_action    = $attr( 'clickAction', 'link' );
$link_target     = $attr( 'linkTarget', '_self' );
$lightbox_anim   = $attr( 'lightboxAnimation', 'zoom' );
$lightbox_info   = $attr( 'lightboxShowInfo', true );
$lightbox_link   = $attr( 'lightboxShowLink', true );
$enable_filter   = $attr( 'enableFiltering', false );
$filter_taxonomy = $attr( 'filterTaxonomy', 'category' );
$cat_taxonomy    = $attr( 'categoryTaxonomy', '' );
$post_types_for_overlay = is_array( $post_type ) ? $post_type : [ $post_type ];

// Prefer portfolio client as overlay category on portfolio grids.
if ( empty( $cat_taxonomy ) && in_array( 'portfolio', $post_types_for_overlay, true ) ) {
	$cat_taxonomy = 'portfolio_client';
}

// Generic fallback if no explicit overlay taxonomy is available.
$cat_taxonomy = $cat_taxonomy ?: $filter_taxonomy;
$filter_style    = $attr( 'filterStyle', 'buttons' );
$filter_all_text = $attr( 'filterAllText', 'Alla' );
$enable_paging   = $attr( 'enablePagination', false );
$paging_type     = $attr( 'paginationType', 'load-more' );
$load_more_text  = $attr( 'loadMoreText', 'Ladda fler' );
$enable_anim     = $attr( 'enableAnimation', true );
$anim_type       = $attr( 'animationType', 'fade-up' );
$anim_stagger    = $attr( 'animationStagger', 50 );
$block_id        = $attributes['blockId'] ?? 'masonry-' . uniqid();

// Determine post type based on query type
switch ( $query_type ) {
	case 'pages':
		$post_type = 'page';
		break;
	case 'media':
		$post_type = 'attachment';
		break;
	case 'mixed':
		$post_types_arr = $attributes['postTypes'] ?? [ 'post' ];
		$post_type      = ! empty( $post_types_arr ) ? $post_types_arr : [ 'post' ];
		break;
	case 'custom':
		// Use the selected custom post type
		break;
	default:
		$post_type = 'post';
}

// Portfolio grids should always keep original media proportions (Pinterest style).
$post_types_for_layout = is_array( $post_type ) ? $post_type : [ $post_type ];
if ( in_array( 'portfolio', $post_types_for_layout, true ) ) {
	$image_ratio = 'original';
}

// Build query args
$is_mixed = is_array( $post_type );
$args     = [
	'post_type'      => $post_type,
	'posts_per_page' => $posts_per_page,
	'offset'         => $offset,
	'orderby'        => $order_by,
	'order'          => $order,
	'post_status'    => $post_type === 'attachment' ? 'inherit' : 'publish',
];

// Handle media/attachment query
if ( $post_type === 'attachment' ) {
	$args['post_mime_type']  = 'image';
	$args['lang']            = ''; // Bypass Polylang — media should not be language-filtered.
	$args['suppress_filters'] = true; // Belt-and-suspenders: skip all query filters including Polylang joins.
}

// Taxonomy query
if ( ! empty( $attributes['taxonomies'] ) ) {
	$tax_query = [];
	foreach ( $attributes['taxonomies'] as $taxonomy => $terms ) {
		if ( ! empty( $terms ) ) {
			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $terms,
			];
		}
	}
	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = $tax_query;
	}
}

// On taxonomy archive pages, auto-filter by the current term
if ( ( is_tax() || is_category() || is_tag() ) && empty( $attributes['taxonomies'] ) ) {
	$queried = get_queried_object();
	if ( $queried instanceof WP_Term ) {
		$tax_obj  = get_taxonomy( $queried->taxonomy );
		$pt_array = is_array( $post_type ) ? $post_type : [ $post_type ];
		if ( $tax_obj && array_intersect( $pt_array, (array) $tax_obj->object_type ) ) {
			$args['tax_query'][] = [
				'taxonomy' => $queried->taxonomy,
				'field'    => 'term_id',
				'terms'    => [ $queried->term_id ],
			];
		}
	}
}

// Include/Exclude IDs
if ( ! empty( $attributes['includeIds'] ) ) {
	$args['post__in'] = $attributes['includeIds'];
	$args['orderby']  = 'post__in';
}
if ( ! empty( $attributes['excludeIds'] ) ) {
	$args['post__not_in'] = $attributes['excludeIds'];
}

// Exclude current post
if ( ( $attributes['excludeCurrent'] ?? true ) && ! empty( $block->context['postId'] ) ) {
	$args['post__not_in'] = array_merge(
		$args['post__not_in'] ?? [],
		[ $block->context['postId'] ]
	);
}

$query = new WP_Query( $args );

if ( ! $query->have_posts() ) {
	echo '<div class="masonry-query__empty" style="text-align:center;padding:var(--wp--preset--spacing--lg, 3rem) var(--wp--preset--spacing--md, 1.5rem);"><p>' . esc_html__( 'Inga resultat hittades.', 'goodblocks' ) . '</p></div>';
	return;
}

// Calculate aspect ratio CSS
$ratio_map = [
	'1:1'  => '100%',
	'4:5'  => '125%',
	'3:4'  => '133.33%',
	'2:3'  => '150%',
	'4:3'  => '75%',
	'3:2'  => '66.67%',
	'16:9' => '56.25%',
	'9:16' => '177.78%',
];
$padding_ratio = $ratio_map[ $image_ratio ] ?? null;

// Build wrapper classes
$wrapper_classes = [
	'wp-block-goodblocks-masonry-query',
	'masonry-query',
	"masonry-query--radius-{$border_radius}",
	"masonry-query--hover-{$hover_effect}",
	"masonry-query--overlay-{$overlay_style}",
	"masonry-query--overlay-pos-{$overlay_pos}",
	"masonry-query--overlay-vis-{$overlay_vis}",
];

if ( $image_ratio === 'original' ) {
	$wrapper_classes[] = 'masonry-query--masonry';
} else {
	$wrapper_classes[] = 'masonry-query--grid';
}

// CSS custom properties
$style_vars = [
	"--masonry-cols-desktop: {$columns['desktop']}",
	"--masonry-cols-tablet: {$columns['tablet']}",
	"--masonry-cols-mobile: {$columns['mobile']}",
	"--masonry-gap: " . intval( $gap ) . "px",
];

if ( $padding_ratio ) {
	$style_vars[] = "--masonry-ratio: {$padding_ratio}";
}

// Data attributes for JS
$data_attrs = [
	'data-block-id'     => esc_attr( $block_id ),
	'data-click-action' => esc_attr( $click_action ),
	'data-lightbox-animation' => esc_attr( $lightbox_anim ),
	'data-lightbox-info' => $lightbox_info ? 'true' : 'false',
	'data-lightbox-link' => $lightbox_link ? 'true' : 'false',
	'data-enable-animation' => $enable_anim ? 'true' : 'false',
	'data-animation-type' => esc_attr( $anim_type ),
	'data-animation-stagger' => esc_attr( $anim_stagger ),
];

// Pass pagination-relevant attributes to JS for load-more REST calls.
if ( $enable_paging ) {
	$pagination_attrs = [
		'queryType'       => $query_type,
		'postType'        => $post_type,
		'postsPerPage'    => $posts_per_page,
		'offset'          => $offset,
		'orderBy'         => $order_by,
		'order'           => $order,
		'imageSource'     => $image_source,
		'imageRatio'      => $image_ratio,
		'clickAction'     => $click_action,
		'linkTarget'      => $link_target,
		'showTitle'       => $show_title,
		'showExcerpt'     => $show_excerpt,
		'showCategory'    => $show_category,
		'showDate'        => $show_date,
		'overlayStyle'    => $overlay_style,
		'overlayPosition' => $overlay_pos,
		'overlayVisibility' => $overlay_vis,
		'categoryTaxonomy'  => $cat_taxonomy,
		'filterTaxonomy'    => $filter_taxonomy,
		'acfImageField'     => $acf_image_field,
	];
	if ( ! empty( $attributes['taxonomies'] ) ) {
		$pagination_attrs['taxonomies'] = $attributes['taxonomies'];
	}
	if ( ! empty( $attributes['includeIds'] ) ) {
		$pagination_attrs['includeIds'] = $attributes['includeIds'];
	}
	if ( ! empty( $attributes['excludeIds'] ) ) {
		$pagination_attrs['excludeIds'] = $attributes['excludeIds'];
	}
	if ( ! empty( $attributes['postTypes'] ) ) {
		$pagination_attrs['postTypes'] = $attributes['postTypes'];
	}
	if ( ! empty( $attributes['fallbackImageId'] ) ) {
		$pagination_attrs['fallbackImageId'] = $attributes['fallbackImageId'];
	}
	$data_attrs['data-query-attrs'] = esc_attr( wp_json_encode( $pagination_attrs ) );
}

// Get all terms for filtering
$all_terms = [];
if ( $enable_filter ) {
	$temp_posts = $query->posts;
	foreach ( $temp_posts as $p ) {
		$terms = get_the_terms( $p->ID, $filter_taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$all_terms[ $term->slug ] = $term->name;
			}
		}
	}
	asort( $all_terms );
}

$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => implode( ' ', $wrapper_classes ),
	'style' => implode( '; ', $style_vars ),
] );

// Add data attributes to wrapper
$data_string = '';
foreach ( $data_attrs as $key => $value ) {
	$data_string .= " {$key}=\"{$value}\"";
}
?>

<div <?php echo $wrapper_attributes . $data_string; ?>>

	<?php if ( $enable_filter && ! empty( $all_terms ) ) : ?>
		<div class="masonry-query__filters masonry-query__filters--<?php echo esc_attr( $filter_style ); ?> portfolio-meta__tags">
			<button type="button" class="masonry-query__filter portfolio-meta__pill is-active" data-filter="*" aria-pressed="true">
				<?php echo esc_html( $filter_all_text ); ?>
			</button>
			<?php foreach ( $all_terms as $slug => $name ) : ?>
				<button type="button" class="masonry-query__filter portfolio-meta__pill" data-filter="<?php echo esc_attr( $slug ); ?>" aria-pressed="false">
					<?php echo esc_html( $name ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="masonry-query__grid" id="<?php echo esc_attr( $block_id ); ?>">
		<?php
		$index = 0;
		while ( $query->have_posts() ) :
			$query->the_post();
			$post_id = get_the_ID();

			// Get image
			$image_id = null;
			switch ( $image_source ) {
				case 'featured':
					$image_id = get_post_thumbnail_id( $post_id );
					break;
				case 'first':
					// Get first image from content
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
						$image_id = is_array( $acf_value ) ? ( $acf_value['ID'] ?? null ) : $acf_value;
					}
					if ( ! $image_id ) {
						$image_id = get_post_thumbnail_id( $post_id );
					}
					break;
			}

			// For media/attachment, the post itself is the image
			if ( $post_type === 'attachment' ) {
				$image_id = $post_id;
			}

			// Fallback image
			if ( ! $image_id && ! empty( $attributes['fallbackImageId'] ) ) {
				$image_id = $attributes['fallbackImageId'];
			}

			// Check for hero video (portfolio posts)
			$hero_video_url = get_post_meta( $post_id, 'portfolio_hero_video', true );

			$image_full  = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : null;
			// In masonry mode, prefer full-size to preserve natural aspect ratios.
			$image_large = $image_id ? wp_get_attachment_image_src( $image_id, $image_ratio === 'original' ? 'full' : 'large' ) : null;
			$image_alt   = $image_id ? ( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post_id ) ) : get_the_title( $post_id );
			$has_image   = $image_id && ( $image_full || ! empty( $hero_video_url ) );

			// Get category for overlay display (may differ from filter taxonomy)
			$overlay_terms = get_the_terms( $post_id, $cat_taxonomy );
			if ( ( ! $overlay_terms || is_wp_error( $overlay_terms ) ) && get_post_type( $post_id ) === 'portfolio' && $cat_taxonomy !== 'portfolio_client' ) {
				$overlay_terms = get_the_terms( $post_id, 'portfolio_client' );
			}
			if ( ( ! $overlay_terms || is_wp_error( $overlay_terms ) ) && get_post_type( $post_id ) !== 'portfolio' && $filter_taxonomy && $filter_taxonomy !== $cat_taxonomy ) {
				$overlay_terms = get_the_terms( $post_id, $filter_taxonomy );
			}
			$primary_cat   = $overlay_terms && ! is_wp_error( $overlay_terms ) ? $overlay_terms[0] : null;

			// Get filter taxonomy terms for data attributes
			$categories = $cat_taxonomy !== $filter_taxonomy
				? get_the_terms( $post_id, $filter_taxonomy )
				: $overlay_terms;

			// Get all tags for the item (for lightbox) with URLs
			$post_tags = get_the_terms( $post_id, 'post_tag' );
			$tag_names = [];
			$tag_links = [];
			if ( $post_tags && ! is_wp_error( $post_tags ) ) {
				foreach ( $post_tags as $pt ) {
					$tag_names[] = $pt->name;
					$tag_links[ $pt->name ] = get_term_link( $pt );
				}
			}
			// Add category/filter taxonomy terms with URLs
			if ( $categories && ! is_wp_error( $categories ) ) {
				foreach ( $categories as $cat ) {
					$tag_links[ $cat->name ] = get_term_link( $cat );
				}
			}
			// Add service taxonomy terms if portfolio
			$service_terms = get_the_terms( $post_id, 'portfolio_service' );
			if ( $service_terms && ! is_wp_error( $service_terms ) ) {
				foreach ( $service_terms as $st ) {
					$tag_links[ $st->name ] = get_term_link( $st );
				}
			}

			// Get excerpt for lightbox (always available even if hidden)
			$excerpt = wp_trim_words( get_the_excerpt( $post_id ), 30 );

			// Build gallery array for lightbox (hero video/image + content gallery)
			$gallery = [];
			if ( ! empty( $hero_video_url ) ) {
				$gallery[] = [ 'type' => 'video', 'src' => $hero_video_url ];
			}
			if ( $image_full ) {
				$img_caption = wp_get_attachment_caption( $image_id );
				$slide       = [ 'type' => 'image', 'src' => $image_full[0], 'w' => $image_full[1], 'h' => $image_full[2], 'cap' => $img_caption ?: '' ];
				$exif        = agoodsite_fse_get_image_exif( $image_id );
				if ( $exif ) {
					$slide['meta'] = $exif;
				}
				$gallery[] = $slide;
			}
			// Parse gallery blocks from post content
			$post_content = get_post_field( 'post_content', $post_id );
			if ( $post_content && has_blocks( $post_content ) ) {
				$blocks = parse_blocks( $post_content );
				foreach ( $blocks as $pblock ) {
					if ( 'core/gallery' === $pblock['blockName'] && ! empty( $pblock['innerBlocks'] ) ) {
						foreach ( $pblock['innerBlocks'] as $inner ) {
							if ( 'core/image' === $inner['blockName'] && ! empty( $inner['attrs']['id'] ) ) {
								$gid = $inner['attrs']['id'];
								if ( $gid === $image_id ) continue; // skip featured image duplicate
								$gimg = wp_get_attachment_image_src( $gid, 'large' );
								if ( $gimg ) {
									$gcap  = wp_get_attachment_caption( $gid );
									$slide = [ 'type' => 'image', 'src' => $gimg[0], 'w' => $gimg[1], 'h' => $gimg[2], 'cap' => $gcap ?: '' ];
									$exif  = agoodsite_fse_get_image_exif( $gid );
									if ( $exif ) {
										$slide['meta'] = $exif;
									}
									$gallery[] = $slide;
								}
							}
						}
					}
				}
			}

			// Determine link href
			$href = $click_action === 'link' ? get_permalink( $post_id ) : ( $image_full[0] ?? '' );
			$tag  = $click_action === 'none' ? 'div' : 'a';
			$tag  = in_array( $tag, [ 'a', 'div' ], true ) ? $tag : 'div';

			// Item classes
			$item_classes = [ 'masonry-query__item' ];
			if ( $primary_cat ) {
				$item_classes[] = 'masonry-query__item--cat-' . $primary_cat->slug;
			}
			?>

			<<?php echo $tag; ?>
				class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
				<?php if ( $tag === 'a' ) : ?>
					href="<?php echo esc_url( $href ); ?>"
					<?php if ( $click_action === 'link' && $link_target === '_blank' ) : ?>
						target="_blank" rel="noopener"
					<?php endif; ?>
				<?php endif; ?>
				data-index="<?php echo esc_attr( $index ); ?>"
				data-post-id="<?php echo esc_attr( $post_id ); ?>"
				data-permalink="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
				<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
					data-category="<?php echo esc_attr( implode( ',', wp_list_pluck( $categories, 'slug' ) ) ); ?>"
				<?php endif; ?>
				<?php if ( ! empty( $tag_names ) ) : ?>
					data-tags="<?php echo esc_attr( implode( ',', $tag_names ) ); ?>"
				<?php endif; ?>
				<?php if ( ! empty( $tag_links ) ) : ?>
					data-tag-links="<?php echo esc_attr( wp_json_encode( $tag_links ) ); ?>"
				<?php endif; ?>
				<?php if ( $click_action === 'lightbox' && $image_full ) : ?>
					data-pswp-src="<?php echo esc_url( $image_full[0] ); ?>"
					data-pswp-width="<?php echo esc_attr( $image_full[1] ); ?>"
					data-pswp-height="<?php echo esc_attr( $image_full[2] ); ?>"
				<?php endif; ?>
				<?php if ( ! empty( $gallery ) ) : ?>
					data-gallery="<?php echo esc_attr( wp_json_encode( $gallery ) ); ?>"
				<?php endif; ?>
				<?php if ( ! empty( $excerpt ) ) : ?>
					data-excerpt="<?php echo esc_attr( $excerpt ); ?>"
				<?php endif; ?>
			>
				<div class="masonry-query__image-wrapper" style="<?php echo $image_fit === 'contain' ? '--image-fit: contain;' : ''; ?><?php echo ! $has_image ? 'aspect-ratio:4/3;' : ''; ?>">
					<?php if ( ! empty( $hero_video_url ) ) : ?>
						<video autoplay muted loop playsinline webkit-playsinline preload="auto" disablepictureinpicture disableremoteplayback aria-hidden="true" src="<?php echo esc_url( $hero_video_url ); ?>"></video>
					<?php elseif ( $image_full ) : ?>
						<img
							src="<?php echo esc_url( $image_large[0] ?? $image_full[0] ); ?>"
							alt="<?php echo esc_attr( $image_alt ); ?>"
							width="<?php echo esc_attr( $image_large[1] ?? $image_full[1] ); ?>"
							height="<?php echo esc_attr( $image_large[2] ?? $image_full[2] ); ?>"
							loading="lazy"
							decoding="async"
						/>
					<?php endif; ?>
				</div>

				<!-- Overlay with visible content -->
				<?php if ( $overlay_style !== 'none' && ( $show_title || $show_category || $show_excerpt || $show_date ) ) : ?>
					<div class="masonry-query__overlay">
						<div class="masonry-query__content"<?php if ( $overlay_font ) : ?> style="font-family: var(--wp--preset--font-family--<?php echo esc_attr( $overlay_font ); ?>)"<?php endif; ?>>
							<?php if ( $show_category && $primary_cat ) : ?>
								<span class="masonry-query__category">
									<?php echo esc_html( $primary_cat->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $show_title ) : ?>
								<h3 class="masonry-query__title">
									<?php echo esc_html( get_the_title( $post_id ) ); ?>
								</h3>
							<?php endif; ?>

							<?php if ( $show_excerpt ) : ?>
								<p class="masonry-query__excerpt">
									<?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 15 ) ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $show_date ) : ?>
								<time class="masonry-query__date" datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>">
									<?php echo esc_html( get_the_date( '', $post_id ) ); ?>
								</time>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Hidden data for lightbox (always present) -->
				<div class="masonry-query__lightbox-data" hidden>
					<?php if ( ! $show_title ) : ?>
						<h3 class="masonry-query__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
					<?php endif; ?>
					<?php if ( ! $show_excerpt && $excerpt ) : ?>
						<p class="masonry-query__excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
					<?php if ( ! $show_category && $primary_cat ) : ?>
						<span class="masonry-query__category"><?php echo esc_html( $primary_cat->name ); ?></span>
					<?php endif; ?>
				</div>

			</<?php echo $tag; ?>>

			<?php
			$index++;
		endwhile;
		wp_reset_postdata();
		?>
	</div>

	<?php if ( $enable_paging && $query->max_num_pages > 1 ) : ?>
		<div class="masonry-query__pagination" data-type="<?php echo esc_attr( $paging_type ); ?>" data-max-pages="<?php echo esc_attr( $query->max_num_pages ); ?>">
			<?php if ( $paging_type === 'load-more' || $paging_type === 'infinite' ) : ?>
				<button type="button" class="masonry-query__load-more">
					<?php echo esc_html( $load_more_text ); ?>
				</button>
			<?php elseif ( $paging_type === 'numbered' ) : ?>
				<?php
				echo paginate_links( [
					'total'     => $query->max_num_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				] );
				?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</div>
