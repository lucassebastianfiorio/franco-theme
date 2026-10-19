<?php
/**
 * Security Hardening & Headers
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Send Secure HTTP Headers
 */
function franco_send_security_headers() {
	if ( headers_sent() ) {
		return;
	}

	// Prevent MIME-type sniffing
	header( 'X-Content-Type-Options: nosniff' );

	// Prevent clickjacking
	header( 'X-Frame-Options: SAMEORIGIN' );

	// Referrer Policy
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );

	// Permissions Policy
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );

	// Legacy X-XSS-Protection
	header( 'X-XSS-Protection: 1; mode=block' );

	// HTTP Strict Transport Security (HSTS) when on HTTPS
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}

	// Content Security Policy (CSP) with payment gateways & external asset support
	if ( ! is_admin() ) {
		$csp = "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; " .
			"script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.mercadopago.com https://*.mercadolibre.com https://*.decidir.com https://*.payway.com.ar https://cdnjs.cloudflare.com; " .
			"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
			"font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
			"img-src 'self' data: blob: https:; " .
			"frame-src 'self' https://*.mercadopago.com https://*.mercadolibre.com https://*.decidir.com https://*.payway.com.ar; " .
			"connect-src 'self' https:;";

		$filtered_csp = apply_filters( 'franco_content_security_policy', $csp );
		if ( ! empty( $filtered_csp ) ) {
			header( 'Content-Security-Policy: ' . $filtered_csp );
		}
	}
}
add_action( 'send_headers', 'franco_send_security_headers' );

/**
 * 2. Block Author Query String Enumeration (?author=1)
 */
function franco_block_user_enumeration() {
	if ( ! is_admin() && isset( $_REQUEST['author'] ) && ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( home_url(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'franco_block_user_enumeration' );

/**
 * 3. Restrict WP REST API /wp/v2/users for Unauthenticated Requests
 */
function franco_restrict_rest_users( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	if ( ! is_user_logged_in() && isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( (string) $_SERVER['REQUEST_URI'], '/wp/v2/users' ) ) {
		return new WP_Error(
			'rest_forbidden_context',
			__( 'Acceso restringido.', 'franco-theme' ),
			array( 'status' => 401 )
		);
	}

	return $result;
}
add_filter( 'rest_authentication_errors', 'franco_restrict_rest_users' );

/**
 * 4. Disable XML-RPC to Prevent Brute-Force & Amplification Attacks
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * 5. Generic Login Error Messages (Prevent Username Enumeration on Login)
 */
function franco_generic_login_errors() {
	return __( 'Error: El usuario o la contraseña ingresados son incorrectos.', 'franco-theme' );
}
add_filter( 'login_errors', 'franco_generic_login_errors' );
