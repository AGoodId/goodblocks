<?php
/**
 * Story Card Block — Server-side render.
 *
 * NEW PATTERN i kodbasen: dynamic block + InnerBlocks. $content innehåller
 * pre-renderade inner blocks från Gutenberg och echo:as inom <details>-body.
 *
 * Etapp 1: default-layout. Andra layouts (reverse, split-left, split-right,
 * bg-full) tillkommer i Etapp 2 — DOM-strukturen är dock redan komplett.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks-rendered HTML.
 * @var WP_Block $block      Block instance.
 * @package GoodBlocks
 */

// ─── Defensive enum-validering ──────────────────────────────────────────────

$layout = isset( $attributes['layout'] ) ? (string) $attributes['layout'] : 'default';
if ( ! in_array( $layout, [ 'default', 'reverse', 'split-left', 'split-right', 'bg-full' ], true ) ) {
	$layout = 'default';
}

$theme = isset( $attributes['theme'] ) ? (string) $attributes['theme'] : 'light';
if ( ! in_array( $theme, [ 'light', 'dark', 'accent' ], true ) ) {
	$theme = 'light';
}

$media_type = isset( $attributes['mediaType'] ) ? (string) $attributes['mediaType'] : 'image';
if ( ! in_array( $media_type, [ 'image', 'video' ], true ) ) {
	$media_type = 'image';
}

$action_target = isset( $attributes['actionTarget'] ) ? (string) $attributes['actionTarget'] : '_self';
if ( ! in_array( $action_target, [ '_self', '_blank' ], true ) ) {
	$action_target = '_self';
}

// ─── Text-attribut ──────────────────────────────────────────────────────────

$kicker  = isset( $attributes['kicker'] )  ? (string) $attributes['kicker']  : '';
$title   = isset( $attributes['title'] )   ? (string) $attributes['title']   : '';
$excerpt = isset( $attributes['excerpt'] ) ? (string) $attributes['excerpt'] : '';

// ─── Media ──────────────────────────────────────────────────────────────────

$media_url = isset( $attributes['mediaUrl'] ) ? (string) $attributes['mediaUrl'] : '';
$media_alt = isset( $attributes['mediaAlt'] ) ? (string) $attributes['mediaAlt'] : '';
$has_media = $media_url !== '';

// ─── Action ─────────────────────────────────────────────────────────────────

$action_url   = isset( $attributes['actionUrl'] )   ? (string) $attributes['actionUrl']   : '';
$action_label = isset( $attributes['actionLabel'] ) ? (string) $attributes['actionLabel'] : '';
$has_action   = $action_url !== '' && $action_label !== '';

// ─── Labels ─────────────────────────────────────────────────────────────────

$labels = isset( $attributes['labels'] ) ? (array) $attributes['labels'] : [];
$labels = array_values( array_filter(
	array_map(
		static fn( $l ) => trim( (string) $l ),
		$labels
	),
	static fn( $l ) => $l !== ''
) );

// ─── Disclosure ─────────────────────────────────────────────────────────────

$summary_label_attr = isset( $attributes['summaryLabel'] ) ? trim( (string) $attributes['summaryLabel'] ) : '';
$summary_label      = $summary_label_attr !== '' ? $summary_label_attr : __( 'Read more', 'goodblocks' );
$open_by_default    = ! empty( $attributes['openByDefault'] );

// ─── Minimum-content guard ──────────────────────────────────────────────────
// Om hela blocket saknar både text-attribut OCH inner-content, returnera tomt
// så att tomma block inte producerar synlig HTML.

$has_content    = trim( (string) $content ) !== '';
$has_any_text   = $title !== '' || $kicker !== '' || $excerpt !== '';
$has_anything   = $has_any_text || $has_action || ! empty( $labels ) || $has_media || $has_content;

if ( ! $has_anything ) {
	return;
}

// ─── Wrapper-attribut ───────────────────────────────────────────────────────

$classes = [
	'story-card',
	'story-card--' . $layout,
	'story-card--' . $theme,
];

// get_block_wrapper_attributes() inkluderar automatiskt id="..." om
// supports.anchor är satt och redaktör angivit ankare.
$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => implode( ' ', $classes ),
] );
?>
<article <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $has_media && $layout === 'bg-full' ) : ?>
		<div class="story-card__bg" aria-hidden="true">
			<?php if ( $media_type === 'video' ) : ?>
				<video class="story-card__bg-media" src="<?php echo esc_url( $media_url ); ?>" autoplay muted loop playsinline></video>
			<?php else : ?>
				<img class="story-card__bg-media" src="<?php echo esc_url( $media_url ); ?>" alt="" loading="lazy" />
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="story-card__inner">

		<div class="story-card__text">

			<?php if ( $kicker !== '' || $title !== '' || $excerpt !== '' ) : ?>
				<header class="story-card__header">
					<?php if ( $kicker !== '' ) : ?>
						<span class="story-card__kicker"><?php echo wp_kses_post( $kicker ); ?></span>
					<?php endif; ?>
					<?php if ( $title !== '' ) : ?>
						<h3 class="story-card__title"><?php echo wp_kses_post( $title ); ?></h3>
					<?php endif; ?>
					<?php if ( $excerpt !== '' ) : ?>
						<p class="story-card__excerpt"><?php echo wp_kses_post( $excerpt ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<?php if ( ! empty( $labels ) ) : ?>
				<ul class="story-card__labels">
					<?php foreach ( $labels as $label ) : ?>
						<li class="story-card__label"><?php echo esc_html( $label ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $has_action ) : ?>
				<div class="story-card__actions">
					<a class="story-card__action"
					   href="<?php echo esc_url( $action_url ); ?>"
					   target="<?php echo esc_attr( $action_target ); ?>"<?php
					   echo $action_target === '_blank' ? ' rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html( $action_label ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( $has_content ) : ?>
				<details class="story-card__disclosure"<?php echo $open_by_default ? ' open' : ''; ?>>
					<summary class="story-card__summary">
						<span class="story-card__summary-label"><?php echo esc_html( $summary_label ); ?></span>
					</summary>
					<div class="story-card__body">
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</details>
			<?php endif; ?>

		</div>

		<?php if ( $has_media && $layout !== 'bg-full' ) : ?>
			<figure class="story-card__media">
				<?php if ( $media_type === 'video' ) : ?>
					<video class="story-card__media-element" src="<?php echo esc_url( $media_url ); ?>"
						<?php echo $media_alt !== '' ? ' aria-label="' . esc_attr( $media_alt ) . '"' : ''; ?>
						autoplay muted loop playsinline></video>
				<?php else : ?>
					<img class="story-card__media-element" src="<?php echo esc_url( $media_url ); ?>"
						alt="<?php echo esc_attr( $media_alt ); ?>"
						loading="lazy" />
				<?php endif; ?>
			</figure>
		<?php endif; ?>

	</div>
</article>
