<?php
/**
 * Instagram Feed — server-side render.
 *
 * @var array $attributes Block attributes.
 *
 * @package GoodBlocks
 */

if ( ! function_exists( 'goodblocks_instagram_get_cache' ) ) {
	return;
}

$posts_to_show     = isset( $attributes['postsToShow'] ) ? max( 1, min( 24, absint( $attributes['postsToShow'] ) ) ) : 8;
$columns           = isset( $attributes['columns'] ) ? max( 1, min( 6, absint( $attributes['columns'] ) ) ) : 4;
$gap               = isset( $attributes['gap'] ) ? max( 0, min( 48, absint( $attributes['gap'] ) ) ) : 16;
$aspect_ratio      = isset( $attributes['aspectRatio'] ) ? sanitize_text_field( $attributes['aspectRatio'] ) : 'auto';
$allowed_ratios    = [ '1/1', '4/5', '4/3', 'auto' ];
$show_caption      = ! empty( $attributes['showCaption'] );
$show_profile_link = ! empty( $attributes['showProfileLink'] );
$profile_link_text = isset( $attributes['profileLinkText'] ) ? $attributes['profileLinkText'] : __( 'Följ oss på Instagram', 'goodblocks' );
$fallback_text     = isset( $attributes['fallbackText'] ) ? $attributes['fallbackText'] : __( 'Instagramflödet är inte tillgängligt just nu.', 'goodblocks' );
$profile_url       = get_option( 'goodblocks_instagram_profile_url', '' );

if ( ! in_array( $aspect_ratio, $allowed_ratios, true ) ) {
	$aspect_ratio = 'auto';
}

$cache = goodblocks_instagram_get_cache();
$items = array_slice( $cache['items'], 0, $posts_to_show );
$style = sprintf(
	'--instagram-feed-columns: %d; --instagram-feed-tablet-columns: %d; --instagram-feed-mobile-columns: %d; --instagram-feed-gap: %dpx; --instagram-feed-aspect-ratio: %s;',
	$columns,
	min( 3, $columns ),
	min( 2, $columns ),
	$gap,
	'auto' === $aspect_ratio ? 'auto' : str_replace( '/', ' / ', $aspect_ratio )
);
$class = 'goodblocks-instagram-feed' . ( 'auto' === $aspect_ratio ? ' is-aspect-auto' : '' );
?>

<div <?php echo get_block_wrapper_attributes( [
	'class' => $class,
	'style' => $style,
] ); ?>>
	<?php if ( ! empty( $items ) ) : ?>
		<div class="goodblocks-instagram-feed__grid">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$caption = isset( $item['caption'] ) ? $item['caption'] : '';
				$alt     = $caption ? wp_trim_words( $caption, 18, '...' ) : __( 'Instagram post', 'goodblocks' );
				?>
				<a class="goodblocks-instagram-feed__item" href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank" rel="noopener noreferrer">
					<img
						class="goodblocks-instagram-feed__media"
						src="<?php echo esc_url( $item['image_url'] ); ?>"
						alt="<?php echo esc_attr( $alt ); ?>"
						data-no-lazy="1"
						loading="lazy"
						decoding="async"
					/>
					<?php if ( ! empty( $item['media_type'] ) && 'image' !== $item['media_type'] ) : ?>
						<span class="goodblocks-instagram-feed__badge" aria-label="<?php esc_attr_e( 'Video or carousel', 'goodblocks' ); ?>"><?php esc_html_e( 'Video', 'goodblocks' ); ?></span>
					<?php endif; ?>
					<?php if ( $show_caption && $caption ) : ?>
						<p class="goodblocks-instagram-feed__caption"><?php echo esc_html( wp_trim_words( $caption, 22, '...' ) ); ?></p>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php if ( $show_profile_link && $profile_url && $profile_link_text ) : ?>
			<div class="goodblocks-instagram-feed__footer">
				<a class="goodblocks-instagram-feed__profile-link" href="<?php echo esc_url( $profile_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $profile_link_text ); ?>
				</a>
			</div>
		<?php endif; ?>
	<?php elseif ( $fallback_text ) : ?>
		<p class="goodblocks-instagram-feed__fallback"><?php echo esc_html( $fallback_text ); ?></p>
	<?php endif; ?>
</div>
