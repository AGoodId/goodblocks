<?php
/**
 * Local navigation rendering helpers.
 *
 * @package GoodBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the top-level page ancestor for the current request.
 *
 * @param int $post_id Optional post ID.
 * @return int
 */
function goodblocks_local_navigation_get_root_id( $post_id = 0 ) {
	$post_id = absint( $post_id ?: get_queried_object_id() );

	if ( ! $post_id ) {
		global $post;

		if ( $post instanceof WP_Post ) {
			$post_id = $post->ID;
		}
	}

	if ( ! $post_id && ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$request_path = strtok( wp_unslash( $_SERVER['REQUEST_URI'] ), '?' );
		$post_id      = url_to_postid( home_url( $request_path ) );
	}

	if ( ! $post_id || 'page' !== get_post_type( $post_id ) ) {
		return 0;
	}

	$ancestors = get_post_ancestors( $post_id );

	return empty( $ancestors ) ? $post_id : (int) end( $ancestors );
}

/**
 * Normalize a local URL to its path.
 *
 * @param string $url URL.
 * @return string
 */
function goodblocks_local_navigation_normalize_url( $url ) {
	$path = wp_parse_url( $url, PHP_URL_PATH );

	if ( empty( $path ) ) {
		return '/';
	}

	return trailingslashit( $path );
}

/**
 * Get the current request path.
 *
 * @return string
 */
function goodblocks_local_navigation_get_current_path() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}

	$request_path = strtok( wp_unslash( $_SERVER['REQUEST_URI'] ), '?' );

	return goodblocks_local_navigation_normalize_url( home_url( $request_path ) );
}

/**
 * Sanitize a sort column value for get_pages().
 *
 * @param string $orderby Sort column.
 * @return string
 */
function goodblocks_local_navigation_sanitize_orderby( $orderby ) {
	$orderby = sanitize_text_field( (string) $orderby );
	$allowed = array(
		'post_title',
		'menu_order',
		'menu_order,post_title',
		'post_date',
		'post_modified',
		'ID',
	);

	return in_array( $orderby, $allowed, true ) ? $orderby : 'menu_order,post_title';
}

/**
 * Render a local navigation tree.
 *
 * @param array $attributes Block or shortcode attributes.
 * @return string
 */
function goodblocks_render_local_navigation( $attributes = array() ) {
	$defaults = array(
		'source'       => 'pages',
		'parentPostId' => 0,
		'depth'        => 0,
		'orderby'      => 'menu_order,post_title',
		'order'        => 'ASC',
		'showRoot'     => false,
		'accordion'    => true,
		'markupPreset' => 'goodblocks',
		'configId'     => 'main',
	);
	$attributes = wp_parse_args( $attributes, $defaults );

	if ( 'pages' !== $attributes['source'] ) {
		return '';
	}

	$root_id = absint( $attributes['parentPostId'] );

	if ( ! $root_id ) {
		$root_id = goodblocks_local_navigation_get_root_id();
	}

	if ( ! $root_id || 'page' !== get_post_type( $root_id ) ) {
		return '';
	}

	$depth         = max( 0, absint( $attributes['depth'] ) );
	$orderby       = goodblocks_local_navigation_sanitize_orderby( $attributes['orderby'] );
	$order         = 'DESC' === strtoupper( (string) $attributes['order'] ) ? 'DESC' : 'ASC';
	$show_root     = filter_var( $attributes['showRoot'], FILTER_VALIDATE_BOOLEAN );
	$accordion     = filter_var( $attributes['accordion'], FILTER_VALIDATE_BOOLEAN );
	$markup_preset = 'bellows' === $attributes['markupPreset'] ? 'bellows' : 'goodblocks';
	$config_id     = sanitize_html_class( $attributes['configId'] ?: 'main' );
	$current_id    = get_queried_object_id();
	$current_path  = goodblocks_local_navigation_get_current_path();

	$tree_args = array(
		'depth'        => $depth,
		'orderby'      => $orderby,
		'order'        => $order,
		'accordion'    => $accordion,
		'markupPreset' => $markup_preset,
		'configId'     => $config_id,
		'currentId'    => $current_id,
		'currentPath'  => $current_path,
	);

	$items = goodblocks_local_navigation_get_pages_markup( $root_id, $tree_args );

	if ( empty( $items ) ) {
		return '';
	}

	if ( 'bellows' === $markup_preset ) {
		return goodblocks_local_navigation_wrap_bellows_markup( $items, $root_id, $show_root, $config_id );
	}

	return goodblocks_local_navigation_wrap_default_markup( $items, $root_id, $show_root );
}

/**
 * Render child pages for a local navigation tree.
 *
 * @param int   $parent_id Parent page ID.
 * @param array $args Render args.
 * @param int   $level Current depth level.
 * @return string
 */
function goodblocks_local_navigation_get_pages_markup( $parent_id, $args, $level = 0 ) {
	$max_depth = absint( $args['depth'] );

	if ( $max_depth && $level >= $max_depth ) {
		return '';
	}

	$pages = get_pages(
		array(
			'parent'      => absint( $parent_id ),
			'post_status' => 'publish',
			'sort_column' => $args['orderby'],
			'sort_order'  => $args['order'],
		)
	);

	if ( empty( $pages ) ) {
		return '';
	}

	$output = '';

	foreach ( $pages as $page ) {
		$children = goodblocks_local_navigation_get_pages_markup( $page->ID, $args, $level + 1 );
		$is_current = (int) $args['currentId'] === (int) $page->ID;
		$is_ancestor = $args['currentId'] && in_array( (int) $page->ID, get_post_ancestors( (int) $args['currentId'] ), true );
		$is_open = ! empty( $children ) && ( $is_current || $is_ancestor );

		if ( 'bellows' === $args['markupPreset'] ) {
			$output .= goodblocks_local_navigation_get_bellows_item_markup( $page, $children, $level, $is_current, $is_ancestor, $is_open, $args );
		} else {
			$output .= goodblocks_local_navigation_get_default_item_markup( $page, $children, $is_current, $is_ancestor, $is_open, $args );
		}
	}

	return $output;
}

/**
 * Render a Bellows-compatible item.
 *
 * @param WP_Post $page Page.
 * @param string  $children Children markup.
 * @param int     $level Current level.
 * @param bool    $is_current Whether current.
 * @param bool    $is_ancestor Whether ancestor.
 * @param bool    $is_open Whether open.
 * @param array   $args Render args.
 * @return string
 */
function goodblocks_local_navigation_get_bellows_item_markup( $page, $children, $level, $is_current, $is_ancestor, $is_open, $args ) {
	$classes = array(
		'bellows-menu-item',
		'bellows-menu-item-post-' . $page->ID,
		'bellows-menu-item-type-post_type',
		'bellows-menu-item-object-page',
		'bellows-menu-item-' . $page->ID,
		'bellows-item-level-' . $level,
	);

	if ( $is_current ) {
		$classes[] = 'bellows-current-menu-item';
		$classes[] = 'bellows-current_page_item';
	}

	if ( $is_ancestor ) {
		$classes[] = 'bellows-current-menu-ancestor';
		$classes[] = 'bellows-current_page_ancestor';
	}

	if ( ! empty( $children ) ) {
		$classes[] = 'bellows-menu-item-has-children';
		$classes[] = 'page_item_has_children';

		if ( $is_open ) {
			$classes[] = 'bellows-active';
			$classes[] = 'goodblocks-local-navigation__item--open';
		}
	}

	$output  = sprintf(
		'<li id="menu-item-post-%1$d" class="%2$s" data-post-id="%1$d">',
		absint( $page->ID ),
		esc_attr( implode( ' ', $classes ) )
	);
	$output .= sprintf(
		'<a href="%1$s" class="bellows-target menu-link"%2$s><span class="bellows-target-title bellows-target-text">%3$s</span></a>',
		esc_url( get_permalink( $page->ID ) ),
		$is_current ? ' aria-current="page"' : '',
		esc_html( get_the_title( $page->ID ) )
	);

	if ( ! empty( $children ) && $args['accordion'] ) {
		$output .= sprintf(
			'<button class="bellows-subtoggle goodblocks-local-navigation__toggle" type="button" aria-label="%1$s" aria-expanded="%2$s"><span class="screen-reader-text">%1$s</span><span class="bellows-subtoggle-icon" aria-hidden="true"></span></button>',
			esc_attr__( 'Visa eller dölj undersidor', 'goodblocks' ),
			$is_open ? 'true' : 'false'
		);
	}

	if ( ! empty( $children ) ) {
		$output .= sprintf(
			'<ul class="bellows-submenu"%1$s>%2$s</ul>',
			$is_open ? '' : ' hidden',
			$children // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	$output .= '</li>';

	return $output;
}

/**
 * Render a default GoodBlocks item.
 *
 * @param WP_Post $page Page.
 * @param string  $children Children markup.
 * @param bool    $is_current Whether current.
 * @param bool    $is_ancestor Whether ancestor.
 * @param bool    $is_open Whether open.
 * @param array   $args Render args.
 * @return string
 */
function goodblocks_local_navigation_get_default_item_markup( $page, $children, $is_current, $is_ancestor, $is_open, $args ) {
	$classes = array( 'goodblocks-local-navigation__item', 'page_item', 'page-item-' . $page->ID );

	if ( $is_current ) {
		$classes[] = 'current_page_item';
	}

	if ( $is_ancestor ) {
		$classes[] = 'current_page_ancestor';
	}

	if ( ! empty( $children ) ) {
		$classes[] = 'page_item_has_children';

		if ( $is_open ) {
			$classes[] = 'goodblocks-local-navigation__item--open';
		}
	}

	$output  = sprintf( '<li class="%s">', esc_attr( implode( ' ', $classes ) ) );
	$output .= '<div class="goodblocks-local-navigation__item-row">';
	$output .= sprintf(
		'<a href="%1$s"%2$s>%3$s</a>',
		esc_url( get_permalink( $page->ID ) ),
		$is_current ? ' aria-current="page"' : '',
		esc_html( get_the_title( $page->ID ) )
	);

	if ( ! empty( $children ) && $args['accordion'] ) {
		$output .= sprintf(
			'<button class="goodblocks-local-navigation__toggle" type="button" aria-expanded="%1$s"><span class="screen-reader-text">%2$s</span><span class="goodblocks-local-navigation__toggle-icon" aria-hidden="true"></span></button>',
			$is_open ? 'true' : 'false',
			esc_html__( 'Visa eller dölj undersidor', 'goodblocks' )
		);
	}

	$output .= '</div>';

	if ( ! empty( $children ) ) {
		$output .= sprintf(
			'<ul class="children"%1$s>%2$s</ul>',
			$is_open ? '' : ' hidden',
			$children // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	$output .= '</li>';

	return $output;
}

/**
 * Wrap Bellows-compatible markup.
 *
 * @param string $items Items markup.
 * @param int    $root_id Root page ID.
 * @param bool   $show_root Whether to show root title.
 * @param string $config_id Bellows config ID.
 * @return string
 */
function goodblocks_local_navigation_wrap_bellows_markup( $items, $root_id, $show_root, $config_id ) {
	$classes = array(
		'goodblocks-local-navigation',
		'goodblocks-local-navigation--bellows',
		'bellows',
		'bellows-nojs',
		'bellows-' . sanitize_html_class( $config_id ),
		'bellows-source-posts',
		'bellows-align-full',
		'bellows-skin-vanilla',
		'bellows-type-accordion',
	);

	$output = sprintf(
		'<nav id="goodblocks-local-navigation-%1$d-%2$s" class="%3$s" aria-label="%4$s">',
		absint( $root_id ),
		esc_attr( sanitize_html_class( $config_id ) ),
		esc_attr( implode( ' ', $classes ) ),
		esc_attr__( 'Lokal navigation', 'goodblocks' )
	);

	if ( $show_root ) {
		$output .= sprintf(
			'<h2 class="goodblocks-local-navigation__title"><a href="%1$s">%2$s</a></h2>',
			esc_url( get_permalink( $root_id ) ),
			esc_html( get_the_title( $root_id ) )
		);
	}

	$output .= sprintf(
		'<ul id="menu-goodblocks-local-navigation-%1$d" class="bellows-nav" data-bellows-config="%2$s">%3$s</ul>',
		absint( $root_id ),
		esc_attr( $config_id ),
		$items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	$output .= '</nav>';

	return $output;
}

/**
 * Wrap default GoodBlocks markup.
 *
 * @param string $items Items markup.
 * @param int    $root_id Root page ID.
 * @param bool   $show_root Whether to show root title.
 * @return string
 */
function goodblocks_local_navigation_wrap_default_markup( $items, $root_id, $show_root ) {
	$output = '<nav class="goodblocks-local-navigation" aria-label="' . esc_attr__( 'Lokal navigation', 'goodblocks' ) . '">';

	if ( $show_root ) {
		$output .= sprintf(
			'<h2 class="goodblocks-local-navigation__title"><a href="%1$s">%2$s</a></h2>',
			esc_url( get_permalink( $root_id ) ),
			esc_html( get_the_title( $root_id ) )
		);
	}

	$output .= '<ul class="goodblocks-local-navigation__list">' . $items . '</ul>';
	$output .= '</nav>';

	return $output;
}

/**
 * Shortcode wrapper.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function goodblocks_local_navigation_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'parent'   => 0,
			'depth'    => 0,
			'orderby'  => 'menu_order,post_title',
			'order'    => 'ASC',
			'showroot' => '0',
			'markup'   => 'goodblocks',
			'config'   => '',
			'config_id' => '',
		),
		$atts,
		'goodblocks_local_navigation'
	);

	return goodblocks_render_local_navigation(
		array(
			'parentPostId' => absint( $atts['parent'] ),
			'depth'        => absint( $atts['depth'] ),
			'orderby'      => $atts['orderby'],
			'order'        => $atts['order'],
			'showRoot'     => filter_var( $atts['showroot'], FILTER_VALIDATE_BOOLEAN ),
			'markupPreset' => 'bellows' === $atts['markup'] ? 'bellows' : 'goodblocks',
			'configId'     => $atts['config_id'] ?: ( $atts['config'] ?: 'main' ),
		)
	);
}
add_shortcode( 'goodblocks_local_navigation', 'goodblocks_local_navigation_shortcode' );
