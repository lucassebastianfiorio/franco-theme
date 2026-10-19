<?php
/**
 * Reset Password Form Template
 *
 * @package FrancoTheme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_reset_password_form' );
?>

<div class="franco-auth-container">
	<div class="franco-auth-card">

		<div class="franco-auth-header">
			<div class="franco-auth-icon-badge">
				<i class="fa-solid fa-lock"></i>
			</div>
			<h2 class="franco-auth-title">
				<?php esc_html_e( 'Crear Nueva Contraseña', 'franco-theme' ); ?>
			</h2>
			<p class="franco-auth-subtitle">
				<?php esc_html_e( 'Ingresá tu nueva contraseña para acceder a tu cuenta de Franco Deportes.', 'franco-theme' ); ?>
			</p>
		</div>

		<form method="post" class="woocommerce-ResetPassword lost_reset_password franco-auth-form">

			<div class="form-group">
				<label class="form-label" for="password_1">
					<?php esc_html_e( 'Nueva Contraseña *', 'franco-theme' ); ?>
				</label>
				<div class="input-with-icon input-password-wrap">
					<i class="fa-solid fa-lock input-icon"></i>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text form-input password-input" name="password_1" id="password_1" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Nueva contraseña', 'franco-theme' ); ?>" required />
					<button type="button" class="btn-toggle-password" onclick="francoTogglePasswordVisibility(this)" aria-label="<?php esc_attr_e( 'Mostrar u ocultar contraseña', 'franco-theme' ); ?>" tabindex="-1">
						<i class="fa-regular fa-eye"></i>
					</button>
				</div>
			</div>

			<div class="form-group">
				<label class="form-label" for="password_2">
					<?php esc_html_e( 'Confirmar Nueva Contraseña *', 'franco-theme' ); ?>
				</label>
				<div class="input-with-icon input-password-wrap">
					<i class="fa-solid fa-lock input-icon"></i>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text form-input password-input" name="password_2" id="password_2" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Repetí tu nueva contraseña', 'franco-theme' ); ?>" required />
					<button type="button" class="btn-toggle-password" onclick="francoTogglePasswordVisibility(this)" aria-label="<?php esc_attr_e( 'Mostrar u ocultar contraseña', 'franco-theme' ); ?>" tabindex="-1">
						<i class="fa-regular fa-eye"></i>
					</button>
				</div>
			</div>

			<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>" />
			<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>" />

			<?php do_action( 'woocommerce_resetpassword_form' ); ?>

			<div class="form-submit-row">
				<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>
				<button type="submit" class="woocommerce-Button button btn-auth-submit" value="<?php esc_attr_e( 'Guardar contraseña', 'franco-theme' ); ?>">
					<?php esc_html_e( 'Guardar Nueva Contraseña', 'franco-theme' ); ?>
				</button>
			</div>

		</form>

	</div>
</div>

<?php do_action( 'woocommerce_after_reset_password_form' ); ?>
