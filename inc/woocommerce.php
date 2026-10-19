<?php
/**
 * WooCommerce Integration Module
 *
 * @package FrancoTheme
 * @version 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Register DNI / CUIT Custom Field in WooCommerce Checkout
 */
function franco_wc_add_dni_checkout_field( $fields ) {
	$fields['billing']['billing_dni'] = array(
		'type'        => 'text',
		'label'       => __( 'DNI / CUIT', 'franco-theme' ),
		'placeholder' => __( '38.123.456', 'franco-theme' ),
		'required'    => true,
		'class'       => array( 'form-row-wide' ),
		'priority'    => 25,
	);
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'franco_wc_add_dni_checkout_field' );

// Validate DNI/CUIT field
function franco_wc_validate_dni_checkout_field() {
	if ( isset( $_POST['billing_dni'] ) && empty( sanitize_text_field( $_POST['billing_dni'] ) ) ) {
		wc_add_notice( __( 'Por favor ingresa tu DNI / CUIT.', 'franco-theme' ), 'error' );
	}
}
add_action( 'woocommerce_checkout_process', 'franco_wc_validate_dni_checkout_field' );

// Save DNI/CUIT field to Order Meta and Customer User Meta
function franco_wc_save_dni_checkout_field( $order, $data ) {
	if ( ! empty( $_POST['billing_dni'] ) ) {
		$dni = sanitize_text_field( wp_unslash( $_POST['billing_dni'] ) );
		$order->update_meta_data( '_billing_dni', $dni );
		$user_id = $order->get_customer_id();
		if ( $user_id ) {
			update_user_meta( $user_id, 'billing_dni', $dni );
		}
	}
}
add_action( 'woocommerce_checkout_create_order', 'franco_wc_save_dni_checkout_field', 10, 2 );

// Save DNI/CUIT when customer updates account details
function franco_wc_save_dni_account_details( $user_id ) {
	if ( isset( $_POST['billing_dni'] ) ) {
		update_user_meta( $user_id, 'billing_dni', sanitize_text_field( wp_unslash( $_POST['billing_dni'] ) ) );
	}
}
add_action( 'woocommerce_save_account_details', 'franco_wc_save_dni_account_details', 10, 1 );

// Display DNI in WP Admin -> WooCommerce -> Orders
function franco_wc_display_dni_in_admin_order( $order ) {
	$dni = $order->get_meta( '_billing_dni' );
	if ( $dni ) {
		echo '<p><strong>' . esc_html__( 'DNI / CUIT:', 'franco-theme' ) . '</strong> ' . esc_html( $dni ) . '</p>';
	}
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'franco_wc_display_dni_in_admin_order', 10, 1 );

// Display DNI in Customer Emails
function franco_wc_display_dni_in_emails( $order, $sent_to_admin, $plain_text, $email ) {
	$dni = $order->get_meta( '_billing_dni' );
	if ( $dni ) {
		echo '<p><strong>' . esc_html__( 'DNI / CUIT:', 'franco-theme' ) . '</strong> ' . esc_html( $dni ) . '</p>';
	}
}
add_action( 'woocommerce_email_customer_details', 'franco_wc_display_dni_in_emails', 15, 4 );


/**
 * 1b. Native WooCommerce checkout processing helpers
 *
 * The custom checkout form posts to WooCommerce's own checkout endpoint (wc-ajax=checkout),
 * so validation, shipping, payment gateway and order creation are handled by WooCommerce.
 */

/**
 * Whether a shipping rate id belongs to a local pickup method.
 *
 * @param string $rate_id Shipping rate id (e.g. "local_pickup:3").
 * @return bool
 */
function franco_wc_is_pickup_rate( $rate_id ) {
	$rate_id = (string) $rate_id;
	return 0 === strpos( $rate_id, 'local_pickup' ) || 0 === strpos( $rate_id, 'pickup_location' );
}

/**
 * Get the shipping rate id posted during checkout processing.
 *
 * @return string
 */
function franco_wc_get_posted_shipping_rate_id() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before reading fields.
	if ( ! isset( $_POST['shipping_method'] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$posted = wc_clean( wp_unslash( $_POST['shipping_method'] ) );
	if ( is_array( $posted ) ) {
		$posted = reset( $posted );
	}

	return is_string( $posted ) ? $posted : '';
}

/**
 * Adjust checkout fields: the full name lives in billing_first_name (last name is optional and
 * split server-side), and address fields are optional for local pickup.
 */
function franco_wc_adjust_checkout_fields( $fields ) {
	if ( isset( $fields['billing']['billing_last_name'] ) ) {
		$fields['billing']['billing_last_name']['required'] = false;
	}

	$rate_id = franco_wc_get_posted_shipping_rate_id();
	if ( '' !== $rate_id && franco_wc_is_pickup_rate( $rate_id ) ) {
		foreach ( array( 'billing_address_1', 'billing_city', 'billing_postcode', 'billing_state' ) as $key ) {
			if ( isset( $fields['billing'][ $key ] ) ) {
				$fields['billing'][ $key ]['required'] = false;
			}
		}
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'franco_wc_adjust_checkout_fields', 20 );

/**
 * Split the full name into first/last name and mirror billing data into shipping
 * when the customer is not shipping to a different address.
 */
function franco_wc_filter_checkout_posted_data( $data ) {
	$first = isset( $data['billing_first_name'] ) ? trim( (string) $data['billing_first_name'] ) : '';
	$last  = isset( $data['billing_last_name'] ) ? trim( (string) $data['billing_last_name'] ) : '';

	if ( '' === $last && '' !== $first ) {
		$parts = preg_split( '/\s+/', $first, 2 );

		$data['billing_first_name'] = $parts[0];
		$data['billing_last_name']  = isset( $parts[1] ) ? $parts[1] : '';
	}

	if ( empty( $data['ship_to_different_address'] ) ) {
		foreach ( array( 'first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) as $field ) {
			if ( empty( $data[ 'shipping_' . $field ] ) && isset( $data[ 'billing_' . $field ] ) ) {
				$data[ 'shipping_' . $field ] = $data[ 'billing_' . $field ];
			}
		}
	}

	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'franco_wc_filter_checkout_posted_data' );


/**
 * 2. Product size helpers (sizes are real pa_talle variation attributes)
 */

/**
 * Normalize product size (talle) format.
 * Converts 3-digit formats (e.g. 400 -> 40, 405 -> 40.5, 355 -> 35.5, 380 -> 38, 440 -> 44)
 * to standard format while preserving regular sizes (e.g. 40, 40.5, 38, 44).
 *
 * @param string|int|float $talle Size string or number.
 * @return string Normalized size string.
 */
function franco_normalize_talle( $talle ) {
	if ( null === $talle || '' === $talle ) {
		return '';
	}

	$talle_str = str_replace( ',', '.', trim( (string) $talle ) );

	if ( ctype_digit( $talle_str ) ) {
		$num = (int) $talle_str;
		if ( $num >= 150 && $num <= 600 ) {
			$norm = $num / 10;
			return (string) (float) $norm;
		}
	}

	return $talle_str;
}

/**
 * Get the sizes of a variable product from its pa_talle variations.
 *
 * @param WC_Product|false $product Product.
 * @return array[] List of array( 'slug' => string, 'name' => string ). Empty for non-variable products.
 */
function franco_wc_get_product_sizes( $product ) {
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return array();
	}

	$attributes = $product->get_variation_attributes();
	if ( empty( $attributes['pa_talle'] ) ) {
		return array();
	}

	$sizes           = array();
	$seen_normalized = array();

	foreach ( $attributes['pa_talle'] as $slug ) {
		$raw_label  = franco_wc_get_size_label( $slug );
		$norm_label = franco_normalize_talle( $raw_label );

		if ( isset( $seen_normalized[ $norm_label ] ) ) {
			continue;
		}
		$seen_normalized[ $norm_label ] = true;

		$sizes[] = array(
			'slug' => (string) $slug,
			'name' => $norm_label,
		);
	}

	usort( $sizes, function( $a, $b ) {
		$num_a = is_numeric( $a['name'] ) ? (float) $a['name'] : null;
		$num_b = is_numeric( $b['name'] ) ? (float) $b['name'] : null;
		if ( null !== $num_a && null !== $num_b ) {
			return $num_a <=> $num_b;
		}
		return strnatcasecmp( $a['name'], $b['name'] );
	} );

	return $sizes;
}

/**
 * Map a pa_talle term slug to its display name.
 *
 * @param string $slug Term slug.
 * @return string
 */
function franco_wc_get_size_label( $slug ) {
	$term = get_term_by( 'slug', $slug, 'pa_talle' );
	if ( ! $term || is_wp_error( $term ) ) {
		$term = get_term_by( 'name', $slug, 'pa_talle' );
	}

	$label = ( $term && ! is_wp_error( $term ) ) ? $term->name : (string) $slug;
	return franco_normalize_talle( $label );
}

/**
 * Resolve a selected size (and optional color) to one unambiguous product variation.
 *
 * @param WC_Product_Variable $product    Variable product.
 * @param string              $size_slug  pa_talle term slug.
 * @param string              $color_slug pa_color term slug (optional).
 * @return int Matching variation id, or zero when no unique match exists.
 */
function franco_wc_find_size_variation( $product, $size_slug, $color_slug = '' ) {
	$size_key    = 'attribute_pa_talle';
	$color_key   = 'attribute_pa_color';
	$candidates  = array();
	$target_norm = franco_normalize_talle( $size_slug );

	foreach ( $product->get_visible_children() as $child_id ) {
		$attributes = wc_get_product_variation_attributes( $child_id );

		$has_size_key = array_key_exists( $size_key, $attributes );
		if ( $has_size_key ) {
			$attr_val  = (string) $attributes[ $size_key ];
			$attr_norm = franco_normalize_talle( $attr_val );
			$size_match = ( '' === $attr_val || (string) $size_slug === $attr_val || $target_norm === $attr_norm );
		} else {
			$size_match = true;
		}

		if ( ! $size_match ) {
			continue;
		}

		if ( $color_slug && array_key_exists( $color_key, $attributes ) ) {
			$c_val = (string) $attributes[ $color_key ];
			if ( '' !== $c_val && $color_slug !== $c_val && sanitize_title( $color_slug ) !== sanitize_title( $c_val ) ) {
				continue;
			}
		}

		$candidates[] = (int) $child_id;
	}

	if ( count( $candidates ) > 1 && $color_slug ) {
		foreach ( $candidates as $cid ) {
			$attrs = wc_get_product_variation_attributes( $cid );
			if ( isset( $attrs[ $color_key ] ) && ( $color_slug === $attrs[ $color_key ] || sanitize_title( $color_slug ) === sanitize_title( $attrs[ $color_key ] ) ) ) {
				return $cid;
			}
		}
	}

	if ( count( $candidates ) > 1 ) {
		foreach ( $candidates as $cid ) {
			$attrs = wc_get_product_variation_attributes( $cid );
			if ( isset( $attrs[ $size_key ] ) && (string) $attrs[ $size_key ] === (string) $size_slug ) {
				return $cid;
			}
		}
		return $candidates[0];
	}

	return 1 === count( $candidates ) ? $candidates[0] : 0;
}

/**
 * WooCommerce Filters for Attribute Display Normalization
 */
add_filter( 'woocommerce_attribute', function( $text, $attribute, $values ) {
	if ( is_a( $attribute, 'WC_Product_Attribute' ) && ( 'pa_talle' === $attribute->get_name() || 'talle' === $attribute->get_name() ) ) {
		if ( ! empty( $values ) ) {
			$norm = array();
			foreach ( $values as $v ) {
				$val_str = is_object( $v ) ? $v->name : (string) $v;
				$norm[]  = franco_normalize_talle( $val_str );
			}
			$norm = array_unique( $norm );
			usort( $norm, function( $a, $b ) {
				$num_a = is_numeric( $a ) ? (float) $a : null;
				$num_b = is_numeric( $b ) ? (float) $b : null;
				if ( null !== $num_a && null !== $num_b ) {
					return $num_a <=> $num_b;
				}
				return strnatcasecmp( $a, $b );
			} );
			return wptexturize( implode( ', ', $norm ) );
		}
	}
	return $text;
}, 10, 3 );

add_filter( 'woocommerce_variation_option_name', function( $name, $term, $attribute, $product ) {
	if ( 'pa_talle' === $attribute || 'talle' === $attribute ) {
		return franco_normalize_talle( $name );
	}
	return $name;
}, 10, 4 );

/**
 * Get product color attributes (from taxonomy pa_color or custom attribute 'Color').
 *
 * @param WC_Product $product WooCommerce product.
 * @return array Array of color items.
 */
function franco_wc_get_product_colors( $product ) {
	$colors = array();

	if ( ! $product ) {
		return $colors;
	}

	$taxonomies = array( 'pa_color', 'pa_colors', 'pa_color-opcion' );
	foreach ( $taxonomies as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			$terms = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$hex = get_term_meta( $term->term_id, 'color', true ) ?: get_term_meta( $term->term_id, 'pa_color_hex', true );
					if ( ! $hex ) {
						$hex = franco_slug_to_hex( $term->slug );
					}
					$colors[] = array(
						'name' => $term->name,
						'slug' => $term->slug,
						'hex'  => $hex,
					);
				}
				return $colors;
			}
		}
	}

	$attributes = $product->get_attributes();
	foreach ( $attributes as $attr_name => $attr_obj ) {
		$name_lower = strtolower( $attr_name );
		if ( false !== strpos( $name_lower, 'color' ) ) {
			if ( is_object( $attr_obj ) && method_exists( $attr_obj, 'get_options' ) ) {
				$options = $attr_obj->get_options();
				if ( ! empty( $options ) ) {
					foreach ( $options as $option ) {
						if ( is_string( $option ) ) {
							$colors[] = array(
								'name' => $option,
								'slug' => sanitize_title( $option ),
								'hex'  => franco_slug_to_hex( sanitize_title( $option ) ),
							);
						}
					}
				}
			}
		}
	}

	return $colors;
}

function franco_slug_to_hex( $slug ) {
	$slug = strtolower( trim( (string) $slug ) );
	$color_map = array(
		'blanco'   => '#ffffff',
		'white'    => '#ffffff',
		'negro'    => '#0f172a',
		'black'    => '#0f172a',
		'rojo'     => '#e11d48',
		'red'      => '#e11d48',
		'azul'     => '#2563eb',
		'blue'     => '#2563eb',
		'gris'     => '#64748b',
		'grey'     => '#64748b',
		'gray'     => '#64748b',
		'verde'    => '#16a34a',
		'green'    => '#16a34a',
		'amarillo' => '#eab308',
		'yellow'   => '#eab308',
		'rosa'     => '#ec4899',
		'pink'     => '#ec4899',
		'violeta'  => '#8b5cf6',
		'purple'   => '#8b5cf6',
		'naranja'  => '#f97316',
		'orange'   => '#f97316',
		'marron'   => '#78350f',
		'brown'    => '#78350f',
		'beige'    => '#fef3c7',
	);
	return isset( $color_map[ $slug ] ) ? $color_map[ $slug ] : '#94a3b8';
}

/**
 * Human readable shipping cost for the current cart.
 *
 * @return string
 */
function franco_wc_get_shipping_label() {
	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return __( 'No requiere envío', 'franco-theme' );
	}

	$total = (float) WC()->cart->get_shipping_total();
	if ( WC()->cart->display_prices_including_tax() ) {
		$total += (float) WC()->cart->get_shipping_tax();
	}

	if ( $total > 0 ) {
		return franco_clean_price( wc_price( $total ) );
	}

	return __( 'GRATIS', 'franco-theme' );
}


/**
 * 3. Formatted Cart Data Helper (Cleans HTML Entities like &#36;&nbsp;0,00)
 *
 * Read-only: totals are recalculated by the mutating endpoints, not here.
 */
function franco_wc_get_formatted_cart_data() {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		return array(
			'success' => false,
			'message' => __( 'Carrito de WooCommerce no disponible.', 'franco-theme' ),
		);
	}

	$items = array();
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

		if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

			$size = __( 'Único', 'franco-theme' );
			if ( ! empty( $cart_item['variation']['attribute_pa_talle'] ) ) {
				$size = franco_wc_get_size_label( $cart_item['variation']['attribute_pa_talle'] );
			}

			$items[] = array(
				'key'             => $cart_item_key,
				'product_id'      => $product_id,
				'variation_id'    => $cart_item['variation_id'],
				'name'            => $_product->get_name(),
				'price'           => $_product->get_price(),
				'price_html'      => franco_clean_price( wc_price( $_product->get_price() ) ),
				'line_total'      => $cart_item['line_total'],
				'line_total_html' => franco_clean_price( wc_price( $cart_item['line_total'] ) ),
				'qty'             => $cart_item['quantity'],
				'image'           => get_the_post_thumbnail_url( $product_id, 'thumbnail' ) ?: wc_placeholder_img_src(),
				'permalink'       => $product_permalink,
				'size'            => $size,
				'item_code'       => function_exists( 'franco_get_product_item_code' ) ? franco_get_product_item_code( $_product ) : '',
			);
		}
	}

	return array(
		'success'        => true,
		'items'          => $items,
		'count'          => WC()->cart->get_cart_contents_count(),
		'subtotal'       => franco_clean_price( WC()->cart->get_cart_subtotal() ),
		'shipping_total' => franco_clean_price( wc_price( WC()->cart->get_shipping_total() ) ),
		'shipping_label' => franco_wc_get_shipping_label(),
		'discount_total' => franco_clean_price( wc_price( WC()->cart->get_discount_total() ) ),
		'tax_total'      => franco_clean_price( wc_price( WC()->cart->get_taxes_total() ) ),
		'total'          => franco_clean_price( WC()->cart->get_cart_total() ),
		'order_total'    => franco_clean_price( WC()->cart->get_total() ),
		'total_raw'      => WC()->cart->get_total( 'edit' ),
	);
}

/**
 * AJAX: Fetch Cart Data (read-only, no nonce required; returns a fresh nonce for mutating calls)
 */
function franco_wc_ajax_get_cart() {
	$data = franco_wc_get_formatted_cart_data();

	$data['nonce'] = wp_create_nonce( 'franco_wc_nonce' );

	wp_send_json( $data );
}
add_action( 'wp_ajax_franco_get_cart', 'franco_wc_ajax_get_cart' );
add_action( 'wp_ajax_nopriv_franco_get_cart', 'franco_wc_ajax_get_cart' );

/**
 * AJAX: Add Product to WooCommerce Cart
 */
function franco_wc_ajax_add_to_cart() {
	check_ajax_referer( 'franco_wc_nonce', 'nonce' );

	$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
	$size_slug    = isset( $_POST['size'] ) ? sanitize_title( wp_unslash( $_POST['size'] ) ) : '';
	$color_slug   = isset( $_POST['color'] ) ? sanitize_title( wp_unslash( $_POST['color'] ) ) : '';

	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => __( 'ID de producto no válido.', 'franco-theme' ) ) );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_purchasable() || ! $product->is_type( array( 'simple', 'variable' ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Producto no válido.', 'franco-theme' ) ) );
	}

	$variation = array();

	if ( $product->is_type( 'variable' ) ) {
		$size_key  = 'attribute_pa_talle';
		$color_key = 'attribute_pa_color';

		if ( $variation_id ) {
			$variation_product = wc_get_product( $variation_id );
			if ( ! $variation_product || (int) $variation_product->get_parent_id() !== $product_id ) {
				wp_send_json_error( array( 'message' => __( 'Seleccioná un talle válido.', 'franco-theme' ) ) );
			}

			$variation = wc_get_product_variation_attributes( $variation_id );
		} elseif ( $size_slug ) {
			$variation_id = franco_wc_find_size_variation( $product, $size_slug, $color_slug );
			$variation    = $variation_id ? wc_get_product_variation_attributes( $variation_id ) : array();
		}

		if ( ! $variation_id ) {
			wp_send_json_error( array( 'message' => __( 'Seleccioná un talle válido.', 'franco-theme' ) ) );
		}

		// Ensure variation attribute keys are populated for size & color
		if ( empty( $variation[ $size_key ] ) && $size_slug ) {
			$variation[ $size_key ] = $size_slug;
		}

		if ( empty( $variation[ $color_key ] ) && $color_slug ) {
			$variation[ $color_key ] = $color_slug;
		} elseif ( empty( $variation[ $color_key ] ) ) {
			$product_colors = franco_wc_get_product_colors( $product );
			if ( ! empty( $product_colors ) ) {
				$variation[ $color_key ] = $product_colors[0]['slug'];
			}
		}

		// Populate $_POST and $_REQUEST with all variation attributes so WooCommerce core validation passes
		foreach ( $variation as $attr_key => $attr_val ) {
			if ( ! empty( $attr_val ) ) {
				$_POST[ $attr_key ]    = $attr_val;
				$_REQUEST[ $attr_key ] = $attr_val;
			}
		}
	} else {
		// Simple products have no size: never accept free text.
		$variation_id = 0;
	}

	$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );

	if ( ! $passed_validation ) {
		$message = __( 'Validación de producto fallida.', 'franco-theme' );
		$notices = wc_get_notices( 'error' );
		if ( ! empty( $notices ) ) {
			$first   = reset( $notices );
			$message = wp_strip_all_tags( is_array( $first ) && isset( $first['notice'] ) ? $first['notice'] : (string) $first );
		}
		wc_clear_notices();
		wp_send_json_error( array( 'message' => $message ) );
	}

	$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

	if ( ! $added ) {
		$message = __( 'No se pudo agregar el producto al carrito.', 'franco-theme' );
		$notices = wc_get_notices( 'error' );
		if ( ! empty( $notices ) ) {
			$first   = reset( $notices );
			$message = wp_strip_all_tags( is_array( $first ) && isset( $first['notice'] ) ? $first['notice'] : (string) $first );
		}
		wc_clear_notices();
		wp_send_json_error( array( 'message' => $message ) );
	}

	wc_clear_notices();
	WC()->cart->calculate_totals();

	$response                    = franco_wc_get_formatted_cart_data();
	$response['added_item_name'] = $product->get_name();
	wp_send_json_success( $response );
}
add_action( 'wp_ajax_franco_add_to_cart', 'franco_wc_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_franco_add_to_cart', 'franco_wc_ajax_add_to_cart' );

/**
 * AJAX: Update Cart Item Quantity
 */
function franco_wc_ajax_update_cart_qty() {
	check_ajax_referer( 'franco_wc_nonce', 'nonce' );

	$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
	$quantity      = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 0;

	if ( ! $cart_item_key ) {
		wp_send_json_error( array( 'message' => __( 'Clave de ítem no válida.', 'franco-theme' ) ) );
	}

	$cart_item = WC()->cart->get_cart_item( $cart_item_key );
	if ( empty( $cart_item ) ) {
		wp_send_json_error( array( 'message' => __( 'Clave de ítem no válida.', 'franco-theme' ) ) );
	}

	if ( $quantity <= 0 ) {
		WC()->cart->remove_cart_item( $cart_item_key );
	} else {
		$cart_product = $cart_item['data'];

		if ( $cart_product->is_sold_individually() ) {
			$quantity = 1;
		}

		$max_quantity = $cart_product->get_max_purchase_quantity();
		if ( $max_quantity > 0 ) {
			$quantity = min( $quantity, $max_quantity );
		}

		WC()->cart->set_quantity( $cart_item_key, $quantity, true );
	}

	WC()->cart->calculate_totals();

	wp_send_json_success( franco_wc_get_formatted_cart_data() );
}
add_action( 'wp_ajax_franco_update_cart_qty', 'franco_wc_ajax_update_cart_qty' );
add_action( 'wp_ajax_nopriv_franco_update_cart_qty', 'franco_wc_ajax_update_cart_qty' );

/**
 * AJAX: Remove Cart Item
 */
function franco_wc_ajax_remove_cart_item() {
	check_ajax_referer( 'franco_wc_nonce', 'nonce' );

	$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';

	if ( $cart_item_key && ! empty( WC()->cart->get_cart_item( $cart_item_key ) ) ) {
		WC()->cart->remove_cart_item( $cart_item_key );
		WC()->cart->calculate_totals();
		wp_send_json_success( franco_wc_get_formatted_cart_data() );
	} else {
		wp_send_json_error( array( 'message' => __( 'Clave de carrito inválida.', 'franco-theme' ) ) );
	}
}
add_action( 'wp_ajax_franco_remove_cart_item', 'franco_wc_ajax_remove_cart_item' );
add_action( 'wp_ajax_nopriv_franco_remove_cart_item', 'franco_wc_ajax_remove_cart_item' );

/**
 * AJAX: Empty the entire WooCommerce Cart
 */
function franco_wc_ajax_clear_cart() {
	check_ajax_referer( 'franco_wc_nonce', 'nonce' );

	if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
		WC()->cart->empty_cart();
		WC()->cart->calculate_totals();
		wp_send_json_success( franco_wc_get_formatted_cart_data() );
	} else {
		wp_send_json_error( array( 'message' => __( 'No se pudo vaciar el carrito.', 'franco-theme' ) ) );
	}
}
add_action( 'wp_ajax_franco_clear_cart', 'franco_wc_ajax_clear_cart' );
add_action( 'wp_ajax_nopriv_franco_clear_cart', 'franco_wc_ajax_clear_cart' );

/**
 * AJAX: Set the chosen shipping method and return refreshed totals
 */
function franco_wc_ajax_set_shipping_method() {
	check_ajax_referer( 'franco_wc_nonce', 'nonce' );

	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		wp_send_json_error( array( 'message' => __( 'Tu carrito está vacío.', 'franco-theme' ) ) );
	}

	$rate_id       = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : '';
	$package_index = isset( $_POST['package'] ) ? absint( $_POST['package'] ) : 0;

	WC()->cart->calculate_shipping();
	$packages = WC()->shipping()->get_packages();

	if ( ! is_string( $rate_id ) || ! isset( $packages[ $package_index ]['rates'][ $rate_id ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Método de envío no válido.', 'franco-theme' ) ) );
	}

	$chosen                   = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$chosen[ $package_index ] = $rate_id;
	WC()->session->set( 'chosen_shipping_methods', $chosen );

	WC()->cart->calculate_totals();

	wp_send_json_success( franco_wc_get_formatted_cart_data() );
}
add_action( 'wp_ajax_franco_set_shipping_method', 'franco_wc_ajax_set_shipping_method' );
add_action( 'wp_ajax_nopriv_franco_set_shipping_method', 'franco_wc_ajax_set_shipping_method' );

/**
 * 5. Live Search WooCommerce Products Endpoint (In-Stock Products Only)
 */
function franco_wc_ajax_live_search() {
	// Read-only public endpoint: no nonce (a stale cached nonce would break search).
	$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$query = mb_substr( $query, 0, 100 );

	if ( empty( $query ) ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'no_found_rows'  => true,
		's'              => $query,
		'meta_query'     => array(
			array(
				'key'     => '_stock_status',
				'value'   => 'instock',
				'compare' => '=',
			),
		),
	);

	$loop    = new WP_Query( $args );
	$results = array();

	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) {
			$loop->the_post();
			global $product;

			if ( $product && $product->is_in_stock() ) {
				$results[] = array(
					'id'        => get_the_ID(),
					'name'      => get_the_title(),
					'price'     => franco_clean_price( wc_price( $product->get_price() ) ),
					'price_old' => $product->is_on_sale() ? franco_clean_price( wc_price( $product->get_regular_price() ) ) : '',
					'image'     => get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ) ?: wc_placeholder_img_src(),
					'permalink' => get_permalink(),
				);
			}
		}
		wp_reset_postdata();
	}

	wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_franco_live_search', 'franco_wc_ajax_live_search' );
add_action( 'wp_ajax_nopriv_franco_live_search', 'franco_wc_ajax_live_search' );

/**
 * AJAX: Fetch visible subcategories for a given parent category
 */
function franco_wc_ajax_get_subcategories() {
	$parent_cat = isset( $_POST['parent_category'] ) ? sanitize_text_field( wp_unslash( $_POST['parent_category'] ) ) : '';

	if ( empty( $parent_cat ) || 'all' === $parent_cat || 'ofertas' === $parent_cat ) {
		wp_send_json_success( array( 'subcategories' => array() ) );
	}

	$subcats   = franco_get_visible_subcategories( $parent_cat );
	$formatted = array();

	if ( ! empty( $subcats ) ) {
		foreach ( $subcats as $sc ) {
			$formatted[] = array(
				'slug' => $sc->slug,
				'name' => $sc->name,
			);
		}
	}

	wp_send_json_success( array( 'subcategories' => $formatted ) );
}
add_action( 'wp_ajax_franco_get_subcategories', 'franco_wc_ajax_get_subcategories' );
add_action( 'wp_ajax_nopriv_franco_get_subcategories', 'franco_wc_ajax_get_subcategories' );

/**
 * 6. Dynamic AJAX Product Filtering Endpoint (In-Stock Only & Categories with Products)
 */
function franco_wc_ajax_filter_products() {
	// Read-only public endpoint: no nonce (a stale cached nonce would break filtering).
	$category    = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'all';
	$subcategory = isset( $_POST['subcategory'] ) ? sanitize_text_field( wp_unslash( $_POST['subcategory'] ) ) : 'all';
	$brand       = isset( $_POST['brand'] ) ? sanitize_text_field( wp_unslash( $_POST['brand'] ) ) : 'all';
	$talle       = isset( $_POST['talle'] ) ? sanitize_text_field( wp_unslash( $_POST['talle'] ) ) : 'all';
	$sexo        = isset( $_POST['sexo'] ) ? sanitize_text_field( wp_unslash( $_POST['sexo'] ) ) : 'all';
	$color       = isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : 'all';
	$min_price   = isset( $_POST['min_price'] ) ? floatval( $_POST['min_price'] ) : 0;
	$max_price   = isset( $_POST['max_price'] ) ? floatval( $_POST['max_price'] ) : 0;
	$sort_by     = isset( $_POST['sort_by'] ) ? sanitize_text_field( wp_unslash( $_POST['sort_by'] ) ) : 'popular';
	$search_term = isset( $_POST['search'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['search'] ) ), 0, 100 ) : '';
	$paged       = isset( $_POST['page'] ) ? min( max( 1, absint( $_POST['page'] ) ), 50 ) : 1;
	$context     = ( isset( $_POST['context'] ) && 'home' === sanitize_key( wp_unslash( $_POST['context'] ) ) ) ? 'home' : 'shop';

	// Per-page is decided server-side. The shop context mirrors the main catalog query so pagination lines up.
	if ( 'home' === $context ) {
		$limit = 8;
	} else {
		$limit = (int) apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() );
		$limit = $limit > 0 ? min( $limit, 48 ) : 12;
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'paged'          => $paged,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => '_stock_status',
				'value'   => 'instock',
				'compare' => '=',
			),
		),
	);

	// Search Term Filter
	if ( ! empty( $search_term ) ) {
		$args['s'] = $search_term;
	}

	// Category Tax Query (Subcategory overrides parent category if selected)
	$cat_to_filter = ( $subcategory && 'all' !== $subcategory ) ? $subcategory : $category;

	if ( $cat_to_filter && 'all' !== $cat_to_filter ) {
		if ( 'ofertas' === $cat_to_filter ) {
			$on_sale_ids      = wc_get_product_ids_on_sale();
			$args['post__in'] = ! empty( $on_sale_ids ) ? $on_sale_ids : array( 0 );
		} else {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $cat_to_filter,
			);
		}
	}

	// Brand Tax Query ("marca" or "pa_marca")
	if ( $brand && 'all' !== $brand ) {
		$brand_tax = taxonomy_exists( 'pa_marca' ) ? 'pa_marca' : ( taxonomy_exists( 'marca' ) ? 'marca' : 'pa_marca' );
		$args['tax_query'][] = array(
			'taxonomy' => $brand_tax,
			'field'    => 'slug',
			'terms'    => $brand,
		);
	}

	// Talle Tax Query ("talle" or "pa_talle")
	if ( $talle && 'all' !== $talle ) {
		$talle_tax      = taxonomy_exists( 'pa_talle' ) ? 'pa_talle' : ( taxonomy_exists( 'talle' ) ? 'talle' : 'pa_talle' );
		$target_norm    = franco_normalize_talle( $talle );
		$matching_terms = array( $talle, $target_norm );

		if ( taxonomy_exists( $talle_tax ) ) {
			$all_terms = get_terms( array(
				'taxonomy'   => $talle_tax,
				'hide_empty' => false,
			) );
			if ( ! is_wp_error( $all_terms ) && ! empty( $all_terms ) ) {
				foreach ( $all_terms as $term_obj ) {
					if ( franco_normalize_talle( $term_obj->name ) === $target_norm || franco_normalize_talle( $term_obj->slug ) === $target_norm ) {
						$matching_terms[] = $term_obj->slug;
						$matching_terms[] = $term_obj->name;
					}
				}
			}
		}

		$matching_terms = array_unique( array_filter( $matching_terms ) );

		$args['tax_query'][] = array(
			'taxonomy' => $talle_tax,
			'field'    => 'slug',
			'terms'    => $matching_terms,
		);
	}

	// Sexo Tax Query ("sexo" or "pa_sexo")
	if ( $sexo && 'all' !== $sexo ) {
		$sexo_tax = taxonomy_exists( 'pa_sexo' ) ? 'pa_sexo' : ( taxonomy_exists( 'sexo' ) ? 'sexo' : 'pa_sexo' );
		$args['tax_query'][] = array(
			'taxonomy' => $sexo_tax,
			'field'    => 'slug',
			'terms'    => $sexo,
		);
	}

	// Color Tax Query ("color" or "pa_color")
	if ( $color && 'all' !== $color ) {
		$color_tax = taxonomy_exists( 'pa_color' ) ? 'pa_color' : ( taxonomy_exists( 'color' ) ? 'color' : 'pa_color' );
		$args['tax_query'][] = array(
			'taxonomy' => $color_tax,
			'field'    => 'slug',
			'terms'    => $color,
		);
	}

	if ( ! empty( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
		$args['tax_query']['relation'] = 'AND';
	}

	// Min & Max Price Meta Query
	if ( $min_price > 0 && $max_price > 0 ) {
		$args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => array( $min_price, $max_price ),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
	} elseif ( $min_price > 0 ) {
		$args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => $min_price,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	} elseif ( $max_price > 0 ) {
		$args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => $max_price,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	// Ordering. On the home page, "popular" shows random products. On the shop, it uses catalog default order.
	if ( 'home' === $context && 'popular' === $sort_by ) {
		$args['orderby'] = 'rand';
	} elseif ( 'shop' === $context && 'popular' === $sort_by && function_exists( 'WC' ) && WC()->query ) {
		$ordering = WC()->query->get_catalog_ordering_args();
		if ( ! empty( $ordering['orderby'] ) ) {
			$args['orderby'] = $ordering['orderby'];
			$args['order']   = ! empty( $ordering['order'] ) ? $ordering['order'] : 'ASC';
			if ( ! empty( $ordering['meta_key'] ) ) {
				$args['meta_key'] = $ordering['meta_key'];
			}
		}
	} elseif ( 'price-low' === $sort_by ) {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = '_price';
		$args['order']    = 'ASC';
	} elseif ( 'price-high' === $sort_by ) {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = '_price';
		$args['order']    = 'DESC';
	} elseif ( 'name' === $sort_by ) {
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
	}

	$loop = new WP_Query( $args );

	ob_start();
	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) {
			$loop->the_post();
			global $product;
			if ( $product && $product->is_in_stock() ) {
				wc_get_template_part( 'content', 'product' );
			}
		}
		wp_reset_postdata();
	} else {
		if ( 1 === $paged ) {
			echo '<div class="products-empty">';
			echo '<i class="fa-solid fa-magnifying-glass products-empty-icon"></i>';
			echo '<h3 class="products-empty-title">' . esc_html__( 'No se encontraron productos disponibles', 'franco-theme' ) . '</h3>';
			echo '<p class="text-muted">' . esc_html__( 'Prueba ajustando los filtros de búsqueda, categoría o marca.', 'franco-theme' ) . '</p>';
			echo '</div>';
		}
	}
	$html = ob_get_clean();

	$has_more = ( $loop->max_num_pages > $paged );

	wp_send_json_success( array(
		'html'     => $html,
		'has_more' => $has_more,
		'count'    => sprintf( __( 'Mostrando %d productos', 'franco-theme' ), $loop->found_posts ),
	) );
}
add_action( 'wp_ajax_franco_filter_products', 'franco_wc_ajax_filter_products' );
add_action( 'wp_ajax_nopriv_franco_filter_products', 'franco_wc_ajax_filter_products' );

/**
 * 7. Quick View Product Details Endpoint
 */
function franco_wc_ajax_get_quick_view_product() {
	// Read-only public endpoint: no nonce.
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => __( 'Producto no encontrado.', 'franco-theme' ) ) );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() || post_password_required( $product_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Producto no válido.', 'franco-theme' ) ) );
	}

	$terms = get_the_terms( $product_id, 'product_cat' );
	$brand = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? mb_strtoupper( $terms[0]->name ) : 'FRANCO ATHLETICS';

	$post        = get_post( $product_id );
	$description = $post && $post->post_content ? $post->post_content : $product->get_short_description();

	$gallery = array();
	$main_img = get_the_post_thumbnail_url( $product_id, 'medium_large' ) ?: wc_placeholder_img_src();
	if ( $main_img ) {
		$gallery[] = $main_img;
	}
	$attachment_ids = $product->get_gallery_image_ids();
	if ( ! empty( $attachment_ids ) ) {
		foreach ( $attachment_ids as $att_id ) {
			$att_url = wp_get_attachment_image_url( $att_id, 'medium_large' );
			if ( $att_url && ! in_array( $att_url, $gallery, true ) ) {
				$gallery[] = $att_url;
			}
		}
	}

	$data = array(
		'id'          => $product_id,
		'name'        => $product->get_name(),
		'brand'       => $brand,
		'price'       => franco_clean_price( wc_price( $product->get_price() ) ),
		'price_old'   => $product->is_on_sale() ? franco_clean_price( wc_price( $product->get_regular_price() ) ) : '',
		'image'       => $main_img,
		'gallery'     => $gallery,
		'description' => wp_trim_words( wp_strip_all_tags( strip_shortcodes( $description ) ), 60 ),
		'sizes'       => franco_wc_get_product_sizes( $product ),
		'colors'      => franco_wc_get_product_colors( $product ),
		'item_code'   => function_exists( 'franco_get_product_item_code' ) ? franco_get_product_item_code( $product ) : '',
		'is_variable' => $product->is_type( 'variable' ),
		'permalink'   => get_permalink( $product_id ),
	);

	wp_send_json_success( $data );
}
add_action( 'wp_ajax_franco_get_quick_view_product', 'franco_wc_ajax_get_quick_view_product' );
add_action( 'wp_ajax_nopriv_franco_get_quick_view_product', 'franco_wc_ajax_get_quick_view_product' );

/**
 * 8. Wishlist Products Endpoint
 */
/**
 * Read, sanitize and cap the wishlist product ids sent by the client.
 *
 * @return int[]
 */
function franco_wc_get_posted_wishlist_ids() {
	$product_ids = isset( $_POST['product_ids'] ) ? (array) wp_unslash( $_POST['product_ids'] ) : array();
	$product_ids = array_filter( array_map( 'absint', array_filter( $product_ids, 'is_scalar' ) ) );

	return array_values( array_slice( array_unique( $product_ids ), 0, 50 ) );
}

function franco_wc_ajax_get_wishlist_products() {
	// Read-only public endpoint: no nonce.
	$product_ids = franco_wc_get_posted_wishlist_ids();

	if ( empty( $product_ids ) ) {
		wp_send_json_success( array( 'html' => '' ) );
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'post__in'       => $product_ids,
		'posts_per_page' => count( $product_ids ),
		'orderby'        => 'post__in',
		'no_found_rows'  => true,
	);

	$loop = new WP_Query( $args );

	ob_start();
	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) {
			$loop->the_post();
			global $product;
			if ( $product && $product->is_in_stock() ) {
				wc_get_template_part( 'content', 'product' );
			}
		}
		wp_reset_postdata();
	}
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_franco_get_wishlist_products', 'franco_wc_ajax_get_wishlist_products' );
add_action( 'wp_ajax_nopriv_franco_get_wishlist_products', 'franco_wc_ajax_get_wishlist_products' );

/**
 * 9. Wishlist In-Stock Count Endpoint (Filters out out-of-stock items)
 */
function franco_wc_ajax_get_wishlist_count() {
	// Read-only public endpoint: no nonce.
	$product_ids = franco_wc_get_posted_wishlist_ids();

	if ( empty( $product_ids ) ) {
		wp_send_json_success( array( 'count' => 0 ) );
	}

	$query = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'post__in'       => $product_ids,
		'posts_per_page' => count( $product_ids ),
		'no_found_rows'  => true,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => '_stock_status',
				'value'   => 'instock',
				'compare' => '=',
			),
		),
	) );

	wp_send_json_success( array( 'count' => count( $query->posts ) ) );
}
add_action( 'wp_ajax_franco_get_wishlist_count', 'franco_wc_ajax_get_wishlist_count' );
add_action( 'wp_ajax_nopriv_franco_get_wishlist_count', 'franco_wc_ajax_get_wishlist_count' );

/**
 * 10. Guarantee WooCommerce Shortcodes and Templates execute on configured WooCommerce pages
 */
function franco_wc_ensure_page_templates( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$checkout_page_id = (int) get_option( 'woocommerce_checkout_page_id', get_option( 'franco_page_checkout', 0 ) );
	$cart_page_id     = (int) get_option( 'woocommerce_cart_page_id', get_option( 'franco_page_carrito', 0 ) );
	$myaccount_id     = (int) get_option( 'woocommerce_myaccount_page_id', get_option( 'franco_page_myaccount', 0 ) );

	if ( ( $checkout_page_id && is_page( $checkout_page_id ) ) || is_checkout() ) {
		if ( false === strpos( $content, '[woocommerce_checkout]' ) && false === strpos( $content, 'wp:woocommerce/checkout' ) ) {
			return $content . do_shortcode( '[woocommerce_checkout]' );
		}
	} elseif ( ( $cart_page_id && is_page( $cart_page_id ) ) || is_cart() ) {
		if ( false === strpos( $content, '[woocommerce_cart]' ) && false === strpos( $content, 'wp:woocommerce/cart' ) ) {
			return $content . do_shortcode( '[woocommerce_cart]' );
		}
	} elseif ( ( $myaccount_id && is_page( $myaccount_id ) ) || is_account_page() ) {
		if ( false === strpos( $content, '[woocommerce_my_account]' ) && false === strpos( $content, 'wp:woocommerce/my-account' ) ) {
			return $content . do_shortcode( '[woocommerce_my_account]' );
		}
	}

	return $content;
}
add_filter( 'the_content', 'franco_wc_ensure_page_templates', 25 );

/**
 * 11. Override template loading for WooCommerce Shop page if standard archive template is bypassed
 */
function franco_wc_custom_template_loader( $template ) {
	if ( is_admin() ) {
		return $template;
	}

	$shop_page_id = (int) get_option( 'woocommerce_shop_page_id', get_option( 'franco_page_tienda', 0 ) );

	if ( $shop_page_id && is_page( $shop_page_id ) && ! is_shop() ) {
		$theme_archive = get_stylesheet_directory() . '/woocommerce/archive-product.php';
		if ( file_exists( $theme_archive ) ) {
			return $theme_archive;
		}
	}

	return $template;
}
add_filter( 'template_include', 'franco_wc_custom_template_loader', 99 );

/**
 * 12. Helper to check if current view is Checkout
 */
function franco_is_checkout_page() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	$checkout_page_id = (int) get_option( 'woocommerce_checkout_page_id', get_option( 'franco_page_checkout', 0 ) );
	return is_checkout() || ( $checkout_page_id > 0 && is_page( $checkout_page_id ) );
}

/**
 * Helper to get My Account URL or specific endpoint URL
 */
function franco_get_myaccount_url( $endpoint = '' ) {
	$myaccount_id = (int) get_option( 'woocommerce_myaccount_page_id', get_option( 'franco_page_myaccount', 0 ) );
	$url = $myaccount_id ? get_permalink( $myaccount_id ) : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mi-cuenta' ) );
	if ( ! empty( $endpoint ) && function_exists( 'wc_get_account_endpoint_url' ) ) {
		return wc_get_account_endpoint_url( $endpoint );
	}
	return $url;
}

/**
 * Helper to check if current page is My Account
 */
function franco_is_account_page() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	$myaccount_id = (int) get_option( 'woocommerce_myaccount_page_id', get_option( 'franco_page_myaccount', 0 ) );
	return is_account_page() || ( $myaccount_id > 0 && is_page( $myaccount_id ) );
}

/**
 * Registro de usuarios: Respeta la configuración nativa de WooCommerce (Ajustes > Cuentas).
 * Si el registro está habilitado, añade protección Honeypot y Time-gate contra bots automatizados.
 */
function franco_registration_honeypot_fields() {
	$timestamp = time();
	$hash      = wp_hash( 'franco_reg_time_' . $timestamp );
	?>
	<div style="position: absolute !important; left: -9999px !important; width: 1px !important; height: 1px !important; overflow: hidden !important;" aria-hidden="true">
		<label for="franco_website_hp">Leave this field blank</label>
		<input type="text" name="franco_website_hp" id="franco_website_hp" value="" tabindex="-1" autocomplete="off" />
		<input type="hidden" name="franco_reg_ts" value="<?php echo esc_attr( $timestamp ); ?>" />
		<input type="hidden" name="franco_reg_tsh" value="<?php echo esc_attr( $hash ); ?>" />
	</div>
	<?php
}
add_action( 'woocommerce_register_form', 'franco_registration_honeypot_fields' );

function franco_validate_registration_honeypot( $errors, $username, $email ) {
	// 1. Si el registro está deshabilitado en WooCommerce pero se envió el formulario POST
	if ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) ) {
		$errors->add( 'registration-disabled', __( 'El registro de nuevas cuentas está deshabilitado en este momento.', 'franco-theme' ) );
		return $errors;
	}

	// 2. Comprobar campo Honeypot (debe estar completamente vacío)
	if ( ! empty( $_POST['franco_website_hp'] ) ) {
		$errors->add( 'spam-bot-detected', __( 'Error al procesar la solicitud. Por favor intenta nuevamente.', 'franco-theme' ) );
		return $errors;
	}

	// 3. Comprobar Time-gate (mínimo 2 segundos entre render y envío humano)
	if ( isset( $_POST['franco_reg_ts'], $_POST['franco_reg_tsh'] ) ) {
		$ts   = absint( $_POST['franco_reg_ts'] );
		$hash = sanitize_text_field( wp_unslash( $_POST['franco_reg_tsh'] ) );
		if ( wp_hash( 'franco_reg_time_' . $ts ) === $hash ) {
			if ( ( time() - $ts ) < 2 ) {
				$errors->add( 'spam-fast-submission', __( 'Envío demasiado rápido. Por favor intenta nuevamente.', 'franco-theme' ) );
				return $errors;
			}
		}
	}

	return $errors;
}
add_filter( 'woocommerce_process_registration_errors', 'franco_validate_registration_honeypot', 10, 3 );

/**
 * 11. Main shop / product taxonomy queries: in-stock only (matches the AJAX filter endpoint).
 *
 * @param WP_Query $q Main product query.
 */
function franco_wc_main_query_instock_only( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}

	if ( ! ( ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) ) {
		return;
	}

	$meta_query   = (array) $q->get( 'meta_query' );
	$meta_query[] = array(
		'key'     => '_stock_status',
		'value'   => 'instock',
		'compare' => '=',
	);
	$q->set( 'meta_query', $meta_query );
}
add_action( 'woocommerce_product_query', 'franco_wc_main_query_instock_only' );

/**
 * 12. Product structured data: add brand (pa_marca / marca) when WooCommerce did not provide one.
 *
 * @param array      $markup  Product schema markup.
 * @param WC_Product $product Product object.
 * @return array
 */
function franco_wc_structured_data_brand( $markup, $product ) {
	if ( ! is_array( $markup ) || ! empty( $markup['brand'] ) || ! $product instanceof WC_Product ) {
		return $markup;
	}

	foreach ( array( 'pa_marca', 'marca' ) as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$terms = get_the_terms( $product->get_id(), $taxonomy );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$term = reset( $terms );

			$markup['brand'] = array(
				'@type' => 'Brand',
				'name'  => $term->name,
			);
			break;
		}
	}

	return $markup;
}
add_filter( 'woocommerce_structured_data_product', 'franco_wc_structured_data_brand', 10, 2 );

/**
 * Ocultar el atributo Color en el front (carrito, checkout, títulos de variación y atributos).
 * Sigue existiendo como atributo en WordPress.
 */
function franco_is_color_attr_key( $key ) {
	$key = strtolower( str_replace( 'attribute_', '', (string) $key ) );
	return in_array( $key, array( 'pa_color', 'color', 'pa_colour', 'colour' ), true );
}

add_filter( 'woocommerce_get_item_data', function( $item_data ) {
	foreach ( $item_data as $i => $data ) {
		$label = isset( $data['key'] ) ? $data['key'] : ( isset( $data['name'] ) ? $data['name'] : '' );
		if ( in_array( strtolower( wp_strip_all_tags( $label ) ), array( 'color', 'colour' ), true ) ) {
			unset( $item_data[ $i ] );
		}
	}
	return $item_data;
}, 20 );

add_filter( 'woocommerce_order_item_get_formatted_meta_data', function( $meta ) {
	if ( is_admin() ) {
		return $meta;
	}
	foreach ( $meta as $id => $m ) {
		if ( franco_is_color_attr_key( $m->key ) ) {
			unset( $meta[ $id ] );
		}
	}
	return $meta;
}, 20 );

add_filter( 'woocommerce_display_product_attributes', function( $attrs ) {
	foreach ( $attrs as $key => $a ) {
		if ( franco_is_color_attr_key( str_replace( 'attribute_', '', $key ) ) ) {
			unset( $attrs[ $key ] );
		}
	}
	return $attrs;
}, 20 );

add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );

/**
 * 13. Save Product Item Code to Order Line Items and Order Meta
 */

// Add Item N° to order line item during checkout
function franco_add_item_code_to_order_line_item( $item, $cart_item_key, $values, $order ) {
	$product = $item->get_product();
	if ( ! $product || ! function_exists( 'franco_get_product_item_code' ) ) {
		return;
	}

	$code = franco_get_product_item_code( $product );
	if ( ! empty( $code ) ) {
		$item->add_meta_data( __( 'Item N°', 'franco-theme' ), $code, true );
		$item->add_meta_data( '_item_code', $code, true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'franco_add_item_code_to_order_line_item', 10, 4 );

// Add Item N° when an item is manually added to an order via WP Admin
function franco_ajax_add_order_item_code_meta( $item_id, $item ) {
	if ( $item instanceof WC_Order_Item_Product && function_exists( 'franco_get_product_item_code' ) ) {
		$product = $item->get_product();
		if ( $product ) {
			$code = franco_get_product_item_code( $product );
			if ( ! empty( $code ) ) {
				wc_add_order_item_meta( $item_id, __( 'Item N°', 'franco-theme' ), $code, true );
				wc_add_order_item_meta( $item_id, '_item_code', $code, true );
			}
		}
	}
}
add_action( 'woocommerce_ajax_add_order_item_meta', 'franco_ajax_add_order_item_code_meta', 10, 2 );

// Aggregate item codes into order meta upon order creation for admin search
function franco_save_order_aggregated_item_codes( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	$codes = array();
	foreach ( $order->get_items() as $item ) {
		$code = $item->get_meta( 'Item N°' ) ?: $item->get_meta( '_item_code' );
		if ( ! empty( $code ) && ! in_array( $code, $codes, true ) ) {
			$codes[] = $code;
		}
	}
	if ( ! empty( $codes ) ) {
		$order->update_meta_data( '_franco_order_item_codes', implode( ', ', $codes ) );
		$order->save();
	}
}
add_action( 'woocommerce_checkout_order_created', 'franco_save_order_aggregated_item_codes', 20, 1 );

// Support searching orders by Item Code in WP Admin (HPOS + Classic post meta)
add_filter( 'woocommerce_shop_order_search_fields', function( $fields ) {
	$fields[] = '_franco_order_item_codes';
	$fields[] = 'Item N°';
	$fields[] = '_item_code';
	return $fields;
} );

add_filter( 'woocommerce_order_table_search_query_meta_keys', function( $keys ) {
	$keys[] = '_franco_order_item_codes';
	$keys[] = 'Item N°';
	$keys[] = '_item_code';
	return $keys;
} );

// Display Item N° in standard WooCommerce cart item data (e.g. cart table fallback)
add_filter( 'woocommerce_get_item_data', function( $item_data, $cart_item ) {
	$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
	if ( $product && function_exists( 'franco_get_product_item_code' ) ) {
		$code = franco_get_product_item_code( $product );
		if ( ! empty( $code ) ) {
			$item_data[] = array(
				'key'     => __( 'Item N°', 'franco-theme' ),
				'value'   => $code,
				'display' => $code,
			);
		}
	}
	return $item_data;
}, 10, 2 );

// Fallback hook for single product summary (if standard WooCommerce templates are used)
add_action( 'woocommerce_single_product_summary', function() {
	global $product;
	if ( ! $product || ! function_exists( 'franco_get_product_item_code' ) ) {
		return;
	}
	$code = franco_get_product_item_code( $product );
	if ( ! empty( $code ) ) {
		echo '<div class="product-detail-item-code"><span>' . sprintf( esc_html__( 'Item N° %s', 'franco-theme' ), esc_html( $code ) ) . '</span></div>';
	}
}, 6 );

/**
 * 14. Customer Postal Code Management & Dynamic Shipping Calculation Handlers
 */

/**
 * Validar y sanitizar código postal de Argentina (4 números o CPA nuevo).
 *
 * @param string $postcode Código postal ingresado.
 * @return string Código postal sanitizado o string vacío si no es válido.
 */
function franco_validate_ar_postcode( $postcode ) {
	$clean = strtoupper( trim( preg_replace( '/[^A-Za-z0-9]/', '', (string) $postcode ) ) );
	if ( strlen( $clean ) < 4 || strlen( $clean ) > 8 ) {
		return '';
	}
	if ( ! preg_match( '/^[A-Z]?\d{4}[A-Z]{0,3}$/', $clean ) ) {
		return '';
	}
	return $clean;
}

/**
 * Guardar cookie segura para el código postal del visitante.
 *
 * @param string $postcode Código postal válido.
 */
function franco_set_postcode_cookie( $postcode ) {
	if ( headers_sent() ) {
		return;
	}
	$options = array(
		'expires'  => time() + 30 * DAY_IN_SECONDS,
		'path'     => COOKIEPATH ? COOKIEPATH : '/',
		'domain'   => COOKIE_DOMAIN,
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	);
	setcookie( 'franco_postcode', $postcode, $options );
}

// Automatically sync customer postcode from persistent cookie upon WooCommerce init
function franco_sync_customer_postcode_from_cookie() {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->customer ) {
		return;
	}
	$current_postcode = WC()->customer->get_shipping_postcode();
	if ( empty( $current_postcode ) && ! empty( $_COOKIE['franco_postcode'] ) ) {
		$cp = franco_validate_ar_postcode( wp_unslash( $_COOKIE['franco_postcode'] ) );
		if ( ! empty( $cp ) ) {
			WC()->customer->set_shipping_country( 'AR' );
			WC()->customer->set_billing_country( 'AR' );
			WC()->customer->set_shipping_postcode( $cp );
			WC()->customer->set_billing_postcode( $cp );
		}
	}
}
add_action( 'woocommerce_init', 'franco_sync_customer_postcode_from_cookie' );

/**
 * Render dynamic shipping method choices HTML for checkout Step 3
 *
 * @return string
 */
function franco_wc_render_shipping_methods_html() {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		return '';
	}

	$needs_shipping    = WC()->cart->needs_shipping();
	$shipping_packages = $needs_shipping ? WC()->shipping()->get_packages() : array();
	$chosen_shipping   = ( WC()->session ) ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();

	ob_start();
	$rendered_rates = 0;
	foreach ( $shipping_packages as $package_index => $package ) :
		if ( empty( $package['rates'] ) ) {
			continue;
		}

		$package_chosen = isset( $chosen_shipping[ $package_index ] ) ? $chosen_shipping[ $package_index ] : '';
		if ( ! isset( $package['rates'][ $package_chosen ] ) ) {
			$rate_ids       = array_keys( $package['rates'] );
			$package_chosen = reset( $rate_ids );
		}

		foreach ( $package['rates'] as $rate_id => $rate ) :
			$rendered_rates++;
			$rate_cost = (float) $rate->get_cost();
			if ( WC()->cart->display_prices_including_tax() ) {
				$rate_cost += (float) $rate->get_shipping_tax();
			}
			$is_pickup = franco_wc_is_pickup_rate( $rate_id );
			?>
			<label class="checkout-choice-card">
				<input type="radio" name="shipping_method[<?php echo esc_attr( $package_index ); ?>]" value="<?php echo esc_attr( $rate_id ); ?>" data-package="<?php echo esc_attr( $package_index ); ?>" data-pickup="<?php echo $is_pickup ? '1' : '0'; ?>" <?php checked( $rate_id, $package_chosen ); ?> class="choice-radio">
				<div class="choice-content">
					<div class="choice-title-row">
						<strong class="choice-title"><i class="fa-solid <?php echo $is_pickup ? 'fa-store' : 'fa-truck-fast'; ?>"></i> <?php echo esc_html( $rate->get_label() ); ?></strong>
						<?php if ( $rate_cost > 0 ) : ?>
							<span class="choice-badge-price"><?php echo esc_html( franco_clean_price( wc_price( $rate_cost ) ) ); ?></span>
						<?php else : ?>
							<span class="choice-badge-free"><?php esc_html_e( 'GRATIS', 'franco-theme' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</label>
			<?php
		endforeach;
	endforeach;

	if ( $needs_shipping && 0 === $rendered_rates ) :
		$postcode = WC()->customer ? WC()->customer->get_shipping_postcode() : '';
		if ( empty( $postcode ) ) :
			?>
			<div class="checkout-alert-info checkout-alert-flush">
				<i class="fa-solid fa-location-dot"></i> <?php esc_html_e( 'Ingresá tu código postal para cotizar las opciones de envío disponibles con Andreani y Correo.', 'franco-theme' ); ?>
			</div>
			<?php
		else :
			?>
			<div class="checkout-alert-error checkout-alert-flush">
				<i class="fa-solid fa-triangle-exclamation"></i> <?php esc_html_e( 'No hay métodos de envío disponibles para tu zona. Contactanos por WhatsApp.', 'franco-theme' ); ?>
			</div>
			<?php
		endif;
	elseif ( ! $needs_shipping ) :
		?>
		<p class="checkout-no-shipping-note"><?php esc_html_e( 'Tu pedido no requiere envío.', 'franco-theme' ); ?></p>
		<?php
	endif;

	return ob_get_clean();
}

/**
 * AJAX: Set Customer Postcode and recalculate shipping and totals
 */
function franco_wc_ajax_set_customer_postcode() {
	check_ajax_referer( 'franco_wc_nonce', 'nonce' );

	$raw_postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
	$postcode     = franco_validate_ar_postcode( $raw_postcode );
	$state        = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';

	if ( empty( $postcode ) ) {
		wp_send_json_error( array( 'message' => __( 'Por favor ingresá un código postal argentino válido (ej: 1414).', 'franco-theme' ) ) );
	}

	if ( class_exists( 'WooCommerce' ) ) {
		if ( WC()->customer ) {
			WC()->customer->set_shipping_country( 'AR' );
			WC()->customer->set_billing_country( 'AR' );
			WC()->customer->set_shipping_postcode( $postcode );
			WC()->customer->set_billing_postcode( $postcode );
			if ( ! empty( $state ) ) {
				$states = ( WC()->countries ) ? WC()->countries->get_states( 'AR' ) : array();
				if ( is_array( $states ) && isset( $states[ $state ] ) ) {
					WC()->customer->set_shipping_state( $state );
					WC()->customer->set_billing_state( $state );
				}
			}
			WC()->customer->save();
		}

		franco_set_postcode_cookie( $postcode );

		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_totals();
		}
	}

	$shipping_methods_html = franco_wc_render_shipping_methods_html();
	$cart_subtotal         = ( class_exists( 'WooCommerce' ) && WC()->cart ) ? franco_clean_price( WC()->cart->get_cart_subtotal() ) : '$0';
	$cart_total            = ( class_exists( 'WooCommerce' ) && WC()->cart ) ? franco_clean_price( WC()->cart->get_total() ) : '$0';
	$shipping_label        = ( class_exists( 'WooCommerce' ) && WC()->cart ) ? franco_wc_get_shipping_label() : '';

	wp_send_json_success( array(
		'postcode'              => $postcode,
		'shipping_methods_html' => $shipping_methods_html,
		'shipping_label'        => $shipping_label,
		'cart_subtotal'         => $cart_subtotal,
		'cart_total'            => $cart_total,
	) );
}
add_action( 'wp_ajax_franco_set_customer_postcode', 'franco_wc_ajax_set_customer_postcode' );
add_action( 'wp_ajax_nopriv_franco_set_customer_postcode', 'franco_wc_ajax_set_customer_postcode' );

/**
 * AJAX: Calculate shipping options for a single product preview
 */
function franco_wc_ajax_calculate_product_shipping() {
	check_ajax_referer( 'franco_wc_nonce', 'nonce' );

	$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$raw_postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
	$postcode     = franco_validate_ar_postcode( $raw_postcode );

	if ( ! $product_id || empty( $postcode ) ) {
		wp_send_json_error( array( 'message' => __( 'Código postal o producto no válido.', 'franco-theme' ) ) );
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_visible() || 'publish' !== $product->get_status() ) {
		wp_send_json_error( array( 'message' => __( 'Producto no disponible.', 'franco-theme' ) ) );
	}

	if ( class_exists( 'WooCommerce' ) ) {
		if ( WC()->customer ) {
			WC()->customer->set_shipping_country( 'AR' );
			WC()->customer->set_billing_country( 'AR' );
			WC()->customer->set_shipping_postcode( $postcode );
			WC()->customer->set_billing_postcode( $postcode );
			WC()->customer->save();
		}

		franco_set_postcode_cookie( $postcode );
	}

	// Cache shipping calculation for this product + postcode for 10 minutes
	$shipping_cache_key = 'franco_ship_' . $product_id . '_' . md5( $postcode );
	$cached_rates       = get_transient( $shipping_cache_key );
	if ( false !== $cached_rates && is_array( $cached_rates ) ) {
		wp_send_json_success( array(
			'postcode' => $postcode,
			'rates'    => $cached_rates,
		) );
	}

	$package = array(
		'contents'        => array(
			'preview_item' => array(
				'data'     => $product,
				'quantity' => 1,
			),
		),
		'contents_cost'   => (float) $product->get_price(),
		'applied_coupons' => array(),
		'user'            => array(
			'ID' => get_current_user_id(),
		),
		'destination'     => array(
			'country'   => 'AR',
			'state'     => ( WC()->customer ) ? WC()->customer->get_shipping_state() : '',
			'postcode'  => $postcode,
			'city'      => '',
			'address'   => '',
			'address_2' => '',
		),
	);

	$shipping_zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
	$shipping_methods = $shipping_zone->get_shipping_methods( true );

	$rates = array();
	foreach ( $shipping_methods as $method ) {
		if ( ! $method->is_available( $package ) ) {
			continue;
		}
		$method_rates = $method->get_rates_for_package( $package );
		foreach ( $method_rates as $r ) {
			$cost = (float) $r->get_cost();
			$rates[] = array(
				'id'        => $r->get_id(),
				'label'     => $r->get_label(),
				'cost'      => $cost,
				'cost_html' => $cost > 0 ? franco_clean_price( wc_price( $cost ) ) : __( 'GRATIS', 'franco-theme' ),
				'is_pickup' => franco_wc_is_pickup_rate( $r->get_id() ),
			);
		}
	}

	set_transient( $shipping_cache_key, $rates, 10 * MINUTE_IN_SECONDS );

	wp_send_json_success( array(
		'postcode' => $postcode,
		'rates'    => $rates,
	) );
}
add_action( 'wp_ajax_franco_calculate_product_shipping', 'franco_wc_ajax_calculate_product_shipping' );
add_action( 'wp_ajax_nopriv_franco_calculate_product_shipping', 'franco_wc_ajax_calculate_product_shipping' );

/**
 * Helper compatibility fallback for account navigation active item check
 */
if ( ! function_exists( 'wc_is_account_menu_item_active' ) ) {
	function wc_is_account_menu_item_active( $endpoint ) {
		if ( function_exists( 'wc_get_account_menu_item_classes' ) ) {
			return false !== strpos( wc_get_account_menu_item_classes( $endpoint ), 'is-active' );
		}
		return false;
	}
}
