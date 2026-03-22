<?php
/**
 * Post Grid — People layout template.
 *
 * Override by copying to: your-theme/goodblocks/templates/post-grid/people.php
 *
 * @var array $attributes Block attributes.
 * @package GoodBlocks
 */
?>
<div class="post-grid-item post-grid-people">

	<?php if ( $attributes['showFeaturedImage'] ) : ?>
		<?php
		$people_image = get_post_meta( get_the_ID(), 'image', true );
		if ( ! empty( $people_image ) ) :
			?>
			<a href="<?php the_permalink(); ?>" class="post-thumbnail post-thumbnail-people"
				style="--aspect-ratio: <?php echo esc_attr( $attributes['aspectRatio'] ); ?>;">
				<?php echo wp_get_attachment_image( $people_image, 'large' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php the_permalink(); ?>" class="post-thumbnail post-thumbnail-people"
				style="--aspect-ratio: <?php echo esc_attr( $attributes['aspectRatio'] ); ?>;">
				<?php echo goodblocks_get_thumbnail( 'large' ); ?>
			</a>
		<?php endif; ?>
	<?php endif; ?>

	<div class="post-grid-below-wrapper">
		<?php if ( $attributes['showTitle'] ) : ?>
			<h3 class="post-title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h3>
		<?php endif; ?>

		<?php
		$people_title = get_post_meta( get_the_ID(), 'title', true );
		if ( ! empty( $people_title ) ) :
			?>
			<div class="people-title-meta">
				<?php echo esc_html( $people_title ); ?>
			</div>
		<?php endif; ?>
	</div>

</div>
