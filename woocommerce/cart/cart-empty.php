<?php
/**
 * Empty Cart Page Template
 *
 * @package FrancoTheme
 * @version 1.6.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="empty-cart-page-wrapper">
	<div class="empty-cart-card">
		<div class="empty-cart-icon-circle">
			<i class="fa-solid fa-basket-shopping"></i>
		</div>
		<h2 class="empty-cart-title"><?php esc_html_e( '¡Tu carrito en este momento está vacío!', 'franco-theme' ); ?></h2>
		<p class="empty-cart-subtitle"><?php esc_html_e( 'Parece que aún no has agregado productos a tu carrito. Explorá nuestra tienda y descubrí las mejores ofertas en zapatillas y ropa deportiva.', 'franco-theme' ); ?></p>
		<div class="empty-cart-cta">
			<a class="btn-primary" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				<i class="fa-solid fa-shop"></i> <?php esc_html_e( 'Explorar Productos de la Tienda', 'franco-theme' ); ?>
			</a>
		</div>
	</div>

	<!-- Recommended Products Section -->
	<div class="empty-cart-suggestions-section">
		<h3 class="suggestions-heading">
			<i class="fa-solid fa-fire text-primary"></i> <?php esc_html_e( 'Nuevo en la tienda', 'franco-theme' ); ?>
		</h3>

		<?php
		$suggested_products = new WP_Query( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				),
			),
		) );

		if ( $suggested_products->have_posts() ) : ?>
			<div class="products-grid empty-cart-products-grid">
				<?php while ( $suggested_products->have_posts() ) : $suggested_products->the_post(); ?>
					<?php wc_get_template_part( 'content', 'product' ); ?>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
