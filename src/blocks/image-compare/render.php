<?php
/**
 * Image Compare Block - Server-side Render
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$before_url = $attributes['beforeUrl'] ?? '';
$after_url  = $attributes['afterUrl'] ?? '';
if ( ! $before_url || ! $after_url ) {
	return '';
}

$before_alt    = esc_attr( $attributes['beforeAlt'] ?? '' );
$after_alt     = esc_attr( $attributes['afterAlt'] ?? '' );
$before_lbl    = esc_html( $attributes['beforeLabel'] ?? __( 'Before', 'goodblocks' ) );
$after_lbl     = esc_html( $attributes['afterLabel'] ?? __( 'After', 'goodblocks' ) );
$show_labels   = $attributes['showLabels'] ?? true;
$start         = intval( $attributes['startPosition'] ?? 50 );
$enable_tease  = $attributes['enableTease'] ?? true;
$tease_speed   = floatval( $attributes['teaseSpeed'] ?? 3 );
$tease_once    = $attributes['teaseOnce'] ?? false;
$orientation   = $attributes['orientation'] ?? 'horizontal';
$is_vertical   = $orientation === 'vertical';
$aspect_ratio  = $attributes['aspectRatio'] ?? '16:9';
$before_fp     = $attributes['beforeFocalPoint'] ?? [ 'x' => 0.5, 'y' => 0.5 ];
$after_fp      = $attributes['afterFocalPoint'] ?? [ 'x' => 0.5, 'y' => 0.5 ];

$classes = [];
if ( $is_vertical ) {
	$classes[] = 'is-vertical';
}
if ( $aspect_ratio && 'original' !== $aspect_ratio ) {
	$classes[] = 'has-aspect-ratio';
}

// Build inline styles for aspect ratio.
$inline_styles = '';
if ( $aspect_ratio && 'original' !== $aspect_ratio ) {
	$ar_css = str_replace( ':', ' / ', $aspect_ratio );
	$inline_styles = 'aspect-ratio:' . esc_attr( $ar_css ) . ';';
}

$before_obj_pos = esc_attr( round( $before_fp['x'] * 100 ) . '% ' . round( $before_fp['y'] * 100 ) . '%' );
$after_obj_pos  = esc_attr( round( $after_fp['x'] * 100 ) . '% ' . round( $after_fp['y'] * 100 ) . '%' );

$wrapper_attributes = get_block_wrapper_attributes( [
	'class'            => implode( ' ', $classes ),
	'style'            => $inline_styles,
	'data-start'       => $start,
	'data-tease'       => $enable_tease ? '1' : '0',
	'data-tease-speed' => $tease_speed,
	'data-tease-once'  => $tease_once ? '1' : '0',
	'role'             => 'group',
	'aria-label'       => esc_attr__( 'Image comparison', 'goodblocks' ),
] );

$labels = '';
if ( $show_labels ) {
	$labels = '<span class="image-compare__label image-compare__label--before" aria-hidden="true">' . $before_lbl . '</span>'
			. '<span class="image-compare__label image-compare__label--after" aria-hidden="true">' . $after_lbl . '</span>';
}

$handle_axis = $is_vertical ? 'vertical' : 'horizontal';
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="image-compare__after">
		<img src="<?php echo esc_url( $after_url ); ?>" alt="<?php echo $after_alt; ?>" loading="lazy" style="object-position:<?php echo $after_obj_pos; ?>" />
	</div>
	<div class="image-compare__before">
		<img src="<?php echo esc_url( $before_url ); ?>" alt="<?php echo $before_alt; ?>" loading="lazy" style="object-position:<?php echo $before_obj_pos; ?>" />
	</div>
	<button
		type="button"
		class="image-compare__handle"
		role="slider"
		aria-valuenow="<?php echo $start; ?>"
		aria-valuemin="0"
		aria-valuemax="100"
		aria-orientation="<?php echo esc_attr( $handle_axis ); ?>"
		aria-label="<?php esc_attr_e( 'Drag to compare images', 'goodblocks' ); ?>"
	>
		<span class="image-compare__handle-knob" aria-hidden="true"></span>
	</button>
	<?php echo $labels; ?>
</div>
