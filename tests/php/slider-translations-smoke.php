<?php
/** Bounded registration contract; no WordPress bootstrap or database. */
define( 'ABSPATH', __DIR__ );
$actions = array();
$translations = array();
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['actions'][] = array( $hook, $callback, $priority );
}
function wp_set_script_translations( $handle, $domain, $path ) {
	$GLOBALS['translations'][] = array( $handle, $domain, $path );
	return true;
}
require dirname( __DIR__, 2 ) . '/inc/helpers.php';
if ( ! in_array( array( 'init', 'goodblocks_register_slider_translations', 20 ), $actions, true ) ) {
	throw new RuntimeException( 'Translation registration must follow normal block registration.' );
}
goodblocks_register_slider_translations();
$expected = array( 'goodblocks-slider-view-script', 'goodblocks-slider-editor-script', 'goodblocks-slide-editor-script' );
if ( count( $translations ) !== count( $expected ) ) {
	throw new RuntimeException( 'Unexpected script translation scope.' );
}
$catalogs = array();
foreach ( $translations as $index => $row ) {
	if ( $row[0] !== $expected[$index] || $row[1] !== 'goodblocks' ) {
		throw new RuntimeException( 'Incorrect native handle/domain.' );
	}
	$path = $row[2] . '/goodblocks-sv_SE-' . $row[0] . '.json';
	$catalogs[] = file_get_contents( $path );
	$catalog = json_decode( end( $catalogs ), true, 512, JSON_THROW_ON_ERROR );
	if ( $catalog['locale_data']['messages']['']['lang'] !== 'sv_SE' || $catalog['locale_data']['messages']['Play slideshow'] !== array( 'Starta bildspel' ) ) {
		throw new RuntimeException( 'Invalid bundled Swedish catalog.' );
	}
}
if ( count( array_unique( $catalogs ) ) !== 1 ) {
	throw new RuntimeException( 'Editor/frontend catalogs must remain in sync.' );
}
echo "PASS: three native slider handles, registration priority, Swedish catalogs and identical messages; no WP/DB bootstrap.\n";
