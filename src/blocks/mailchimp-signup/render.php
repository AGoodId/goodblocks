<?php
$title            = isset( $attributes['title'] ) ? $attributes['title'] : __( 'Subscribe to our newsletter', 'goodblocks' );
$text             = isset( $attributes['text'] ) ? $attributes['text'] : __( 'Enter your email address to receive our newsletters.', 'goodblocks' );
$mailchimp_action = ! empty( $attributes['listLink'] ) ? $attributes['listLink'] : '';
?>

<div <?php echo get_block_wrapper_attributes(); ?>>
	<div class="content-wrapper">
		<?php if ( ! empty( $title ) ) : ?>
			<h2><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<?php if ( ! empty( $text ) ) : ?>
			<p><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>
		<form action="<?php echo esc_url( $mailchimp_action ); ?>" method="post"
			name="mc-embedded-subscribe-form" class="validate form-search" novalidate>
			<input type="email" name="EMAIL" placeholder="<?php esc_attr_e( 'Email', 'goodblocks' ); ?>" required
				autocomplete="on" />
			<div style="position: absolute; left: -5000px;" aria-hidden="true">
				<input type="text" name="b_honeypot" tabindex="-1" value="">
			</div>
			<input type="submit" value="<?php esc_html_e( 'Subscribe', 'goodblocks' ); ?>" name="subscribe"
				class="btn btn-primary">
			</input>
		</form>
	</div>
</div>
