<?php
/**
 * Custom WooCommerce Thank You / Order Received Template
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );

$cbu       = trim( (string) franco_get_setting( 'franco_bank_cbu', '' ) );
$alias     = trim( (string) franco_get_setting( 'franco_bank_alias', '' ) );
$bank_name = trim( (string) franco_get_setting( 'franco_bank_name', '' ) );
$wa        = preg_replace( '/\D/', '', (string) franco_get_setting( 'franco_whatsapp_number', '' ) );

// Never show the placeholder CBU to customers.
$show_bank_box = ( $order && 'bacs' === $order->get_payment_method() && '' !== $cbu && '0000003100000000001234' !== $cbu );
?>

<section class="section thankyou-section">
	<?php if ( $order ) : ?>
		<div class="thankyou-card">
			<div class="thankyou-check-circle">
				<i class="fa-solid fa-circle-check"></i>
			</div>

			<h1 class="thankyou-title">
				<?php esc_html_e( '¡PEDIDO CONFIRMADO CON ÉXITO!', 'franco-theme' ); ?>
			</h1>
			<p class="thankyou-lead">
				<?php printf( esc_html__( 'Gracias por tu compra. Tu número de pedido es #%s.', 'franco-theme' ), esc_html( $order->get_order_number() ) ); ?>
			</p>

			<!-- Order Summary Cards -->
			<div class="thankyou-summary-grid">
				<div class="thankyou-summary-item">
					<span class="thankyou-summary-label"><?php esc_html_e( 'Fecha:', 'franco-theme' ); ?></span>
					<strong class="thankyou-summary-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
				</div>

				<div class="thankyou-summary-item">
					<span class="thankyou-summary-label"><?php esc_html_e( 'Método de Pago:', 'franco-theme' ); ?></span>
					<strong class="thankyou-summary-value-accent"><?php echo esc_html( $order->get_payment_method_title() ); ?></strong>
				</div>

				<div class="thankyou-summary-item">
					<span class="thankyou-summary-label"><?php esc_html_e( 'Total a Pagar:', 'franco-theme' ); ?></span>
					<strong class="thankyou-summary-total"><?php echo esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ); ?></strong>
				</div>
			</div>

			<?php if ( $show_bank_box ) : ?>
			<!-- Bank Transfer Details Box (bank transfer orders only) -->
			<div class="thankyou-bank-box">
				<h3 class="thankyou-bank-title">
					<i class="fa-solid fa-building-columns"></i> <?php esc_html_e( 'Datos para Transferencia Directa:', 'franco-theme' ); ?>
				</h3>
				<p class="thankyou-bank-text">
					<?php esc_html_e( 'Transferí el importe total a la siguiente cuenta y enviá el comprobante por WhatsApp:', 'franco-theme' ); ?>
				</p>
				<ul class="thankyou-bank-list">
					<li><strong>CBU:</strong> <code class="thankyou-code"><?php echo esc_html( $cbu ); ?></code></li>
					<?php if ( '' !== $alias ) : ?>
						<li><strong>Alias:</strong> <code class="thankyou-code"><?php echo esc_html( $alias ); ?></code></li>
					<?php endif; ?>
					<?php if ( '' !== $bank_name ) : ?>
						<li><strong>Titular:</strong> <?php echo esc_html( $bank_name ); ?></li>
					<?php endif; ?>
				</ul>
			</div>
			<?php endif; ?>

			<!-- Items Ordered Table -->
			<div class="thankyou-items">
				<h3 class="thankyou-items-title">
					<?php esc_html_e( 'Detalle de Productos', 'franco-theme' ); ?>
				</h3>
				<div class="thankyou-items-box">
					<?php foreach ( $order->get_items() as $item ) : ?>
						<div class="thankyou-item-row">
							<div>
								<strong class="thankyou-item-name"><?php echo esc_html( $item->get_name() ); ?></strong>
								<?php
								$order_item_code = $item->get_meta( 'Item N°' ) ?: $item->get_meta( '_item_code' );
								if ( ! empty( $order_item_code ) ) :
								?>
									<span class="thankyou-item-code" style="display: block; font-size: 0.82rem; color: var(--text-muted, #737373); font-weight: 600; margin-top: 2px;">
										<?php printf( esc_html__( 'Item N° %s', 'franco-theme' ), esc_html( $order_item_code ) ); ?>
									</span>
								<?php endif; ?>
								<span class="thankyou-item-qty">Cantidad: <?php echo esc_html( $item->get_quantity() ); ?></span>
							</div>
							<span class="thankyou-item-price"><?php echo esc_html( wp_strip_all_tags( wc_price( $item->get_total() ) ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Send WhatsApp Confirmation Button -->
			<?php if ( '' !== $wa ) : ?>
				<?php
				$wa_msg = '👋 ¡Hola Franco Deportes! Realicé el pedido #' . $order->get_order_number() . ' por ' . wp_strip_all_tags( $order->get_formatted_order_total() ) . '. Adjunto mi comprobante para acelerar el envío.';
				$wa_url = 'https://wa.me/' . $wa . '?text=' . rawurlencode( $wa_msg );
				?>

				<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" class="btn-primary thankyou-whatsapp-btn">
					<i class="fa-brands fa-whatsapp thankyou-whatsapp-icon"></i> <?php esc_html_e( 'Enviar Comprobante por WhatsApp', 'franco-theme' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="centered-box">
			<h2><?php esc_html_e( 'Gracias por tu compra.', 'franco-theme' ); ?></h2>
			<p><?php esc_html_e( 'Tu pedido ha sido recibido correctamente.', 'franco-theme' ); ?></p>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer( 'shop' );
