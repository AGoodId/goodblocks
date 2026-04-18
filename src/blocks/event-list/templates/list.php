<?php
/**
 * Event List — List layout template.
 *
 * Override by copying to: your-theme/goodblocks/templates/event-list/list.php
 *
 * @var array $attributes Block attributes.
 * @package GoodBlocks
 */

$event_start = get_post_meta( get_the_ID(), '_event_start', true );
$event_end   = get_post_meta( get_the_ID(), '_event_end', true );
?>
<div class="event-list-item">

	<?php if ( $attributes['showFeaturedImage'] && has_post_thumbnail() ) : ?>
		<a href="<?php the_permalink(); ?>" class="event-thumbnail">
			<?php echo goodblocks_get_thumbnail( 'large' ); ?>
		</a>
	<?php endif; ?>

	<div class="event-content">
		<?php if ( $event_start ) : ?>
			<div class="event-date"><?php echo esc_html( goodblocks_format_event_date( $event_start, $event_end ) ); ?></div>
		<?php endif; ?>

		<h3 class="event-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<?php if ( $attributes['showExcerpt'] ) : ?>
			<div class="event-excerpt">
				<?php echo esc_html( wp_trim_words( get_the_excerpt(), $attributes['excerptLength'], '…' ) ); ?>
			</div>
		<?php endif; ?>
	</div>

</div>
