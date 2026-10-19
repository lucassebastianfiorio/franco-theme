<?php
/**
 * Franco Deportes Theme Functions
 *
 * @package FrancoTheme
 * @version 3.9.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'FRANCO_THEME_VERSION', '3.9.2' );
define( 'FRANCO_THEME_DIR', get_template_directory() );
define( 'FRANCO_THEME_URI', get_template_directory_uri() );

/**
 * Require Modular Theme Files
 */
require_once FRANCO_THEME_DIR . '/inc/setup.php';
require_once FRANCO_THEME_DIR . '/inc/enqueue.php';
require_once FRANCO_THEME_DIR . '/inc/menus.php';
require_once FRANCO_THEME_DIR . '/inc/widgets.php';
require_once FRANCO_THEME_DIR . '/inc/admin-settings.php';
require_once FRANCO_THEME_DIR . '/inc/template-functions.php';
require_once FRANCO_THEME_DIR . '/inc/theme-updater.php';

// Load WooCommerce Integration if WooCommerce is Active
if ( class_exists( 'WooCommerce' ) ) {
	require_once FRANCO_THEME_DIR . '/inc/woocommerce.php';
}
