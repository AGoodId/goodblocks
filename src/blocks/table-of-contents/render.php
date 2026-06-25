<?php
/**
 * Table of Contents — server-side render.
 *
 * @var array $attributes Block attributes.
 *
 * @package GoodBlocks
 */

$title             = isset( $attributes['title'] ) ? (string) $attributes['title'] : __( 'Innehåll', 'goodblocks' );
$layout            = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'card';
$floating_position = isset( $attributes['floatingPosition'] ) ? sanitize_key( $attributes['floatingPosition'] ) : 'right';
$scroll_offset     = isset( $attributes['scrollOffset'] ) ? max( 0, min( 240, absint( $attributes['scrollOffset'] ) ) ) : 96;
$min_headings      = isset( $attributes['minHeadings'] ) ? max( 1, min( 8, absint( $attributes['minHeadings'] ) ) ) : 2;
$scope_selector    = isset( $attributes['scopeSelector'] ) ? sanitize_text_field( $attributes['scopeSelector'] ) : '';
$heading_selector  = isset( $attributes['headingSelector'] ) ? sanitize_text_field( $attributes['headingSelector'] ) : '';
$exclude_selector  = isset( $attributes['excludeSelector'] ) ? sanitize_text_field( $attributes['excludeSelector'] ) : '';

if ( ! in_array( $layout, [ 'card', 'minimal', 'floating' ], true ) ) {
	$layout = 'card';
}

if ( ! in_array( $floating_position, [ 'left', 'right' ], true ) ) {
	$floating_position = 'right';
}

$levels = isset( $attributes['includeLevels'] ) && is_array( $attributes['includeLevels'] ) ? array_map( 'absint', $attributes['includeLevels'] ) : [];
$levels = array_values( array_filter( $levels, static function ( int $level ): bool {
	return $level >= 2 && $level <= 6;
} ) );

if ( empty( $levels ) ) {
	foreach ( [ 2, 3, 4, 5, 6 ] as $level ) {
		if ( ! empty( $attributes[ 'includeH' . $level ] ) ) {
			$levels[] = $level;
		}
	}
}

if ( empty( $levels ) ) {
	$levels = [ 2, 3 ];
}

$classes = [
	'goodblocks-toc',
	'goodblocks-toc--' . $layout,
	'is-floating-' . $floating_position,
];

if ( ! empty( $attributes['sticky'] ) ) {
	$classes[] = 'is-sticky';
}
if ( ! empty( $attributes['showNumbers'] ) ) {
	$classes[] = 'has-numbers';
}
if ( ! empty( $attributes['collapsible'] ) ) {
	$classes[] = 'is-collapsible';
}
if ( ! empty( $attributes['startCollapsed'] ) ) {
	$classes[] = 'is-collapsed';
}

$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => implode( ' ', $classes ),
	'style' => '--goodblocks-toc-offset: ' . $scroll_offset . 'px;',
] );
?>

<nav
	<?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	aria-label="<?php esc_attr_e( 'Table of contents', 'goodblocks' ); ?>"
	data-goodblocks-toc
	data-levels="<?php echo esc_attr( implode( ',', $levels ) ); ?>"
	data-min-headings="<?php echo esc_attr( (string) $min_headings ); ?>"
	data-scroll-offset="<?php echo esc_attr( (string) $scroll_offset ); ?>"
	data-scope-selector="<?php echo esc_attr( $scope_selector ); ?>"
	data-heading-selector="<?php echo esc_attr( $heading_selector ); ?>"
	data-exclude-selector="<?php echo esc_attr( $exclude_selector ); ?>"
	data-show-active="<?php echo ! empty( $attributes['showActive'] ) ? 'true' : 'false'; ?>"
	data-show-numbers="<?php echo ! empty( $attributes['showNumbers'] ) ? 'true' : 'false'; ?>"
	data-collapsible="<?php echo ! empty( $attributes['collapsible'] ) ? 'true' : 'false'; ?>"
	data-start-collapsed="<?php echo ! empty( $attributes['startCollapsed'] ) ? 'true' : 'false'; ?>"
	data-smooth-scroll="<?php echo ! empty( $attributes['smoothScroll'] ) ? 'true' : 'false'; ?>"
>
	<div class="goodblocks-toc__header">
		<?php if ( '' !== $title ) : ?>
			<p class="goodblocks-toc__title"><?php echo wp_kses_post( $title ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['collapsible'] ) ) : ?>
			<button class="goodblocks-toc__toggle" type="button" aria-expanded="<?php echo empty( $attributes['startCollapsed'] ) ? 'true' : 'false'; ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle table of contents', 'goodblocks' ); ?></span>
			</button>
		<?php endif; ?>
	</div>
	<ol class="goodblocks-toc__list" data-goodblocks-toc-list></ol>
</nav>
