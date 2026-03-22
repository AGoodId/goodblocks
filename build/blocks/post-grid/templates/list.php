<?php
/**
 * Post Grid — List layout template.
 *
 * Override by copying to: your-theme/goodblocks/templates/post-grid/list.php
 *
 * @var array $attributes Block attributes.
 * @package GoodBlocks
 */
?>
<div class="list-item">

	<?php if ( $attributes['showFeaturedImage'] && has_post_thumbnail() ) : ?>
		<a href="<?php the_permalink(); ?>" class="post-thumbnail"
			style="aspect-ratio: <?php echo esc_attr( $attributes['aspectRatio'] ); ?>;">
			<?php echo goodblocks_get_thumbnail( 'large' ); ?>
		</a>
	<?php endif; ?>

	<div class="post-content">

		<?php if ( $attributes['showTitle'] ) : ?>
			<h3 class="post-title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>
		<?php endif; ?>

		<?php if ( $attributes['showDate'] ) : ?>
			<?php if ( $attributes['postType'] === 'tribe_events' && function_exists( 'tribe_get_start_date' ) ) : ?>
				<div class="post-date event-post-date"><?php do_action( 'goodblocks_event_date_range', get_the_ID() ); ?></div>
			<?php else : ?>
				<div class="post-date"><?php echo get_the_date(); ?></div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $attributes['showAuthor'] ) : ?>
			<div class="post-author"><?php echo esc_html( get_the_author() ); ?></div>
		<?php endif; ?>

		<?php if ( $attributes['showExcerpt'] ) : ?>
			<div class="post-excerpt">
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
