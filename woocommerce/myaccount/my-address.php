<?php
/**
 * My Addresses
 *
 * Shows billing and shipping addresses on the account page.
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => __( 'Dirección de Facturación', 'franco-theme' ),
			'shipping' => __( 'Dirección de Envío', 'franco-theme' ),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __( 'Dirección de Facturación', 'franco-theme' ),
		),
		$customer_id
	);
}

$oldcol = 1;
$col    = 1;
?>

<div class="franco-addresses-container">
	<div class="franco-addresses-header">
		<h2 class="franco-section-title"><?php esc_html_e( 'Mis Direcciones', 'franco-theme' ); ?></h2>
		<p class="franco-section-subtitle">
			<?php echo apply_filters( 'woocommerce_my_account_my_address_description', esc_html__( 'Las siguientes direcciones se utilizarán de forma predeterminada al tramitar tus pedidos.', 'franco-theme' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>
	</div>

	<?php do_action( 'woocommerce_before_edit_account_address_headline' ); ?>

	<div class="franco-addresses-grid">
		<?php foreach ( $get_addresses as $name => $address_title ) :
			$address = wc_get_account_formatted_address( $name );
			$edit_url = wc_get_endpoint_url( 'edit-address', $name );
			$icon_class = ( 'shipping' === $name ) ? 'fas fa-truck' : 'fas fa-file-invoice';
			?>
			<div class="franco-address-card address-col-<?php echo esc_attr( $name ); ?>">
				<div class="franco-address-card-header">
					<div class="address-title-wrap">
						<i class="<?php echo esc_attr( $icon_class ); ?> address-icon" aria-hidden="true"></i>
						<h3 class="address-title"><?php echo esc_html( $address_title ); ?></h3>
					</div>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="franco-btn-sm franco-btn-outline edit-address-btn">
						<i class="fas fa-edit" aria-hidden="true"></i> <?php echo $address ? esc_html__( 'Editar', 'franco-theme' ) : esc_html__( 'Agregar', 'franco-theme' ); ?>
					</a>
				</div>

				<div class="franco-address-card-body">
					<?php if ( $address ) : ?>
						<address class="address-formatted">
							<?php echo wp_kses_post( $address ); ?>
						</address>
					<?php else : ?>
						<div class="address-empty-notice">
							<i class="fas fa-map-pin" aria-hidden="true"></i>
							<p><?php esc_html_e( 'Todavía no agregaste esta dirección.', 'franco-theme' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<?php do_action( 'woocommerce_after_my_account_my_address' ); ?>
