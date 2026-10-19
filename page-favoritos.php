<?php
/**
 * Wishlist Page Template (page-favoritos.php)
 *
 * @package FrancoTheme
 * @version 1.0.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$shop_url = class_exists( 'WooCommerce' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/tienda' );
?>

<!-- Page Banner -->
<section class="page-banner">
	<div class="page-banner-content">
		<?php franco_render_breadcrumbs(); ?>
		<h1 class="page-banner-title">
			<i class="fa-solid fa-heart icon-accent"></i>
			<?php esc_html_e( 'Mis Productos Favoritos', 'franco-theme' ); ?>
		</h1>
		<p class="page-banner-subtitle">
			<?php esc_html_e( 'Guarda tus productos preferidos para comprarlos cuando quieras.', 'franco-theme' ); ?>
		</p>
	</div>
</section>

<!-- Wishlist Grid Section -->
<section class="section">
	<div class="wishlist-toolbar">
		<span class="wishlist-count-text">
			<span id="wishlist-count-label" class="wishlist-count-number">0</span> <?php esc_html_e( 'productos guardados disponibles', 'franco-theme' ); ?>
		</span>
		<button type="button" onclick="clearFullWishlist()" class="wishlist-clear-btn">
			<i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Vaciar Favoritos', 'franco-theme' ); ?>
		</button>
	</div>

	<div class="products-grid" id="wishlist-products-grid">
		<div class="products-empty">
			<i class="fa-solid fa-spinner fa-spin wishlist-loading-icon"></i>
			<p class="text-muted"><?php esc_html_e( 'Cargando tus productos favoritos...', 'franco-theme' ); ?></p>
		</div>
	</div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
	if (typeof francoRenderWishlistPage === 'function') {
		francoRenderWishlistPage();
	}
});
</script>

<?php
get_footer();
