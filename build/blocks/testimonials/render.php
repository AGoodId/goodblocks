<?php
/**
 * Testimonials Block — Server-side render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks content (unused).
 * @var WP_Block $block      Block instance.
 * @package GoodBlocks
 */

$items = isset( $attributes['items'] ) ? (array) $attributes['items'] : [];

// Filter out slides with no quote.
$items = array_filter( $items, fn( $item ) => ! empty( $item['quote'] ) );

if ( empty( $items ) ) {
	return;
}

$autoplay       = ! empty( $attributes['autoplay'] );
$autoplay_delay = isset( $attributes['autoplayDelay'] ) ? (int) $attributes['autoplayDelay'] : 5000;
$animation      = isset( $attributes['animation'] ) && $attributes['animation'] === 'slide' ? 'slide' : 'fade';
$show_arrows    = ! empty( $attributes['showArrows'] );
$show_dots      = ! empty( $attributes['showDots'] );

$wrapper_attrs = get_block_wrapper_attributes( [
	'class'                 => 'swiper',
	'data-autoplay'         => $autoplay ? 'true' : 'false',
	'data-autoplay-delay'   => (string) $autoplay_delay,
	'data-animation'        => $animation,
	'data-show-arrows'      => $show_arrows ? 'true' : 'false',
	'data-show-dots'        => $show_dots ? 'true' : 'false',
	'data-prev-label'       => __( 'Previous testimonial', 'goodblocks' ),
	'data-next-label'       => __( 'Next testimonial', 'goodblocks' ),
] );
?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="swiper-wrapper">
		<?php foreach ( $items as $item ) :
			$quote  = isset( $item['quote'] )  ? $item['quote']  : '';
			$author = isset( $item['author'] ) ? $item['author'] : '';
			$role   = isset( $item['role'] )   ? $item['role']   : '';
		?>
		<div class="swiper-slide">
			<blockquote class="testimonial-slide">
				<p class="testimonial-quote"><?php echo esc_html( $quote ); ?></p>
				<?php if ( $author ) : ?>
				<footer>
					<cite>
						<span class="testimonial-author"><?php echo esc_html( $author ); ?></span><?php if ( $role ) : ?><span class="testimonial-role"><?php echo esc_html( $role ); ?></span><?php endif; ?>
					</cite>
				</footer>
				<?php endif; ?>
			</blockquote>
		</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $show_arrows ) : ?>
	<div class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Previous', 'goodblocks' ); ?>"></div>
	<div class="swiper-button-next" aria-label="<?php esc_attr_e( 'Next', 'goodblocks' ); ?>"></div>
	<?php endif; ?>

	<?php if ( $show_dots ) : ?>
	<div class="swiper-pagination"></div>
	<?php endif; ?>

	<?php if ( $autoplay ) : ?>
	<button
		class="testimonials-autoplay-toggle"
		data-state="playing"
		aria-label="<?php esc_attr_e( 'Pause carousel', 'goodblocks' ); ?>"
		data-label-play="<?php esc_attr_e( 'Play carousel', 'goodblocks' ); ?>"
		data-label-pause="<?php esc_attr_e( 'Pause carousel', 'goodblocks' ); ?>"
	></button>
	<?php endif; ?>
</div>
