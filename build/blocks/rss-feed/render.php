<?php
/**
 * RSS Feed — server-side render.
 *
 * @var array $attributes Block attributes.
 *
 * @package GoodBlocks
 */

if ( ! function_exists( 'goodblocks_rss_get_items' ) ) {
	return;
}

$source_ids      = isset( $attributes['sourceIds'] ) && is_array( $attributes['sourceIds'] )
	? array_map( 'sanitize_key', $attributes['sourceIds'] )
	: [];
$posts_to_show   = max( 1, min( 24, absint( $attributes['postsToShow'] ?? 8 ) ) );
$layout          = isset( $attributes['layout'] ) && 'list' === $attributes['layout'] ? 'list' : 'grid';
$columns         = max( 1, min( 4, absint( $attributes['columns'] ?? 3 ) ) );
$gap             = max( 0, min( 64, absint( $attributes['gap'] ?? 24 ) ) );
$aspect_ratio    = sanitize_text_field( $attributes['aspectRatio'] ?? '16/9' );
$show_image      = ! isset( $attributes['showImage'] ) || (bool) $attributes['showImage'];
$show_excerpt    = ! isset( $attributes['showExcerpt'] ) || (bool) $attributes['showExcerpt'];
$show_date       = ! isset( $attributes['showDate'] ) || (bool) $attributes['showDate'];
$show_source     = ! isset( $attributes['showSource'] ) || (bool) $attributes['showSource'];
$open_in_new_tab = ! isset( $attributes['openInNewTab'] ) || (bool) $attributes['openInNewTab'];
$fallback_text   = isset( $attributes['fallbackText'] )
	? sanitize_text_field( $attributes['fallbackText'] )
	: __( 'No news is available right now.', 'goodblocks' );
$allowed_ratios  = [ '16/9', '4/3', '1/1' ];

if ( ! in_array( $aspect_ratio, $allowed_ratios, true ) ) {
	$aspect_ratio = '16/9';
}

$items = goodblocks_rss_get_items( $source_ids, $posts_to_show );
$style = sprintf(
	'--goodblocks-rss-columns:%d;--goodblocks-rss-tablet-columns:%d;--goodblocks-rss-gap:%dpx;--goodblocks-rss-aspect-ratio:%s;',
	$columns,
	min( 2, $columns ),
	$gap,
	str_replace( '/', ' / ', $aspect_ratio )
);
$class = 'goodblocks-rss-feed is-layout-' . $layout;
$target = $open_in_new_tab ? '_blank' : '_self';
$rel    = $open_in_new_tab ? 'noopener noreferrer external' : 'external';
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => $class, 'style' => $style ] ); ?>>
	<?php if ( $items ) : ?>
		<div class="goodblocks-rss-feed__items">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$title      = sanitize_text_field( $item['title'] ?? '' );
				$url        = goodblocks_rss_sanitize_http_url( $item['url'] ?? '' );
				$image_url  = goodblocks_rss_sanitize_http_url( $item['image_url'] ?? '' );
				$excerpt    = sanitize_text_field( $item['excerpt'] ?? '' );
				$source     = sanitize_text_field( $item['source_name'] ?? '' );
				$timestamp  = absint( $item['timestamp'] ?? 0 );
				if ( '' === $url || '' === $title ) {
					continue;
				}
				?>
				<article class="goodblocks-rss-feed__item<?php echo $show_image && $image_url ? ' has-image' : ' no-image'; ?>">
					<?php if ( $show_image && $image_url ) : ?>
						<a
							class="goodblocks-rss-feed__image-link"
							href="<?php echo esc_url( $url ); ?>"
							target="<?php echo esc_attr( $target ); ?>"
							rel="<?php echo esc_attr( $rel ); ?>"
							tabindex="-1"
							aria-hidden="true"
						>
							<img
								class="goodblocks-rss-feed__image"
								src="<?php echo esc_url( $image_url ); ?>"
								alt=""
								loading="lazy"
								decoding="async"
								referrerpolicy="no-referrer"
							/>
						</a>
					<?php endif; ?>
					<div class="goodblocks-rss-feed__content">
						<?php if ( ( $show_source && $source ) || ( $show_date && $timestamp ) ) : ?>
							<div class="goodblocks-rss-feed__meta">
								<?php if ( $show_source && $source ) : ?>
									<span class="goodblocks-rss-feed__source"><?php echo esc_html( $source ); ?></span>
								<?php endif; ?>
								<?php if ( $show_date && $timestamp ) : ?>
									<time datetime="<?php echo esc_attr( gmdate( 'c', $timestamp ) ); ?>">
										<?php echo esc_html( wp_date( get_option( 'date_format' ), $timestamp ) ); ?>
									</time>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<h3 class="goodblocks-rss-feed__title">
							<a href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>" rel="<?php echo esc_attr( $rel ); ?>">
								<?php echo esc_html( $title ); ?>
							</a>
						</h3>
						<?php if ( $show_excerpt && $excerpt ) : ?>
							<p class="goodblocks-rss-feed__excerpt"><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php elseif ( $fallback_text ) : ?>
		<p class="goodblocks-rss-feed__fallback"><?php echo esc_html( $fallback_text ); ?></p>
	<?php endif; ?>
</div>
