<?php
/**
 * View Order
 *
 * Shows the details of a particular order on the account page.
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );
if ( ! $order ) {
	return;
}

$status       = $order->get_status();
$status_name  = wc_get_order_status_name( $status );
$order_number = $order->get_order_number();
$order_date   = wc_format_datetime( $order->get_date_created() );
$items        = $order->get_items();
$notes        = $order->get_customer_order_notes();

$whatsapp_raw   = franco_get_setting( 'franco_whatsapp_number', '' );
$whatsapp_clean = preg_replace( '/\D/', '', $whatsapp_raw );

// Status timeline step calculation
$step1_done = true; // Always placed
$step2_done = in_array( $status, array( 'processing', 'completed', 'shipping', 'delivered' ), true );
$step2_active = in_array( $status, array( 'pending', 'on-hold' ), true );
$step3_done = in_array( $status, array( 'completed', 'delivered' ), true );
$step3_active = in_array( $status, array( 'processing', 'shipping' ), true );
$step4_done = ( 'completed' === $status );
$is_cancelled = in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true );
?>

<div class="franco-view-order-container">
	<div class="franco-view-order-topbar">
		<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="franco-back-link">
			<i class="fas fa-arrow-left" aria-hidden="true"></i> <?php esc_html_e( 'Volver a Mis Compras', 'franco-theme' ); ?>
		</a>

		<?php if ( ! empty( $whatsapp_clean ) ) : ?>
			<a href="https://wa.me/<?php echo esc_attr( $whatsapp_clean ); ?>?text=<?php echo rawurlencode( 'Hola, consulto por mi pedido #' . $order_number ); ?>" target="_blank" rel="noopener noreferrer" class="franco-btn-sm franco-btn-whatsapp">
				<i class="fab fa-whatsapp" aria-hidden="true"></i> <?php esc_html_e( 'Ayuda con este pedido', 'franco-theme' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<!-- Order Header Banner -->
	<div class="franco-view-order-header">
		<div class="franco-view-order-title-wrap">
			<span class="franco-order-kicker"><?php esc_html_e( 'Detalle del Pedido', 'franco-theme' ); ?></span>
			<h2 class="franco-order-heading">#<?php echo esc_html( $order_number ); ?></h2>
			<span class="franco-order-datetime">
				<i class="far fa-calendar-alt" aria-hidden="true"></i> <?php echo esc_html( $order_date ); ?>
			</span>
		</div>

		<div class="franco-view-order-status-badge">
			<span class="franco-status-badge status-<?php echo esc_attr( $status ); ?>">
				<?php echo esc_html( $status_name ); ?>
			</span>
		</div>
	</div>

	<!-- Order Timeline -->
	<?php if ( ! $is_cancelled ) : ?>
	<div class="franco-order-timeline-card">
		<h4 class="franco-card-title"><?php esc_html_e( 'Estado del Envío', 'franco-theme' ); ?></h4>
		<div class="franco-timeline-track">
			<div class="franco-timeline-step is-completed">
				<div class="step-circle"><i class="fas fa-check" aria-hidden="true"></i></div>
				<div class="step-label"><?php esc_html_e( 'Pedido Recibido', 'franco-theme' ); ?></div>
			</div>

			<div class="franco-timeline-step <?php echo $step2_done ? 'is-completed' : ( $step2_active ? 'is-active' : '' ); ?>">
				<div class="step-circle"><i class="fas <?php echo $step2_done ? 'fa-check' : 'fa-credit-card'; ?>" aria-hidden="true"></i></div>
				<div class="step-label"><?php esc_html_e( 'Pago Confirmado', 'franco-theme' ); ?></div>
			</div>

			<div class="franco-timeline-step <?php echo $step3_done ? 'is-completed' : ( $step3_active ? 'is-active' : '' ); ?>">
				<div class="step-circle"><i class="fas <?php echo $step3_done ? 'fa-check' : 'fa-box'; ?>" aria-hidden="true"></i></div>
				<div class="step-label"><?php esc_html_e( 'En Preparación', 'franco-theme' ); ?></div>
			</div>

			<div class="franco-timeline-step <?php echo $step4_done ? 'is-completed' : ''; ?>">
				<div class="step-circle"><i class="fas <?php echo $step4_done ? 'fa-check' : 'fa-truck'; ?>" aria-hidden="true"></i></div>
				<div class="step-label"><?php esc_html_e( 'Entregado', 'franco-theme' ); ?></div>
			</div>
		</div>
	</div>
	<?php else : ?>
	<div class="franco-order-cancelled-notice">
		<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
		<div>
			<strong><?php esc_html_e( 'Pedido no completado', 'franco-theme' ); ?></strong>
			<p><?php esc_html_e( 'Este pedido se encuentra cancelado o fallido. Si necesitás más información por favor contactanos.', 'franco-theme' ); ?></p>
		</div>
	</div>
	<?php endif; ?>

	<!-- Items List with Item N° -->
	<div class="franco-order-products-card">
		<h4 class="franco-card-title"><?php esc_html_e( 'Productos en este pedido', 'franco-theme' ); ?></h4>
		<div class="franco-order-items-table">
			<?php foreach ( $items as $item_id => $item ) :
				$product   = $item->get_product();
				$thumbnail = $product ? $product->get_image( array( 80, 80 ) ) : '';
				$raw_name  = $item->get_name();

				// Retrieve Item N° code
				$item_code = $item->get_meta( 'Item N°' ) ?: $item->get_meta( '_item_code' );
				if ( empty( $item_code ) && $product && function_exists( 'franco_get_product_item_code' ) ) {
					$item_code = franco_get_product_item_code( $product );
				}

				// Display clean name (without last word if setting is on)
				$clean_name = ( '1' === (string) franco_get_setting( 'franco_remove_last_word_product_title', '0' ) && function_exists( 'franco_strip_last_word' ) ) ? franco_strip_last_word( $raw_name ) : $raw_name;
				?>
				<div class="franco-order-product-row">
					<div class="product-thumb">
						<?php if ( $thumbnail ) : ?>
							<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<div class="thumb-placeholder"><i class="fas fa-box" aria-hidden="true"></i></div>
						<?php endif; ?>
					</div>

					<div class="product-info">
						<span class="product-title"><?php echo esc_html( $clean_name ); ?></span>
						<?php if ( ! empty( $item_code ) ) : ?>
							<span class="product-item-code-badge">
								<i class="fas fa-barcode" aria-hidden="true"></i> <?php printf( esc_html__( 'Item N° %s', 'franco-theme' ), esc_html( $item_code ) ); ?>
							</span>
						<?php endif; ?>

						<?php
						// Variations / line item meta
						$formatted_meta = $item->get_formatted_meta_data();
						if ( ! empty( $formatted_meta ) ) :
						?>
							<div class="product-meta-list">
								<?php foreach ( $formatted_meta as $meta_id => $meta ) :
									if ( in_array( $meta->key, array( 'Item N°', '_item_code' ), true ) ) {
										continue;
									}
								?>
									<span class="product-meta-pill"><?php echo esc_html( $meta->display_key ); ?>: <?php echo wp_kses_post( $meta->display_value ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<span class="product-qty-sub"><?php esc_html_e( 'Cantidad:', 'franco-theme' ); ?> <strong><?php echo esc_html( $item->get_quantity() ); ?></strong></span>
					</div>

					<div class="product-price-col">
						<span class="product-total"><?php echo $order->get_formatted_line_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Totals and Addresses Grid -->
	<div class="franco-view-order-details-grid">
		<!-- Summary Totals -->
		<div class="franco-card franco-order-totals-card">
			<h4 class="franco-card-title"><?php esc_html_e( 'Resumen del Pedido', 'franco-theme' ); ?></h4>
			<ul class="franco-totals-list">
				<?php foreach ( $order->get_order_item_totals() as $key => $total ) : ?>
					<li class="franco-total-item total-<?php echo sanitize_html_class( $key ); ?>">
						<span class="label"><?php echo esc_html( $total['label'] ); ?></span>
						<span class="value"><?php echo wp_kses_post( $total['value'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<!-- Addresses & Payment -->
		<div class="franco-card franco-order-info-card">
			<h4 class="franco-card-title"><?php esc_html_e( 'Datos de Entrega y Facturación', 'franco-theme' ); ?></h4>

			<div class="franco-order-address-columns">
				<?php if ( $order->get_formatted_shipping_address() ) : ?>
					<div class="address-box">
						<span class="address-type"><i class="fas fa-truck" aria-hidden="true"></i> <?php esc_html_e( 'Dirección de Envío', 'franco-theme' ); ?></span>
						<address class="address-content">
							<?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?>
						</address>
					</div>
				<?php endif; ?>

				<div class="address-box">
					<span class="address-type"><i class="fas fa-file-invoice" aria-hidden="true"></i> <?php esc_html_e( 'Dirección de Facturación', 'franco-theme' ); ?></span>
					<address class="address-content">
						<?php echo wp_kses_post( $order->get_formatted_billing_address() ); ?>
					</address>
					<?php if ( $order->get_billing_phone() ) : ?>
						<p class="address-phone"><i class="fas fa-phone-alt" aria-hidden="true"></i> <?php echo esc_html( $order->get_billing_phone() ); ?></p>
					<?php endif; ?>
					<?php if ( $order->get_billing_email() ) : ?>
						<p class="address-email"><i class="fas fa-envelope" aria-hidden="true"></i> <?php echo esc_html( $order->get_billing_email() ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Customer Notes / Order updates -->
	<?php if ( $notes ) : ?>
	<div class="franco-order-notes-card">
		<h4 class="franco-card-title"><?php esc_html_e( 'Actualizaciones del Pedido', 'franco-theme' ); ?></h4>
		<ul class="franco-order-notes-list">
			<?php foreach ( $notes as $note ) : ?>
				<li class="franco-order-note-item">
					<span class="note-date"><i class="far fa-clock" aria-hidden="true"></i> <?php echo esc_html( date_i18n( __( 'd/m/Y H:i', 'franco-theme' ), strtotime( $note->comment_date ) ) ); ?></span>
					<div class="note-text"><?php echo wpautop( wptexturize( $note->comment_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
</div>

<div class="franco-wc-view-order-hook-wrap" style="display: none;">
	<?php
	// Maintain core action hook for third-party plugins while hiding duplicate table
	do_action( 'woocommerce_view_order', $order_id );
	?>
</div>
