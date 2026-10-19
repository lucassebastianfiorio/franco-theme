<?php
/**
 * Franco Theme - Uninstall Handler
 *
 * Runs automatically when the theme is deleted from WordPress Admin.
 * Cleans up theme-specific options, customizer mods, and transients.
 * NEVER deletes WooCommerce orders, customer data, products, or core pages.
 *
 * @package FrancoTheme
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete Theme-Specific Options
$franco_options = array(
	'franco_catalog_cache_ver',
	'franco_page_nosotros',
	'franco_page_contacto',
	'franco_page_favoritos',
	'franco_page_tienda',
	'franco_page_carrito',
	'franco_page_checkout',
	'franco_page_myaccount',
	'franco_facebook_url',
	'franco_tiktok_url',
	'franco_instagram_url',
	'franco_whatsapp_number',
	'franco_topbar_text_1',
	'franco_topbar_text_2',
	'franco_topbar_text_3',
	'franco_theme_version',
);

foreach ( $franco_options as $opt ) {
	delete_option( $opt );
}

// 2. Delete Theme Mods (Customizer settings)
remove_theme_mods();

// 3. Purge Franco Theme Transients
// Uses targeted SQL cleanup for transient keys starting with franco_
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_franco_%' OR option_name LIKE '_transient_timeout_franco_%'"
);
