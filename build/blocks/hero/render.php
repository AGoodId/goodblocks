<?php
/**
 * Hero Block — Server-side Render
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

// Animation: graceful degradation for legacy values (ingen/standard/wild/from-right/from-left).
$animation = $attributes['animation'] ?? 'none';
if ( ! in_array( $animation, [ 'none', 'fade-up', 'split-words' ], true ) ) {
	$animation = 'none';
}

$image_type = 'color';
if ( ! empty( $attributes['backgroundMedia'] ) ) {
	$image_type = $attributes['backgroundMedia']['type'];
}

$overlay_style = sprintf(
	'background-color:%s;opacity:%s;',
	esc_attr( $attributes['overlayColor'] ?? '#000000' ),
	esc_attr( ( $attributes['dimRatio'] ?? 0 ) / 100 )
);

$inline_style = '';
if ( ! empty( $attributes['backgroundMedia'] ) && 'image' === $image_type ) {
	$inline_style = 'background-image:url(' . esc_url( $attributes['backgroundMedia']['url'] ) . ');';
}

$height = $attributes['height'] ?? '100svh';
$inline_style .= 'height:' . ( '100svh' !== $height
	? esc_attr( $height )
	: 'calc(' . esc_attr( $height ) . ' - var(--wp-admin--admin-bar--height, 0px))' ) . ';';

// Position class derived from contentPosition (no longer cached).
$position_map = [
	'top left'      => 'is-position-top-left',
	'top center'    => 'is-position-top-center',
	'top right'     => 'is-position-top-right',
	'center left'   => 'is-position-center-left',
	'center center' => '',
	'center'        => '',
	'center right'  => 'is-position-center-right',
	'bottom left'   => 'is-position-bottom-left',
	'bottom center' => 'is-position-bottom-center',
	'bottom right'  => 'is-position-bottom-right',
];
$content_position = $attributes['contentPosition'] ?? 'center center';
$position_class   = $position_map[ $content_position ] ?? '';

$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => 'hero-block hero-block--' . $animation,
	'style' => $inline_style,
] );
?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( 'video' === $image_type && ! empty( $attributes['backgroundMedia'] ) ) : ?>
		<video autoplay muted loop playsinline class="hero-block__video">
			<source
				src="<?php echo esc_url( $attributes['backgroundMedia']['url'] ); ?>"
				type="<?php echo esc_attr( $attributes['backgroundMedia']['mime'] ); ?>" />
		</video>
	<?php endif; ?>
	<div class="hero-block__overlay" style="<?php echo esc_attr( $overlay_style ); ?>"></div>
	<div class="hero-block__content <?php echo esc_attr( $position_class ); ?>">
		<div class="hero-block__container">
			<div class="hero-block__text<?php echo ! empty( $attributes['reverseFlow'] ) ? ' reverse-flow' : ''; ?>">
				<?php if ( ! empty( $attributes['rubrik'] ) ) : ?>
					<h2><?php echo wp_kses_post( $attributes['rubrik'] ); ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['text'] ) ) : ?>
					<p><?php echo wp_kses_post( $attributes['text'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $attributes['button'] ) ) : ?>
				<button type="button" class="btn btn-large">
					<span><?php echo wp_kses_post( $attributes['button'] ); ?></span>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( ! empty( $attributes['scrollArrow'] ) ) : ?>
		<button type="button" class="hero-block__scroll-arrow" aria-label="<?php esc_attr_e( 'Scroll down', 'goodblocks' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
				<path d="M440-800v487L216-537l-56 57 320 320 320-320-56-57-224 224v-487h-80Z" />
			</svg>
		</button>
	<?php endif; ?>
</div>
