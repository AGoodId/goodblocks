<?php
/** A chapter uses native, editable inner blocks for its narrative. */
defined( 'ABSPATH' ) || exit;
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'gb-story-step' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
 <div class="gb-story-step__visual">
 <?php if ( ! empty( $attributes['mediaUrl'] ) ) : ?>
  <img src="<?php echo esc_url( $attributes['mediaUrl'] ); ?>" alt="<?php echo esc_attr( $attributes['mediaAlt'] ?? '' ); ?>" loading="lazy" decoding="async" />
 <?php else : ?>
  <span class="gb-story-step__orb" aria-hidden="true"></span><span class="gb-story-step__number" aria-hidden="true"><?php echo esc_html( $attributes['label'] ?? '01' ); ?></span>
 <?php endif; ?>
 </div>
 <div class="gb-story-step__copy"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
</article>
