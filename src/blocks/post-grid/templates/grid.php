<?php
/**
 * Post Grid — Grid layout template.
 *
 * Override by copying to: your-theme/goodblocks/templates/post-grid/grid.php
 *
 * @var array $attributes Block attributes.
 * @package GoodBlocks
 */
?>
<div class="post-grid-item">

	<?php if ( $attributes['showFeaturedImage'] ) : ?>
		<a href="<?php the_permalink(); ?>" class="post-thumbnail"
			style="--aspect-ratio: <?php echo esc_attr( $attributes['aspectRatio'] ); ?>;">
			<?php echo goodblocks_get_thumbnail( 'large' ); ?>
		</a>
	<?php endif; ?>

	<div class="post-grid-below-wrapper">
		<?php if ( $attributes['showTitle'] ) : ?>
			<h3 class="post-title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>
		<?php endif; ?>

		<?php if ( $attributes['showDate'] ) : ?>
			<?php if ( $attributes['postType'] === 'tribe_events' && function_exists( 'tribe_get_start_date' ) ) : ?>
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
				<?php if ( $attributes['postType'] === 'tribe_events' ) :
					$date = strtotime( get_post_meta( get_the_ID(), '_EventEndDate', true ) );
					if ( $date && $date < time() ) {
						echo '<span class="past-event-alert">' . esc_html__( 'Tidigare evenemang.', 'goodblocks' ) . '</span>';
					}
				endif; ?>
				<?php
				if ( has_excerpt() ) {
					echo esc_html( get_the_excerpt() );
				} else {
					echo esc_html( wp_trim_words( get_the_excerpt(), $attributes['excerptLength'], '...' ) );
				}
				?>
			</div>
		<?php endif; ?>
	</div>

</div>
