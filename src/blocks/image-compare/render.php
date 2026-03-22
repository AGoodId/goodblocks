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

$before_alt  = esc_attr( $attributes['beforeAlt'] ?? '' );
$after_alt   = esc_attr( $attributes['afterAlt'] ?? '' );
$before_lbl  = esc_html( $attributes['beforeLabel'] ?? __( 'Before', 'goodblocks' ) );
$after_lbl   = esc_html( $attributes['afterLabel'] ?? __( 'After', 'goodblocks' ) );
$show_labels = $attributes['showLabels'] ?? true;
$start       = intval( $attributes['startPosition'] ?? 50 );

$wrapper_attributes = get_block_wrapper_attributes( [
	'data-start' => $start,
	'role'       => 'group',
	'aria-label' => esc_attr__( 'Image comparison', 'goodblocks' ),
] );

$labels = '';
if ( $show_labels ) {
	$labels = '<span class="image-compare__label image-compare__label--before" aria-hidden="true">' . $before_lbl . '</span>'
			. '<span class="image-compare__label image-compare__label--after" aria-hidden="true">' . $after_lbl . '</span>';
}
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="image-compare__after">
		<img src="<?php echo esc_url( $after_url ); ?>" alt="<?php echo $after_alt; ?>" loading="lazy" />
	</div>
	<div class="image-compare__before">
		<img src="<?php echo esc_url( $before_url ); ?>" alt="<?php echo $before_alt; ?>" loading="lazy" />
	</div>
	<button
		type="button"
		class="image-compare__handle"
		role="slider"
		aria-valuenow="<?php echo $start; ?>"
		aria-valuemin="0"
		aria-valuemax="100"
		aria-label="<?php esc_attr_e( 'Drag to compare images', 'goodblocks' ); ?>"
	>
		<span class="image-compare__handle-knob" aria-hidden="true"></span>
	</button>
	<?php echo $labels; ?>
</div>
