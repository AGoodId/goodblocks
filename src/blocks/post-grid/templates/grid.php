<?php
/**
 * Post Grid — Grid layout template.
 *
 * Override by copying to: your-theme/goodblocks/templates/post-grid/grid.php
 *
 * @var array $attributes Block attributes.
 * @package GoodBlocks
 */
$post_type           = get_post_type();
$is_goodblocks_event = 'goodblocks_event' === $post_type && function_exists( 'goodblocks_get_event_data' );
$event               = $is_goodblocks_event ? goodblocks_get_event_data( get_the_ID() ) : null;
?>
<div class="post-grid-item">

	<?php if ( $attributes['showFeaturedImage'] ) : ?>
		<a href="<?php the_permalink(); ?>" class="post-thumbnail"
			style="--aspect-ratio: <?php echo esc_attr( $attributes['aspectRatio'] ); ?>;">
			<?php echo goodblocks_get_thumbnail( 'large', array(), $attributes['imageSource'] ?? 'featured' ); ?>
		</a>
	<?php endif; ?>

	<div class="post-grid-below-wrapper">
		<?php if ( $attributes['showTitle'] ) : ?>
			<h3 class="post-title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>
		<?php endif; ?>

		<?php if ( $attributes['showDate'] ) : ?>
			<?php if ( $is_goodblocks_event && $event ) : ?>
				<div class="post-date event-post-date"><?php echo esc_html( $event['range_label'] ); ?></div>
				<?php if ( $event['class'] || $event['type'] || $event['venue'] ) : ?>
					<div class="post-event-meta">
						<?php if ( $event['type'] ) : ?>
							<span><?php echo esc_html( $event['type_label'] ); ?></span>
						<?php endif; ?>
						<?php if ( $event['class'] ) : ?>
							<span><?php echo esc_html( $event['class'] ); ?></span>
						<?php endif; ?>
						<?php if ( $event['venue'] ) : ?>
							<span><?php echo esc_html( $event['venue'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php elseif ( $attributes['postType'] === 'tribe_events' && function_exists( 'tribe_get_start_date' ) ) : ?>
				<div class="post-date event-post-date"><?php do_action( 'goodblocks_event_date_range', get_the_ID() ); ?></div>
				<?php if ( function_exists( 'tribe_has_venue' ) && tribe_has_venue( get_the_ID() ) ) : ?>
					<div class="post-venue post-date event-post-date">
						<?php echo esc_html( tribe_get_venue( get_the_ID() ) ); ?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="post-date"><?php echo get_the_date(); ?></div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $attributes['showAuthor'] ) : ?>
			<div class="post-author"><?php echo esc_html( get_the_author() ); ?></div>
		<?php endif; ?>

		<?php if ( $attributes['showExcerpt'] ) : ?>
			<div class="post-excerpt">
				<?php if ( $is_goodblocks_event && $event && in_array( $event['status'], [ 'changed', 'cancelled', 'live' ], true ) ) : ?>
					<span class="past-event-alert event-status-alert is-<?php echo esc_attr( $event['status'] ); ?>"><?php echo esc_html( $event['status_label'] ); ?></span>
				<?php endif; ?>
				<?php if ( $attributes['postType'] === 'tribe_events' ) :
					$date = strtotime( get_post_meta( get_the_ID(), '_EventEndDate', true ) );
					if ( $date && $date < time() ) {
						echo '<span class="past-event-alert">' . esc_html__( 'Tidigare evenemang.', 'goodblocks' ) . '</span>';
					}
				endif; ?>
				<?php echo esc_html( goodblocks_get_trimmed_excerpt( $attributes['excerptLength'] ?? 35 ) ); ?>
			</div>
		<?php endif; ?>
	</div>

</div>
