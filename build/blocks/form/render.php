<?php
$form_id = ! empty( $attributes['formId'] ) ? sanitize_key( $attributes['formId'] ) : '';
$post_id = get_the_ID();
$config  = goodblocks_form_get_config( $post_id, $form_id );
$fields  = $config['fields'] ?? [];
$title   = $config['title'] ?? '';
$notice  = isset( $_GET['goodblocks_form_notice'] ) ? goodblocks_form_get_notice( sanitize_text_field( wp_unslash( $_GET['goodblocks_form_notice'] ) ), $form_id ) : '';

if ( ! $form_id || ! $post_id || empty( $config ) || ! goodblocks_form_turnstile_is_configured() ) {
	return;
}

wp_enqueue_script( 'goodblocks-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, false );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php if ( ! empty( $title ) ) : ?><h2><?php echo esc_html( $title ); ?></h2><?php endif; ?>
	<?php if ( ! empty( $attributes['description'] ) ) : ?><p><?php echo esc_html( $attributes['description'] ); ?></p><?php endif; ?>
	<?php if ( 'success' === $notice ) : ?><p class="goodblocks-form__notice goodblocks-form__notice--success" role="status"><?php echo esc_html( $attributes['successMessage'] ?? __( 'Thank you — your message has been sent.', 'goodblocks' ) ); ?></p>
	<?php elseif ( $notice ) : ?><p class="goodblocks-form__notice goodblocks-form__notice--error" role="alert"><?php esc_html_e( 'We could not send your submission. Please check the fields and try again.', 'goodblocks' ); ?></p><?php endif; ?>
	<form class="goodblocks-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="goodblocks_submit_form">
		<input type="hidden" name="goodblocks_form_id" value="<?php echo esc_attr( $form_id ); ?>">
		<input type="hidden" name="goodblocks_form_post_id" value="<?php echo esc_attr( $post_id ); ?>">
		<input type="hidden" name="goodblocks_form_nonce" value="<?php echo esc_attr( wp_create_nonce( 'goodblocks_form_' . $form_id . '_' . $post_id ) ); ?>">
		<p class="goodblocks-form__honeypot" aria-hidden="true"><label><?php esc_html_e( 'Website', 'goodblocks' ); ?><input type="text" name="goodblocks_website" tabindex="-1" autocomplete="off"></label></p>
		<?php foreach ( $fields as $field ) : $name = $field['name']; $type = $field['type']; ?>
			<p class="goodblocks-form__field"><label for="<?php echo esc_attr( $form_id . '-' . $name ); ?>"><?php echo esc_html( $field['label'] ); ?><?php if ( $field['required'] ) : ?> <span aria-hidden="true">*</span><?php endif; ?></label><?php if ( 'textarea' === $type ) : ?><textarea id="<?php echo esc_attr( $form_id . '-' . $name ); ?>" name="goodblocks_fields[<?php echo esc_attr( $name ); ?>]" <?php echo $field['required'] ? 'required' : ''; ?>></textarea><?php else : ?><input id="<?php echo esc_attr( $form_id . '-' . $name ); ?>" type="<?php echo esc_attr( $type ); ?>" name="goodblocks_fields[<?php echo esc_attr( $name ); ?>]" <?php echo $field['required'] ? 'required' : ''; ?>><?php endif; ?></p>
		<?php endforeach; ?>
		<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( get_option( 'goodblocks_form_turnstile_sitekey', '' ) ); ?>" data-action="goodblocks_form"></div>
		<button type="submit" class="wp-element-button"><?php echo esc_html( $attributes['submitLabel'] ?? __( 'Send', 'goodblocks' ) ); ?></button>
	</form>
</div>
