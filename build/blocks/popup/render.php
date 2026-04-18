<?php
/**
 * Popup — server-side render.
 *
 * Always hidden in HTML; view.js shows it based on trigger + cookie.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML.
 * @var WP_Block $block      Block instance.
 *
 * @package GoodBlocks
 */

// Never render in admin/editor context.
if ( is_admin() ) {
	return;
}

$trigger        = in_array( $attributes['trigger'] ?? 'time', [ 'time', 'scroll', 'exit' ], true )
	? $attributes['trigger']
	: 'time';
$delay          = absint( $attributes['delay'] ?? 3 );
$scroll_percent = absint( $attributes['scrollPercent'] ?? 50 );
$cookie_name    = sanitize_key( $attributes['cookieName'] ?? 'gb_popup_1' );
$cookie_days    = absint( $attributes['cookieDays'] ?? 7 );
?>
<div
	<?php echo get_block_wrapper_attributes( [ 'style' => 'display:none;' ] ); ?>
	data-trigger="<?php echo esc_attr( $trigger ); ?>"
	data-delay="<?php echo esc_attr( $delay ); ?>"
	data-scroll-percent="<?php echo esc_attr( $scroll_percent ); ?>"
	data-cookie-name="<?php echo esc_attr( $cookie_name ); ?>"
	data-cookie-days="<?php echo esc_attr( $cookie_days ); ?>"
	role="dialog"
	aria-modal="true"
>
	<div class="popup-backdrop" aria-hidden="true"></div>
	<div class="popup-modal">
		<button class="popup-close" aria-label="<?php esc_attr_e( 'Close', 'goodblocks' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
		<div class="popup-content">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
</div>
