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

$events = goodblocks_get_events( [
	'posts_per_page' => absint( $attributes['eventsToShow'] ),
	'show_past'      => ! empty( $attributes['showPast'] ),
	'category_slug'  => $attributes['categorySlug'] ?? '',
	'order'          => ! empty( $attributes['showPast'] ) ? 'DESC' : 'ASC',
] );

if ( $events ) : ?>
	<div <?php echo get_block_wrapper_attributes( [
		'style' => '--events-per-row: ' . absint( $attributes['eventsPerRow'] ) . ';',
		'class' => 'goodblocks-event-list ' . esc_attr( $attributes['viewMode'] ),
	] ); ?>>
		<?php foreach ( $events as $event ) :
			$post = get_post( $event['id'] );
			if ( ! $post ) {
				continue;
			}
			setup_postdata( $post );
			$GLOBALS['goodblocks_event_data'] = $event;
			if ( 'list' === $attributes['viewMode'] ) {
				goodblocks_template( 'event-list', 'list', $attributes );
			} else {
				goodblocks_template( 'event-list', 'grid', $attributes );
			}
		endforeach; ?>
	</div>
<?php else : ?>
	<p class="goodblocks-no-events"><?php echo esc_html( $attributes['noEventsText'] ); ?></p>
<?php endif;

unset( $GLOBALS['goodblocks_event_data'] );
wp_reset_postdata();
