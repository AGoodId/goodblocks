<?php
/**
 * AGoodApp Media Picker — server-side render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block HTML (unused).
 * @var WP_Block $block      Block instance.
 */

$attachment_id = isset( $attributes['attachmentId'] ) ? absint( $attributes['attachmentId'] ) : 0;
$media_type    = isset( $attributes['mediaType'] ) ? sanitize_key( $attributes['mediaType'] ) : 'image';
$caption       = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';

if ( ! $attachment_id ) {
	return;
}

$wrapper_attrs = get_block_wrapper_attributes( [ 'class' => 'agoodapp-media-picker' ] );

if ( 'video' === $media_type ) {
	$src = wp_get_attachment_url( $attachment_id );
	if ( ! $src ) {
		return;
	}
	?>
	<figure <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<video src="<?php echo esc_url( $src ); ?>" controls preload="metadata"></video>
		<?php if ( $caption ) : ?>
			<figcaption><?php echo esc_html( $caption ); ?></figcaption>
		<?php endif; ?>
	</figure>
	<?php
} else {
	$img = wp_get_attachment_image( $attachment_id, 'full' );
	if ( ! $img ) {
		return;
	}
	?>
	<figure <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $caption ) : ?>
			<figcaption><?php echo esc_html( $caption ); ?></figcaption>
		<?php endif; ?>
	</figure>
	<?php
}
