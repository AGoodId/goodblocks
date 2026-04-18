<?php
/**
 * Search Autocomplete Block - Server-side Render
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$block_id       = $attributes['blockId'] ?? 'search-' . uniqid();
$placeholder    = $attributes['placeholder'] ?? __( 'Sök...', 'goodblocks' );
$min_chars      = $attributes['minChars'] ?? 2;
$max_results    = $attributes['maxResults'] ?? 5;
$post_types     = $attributes['postTypes'] ?? 'post,page,portfolio';
$show_thumbnail = $attributes['showThumbnail'] ?? true;
$show_excerpt   = $attributes['showExcerpt'] ?? true;
$show_type      = $attributes['showType'] ?? true;
$expandable     = $attributes['expandable'] ?? true;
$button_style   = $attributes['buttonStyle'] ?? 'icon';

$raw_links      = $attributes['suggestedLinks'] ?? [];
$safe_links     = array_values( array_filter(
	array_slice(
		array_map( function ( $item ) {
			return [
				'label' => sanitize_text_field( $item['label'] ?? '' ),
				'url'   => esc_url_raw( $item['url'] ?? '' ),
			];
		}, (array) $raw_links ),
		0,
		8
	),
	fn( $i ) => $i['label'] !== '' && $i['url'] !== ''
) );

$wrapper_attributes = get_block_wrapper_attributes( [
	'class'                  => 'search-autocomplete' . ( $expandable ? ' search-autocomplete--expandable' : '' ),
	'id'                     => esc_attr( $block_id ),
	'data-min-chars'         => esc_attr( $min_chars ),
	'data-max-results'       => esc_attr( $max_results ),
	'data-post-types'        => esc_attr( $post_types ),
	'data-show-thumbnail'    => $show_thumbnail ? 'true' : 'false',
	'data-show-excerpt'      => $show_excerpt ? 'true' : 'false',
	'data-show-type'         => $show_type ? 'true' : 'false',
	'data-expandable'        => $expandable ? 'true' : 'false',
	'data-api-url'           => esc_url( rest_url( apply_filters( 'goodblocks_search_rest_namespace', 'goodblocks/v1' ) . '/search' ) ),
	'data-suggestions-url'   => esc_url( rest_url( apply_filters( 'goodblocks_search_rest_namespace', 'goodblocks/v1' ) . '/search/suggestions' ) ),
	'data-suggested-links'   => wp_json_encode( $safe_links ),
] );

$trigger_icon = '<svg class="search-autocomplete__trigger-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>';
$field_icon   = '<svg class="search-autocomplete__field-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>';

$close_icon = '<svg class="search-autocomplete__close-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>';

$spinner_icon = '<svg class="search-autocomplete__spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="32"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>';
?>

<div <?php echo $wrapper_attributes; ?>>
	<?php if ( $expandable ) : ?>
		<button
			type="button"
			class="search-autocomplete__trigger"
			aria-label="<?php esc_attr_e( 'Öppna sök', 'goodblocks' ); ?>"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $block_id ); ?>-form"
		>
			<?php echo $trigger_icon; ?>
			<?php if ( $button_style === 'text' || $button_style === 'both' ) : ?>
				<span class="search-autocomplete__trigger-text"><?php esc_html_e( 'Sök', 'goodblocks' ); ?></span>
			<?php endif; ?>
		</button>
	<?php endif; ?>

	<form
		id="<?php echo esc_attr( $block_id ); ?>-form"
		class="search-autocomplete__form"
		role="search"
		action="<?php echo esc_url( home_url( '/' ) ); ?>"
		method="get"
		<?php echo $expandable ? 'aria-hidden="true"' : ''; ?>
	>
		<div class="search-autocomplete__field">
			<?php echo $field_icon; ?>

			<input
				type="search"
				class="search-autocomplete__input"
				name="s"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				autocomplete="off"
				autocorrect="off"
				autocapitalize="off"
				spellcheck="false"
				aria-label="<?php echo esc_attr( $placeholder ); ?>"
				aria-autocomplete="list"
				aria-controls="<?php echo esc_attr( $block_id ); ?>-results"
				aria-expanded="false"
			/>

			<div class="search-autocomplete__icons">
				<?php echo $spinner_icon; ?>
				<button
					type="button"
					class="search-autocomplete__clear"
					aria-label="<?php esc_attr_e( 'Rensa sökning', 'goodblocks' ); ?>"
					hidden
				>
					<?php echo $close_icon; ?>
				</button>
			</div>
		</div>

		<?php if ( $expandable ) : ?>
			<button
				type="button"
				class="search-autocomplete__close"
				aria-label="<?php esc_attr_e( 'Stäng sök', 'goodblocks' ); ?>"
			>
				<?php echo $close_icon; ?>
			</button>
		<?php endif; ?>

		<div
			id="<?php echo esc_attr( $block_id ); ?>-results"
			class="search-autocomplete__results"
			role="listbox"
			aria-label="<?php esc_attr_e( 'Sökresultat', 'goodblocks' ); ?>"
			aria-live="polite"
			hidden
		></div>
	</form>

	<div class="search-autocomplete__backdrop" aria-hidden="true"></div>
</div>
