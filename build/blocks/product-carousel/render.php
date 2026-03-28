<?php
/**
 * Product Carousel Block — Server-side Render
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return '';
}

$rubrik          = $attributes['rubrik'] ?? '';
$text            = $attributes['text'] ?? '';
$products_to_show = $attributes['productsToShow'] ?? 8;
$category        = $attributes['category'] ?? '';
$tag             = $attributes['tag'] ?? '';
$formgivare      = $attributes['formgivare'] ?? '';
$orderby         = $attributes['orderBy'] ?? 'menu_order';
$manual_mode     = $attributes['manualMode'] ?? false;
$manual_products = $attributes['manualProducts'] ?? [];

if ( $manual_mode && ! empty( $manual_products ) ) {
	$args = [
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => count( $manual_products ),
		'post__in'       => $manual_products,
		'orderby'        => 'post__in',
	];
} else {
	$order  = 'ASC';
	$meta_key = '';

	switch ( $orderby ) {
		case 'title-asc':
			$wp_orderby = 'title';
			$order      = 'ASC';
			break;
		case 'title-desc':
			$wp_orderby = 'title';
			$order      = 'DESC';
			break;
		case 'popularity':
			$wp_orderby = 'meta_value_num';
			$order      = 'DESC';
			$meta_key   = 'total_sales';
			break;
		case 'price':
			$wp_orderby = 'meta_value_num';
			$order      = 'ASC';
			$meta_key   = '_price';
			break;
		case 'price-desc':
			$wp_orderby = 'meta_value_num';
			$order      = 'DESC';
			$meta_key   = '_price';
			break;
		default:
			$wp_orderby = 'menu_order';
			$order      = 'ASC';
	}

	$args = [
		'post_type'      => 'product',
		'posts_per_page' => $products_to_show,
		'post_status'    => 'publish',
		'orderby'        => $wp_orderby,
		'order'          => $order,
	];

	if ( $meta_key ) {
		$args['meta_key'] = $meta_key;
	}

	$tax_query = [];
	if ( $category ) {
		$tax_query[] = [
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $category,
		];
	}
	if ( $tag ) {
		$tax_query[] = [
			'taxonomy' => 'product_tag',
			'field'    => 'slug',
			'terms'    => $tag,
		];
	}
	if ( $formgivare ) {
		$tax_query[] = [
			'taxonomy' => 'pa_formgivare',
			'field'    => 'slug',
			'terms'    => $formgivare,
		];
	}
	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = $tax_query;
	}
}

$query = new WP_Query( $args );

if ( ! $query->have_posts() ) {
	wp_reset_postdata();
	return '';
}

$product_count       = $query->post_count;
$product_count_class = 'products-count-' . $product_count;
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php if ( $rubrik || $text ) : ?>
		<div class="product-carousel-header">
			<?php if ( $rubrik ) : ?>
				<h2 class="product-carousel-title"><?php echo esc_html( $rubrik ); ?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<div class="product-carousel-text"><?php echo esc_html( $text ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="swiper product-carousel-swiper">
		<div class="swiper-wrapper products <?php echo esc_attr( $product_count_class ); ?>">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<div class="swiper-slide">
					<?php wc_get_template_part( 'content', 'product' ); ?>
				</div>
			<?php endwhile; ?>
		</div>
		<div class="swiper-button-next"></div>
		<div class="swiper-button-prev"></div>
	</div>
</div>
<?php
wp_reset_postdata();
