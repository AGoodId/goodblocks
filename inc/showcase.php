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

	// ── Sprint A foundation blocks (260429) ──────────────────────────────────────

	$blocks[] = [
		'slug'     => 'goodblocks/hero',
		'help_key' => 'hero',
		'live'     => false,
		'note'     => 'Hero kräver bakgrundsmedia (bild eller video) och fungerar bäst i full sidkontext.',
	];

	$blocks[] = [
		'slug'     => 'goodblocks/section-header',
		'help_key' => 'section-header',
		'live'     => true,
		'configs'  => [
			[
				'label' => 'Light, vänsterställd med kicker',
				'attrs' => [
					'kicker'         => '01',
					'title'          => 'A sustainable strategy',
					'lead'           => 'Built into how we work.',
					'alignment'      => 'left',
					'numberPosition' => 'before',
					'theme'          => 'light',
				],
			],
			[
				'label' => 'Dark, centrerad utan kicker',
				'attrs' => [
					'title'          => 'Clear targets. Quarterly accountability.',
					'alignment'      => 'center',
					'numberPosition' => 'none',
					'theme'          => 'dark',
				],
			],
			[
				'label' => 'Accent (för kapitelintron)',
				'attrs' => [
					'title'          => 'Materiality Assessment & Stakeholder Engagement',
					'alignment'      => 'left',
					'numberPosition' => 'none',
					'theme'          => 'accent',
				],
			],
		],
	];

	$blocks[] = [
		'slug'     => 'goodblocks/kpi-grid',
		'help_key' => 'kpi-grid',
		'live'     => true,
		'configs'  => [
			[
				'label' => '3 tiles med prefix/suffix-mix',
				'attrs' => [
					'columns' => 'auto',
					'theme'   => 'light',
					'items'   => [
						[ 'id' => 'sg-kpi-1', 'prefix' => '−', 'value' => '71', 'suffix' => '%', 'label' => 'SCOPE 1 & 2 VS 2022' ],
						[ 'id' => 'sg-kpi-2', 'value' => '2025', 'label' => 'TARGET REACHED' ],
						[ 'id' => 'sg-kpi-3', 'value' => '5', 'suffix' => 'yrs', 'label' => 'AHEAD OF SCHEDULE' ],
					],
				],
			],
			[
				'label' => '6 tiles på accent-bakgrund',
				'attrs' => [
					'columns' => 'auto',
					'theme'   => 'accent',
					'items'   => [
						[ 'id' => 'sg-kpi-4', 'value' => '2',     'label' => 'LOST TIME INCIDENTS' ],
						[ 'id' => 'sg-kpi-5', 'value' => '5',     'label' => 'TOTAL INJURIES' ],
						[ 'id' => 'sg-kpi-6', 'value' => '↑',     'label' => 'NEAR MISSES' ],
						[ 'id' => 'sg-kpi-7', 'value' => '30',    'suffix' => '%', 'label' => 'HVO SHARE' ],
						[ 'id' => 'sg-kpi-8', 'prefix' => '−',    'value' => '39', 'suffix' => '%', 'label' => 'SCOPE 1 VS 2022' ],
						[ 'id' => 'sg-kpi-9', 'value' => '1 444', 'suffix' => 't', 'label' => 'SCOPE 3 CO₂E' ],
					],
				],
			],
		],
	];

	return $blocks;
} );
