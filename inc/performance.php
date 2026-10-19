<?php
/**
 * Performance and Asset Optimizations
 *
 * Removes unneeded core scripts, styles, emojis and generator tags.
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clean up WordPress Head bloat.
 */
function franco_cleanup_head() {
	// Emojis
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	// Meta tags, RSD, WLW, WP generator
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'franco_cleanup_head' );

/**
 * Filter out Emoji DNS prefetch.
 */
function franco_remove_emoji_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );
		foreach ( $urls as $key => $url ) {
			if ( is_string( $url ) && false !== strpos( $url, $emoji_svg_url ) ) {
				unset( $urls[ $key ] );
			}
		}
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'franco_remove_emoji_dns_prefetch', 10, 2 );

/**
 * Dequeue unneeded block library styles and default WC scripts on frontend.
 */
function franco_dequeue_unneeded_assets() {
	if ( is_admin() ) {
		return;
	}

	// Remove WP Core Block Library CSS (Theme is 100% custom styling)
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );

	// Disable wc-cart-fragments on non-cart/non-checkout pages if active (Theme uses native francoFetchCart)
	if ( ! is_cart() && ! is_checkout() ) {
		wp_dequeue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'franco_dequeue_unneeded_assets', 100 );
