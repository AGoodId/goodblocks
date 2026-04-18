<?php
/**
 * Event List — server-side render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package GoodBlocks
 */

$current_dt = current_time( 'mysql' );

$query_args = [
	'post_type'      => 'goodblocks_event',
	'posts_per_page' => absint( $attributes['eventsToShow'] ),
	'post_status'    => 'publish',
	'orderby'        => 'meta_value',
	'meta_key'       => '_event_start',
	'meta_type'      => 'DATETIME',
	'order'          => ! empty( $attributes['showPast'] ) ? 'DESC' : 'ASC',
];

if ( empty( $attributes['showPast'] ) ) {
	$query_args['meta_query'] = [
		[
			'key'     => '_event_start',
			'value'   => $current_dt,
			'compare' => '>=',
			'type'    => 'DATETIME',
		],
	];
}

if ( ! empty( $attributes['categorySlug'] ) ) {
	$query_args['tax_query'] = [
		[
			'taxonomy' => 'event_category',
			'field'    => 'slug',
			'terms'    => sanitize_key( $attributes['categorySlug'] ),
		],
	];
}

$query = new WP_Query( $query_args );

if ( $query->have_posts() ) : ?>
	<div <?php echo get_block_wrapper_attributes( [
		'style' => '--events-per-row: ' . absint( $attributes['eventsPerRow'] ) . ';',
		'class' => 'goodblocks-event-list ' . esc_attr( $attributes['viewMode'] ),
	] ); ?>>
		<?php while ( $query->have_posts() ) :
			$query->the_post();
			if ( 'list' === $attributes['viewMode'] ) {
				goodblocks_template( 'event-list', 'list', $attributes );
			} else {
				goodblocks_template( 'event-list', 'grid', $attributes );
			}
		endwhile; ?>
	</div>
<?php else : ?>
	<p class="goodblocks-no-events"><?php echo esc_html( $attributes['noEventsText'] ); ?></p>
<?php endif;

wp_reset_postdata();
