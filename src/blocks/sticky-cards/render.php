<?php
/** Sticky cards preserve the document order and all native Group settings. */
defined( 'ABSPATH' ) || exit;
$offset = max( 0, min( 180, (int) ( $attributes['stickyOffset'] ?? 96 ) ) );
$gap = max( 0, min( 48, (int) ( $attributes['cardGap'] ?? 24 ) ) );
?>
<div <?php echo get_block_wrapper_attributes( array( 'id' => (string) ( $attributes['anchor'] ?? '' ), 'class' => 'gb-sticky-cards', 'style' => '--gb-card-offset:' . $offset . 'px;--gb-card-gap:' . $gap . 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
