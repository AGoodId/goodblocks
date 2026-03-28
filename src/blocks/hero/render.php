<?php
/**
 * Hero Block — Server-side Render
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$animation_class = 'hero-block--' . esc_attr( $attributes['animation'] ?? 'ingen' );
$image_type      = 'color';

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
	: 'calc(' . esc_attr( $height ) . ' - var(--wp-admin--admin-bar--height))' ) . ';';

// Build animated title.
$title     = '';
$animation = $attributes['animation'] ?? 'ingen';
$rubrik    = $attributes['rubrik'] ?? '';

if ( 'standard' === $animation || 'wild' === $animation ) {
	$lines = explode( '<br>', str_replace( '||br||', '<br>', $rubrik ) );
	$title = '<h2>';
	foreach ( $lines as $line ) {
		$letters = mb_str_split( $line );
		foreach ( $letters as $index => $letter ) {
			$position  = $index + 1;
			$isSpecial = ( 3 === $position || 0 === ( $position - 3 ) % 8 );
			if ( $isSpecial ) {
				$class = ( 0 === ( $position - 3 ) % 16 ) ? 'inline-block vertical-flip' : 'inline-block spin-right';
			} else {
				$class = 'inline-block';
			}
			if ( '*' === $letter ) {
				$class = 'inline-block pulse';
			}
			$title .= '<span class="' . $class . '">';
			$title .= ' ' === $letter ? '&nbsp;' : esc_html( $letter );
			$title .= '</span>';
		}
		$title .= '<br>';
	}
	$title .= '</h2>';
} elseif ( 'from-right' === $animation ) {
	$title = '<h2 class="from-right">' . wp_kses_post( $rubrik ) . '</h2>';
} elseif ( 'from-left' === $animation ) {
	$title = '<h2 class="from-left">' . wp_kses_post( $rubrik ) . '</h2>';
} else {
	$title = '<h2>' . wp_kses_post( $rubrik ) . '</h2>';
}

$position_class = esc_attr( $attributes['positionClass'] ?? '' );
?>
<div <?php echo get_block_wrapper_attributes( [ 'style' => $inline_style ] ); ?>>
	<?php if ( 'video' === $image_type && ! empty( $attributes['backgroundMedia'] ) ) : ?>
		<video autoplay muted loop playsinline class="hero-block__video">
			<source
				src="<?php echo esc_url( $attributes['backgroundMedia']['url'] ); ?>"
				type="<?php echo esc_attr( $attributes['backgroundMedia']['mime'] ); ?>" />
		</video>
	<?php endif; ?>
	<div class="hero-block__overlay" style="<?php echo esc_attr( $overlay_style ); ?>"></div>
	<div class="hero-block__content <?php echo $animation_class; ?> <?php echo $position_class; ?>">
		<div class="hero-block__container">
			<div class="hero-block__text<?php echo ! empty( $attributes['reverseFlow'] ) ? ' reverse-flow' : ''; ?>">
				<?php if ( ! empty( $rubrik ) ) : ?>
					<?php echo $title; ?>
				<?php endif; ?>
				<?php if ( ! empty( $attributes['text'] ) ) : ?>
					<p><?php echo wp_kses_post( $attributes['text'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $attributes['button'] ) ) : ?>
				<button class="btn btn-large">
					<span><?php echo wp_kses_post( $attributes['button'] ); ?></span>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( ! empty( $attributes['scrollArrow'] ) ) : ?>
		<span class="hero-block__scroll-arrow">
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
				<path d="M440-800v487L216-537l-56 57 320 320 320-320-56-57-224 224v-487h-80Z" />
			</svg>
		</span>
	<?php endif; ?>
</div>
