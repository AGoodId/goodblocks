<?php
/**
 * Section Header Block — Server-side render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks content (unused).
 * @var WP_Block $block      Block instance.
 * @package GoodBlocks
 */

$kicker          = isset( $attributes['kicker'] ) ? (string) $attributes['kicker'] : '';
$title           = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
$lead            = isset( $attributes['lead'] ) ? (string) $attributes['lead'] : '';
$alignment       = isset( $attributes['alignment'] ) && $attributes['alignment'] === 'center' ? 'center' : 'left';
$number_position = isset( $attributes['numberPosition'] ) ? $attributes['numberPosition'] : 'none';
if ( ! in_array( $number_position, [ 'before', 'after', 'none' ], true ) ) {
	$number_position = 'none';
}
$theme = isset( $attributes['theme'] ) ? $attributes['theme'] : 'light';
if ( ! in_array( $theme, [ 'light', 'dark', 'accent' ], true ) ) {
	$theme = 'light';
}

if ( $title === '' && $kicker === '' && $lead === '' ) {
	return;
}

$show_kicker_before = ( $kicker !== '' && $number_position === 'before' );
$show_kicker_after  = ( $kicker !== '' && $number_position === 'after' );
$has_kicker         = $show_kicker_before || $show_kicker_after;

$classes = [
	'section-header',
	'section-header--' . $theme,
	'is-aligned-' . $alignment,
];
if ( $has_kicker ) {
	$classes[] = 'has-kicker';
}
if ( $show_kicker_after ) {
	$classes[] = 'has-kicker--after';
}

$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => implode( ' ', $classes ),
] );
?>
<section <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="section-header__inner">
		<?php if ( $show_kicker_before ) : ?>
			<span class="section-header__kicker"><?php echo wp_kses_post( $kicker ); ?></span>
		<?php endif; ?>

		<?php if ( $title !== '' ) : ?>
			<h2 class="section-header__title"><?php echo wp_kses_post( $title ); ?></h2>
		<?php endif; ?>

		<?php if ( $show_kicker_after ) : ?>
			<span class="section-header__kicker section-header__kicker--after"><?php echo wp_kses_post( $kicker ); ?></span>
		<?php endif; ?>

		<?php if ( $lead !== '' ) : ?>
			<p class="section-header__lead"><?php echo wp_kses_post( $lead ); ?></p>
		<?php endif; ?>
	</div>
</section>
