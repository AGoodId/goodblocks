<?php
/**
 * Post Grid — Timeline layout template.
 *
 * Override by copying to: your-theme/goodblocks/templates/post-grid/timeline.php
 *
 * @var array $attributes Block attributes.
 * @package GoodBlocks
 */

$year  = get_post_meta( get_the_ID(), 'year', true );
$small = get_post_meta( get_the_ID(), 'small', true );
?>
<div class="timeline-item <?php echo $attributes['isLeft'] ? 'is-left' : 'is-right'; ?> <?php echo $small ? 'is-small' : ''; ?>">
	<div class="timeline-dot"></div>

	<div class="timeline-content">

		<h3 class="timeline-title">
			<?php if ( $small ) : ?>
				<span class="timeline-year"><?php echo wp_kses_post( $year ); ?></span><span class="timeline-year-separator"> – </span><?php echo get_the_title(); ?>
			<?php else : ?>
				<a href="<?php the_permalink(); ?>"><span class="timeline-year"><?php echo wp_kses_post( $year ); ?></span><span class="timeline-year-separator"> – </span><?php the_title(); ?></a>
			<?php endif; ?>
		</h3>

		<?php if ( $attributes['showFeaturedImage'] && has_post_thumbnail() ) : ?>
			<a href="<?php the_permalink(); ?>" class="timeline-thumbnail">
				<?php echo goodblocks_get_thumbnail( 'large' ); ?>
			</a>
		<?php endif; ?>

		<div class="timeline-text">

			<?php if ( $attributes['showExcerpt'] ) : ?>
				<div class="timeline-excerpt">
					<?php if ( has_excerpt() ) : ?>
						<?php the_excerpt(); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! $small ) : ?>
				<a href="<?php the_permalink(); ?>" class="timeline-read-more btn btn-primary">
					<?php esc_html_e( 'Läs mer', 'goodblocks' ); ?>
				</a>
			<?php endif; ?>

		</div>

	</div>
</div>
