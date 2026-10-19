<?php
/**
 * Front Page Template
 *
 * @package FrancoTheme
 * @version 1.0.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$shop_url = class_exists( 'WooCommerce' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/tienda' );
?>

<!-- Hero Section -->
<section class="hero-section" id="inicio">
	<?php franco_render_hero_image(); ?>
	<div class="hero-content">
		<span class="hero-badge">
			<i class="fa-solid fa-fire"></i> <?php esc_html_e( 'Nueva Colección 2026', 'franco-theme' ); ?>
		</span>
		<h1 class="hero-title">
			PASIÓN Y RENDIMIENTO <span>EN CADA PASO</span>
		</h1>
		<p class="hero-subtitle">
			<?php esc_html_e( 'Equípate con lo mejor en calzado deportivo, botines de fútbol e indumentaria técnica de alto rendimiento.', 'franco-theme' ); ?>
		</p>

		<div class="hero-btns">
			<a href="<?php echo esc_url( $shop_url ); ?>" class="btn-primary">
				<i class="fa-solid fa-shop"></i> <?php esc_html_e( 'Ver Catálogo Completo', 'franco-theme' ); ?>
			</a>
		</div>
	</div>
</section>

<!-- Main Products Section -->
<section class="section" id="catalogo">
	<div class="section-header">
		<div>
			<h2 class="section-title">PRODUCTOS <span>DESTACADOS</span></h2>
			<p class="home-section-subtitle">
				<?php esc_html_e( 'Modelos en tendencia con disponibilidad inmediata y envío rápido.', 'franco-theme' ); ?>
			</p>
		</div>
		<a href="<?php echo esc_url( $shop_url ); ?>" class="home-explore-link">
			<?php esc_html_e( 'Explorar Tienda', 'franco-theme' ); ?> <i class="fa-solid fa-arrow-right"></i>
		</a>
	</div>

	<!-- Dynamic Category Filter Pills (Only Parent Categories with Visible Products) -->
	<div class="filter-pills-bar home-category-pills">
		<a href="<?php echo esc_url( $shop_url ); ?>" class="filter-pill-btn active" data-category="all"><?php esc_html_e( 'Todos los Productos', 'franco-theme' ); ?></a>
		<?php
		if ( taxonomy_exists( 'product_cat' ) ) {
			$parent_categories = franco_get_visible_parent_categories();

			if ( ! empty( $parent_categories ) ) {
				foreach ( $parent_categories as $cat ) {
					$cat_link = get_term_link( $cat );
					if ( is_wp_error( $cat_link ) ) {
						continue;
					}
					echo '<a href="' . esc_url( $cat_link ) . '" class="filter-pill-btn" data-category="' . esc_attr( $cat->slug ) . '">' . esc_html( $cat->name ) . '</a>';
				}
			}
		}
		?>
		<a href="<?php echo esc_url( $shop_url ); ?>" class="filter-pill-btn" data-category="ofertas"><?php esc_html_e( 'Ofertas / Outlet', 'franco-theme' ); ?></a>
	</div>

	<!-- Dynamic WooCommerce Products Loop (Only In-Stock Products) -->
	<div class="products-grid" id="products-grid">
		<?php
		if ( class_exists( 'WooCommerce' ) ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'orderby'        => 'rand',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => '_stock_status',
						'value'   => 'instock',
						'compare' => '=',
					),
				),
			);
			$loop = new WP_Query( $args );

			if ( $loop->have_posts() ) {
				while ( $loop->have_posts() ) {
					$loop->the_post();
					wc_get_template_part( 'content', 'product' );
				}
				wp_reset_postdata();
			} else {
				echo '<div class="home-products-empty">';
				echo '<p class="text-muted">' . esc_html__( 'No hay productos disponibles en stock.', 'franco-theme' ) . '</p>';
				echo '</div>';
			}
		}
		?>
	</div>

	<!-- See More / Go to Shop Button -->
	<div class="home-see-more-wrap">
		<a href="<?php echo esc_url( $shop_url ); ?>" class="btn-primary home-see-more-btn">
			<i class="fa-solid fa-store"></i> <?php esc_html_e( 'Ir a la Tienda', 'franco-theme' ); ?>
		</a>
	</div>
</section>

<!-- Trust / Features Grid Banner -->
<section class="section home-trust-section">
	<div class="home-trust-grid">
		<div class="home-trust-item">
			<i class="fa-solid fa-truck-fast home-trust-icon"></i>
			<div>
				<strong class="home-trust-title"><?php esc_html_e( 'Envíos a Todo el País', 'franco-theme' ); ?></strong>
				<span class="home-trust-text"><?php esc_html_e( 'Despachos en 24h hábiles', 'franco-theme' ); ?></span>
			</div>
		</div>

		<div class="home-trust-item">
			<i class="fa-solid fa-credit-card home-trust-icon"></i>
			<div>
				<strong class="home-trust-title"><?php esc_html_e( '3 y 6 Cuotas Sin Interés', 'franco-theme' ); ?></strong>
				<span class="home-trust-text"><?php esc_html_e( 'Con todas las tarjetas', 'franco-theme' ); ?></span>
			</div>
		</div>

		<div class="home-trust-item">
			<i class="fa-solid fa-shield-halved home-trust-icon"></i>
			<div>
				<strong class="home-trust-title"><?php esc_html_e( 'Garantía de Fábrica', 'franco-theme' ); ?></strong>
				<span class="home-trust-text"><?php esc_html_e( 'Productos 100% originales', 'franco-theme' ); ?></span>
			</div>
		</div>

		<div class="home-trust-item">
			<i class="fa-solid fa-rotate-left home-trust-icon"></i>
			<div>
				<strong class="home-trust-title"><?php esc_html_e( 'Cambios sin Cargo', 'franco-theme' ); ?></strong>
				<span class="home-trust-text"><?php esc_html_e( '30 días para cambio de talle', 'franco-theme' ); ?></span>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
