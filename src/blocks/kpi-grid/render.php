<?php
/**
 * KPI Grid Block — Server-side render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks content (unused).
 * @var WP_Block $block      Block instance.
 * @package GoodBlocks
 */

$items = isset( $attributes['items'] ) ? (array) $attributes['items'] : [];

// Cap at 6 tiles (defensive — editor enforces this but block.json could be edited manually).
if ( count( $items ) > 6 ) {
	$items = array_slice( $items, 0, 6 );
}

// Filter out tiles with no value AND no label (empty tile is meaningless).
$items = array_values( array_filter(
	$items,
	static function ( $item ) {
		$value = isset( $item['value'] ) ? (string) $item['value'] : '';
		$label = isset( $item['label'] ) ? (string) $item['label'] : '';
		return $value !== '' || $label !== '';
	}
) );

if ( empty( $items ) ) {
	return;
}

$columns = isset( $attributes['columns'] ) ? (string) $attributes['columns'] : 'auto';
if ( ! in_array( $columns, [ 'auto', '2', '3', '4', '5', '6' ], true ) ) {
	$columns = 'auto';
}
$columns_resolved = $columns === 'auto' ? min( count( $items ), 6 ) : (int) $columns;

$theme = isset( $attributes['theme'] ) ? (string) $attributes['theme'] : 'light';
if ( ! in_array( $theme, [ 'light', 'dark', 'accent' ], true ) ) {
	$theme = 'light';
}

$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => 'kpi-grid kpi-grid--' . $theme . ' kpi-grid--cols-' . $columns_resolved,
] );
?>
<section <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="kpi-grid__inner">
		<?php foreach ( $items as $item ) :
			$id     = isset( $item['id'] )     ? (string) $item['id']     : '';
			$value  = isset( $item['value'] )  ? (string) $item['value']  : '';
			$label  = isset( $item['label'] )  ? (string) $item['label']  : '';
			$prefix = isset( $item['prefix'] ) ? (string) $item['prefix'] : '';
			$suffix = isset( $item['suffix'] ) ? (string) $item['suffix'] : '';
			?>
			<div class="kpi-grid__tile"<?php echo $id ? ' data-id="' . esc_attr( $id ) . '"' : ''; ?>>
				<?php if ( $value !== '' || $prefix !== '' || $suffix !== '' ) : ?>
					<div class="kpi-grid__value">
						<?php if ( $prefix !== '' ) : ?>
							<span class="kpi-grid__prefix"><?php echo esc_html( $prefix ); ?></span>
						<?php endif; ?>
						<?php if ( $value !== '' ) : ?>
							<span class="kpi-grid__number"><?php echo esc_html( $value ); ?></span>
						<?php endif; ?>
						<?php if ( $suffix !== '' ) : ?>
							<span class="kpi-grid__suffix"><?php echo esc_html( $suffix ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $label !== '' ) : ?>
					<div class="kpi-grid__label"><?php echo esc_html( $label ); ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
