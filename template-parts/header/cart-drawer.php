<?php
/**
 * Slide-over Cart Drawer Template Part
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkout_url = class_exists( 'WooCommerce' ) ? wc_get_checkout_url() : home_url( '/checkout' );
?>

<div class="cart-drawer-backdrop" id="cart-drawer-backdrop">
	<div class="cart-drawer">
		<div class="cart-header">
			<h3 class="cart-drawer-title">
				<i class="fa-solid fa-bag-shopping text-primary"></i>
				<?php esc_html_e( 'Tu Carrito de Compras', 'franco-theme' ); ?>
			</h3>
			<button class="cart-close" id="cart-close" aria-label="<?php esc_attr_e( 'Cerrar carrito', 'franco-theme' ); ?>"><i class="fa-solid fa-xmark"></i></button>
		</div>

		<div class="cart-body" id="cart-body">
			<!-- Rendered dynamically via JS from WooCommerce Cart API -->
			<div class="cart-empty">
				<i class="fa-solid fa-circle-notch fa-spin cart-loading-icon"></i>
				<p><?php esc_html_e( 'Cargando tu carrito...', 'franco-theme' ); ?></p>
			</div>
		</div>

		<div class="cart-footer" id="cart-footer" style="display: none;">
			<div class="cart-total-row">
				<span><?php esc_html_e( 'Total Estimado:', 'franco-theme' ); ?></span>
				<span class="cart-total-price" id="cart-total-price">$0</span>
			</div>
			<div class="cart-shipping-note">
				<i class="fa-solid fa-truck cart-shipping-icon"></i> <?php esc_html_e( 'Envíos calculados en el checkout', 'franco-theme' ); ?>
			</div>
			<a href="<?php echo esc_url( $checkout_url ); ?>" class="btn-primary cart-checkout-btn" id="cart-checkout-btn" style="display: none;">
				<i class="fa-solid fa-lock"></i> <?php esc_html_e( 'Finalizar Compra', 'franco-theme' ); ?>
			</a>
		</div>
	</div>
</div>
