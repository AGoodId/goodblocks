<?php
/**
 * Event Now / Next — server-side render.
 *
 * @var array $attributes Block attributes.
 *
 * @package GoodBlocks
 */

$events = goodblocks_get_events( [
	'posts_per_page' => absint( $attributes['itemsToShow'] ?? 3 ),
	'show_past'      => false,
] );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => 'goodblocks-event-now-next' ] ); ?>>
	<?php if ( ! empty( $attributes['heading'] ) ) : ?>
		<h2 class="goodblocks-event-now-next__heading"><?php echo esc_html( $attributes['heading'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $events ) : ?>
		<div class="goodblocks-event-now-next__items">
			<?php foreach ( $events as $index => $event ) : ?>
				<article class="goodblocks-event-now-next__item">
					<div class="goodblocks-event-now-next__badge"><?php echo $event['is_current'] ? esc_html__( 'Now', 'goodblocks' ) : ( 0 === $index ? esc_html__( 'Next', 'goodblocks' ) : esc_html__( 'Later', 'goodblocks' ) ); ?></div>
					<div>
						<h3><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>
						<p>
							<span><?php echo esc_html( $event['range_label'] ); ?></span>
							<?php if ( $event['class'] ) : ?>
								<span><?php echo esc_html( $event['class'] ); ?></span>
							<?php endif; ?>
							<?php if ( $event['venue'] ) : ?>
								<span><?php echo esc_html( $event['venue'] ); ?></span>
							<?php endif; ?>
						</p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="goodblocks-event-now-next__empty"><?php echo esc_html( $attributes['emptyText'] ?? __( 'No upcoming schedule items.', 'goodblocks' ) ); ?></p>
	<?php endif; ?>
</section>
