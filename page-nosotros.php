<?php
/**
 * Template Name: Nosotros (Sobre Nosotros)
 *
 * @package FrancoTheme
 * @version 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hero_img = franco_get_setting( 'franco_hero_banner_image', FRANCO_THEME_URI . '/assets/images/hero_banner.webp' );
?>

<!-- Page Banner -->
<section class="page-banner page-about-banner">
	<div class="page-banner-content">
		<span class="hero-badge page-banner-badge">
			<i class="fa-solid fa-store"></i> <?php esc_html_e( 'Nuestra Historia', 'franco-theme' ); ?>
		</span>
		<h1 class="page-banner-title">
			<?php esc_html_e( 'SOBRE FRANCO DEPORTES', 'franco-theme' ); ?>
		</h1>
		<p class="page-banner-subtitle">
			<?php esc_html_e( 'Más que una tienda deportiva: una comunidad apasionada por el rendimiento, la moda urbana y el deporte.', 'franco-theme' ); ?>
		</p>
	</div>
</section>

<!-- About Story Section -->
<section class="section">
	<div class="about-grid about-story-grid">
		<div>
			<span class="about-eyebrow">
				<?php esc_html_e( 'Desde José León Suárez al país', 'franco-theme' ); ?>
			</span>
			<h2 class="about-story-title">
				<?php esc_html_e( 'PASIÓN, CALIDAD Y COMPROMISO CON EL DEPORTISTA', 'franco-theme' ); ?>
			</h2>

			<div class="entry-content about-story-content">
				<?php
				if ( have_posts() && get_the_content() ) {
					the_content();
				} else {
					echo '<p>' . esc_html__( 'Nacimos con una misión clara: acercar el mejor calzado e indumentaria deportiva a cada atleta y aficionado. Ubicados en Av. Brig. Juan Manuel de Rosas 2525, José León Suárez, nos hemos consolidado como un referente regional gracias a la atención personalizada y la autenticidad garantizada en cada producto.', 'franco-theme' ) . '</p>';
					echo '<p>' . esc_html__( 'Trabajamos únicamente con marcas oficiales y distribuidores verificados. Nuestro equipo está compuesto por deportistas y amantes del entrenamiento que entienden exactamente lo que necesitas para tu disciplina.', 'franco-theme' ) . '</p>';
				}
				?>
			</div>

			<div class="about-stats-row">
				<div class="about-stat-card stat-card">
					<h3 class="about-stat-value stat-number">100%</h3>
					<p class="about-stat-label"><?php esc_html_e( 'Productos Originales', 'franco-theme' ); ?></p>
				</div>
				<div class="about-stat-card stat-card">
					<h3 class="about-stat-value stat-number">+10K</h3>
					<p class="about-stat-label"><?php esc_html_e( 'Clientes Satisfechos', 'franco-theme' ); ?></p>
				</div>
			</div>
		</div>

		<div class="about-image-wrap">
			<img src="<?php echo esc_url( $hero_img ); ?>" alt="<?php esc_attr_e( 'Franco Deportes Store', 'franco-theme' ); ?>" width="600" height="400" loading="lazy" class="about-image">
		</div>
	</div>
</section>

<!-- Values Section -->
<section class="about-values-section">
	<div class="about-values-inner">
		<div class="about-values-header">
			<h2 class="section-title">NUESTROS <span>PILARES</span></h2>
			<p class="about-values-subtitle"><?php esc_html_e( 'Lo que nos define en cada interacción y venta', 'franco-theme' ); ?></p>
		</div>

		<div class="benefits-grid about-values-grid">
			<div class="benefit-card about-value-card">
				<div class="benefit-icon about-value-icon">
					<i class="fa-solid fa-award"></i>
				</div>
				<div>
					<h4 class="about-value-title"><?php esc_html_e( 'Garantía de Autenticidad', 'franco-theme' ); ?></h4>
					<p class="about-value-text"><?php esc_html_e( 'Todos nuestros botines, zapatillas y prendas cuentan con garantía oficial de marca.', 'franco-theme' ); ?></p>
				</div>
			</div>

			<div class="benefit-card about-value-card">
				<div class="benefit-icon about-value-icon">
					<i class="fa-solid fa-headset"></i>
				</div>
				<div>
					<h4 class="about-value-title"><?php esc_html_e( 'Asesoramiento Real', 'franco-theme' ); ?></h4>
					<p class="about-value-text"><?php esc_html_e( 'Te ayudamos a elegir el talle exacto y el calzado según tu superficie de juego o pisada.', 'franco-theme' ); ?></p>
				</div>
			</div>

			<div class="benefit-card about-value-card">
				<div class="benefit-icon about-value-icon">
					<i class="fa-solid fa-rotate-left"></i>
				</div>
				<div>
					<h4 class="about-value-title"><?php esc_html_e( 'Cambios sin Complicaciones', 'franco-theme' ); ?></h4>
					<p class="about-value-text"><?php esc_html_e( '¿No te quedó el talle? Realizamos el cambio de manera rápida y sencilla.', 'franco-theme' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
