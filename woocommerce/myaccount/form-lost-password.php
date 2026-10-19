<?php
/**
 * Lost Password Form Template
 *
 * @package FrancoTheme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$account_url = function_exists( 'franco_get_myaccount_url' ) ? franco_get_myaccount_url() : home_url( '/mi-cuenta' );

do_action( 'woocommerce_before_lost_password_form' );
?>

<div class="franco-auth-container">
	<div class="franco-auth-card">

		<div class="franco-auth-header">
			<div class="franco-auth-icon-badge">
				<i class="fa-solid fa-key"></i>
			</div>
			<h2 class="franco-auth-title">
				<?php esc_html_e( 'Recuperar Contraseña', 'franco-theme' ); ?>
			</h2>
			<p class="franco-auth-subtitle">
				<?php esc_html_e( 'Ingresá tu correo electrónico o usuario. Te enviaremos un enlace seguro para restablecer tu contraseña.', 'franco-theme' ); ?>
			</p>
		</div>

		<form method="post" class="woocommerce-ResetPassword lost_reset_password franco-auth-form">

			<div class="form-group">
				<label class="form-label" for="user_login">
					<?php esc_html_e( 'Usuario o Correo Electrónico *', 'franco-theme' ); ?>
				</label>
				<div class="input-with-icon">
					<i class="fa-solid fa-envelope input-icon"></i>
					<input class="woocommerce-Input woocommerce-Input--text input-text form-input" type="text" name="user_login" id="user_login" autocomplete="username" placeholder="<?php esc_attr_e( 'tu-email@ejemplo.com', 'franco-theme' ); ?>" required />
				</div>
			</div>

			<?php do_action( 'woocommerce_lostpassword_form' ); ?>

			<input type="hidden" name="wc_reset_password" value="true" />

			<div class="form-submit-row">
				<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
				<button type="submit" class="woocommerce-Button button btn-auth-submit" value="<?php esc_attr_e( 'Restablecer contraseña', 'franco-theme' ); ?>">
					<?php esc_html_e( 'Enviar Enlace de Recuperación', 'franco-theme' ); ?>
				</button>
			</div>

			<div class="auth-back-link-row">
				<a href="<?php echo esc_url( $account_url ); ?>" class="link-inherit">
					<i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Volver a Iniciar Sesión', 'franco-theme' ); ?>
				</a>
			</div>

		</form>

	</div>
</div>

<?php do_action( 'woocommerce_after_lost_password_form' ); ?>
