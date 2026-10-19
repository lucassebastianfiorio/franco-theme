<?php
/**
 * Enqueue Scripts and Styles
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function franco_enqueue_assets() {
	// Google Fonts (Outfit & Plus Jakarta Sans)
	wp_enqueue_style( 'franco-google-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', array(), null );

	// Font Awesome 6.5.1 CDN
	wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1' );

	// Custom Design System CSS
	wp_enqueue_style( 'franco-custom-css', FRANCO_THEME_URI . '/assets/css/styles.css', array(), FRANCO_THEME_VERSION );

	// WooCommerce Custom Overrides CSS
	wp_enqueue_style( 'franco-woocommerce-custom-css', FRANCO_THEME_URI . '/assets/css/woocommerce-custom.css', array( 'franco-custom-css' ), FRANCO_THEME_VERSION );

	// Component classes (replace former inline styles); must load after the two sheets above
	wp_enqueue_style( 'franco-components-css', FRANCO_THEME_URI . '/assets/css/components.css', array( 'franco-woocommerce-custom-css' ), FRANCO_THEME_VERSION );

	// Enqueue jQuery (WordPress Core)
	wp_enqueue_script( 'jquery' );

	// Interactive App JS
	wp_enqueue_script( 'franco-app-js', FRANCO_THEME_URI . '/assets/js/app.js', array( 'jquery' ), FRANCO_THEME_VERSION, true );
	wp_script_add_data( 'franco-app-js', 'strategy', 'defer' );

	// WooCommerce Integration JS (only when WooCommerce is active)
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_script( 'franco-woocommerce-js', FRANCO_THEME_URI . '/assets/js/franco-woocommerce.js', array( 'jquery', 'franco-app-js' ), FRANCO_THEME_VERSION, true );
		wp_script_add_data( 'franco-woocommerce-js', 'strategy', 'defer' );

		$whatsapp_number = preg_replace( '/\D/', '', (string) franco_get_setting( 'franco_whatsapp_number', '' ) );
		$customer_postcode = ( class_exists( 'WooCommerce' ) && WC()->customer ) ? WC()->customer->get_shipping_postcode() : '';
		if ( empty( $customer_postcode ) && ! empty( $_COOKIE['franco_postcode'] ) ) {
			$customer_postcode = sanitize_text_field( wp_unslash( $_COOKIE['franco_postcode'] ) );
		}

		wp_localize_script( 'franco-woocommerce-js', 'franco_wc_params', array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'checkout_ajax_url' => WC_AJAX::get_endpoint( 'checkout' ),
			'cart_url'          => wc_get_cart_url(),
			'checkout_url'      => wc_get_checkout_url(),
			'shop_url'          => get_permalink( wc_get_page_id( 'shop' ) ),
			'nonce'             => wp_create_nonce( 'franco_wc_nonce' ),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'whatsapp'          => $whatsapp_number,
			'customer_postcode' => $customer_postcode,
			'i18n'              => array(
				'added_to_cart'    => __( '¡Producto agregado al carrito!', 'franco-theme' ),
				'cart_empty'       => __( 'Tu carrito está vacío.', 'franco-theme' ),
				'error_occurred'   => __( 'Ocurrió un error. Intenta nuevamente.', 'franco-theme' ),
				'processing_order' => __( 'Procesando tu pedido...', 'franco-theme' ),
				'confirm_remove'   => __( '¿Deseas eliminar este producto?', 'franco-theme' ),
			),
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'franco_enqueue_assets' );

/**
 * Enqueue the settings page stylesheet only on the Franco Deportes admin screen.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 */
function franco_enqueue_admin_assets( $hook_suffix ) {
	if ( 'toplevel_page_franco-settings' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_style( 'franco-admin-css', FRANCO_THEME_URI . '/assets/css/admin.css', array(), FRANCO_THEME_VERSION );
}
add_action( 'admin_enqueue_scripts', 'franco_enqueue_admin_assets' );

/**
 * Preload the hero image (LCP element) on the front page so the browser fetches it before the CSS/JS blocking work.
 */
function franco_preload_hero_image() {
	if ( ! is_front_page() ) {
		return;
	}

	$hero = franco_get_hero_image();
	if ( '' === $hero['url'] ) {
		return;
	}

	$attrs = ' rel="preload" as="image" href="' . esc_url( $hero['url'] ) . '"';
	if ( '' !== $hero['srcset'] ) {
		$attrs .= ' imagesrcset="' . esc_attr( $hero['srcset'] ) . '" imagesizes="' . esc_attr( $hero['sizes'] ) . '"';
	}

	// $attrs is built exclusively from esc_url() / esc_attr() output above.
	echo '<link' . $attrs . ' fetchpriority="high">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'franco_preload_hero_image', 1 );

/**
 * Add resource hints (preconnect and dns-prefetch) for external fonts and CDNs to improve performance
 */
function franco_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href' => 'https://fonts.googleapis.com',
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
		$urls[] = array(
			'href'        => 'https://cdnjs.cloudflare.com',
			'crossorigin' => 'anonymous',
		);
	} elseif ( 'dns-prefetch' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = 'https://fonts.gstatic.com';
		$urls[] = 'https://cdnjs.cloudflare.com';
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'franco_resource_hints', 10, 2 );

/**
 * Add Subresource Integrity (SRI) to external Font Awesome stylesheet.
 *
 * @param string $html Tag HTML markup.
 * @param string $handle Script/style handle.
 * @return string Modified markup.
 */
function franco_add_font_awesome_attributes( $html, $handle ) {
	if ( 'font-awesome' === $handle ) {
		return str_replace(
			" id='font-awesome-css'",
			" integrity=\"sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==\" crossorigin=\"anonymous\" referrerpolicy=\"no-referrer\" id='font-awesome-css'",
			$html
		);
	}
	return $html;
}
add_filter( 'style_loader_tag', 'franco_add_font_awesome_attributes', 10, 2 );

