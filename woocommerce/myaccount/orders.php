<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );
?>

<div class="franco-orders-container">
	<div class="franco-orders-header">
		<h2 class="franco-section-title"><?php esc_html_e( 'Mis Compras', 'franco' ); ?></h2>
		<p class="franco-section-subtitle"><?php esc_html_e( 'Revisá el historial, seguimiento y detalle de todos tus pedidos.', 'franco' ); ?></p>
	</div>

	<?php if ( $has_orders ) : ?>
		<div class="franco-orders-list">
			<?php
			foreach ( $customer_orders->orders as $customer_order ) :
				$order      = wc_get_order( $customer_order );
				if ( ! $order ) {
					continue;
				}
				$item_count = $order->get_item_count();
				$status     = $order->get_status();
				$status_name = wc_get_order_status_name( $status );
				$order_date = wc_format_datetime( $order->get_date_created() );
				$actions    = wc_get_account_orders_actions( $order );
				$items      = $order->get_items();
				?>
				<div class="franco-order-card" id="order-<?php echo esc_attr( $order->get_id() ); ?>">
					<div class="franco-order-card-header">
						<div class="franco-order-meta-col">
							<span class="franco-order-num">
								<i class="fas fa-receipt" aria-hidden="true"></i>
								#<?php echo esc_html( $order->get_order_number() ); ?>
							</span>
							<span class="franco-order-date"><?php echo esc_html( $order_date ); ?></span>
						</div>

						<div class="franco-order-status-col">
							<span class="franco-status-badge status-<?php echo esc_attr( $status ); ?>">
								<?php echo esc_html( $status_name ); ?>
							</span>
						</div>
					</div>

					<div class="franco-order-card-body">
						<div class="franco-order-items-preview">
							<?php
							$shown = 0;
							foreach ( $items as $item_id => $item ) :
								if ( $shown >= 4 ) {
									break;
								}
								$product = $item->get_product();
								$thumbnail = $product ? $product->get_image( array( 60, 60 ) ) : '';
								$item_name = $item->get_name();
								$item_qty  = $item->get_quantity();
								$shown++;
								?>
								<div class="franco-order-item-thumb" title="<?php echo esc_attr( $item_name ); ?> (x<?php echo esc_attr( $item_qty ); ?>)">
									<?php if ( $thumbnail ) : ?>
										<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php else : ?>
										<div class="thumb-placeholder"><i class="fas fa-box" aria-hidden="true"></i></div>
									<?php endif; ?>
									<span class="thumb-qty">x<?php echo esc_html( $item_qty ); ?></span>
								</div>
							<?php endforeach; ?>

							<?php if ( count( $items ) > 4 ) : ?>
								<div class="franco-order-item-more">
									+<?php echo esc_html( count( $items ) - 4 ); ?>
								</div>
							<?php endif; ?>

							<div class="franco-order-items-summary">
								<span class="items-count-text">
									<?php echo esc_html( $item_count ); ?> <?php echo esc_html( _n( 'artículo', 'artículos', $item_count, 'franco' ) ); ?>
								</span>
							</div>
						</div>

						<div class="franco-order-total-block">
							<span class="total-label"><?php esc_html_e( 'Total:', 'franco' ); ?></span>
							<span class="total-amount"><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</div>
					</div>

					<div class="franco-order-card-footer">
						<div class="franco-order-actions">
							<?php
							if ( ! empty( $actions ) ) {
								foreach ( $actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									$btn_class = 'view' === $key ? 'franco-btn-primary' : 'franco-btn-outline';
									echo '<a href="' . esc_url( $action['url'] ) . '" class="franco-btn-sm ' . esc_attr( $btn_class ) . ' order-action-' . sanitize_html_class( $key ) . '">';
									if ( 'view' === $key ) {
										echo '<i class="fas fa-eye" aria-hidden="true"></i> ';
									} elseif ( 'pay' === $key ) {
										echo '<i class="fas fa-credit-card" aria-hidden="true"></i> ';
									} elseif ( 'cancel' === $key ) {
										echo '<i class="fas fa-times" aria-hidden="true"></i> ';
									}
									echo esc_html( $action['name'] );
									echo '</a>';
								}
							}
							?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

		<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
			<div class="franco-pagination-wrap woocommerce-pagination">
				<?php if ( 1 !== $current_page ) : ?>
					<a class="franco-pagination-btn prev" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">
						<i class="fas fa-chevron-left" aria-hidden="true"></i> <?php esc_html_e( 'Anterior', 'franco' ); ?>
					</a>
				<?php endif; ?>

				<span class="franco-pagination-current">
					<?php
					/* translators: 1: current page 2: max pages */
					printf( esc_html__( 'Página %1$d de %2$d', 'franco' ), (int) $current_page, (int) $customer_orders->max_num_pages );
					?>
				</span>

				<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
					<a class="franco-pagination-btn next" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">
						<?php esc_html_e( 'Siguiente', 'franco' ); ?> <i class="fas fa-chevron-right" aria-hidden="true"></i>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<div class="franco-orders-empty">
			<div class="franco-empty-icon">
				<i class="fas fa-shopping-bag" aria-hidden="true"></i>
			</div>
			<h3 class="franco-empty-title"><?php esc_html_e( 'Aún no tenés ningún pedido', 'franco' ); ?></h3>
			<p class="franco-empty-desc"><?php esc_html_e( 'Explorá nuestro catálogo y descubrí los mejores productos disponibles.', 'franco' ); ?></p>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="franco-btn franco-btn-primary">
				<i class="fas fa-store" aria-hidden="true"></i> <?php esc_html_e( 'Ver Tienda', 'franco' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
