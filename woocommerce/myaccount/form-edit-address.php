<?php
/**
 * Edit address form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-address.php.
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? esc_html__( 'Dirección de Facturación', 'franco-theme' ) : esc_html__( 'Dirección de Envío', 'franco-theme' );

do_action( 'woocommerce_before_edit_account_address_form' );
?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<div class="franco-edit-address-wrap">
		<div class="franco-form-header">
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>" class="franco-back-link">
				<i class="fas fa-arrow-left" aria-hidden="true"></i> <?php esc_html_e( 'Volver a Direcciones', 'franco-theme' ); ?>
			</a>
			<h2 class="franco-section-title"><?php echo esc_html( $page_title ); ?></h2>
			<p class="franco-section-subtitle"><?php esc_html_e( 'Completá los datos requeridos para que podamos procesar tus pedidos correctamente.', 'franco-theme' ); ?></p>
		</div>

		<form method="post" class="franco-address-form">

			<div class="woocommerce-address-fields">
				<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

				<div class="woocommerce-address-fields__field-wrapper franco-address-fields-grid">
					<?php
					foreach ( $address as $key => $field ) {
						woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
					}
					?>
				</div>

				<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

				<div class="franco-form-actions">
					<button type="submit" class="franco-btn franco-btn-primary" name="save_address" value="<?php esc_attr_e( 'Guardar Dirección', 'franco-theme' ); ?>">
						<i class="fas fa-save" aria-hidden="true"></i> <?php esc_html_e( 'Guardar Dirección', 'franco-theme' ); ?>
					</button>
					<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
					<input type="hidden" name="action" value="edit_address" />
				</div>
			</div>

		</form>
	</div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
