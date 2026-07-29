<?php
/**
 * GoodBlocks helper functions.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get post thumbnail or fall back to the first content image/site default image.
 *
 * @param string $size Image size.
 * @param array  $attr Image attributes.
 * @param string $source Image source preference.
 * @return string Image HTML.
 */
function goodblocks_get_thumbnail( $size = 'large', $attr = array(), $source = 'featured' ) {
	$post = get_post();

	if ( 'first' !== $source ) {
		$thumbnail = goodblocks_get_post_thumbnail_html( $post, $size, $attr );
		if ( $thumbnail ) {
			return $thumbnail;
		}
	}

	if ( 'first' === $source ) {
		$content = get_the_content( null, false, $post );

		if ( preg_match( '/wp-image-(\d+)/', $content, $image_id ) ) {
			$attachment_id = absint( $image_id[1] );
			$mime_type     = get_post_mime_type( $attachment_id );
			$image         = 'image/svg+xml' !== $mime_type ? wp_get_attachment_image( $attachment_id, $size, false, $attr ) : '';
			if ( $image ) {
				return $image;
			}
		}

		if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches ) ) {
			$src = esc_url( $matches[1] );
			$path = wp_parse_url( $src, PHP_URL_PATH );
			if ( $src && 'svg' !== strtolower( pathinfo( (string) $path, PATHINFO_EXTENSION ) ) ) {
				return sprintf(
					'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
					$src,
					esc_attr( get_the_title( $post ) )
				);
			}
		}

		$thumbnail = goodblocks_get_post_thumbnail_html( $post, $size, $attr );
		if ( $thumbnail ) {
			return $thumbnail;
		}
	}

	$default_image = get_option( 'goodblocks_default_image', '' );

	if ( $default_image ) {
		return wp_get_attachment_image( $default_image, $size );
	}

	$legacy_default_image = apply_filters( 'pt_cv_default_image', '' );

	if ( is_string( $legacy_default_image ) && $legacy_default_image ) {
		$src = esc_url( $legacy_default_image );
		if ( $src ) {
			return sprintf(
				'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
				$src,
				esc_attr( get_the_title( $post ) )
			);
		}
	}

	return '';
}

/**
 * Get a featured image only when it is suitable for thumbnail contexts.
 *
 * @param WP_Post|int|null $post Post object or ID.
 * @param string           $size Image size.
 * @param array            $attr Image attributes.
 * @return string Image HTML.
 */
function goodblocks_get_post_thumbnail_html( $post, $size = 'large', $attr = array() ) {
	if ( ! has_post_thumbnail( $post ) ) {
		return '';
	}

	$thumbnail_id = get_post_thumbnail_id( $post );

	if ( $thumbnail_id && 'image/svg+xml' === get_post_mime_type( $thumbnail_id ) ) {
		return '';
	}

	return get_the_post_thumbnail( $post, $size, $attr );
}

/**
 * Get a trimmed excerpt without WordPress' default bracketed ellipsis.
 *
 * @param int $word_count Number of words.
 * @return string Excerpt text.
 */
function goodblocks_get_trimmed_excerpt( $word_count = 35 ) {
	$post = get_post();

	if ( ! $post ) {
		return '';
	}

	$text = get_the_excerpt( $post );
	$text = preg_replace( '/\s*\[(?:&hellip;|&#8230;|…|\.\.\.)\]\s*$/u', '', $text );
	$text = preg_replace( '/\s*(?:&hellip;|&#8230;|…|\.\.\.)\s*$/u', '', $text );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );
	$text = preg_replace( '/\s+/u', ' ', trim( $text ) );

	$excerpt = wp_trim_words( $text, max( 1, absint( $word_count ) ), ' …' );

	return preg_replace( '/[.!?]\s+…$/u', ' …', $excerpt );
}

/**
 * Display an event date range for legacy The Events Calendar events.
 *
 * @param int|null $event_id Event post ID.
 */
function goodblocks_display_tribe_event_date_range( $event_id = null ) {
	if ( ! function_exists( 'tribe_get_start_date' ) ) {
		return;
	}

	$event_id = $event_id ? absint( $event_id ) : get_the_ID();
	if ( ! $event_id ) {
		return;
	}

	$is_all_day = function_exists( 'tribe_event_is_all_day' ) && tribe_event_is_all_day( $event_id );
	$current_year = date_i18n( 'Y' );
	$date_format  = 'j F';
	$time_format  = ' H.i';

	$start_date = tribe_get_start_date( $event_id, false, 'Y-m-d' );
	$end_date   = tribe_get_end_date( $event_id, false, 'Y-m-d' );
	$start_year = date( 'Y', strtotime( $start_date ) );
	$end_year   = date( 'Y', strtotime( $end_date ) );

	$start_format = $date_format;
	if ( $start_year !== $current_year ) {
		$start_format .= ' Y';
	}
	if ( ! $is_all_day ) {
		$start_format .= $time_format;
	}

	$end_format = $date_format;
	if ( $end_year !== $current_year ) {
		$end_format .= ' Y';
	}
	if ( ! $is_all_day ) {
		$end_format .= $time_format;
	}

	$start    = tribe_get_start_date( $event_id, false, $start_format );
	$end      = tribe_get_end_date( $event_id, false, $end_format );
	$end_time = function_exists( 'tribe_get_end_time' ) ? tribe_get_end_time( $event_id, 'H.i' ) : '';

	if ( $start_date === $end_date ) {
		echo esc_html( $is_all_day || ! $end_time ? $start : sprintf( '%s-%s', $start, $end_time ) );
		return;
	}

	echo esc_html( sprintf( '%s-%s', $start, $end ) );
}
add_action( 'goodblocks_event_date_range', 'goodblocks_display_tribe_event_date_range', 10, 1 );

/**
 * Load a block template with theme override support.
 *
 * Lookup order:
 *  1. Child theme:  goodblocks/templates/{block}/{template}.php
 *  2. Parent theme: goodblocks/templates/{block}/{template}.php
 *  3. Plugin:       src/blocks/{block}/templates/{template}.php
 *
 * @param string $block         Block slug (e.g. 'post-grid').
 * @param string $template_name Template name without .php (e.g. 'grid').
 * @param array  $attributes    Block attributes passed to the template.
 */
function goodblocks_template( string $block, string $template_name, array $attributes = array() ): void {
	$template_path = '';
	$template_file = 'goodblocks/templates/' . $block . '/' . $template_name . '.php';

	// Check child theme first.
	if ( is_child_theme() ) {
		$child_path = get_stylesheet_directory() . '/' . $template_file;
		if ( file_exists( $child_path ) ) {
			$template_path = $child_path;
		}
	}

	// Check parent theme.
	if ( ! $template_path ) {
		$parent_path = get_template_directory() . '/' . $template_file;
		if ( file_exists( $parent_path ) ) {
			$template_path = $parent_path;
		}
	}

	// Fallback to plugin template (check build first, then src).
	if ( ! $template_path ) {
		$build_path = GOODBLOCKS_DIR . 'build/blocks/' . $block . '/templates/' . $template_name . '.php';
		$src_path   = GOODBLOCKS_DIR . 'src/blocks/' . $block . '/templates/' . $template_name . '.php';

		if ( file_exists( $build_path ) ) {
			$template_path = $build_path;
		} elseif ( file_exists( $src_path ) ) {
			$template_path = $src_path;
		}
	}

	$template_path = apply_filters( 'goodblocks_template_path', $template_path, $block, $template_name, $attributes );

	if ( $template_path && file_exists( $template_path ) ) {
		( static function ( $__template_path, $attributes ) {
			include $__template_path;
		} )( $template_path, $attributes );
	}
}
