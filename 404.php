<?php
/**
 * 404 Not Found Template
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$shop_url = class_exists( 'WooCommerce' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/tienda' );
?>

<section class="section not-found-section">
	<div class="not-found-card">
		<i class="fa-solid fa-ghost not-found-icon"></i>
		<h1 class="not-found-title">
			<?php esc_html_e( 'PÁGINA NO ENCONTRADA', 'franco-theme' ); ?>
		</h1>
		<p class="not-found-text">
			<?php esc_html_e( 'Lo sentimos, la página que buscas no existe o ha sido movida.', 'franco-theme' ); ?>
		</p>
		<a href="<?php echo esc_url( $shop_url ); ?>" class="btn-primary not-found-btn">
			<i class="fa-solid fa-shop"></i> <?php esc_html_e( 'Volver a la Tienda', 'franco-theme' ); ?>
		</a>
	</div>
</section>

<?php
get_footer();
