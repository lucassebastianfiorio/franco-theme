<?php
/**
 * My Account Dashboard
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$first_name   = $current_user->first_name ?: $current_user->display_name;

$whatsapp_raw = franco_get_setting( 'franco_whatsapp_number', '' );
$whatsapp_clean = preg_replace( '/\D/', '', $whatsapp_raw );

// Get customer orders count
$customer_orders = wc_get_orders( array(
	'customer_id' => $current_user->ID,
	'limit'       => -1,
	'return'      => 'ids',
) );
$orders_count = count( $customer_orders );

do_action( 'woocommerce_account_dashboard' );
?>

<div class="franco-dashboard-welcome">
	<div class="franco-dashboard-hero">
		<h2 class="franco-dashboard-title">
			<?php
			/* translators: 1: user first name */
			printf( esc_html__( '¡Bienvenido a tu cuenta, %s!', 'franco' ), '<span>' . esc_html( $first_name ) . '</span>' );
			?>
		</h2>
		<p class="franco-dashboard-subtitle">
			<?php esc_html_e( 'Desde aquí podés ver tus compras recientes, gestionar tus direcciones de envío y actualizar los datos de tu cuenta.', 'franco' ); ?>
		</p>
	</div>

	<div class="franco-dashboard-grid">
		<!-- Orders Card -->
		<div class="franco-dash-card">
			<div class="franco-dash-card-icon">
				<i class="fas fa-box-open" aria-hidden="true"></i>
			</div>
			<div class="franco-dash-card-body">
				<span class="franco-dash-card-badge"><?php echo esc_html( $orders_count ); ?> <?php echo esc_html( _n( 'pedido', 'pedidos', $orders_count, 'franco' ) ); ?></span>
				<h4 class="franco-dash-card-title"><?php esc_html_e( 'Mis Compras', 'franco' ); ?></h4>
				<p class="franco-dash-card-text"><?php esc_html_e( 'Consultá el estado y seguimiento de tus pedidos.', 'franco' ); ?></p>
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="franco-btn-sm franco-btn-outline">
					<?php esc_html_e( 'Ver pedidos', 'franco' ); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i>
				</a>
			</div>
		</div>

		<!-- Addresses Card -->
		<div class="franco-dash-card">
			<div class="franco-dash-card-icon">
				<i class="fas fa-map-marked-alt" aria-hidden="true"></i>
			</div>
			<div class="franco-dash-card-body">
				<span class="franco-dash-card-badge"><?php esc_html_e( 'Direcciones', 'franco' ); ?></span>
				<h4 class="franco-dash-card-title"><?php esc_html_e( 'Dirección de Entrega', 'franco' ); ?></h4>
				<p class="franco-dash-card-text"><?php esc_html_e( 'Administrá tus datos de facturación y entrega de productos.', 'franco' ); ?></p>
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>" class="franco-btn-sm franco-btn-outline">
					<?php esc_html_e( 'Administrar', 'franco' ); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i>
				</a>
			</div>
		</div>

		<!-- Account Details Card -->
		<div class="franco-dash-card">
			<div class="franco-dash-card-icon">
				<i class="fas fa-user-shield" aria-hidden="true"></i>
			</div>
			<div class="franco-dash-card-body">
				<span class="franco-dash-card-badge"><?php esc_html_e( 'Seguridad', 'franco' ); ?></span>
				<h4 class="franco-dash-card-title"><?php esc_html_e( 'Datos y Contraseña', 'franco' ); ?></h4>
				<p class="franco-dash-card-text"><?php esc_html_e( 'Modificá tu correo electrónico, DNI o cambiá tu clave de acceso.', 'franco' ); ?></p>
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" class="franco-btn-sm franco-btn-outline">
					<?php esc_html_e( 'Editar datos', 'franco' ); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i>
				</a>
			</div>
		</div>

		<!-- WhatsApp Direct Support Card -->
		<?php if ( ! empty( $whatsapp_clean ) ) : ?>
		<div class="franco-dash-card franco-dash-card-highlight">
			<div class="franco-dash-card-icon icon-whatsapp">
				<i class="fab fa-whatsapp" aria-hidden="true"></i>
			</div>
			<div class="franco-dash-card-body">
				<span class="franco-dash-card-badge badge-support"><?php esc_html_e( 'Atención Directa', 'franco' ); ?></span>
				<h4 class="franco-dash-card-title"><?php esc_html_e( '¿Necesitás Ayuda?', 'franco' ); ?></h4>
				<p class="franco-dash-card-text"><?php esc_html_e( 'Chateá directamente con nuestro equipo ante cualquier duda con tu compra.', 'franco' ); ?></p>
				<a href="https://wa.me/<?php echo esc_attr( $whatsapp_clean ); ?>?text=<?php echo rawurlencode( 'Hola, me comunico desde Mi Cuenta en la tienda.' ); ?>" target="_blank" rel="noopener noreferrer" class="franco-btn-sm franco-btn-whatsapp">
					<i class="fab fa-whatsapp" aria-hidden="true"></i> <?php esc_html_e( 'Contactar por WhatsApp', 'franco' ); ?>
				</a>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php
/**
 * Deprecated / hook points
 */
do_action( 'woocommerce_after_my_account' );
