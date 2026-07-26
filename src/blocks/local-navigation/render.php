<?php
/**
 * Local Navigation block render template.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo goodblocks_render_local_navigation( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
