<?php
/**
 * REST API Endpoints for Cacheable Public Catalog Data
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST API Routes under the namespace franco/v1
 */
function franco_register_rest_routes() {
	$namespace = 'franco/v1';

	// 1. Filtered Products Endpoint
	register_rest_route( $namespace, '/products', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'franco_rest_get_products',
		'permission_callback' => '__return_true',
		'args'                => array(
			'category'    => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'all' ),
			'subcategory' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'all' ),
			'brand'       => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'all' ),
			'talle'       => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'all' ),
			'sexo'        => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'all' ),
			'color'       => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'all' ),
			'min_price'   => array( 'type' => 'number', 'sanitize_callback' => 'floatval', 'default' => 0 ),
			'max_price'   => array( 'type' => 'number', 'sanitize_callback' => 'floatval', 'default' => 0 ),
			'sort_by'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'popular' ),
			'search'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ),
			'page'        => array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1 ),
			'context'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'default' => 'shop' ),
		),
	) );

	// 2. Live Search Endpoint
	register_rest_route( $namespace, '/search', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'franco_rest_live_search',
		'permission_callback' => '__return_true',
		'args'                => array(
			'term' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $param ) {
					return is_string( $param ) && mb_strlen( trim( $param ) ) >= 2;
				},
			),
		),
	) );

	// 3. Quick View Product Endpoint
	register_rest_route( $namespace, '/quick-view/(?P<id>\d+)', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'franco_rest_get_quick_view',
		'permission_callback' => '__return_true',
		'args'                => array(
			'id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		),
	) );

	// 4. Subcategories Endpoint
	register_rest_route( $namespace, '/subcategories', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'franco_rest_get_subcategories',
		'permission_callback' => '__return_true',
		'args'                => array(
			'parent' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
		),
	) );

	// 5. Wishlist Products Endpoint
	register_rest_route( $namespace, '/wishlist', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'franco_rest_get_wishlist',
		'permission_callback' => '__return_true',
		'args'                => array(
			'ids' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
		),
	) );
}
add_action( 'rest_api_init', 'franco_register_rest_routes' );

/**
 * REST Handler: Filter Products
 */
function franco_rest_get_products( WP_REST_Request $request ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_REST_Response( array( 'html' => '', 'count' => 0, 'has_more' => false ), 200 );
	}

	$category    = $request->get_param( 'category' );
	$subcategory = $request->get_param( 'subcategory' );
	$brand       = $request->get_param( 'brand' );
	$talle       = $request->get_param( 'talle' );
	$sexo        = $request->get_param( 'sexo' );
	$color       = $request->get_param( 'color' );
	$min_price   = $request->get_param( 'min_price' );
	$max_price   = $request->get_param( 'max_price' );
	$sort_by     = $request->get_param( 'sort_by' );
	$search_term = mb_substr( (string) $request->get_param( 'search' ), 0, 100 );
	$paged       = min( max( 1, absint( $request->get_param( 'page' ) ) ), 50 );
	$context     = 'home' === $request->get_param( 'context' ) ? 'home' : 'shop';

	$limit = ( 'home' === $context ) ? 8 : (int) apply_filters( 'loop_shop_per_page', 12 );
	if ( $limit <= 0 || $limit > 48 ) {
		$limit = 12;
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

	if ( ! empty( $search_term ) ) {
		$args['s'] = $search_term;
	}

	$target_cat = ( ! empty( $subcategory ) && 'all' !== $subcategory ) ? $subcategory : $category;
	if ( $target_cat && 'all' !== $target_cat && 'ofertas' !== $target_cat ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $target_cat,
		);
	} elseif ( 'ofertas' === $target_cat || 'ofertas' === $category ) {
		$product_ids_on_sale = wc_get_product_ids_on_sale();
		$args['post__in']    = ! empty( $product_ids_on_sale ) ? $product_ids_on_sale : array( 0 );
	}

	if ( $brand && 'all' !== $brand ) {
		$brand_tax = taxonomy_exists( 'pa_marca' ) ? 'pa_marca' : ( taxonomy_exists( 'marca' ) ? 'marca' : 'pa_marca' );
		$args['tax_query'][] = array(
			'taxonomy' => $brand_tax,
			'field'    => 'slug',
			'terms'    => $brand,
		);
	}

	if ( $talle && 'all' !== $talle ) {
		$talle_tax = franco_find_attribute_taxonomy( 'talle' );
		if ( ! $talle_tax ) {
			$talle_tax = 'pa_talle';
		}

		$matching_terms = array( $talle );
		$target_norm    = franco_normalize_talle( $talle );
		$all_terms      = get_terms( array( 'taxonomy' => $talle_tax, 'hide_empty' => false ) );

		if ( ! empty( $all_terms ) && ! is_wp_error( $all_terms ) ) {
			foreach ( $all_terms as $term_obj ) {
				if ( franco_normalize_talle( $term_obj->name ) === $target_norm || franco_normalize_talle( $term_obj->slug ) === $target_norm ) {
					$matching_terms[] = $term_obj->slug;
				}
			}
		}

		$args['tax_query'][] = array(
			'taxonomy' => $talle_tax,
			'field'    => 'slug',
			'terms'    => array_unique( array_filter( $matching_terms ) ),
		);
	}

	if ( $sexo && 'all' !== $sexo ) {
		$sexo_tax = taxonomy_exists( 'pa_sexo' ) ? 'pa_sexo' : ( taxonomy_exists( 'sexo' ) ? 'sexo' : 'pa_sexo' );
		$args['tax_query'][] = array(
			'taxonomy' => $sexo_tax,
			'field'    => 'slug',
			'terms'    => $sexo,
		);
	}

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

	if ( 'home' === $context && 'popular' === $sort_by && ( empty( $category ) || 'all' === $category ) ) {
		$home_ids = function_exists( 'franco_get_homepage_product_ids' ) ? franco_get_homepage_product_ids( 8 ) : array();
		if ( ! empty( $home_ids ) ) {
			$args['post__in'] = $home_ids;
			$args['orderby']  = 'post__in';
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
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
	} else {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	$loop = new WP_Query( $args );

	ob_start();
	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) {
			$loop->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		wp_reset_postdata();
	}
	$html = ob_get_clean();

	$response_data = array(
		'html'     => $html,
		'count'    => sprintf( __( 'Mostrando %d productos', 'franco-theme' ), $loop->found_posts ),
		'total'    => (int) $loop->found_posts,
		'has_more' => ( $paged < $loop->max_num_pages ),
	);

	$response = new WP_REST_Response( array( 'success' => true, 'data' => $response_data ), 200 );
	$response->header( 'Cache-Control', 'public, max-age=60, s-maxage=300' );
	return $response;
}

/**
 * REST Handler: Live Search
 */
function franco_rest_live_search( WP_REST_Request $request ) {
	$param_q = $request->get_param( 'q' );
	$param_t = $request->get_param( 'term' );
	$term    = mb_substr( trim( (string) ( ! empty( $param_q ) ? $param_q : $param_t ) ), 0, 100 );

	if ( mb_strlen( $term ) < 2 ) {
		return new WP_REST_Response( array( 'success' => true, 'data' => array( 'results' => array() ) ), 200 );
	}

	$cache_key = 'franco_rest_search_' . md5( mb_strtolower( $term ) );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached && is_array( $cached ) ) {
		$response = new WP_REST_Response( array( 'success' => true, 'data' => array( 'results' => $cached ) ), 200 );
		$response->header( 'Cache-Control', 'public, max-age=120, s-maxage=600' );
		return $response;
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		's'              => $term,
		'no_found_rows'  => true,
		'meta_query'     => array(
			array(
				'key'     => '_stock_status',
				'value'   => 'instock',
				'compare' => '=',
			),
		),
	);

	$query   = new WP_Query( $args );
	$results = array();

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$product = wc_get_product( get_the_ID() );
			if ( ! $product ) {
				continue;
			}
			$results[] = array(
				'id'        => $product->get_id(),
				'name'      => $product->get_name(),
				'title'     => $product->get_name(),
				'price'     => franco_clean_price( wc_price( $product->get_price() ) ),
				'price_old' => $product->is_on_sale() ? franco_clean_price( wc_price( $product->get_regular_price() ) ) : '',
				'image'     => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src(),
				'url'       => get_permalink( $product->get_id() ),
				'permalink' => get_permalink( $product->get_id() ),
			);
		}
		wp_reset_postdata();
	}

	set_transient( $cache_key, $results, 2 * MINUTE_IN_SECONDS );

	$response = new WP_REST_Response( array( 'success' => true, 'data' => array( 'results' => $results ) ), 200 );
	$response->header( 'Cache-Control', 'public, max-age=120, s-maxage=600' );
	return $response;
}

/**
 * REST Handler: Quick View Product Data
 */
function franco_rest_get_quick_view( WP_REST_Request $request ) {
	$product_id = absint( $request->get_param( 'id' ) );
	$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

	if ( ! $product || ! $product->is_visible() ) {
		return new WP_REST_Response( array( 'success' => false, 'data' => array( 'message' => __( 'Producto no disponible.', 'franco-theme' ) ) ), 404 );
	}

	$gallery_ids = $product->get_gallery_image_ids();
	$main_img    = wp_get_attachment_image_url( $product->get_image_id(), 'franco-product-gallery' ) ?: wc_placeholder_img_src();
	$gallery     = array( $main_img );

	if ( ! empty( $gallery_ids ) ) {
		foreach ( $gallery_ids as $g_id ) {
			$src = wp_get_attachment_image_url( $g_id, 'franco-product-gallery' );
			if ( $src && ! in_array( $src, $gallery, true ) ) {
				$gallery[] = $src;
			}
		}
	}

	$terms       = get_the_terms( $product_id, 'pa_marca' ) ?: get_the_terms( $product_id, 'product_cat' );
	$brand       = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? mb_strtoupper( $terms[0]->name ) : 'FRANCO ATHLETICS';
	$description = $product->get_short_description() ?: $product->get_description();

	$data = array(
		'id'          => $product_id,
		'title'       => $product->get_name(),
		'brand'       => $brand,
		'price'       => franco_clean_price( wc_price( $product->get_price() ) ),
		'price_old'   => $product->is_on_sale() ? franco_clean_price( wc_price( $product->get_regular_price() ) ) : '',
		'image'       => $main_img,
		'gallery'     => $gallery,
		'description' => wp_trim_words( wp_strip_all_tags( strip_shortcodes( $description ) ), 60 ),
		'sizes'       => function_exists( 'franco_wc_get_product_sizes' ) ? franco_wc_get_product_sizes( $product ) : array(),
		'colors'      => function_exists( 'franco_wc_get_product_colors' ) ? franco_wc_get_product_colors( $product ) : array(),
		'item_code'   => function_exists( 'franco_get_product_item_code' ) ? franco_get_product_item_code( $product ) : '',
		'is_variable' => $product->is_type( 'variable' ),
		'permalink'   => get_permalink( $product_id ),
	);

	$response = new WP_REST_Response( array( 'success' => true, 'data' => $data ), 200 );
	$response->header( 'Cache-Control', 'public, max-age=300, s-maxage=1800' );
	return $response;
}

/**
 * REST Handler: Subcategories
 */
function franco_rest_get_subcategories( WP_REST_Request $request ) {
	$param_p    = $request->get_param( 'parent_category' );
	$param_par  = $request->get_param( 'parent' );
	$parent_cat = sanitize_text_field( (string) ( ! empty( $param_p ) ? $param_p : $param_par ) );

	if ( empty( $parent_cat ) || 'all' === $parent_cat || 'ofertas' === $parent_cat ) {
		return new WP_REST_Response( array( 'success' => true, 'data' => array( 'subcategories' => array() ) ), 200 );
	}

	$subcats   = function_exists( 'franco_get_visible_subcategories' ) ? franco_get_visible_subcategories( $parent_cat ) : array();
	$formatted = array();

	if ( ! empty( $subcats ) ) {
		foreach ( $subcats as $sc ) {
			$formatted[] = array(
				'slug' => $sc->slug,
				'name' => $sc->name,
			);
		}
	}

	$response = new WP_REST_Response( array( 'success' => true, 'data' => array( 'subcategories' => $formatted ) ), 200 );
	$response->header( 'Cache-Control', 'public, max-age=600, s-maxage=3600' );
	return $response;
}

/**
 * REST Handler: Wishlist HTML
 */
function franco_rest_get_wishlist( WP_REST_Request $request ) {
	$param_ids = $request->get_param( 'product_ids' );
	$param_i   = $request->get_param( 'ids' );
	$ids_raw   = (string) ( ! empty( $param_ids ) ? $param_ids : $param_i );
	$ids_arr   = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );
	$product_ids = array_values( array_slice( array_unique( $ids_arr ), 0, 50 ) );

	if ( empty( $product_ids ) ) {
		return new WP_REST_Response( array( 'success' => true, 'data' => array( 'html' => '', 'count' => 0 ) ), 200 );
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

	$response = new WP_REST_Response( array( 'success' => true, 'data' => array( 'html' => $html, 'count' => count( $loop->posts ) ) ), 200 );
	$response->header( 'Cache-Control', 'public, max-age=60' );
	return $response;
}
