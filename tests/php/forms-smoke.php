<?php

declare( strict_types=1 );

/** Smoke tests for GoodBlocks Form schema normalization. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

function add_action(): void {}
function __( string $text ): string { return $text; }
function sanitize_key( $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }

function goodblocks_forms_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

require_once dirname( __DIR__, 2 ) . '/inc/forms.php';

$fields = goodblocks_form_normalize_fields(
	[
		[ 'name' => 'email', 'label' => '<b>Email</b>', 'type' => 'email', 'required' => true ],
		[ 'name' => 'email', 'label' => 'Duplicate', 'type' => 'text' ],
		[ 'name' => 'bad field', 'label' => 'Bad', 'type' => 'text' ],
		[ 'name' => 'message', 'label' => 'Message', 'type' => 'textarea' ],
		[ 'name' => 'invalid_type', 'label' => 'Invalid', 'type' => 'file' ],
	]
);

goodblocks_forms_assert_same(
	[
		[ 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true ],
		[ 'name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => false ],
	],
	$fields,
	'Only unique, supported form fields should be normalized.'
);

fwrite( STDOUT, "Form smoke tests passed.\n" );
