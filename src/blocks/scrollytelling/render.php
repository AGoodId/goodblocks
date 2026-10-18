<?php
/** Scrollytelling container: content remains available without JavaScript. */
defined( 'ABSPATH' ) || exit;
$side = in_array( $attributes['visualSide'] ?? '', array( 'left', 'right' ), true ) ? $attributes['visualSide'] : 'left';
$pace = 'compact' === ( $attributes['pace'] ?? '' ) ? 'compact' : 'relaxed';
$offset = max( 0, min( 180, (int) ( $attributes['stickyOffset'] ?? 96 ) ) );
?>
<div <?php echo get_block_wrapper_attributes( array( 'id' => (string) ( $attributes['anchor'] ?? '' ), 'class' => 'gb-scrollytelling gb-scrollytelling--' . $side . ' gb-scrollytelling--' . $pace, 'style' => '--gb-story-offset:' . $offset . 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
