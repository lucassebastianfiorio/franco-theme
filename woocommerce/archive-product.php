<?php
/**
 * Custom WooCommerce Store Catalog / Archive Template (tienda.html Layout)
 *
 * @package FrancoTheme
 * @version 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );

$store_banner_img = franco_get_setting( 'franco_store_banner_image', '' );
?>

<!-- Page Banner -->
<section class="page-banner<?php echo $store_banner_img ? ' page-banner--image' : ''; ?>" <?php echo $store_banner_img ? 'style="--page-banner-image: url(\'' . esc_url( $store_banner_img ) . '\');"' : ''; ?>>
	<div class="page-banner-content">
		<?php franco_render_breadcrumbs(); ?>
		<h1 class="page-banner-title">
			<?php
			if ( is_product_category() ) {
				single_term_title();
			} else {
				esc_html_e( 'Catálogo Completo', 'franco-theme' );
			}
			?>
		</h1>
		<p class="page-banner-subtitle">
			<?php esc_html_e( 'Productos integrados con sistema de gestión y disponibilidad inmediata.', 'franco-theme' ); ?>
		</p>
	</div>
</section>

<!-- Main Store Section -->
<section class="section" id="catalogo">
	<!-- Mobile Filter Trigger Button -->
	<button class="mobile-filter-btn" id="mobile-filter-btn">
		<i class="fa-solid fa-sliders"></i> <?php esc_html_e( 'Filtrar y Ordenar Productos', 'franco-theme' ); ?>
	</button>

	<div class="mobile-filter-backdrop" id="mobile-filter-backdrop"></div>

	<div class="store-container">
		<!-- Sidebar Filters -->
		<?php
		$price_range = franco_get_min_max_prices();
		$min_price   = $price_range['min'];
		$max_price   = $price_range['max'];
		?>
		<aside class="filter-sidebar" data-min-price="<?php echo esc_attr( $min_price ); ?>" data-max-price="<?php echo esc_attr( $max_price ); ?>">
			<div class="mobile-filter-header">
				<h3 class="mobile-filter-title"><?php esc_html_e( 'Filtros de Tienda', 'franco-theme' ); ?></h3>
				<button type="button" class="mobile-filter-close" id="mobile-filter-close" aria-label="<?php esc_attr_e( 'Cerrar filtros', 'franco-theme' ); ?>"><i class="fa-solid fa-xmark"></i></button>
			</div>

			<!-- Category Filter Group -->
			<div class="filter-group filter-group-collapsible">
				<button type="button" class="filter-group-header" aria-expanded="true">
					<span class="filter-title-text"><?php esc_html_e( 'Categoría', 'franco-theme' ); ?></span>
					<i class="fa-solid fa-chevron-down filter-toggle-icon"></i>
				</button>
				<div class="filter-group-content">
					<select id="cat-select" class="filter-select filter-select-field">
						<option value="all"><?php esc_html_e( 'Todas las Categorías', 'franco-theme' ); ?></option>
						<?php
						$parent_categories = franco_get_visible_parent_categories();
						$current_cat_obj   = is_product_category() ? get_queried_object() : null;

						$active_parent_slug = 'all';
						$active_subcat_slug = 'all';
						if ( $current_cat_obj ) {
							if ( $current_cat_obj->parent > 0 ) {
								$parent_obj = get_term( $current_cat_obj->parent, 'product_cat' );
								if ( $parent_obj && ! is_wp_error( $parent_obj ) ) {
									$active_parent_slug = $parent_obj->slug;
									$active_subcat_slug = $current_cat_obj->slug;
								}
							} else {
								$active_parent_slug = $current_cat_obj->slug;
							}
						}

						if ( ! empty( $parent_categories ) ) {
							foreach ( $parent_categories as $pcat ) {
								$selected = ( $active_parent_slug === $pcat->slug ) ? 'selected' : '';
								echo '<option value="' . esc_attr( $pcat->slug ) . '" ' . $selected . '>' . esc_html( $pcat->name ) . '</option>';
							}
						}
						?>
					</select>

					<!-- Dynamic Subcategory Select -->
					<?php
					$initial_subcats = ( 'all' !== $active_parent_slug ) ? franco_get_visible_subcategories( $active_parent_slug ) : array();
					$has_subcats     = ! empty( $initial_subcats );
					?>
					<div id="subcat-select-wrap" class="subcat-select-wrap" style="<?php echo $has_subcats ? '' : 'display:none;'; ?> margin-top: 10px;">
						<label for="subcat-select" class="filter-field-label filter-subfield-label"><?php esc_html_e( 'Subcategoría', 'franco-theme' ); ?></label>
						<select id="subcat-select" class="filter-select filter-select-field">
							<option value="all"><?php esc_html_e( 'Todas las Subcategorías', 'franco-theme' ); ?></option>
							<?php
							if ( $has_subcats ) {
								foreach ( $initial_subcats as $scat ) {
									$selected = ( $active_subcat_slug === $scat->slug ) ? 'selected' : '';
									echo '<option value="' . esc_attr( $scat->slug ) . '" ' . $selected . '>' . esc_html( $scat->name ) . '</option>';
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>

			<!-- Brand Attribute Select -->
			<div class="filter-group filter-group-collapsible collapsed">
				<button type="button" class="filter-group-header" aria-expanded="false">
					<span class="filter-title-text"><?php esc_html_e( 'Marca', 'franco-theme' ); ?></span>
					<i class="fa-solid fa-chevron-down filter-toggle-icon"></i>
				</button>
				<div class="filter-group-content">
					<select id="brand-select" class="filter-select filter-select-field">
						<option value="all"><?php esc_html_e( 'Todas las Marcas', 'franco-theme' ); ?></option>
						<?php
						$brand_taxonomy = taxonomy_exists( 'pa_marca' ) ? 'pa_marca' : ( taxonomy_exists( 'marca' ) ? 'marca' : '' );
						if ( $brand_taxonomy ) {
							$brands = franco_get_instock_terms( $brand_taxonomy );
							if ( ! empty( $brands ) ) {
								foreach ( $brands as $brand_term ) {
									echo '<option value="' . esc_attr( $brand_term->slug ) . '">' . esc_html( $brand_term->name ) . '</option>';
								}
							}
						}
						?>
					</select>
				</div>
			</div>

			<!-- Talle Attribute Select -->
			<?php
			$talle_taxonomy   = taxonomy_exists( 'pa_talle' ) ? 'pa_talle' : ( taxonomy_exists( 'talle' ) ? 'talle' : '' );
			$talle_raw_terms  = $talle_taxonomy ? franco_get_instock_terms( $talle_taxonomy ) : array();
			$talle_terms      = franco_format_talle_terms( $talle_raw_terms );
			if ( ! empty( $talle_terms ) ) :
				?>
				<div class="filter-group filter-group-collapsible collapsed">
					<button type="button" class="filter-group-header" aria-expanded="false">
						<span class="filter-title-text"><?php esc_html_e( 'Talle', 'franco-theme' ); ?></span>
						<i class="fa-solid fa-chevron-down filter-toggle-icon"></i>
					</button>
					<div class="filter-group-content">
						<select id="talle-select" class="filter-select filter-select-field">
							<option value="all"><?php esc_html_e( 'Todos los Talles', 'franco-theme' ); ?></option>
							<?php foreach ( $talle_terms as $t_term ) : ?>
								<option value="<?php echo esc_attr( $t_term->slug ); ?>"><?php echo esc_html( $t_term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<!-- Sexo Attribute Select -->
			<?php
			$sexo_taxonomy = taxonomy_exists( 'pa_sexo' ) ? 'pa_sexo' : ( taxonomy_exists( 'sexo' ) ? 'sexo' : '' );
			$sexo_terms    = $sexo_taxonomy ? franco_get_instock_terms( $sexo_taxonomy ) : array();
			if ( ! empty( $sexo_terms ) ) :
				?>
				<div class="filter-group filter-group-collapsible collapsed">
					<button type="button" class="filter-group-header" aria-expanded="false">
						<span class="filter-title-text"><?php esc_html_e( 'Sexo / Género', 'franco-theme' ); ?></span>
						<i class="fa-solid fa-chevron-down filter-toggle-icon"></i>
					</button>
					<div class="filter-group-content">
						<select id="sexo-select" class="filter-select filter-select-field">
							<option value="all"><?php esc_html_e( 'Todos', 'franco-theme' ); ?></option>
							<?php foreach ( $sexo_terms as $s_term ) : ?>
								<option value="<?php echo esc_attr( $s_term->slug ); ?>"><?php echo esc_html( $s_term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<!-- Dynamic Price Range Widget -->
			<?php
			$price_step = max( 500, round( ( $max_price - $min_price ) / 100 ) );
			?>
			<div class="filter-group filter-group-collapsible">
				<button type="button" class="filter-group-header" aria-expanded="true">
					<span class="filter-title-text"><?php esc_html_e( 'Rango de precios', 'franco-theme' ); ?></span>
					<i class="fa-solid fa-chevron-down filter-toggle-icon"></i>
				</button>
				<div class="filter-group-content">
					<div class="price-inputs-row">
						<div class="price-input-col">
							<span class="price-input-caption"><?php esc_html_e( 'Mínimo ($)', 'franco-theme' ); ?></span>
							<input type="number" id="price-min-input" class="filter-select price-number-input" min="<?php echo esc_attr( $min_price ); ?>" max="<?php echo esc_attr( $max_price ); ?>" step="<?php echo esc_attr( $price_step ); ?>" value="<?php echo esc_attr( $min_price ); ?>" placeholder="<?php echo esc_attr( $min_price ); ?>">
						</div>
						<span class="price-input-separator">-</span>
						<div class="price-input-col">
							<span class="price-input-caption"><?php esc_html_e( 'Máximo ($)', 'franco-theme' ); ?></span>
							<input type="number" id="price-max-input" class="filter-select price-number-input" min="<?php echo esc_attr( $min_price ); ?>" max="<?php echo esc_attr( $max_price ); ?>" step="<?php echo esc_attr( $price_step ); ?>" value="<?php echo esc_attr( $max_price ); ?>" placeholder="<?php echo esc_attr( $max_price ); ?>">
						</div>
					</div>
					<div class="dual-range-wrapper price-range-wrap">
						<div class="dual-range-track price-range-track" id="dual-range-track"></div>
						<input type="range" id="price-min-range" class="dual-range-input" min="<?php echo esc_attr( $min_price ); ?>" max="<?php echo esc_attr( $max_price ); ?>" step="<?php echo esc_attr( $price_step ); ?>" value="<?php echo esc_attr( $min_price ); ?>">
						<input type="range" id="price-max-range" class="dual-range-input" min="<?php echo esc_attr( $min_price ); ?>" max="<?php echo esc_attr( $max_price ); ?>" step="<?php echo esc_attr( $price_step ); ?>" value="<?php echo esc_attr( $max_price ); ?>">
					</div>
					<div class="price-labels-row">
						<span id="price-min-label" class="price-label-value">$<?php echo esc_html( number_format( $min_price, 0, ',', '.' ) ); ?></span>
						<span id="price-max-label" class="price-label-value">$<?php echo esc_html( number_format( $max_price, 0, ',', '.' ) ); ?></span>
					</div>
				</div>
			</div>

			<!-- Clear Filters Button -->
			<button type="button" id="clear-filters-btn" class="clear-filters-btn filter-clear-btn">
				<i class="fa-solid fa-rotate-left"></i> <?php esc_html_e( 'Limpiar Filtros', 'franco-theme' ); ?>
			</button>
		</aside>

		<!-- Main Catalog Grid Area -->
		<main class="store-main">
			<!-- Toolbar: Search Input + Sorting Dropdown -->
			<div class="store-toolbar">
				<div class="store-search-wrap">
					<i class="fa-solid fa-magnifying-glass store-search-icon"></i>
					<input type="text" id="store-search-input" class="search-input store-search-input" placeholder="<?php esc_attr_e( 'Buscar en la tienda...', 'franco-theme' ); ?>">
				</div>

				<div class="store-sort-wrap">
					<label for="sort-select" class="store-sort-label"><?php esc_html_e( 'Ordenar por:', 'franco-theme' ); ?></label>
					<select id="sort-select" class="sort-select store-sort-select">
						<option value="popular"><?php esc_html_e( 'Más Populares', 'franco-theme' ); ?></option>
						<option value="price-low"><?php esc_html_e( 'Menor Precio', 'franco-theme' ); ?></option>
						<option value="price-high"><?php esc_html_e( 'Mayor Precio', 'franco-theme' ); ?></option>
						<option value="name"><?php esc_html_e( 'Nombre A-Z', 'franco-theme' ); ?></option>
					</select>
				</div>
			</div>

			<!-- Product Cards Grid -->
			<?php
			global $wp_query;
			$grid_current_page = max( 1, (int) get_query_var( 'paged' ) );
			$grid_max_pages    = max( 1, (int) $wp_query->max_num_pages );
			// AJAX infinite scroll only understands the shop and product category archives.
			$grid_infinite     = ( is_shop() || is_product_category() );
			$grid_category     = is_product_category() ? get_queried_object()->slug : 'all';
			?>
			<div class="products-grid" id="products-grid" data-current-page="<?php echo esc_attr( $grid_current_page ); ?>" data-max-pages="<?php echo esc_attr( $grid_max_pages ); ?>" data-category="<?php echo esc_attr( $grid_category ); ?>" data-infinite="<?php echo $grid_infinite ? '1' : '0'; ?>">
				<?php
				if ( have_posts() ) {
					while ( have_posts() ) {
						the_post();
						global $product;
						if ( $product && $product->is_in_stock() ) {
							wc_get_template_part( 'content', 'product' );
						}
					}
				} else {
					echo '<div class="products-empty">';
					echo '<i class="fa-solid fa-magnifying-glass products-empty-icon"></i>';
					echo '<h3 class="products-empty-title">' . esc_html__( 'No se encontraron productos disponibles', 'franco-theme' ) . '</h3>';
					echo '<p class="text-muted">' . esc_html__( 'Prueba ajustando los filtros de búsqueda o categoría.', 'franco-theme' ) . '</p>';
					echo '</div>';
				}
				?>
			</div>

			<?php if ( $grid_max_pages > 1 ) : ?>
				<!-- Crawlable pagination: hidden by JS when infinite scroll takes over -->
				<nav class="woocommerce-pagination franco-shop-pagination" id="shop-pagination" aria-label="<?php esc_attr_e( 'Paginación de productos', 'franco-theme' ); ?>">
					<?php
					$pagination_big = 999999999;
					echo wp_kses_post( paginate_links( array(
						'base'      => str_replace( $pagination_big, '%#%', esc_url( get_pagenum_link( $pagination_big ) ) ),
						'format'    => '',
						'current'   => $grid_current_page,
						'total'     => $grid_max_pages,
						'type'      => 'list',
						'mid_size'  => 2,
						'prev_text' => '&larr;',
						'next_text' => '&rarr;',
					) ) );
					?>
				</nav>
			<?php endif; ?>

			<!-- Infinite Scroll Loading Indicator -->
			<div id="infinite-scroll-loader" class="infinite-scroll-loader">
				<i class="fa-solid fa-spinner fa-spin infinite-scroll-spinner"></i>
				<p class="infinite-scroll-text"><?php esc_html_e( 'Cargando más productos...', 'franco-theme' ); ?></p>
			</div>

			<?php
			// Category copy (first page only, like WooCommerce core, to avoid duplicated content across pages).
			if ( is_product_category() && $grid_current_page < 2 ) {
				$term_description = term_description();
				if ( $term_description ) {
					echo '<div class="term-description">' . wp_kses_post( $term_description ) . '</div>';
				}
			}
			?>
		</main>
	</div>
</section>

<?php
get_footer( 'shop' );
