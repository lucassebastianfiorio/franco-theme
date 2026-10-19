<?php
/**
 * Template Helper Functions
 *
 * @package FrancoTheme
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clean HTML entities and non-breaking spaces from WooCommerce prices
 * Fixes &#36;&nbsp;0,00 display issue
 */
function franco_clean_price( $price_html ) {
	if ( empty( $price_html ) ) {
		return '$0';
	}
	$text = wp_strip_all_tags( $price_html );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( array( "\xC2\xA0", '&nbsp;', 'nbsp;' ), ' ', $text );
	return trim( $text );
}

/**
 * Single source of truth for theme settings.
 * The admin panel (options) wins; legacy Customizer values (theme mods) are a fallback.
 *
 * @param string $key     Setting key.
 * @param string $default Default value when nothing is stored.
 * @return mixed
 */
function franco_get_setting( $key, $default = '' ) {
	static $cache = array();
	if ( array_key_exists( $key, $cache ) ) {
		return ( '' !== $cache[ $key ] && null !== $cache[ $key ] && false !== $cache[ $key ] ) ? $cache[ $key ] : $default;
	}

	$value = get_option( $key );
	if ( false !== $value && '' !== $value && null !== $value ) {
		$cache[ $key ] = $value;
		return $value;
	}

	$value = get_theme_mod( $key );
	if ( false !== $value && '' !== $value && null !== $value ) {
		$cache[ $key ] = $value;
		return $value;
	}

	$cache[ $key ] = $default;
	return $default;
}

/**
 * Obtener URL de un logo del tema con fallback en cascada (ID -> URL -> Custom Logo -> Asset local).
 *
 * @param string $context 'header'|'checkout'|'footer'
 * @return string URL del logo.
 */
function franco_get_theme_logo_url( $context = 'header' ) {
	$id_key  = 'franco_' . $context . '_logo_id';
	$url_key = 'franco_' . $context . '_logo';

	$logo_id = absint( franco_get_setting( $id_key, 0 ) );
	if ( $logo_id > 0 ) {
		$img = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( $img ) {
			return $img[0];
		}
	}

	$url = (string) franco_get_setting( $url_key, '' );
	if ( ! empty( $url ) ) {
		return $url;
	}

	if ( 'header' !== $context ) {
		return franco_get_theme_logo_url( 'header' );
	}

	if ( has_custom_logo() ) {
		$custom_id = get_theme_mod( 'custom_logo' );
		$img = wp_get_attachment_image_src( $custom_id, 'full' );
		if ( $img ) {
			return $img[0];
		}
	}

	return FRANCO_THEME_URI . '/assets/images/logo.webp';
}

/**
 * Render Brand Logo or Site Title
 */
function franco_render_site_logo() {
	$logo_url = franco_get_theme_logo_url( 'header' );

	echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="brand-logo-wrap">';
	echo '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="159" height="43" decoding="async">';
	echo '</a>';
}

/**
 * Resolve the front page hero image (configured URL, attachment data and intrinsic size).
 * Shared by the hero markup and the <head> preload hint so both request the very same file.
 *
 * @return array{url:string,id:int,srcset:string,sizes:string,width:int,height:int}
 */
function franco_get_hero_image() {
	static $hero = null;

	if ( null !== $hero ) {
		return $hero;
	}

	$hero = array(
		'url'    => '',
		'id'     => 0,
		'srcset' => '',
		'sizes'  => '100vw',
		'width'  => 0,
		'height' => 0,
	);

	$configured_id = absint( franco_get_setting( 'franco_hero_banner_image_id', 0 ) );
	$url           = (string) franco_get_setting( 'franco_hero_banner_image', '' );

	if ( $configured_id > 0 ) {
		$image = wp_get_attachment_image_src( $configured_id, 'full' );
		if ( $image ) {
			$hero['id']     = $configured_id;
			$hero['url']    = $image[0];
			$hero['width']  = (int) $image[1];
			$hero['height'] = (int) $image[2];
			$hero['srcset'] = (string) wp_get_attachment_image_srcset( $configured_id, 'full' );
			return $hero;
		}
	}

	if ( '' === $url ) {
		$url = FRANCO_THEME_URI . '/assets/images/hero_banner.webp';
	}

	$hero['url'] = $url;

	$attachment_id = (int) attachment_url_to_postid( $url );
	if ( $attachment_id > 0 ) {
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( $image ) {
			$hero['id']     = $attachment_id;
			$hero['url']    = $image[0];
			$hero['width']  = (int) $image[1];
			$hero['height'] = (int) $image[2];
			$hero['srcset'] = (string) wp_get_attachment_image_srcset( $attachment_id, 'full' );
		}
		return $hero;
	}

	// Bundled theme image: read the intrinsic size from disk so the <img> can reserve its box.
	if ( 0 === strpos( $url, FRANCO_THEME_URI . '/' ) ) {
		$path = FRANCO_THEME_DIR . '/' . ltrim( substr( $url, strlen( FRANCO_THEME_URI ) ), '/' );
		$size = is_readable( $path ) ? wp_getimagesize( $path ) : false;
		if ( $size ) {
			$hero['width']  = (int) $size[0];
			$hero['height'] = (int) $size[1];
		}
	}

	return $hero;
}

/**
 * Print the hero background as a real, high-priority <img> (LCP candidate) instead of a CSS background.
 */
function franco_render_hero_image() {
	$hero = franco_get_hero_image();

	if ( '' === $hero['url'] ) {
		return;
	}

	if ( $hero['id'] > 0 ) {
		echo wp_get_attachment_image( $hero['id'], 'full', false, array(
			'class'         => 'hero-bg-img',
			'alt'           => '',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decoding'      => 'async',
			'sizes'         => '100vw',
		) );
		return;
	}

	$dimensions = ( $hero['width'] > 0 && $hero['height'] > 0 )
		? sprintf( ' width="%d" height="%d"', $hero['width'], $hero['height'] )
		: '';

	printf(
		'<img class="hero-bg-img" src="%1$s"%2$s alt="" loading="eager" fetchpriority="high" decoding="async">',
		esc_url( $hero['url'] ),
		$dimensions // Integers only, built with %d above.
	);
}

/**
 * Helper to determine WooCommerce Product Badge
 */
function franco_get_product_badge( $product ) {
	if ( ! $product ) return 'NUEVO';

	if ( $product->is_on_sale() ) {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		if ( $regular > 0 && $sale > 0 ) {
			$percentage = round( ( ( $regular - $sale ) / $regular ) * 100 );
			return sprintf( __( 'DESCUENTO %d%%', 'franco-theme' ), $percentage );
		}
		return __( 'OFERTA', 'franco-theme' );
	}

	if ( $product->is_featured() ) {
		return __( 'MÁS VENDIDO', 'franco-theme' );
	}

	return __( 'NUEVO', 'franco-theme' );
}

/**
 * Print BreadcrumbList JSON-LD (through WooCommerce's structured data printer) on product, shop and category views.
 * Runs once per request, so it can never duplicate if breadcrumbs are rendered twice.
 */
function franco_output_breadcrumb_schema() {
	static $done = false;

	if ( $done || ! class_exists( 'WC_Breadcrumb' ) || ! function_exists( 'WC' ) || ! isset( WC()->structured_data ) ) {
		return;
	}

	if ( ! ( is_product() || is_shop() || is_product_category() ) ) {
		return;
	}

	$done       = true;
	$breadcrumb = new WC_Breadcrumb();
	$breadcrumb->generate();
	WC()->structured_data->generate_breadcrumblist_data( $breadcrumb );
}

/**
 * Breadcrumb Helper
 */
function franco_render_breadcrumbs() {
	franco_output_breadcrumb_schema();

	echo '<div class="page-breadcrumbs">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="breadcrumb-link">' . esc_html__( 'Inicio', 'franco-theme' ) . '</a> / ';

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		echo '<span class="text-primary">' . esc_html__( 'Tienda', 'franco-theme' ) . '</span>';
	} elseif ( is_single() ) {
		$shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/tienda' );
		echo '<a href="' . esc_url( $shop_url ) . '" class="breadcrumb-link">' . esc_html__( 'Tienda', 'franco-theme' ) . '</a> / ';
		echo '<span class="text-primary">' . esc_html( get_the_title() ) . '</span>';
	} else {
		echo '<span class="text-primary">' . esc_html( get_the_title() ) . '</span>';
	}
	echo '</div>';
}

/**
 * Smart Taxonomy Finder for Product Attributes (Talle, Sexo, Color, Marca, etc.)
 * Checks registered taxonomies, WooCommerce attribute taxonomies, and synonyms.
 *
 * @param string $attribute_key Short key (e.g. 'talle', 'sexo', 'color', 'marca').
 * @return string Registered taxonomy name (e.g. 'pa_talle') or empty string.
 */
function franco_find_attribute_taxonomy( $attribute_key ) {
	$attribute_key = sanitize_key( $attribute_key );
	if ( empty( $attribute_key ) ) {
		return '';
	}

	$candidates = array(
		'pa_' . $attribute_key,
		$attribute_key,
	);

	if ( 'talle' === $attribute_key ) {
		$candidates[] = 'pa_talla';
		$candidates[] = 'talla';
		$candidates[] = 'pa_size';
		$candidates[] = 'size';
	} elseif ( 'sexo' === $attribute_key ) {
		$candidates[] = 'pa_genero';
		$candidates[] = 'genero';
		$candidates[] = 'pa_gender';
		$candidates[] = 'gender';
	} elseif ( 'color' === $attribute_key ) {
		$candidates[] = 'pa_colour';
		$candidates[] = 'colour';
	} elseif ( 'marca' === $attribute_key ) {
		$candidates[] = 'product_brand';
		$candidates[] = 'brand';
	}

	foreach ( $candidates as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			return $tax;
		}
	}

	// WooCommerce attribute taxonomy lookup
	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		$wc_attribute_taxonomies = wc_get_attribute_taxonomies();
		if ( ! empty( $wc_attribute_taxonomies ) ) {
			foreach ( $wc_attribute_taxonomies as $tax_obj ) {
				$name_slug  = sanitize_key( $tax_obj->attribute_name );
				$label_slug = sanitize_key( $tax_obj->attribute_label );

				if ( $name_slug === $attribute_key || $label_slug === $attribute_key || 0 === strpos( $name_slug, $attribute_key ) ) {
					$full_tax = wc_attribute_taxonomy_name( $tax_obj->attribute_name );
					if ( taxonomy_exists( $full_tax ) ) {
						return $full_tax;
					}
				}
			}
		}
	}

	return '';
}

/**
 * Obtener la versión global de caché del catálogo.
 *
 * @return int
 */
function franco_get_cache_version() {
	$ver = get_option( 'franco_catalog_cache_ver' );
	if ( false === $ver ) {
		$ver = 1;
		update_option( 'franco_catalog_cache_ver', $ver, false );
	}
	return (int) $ver;
}

/**
 * Generar clave de transient versionada para el catálogo.
 *
 * @param string $prefix Prefijo descriptivo del transient.
 * @return string Clave con versión.
 */
function franco_cache_key( $prefix ) {
	return 'franco_' . $prefix . '_v' . franco_get_cache_version();
}

/**
 * Invalidate catalog transients when products are updated or changed.
 */
function franco_invalidate_catalog_cache() {
	$ver = (int) get_option( 'franco_catalog_cache_ver', 1 );
	update_option( 'franco_catalog_cache_ver', $ver + 1, false );
}
add_action( 'woocommerce_update_product', 'franco_invalidate_catalog_cache' );
add_action( 'woocommerce_new_product', 'franco_invalidate_catalog_cache' );
add_action( 'woocommerce_product_set_stock_status', 'franco_invalidate_catalog_cache' );
add_action( 'save_post_product', 'franco_invalidate_catalog_cache' );

/**
 * Get product IDs for homepage showcase without using expensive ORDER BY RAND().
 *
 * @param int $limit Number of product IDs to retrieve.
 * @return int[] Array of product IDs.
 */
function franco_get_homepage_product_ids( $limit = 8 ) {
	$cache_key = franco_cache_key( 'homepage_product_ids_' . $limit );
	$ids       = get_transient( $cache_key );

	if ( false === $ids || ! is_array( $ids ) ) {
		$pool = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 40,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		if ( empty( $pool ) ) {
			return array();
		}

		shuffle( $pool );
		$ids = array_slice( $pool, 0, $limit );
		set_transient( $cache_key, $ids, 15 * MINUTE_IN_SECONDS );
	}

	return $ids;
}

/**
 * Query term IDs associated with published, in-stock products that have a valid price > 0.
 *
 * @param string $taxonomy Taxonomy name e.g. 'product_cat', 'pa_talle', 'pa_color', 'pa_sexo', 'pa_marca'.
 * @return int[] List of term IDs.
 */
function franco_get_visible_product_term_ids( $taxonomy = 'product_cat' ) {
	global $wpdb;

	$cache_key = franco_cache_key( 'vterms_' . sanitize_key( $taxonomy ) );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';
	$has_lookup   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lookup_table ) ) === $lookup_table;

	if ( $has_lookup ) {
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT tt.term_id
				 FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 INNER JOIN {$lookup_table} ml ON tr.object_id = ml.product_id
				 WHERE tt.taxonomy = %s
				   AND ml.stock_status = 'instock'
				   AND ml.min_price > 0",
				$taxonomy
			)
		);
	} else {
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT tt.term_id
				 FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				 INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
				 LEFT JOIN {$wpdb->posts} parent_p ON (p.post_parent = parent_p.ID AND p.post_type = 'product_variation')
				 LEFT JOIN {$wpdb->postmeta} pm_stock ON (p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status')
				 LEFT JOIN {$wpdb->postmeta} pm_parent_stock ON (parent_p.ID = pm_parent_stock.post_id AND pm_parent_stock.meta_key = '_stock_status')
				 LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
				 LEFT JOIN {$wpdb->postmeta} pm_parent_price ON (parent_p.ID = pm_parent_price.post_id AND pm_parent_price.meta_key = '_price')
				 WHERE tt.taxonomy = %s
				   AND p.post_type IN ('product', 'product_variation')
				   AND p.post_status = 'publish'
				   AND (pm_stock.meta_value = 'instock' OR pm_stock.meta_value IS NULL OR (p.post_type = 'product_variation' AND pm_parent_stock.meta_value = 'instock'))
				   AND ((pm_price.meta_value IS NOT NULL AND pm_price.meta_value != '' AND CAST(pm_price.meta_value AS DECIMAL(10,2)) > 0)
				        OR (pm_parent_price.meta_value IS NOT NULL AND pm_parent_price.meta_value != '' AND CAST(pm_parent_price.meta_value AS DECIMAL(10,2)) > 0))",
				$taxonomy
			)
		);
	}

	$term_ids = array_map( 'absint', $results ? $results : array() );

	// For hierarchical taxonomies like product_cat, include all ancestors of terms attached to visible products
	if ( is_taxonomy_hierarchical( $taxonomy ) && ! empty( $term_ids ) ) {
		$all_term_ids = $term_ids;
		foreach ( $term_ids as $tid ) {
			$ancestors = get_ancestors( $tid, $taxonomy, 'taxonomy' );
			if ( ! empty( $ancestors ) ) {
				$all_term_ids = array_merge( $all_term_ids, $ancestors );
			}
		}
		$term_ids = array_unique( array_map( 'absint', $all_term_ids ) );
	}

	set_transient( $cache_key, $term_ids, 2 * HOUR_IN_SECONDS );
	return $term_ids;
}

/**
 * Get parent categories (parent == 0) that have visible in-stock priced products.
 *
 * @return WP_Term[]
 */
function franco_get_visible_parent_categories() {
	$visible_term_ids = franco_get_visible_product_term_ids( 'product_cat' );
	if ( empty( $visible_term_ids ) ) {
		return array();
	}

	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'include'    => $visible_term_ids,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$filtered = array();
	foreach ( $terms as $term ) {
		if ( 'sin-categorizar' !== $term->slug && 'uncategorized' !== $term->slug ) {
			$filtered[] = $term;
		}
	}

	return $filtered;
}

/**
 * Get visible subcategories for a given parent category slug or ID.
 *
 * @param string|int $parent_id_or_slug Parent term slug or ID.
 * @return WP_Term[]
 */
function franco_get_visible_subcategories( $parent_id_or_slug ) {
	if ( empty( $parent_id_or_slug ) || 'all' === $parent_id_or_slug || 'ofertas' === $parent_id_or_slug ) {
		return array();
	}

	if ( is_numeric( $parent_id_or_slug ) ) {
		$parent_id = absint( $parent_id_or_slug );
	} else {
		$parent_term = get_term_by( 'slug', sanitize_title( $parent_id_or_slug ), 'product_cat' );
		if ( ! $parent_term || is_wp_error( $parent_term ) ) {
			return array();
		}
		$parent_id = $parent_term->term_id;
	}

	$visible_term_ids = franco_get_visible_product_term_ids( 'product_cat' );
	if ( empty( $visible_term_ids ) ) {
		return array();
	}

	$subcats = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => $parent_id,
		'include'    => $visible_term_ids,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	if ( is_wp_error( $subcats ) || empty( $subcats ) ) {
		return array();
	}

	return array_values( $subcats );
}

/**
 * Get min and max product prices dynamically from published, in-stock products with valid prices.
 *
 * @return array{min:int, max:int}
 */
function franco_get_min_max_prices() {
	global $wpdb;

	$cache_key = franco_cache_key( 'min_max_prices' );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached && is_array( $cached ) && isset( $cached['min'], $cached['max'] ) ) {
		return $cached;
	}

	$lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';
	$has_lookup   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lookup_table ) ) === $lookup_table;

	if ( $has_lookup ) {
		$results = $wpdb->get_row(
			"SELECT 
				MIN(min_price) as min_price,
				MAX(max_price) as max_price
			 FROM {$lookup_table}
			 WHERE stock_status = 'instock'
			   AND min_price > 0"
		);
	} else {
		$results = $wpdb->get_row(
			"SELECT 
				MIN(CAST(pm_price.meta_value AS DECIMAL(10,2))) as min_price,
				MAX(CAST(pm_price.meta_value AS DECIMAL(10,2))) as max_price
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
			 LEFT JOIN {$wpdb->postmeta} pm_stock ON (p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status')
			 WHERE p.post_type IN ('product', 'product_variation')
			   AND p.post_status = 'publish'
			   AND (pm_stock.meta_value = 'instock' OR pm_stock.meta_value IS NULL)
			   AND pm_price.meta_value IS NOT NULL
			   AND pm_price.meta_value != ''
			   AND CAST(pm_price.meta_value AS DECIMAL(10,2)) > 0"
		);
	}

	$min = ( $results && null !== $results->min_price ) ? floor( (float) $results->min_price ) : 0;
	$max = ( $results && null !== $results->max_price ) ? ceil( (float) $results->max_price ) : 250000;

	if ( $min < 0 ) {
		$min = 0;
	}
	if ( $max <= $min ) {
		$max = $min + 50000;
	}

	$data = array(
		'min' => (int) $min,
		'max' => (int) $max,
	);

	set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );
	return $data;
}

/**
 * Get Taxonomy Terms associated with visible, in-stock products/variations (Cached via Transient)
 */
function franco_get_instock_terms( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$transient_key = franco_cache_key( 'instock_' . sanitize_key( $taxonomy ) );
	$cached_terms  = get_transient( $transient_key );

	if ( false !== $cached_terms ) {
		return $cached_terms;
	}

	$visible_term_ids = franco_get_visible_product_term_ids( $taxonomy );

	if ( empty( $visible_term_ids ) ) {
		set_transient( $transient_key, array(), 2 * HOUR_IN_SECONDS );
		return array();
	}

	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'include'    => $visible_term_ids,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		$result = array();
	} else {
		$result = array_values( $terms );
	}

	set_transient( $transient_key, $result, 2 * HOUR_IN_SECONDS );
	return $result;
}

/**
 * Format, normalize and deduplicate talle terms list.
 *
 * @param array $terms Terms or options list.
 * @return array Array of objects with ->slug and ->name properties.
 */
function franco_format_talle_terms( $terms ) {
	if ( empty( $terms ) ) {
		return array();
	}

	$formatted       = array();
	$seen_normalized = array();

	foreach ( $terms as $term ) {
		$raw_name  = is_object( $term ) ? ( isset( $term->name ) ? $term->name : $term->slug ) : (string) $term;
		$norm_name = franco_normalize_talle( $raw_name );

		if ( isset( $seen_normalized[ $norm_name ] ) ) {
			continue;
		}
		$seen_normalized[ $norm_name ] = true;

		$formatted[] = (object) array(
			'slug' => $norm_name,
			'name' => $norm_name,
		);
	}

	usort( $formatted, function( $a, $b ) {
		$num_a = is_numeric( $a->name ) ? (float) $a->name : null;
		$num_b = is_numeric( $b->name ) ? (float) $b->name : null;
		if ( null !== $num_a && null !== $num_b ) {
			return $num_a <=> $num_b;
		}
		return strnatcasecmp( $a->name, $b->name );
	} );

	return $formatted;
}


/**
 * Invalidate In-Stock Terms Transient Cache via version bump (with shutdown debounce).
 * Compatible with Redis, Memcached and traditional MySQL transients without table locks.
 */
function franco_clear_instock_terms_cache() {
	static $scheduled = false;
	if ( ! $scheduled ) {
		$scheduled = true;
		add_action( 'shutdown', function() {
			update_option( 'franco_catalog_cache_ver', time(), false );
		} );
	}
}
add_action( 'save_post_product', 'franco_clear_instock_terms_cache' );
add_action( 'woocommerce_update_product', 'franco_clear_instock_terms_cache' );
add_action( 'woocommerce_new_product', 'franco_clear_instock_terms_cache' );
add_action( 'edited_term', 'franco_clear_instock_terms_cache' );
add_action( 'created_term', 'franco_clear_instock_terms_cache' );
add_action( 'delete_term', 'franco_clear_instock_terms_cache' );

/**
 * Clear the in-stock terms cache when a product is trashed or deleted.
 *
 * @param int $post_id Post ID.
 */
function franco_clear_instock_terms_cache_on_post_removal( $post_id ) {
	if ( 'product' === get_post_type( $post_id ) ) {
		franco_clear_instock_terms_cache();
	}
}
add_action( 'trashed_post', 'franco_clear_instock_terms_cache_on_post_removal' );
add_action( 'delete_post', 'franco_clear_instock_terms_cache_on_post_removal' );
add_action( 'woocommerce_product_set_stock_status', 'franco_clear_instock_terms_cache' );
add_action( 'woocommerce_variation_set_stock_status', 'franco_clear_instock_terms_cache' );

/**
 * Keep the Favoritos page out of the index (per-visitor content, no SEO value).
 *
 * @param array $robots Robots directives.
 * @return array
 */
function franco_robots_noindex_favoritos( $robots ) {
	$favoritos_id = (int) get_option( 'franco_page_favoritos', 0 );

	if ( ( $favoritos_id > 0 && is_page( $favoritos_id ) ) || is_page_template( 'page-favoritos.php' ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'franco_robots_noindex_favoritos' );

/**
 * Strip the last word from a title string.
 * Useful when products are imported with codes/SKUs at the end (e.g. "ZAPATILLA TOPPER T89600" -> "ZAPATILLA TOPPER").
 *
 * @param string $title Product title or name.
 * @return string
 */
function franco_strip_last_word( $title ) {
	if ( empty( $title ) || ! is_string( $title ) ) {
		return $title;
	}
	$title = trim( $title );
	$words = preg_split( '/\s+/', $title );
	if ( count( $words ) > 1 ) {
		array_pop( $words );
		return implode( ' ', $words );
	}
	return $title;
}

/**
 * Extract the last word from a title string.
 *
 * @param string $title Product title or name.
 * @return string
 */
function franco_get_last_word( $title ) {
	if ( empty( $title ) || ! is_string( $title ) ) {
		return '';
	}
	$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
	$title = trim( wp_strip_all_tags( $title ) );
	$title = str_replace( "\xc2\xa0", ' ', $title );
	$words = preg_split( '/\s+/', $title );
	if ( is_array( $words ) && count( $words ) > 1 ) {
		return end( $words );
	}
	return '';
}

/**
 * Get product item code (last word of original product title) when setting is enabled.
 *
 * @param WC_Product|int $product_or_id
 * @return string
 */
function franco_get_product_item_code( $product_or_id ) {
	if ( '1' !== (string) franco_get_setting( 'franco_remove_last_word_product_title', '0' ) ) {
		return '';
	}

	$product_id = 0;
	if ( $product_or_id instanceof WC_Product ) {
		$product_id = $product_or_id->is_type( 'variation' ) ? $product_or_id->get_parent_id() : $product_or_id->get_id();
	} elseif ( is_numeric( $product_or_id ) ) {
		$product_id = absint( $product_or_id );
		$parent_id  = wp_get_post_parent_id( $product_id );
		if ( $parent_id ) {
			$product_id = $parent_id;
		}
	}

	if ( ! $product_id ) {
		return '';
	}

	$raw_title = get_post_field( 'post_title', $product_id, 'raw' );
	return franco_get_last_word( $raw_title );
}

/**
 * Filter WooCommerce Product get_name() on frontend when setting is enabled.
 */
function franco_filter_wc_product_name_last_word( $name, $product = null ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $name;
	}
	if ( '1' === (string) franco_get_setting( 'franco_remove_last_word_product_title', '0' ) ) {
		return franco_strip_last_word( $name );
	}
	return $name;
}
add_filter( 'woocommerce_product_get_name', 'franco_filter_wc_product_name_last_word', 10, 2 );

/**
 * Filter standard WordPress the_title for products on frontend when setting is enabled.
 */
function franco_filter_the_title_product_last_word( $title, $post_id = 0 ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $title;
	}
	if ( '1' === (string) franco_get_setting( 'franco_remove_last_word_product_title', '0' ) ) {
		if ( $post_id && 'product' === get_post_type( $post_id ) ) {
			return franco_strip_last_word( $title );
		}
	}
	return $title;
}
add_filter( 'the_title', 'franco_filter_the_title_product_last_word', 10, 2 );

