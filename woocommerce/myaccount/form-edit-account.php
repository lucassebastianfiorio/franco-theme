<?php
/**
 * Edit account form
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

$user_dni = get_user_meta( $user->ID, 'billing_dni', true );

do_action( 'woocommerce_before_edit_account_form' );
?>

<div class="franco-edit-account-wrap">
	<div class="franco-form-header">
		<h2 class="franco-section-title"><?php esc_html_e( 'Datos de la Cuenta', 'franco' ); ?></h2>
		<p class="franco-section-subtitle"><?php esc_html_e( 'Actualizá tu información personal, correo electrónico y contraseña de acceso.', 'franco' ); ?></p>
	</div>

	<form class="woocommerce-EditAccountForm edit-account franco-account-form" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>

		<div class="franco-form-section">
			<h4 class="franco-section-legend"><i class="fas fa-id-card" aria-hidden="true"></i> <?php esc_html_e( 'Información Personal', 'franco' ); ?></h4>

			<div class="franco-form-row-grid">
				<div class="franco-input-group">
					<label for="account_first_name"><?php esc_html_e( 'Nombre', 'franco' ); ?> <span class="required">*</span></label>
					<div class="franco-input-wrapper">
						<i class="fas fa-user input-icon" aria-hidden="true"></i>
						<input type="text" class="franco-input" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" required />
					</div>
				</div>

				<div class="franco-input-group">
					<label for="account_last_name"><?php esc_html_e( 'Apellido', 'franco' ); ?> <span class="required">*</span></label>
					<div class="franco-input-wrapper">
						<i class="fas fa-user input-icon" aria-hidden="true"></i>
						<input type="text" class="franco-input" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" required />
					</div>
				</div>
			</div>

			<div class="franco-form-row-grid">
				<div class="franco-input-group">
					<label for="account_display_name"><?php esc_html_e( 'Nombre Visible', 'franco' ); ?> <span class="required">*</span></label>
					<div class="franco-input-wrapper">
						<i class="fas fa-signature input-icon" aria-hidden="true"></i>
						<input type="text" class="franco-input" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required />
					</div>
					<small class="franco-input-tip"><?php esc_html_e( 'Así es como se mostrará tu nombre en la barra de usuario y en tus reseñas.', 'franco' ); ?></small>
				</div>

				<div class="franco-input-group">
					<label for="account_billing_dni"><?php esc_html_e( 'DNI / CUIT', 'franco' ); ?></label>
					<div class="franco-input-wrapper">
						<i class="fas fa-id-badge input-icon" aria-hidden="true"></i>
						<input type="text" class="franco-input" name="billing_dni" id="account_billing_dni" value="<?php echo esc_attr( $user_dni ); ?>" placeholder="Número de documento" />
					</div>
					<small class="franco-input-tip"><?php esc_html_e( 'Utilizado para la facturación y envío de tus pedidos.', 'franco' ); ?></small>
				</div>
			</div>

			<div class="franco-input-group full-width">
				<label for="account_email"><?php esc_html_e( 'Correo Electrónico', 'franco' ); ?> <span class="required">*</span></label>
				<div class="franco-input-wrapper">
					<i class="fas fa-envelope input-icon" aria-hidden="true"></i>
					<input type="email" class="franco-input" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" required />
				</div>
			</div>
		</div>

		<div class="franco-form-section password-change-section">
			<h4 class="franco-section-legend"><i class="fas fa-shield-alt" aria-hidden="true"></i> <?php esc_html_e( 'Cambiar Contraseña', 'franco' ); ?></h4>
			<p class="franco-section-tip"><?php esc_html_e( 'Dejá estos campos en blanco si no deseás cambiar tu contraseña actual.', 'franco' ); ?></p>

			<div class="franco-input-group full-width">
				<label for="password_current"><?php esc_html_e( 'Contraseña actual (requerida para cambiarla)', 'franco' ); ?></label>
				<div class="franco-input-wrapper">
					<i class="fas fa-lock input-icon" aria-hidden="true"></i>
					<input type="password" class="franco-input password-input" name="password_current" id="password_current" autocomplete="current-password" />
					<button type="button" class="franco-password-toggle" onclick="francoTogglePasswordVisibility(this)" aria-label="<?php esc_attr_e( 'Ver contraseña', 'franco' ); ?>">
						<i class="far fa-eye" aria-hidden="true"></i>
					</button>
				</div>
			</div>

			<div class="franco-form-row-grid">
				<div class="franco-input-group">
					<label for="password_1"><?php esc_html_e( 'Nueva contraseña', 'franco' ); ?></label>
					<div class="franco-input-wrapper">
						<i class="fas fa-key input-icon" aria-hidden="true"></i>
						<input type="password" class="franco-input password-input" name="password_1" id="password_1" autocomplete="new-password" />
						<button type="button" class="franco-password-toggle" onclick="francoTogglePasswordVisibility(this)" aria-label="<?php esc_attr_e( 'Ver contraseña', 'franco' ); ?>">
							<i class="far fa-eye" aria-hidden="true"></i>
						</button>
					</div>
				</div>

				<div class="franco-input-group">
					<label for="password_2"><?php esc_html_e( 'Confirmar nueva contraseña', 'franco' ); ?></label>
					<div class="franco-input-wrapper">
						<i class="fas fa-check-double input-icon" aria-hidden="true"></i>
						<input type="password" class="franco-input password-input" name="password_2" id="password_2" autocomplete="new-password" />
						<button type="button" class="franco-password-toggle" onclick="francoTogglePasswordVisibility(this)" aria-label="<?php esc_attr_e( 'Ver contraseña', 'franco' ); ?>">
							<i class="far fa-eye" aria-hidden="true"></i>
						</button>
					</div>
				</div>
			</div>
		</div>

		<?php
		/**
		 * Hook where additional fields can be hooked.
		 *
		 * @since 8.7.0
		 */
		do_action( 'woocommerce_edit_account_form' );
		?>

		<div class="franco-form-actions">
			<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
			<button type="submit" class="franco-btn franco-btn-primary" name="save_account_details" value="<?php esc_attr_e( 'Guardar Cambios', 'franco' ); ?>">
				<i class="fas fa-save" aria-hidden="true"></i> <?php esc_html_e( 'Guardar Cambios', 'franco' ); ?>
			</button>
			<input type="hidden" name="action" value="save_account_details" />
		</div>

		<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
	</form>
</div>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
