<?php
/**
 * Template Name: Contacto
 *
 * @package FrancoTheme
 * @version 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$phone    = franco_get_setting( 'franco_contact_phone', '+54 11 1234 5678' );
$email    = franco_get_setting( 'franco_contact_email', 'ventas@francodeportes.com' );
$whatsapp = preg_replace( '/\D/', '', (string) franco_get_setting( 'franco_whatsapp_number', '5491112345678' ) );
$ig_url   = franco_get_setting( 'franco_instagram_url', 'https://instagram.com' );
?>

<!-- Page Banner -->
<section class="page-banner">
	<div class="page-banner-content">
		<span class="hero-badge page-banner-badge">
			<i class="fa-solid fa-comments"></i> <?php esc_html_e( 'Estamos para ayudarte', 'franco-theme' ); ?>
		</span>
		<h1 class="page-banner-title">
			<?php esc_html_e( 'CONTACTATE CON NOSOTROS', 'franco-theme' ); ?>
		</h1>
		<p class="page-banner-subtitle">
			<?php esc_html_e( 'Resuelve tus dudas sobre talles, stock en sucursal, envíos o medios de pago en minutos.', 'franco-theme' ); ?>
		</p>
	</div>
</section>

<!-- Contact Layout -->
<section class="section">
	<div class="contact-grid contact-layout-grid">
		<!-- Contact Info Cards -->
		<div>
			<h2 class="contact-heading">
				<?php esc_html_e( 'INFORMACIÓN DE CONTACTO', 'franco-theme' ); ?>
			</h2>

			<div class="contact-cards-list">
				<a href="https://maps.app.goo.gl/3dKc2iGpuVkWGCRr8" target="_blank" rel="noopener noreferrer" class="benefit-card contact-info-card contact-card-link">
					<div class="benefit-icon contact-info-icon">
						<i class="fa-solid fa-location-dot"></i>
					</div>
					<div>
						<h4 class="contact-info-title"><?php esc_html_e( 'Sucursal Comercial', 'franco-theme' ); ?></h4>
						<p class="contact-info-text"><?php esc_html_e( 'Av. Brig. Juan Manuel de Rosas 2525, José León Suárez, Buenos Aires.', 'franco-theme' ); ?></p>
					</div>
				</a>

				<a href="https://wa.me/<?php echo esc_attr( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer" class="benefit-card contact-info-card contact-card-link">
					<div class="benefit-icon contact-info-icon-whatsapp">
						<i class="fa-brands fa-whatsapp"></i>
					</div>
					<div>
						<h4 class="contact-info-title"><?php esc_html_e( 'Atención por WhatsApp', 'franco-theme' ); ?></h4>
						<p class="contact-info-text"><?php echo esc_html( 'Respuesta inmediata de ' . franco_get_setting( 'franco_business_hours', 'Lunes a Sábados de 09:00 a 20:00 hs.' ) ); ?></p>
						<span class="contact-whatsapp-link">
							<i class="fa-brands fa-whatsapp"></i> <?php esc_html_e( 'Iniciar Chat por WhatsApp', 'franco-theme' ); ?>
						</span>
					</div>
				</a>

				<a href="mailto:<?php echo esc_attr( $email ); ?>" target="_blank" rel="noopener noreferrer" class="benefit-card contact-info-card contact-card-link">
					<div class="benefit-icon contact-info-icon">
						<i class="fa-solid fa-envelope"></i>
					</div>
					<div>
						<h4 class="contact-info-title"><?php esc_html_e( 'Correo Electrónico', 'franco-theme' ); ?></h4>
						<p class="contact-info-text"><?php echo esc_html( $email ); ?></p>
					</div>
				</a>
			</div>

			<!-- Social Channels -->
			<h3 class="contact-social-title"><?php esc_html_e( 'Redes Oficiales', 'franco-theme' ); ?></h3>
			<div class="contact-social-row">
				<a href="<?php echo esc_url( $ig_url ); ?>" target="_blank" rel="noopener" class="btn-primary contact-instagram-btn">
					<i class="fa-brands fa-instagram"></i> Instagram @francodeportes
				</a>
			</div>
		</div>

		<!-- Contact Form -->
		<div class="contact-form-card">
			<h3 class="contact-form-title">
				<?php esc_html_e( 'Enviar Mensaje Directo', 'franco-theme' ); ?>
			</h3>

			<form id="contact-form" onsubmit="handleContactPageFormSubmit(event)">
				<div class="contact-field">
					<label class="filter-label contact-label"><?php esc_html_e( 'Nombre Completo *', 'franco-theme' ); ?></label>
					<input type="text" id="contact-name" required class="search-input contact-input" placeholder="<?php esc_attr_e( 'Tu nombre...', 'franco-theme' ); ?>">
				</div>

				<div class="contact-field">
					<label class="filter-label contact-label"><?php esc_html_e( 'Correo Electrónico *', 'franco-theme' ); ?></label>
					<input type="email" id="contact-email" required class="search-input contact-input" placeholder="tuemail@ejemplo.com">
				</div>

				<div class="contact-field">
					<label class="filter-label contact-label"><?php esc_html_e( 'Teléfono / WhatsApp', 'franco-theme' ); ?></label>
					<input type="tel" id="contact-phone" class="search-input contact-input" placeholder="+54 11 ...">
				</div>

				<div class="contact-field-message">
					<label class="filter-label contact-label"><?php esc_html_e( 'Mensaje o Consulta *', 'franco-theme' ); ?></label>
					<textarea id="contact-message" required rows="4" class="search-input contact-textarea" placeholder="<?php esc_attr_e( 'Escribe tu consulta sobre talles, stock o envíos...', 'franco-theme' ); ?>"></textarea>
				</div>

				<button type="submit" class="btn-primary contact-submit-btn">
					<i class="fa-solid fa-paper-plane"></i> <?php esc_html_e( 'Enviar Consulta por WhatsApp', 'franco-theme' ); ?>
				</button>
			</form>
		</div>
	</div>
</section>

<script>
function handleContactPageFormSubmit(e) {
	e.preventDefault();
	const name = document.getElementById('contact-name').value;
	const email = document.getElementById('contact-email').value;
	const phone = document.getElementById('contact-phone').value;
	const msg = document.getElementById('contact-message').value;

	let text = `👋 ¡Hola Franco Deportes! Mi nombre es *${name}* (${email}, ${phone}).\n\n💬 *Consulta:* ${msg}`;
	let url = `https://wa.me/<?php echo esc_js( $whatsapp ); ?>?text=${encodeURIComponent(text)}`;

	if (typeof showToast === 'function') {
		showToast('Redirigiendo a WhatsApp...');
	}
	window.open(url, '_blank');
}
</script>

<?php
get_footer();
