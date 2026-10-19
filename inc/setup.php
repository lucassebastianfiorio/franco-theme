<?php
/**
 * Theme Setup Functions
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'franco_theme_setup' ) ) :
	function franco_theme_setup() {
		// Make theme available for translation
		load_theme_textdomain( 'franco-theme', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head
		add_theme_support( 'automatic-feed-links' );

		// Let WordPress manage the document title
		add_theme_support( 'title-tag' );

		// Enable support for Post Thumbnails on posts and pages
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'franco-product-card', 400, 400, true );
		add_image_size( 'franco-product-gallery', 800, 800, true );

		// Custom Logo Support
		add_theme_support( 'custom-logo', array(
			'height'      => 60,
			'width'       => 200,
			'flex-height' => true,
			'flex-width'  => true,
			'header-text' => array( 'site-title', 'site-description' ),
		) );

		// Switch default core markup to output valid HTML5
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		) );

		// Declare WooCommerce support
		add_theme_support( 'woocommerce', array(
			'thumbnail_image_width' => 400,
			'single_image_width'    => 800,
			'product_grid'          => array(
				'default_rows'    => 4,
				'min_rows'        => 2,
				'max_rows'        => 8,
				'default_columns' => 4,
				'min_columns'     => 1,
				'max_columns'     => 4,
			),
		) );

		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
endif;
add_action( 'after_setup_theme', 'franco_theme_setup' );

/**
 * Set Content Width
 */
function franco_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'franco_content_width', 1200 );
}
add_action( 'after_setup_theme', 'franco_content_width', 0 );
