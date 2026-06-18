<?php
/**
 * Instagram feed settings, API refresh, and cache helpers.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GOODBLOCKS_INSTAGRAM_CACHE_OPTION = 'goodblocks_instagram_feed_cache';
const GOODBLOCKS_INSTAGRAM_ERROR_OPTION = 'goodblocks_instagram_feed_error';
const GOODBLOCKS_INSTAGRAM_CRON_HOOK    = 'goodblocks_instagram_refresh_feed';
const GOODBLOCKS_INSTAGRAM_TOKEN_EXPIRES_AT_OPTION    = 'goodblocks_instagram_token_expires_at';
const GOODBLOCKS_INSTAGRAM_TOKEN_REFRESHED_AT_OPTION  = 'goodblocks_instagram_token_refreshed_at';
const GOODBLOCKS_INSTAGRAM_TOKEN_LAST_ATTEMPT_OPTION  = 'goodblocks_instagram_token_last_refresh_attempt';
const GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_ERROR_OPTION = 'goodblocks_instagram_token_refresh_error';
const GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_MARGIN       = 30 * DAY_IN_SECONDS;

add_action( 'admin_menu', 'goodblocks_instagram_add_settings_page' );
add_action( 'admin_init', 'goodblocks_instagram_register_settings' );
add_action( 'admin_post_goodblocks_instagram_refresh', 'goodblocks_instagram_handle_manual_refresh' );
add_action( GOODBLOCKS_INSTAGRAM_CRON_HOOK, 'goodblocks_instagram_refresh_feed' );
add_action( 'update_option_goodblocks_instagram_cache_minutes', 'goodblocks_instagram_reschedule_cron' );
add_filter( 'cron_schedules', 'goodblocks_instagram_add_cron_schedule' );

/**
 * Register Settings -> Instagram Feed.
 */
function goodblocks_instagram_add_settings_page(): void {
	add_options_page(
		__( 'Instagram Feed', 'goodblocks' ),
		__( 'Instagram Feed', 'goodblocks' ),
		'manage_options',
		'goodblocks-instagram-feed',
		'goodblocks_instagram_render_settings_page'
	);
}

/**
 * Register Instagram connection settings.
 */
function goodblocks_instagram_register_settings(): void {
	register_setting( 'goodblocks_instagram_settings', 'goodblocks_instagram_api_base_url', [
		'sanitize_callback' => 'esc_url_raw',
		'default'           => 'https://graph.instagram.com',
	] );
	register_setting( 'goodblocks_instagram_settings', 'goodblocks_instagram_account_id', [
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );
	register_setting( 'goodblocks_instagram_settings', 'goodblocks_instagram_access_token', [
		'sanitize_callback' => 'goodblocks_instagram_sanitize_access_token',
		'default'           => '',
	] );
	register_setting( 'goodblocks_instagram_settings', 'goodblocks_instagram_profile_url', [
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	] );
	register_setting( 'goodblocks_instagram_settings', 'goodblocks_instagram_cache_minutes', [
		'sanitize_callback' => 'goodblocks_instagram_sanitize_cache_minutes',
		'default'           => 60,
	] );

	add_settings_section(
		'goodblocks_instagram_connection',
		__( 'Connection', 'goodblocks' ),
		'goodblocks_instagram_connection_intro',
		'goodblocks-instagram-feed'
	);

	add_settings_field(
		'goodblocks_instagram_api_base_url',
		__( 'API Base URL', 'goodblocks' ),
		'goodblocks_instagram_field_api_base_url',
		'goodblocks-instagram-feed',
		'goodblocks_instagram_connection'
	);
	add_settings_field(
		'goodblocks_instagram_account_id',
		__( 'Account ID', 'goodblocks' ),
		'goodblocks_instagram_field_account_id',
		'goodblocks-instagram-feed',
		'goodblocks_instagram_connection'
	);
	add_settings_field(
		'goodblocks_instagram_access_token',
		__( 'Access Token', 'goodblocks' ),
		'goodblocks_instagram_field_access_token',
		'goodblocks-instagram-feed',
		'goodblocks_instagram_connection'
	);
	add_settings_field(
		'goodblocks_instagram_profile_url',
		__( 'Profile URL', 'goodblocks' ),
		'goodblocks_instagram_field_profile_url',
		'goodblocks-instagram-feed',
		'goodblocks_instagram_connection'
	);
	add_settings_field(
		'goodblocks_instagram_cache_minutes',
		__( 'Refresh interval', 'goodblocks' ),
		'goodblocks_instagram_field_cache_minutes',
		'goodblocks-instagram-feed',
		'goodblocks_instagram_connection'
	);
}

/**
 * Clamp the cache interval.
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function goodblocks_instagram_sanitize_cache_minutes( $value ): int {
	return max( 15, min( 1440, absint( $value ) ) );
}

/**
 * Sanitize the stored access token and reset token metadata when it changes.
 *
 * @param mixed $value Raw submitted value.
 * @return string
 */
function goodblocks_instagram_sanitize_access_token( $value ): string {
	$token = sanitize_text_field( $value );
	$old   = get_option( 'goodblocks_instagram_access_token', '' );

	if ( $token !== $old ) {
		if ( '' === $token ) {
			delete_option( GOODBLOCKS_INSTAGRAM_TOKEN_EXPIRES_AT_OPTION );
		} else {
			update_option( GOODBLOCKS_INSTAGRAM_TOKEN_EXPIRES_AT_OPTION, time() + ( 60 * DAY_IN_SECONDS ), false );
		}

		delete_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESHED_AT_OPTION );
		delete_option( GOODBLOCKS_INSTAGRAM_TOKEN_LAST_ATTEMPT_OPTION );
		delete_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_ERROR_OPTION );
	}

	return $token;
}

/**
 * Add a configurable cron schedule for feed refreshes.
 *
 * @param array $schedules Registered schedules.
 * @return array
 */
function goodblocks_instagram_add_cron_schedule( array $schedules ): array {
	$minutes = goodblocks_instagram_sanitize_cache_minutes( get_option( 'goodblocks_instagram_cache_minutes', 60 ) );

	$schedules['goodblocks_instagram_interval'] = [
		'interval' => $minutes * MINUTE_IN_SECONDS,
		'display'  => sprintf(
			/* translators: %d: number of minutes */
			__( 'Every %d minutes', 'goodblocks' ),
			$minutes
		),
	];

	return $schedules;
}

/**
 * Settings intro copy.
 */
function goodblocks_instagram_connection_intro(): void {
	echo '<p>' . esc_html__( 'Connect an Instagram access token from Meta. The feed is fetched server-side and cached in WordPress so visitors never call the Instagram API directly.', 'goodblocks' ) . '</p>';
}

function goodblocks_instagram_field_api_base_url(): void {
	$value = get_option( 'goodblocks_instagram_api_base_url', 'https://graph.instagram.com' );
	printf(
		'<input type="url" name="goodblocks_instagram_api_base_url" value="%s" class="regular-text" placeholder="https://graph.instagram.com" />
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'Use https://graph.instagram.com for Instagram Login tokens, or a Graph API version URL for Facebook Login setups.', 'goodblocks' )
	);
}

function goodblocks_instagram_field_account_id(): void {
	$value = get_option( 'goodblocks_instagram_account_id', '' );
	printf(
		'<input type="text" name="goodblocks_instagram_account_id" value="%s" class="regular-text" placeholder="me" />
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'Leave empty to call /me/media. Add an Instagram Business account ID when using Facebook Login.', 'goodblocks' )
	);
}

function goodblocks_instagram_field_access_token(): void {
	$value = get_option( 'goodblocks_instagram_access_token', '' );
	printf(
		'<input type="password" name="goodblocks_instagram_access_token" value="%s" class="regular-text" autocomplete="new-password" />
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'Long-lived Instagram access token. Store production tokens only in WordPress options, never in theme code.', 'goodblocks' )
	);
}

function goodblocks_instagram_field_profile_url(): void {
	$value = get_option( 'goodblocks_instagram_profile_url', '' );
	printf(
		'<input type="url" name="goodblocks_instagram_profile_url" value="%s" class="regular-text" placeholder="https://www.instagram.com/example/" />
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'Optional profile link used by the block CTA.', 'goodblocks' )
	);
}

function goodblocks_instagram_field_cache_minutes(): void {
	$value = get_option( 'goodblocks_instagram_cache_minutes', 60 );
	printf(
		'<input type="number" min="15" max="1440" step="15" name="goodblocks_instagram_cache_minutes" value="%s" class="small-text" /> %s
		<p class="description">%s</p>',
		esc_attr( $value ),
		esc_html__( 'minutes', 'goodblocks' ),
		esc_html__( 'Cron refresh cadence. WordPress cron runs when the site receives traffic.', 'goodblocks' )
	);
}

/**
 * Render settings page.
 */
function goodblocks_instagram_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$cache = goodblocks_instagram_get_cache();
	$error = get_option( GOODBLOCKS_INSTAGRAM_ERROR_OPTION, '' );
	$token_expires_at    = absint( get_option( GOODBLOCKS_INSTAGRAM_TOKEN_EXPIRES_AT_OPTION, 0 ) );
	$token_refreshed_at  = absint( get_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESHED_AT_OPTION, 0 ) );
	$token_refresh_error = get_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_ERROR_OPTION, '' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Instagram Feed', 'goodblocks' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'goodblocks_instagram_settings' );
			do_settings_sections( 'goodblocks-instagram-feed' );
			submit_button();
			?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Cache status', 'goodblocks' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: item count, 2: last refresh date */
				esc_html__( '%1$d cached items. Last refreshed: %2$s.', 'goodblocks' ),
				absint( count( $cache['items'] ) ),
				! empty( $cache['fetched_at'] ) ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), absint( $cache['fetched_at'] ) ) ) : esc_html__( 'Never', 'goodblocks' )
			);
			?>
		</p>
		<?php if ( $error ) : ?>
			<p><strong><?php esc_html_e( 'Last error:', 'goodblocks' ); ?></strong> <?php echo esc_html( $error ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Token status', 'goodblocks' ); ?></h2>
		<p>
			<?php if ( $token_expires_at ) : ?>
				<?php
				printf(
					/* translators: %s: token expiry date */
					esc_html__( 'Token expires: %s.', 'goodblocks' ),
					esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $token_expires_at ) )
				);
				?>
			<?php else : ?>
				<?php esc_html_e( 'Token expiry is unknown. GoodBlocks will attempt to refresh it automatically.', 'goodblocks' ); ?>
			<?php endif; ?>
			<?php if ( $token_refreshed_at ) : ?>
				<br />
				<?php
				printf(
					/* translators: %s: token refresh date */
					esc_html__( 'Last token refresh: %s.', 'goodblocks' ),
					esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $token_refreshed_at ) )
				);
				?>
			<?php endif; ?>
		</p>
		<?php if ( $token_refresh_error ) : ?>
			<p><strong><?php esc_html_e( 'Last token refresh error:', 'goodblocks' ); ?></strong> <?php echo esc_html( $token_refresh_error ); ?></p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="goodblocks_instagram_refresh" />
			<?php wp_nonce_field( 'goodblocks_instagram_refresh' ); ?>
			<?php submit_button( __( 'Refresh now', 'goodblocks' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Fetch fresh Instagram media for manual refreshes.
 */
function goodblocks_instagram_handle_manual_refresh(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to refresh this feed.', 'goodblocks' ) );
	}

	check_admin_referer( 'goodblocks_instagram_refresh' );
	goodblocks_instagram_refresh_feed( true );
	wp_safe_redirect( admin_url( 'options-general.php?page=goodblocks-instagram-feed' ) );
	exit;
}

/**
 * Schedule cron refresh if it is not already registered.
 */
function goodblocks_instagram_ensure_cron(): void {
	if ( ! wp_next_scheduled( GOODBLOCKS_INSTAGRAM_CRON_HOOK ) ) {
		wp_schedule_event( time() + 300, 'goodblocks_instagram_interval', GOODBLOCKS_INSTAGRAM_CRON_HOOK );
	}
}

/**
 * Clear scheduled Instagram cron.
 */
function goodblocks_instagram_clear_cron(): void {
	$timestamp = wp_next_scheduled( GOODBLOCKS_INSTAGRAM_CRON_HOOK );

	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, GOODBLOCKS_INSTAGRAM_CRON_HOOK );
		$timestamp = wp_next_scheduled( GOODBLOCKS_INSTAGRAM_CRON_HOOK );
	}
}

/**
 * Reschedule cron after the refresh interval changes.
 */
function goodblocks_instagram_reschedule_cron(): void {
	goodblocks_instagram_clear_cron();
	goodblocks_instagram_ensure_cron();
}

/**
 * Get normalized cached feed data.
 *
 * @return array{items: array, fetched_at: int}
 */
function goodblocks_instagram_get_cache(): array {
	$cache = get_option( GOODBLOCKS_INSTAGRAM_CACHE_OPTION, [] );

	if ( ! is_array( $cache ) ) {
		return [
			'items'      => [],
			'fetched_at' => 0,
		];
	}

	return [
		'items'      => isset( $cache['items'] ) && is_array( $cache['items'] ) ? $cache['items'] : [],
		'fetched_at' => isset( $cache['fetched_at'] ) ? absint( $cache['fetched_at'] ) : 0,
	];
}

/**
 * Check whether the cache has passed the configured max age.
 *
 * @param array $cache Feed cache.
 * @return bool
 */
function goodblocks_instagram_is_cache_stale( array $cache ): bool {
	$cache_minutes = goodblocks_instagram_sanitize_cache_minutes( get_option( 'goodblocks_instagram_cache_minutes', 60 ) );

	return empty( $cache['fetched_at'] ) || ( time() - absint( $cache['fetched_at'] ) ) >= ( $cache_minutes * MINUTE_IN_SECONDS );
}

/**
 * Check whether the stored token should be refreshed.
 *
 * @return bool
 */
function goodblocks_instagram_should_refresh_token(): bool {
	$token = get_option( 'goodblocks_instagram_access_token', '' );
	if ( empty( $token ) ) {
		return false;
	}

	$expires_at = absint( get_option( GOODBLOCKS_INSTAGRAM_TOKEN_EXPIRES_AT_OPTION, 0 ) );
	if ( $expires_at ) {
		return $expires_at <= ( time() + GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_MARGIN );
	}

	$last_attempt = absint( get_option( GOODBLOCKS_INSTAGRAM_TOKEN_LAST_ATTEMPT_OPTION, 0 ) );

	return ! $last_attempt || ( time() - $last_attempt ) >= DAY_IN_SECONDS;
}

/**
 * Get the Instagram token refresh endpoint for Instagram Login tokens.
 *
 * @return string
 */
function goodblocks_instagram_get_token_refresh_endpoint(): string {
	$api_base = untrailingslashit( get_option( 'goodblocks_instagram_api_base_url', 'https://graph.instagram.com' ) );
	$host     = wp_parse_url( $api_base, PHP_URL_HOST );

	if ( 'graph.instagram.com' !== $host ) {
		return '';
	}

	return 'https://graph.instagram.com/refresh_access_token';
}

/**
 * Refresh the stored long-lived Instagram token when it is due.
 *
 * @param bool $force Refresh even when token metadata says it is fresh.
 * @return string Current token, refreshed when possible.
 */
function goodblocks_instagram_maybe_refresh_token( bool $force = false ): string {
	$token = get_option( 'goodblocks_instagram_access_token', '' );
	if ( empty( $token ) ) {
		return '';
	}

	if ( ! $force && ! goodblocks_instagram_should_refresh_token() ) {
		return $token;
	}

	$endpoint = goodblocks_instagram_get_token_refresh_endpoint();
	if ( ! $endpoint ) {
		update_option(
			GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_ERROR_OPTION,
			__( 'Automatic token refresh is only supported for graph.instagram.com Instagram Login tokens.', 'goodblocks' ),
			false
		);
		return $token;
	}

	update_option( GOODBLOCKS_INSTAGRAM_TOKEN_LAST_ATTEMPT_OPTION, time(), false );

	$url = add_query_arg(
		[
			'grant_type'   => 'ig_refresh_token',
			'access_token' => $token,
		],
		$endpoint
	);

	$response = wp_remote_get( $url, [
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		update_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_ERROR_OPTION, $response->get_error_message(), false );
		return $token;
	}

	$status = wp_remote_retrieve_response_code( $response );
	$body   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $status || ! is_array( $body ) || empty( $body['access_token'] ) ) {
		$message = isset( $body['error']['message'] ) ? sanitize_text_field( $body['error']['message'] ) : __( 'Instagram API did not return a refreshed token.', 'goodblocks' );
		update_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_ERROR_OPTION, $message, false );
		return $token;
	}

	$refreshed_token = sanitize_text_field( $body['access_token'] );
	$expires_in      = isset( $body['expires_in'] ) ? absint( $body['expires_in'] ) : 60 * DAY_IN_SECONDS;

	update_option( 'goodblocks_instagram_access_token', $refreshed_token, false );
	update_option( GOODBLOCKS_INSTAGRAM_TOKEN_EXPIRES_AT_OPTION, time() + $expires_in, false );
	update_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESHED_AT_OPTION, time(), false );
	delete_option( GOODBLOCKS_INSTAGRAM_TOKEN_REFRESH_ERROR_OPTION );

	return $refreshed_token;
}

/**
 * Refresh feed cache when stale or forced.
 *
 * @param bool $force Fetch even when cache is fresh.
 * @return array{items: array, fetched_at: int}
 */
function goodblocks_instagram_refresh_feed( bool $force = false ): array {
	$cache = goodblocks_instagram_get_cache();

	if ( ! $force && ! goodblocks_instagram_is_cache_stale( $cache ) ) {
		return $cache;
	}

	$token = goodblocks_instagram_maybe_refresh_token();
	if ( empty( $token ) ) {
		update_option( GOODBLOCKS_INSTAGRAM_ERROR_OPTION, __( 'Missing Instagram access token.', 'goodblocks' ), false );
		return $cache;
	}

	$api_base   = untrailingslashit( get_option( 'goodblocks_instagram_api_base_url', 'https://graph.instagram.com' ) );
	$account_id = trim( get_option( 'goodblocks_instagram_account_id', '' ) );
	$subject    = $account_id ? rawurlencode( $account_id ) : 'me';
	$url        = add_query_arg(
		[
			'fields'       => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username',
			'limit'        => 50,
			'access_token' => $token,
		],
		$api_base . '/' . $subject . '/media'
	);

	$response = wp_remote_get( $url, [
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		update_option( GOODBLOCKS_INSTAGRAM_ERROR_OPTION, $response->get_error_message(), false );
		return $cache;
	}

	$status = wp_remote_retrieve_response_code( $response );
	$body   = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $status || ! is_array( $body ) || empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		$message = isset( $body['error']['message'] ) ? sanitize_text_field( $body['error']['message'] ) : __( 'Instagram API returned no media.', 'goodblocks' );
		update_option( GOODBLOCKS_INSTAGRAM_ERROR_OPTION, $message, false );
		return $cache;
	}

	$items = array_values( array_filter( array_map( 'goodblocks_instagram_normalize_item', $body['data'] ) ) );
	$cache = [
		'items'      => $items,
		'fetched_at' => time(),
	];

	update_option( GOODBLOCKS_INSTAGRAM_CACHE_OPTION, $cache, false );
	delete_option( GOODBLOCKS_INSTAGRAM_ERROR_OPTION );

	return $cache;
}

/**
 * Normalize a raw Instagram media item.
 *
 * @param array $item Raw API item.
 * @return array|null
 */
function goodblocks_instagram_normalize_item( array $item ): ?array {
	$media_type = isset( $item['media_type'] ) ? sanitize_key( $item['media_type'] ) : '';
	$image_url  = '';

	if ( ! empty( $item['thumbnail_url'] ) ) {
		$image_url = esc_url_raw( $item['thumbnail_url'] );
	} elseif ( ! empty( $item['media_url'] ) ) {
		$image_url = esc_url_raw( $item['media_url'] );
	}

	if ( empty( $image_url ) || empty( $item['permalink'] ) ) {
		return null;
	}

	return [
		'id'         => isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '',
		'caption'    => isset( $item['caption'] ) ? wp_strip_all_tags( $item['caption'] ) : '',
		'media_type' => $media_type,
		'image_url'  => $image_url,
		'permalink'  => esc_url_raw( $item['permalink'] ),
		'timestamp'  => isset( $item['timestamp'] ) ? sanitize_text_field( $item['timestamp'] ) : '',
		'username'   => isset( $item['username'] ) ? sanitize_text_field( $item['username'] ) : '',
	];
}
