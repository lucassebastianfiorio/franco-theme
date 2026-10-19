<?php
/**
 * Navigation Menus Registration
 *
 * @package FrancoTheme
 * @version 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function franco_register_nav_menus() {
	register_nav_menus( array(
		'primary' => __( 'Menú Principal Navegación', 'franco-theme' ),
		'mobile'  => __( 'Menú Móvil Navigation', 'franco-theme' ),
		'footer'  => __( 'Menú Pie de Página', 'franco-theme' ),
	) );
}
add_action( 'init', 'franco_register_nav_menus' );

/**
 * Fallback Header Nav Menu if user hasn't set up WP Menu yet
 */
function franco_primary_menu_fallback() {
	$home_url = home_url( '/' );

	$nosotros_id = get_option( 'franco_page_nosotros', 0 );
	$contacto_id = get_option( 'franco_page_contacto', 0 );
	$tienda_id   = get_option( 'franco_page_tienda', ( class_exists( 'WooCommerce' ) ? wc_get_page_id( 'shop' ) : 0 ) );

	$shop_url     = $tienda_id ? get_permalink( $tienda_id ) : home_url( '/tienda' );
	$nosotros_url = $nosotros_id ? get_permalink( $nosotros_id ) : home_url( '/nosotros' );
	$contacto_url = $contacto_id ? get_permalink( $contacto_id ) : home_url( '/contacto' );

	$is_home     = is_front_page() ? 'active' : '';
	$is_shop     = ( function_exists( 'is_shop' ) && is_shop() ) ? 'active' : '';
	$is_nosotros = ( $nosotros_id && is_page( $nosotros_id ) ) || is_page( 'nosotros' ) ? 'active' : '';
	$is_contacto = ( $contacto_id && is_page( $contacto_id ) ) || is_page( 'contacto' ) ? 'active' : '';

	echo '<ul class="nav-menu" id="nav-menu">';
	echo '<button class="nav-menu-close" id="mobile-menu-close" aria-label="Cerrar menú"><i class="fa-solid fa-xmark"></i></button>';
	echo '<li><a href="' . esc_url( $home_url ) . '" class="nav-link ' . $is_home . '">' . esc_html__( 'Inicio', 'franco-theme' ) . '</a></li>';
	echo '<li><a href="' . esc_url( $shop_url ) . '" class="nav-link ' . $is_shop . '">' . esc_html__( 'Tienda', 'franco-theme' ) . '</a></li>';
	echo '<li><a href="' . esc_url( $nosotros_url ) . '" class="nav-link ' . $is_nosotros . '">' . esc_html__( 'Nosotros', 'franco-theme' ) . '</a></li>';
	echo '<li><a href="' . esc_url( $contacto_url ) . '" class="nav-link ' . $is_contacto . '">' . esc_html__( 'Contacto', 'franco-theme' ) . '</a></li>';
	echo '</ul>';
}
