<?php
/**
 * GoodBlocks — Style guide showcase registration.
 *
 * Registers GoodBlocks blocks in the agoodsite-fse style guide
 * via the `agoodsite_fse_showcase_blocks` filter.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'agoodsite_fse_showcase_blocks', function ( $blocks ) {

	$blocks[] = [
		'slug'     => 'goodblocks/card-feature',
		'help_key' => 'card-feature',
		'live'     => false,
		'note'     => 'Feature Card kräver GoodBlocks-pluginet. Infoga via + → Feature Card.',
	];

	$blocks[] = [
		'slug'     => 'goodblocks/masonry-query',
		'help_key' => 'masonry-query',
		'live'     => false,
		'note'     => 'Masonry Query kräver bildurval och kategorier i Inspectorn.',
	];

	$blocks[] = [
		'slug'     => 'goodblocks/image-compare',
		'help_key' => 'image-compare',
		'live'     => false,
		'note'     => 'Bildjämförelse kräver två bilder valda i Inspectorn.',
	];

	$blocks[] = [
		'slug'     => 'goodblocks/search-autocomplete',
		'help_key' => 'search-autocomplete',
		'live'     => true,
		'configs'  => [
			[
				'label' => 'Expanderbar (tryck ⌘K eller klicka på sökikonen)',
				'attrs' => [ 'expandable' => true, 'blockId' => 'sg-search-1' ],
			],
			[
				'label' => 'Alltid synlig (inbäddad i sida)',
				'attrs' => [ 'expandable' => false, 'placeholder' => 'Prova att söka…', 'blockId' => 'sg-search-2' ],
			],
		],
	];

	$blocks[] = [
		'slug'     => 'goodblocks/testimonials',
		'help_key' => 'testimonials',
		'live'     => true,
		'configs'  => [
			[
				'label' => 'Fade-animation (default)',
				'attrs' => [
					'animation'    => 'fade',
					'autoplay'     => true,
					'autoplayDelay' => 5000,
					'showArrows'   => true,
					'showDots'     => true,
					'items'        => [
						[
							'quote'  => 'En fantastisk upplevelse från start till mål.',
							'author' => 'Anna Svensson',
							'role'   => 'VD, Acme AB',
						],
						[
							'quote'  => 'Professionellt, snabbt och över förväntan.',
							'author' => 'Erik Lindqvist',
							'role'   => 'Marknadschef',
						],
					],
				],
			],
		],
	];

	$blocks[] = [
		'slug'     => 'goodblocks/post-grid',
		'help_key' => 'post-grid',
		'live'     => false,
		'note'     => 'Post Grid kräver inlägg med inläggsbilder. Konfigurera layout och posttyp i Inspectorn.',
	];

	$blocks[] = [
		'slug'     => 'goodblocks/event-list',
		'help_key' => 'event-list',
		'live'     => false,
		'note'     => 'Event List kräver registrerade händelser av typen goodblocks_event.',
	];

	$blocks[] = [
		'slug'     => 'goodblocks/countdown',
		'help_key' => 'countdown',
		'live'     => true,
		'configs'  => [
			[
				'label' => 'Nedräkning',
				'attrs' => [],
			],
		],
	];

	$blocks[] = [
		'slug'     => 'goodblocks/mailchimp-signup',
		'help_key' => 'mailchimp-signup',
		'live'     => false,
		'note'     => 'Mailchimp Signup kräver API-nyckel och list-ID konfigurerade i plugin-inställningarna.',
	];

	return $blocks;
} );
