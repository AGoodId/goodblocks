<?php
/**
 * Matterport embed helpers and shortcode.
 *
 * @package GoodBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a privacy-conscious Matterport tour embed.
 *
 * @param array $attributes Block/shortcode attributes.
 * @return string
 */
function goodblocks_render_matterport( array $attributes ): string {
	$model_id = sanitize_text_field( (string) ( $attributes['modelId'] ?? $attributes['model'] ?? '' ) );
	if ( ! preg_match( '/^[A-Za-z0-9_-]+$/', $model_id ) ) {
		return '';
	}

	$title       = sanitize_text_field( (string) ( $attributes['title'] ?? __( 'Virtual tour', 'goodblocks' ) ) );
	$description = sanitize_text_field( (string) ( $attributes['description'] ?? '' ) );
	$eyebrow     = sanitize_text_field( (string) ( $attributes['eyebrow'] ?? '' ) );
	$height      = max( 320, min( 1000, absint( $attributes['height'] ?? 640 ) ) );
	$src         = add_query_arg( 'm', $model_id, 'https://my.matterport.com/show/' );
	$label_id    = wp_unique_id( 'goodblocks-matterport-title-' );
	$extra_classes = array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', (string) ( $attributes['className'] ?? '' ) ) ) );
	$classes       = trim( 'goodblocks-matterport ' . implode( ' ', $extra_classes ) );

	ob_start();
	?>
	<section class="<?php echo esc_attr( $classes ); ?>" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
		<?php if ( $eyebrow || $title || $description ) : ?>
			<header class="goodblocks-matterport__header">
				<?php if ( $eyebrow ) : ?><p class="goodblocks-matterport__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<?php if ( $title ) : ?><h2 id="<?php echo esc_attr( $label_id ); ?>" class="goodblocks-matterport__title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
				<?php if ( $description ) : ?><p class="goodblocks-matterport__description"><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</header>
		<?php endif; ?>
		<div class="goodblocks-matterport__frame" style="--goodblocks-matterport-height:<?php echo esc_attr( $height ); ?>px">
			<iframe src="<?php echo esc_url( $src ); ?>" title="<?php echo esc_attr( $title ?: __( 'Matterport virtual tour', 'goodblocks' ) ); ?>" loading="lazy" allow="fullscreen; xr-spatial-tracking" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
		</div>
		<p class="goodblocks-matterport__fallback"><a href="<?php echo esc_url( $src ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open the virtual tour in a new window', 'goodblocks' ); ?></a></p>
	</section>
	<?php
	return (string) ob_get_clean();
}

/** Shortcode compatibility: [goodblocks_matterport model="..."] */
function goodblocks_matterport_shortcode( $attributes ): string {
	return goodblocks_render_matterport( shortcode_atts( [
		'model'       => '',
		'title'       => __( 'Virtual tour', 'goodblocks' ),
		'description' => '',
		'eyebrow'     => '',
		'height'      => 640,
	], (array) $attributes, 'goodblocks_matterport' ) );
}
add_shortcode( 'goodblocks_matterport', 'goodblocks_matterport_shortcode' );
