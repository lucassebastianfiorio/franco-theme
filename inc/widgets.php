<?php
/**
 * Register Sidebars & Widget Areas
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function franco_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar de Tienda (Filtros)', 'franco-theme' ),
		'id'            => 'shop-sidebar',
		'description'   => __( 'Widgets visibles en el lateral de la tienda.', 'franco-theme' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s filter-group">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="filter-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => __( 'Pie de Página - Columna 1', 'franco-theme' ),
		'id'            => 'footer-1',
		'description'   => __( 'Widgets para la primera columna del footer.', 'franco-theme' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s footer-col">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => __( 'Pie de Página - Columna 2', 'franco-theme' ),
		'id'            => 'footer-2',
		'description'   => __( 'Widgets para la segunda columna del footer.', 'franco-theme' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s footer-col">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-title">',
		'after_title'   => '</h4>',
	) );
}
add_action( 'widgets_init', 'franco_widgets_init' );
