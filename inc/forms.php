<?php
/**
 * Secure, self-hosted form handling for the GoodBlocks Form block.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GOODBLOCKS_FORM_RETENTION_HOOK = 'goodblocks_form_purge_submissions';

add_action( 'init', 'goodblocks_register_form_submissions' );
add_action( 'admin_menu', 'goodblocks_form_add_settings_page' );
add_action( 'admin_init', 'goodblocks_form_register_settings' );
add_action( 'init', 'goodblocks_form_schedule_retention' );
add_action( GOODBLOCKS_FORM_RETENTION_HOOK, 'goodblocks_form_purge_expired_submissions' );

/** Register the private submission store used by GoodBlocks forms. */
function goodblocks_register_form_submissions(): void {
	register_post_type(
		'goodblocks_form_submission',
		[
			'labels' => [
				'name'          => __( 'Form submissions', 'goodblocks' ),
				'singular_name' => __( 'Form submission', 'goodblocks' ),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'tools.php',
			'supports'            => [ 'title' ],
			'capability_type'     => 'post',
			'capabilities'        => goodblocks_form_submission_capabilities(),
			'map_meta_cap'        => true,
			'exclude_from_search' => true,
			'show_in_rest'        => false,
		]
	);
}

/** Restrict stored submissions, which may contain PII, to site administrators. */
function goodblocks_form_submission_capabilities(): array {
	return [
		'edit_post'              => 'manage_options',
		'read_post'              => 'manage_options',
		'delete_post'            => 'manage_options',
		'edit_posts'             => 'manage_options',
		'edit_others_posts'      => 'manage_options',
		'delete_posts'           => 'manage_options',
		'delete_private_posts'   => 'manage_options',
		'delete_published_posts' => 'manage_options',
		'publish_posts'          => 'manage_options',
		'read_private_posts'     => 'manage_options',
		'create_posts'           => 'do_not_allow',
	];
}

/** Register form security and retention settings. */
function goodblocks_form_register_settings(): void {
	register_setting( 'goodblocks_form_settings', 'goodblocks_form_turnstile_sitekey', [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
	register_setting( 'goodblocks_form_settings', 'goodblocks_form_turnstile_secret', [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
	register_setting( 'goodblocks_form_settings', 'goodblocks_form_recipient_domains', [ 'sanitize_callback' => 'goodblocks_form_sanitize_recipient_domains', 'default' => '' ] );
	register_setting( 'goodblocks_form_settings', 'goodblocks_form_retention_days', [ 'sanitize_callback' => 'goodblocks_form_sanitize_retention_days', 'default' => 90 ] );

	add_settings_section( 'goodblocks_form_security', __( 'Form security and retention', 'goodblocks' ), 'goodblocks_form_settings_intro', 'goodblocks-forms' );
	add_settings_field( 'goodblocks_form_turnstile_sitekey', __( 'Turnstile site key', 'goodblocks' ), 'goodblocks_form_sitekey_field', 'goodblocks-forms', 'goodblocks_form_security' );
	add_settings_field( 'goodblocks_form_turnstile_secret', __( 'Turnstile secret key', 'goodblocks' ), 'goodblocks_form_secret_field', 'goodblocks-forms', 'goodblocks_form_security' );
	add_settings_field( 'goodblocks_form_recipient_domains', __( 'Allowed recipient domains', 'goodblocks' ), 'goodblocks_form_recipient_domains_field', 'goodblocks-forms', 'goodblocks_form_security' );
	add_settings_field( 'goodblocks_form_retention_days', __( 'Submission retention (days)', 'goodblocks' ), 'goodblocks_form_retention_field', 'goodblocks-forms', 'goodblocks_form_security' );
}

function goodblocks_form_sanitize_retention_days( $value ): int {
	return max( 1, min( 3650, absint( $value ) ) );
}

function goodblocks_form_sanitize_recipient_domains( $value ): string {
	$domains = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $value ) ) ) );
	$domains = array_filter( $domains, static fn( $domain ): bool => (bool) preg_match( '/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain ) );
	return implode( ',', array_unique( $domains ) );
}

function goodblocks_form_add_settings_page(): void {
	add_options_page( __( 'GoodBlocks Forms', 'goodblocks' ), __( 'GoodBlocks Forms', 'goodblocks' ), 'manage_options', 'goodblocks-forms', 'goodblocks_form_render_settings_page' );
}

function goodblocks_form_settings_intro(): void {
	echo '<p>' . esc_html__( 'Forms require Cloudflare Turnstile. The secret key stays in WordPress and is never sent to visitors. Saved submissions may contain personal data and are permanently deleted after the configured retention period.', 'goodblocks' ) . '</p>';
}

function goodblocks_form_sitekey_field(): void {
	printf( '<input type="text" name="goodblocks_form_turnstile_sitekey" value="%s" class="regular-text" autocomplete="off" />', esc_attr( get_option( 'goodblocks_form_turnstile_sitekey', '' ) ) );
}

function goodblocks_form_secret_field(): void {
	printf( '<input type="password" name="goodblocks_form_turnstile_secret" value="%s" class="regular-text" autocomplete="new-password" />', esc_attr( get_option( 'goodblocks_form_turnstile_secret', '' ) ) );
}

function goodblocks_form_recipient_domains_field(): void {
	$value = get_option( 'goodblocks_form_recipient_domains', '' );
	if ( '' === $value ) {
		$value = substr( strrchr( (string) get_option( 'admin_email' ), '@' ) ?: '', 1 );
	}
	printf( '<input type="text" name="goodblocks_form_recipient_domains" value="%s" class="regular-text" /><p class="description">%s</p>', esc_attr( $value ), esc_html__( 'Comma-separated domains. Forms cannot send to addresses outside this list.', 'goodblocks' ) );
}

function goodblocks_form_retention_field(): void {
	printf( '<input type="number" min="1" max="3650" name="goodblocks_form_retention_days" value="%d" class="small-text" />', absint( get_option( 'goodblocks_form_retention_days', 90 ) ) );
}

function goodblocks_form_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap"><h1><?php esc_html_e( 'GoodBlocks Forms', 'goodblocks' ); ?></h1><form method="post" action="options.php"><?php settings_fields( 'goodblocks_form_settings' ); do_settings_sections( 'goodblocks-forms' ); submit_button(); ?></form></div>
	<?php
}

/** Whether this site has the keys required to receive public submissions. */
function goodblocks_form_turnstile_is_configured(): bool {
	return '' !== (string) get_option( 'goodblocks_form_turnstile_sitekey', '' ) && '' !== (string) get_option( 'goodblocks_form_turnstile_secret', '' );
}

/** Return whether the configured recipient belongs to an administrator-approved domain. */
function goodblocks_form_recipient_is_allowed( string $recipient ): bool {
	$domain  = strtolower( substr( strrchr( $recipient, '@' ) ?: '', 1 ) );
	$allowed = goodblocks_form_sanitize_recipient_domains( get_option( 'goodblocks_form_recipient_domains', '' ) );
	if ( '' === $allowed ) {
		$allowed = strtolower( substr( strrchr( (string) get_option( 'admin_email' ), '@' ) ?: '', 1 ) );
	}

	return '' !== $domain && in_array( $domain, explode( ',', $allowed ), true );
}

/** Schedule daily deletion of expired submissions. */
function goodblocks_form_schedule_retention(): void {
	if ( ! wp_next_scheduled( GOODBLOCKS_FORM_RETENTION_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', GOODBLOCKS_FORM_RETENTION_HOOK );
	}
}

/** Normalize form fields once, for both rendering and server-side validation. */
function goodblocks_form_normalize_fields( $fields ): array {
	$normalized = [];
	$names      = [];
	$types      = [ 'text', 'email', 'tel', 'textarea' ];

	foreach ( array_slice( (array) $fields, 0, 20 ) as $field ) {
		if ( ! is_array( $field ) ) {
			continue;
		}

		$raw_name = isset( $field['name'] ) && is_scalar( $field['name'] ) ? (string) $field['name'] : '';
		$name     = sanitize_key( $raw_name );
		$type = sanitize_key( $field['type'] ?? 'text' );
		if ( $raw_name !== $name || ! preg_match( '/^[a-z][a-z0-9_]{0,63}$/', $name ) || ! in_array( $type, $types, true ) || isset( $names[ $name ] ) ) {
			continue;
		}

		$names[ $name ] = true;
		$normalized[]   = [
			'name'     => $name,
			'label'    => sanitize_text_field( $field['label'] ?? $name ),
			'type'     => $type,
			'required' => ! empty( $field['required'] ),
		];
	}

	return $normalized;
}

/** Find a form block's server-side configuration in a published post. */
function goodblocks_form_get_config( int $post_id, string $form_id ): array {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== get_post_status( $post ) ) {
		return [];
	}

	$attributes = goodblocks_form_find_attributes( parse_blocks( $post->post_content ), $form_id );
	$fields     = goodblocks_form_normalize_fields( $attributes['fields'] ?? [] );
	if ( empty( $attributes ) || empty( $fields ) ) {
		return [];
	}

	$recipient = sanitize_email( $attributes['recipientEmail'] ?? '' );
	if ( ! $recipient ) {
		$recipient = sanitize_email( get_option( 'admin_email' ) );
	}
	if ( ! $recipient || ! goodblocks_form_recipient_is_allowed( $recipient ) ) {
		return [];
	}

	return [
		'id'                => $form_id,
		'title'             => sanitize_text_field( $attributes['title'] ?? __( 'Form', 'goodblocks' ) ),
		'recipient'         => $recipient,
		'subject'           => sanitize_text_field( $attributes['emailSubject'] ?? __( 'New form submission', 'goodblocks' ) ),
		'store_submissions' => ! empty( $attributes['storeSubmissions'] ),
		'fields'            => $fields,
	];
}

/** Search parsed blocks recursively for the requested form ID. */
function goodblocks_form_find_attributes( array $blocks, string $form_id ): array {
	$matches = goodblocks_form_find_matching_attributes( $blocks, $form_id );
	return 1 === count( $matches ) ? reset( $matches ) : [];
}

/** Return all matching form blocks so duplicate IDs cannot cross-send submissions. */
function goodblocks_form_find_matching_attributes( array $blocks, string $form_id ): array {
	$matches = [];
	foreach ( $blocks as $block ) {
		$attributes = $block['attrs'] ?? [];
		if ( 'goodblocks/form' === ( $block['blockName'] ?? '' ) && $form_id === sanitize_key( $attributes['formId'] ?? '' ) ) {
			$matches[] = is_array( $attributes ) ? $attributes : [];
		}

		$matches = array_merge( $matches, goodblocks_form_find_matching_attributes( $block['innerBlocks'] ?? [], $form_id ) );
	}

	return $matches;
}

/** Count a submission attempt before calling external verification services. */
function goodblocks_form_register_attempt( string $form_id ): bool {
	$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$lock_key  = 'goodblocks_form_lock_' . md5( $ip );
	$keys      = [
		'goodblocks_form_attempt_' . md5( $form_id . '|' . $ip ) => 10,
		'goodblocks_form_attempt_' . md5( 'global|' . $ip ) => 30,
	];

	// add_option() has a unique database key, making this lock atomic across PHP workers.
	if ( ! add_option( $lock_key, time(), '', false ) ) {
		$locked_at = absint( get_option( $lock_key, 0 ) );
		if ( $locked_at && ( time() - $locked_at ) > 30 ) {
			delete_option( $lock_key );
		}
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			return false;
		}
	}

	try {
		foreach ( $keys as $key => $limit ) {
			$count = (int) get_transient( $key );
			if ( $count >= $limit ) {
				return false;
			}
			set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
		}
	} finally {
		delete_option( $lock_key );
	}

	return true;
}

/** Validate one Turnstile token on the server. */
function goodblocks_form_verify_turnstile( string $token ): bool {
	$secret = (string) get_option( 'goodblocks_form_turnstile_secret', '' );
	if ( '' === $secret || '' === $token || strlen( $token ) > 2048 ) {
		return false;
	}

	$response = wp_safe_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		[
			'timeout' => 5,
			'body'    => [
				'secret'   => $secret,
				'response' => $token,
				'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			],
		]
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$body          = json_decode( wp_remote_retrieve_body( $response ), true );
	$expected_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

	return is_array( $body )
		&& ! empty( $body['success'] )
		&& 'goodblocks_form' === ( $body['action'] ?? '' )
		&& $expected_host === strtolower( (string) ( $body['hostname'] ?? '' ) );
}

/** Send a browser back to the source page with a short-lived, unguessable notice. */
function goodblocks_form_redirect( string $status, string $form_id ): void {
	$notice = wp_generate_password( 32, false, false );
	set_transient( 'goodblocks_form_notice_' . $notice, [ 'status' => $status, 'form_id' => $form_id ], 10 * MINUTE_IN_SECONDS );
	$url = add_query_arg( 'goodblocks_form_notice', rawurlencode( $notice ), remove_query_arg( 'goodblocks_form_notice', wp_validate_redirect( wp_get_referer(), home_url( '/' ) ) ) );
	wp_safe_redirect( $url );
	exit;
}

/** Consume a single-use status notice for a rendered form. */
function goodblocks_form_get_notice( string $notice, string $form_id ): string {
	$notice = preg_replace( '/[^A-Za-z0-9]/', '', $notice );
	$data   = get_transient( 'goodblocks_form_notice_' . $notice );
	if ( ! is_array( $data ) || $form_id !== ( $data['form_id'] ?? '' ) ) {
		return '';
	}

	delete_transient( 'goodblocks_form_notice_' . $notice );
	return sanitize_key( $data['status'] ?? '' );
}

/** Handle a public GoodBlocks form submission. */
function goodblocks_handle_form_submission(): void {
	$form_id = isset( $_POST['goodblocks_form_id'] ) ? sanitize_key( wp_unslash( $_POST['goodblocks_form_id'] ) ) : '';
	$post_id = isset( $_POST['goodblocks_form_post_id'] ) ? absint( $_POST['goodblocks_form_post_id'] ) : 0;
	$nonce   = isset( $_POST['goodblocks_form_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['goodblocks_form_nonce'] ) ) : '';
	$config  = goodblocks_form_get_config( $post_id, $form_id );

	if ( ! $form_id || empty( $config ) || ! wp_verify_nonce( $nonce, 'goodblocks_form_' . $form_id . '_' . $post_id ) || ! goodblocks_form_turnstile_is_configured() ) {
		goodblocks_form_redirect( 'error', $form_id );
	}
	if ( ! goodblocks_form_register_attempt( $form_id ) ) {
		goodblocks_form_redirect( 'rate_limited', $form_id );
	}

	if ( ! empty( $_POST['goodblocks_website'] ) ) {
		goodblocks_form_redirect( 'success', $form_id );
	}

	$token = isset( $_POST['cf-turnstile-response'] ) && is_scalar( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
	if ( ! goodblocks_form_verify_turnstile( $token ) ) {
		goodblocks_form_redirect( 'verification_error', $form_id );
	}

	$submitted = isset( $_POST['goodblocks_fields'] ) && is_array( $_POST['goodblocks_fields'] ) ? wp_unslash( $_POST['goodblocks_fields'] ) : [];
	$values    = [];
	$errors    = false;
	foreach ( $config['fields'] as $field ) {
		$name      = $field['name'];
		$raw_value = isset( $submitted[ $name ] ) && is_scalar( $submitted[ $name ] ) ? (string) $submitted[ $name ] : '';
		$value     = 'email' === $field['type'] ? sanitize_email( $raw_value ) : sanitize_textarea_field( $raw_value );
		$value     = 'email' === $field['type'] ? substr( $value, 0, 254 ) : wp_html_excerpt( $value, 10000, '' );
		if ( ( $field['required'] && '' === $value ) || ( 'email' === $field['type'] && '' !== $raw_value && ! is_email( $raw_value ) ) ) {
			$errors = true;
		}
		$values[ $field['label'] ] = $value;
	}
	if ( $errors ) {
		goodblocks_form_redirect( 'validation_error', $form_id );
	}

	$submission_id = 0;
	if ( $config['store_submissions'] ) {
		$submission_id = wp_insert_post( [ 'post_type' => 'goodblocks_form_submission', 'post_status' => 'private', 'post_title' => sprintf( '%s — %s', $config['title'], current_time( 'Y-m-d H:i' ) ) ], true );
		if ( is_wp_error( $submission_id ) ) {
			goodblocks_form_redirect( 'error', $form_id );
		}
		update_post_meta( $submission_id, '_goodblocks_form_id', $form_id );
		update_post_meta( $submission_id, '_goodblocks_form_values', $values );
	}

	$lines = [];
	foreach ( $values as $label => $value ) {
		$lines[] = $label . ': ' . $value;
	}
	$sent = wp_mail( $config['recipient'], $config['subject'], implode( "\n\n", $lines ), [ 'Content-Type: text/plain; charset=UTF-8' ] );
	if ( $submission_id ) {
		update_post_meta( $submission_id, '_goodblocks_form_email_sent', $sent ? '1' : '0' );
	}
	goodblocks_form_redirect( $sent ? 'success' : 'error', $form_id );
}
add_action( 'admin_post_nopriv_goodblocks_submit_form', 'goodblocks_handle_form_submission' );
add_action( 'admin_post_goodblocks_submit_form', 'goodblocks_handle_form_submission' );

/** Delete stored PII after the configured retention period. */
function goodblocks_form_purge_expired_submissions(): void {
	$ids = get_posts( [ 'post_type' => 'goodblocks_form_submission', 'post_status' => 'private', 'date_query' => [ [ 'before' => '-' . goodblocks_form_sanitize_retention_days( get_option( 'goodblocks_form_retention_days', 90 ) ) . ' days' ] ], 'fields' => 'ids', 'posts_per_page' => -1 ] );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
}

/** Render stored values directly below a submission in wp-admin. */
function goodblocks_form_submission_details( WP_Post $post ): void {
	$values = get_post_meta( $post->ID, '_goodblocks_form_values', true );
	if ( ! is_array( $values ) ) {
		return;
	}
	echo '<table class="widefat striped"><tbody>';
	foreach ( $values as $label => $value ) {
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
}
add_action( 'edit_form_after_title', function ( $post ) {
	if ( $post instanceof WP_Post && 'goodblocks_form_submission' === $post->post_type ) {
		goodblocks_form_submission_details( $post );
	}
} );
