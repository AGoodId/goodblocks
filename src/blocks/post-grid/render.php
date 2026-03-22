<?php
/**
 * Post Grid — server-side render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 *
 * @package GoodBlocks
 */

$current_date = current_time( 'mysql' );

// Post type: support both array (postTypes) and legacy string (postType).
$post_type_raw = $attributes['postType'] ?? 'post';
if ( is_array( $post_type_raw ) ) {
	$post_types = $post_type_raw;
} elseif ( str_contains( $post_type_raw, ',' ) ) {
	$post_types = array_map( 'trim', explode( ',', $post_type_raw ) );
} else {
	$post_types = [ $post_type_raw ];
}

// WP_Query accepts array for post_type.
$query_args = [
	'post_type'      => count( $post_types ) === 1 ? $post_types[0] : $post_types,
	'posts_per_page' => $attributes['postsToShow'],
	'post_status'    => 'publish',
	'tax_query'      => [],
];

if ( $attributes['sortOrder'] === 'asc' || $attributes['sortOrder'] === 'desc' ) {
	$query_args['order']   = $attributes['sortOrder'];
	$query_args['orderby'] = 'post_date';
} elseif ( $attributes['sortOrder'] === 'menu' ) {
	$query_args['orderby'] = 'menu_order';
} elseif ( $attributes['sortOrder'] === 'meta_desc' || $attributes['sortOrder'] === 'meta_asc' ) {
	if ( is_numeric( get_post_meta( get_the_ID(), $attributes['metaKey'], true ) ) ) {
		$query_args['orderby'] = 'meta_value_num';
	} else {
		$query_args['orderby'] = 'meta_value';
	}
	$query_args['meta_key'] = $attributes['metaKey'];
	$query_args['order']    = $attributes['sortOrder'] === 'meta_desc' ? 'DESC' : 'ASC';
} elseif ( $attributes['sortOrder'] === 'date_upcoming' ) {
	$query_args['order']      = 'ASC';
	$query_args['orderby']    = 'meta_value';
	$query_args['meta_key']   = '_EventStartDate';
	$query_args['meta_type']  = 'DATETIME';
	$query_args['meta_query'] = [
		[
			'key'     => '_EventStartDate',
			'value'   => $current_date,
			'compare' => '>=',
			'type'    => 'DATETIME',
		],
	];
} elseif ( $attributes['sortOrder'] === 'date_past' ) {
	$query_args['order']      = 'DESC';
	$query_args['orderby']    = 'meta_value';
	$query_args['meta_key']   = '_EventStartDate';
	$query_args['meta_type']  = 'DATETIME';
	$query_args['meta_query'] = [
		[
			'key'     => '_EventStartDate',
			'value'   => $current_date,
			'compare' => '<',
			'type'    => 'DATETIME',
		],
	];
} elseif ( $attributes['sortOrder'] === 'modified_desc' ) {
	$query_args['orderby'] = 'modified';
	$query_args['order']   = 'DESC';
}

// Taxonomy filter — supports multiple terms.
$tax_terms_raw = $attributes['taxonomyTerms'] ?? '';
if ( is_string( $tax_terms_raw ) && $tax_terms_raw !== '' ) {
	$tax_terms = array_map( 'trim', explode( ',', $tax_terms_raw ) );
} elseif ( is_array( $tax_terms_raw ) ) {
	$tax_terms = $tax_terms_raw;
} else {
	$tax_terms = [];
}

if ( ! empty( $tax_terms ) && ! empty( $attributes['selectedTaxonomy'] ) ) {
	$query_args['tax_query'][] = [
		'taxonomy' => $attributes['selectedTaxonomy'],
		'field'    => 'slug',
		'terms'    => $tax_terms,
		'operator' => 'IN',
	];
}

// Child/parent logic for pages.
if (
	! empty( $attributes['showChildren'] ) &&
	in_array( 'page', $post_types, true ) &&
	! empty( $attributes['parentPost'] )
) {
	$query_args['post_parent'] = intval( $attributes['parentPost'] );
}

$query = new WP_Query( $query_args );

// Fallback for upcoming events: show past events if none upcoming.
if (
	in_array( 'tribe_events', $post_types, true ) &&
	$attributes['sortOrder'] === 'date_upcoming' &&
	! $query->have_posts()
) {
	$query_args['order']      = 'DESC';
	$query_args['meta_query'] = [
		[
			'key'     => '_EventStartDate',
			'value'   => $current_date,
			'compare' => '<',
			'type'    => 'DATETIME',
		],
	];
	$query = new WP_Query( $query_args );
}

$index = 0;
if ( $query->have_posts() ) : ?>
	<div <?php echo get_block_wrapper_attributes( [
		'style' => '--posts-per-row: ' . intval( $attributes['postsPerRow'] ) . ';',
		'class' => esc_attr( implode( ' ', $post_types ) . ' ' . $attributes['gridType'] ),
	] ); ?>>
		<div class="timeline-line"></div>

		<?php while ( $query->have_posts() ) :
			$query->the_post();
			$attributes['isLeft'] = $index % 2 === 1;

			if ( $attributes['gridType'] === 'list' ) {
				goodblocks_template( 'post-grid', 'list', $attributes );
			} elseif ( $attributes['gridType'] === 'people' ) {
				goodblocks_template( 'post-grid', 'people', $attributes );
			} elseif ( $attributes['gridType'] === 'timeline' ) {
				goodblocks_template( 'post-grid', 'timeline', $attributes );
			} else {
				goodblocks_template( 'post-grid', 'grid', $attributes );
			}

			$index++;
		endwhile; ?>

		<?php if ( ! empty( $attributes['showMoreLink'] ) && ! empty( $attributes['moreLinkUrl'] ) ) : ?>
			<div class="post-grid-more-link">
				<a href="<?php echo esc_url( $attributes['moreLinkUrl'] ); ?>" class="btn btn-primary">
					<?php echo esc_html( ! empty( $attributes['moreLinkText'] ) ? $attributes['moreLinkText'] : __( 'Fler nyheter', 'goodblocks' ) ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>

<?php else : ?>
	<?php if ( in_array( 'tribe_events', $post_types, true ) ) : ?>
		<p class="my-6"><?php esc_html_e( 'Inga kommande händelser.', 'goodblocks' ); ?></p>
	<?php else : ?>
		<p class="my-6"><?php echo esc_html( $attributes['noPostsText'] ); ?></p>
	<?php endif; ?>
<?php endif;
wp_reset_postdata();
