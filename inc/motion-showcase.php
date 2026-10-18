<?php
/** Editable motion specimens shared with AGoodSite's live block library. */
defined( 'ABSPATH' ) || exit;

function goodblocks_motion_copy( string $number, string $heading, string $text ): string {
	return '<!-- wp:paragraph --><p>' . esc_html( $number ) . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html( $heading ) . '</h3><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
}

function goodblocks_motion_examples( string $name ): array {
	if ( 'goodblocks/story-step' === $name ) {
		$copy = goodblocks_motion_copy( '01', 'A chapter of your own', 'Use ordinary WordPress blocks for the story and choose an image in the inspector.' );
		return array(
			array( 'label' => 'Chapter · abstract visual', 'attrs' => array( 'label' => '01' ), 'innerHTML' => $copy ),
			array( 'label' => 'Chapter · image visual', 'attrs' => array( 'label' => '02', 'mediaUrl' => GOODBLOCKS_URI . 'assets/block-library/scrollytelling.svg', 'mediaAlt' => 'Schematic illustration of a story with a visual and chapters' ), 'innerHTML' => $copy ),
		);
	}
	$sets = array(
		array( 'Discover the possibility', 'Shape the idea', 'Make it real' ),
		array( 'A different perspective', 'Room for exploration', 'Built to last' ),
		array( 'Start with a question', 'Find the connection', 'Move forward together' ),
	);
	$examples = array();
	foreach ( $sets as $variant => $titles ) {
		$inner = '';
		foreach ( $titles as $index => $title ) {
			$label = sprintf( '%02d', $index + 1 );
			$copy = goodblocks_motion_copy( $label, $title, 'A clear idea deserves room to breathe. Bring your own images, tell the story in your own words, and let each chapter build on the last.' );
			if ( 'goodblocks/scrollytelling' === $name ) {
				$inner .= '<!-- wp:goodblocks/story-step ' . wp_json_encode( array( 'label' => $label ) ) . ' -->' . $copy . '<!-- /wp:goodblocks/story-step -->';
			} else {
				$inner .= '<!-- wp:group --><div class="wp-block-group">' . $copy . '</div><!-- /wp:group -->';
			}
		}
		$attrs = 'goodblocks/scrollytelling' === $name
			? array( 'visualSide' => 1 === $variant ? 'right' : 'left', 'pace' => 2 === $variant ? 'compact' : 'relaxed', 'stickyOffset' => 64 )
			: array( 'stickyOffset' => 64, 'cardGap' => array( 24, 0, 40 )[ $variant ] );
		$labels = 'goodblocks/scrollytelling' === $name ? array( 'Process · visual on the left', 'Case study · visual on the right', 'Editorial chapters · compact rhythm' ) : array( 'Services · stepped stack', 'Case studies · flush stack', 'Process · generous stack' );
		$examples[] = array( 'label' => $labels[ $variant ], 'description' => 'Editable native content. Scroll inside the preview or open it full size. Compare desktop and mobile.', 'attrs' => $attrs, 'innerHTML' => $inner );
	}
	return $examples;
}

add_filter( 'agoodsite_fse_site_guide_catalog', static function ( $catalog ) {
	foreach ( $catalog as &$entry ) {
		if ( in_array( $entry['name'], array( 'goodblocks/scrollytelling', 'goodblocks/sticky-cards', 'goodblocks/story-step' ), true ) ) {
			$entry['category'] = 'Motion & storytelling';
			if ( 'goodblocks/scrollytelling' === $entry['name'] ) { $entry['preview_height'] = 760; }
			$entry['thumbnail_url'] = GOODBLOCKS_URI . 'assets/block-library/' . ( 'goodblocks/story-step' === $entry['name'] ? 'scrollytelling' : substr( $entry['name'], strlen( 'goodblocks/' ) ) ) . '.svg';
			$entry['examples'] = goodblocks_motion_examples( $entry['name'] );
		}
	}
	unset( $entry );
	return $catalog;
} );

add_filter( 'agoodsite_fse_showcase_blocks', static function ( $sections ) {
	foreach ( array( 'scrollytelling', 'sticky-cards' ) as $slug ) {
		$sections[] = array( 'slug' => 'goodblocks/' . $slug, 'help_key' => $slug, 'live' => true, 'configs' => goodblocks_motion_examples( 'goodblocks/' . $slug ) );
	}
	return $sections;
} );
