<?php
/**
 * Footer Template
 *
 * @package FrancoTheme
 * @version 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ig_url         = franco_get_setting( 'franco_instagram_url', '' );
$fb_url         = get_option( 'franco_facebook_url', '' );
$tt_url         = get_option( 'franco_tiktok_url', '' );
$phone          = franco_get_setting( 'franco_contact_phone', '' );
$email          = franco_get_setting( 'franco_contact_email', '' );
$business_hours = franco_get_setting( 'franco_business_hours', '' );
$logo_url       = franco_get_theme_logo_url( 'footer' );

$nosotros_id  = get_option( 'franco_page_nosotros', 0 );
$contacto_id  = get_option( 'franco_page_contacto', 0 );
$favoritos_id = get_option( 'franco_page_favoritos', 0 );
$tienda_id    = get_option( 'franco_page_tienda', ( class_exists( 'WooCommerce' ) ? wc_get_page_id( 'shop' ) : 0 ) );

$shop_url      = $tienda_id ? get_permalink( $tienda_id ) : home_url( '/tienda' );
$nosotros_url  = $nosotros_id ? get_permalink( $nosotros_id ) : home_url( '/nosotros' );
$contacto_url  = $contacto_id ? get_permalink( $contacto_id ) : home_url( '/contacto' );
$favoritos_url = $favoritos_id ? get_permalink( $favoritos_id ) : home_url( '/favoritos' );
if ( function_exists( 'franco_is_checkout_page' ) && franco_is_checkout_page() ) : ?>
	<footer class="checkout-minimal-footer">
		<div class="container checkout-footer-inner">
			<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Todos los derechos reservados. Checkout 100% Seguro.', 'franco-theme' ); ?></p>
		</div>
	</footer>
	<!-- Toast Notifications Container -->
	<div id="toast-container" class="toast-container"></div>
	<?php wp_footer(); ?>
	</body>
	</html>
	<?php return; ?>
<?php endif; ?>

<footer>
	<div class="footer-container">
		<div class="footer-col">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Franco Deportes', 'franco-theme' ); ?>" width="126" height="34" loading="lazy" class="footer-logo">
			<p class="footer-about">
				<?php esc_html_e( 'Tu tienda oficial de zapatillas deportivas, botines de fútbol e indumentaria técnica. Calidad, garantía y envíos a todo el país.', 'franco-theme' ); ?>
			</p>
			<?php if ( ! empty( $ig_url ) || ! empty( $fb_url ) || ! empty( $tt_url ) ) : ?>
				<div class="social-links">
					<?php if ( ! empty( $ig_url ) ) : ?>
						<a href="<?php echo esc_url( $ig_url ); ?>" target="_blank" rel="noopener" class="social-icon-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
					<?php endif; ?>
					<?php if ( ! empty( $fb_url ) ) : ?>
						<a href="<?php echo esc_url( $fb_url ); ?>" target="_blank" rel="noopener" class="social-icon-btn" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
					<?php endif; ?>
					<?php if ( ! empty( $tt_url ) ) : ?>
						<a href="<?php echo esc_url( $tt_url ); ?>" target="_blank" rel="noopener" class="social-icon-btn" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="footer-col">
			<h4 class="footer-heading"><?php esc_html_e( 'Navegación', 'franco-theme' ); ?></h4>
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'footer-links',
				) );
			} else {
				echo '<ul class="footer-links">';
				echo '<li><a href="' . esc_url( home_url( '/' ) ) . '" class="footer-link">' . esc_html__( 'Inicio', 'franco-theme' ) . '</a></li>';
				echo '<li><a href="' . esc_url( $shop_url ) . '" class="footer-link">' . esc_html__( 'Tienda', 'franco-theme' ) . '</a></li>';
				echo '<li><a href="' . esc_url( $nosotros_url ) . '" class="footer-link">' . esc_html__( 'Nosotros', 'franco-theme' ) . '</a></li>';
				echo '<li><a href="' . esc_url( $contacto_url ) . '" class="footer-link">' . esc_html__( 'Contacto', 'franco-theme' ) . '</a></li>';
				echo '<li><a href="' . esc_url( $favoritos_url ) . '" class="footer-link">' . esc_html__( 'Mis Favoritos', 'franco-theme' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</div>

		<div class="footer-col">
			<h4 class="footer-heading"><?php esc_html_e( 'Atención al Cliente', 'franco-theme' ); ?></h4>
			<ul class="footer-contact-list footer-links">
				<li>
					<i class="fa-solid fa-location-dot footer-contact-icon"></i>
					Av. Brig. Juan Manuel de Rosas 2525, BsAs
				</li>
				<?php if ( ! empty( $phone ) ) : ?>
					<li>
						<i class="fa-solid fa-phone footer-contact-icon"></i>
						<a class="footer-link" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
							<?php echo esc_html( $phone ); ?>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( ! empty( $email ) ) : ?>
					<li>
						<i class="fa-solid fa-envelope footer-contact-icon"></i>
						<a class="footer-link" href="<?php echo esc_url( 'mailto:' . $email ); ?>">
							<?php echo esc_html( $email ); ?>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( ! empty( $business_hours ) ) : ?>
					<li>
						<i class="fa-solid fa-clock footer-contact-icon"></i>
						<?php echo esc_html( $business_hours ); ?>
					</li>
				<?php endif; ?>
			</ul>
		</div>

		<div class="footer-col">
			<h4 class="footer-heading"><?php esc_html_e( 'Medios de Pago', 'franco-theme' ); ?></h4>
			<div class="footer-payments">
				<i class="fa-brands fa-cc-visa" title="Visa"></i>
				<i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
				<i class="fa-brands fa-cc-amex" title="American Express"></i>
				<i class="fa-solid fa-money-bill-transfer" title="Transferencia"></i>
			</div>
		</div>
	</div>

	<div class="footer-copyright">
		&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Todos los derechos reservados. Desarrollado con WordPress + WooCommerce.', 'franco-theme' ); ?>
	</div>
</footer>

<!-- Toast Notifications Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Non-invasive Postal Code Prompt Banner / Toast -->
<div class="franco-postcode-toast is-hidden" id="franco-postcode-toast" role="dialog" aria-label="<?php esc_attr_e( 'Ingresar código postal', 'franco-theme' ); ?>">
	<div class="postcode-toast-inner">
		<div class="postcode-toast-icon">
			<i class="fa-solid fa-truck-fast"></i>
		</div>
		<div class="postcode-toast-content" id="postcode-toast-content">
			<div class="postcode-toast-title"><?php esc_html_e( '¿A dónde enviamos tu compra?', 'franco-theme' ); ?></div>
			<div class="postcode-toast-subtitle"><?php esc_html_e( 'Ayudanos a localizarte para verificar disponibilidad y cobertura en tu zona', 'franco-theme' ); ?></div>
			<form class="postcode-toast-form" onsubmit="francoSubmitPostcodeToast(event)">
				<input type="text" id="toast-postcode-field" class="form-input" placeholder="Ej: 1650" maxlength="8" autocomplete="postal-code" required>
				<button type="submit" class="btn-primary"><?php esc_html_e( 'Guardar', 'franco-theme' ); ?></button>
			</form>
		</div>
		<button type="button" class="postcode-toast-close" onclick="francoDismissPostcodeToast()" aria-label="<?php esc_attr_e( 'Cerrar', 'franco-theme' ); ?>" title="<?php esc_attr_e( 'Recordar más tarde', 'franco-theme' ); ?>">
			<i class="fa-solid fa-xmark"></i>
		</button>
	</div>
</div>

<!-- Quick View Modal Component -->
<?php get_template_part( 'template-parts/components/quick-view-modal' ); ?>

<?php wp_footer(); ?>
</body>
</html>
