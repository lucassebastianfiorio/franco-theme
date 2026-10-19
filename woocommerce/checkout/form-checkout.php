<?php
/**
 * Custom WooCommerce Checkout Template (Franco Deportes Multi-Step Layout)
 *
 * @package FrancoTheme
 * @version 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_cart = ( class_exists( 'WooCommerce' ) && WC()->cart );
$customer_postcode = ( class_exists( 'WooCommerce' ) && WC()->customer ) ? WC()->customer->get_shipping_postcode() : '';
if ( empty( $customer_postcode ) && ! empty( $_COOKIE['franco_postcode'] ) ) {
	$customer_postcode = sanitize_text_field( wp_unslash( $_COOKIE['franco_postcode'] ) );
	if ( WC()->customer ) {
		WC()->customer->set_shipping_country( 'AR' );
		WC()->customer->set_billing_country( 'AR' );
		WC()->customer->set_shipping_postcode( $customer_postcode );
		WC()->customer->set_billing_postcode( $customer_postcode );
	}
}

if ( $has_cart && ! WC()->cart->is_empty() ) {
	if ( ! empty( $customer_postcode ) ) {
		WC()->cart->calculate_shipping();
	}
	WC()->cart->calculate_totals();
}

$cart_subtotal      = $has_cart ? franco_clean_price( WC()->cart->get_cart_subtotal() ) : '$0';
$cart_total         = $has_cart ? franco_clean_price( WC()->cart->get_total() ) : '$0';
$shipping_label     = $has_cart ? franco_wc_get_shipping_label() : '';
$needs_shipping     = $has_cart && WC()->cart->needs_shipping();
$available_gateways = class_exists( 'WooCommerce' ) ? WC()->payment_gateways()->get_available_payment_gateways() : array();
$shipping_packages  = $needs_shipping ? WC()->shipping()->get_packages() : array();
$chosen_shipping    = ( $has_cart && WC()->session ) ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();
$ar_states          = class_exists( 'WooCommerce' ) ? WC()->countries->get_states( 'AR' ) : array();
$show_terms         = function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled();
?>

<section class="section checkout-section">
	<div class="container checkout-container">

		<!-- Page Heading -->
		<div class="checkout-heading">
			<span class="checkout-badge">
				<i class="fa-solid fa-lock"></i> <?php esc_html_e( 'PROCESO DE PAGO SEGURO', 'franco-theme' ); ?>
			</span>
			<h1 class="checkout-main-title">
				<?php esc_html_e( 'Finalizar Compra', 'franco-theme' ); ?>
			</h1>
			<p class="checkout-heading-text">
				<?php esc_html_e( 'Completá tus datos paso a paso para confirmar tu pedido en Franco Deportes.', 'franco-theme' ); ?>
			</p>
		</div>

		<!-- Multi-Step Progress Indicator (4 Steps: Resumen - Datos - Entrega - Pago) -->
		<div class="checkout-steps-bar checkout-steps-bar-centered" id="checkout-steps-bar">
			<div class="step-indicator active" data-step="1" onclick="goToCheckoutStep(1)">
				<div class="step-badge">
					<span class="step-num">1</span>
				</div>
				<div class="step-label-group">
					<span class="step-title"><?php esc_html_e( '1. Resumen', 'franco-theme' ); ?></span>
					<span class="step-sub"><?php esc_html_e( 'Resumen de Compra', 'franco-theme' ); ?></span>
				</div>
			</div>
			<div class="step-connector"></div>
			<div class="step-indicator disabled" data-step="2" onclick="goToCheckoutStep(2)">
				<div class="step-badge">
					<span class="step-num">2</span>
				</div>
				<div class="step-label-group">
					<span class="step-title"><?php esc_html_e( '2. Datos', 'franco-theme' ); ?></span>
					<span class="step-sub"><?php esc_html_e( 'Contacto y Facturación', 'franco-theme' ); ?></span>
				</div>
			</div>
			<div class="step-connector"></div>
			<div class="step-indicator disabled" data-step="3" onclick="goToCheckoutStep(3)">
				<div class="step-badge">
					<span class="step-num">3</span>
				</div>
				<div class="step-label-group">
					<span class="step-title"><?php esc_html_e( '3. Entrega', 'franco-theme' ); ?></span>
					<span class="step-sub"><?php esc_html_e( 'Envío o Retiro', 'franco-theme' ); ?></span>
				</div>
			</div>
			<div class="step-connector"></div>
			<div class="step-indicator disabled" data-step="4" onclick="goToCheckoutStep(4)">
				<div class="step-badge">
					<span class="step-num">4</span>
				</div>
				<div class="step-label-group">
					<span class="step-title"><?php esc_html_e( '4. Pago', 'franco-theme' ); ?></span>
					<span class="step-sub"><?php esc_html_e( 'Método y Confirmación', 'franco-theme' ); ?></span>
				</div>
			</div>
		</div>

		<div class="checkout-grid checkout-layout-single" id="checkout-layout">
			<!-- Single Column Container for 4 Step Panes -->
			<div class="checkout-form-column" style="width: 100%;">

				<!-- Error Alert Container -->
				<div id="checkout-error-notice" class="checkout-alert-error checkout-error-notice"></div>

				<form id="custom-franco-checkout-form" method="post" novalidate onsubmit="handleFrancoCheckoutSubmit(event)">
					<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
					<input type="hidden" name="billing_country" value="AR">
					<input type="hidden" name="billing_last_name" value="">

					<!-- Step 1 Pane: Resumen de la Compra -->
					<div class="checkout-card checkout-step-pane" id="checkout-step-pane-1">
						<div class="checkout-step-header">
							<span class="step-number">1</span>
							<div>
								<h3><?php esc_html_e( 'Resumen de la Compra', 'franco-theme' ); ?></h3>
								<p><?php esc_html_e( 'Revisá el detalle de los productos y montos de tu pedido.', 'franco-theme' ); ?></p>
							</div>
						</div>

						<div id="order_review" class="checkout-summary-card woocommerce-checkout-review-order" style="border: none; padding: 0; background: transparent; box-shadow: none;">
							<div class="checkout-summary-top-bar">
								<span class="checkout-summary-subtitle">
									<i class="fa-solid fa-cart-flatbed" style="color: var(--color-primary, #e60000);"></i> <?php esc_html_e( 'Productos en tu pedido', 'franco-theme' ); ?>
								</span>
								<button type="button" id="checkout-clear-cart-btn" class="checkout-clear-cart-btn" onclick="francoClearCart()" title="Eliminar todos los productos del carrito">
									<i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Vaciar Carrito', 'franco-theme' ); ?>
								</button>
							</div>

							<div id="checkout-cart-items" class="checkout-summary-items">
								<!-- Rendered dynamically via WooCommerce Cart API -->
								<div class="checkout-summary-loading">
									<i class="fa-solid fa-spinner fa-spin checkout-summary-spinner"></i>
									<p class="checkout-summary-loading-text"><?php esc_html_e( 'Cargando productos...', 'franco-theme' ); ?></p>
								</div>
							</div>

							<div class="checkout-totals-box woocommerce-checkout-review-order-table">
								<div class="total-row cart-subtotal">
									<span><?php esc_html_e( 'Subtotal Productos:', 'franco-theme' ); ?></span>
									<strong id="checkout-subtotal" class="amount woocommerce-Price-amount"><span class="woocommerce-Price-amount amount"><?php echo esc_html( $cart_subtotal ); ?></span></strong>
								</div>
								<div class="total-row shipping">
									<span><?php esc_html_e( 'Costo de Envío:', 'franco-theme' ); ?></span>
									<strong id="checkout-shipping" class="checkout-shipping-value amount woocommerce-Price-amount"><?php echo esc_html( $shipping_label ); ?></strong>
								</div>

								<!-- Step 1 Postcode Calculator Row -->
								<div class="checkout-postcode-calc-row" id="checkout-postcode-calc-row">
									<div class="checkout-postcode-calc-header">
										<div>
											<i class="fa-solid fa-truck-fast text-primary"></i>
											<span id="checkout-step1-cp-status">
												<?php if ( ! empty( $customer_postcode ) ) : ?>
													<?php printf( esc_html__( 'Envío cotizado para CP %s', 'franco-theme' ), '<strong>' . esc_html( $customer_postcode ) . '</strong>' ); ?>
												<?php else : ?>
													<?php esc_html_e( 'Ingresá tu código postal para cotizar envío:', 'franco-theme' ); ?>
												<?php endif; ?>
											</span>
										</div>
										<button type="button" class="link-btn-text" id="checkout-step1-cp-toggle-btn" onclick="francoToggleStep1PostcodeEdit()">
											<?php echo ! empty( $customer_postcode ) ? esc_html__( 'Cambiar', 'franco-theme' ) : esc_html__( 'Ingresar CP', 'franco-theme' ); ?>
										</button>
									</div>
									<div class="checkout-postcode-calc-input-wrap <?php echo ! empty( $customer_postcode ) ? 'is-hidden' : ''; ?>" id="checkout-step1-cp-wrap">
										<input type="text" id="checkout-step1-postcode-input" class="form-input" placeholder="Ej: 1650" value="<?php echo esc_attr( $customer_postcode ); ?>" maxlength="8" autocomplete="postal-code">
										<button type="button" class="btn-primary" id="checkout-step1-postcode-btn" onclick="francoApplyStep1Postcode()"><?php esc_html_e( 'Calcular', 'franco-theme' ); ?></button>
									</div>
								</div>
								<div class="total-row total-row-final order-total">
									<span><?php esc_html_e( 'Total Estimado:', 'franco-theme' ); ?></span>
									<strong id="checkout-total" class="text-primary amount woocommerce-Price-amount"><span class="woocommerce-Price-amount amount"><?php echo esc_html( $cart_total ); ?></span></strong>
								</div>
							</div>

							<!-- Trust & Security Badges Box -->
							<div class="checkout-guarantee-box">
								<div class="guarantee-item">
									<i class="fa-solid fa-shield-cat text-primary"></i>
									<div>
										<strong>Garantía de Satisfacción</strong>
										<span>Productos 100% Originales y con Cambio</span>
									</div>
								</div>
								<div class="guarantee-item">
									<i class="fa-brands fa-whatsapp text-success"></i>
									<div>
										<strong>Atención por WhatsApp</strong>
										<span>Asistencia directa en tu compra</span>
									</div>
								</div>
							</div>
						</div>

						<div class="step-actions-bar step-actions-single" style="margin-top: 24px;">
							<button type="button" class="btn-primary step-btn-next" onclick="nextCheckoutStep(1)">
								<?php esc_html_e( 'Siguiente: Mis Datos', 'franco-theme' ); ?> <i class="fa-solid fa-arrow-right"></i>
							</button>
						</div>
					</div>

					<!-- Step 2 Pane: Customer Info -->
					<div class="checkout-card checkout-step-pane is-hidden" id="checkout-step-pane-2">
						<div class="checkout-step-header">
							<span class="step-number">2</span>
							<div>
								<h3><?php esc_html_e( 'Información Personal y Contacto', 'franco-theme' ); ?></h3>
								<p><?php esc_html_e( 'Ingresá los datos del comprador para la factura y notificaciones del envío.', 'franco-theme' ); ?></p>
							</div>
						</div>

						<div class="checkout-form-grid checkout-grid-personal-top">
							<div class="form-group">
								<label class="form-label"><?php esc_html_e( 'Nombre y Apellido Completo *', 'franco-theme' ); ?></label>
								<input type="text" name="billing_first_name" required class="form-input" placeholder="Ej: Juan Carlos Pérez">
							</div>
							<div class="form-group">
								<label class="form-label"><?php esc_html_e( 'DNI / CUIT (Obligatorio) *', 'franco-theme' ); ?></label>
								<input type="text" name="billing_dni" required class="form-input" placeholder="Ej: 38.123.456">
							</div>
						</div>

						<div class="checkout-form-grid checkout-grid-personal">
							<div class="form-group">
								<label class="form-label"><?php esc_html_e( 'Correo Electrónico *', 'franco-theme' ); ?></label>
								<input type="email" name="billing_email" required class="form-input" placeholder="tu-email@ejemplo.com">
								<span class="form-hint"><?php esc_html_e( 'Enviaremos la confirmación del pedido a este email.', 'franco-theme' ); ?></span>
							</div>
							<div class="form-group">
								<label class="form-label"><?php esc_html_e( 'Teléfono / WhatsApp *', 'franco-theme' ); ?></label>
								<input type="tel" name="billing_phone" required class="form-input" placeholder="Ej: 11 1234 5678">
								<span class="form-hint"><?php esc_html_e( 'Para coordinar la entrega o notificaciones de estado.', 'franco-theme' ); ?></span>
							</div>
						</div>

						<div class="step-actions-bar step-actions-split">
							<button type="button" class="btn-secondary step-btn-prev" onclick="prevCheckoutStep(2)">
								<i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Volver al Resumen', 'franco-theme' ); ?>
							</button>
							<button type="button" class="btn-primary step-btn-next" onclick="nextCheckoutStep(2)">
								<?php esc_html_e( 'Siguiente: Opciones de Entrega', 'franco-theme' ); ?> <i class="fa-solid fa-arrow-right"></i>
							</button>
						</div>
					</div>

					<!-- Step 3 Pane: Shipping Method & Address -->
					<div class="checkout-card checkout-step-pane is-hidden" id="checkout-step-pane-3">
						<div class="checkout-step-header">
							<span class="step-number">3</span>
							<div>
								<h3><?php esc_html_e( 'Opciones de Entrega y Envío', 'franco-theme' ); ?></h3>
								<p><?php esc_html_e( 'Seleccioná cómo deseas recibir tus productos.', 'franco-theme' ); ?></p>
							</div>
						</div>

						<div id="dynamic-shipping-methods-container" class="choice-list shipping-methods-list">
							<?php echo franco_wc_render_shipping_methods_html(); ?>
						</div>

						<div id="address-fields" class="address-fields-box">
							<h4 class="address-fields-title">
								<i class="fa-solid fa-location-dot text-primary"></i> <?php esc_html_e( 'Dirección de Entrega', 'franco-theme' ); ?>
							</h4>
							<div class="form-group form-group-spaced">
								<label class="form-label"><?php esc_html_e( 'Calle, Número, Piso y Departamento *', 'franco-theme' ); ?></label>
								<input type="text" name="billing_address_1" class="form-input" placeholder="Ej: Av. San Martín 1234 4to B">
							</div>
							<div class="checkout-form-grid checkout-grid-address">
								<div class="form-group">
									<label class="form-label"><?php esc_html_e( 'Localidad / Ciudad *', 'franco-theme' ); ?></label>
									<input type="text" name="billing_city" class="form-input" placeholder="Ej: San Martín">
								</div>
								<div class="form-group">
									<label class="form-label"><?php esc_html_e( 'Provincia *', 'franco-theme' ); ?></label>
									<select name="billing_state" id="billing_state" class="form-input">
										<option value=""><?php esc_html_e( 'Seleccioná tu provincia', 'franco-theme' ); ?></option>
										<?php foreach ( $ar_states as $state_code => $state_name ) : ?>
											<option value="<?php echo esc_attr( $state_code ); ?>"><?php echo esc_html( $state_name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="form-group">
									<label class="form-label"><?php esc_html_e( 'Código Postal *', 'franco-theme' ); ?></label>
									<input type="text" name="billing_postcode" class="form-input" placeholder="Ej: 1650" value="<?php echo esc_attr( $customer_postcode ); ?>">
								</div>
							</div>
						</div>

						<div class="step-actions-bar step-actions-split">
							<button type="button" class="btn-secondary step-btn-prev" onclick="prevCheckoutStep(3)">
								<i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Volver a Mis Datos', 'franco-theme' ); ?>
							</button>
							<button type="button" class="btn-primary step-btn-next" onclick="nextCheckoutStep(3)">
								<?php esc_html_e( 'Siguiente: Método de Pago', 'franco-theme' ); ?> <i class="fa-solid fa-arrow-right"></i>
							</button>
						</div>
					</div>

					<!-- Step 4 Pane: Payment Gateways -->
					<div class="checkout-card checkout-step-pane is-hidden" id="checkout-step-pane-4">
						<div class="checkout-step-header">
							<span class="step-number">4</span>
							<div>
								<h3><?php esc_html_e( 'Método de Pago y Confirmación', 'franco-theme' ); ?></h3>
								<p><?php esc_html_e( 'Seleccioná la forma de pago preferida para abonar tu orden.', 'franco-theme' ); ?></p>
							</div>
						</div>

						<div id="dynamic-payment-gateways-container" class="choice-list wc_payment_methods payment_methods">
							<?php if ( ! empty( $available_gateways ) ) : ?>
								<?php
								// Per user requirement: all payment options start collapsed and none is checked by default
								$chosen_gateway = ( $has_cart && WC()->session ) ? WC()->session->get( 'chosen_payment_method' ) : '';
								if ( $chosen_gateway && ! isset( $available_gateways[ $chosen_gateway ] ) ) {
									$chosen_gateway = '';
								}
								foreach ( $available_gateways as $gateway_id => $gateway ) :
									$is_selected = ( ! empty( $chosen_gateway ) && $gateway_id === $chosen_gateway );
									?>
									<div class="checkout-payment-option payment_method_<?php echo esc_attr( $gateway_id ); ?>">
										<label class="checkout-choice-card <?php echo $is_selected ? 'active' : ''; ?>">
											<input type="radio" id="payment_method_<?php echo esc_attr( $gateway_id ); ?>" name="payment_method" value="<?php echo esc_attr( $gateway_id ); ?>" <?php echo $is_selected ? 'checked="checked"' : ''; ?> class="choice-radio input-radio">
											<div class="choice-content">
												<div class="choice-title-row">
													<strong class="choice-title text-primary"><?php echo esc_html( $gateway->get_title() ); ?></strong>
												</div>
												<?php if ( ! $gateway->has_fields() && $gateway->get_description() ) : ?>
													<span class="choice-desc"><?php echo esc_html( wp_strip_all_tags( $gateway->get_description() ) ); ?></span>
												<?php endif; ?>
											</div>
										</label>
										<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
											<div class="payment_box payment_method_<?php echo esc_attr( $gateway_id ); ?>" style="<?php echo $is_selected ? 'display:block;' : 'display:none;'; ?>">
												<?php if ( $gateway->has_fields() ) : ?>
													<?php $gateway->payment_fields(); ?>
												<?php else : ?>
													<p><?php echo wp_kses_post( $gateway->get_description() ); ?></p>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<div class="checkout-alert-error checkout-alert-flush">
									<i class="fa-solid fa-triangle-exclamation"></i> <?php esc_html_e( 'No hay métodos de pago disponibles en este momento. Contactanos por WhatsApp.', 'franco-theme' ); ?>
								</div>
							<?php endif; ?>
						</div>

						<?php if ( $show_terms ) : ?>
							<div class="checkout-terms-box">
								<label class="checkout-terms-label">
									<input type="checkbox" name="terms" id="terms" value="1" class="checkout-terms-checkbox">
									<span><?php wc_terms_and_conditions_checkbox_text(); ?> *</span>
								</label>
								<input type="hidden" name="terms-field" value="1">
							</div>
						<?php endif; ?>

						<div class="step-actions-bar step-actions-final">
							<button type="button" class="btn-secondary step-btn-prev" onclick="prevCheckoutStep(4)">
								<i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Volver a Entrega', 'franco-theme' ); ?>
							</button>

							<button type="submit" id="checkout-submit-btn" class="checkout-submit-btn checkout-submit-flex">
								<i class="fa-solid fa-circle-check"></i> <?php esc_html_e( 'Confirmar y Finalizar Pedido', 'franco-theme' ); ?>
							</button>
						</div>

						<?php if ( ! $show_terms ) : ?>
							<p class="checkout-terms-note">
								<i class="fa-solid fa-lock text-success"></i> <?php esc_html_e( 'Al hacer clic en "Confirmar", aceptas nuestros términos y condiciones de compra.', 'franco-theme' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

	</div>
</section>
