<?php
/**
 * Media Grid Item Block Template
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

$title            = $attributes['title'] ?? 'Default Title';
$text             = $attributes['text'] ?? '';
$background_media = $attributes['backgroundMedia'] ?? [];
$overlay_opacity  = $attributes['overlayOpacity'] ?? 0.3;
$text_position    = $attributes['textPosition'] ?? 'flex-start';
$text_type        = $attributes['textType'] ?? 'titleOnTop';
$image_type       = $attributes['imageType'] ?? 'overlayOnHover';
$link             = $attributes['link'] ?? '';
$grid_column      = $attributes['gridColumn'] ?? '';
$grid_row         = $attributes['gridRow'] ?? '';
$title_size       = $attributes['titleSize'] ?? 'normal';

$has_link = ! empty( $link );
$style_vars = [
	'--overlay-opacity: ' . esc_attr( $overlay_opacity )
];

if ( ! empty( $grid_column ) ) {
	$style_vars[] = 'grid-column: ' . esc_attr( $grid_column );
}
if ( ! empty( $grid_row ) ) {
	$style_vars[] = 'grid-row: ' . esc_attr( $grid_row );
}

$inline_style = implode( '; ', $style_vars );

$content_overlay_style = 'justify-content: ' . esc_attr( $text_position );

$block_wrapper_attributes = get_block_wrapper_attributes( [
	'style'           => $inline_style,
	'data-image-type' => esc_attr( $image_type )
] );
$tag                      = $has_link ? 'a' : 'div';
$extra_attrs              = $has_link ? ' href="' . esc_url( $link ) . '"' : '';
?>

<<?php echo $tag; ?><?php echo $extra_attrs; ?> <?php echo $block_wrapper_attributes; ?>>
	<?php if ( ! empty( $background_media['url'] ) ) : ?>
		<div class="media-background">
			<?php if ( $background_media['type'] === 'video' ) : ?>
				<video src="<?php echo esc_url( $background_media['url'] ); ?>" muted loop playsinline></video>
			<?php else : ?>
				<img src="<?php echo esc_url( $background_media['url'] ); ?>" alt="">
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="content-overlay" style="<?php echo $content_overlay_style; ?>">
		<?php
		$title_order   = ( $text_type === 'titleOnTop' ) ? '1' : '2';
		$text_order    = ( $text_type === 'titleOnTop' ) ? '2' : '1';
		$title_classes = 'item-title';
		if ( $title_size === 'large' ) {
			$title_classes .= ' h2';
		}

		$display_title = $title;
		$display_text  = $text;
		?>
		<h3 class="<?php echo esc_attr( $title_classes ); ?>" style="order: <?php echo esc_attr( $title_order ); ?>">
			<?php echo wp_kses_post( $display_title ); ?>
		</h3>
		<?php if ( ! empty( $display_text ) ) : ?>
			<p class="item-text" style="order: <?php echo esc_attr( $text_order ); ?>">
				<?php echo wp_kses_post( $display_text ); ?>
			</p>
		<?php endif; ?>
	</div>
</<?php echo $tag; ?>>
