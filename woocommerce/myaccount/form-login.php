<?php
/**
 * Login & Registration Form Template (Franco Deportes Unified Tabs)
 *
 * @package FrancoTheme
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enable_registration = ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) );
$active_tab = ( isset( $_GET['action'] ) && 'register' === $_GET['action'] && $enable_registration ) ? 'register' : 'login';

do_action( 'woocommerce_before_customer_login_form' );
?>

<div class="franco-auth-container" id="customer_login">
	<div class="franco-auth-card">

		<div class="franco-auth-header">
			<div class="franco-auth-icon-badge">
				<i class="fa-solid fa-shield-halved"></i>
			</div>
			<h2 class="franco-auth-title" id="auth-title">
				<?php echo ( 'register' === $active_tab ) ? esc_html__( 'Crear una Cuenta', 'franco-theme' ) : esc_html__( 'Acceder a tu Cuenta', 'franco-theme' ); ?>
			</h2>
			<p class="franco-auth-subtitle" id="auth-subtitle">
				<?php echo ( 'register' === $active_tab ) ? esc_html__( 'Registrate para gestionar tus pedidos y envíos con facilidad.', 'franco-theme' ) : esc_html__( 'Ingresá con tus datos para ver tus compras y direcciones.', 'franco-theme' ); ?>
			</p>
		</div>

		<?php if ( $enable_registration ) : ?>
			<div class="franco-auth-tabs" role="tablist">
				<button type="button" class="auth-tab-btn <?php echo ( 'login' === $active_tab ) ? 'active' : ''; ?>" id="tab-btn-login" data-tab="login" role="tab" aria-selected="<?php echo ( 'login' === $active_tab ) ? 'true' : 'false'; ?>">
					<i class="fa-regular fa-user"></i> <?php esc_html_e( 'Iniciar Sesión', 'franco-theme' ); ?>
				</button>
				<button type="button" class="auth-tab-btn <?php echo ( 'register' === $active_tab ) ? 'active' : ''; ?>" id="tab-btn-register" data-tab="register" role="tab" aria-selected="<?php echo ( 'register' === $active_tab ) ? 'true' : 'false'; ?>">
					<i class="fa-solid fa-user-plus"></i> <?php esc_html_e( 'Crear Cuenta', 'franco-theme' ); ?>
				</button>
			</div>
		<?php endif; ?>

		<!-- Login Form Pane -->
		<div class="franco-auth-pane <?php echo ( 'login' !== $active_tab ) ? 'is-hidden' : ''; ?>" id="pane-login" role="tabpanel">
			<form class="woocommerce-form woocommerce-form-login login franco-auth-form" method="post">

				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<div class="form-group">
					<label class="form-label" for="username">
						<?php esc_html_e( 'Correo electrónico o usuario *', 'franco-theme' ); ?>
					</label>
					<div class="input-with-icon">
						<i class="fa-solid fa-envelope input-icon"></i>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-input" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'ejemplo@correo.com', 'franco-theme' ); ?>" required />
					</div>
				</div>

				<div class="form-group">
					<div class="form-label-row">
						<label class="form-label" for="password">
							<?php esc_html_e( 'Contraseña *', 'franco-theme' ); ?>
						</label>
						<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="forgot-password-link">
							<?php esc_html_e( '¿Olvidaste tu contraseña?', 'franco-theme' ); ?>
						</a>
					</div>
					<div class="input-with-icon input-password-wrap">
						<i class="fa-solid fa-lock input-icon"></i>
						<input class="woocommerce-Input woocommerce-Input--text input-text form-input password-input" type="password" name="password" id="password" autocomplete="current-password" placeholder="••••••••" required />
						<button type="button" class="btn-toggle-password" aria-label="<?php esc_attr_e( 'Mostrar u ocultar contraseña', 'franco-theme' ); ?>" tabindex="-1">
							<i class="fa-regular fa-eye"></i>
						</button>
					</div>
				</div>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<div class="form-group form-rememberme-row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme custom-checkbox-label">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
						<span><?php esc_html_e( 'Recordar mis datos', 'franco-theme' ); ?></span>
					</label>
				</div>

				<div class="form-submit-row">
					<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
					<button type="submit" class="woocommerce-button button woocommerce-form-login__submit btn-auth-submit" name="login" value="<?php esc_attr_e( 'Iniciar Sesión', 'franco-theme' ); ?>">
						<?php esc_html_e( 'Iniciar Sesión', 'franco-theme' ); ?>
					</button>
				</div>

				<?php do_action( 'woocommerce_login_form_end' ); ?>

			</form>
		</div>

		<?php if ( $enable_registration ) : ?>
		<!-- Registration Form Pane -->
		<div class="franco-auth-pane <?php echo ( 'register' !== $active_tab ) ? 'is-hidden' : ''; ?>" id="pane-register" role="tabpanel">
			<form method="post" class="woocommerce-form woocommerce-form-register register franco-auth-form" <?php do_action( 'woocommerce_register_form_tag' ); ?>>

				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<div class="form-group">
						<label class="form-label" for="reg_username">
							<?php esc_html_e( 'Nombre de usuario *', 'franco-theme' ); ?>
						</label>
						<div class="input-with-icon">
							<i class="fa-solid fa-user input-icon"></i>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text form-input" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'usuario123', 'franco-theme' ); ?>" required />
						</div>
					</div>
				<?php endif; ?>

				<div class="form-group">
					<label class="form-label" for="reg_email">
						<?php esc_html_e( 'Correo electrónico *', 'franco-theme' ); ?>
					</label>
					<div class="input-with-icon">
						<i class="fa-solid fa-envelope input-icon"></i>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text form-input" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'tu-email@ejemplo.com', 'franco-theme' ); ?>" required />
					</div>
				</div>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<div class="form-group">
						<label class="form-label" for="reg_password">
							<?php esc_html_e( 'Contraseña *', 'franco-theme' ); ?>
						</label>
						<div class="input-with-icon input-password-wrap">
							<i class="fa-solid fa-lock input-icon"></i>
							<input type="password" class="woocommerce-Input woocommerce-Input--text input-text form-input password-input" name="password" id="reg_password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Mínimo 8 caracteres', 'franco-theme' ); ?>" required />
							<button type="button" class="btn-toggle-password" aria-label="<?php esc_attr_e( 'Mostrar u ocultar contraseña', 'franco-theme' ); ?>" tabindex="-1">
								<i class="fa-regular fa-eye"></i>
							</button>
						</div>
						<div class="password-strength-hint">
							<i class="fa-solid fa-circle-info"></i> <?php esc_html_e( 'Utilizá al menos 8 caracteres con letras, números y símbolos.', 'franco-theme' ); ?>
						</div>
					</div>
				<?php else : ?>
					<p class="form-hint" style="margin-bottom: 16px;">
						<i class="fa-solid fa-circle-info text-primary"></i> <?php esc_html_e( 'Se enviará un enlace para establecer tu contraseña a tu correo electrónico.', 'franco-theme' ); ?>
					</p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<div class="form-submit-row">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit btn-auth-submit" name="register" value="<?php esc_attr_e( 'Registrarse', 'franco-theme' ); ?>">
						<?php esc_html_e( 'Crear Cuenta', 'franco-theme' ); ?>
					</button>
				</div>

				<?php do_action( 'woocommerce_register_form_end' ); ?>

			</form>
		</div>
		<?php endif; ?>

	</div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
