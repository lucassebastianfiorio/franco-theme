<?php
/**
 * Header Template
 *
 * @package FrancoTheme
 * @version 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enable_topbar = franco_get_setting( 'franco_enable_topbar', '1' );
$text1 = franco_get_setting( 'franco_topbar_text_1', 'Envíos a todo el país | GRATIS en compras > $80.000' );
$text2 = franco_get_setting( 'franco_topbar_text_2', '3 y 6 Cuotas Sin Interés' );
$text3 = franco_get_setting( 'franco_topbar_text_3', '15% OFF abonando por Transferencia / Efectivo' );
$cart_count = ( class_exists( 'WooCommerce' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

$fav_page_id  = get_option( 'franco_page_favoritos', 0 );
$favoritos_url = $fav_page_id ? get_permalink( $fav_page_id ) : home_url( '/favoritos' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( function_exists( 'franco_is_checkout_page' ) && franco_is_checkout_page() ) : ?>
<!-- Minimal Checkout Header -->
<header class="checkout-minimal-header">
	<div class="container checkout-header-inner">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="checkout-logo-link">
			<img src="<?php echo esc_url( franco_get_theme_logo_url( 'checkout' ) ); ?>" alt="<?php esc_attr_e( 'Franco Deportes', 'franco-theme' ); ?>" class="checkout-logo-img">
		</a>
		<a href="<?php echo esc_url( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda' ) ); ?>" class="btn-secondary checkout-back-store-btn">
			<i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Volver a la tienda', 'franco-theme' ); ?>
		</a>
	</div>
</header>
<?php else : ?>
<?php if ( '1' === (string) $enable_topbar ) : ?>
<!-- Top Announcement Bar -->
<div class="top-bar">
	<div class="top-bar-content">
		<div class="top-bar-item">
			<i class="fa-solid fa-truck-fast"></i>
			<span><?php echo wp_kses_post( $text1 ); ?></span>
		</div>
		<div class="top-bar-item">
			<i class="fa-solid fa-credit-card"></i>
			<span><?php echo wp_kses_post( $text2 ); ?></span>
		</div>
		<div class="top-bar-item">
			<i class="fa-solid fa-bolt"></i>
			<span><?php echo wp_kses_post( $text3 ); ?></span>
		</div>
		<!-- Duplicate items for mobile continuous marquee -->
		<div class="top-bar-item top-bar-item-dup">
			<i class="fa-solid fa-truck-fast"></i>
			<span><?php echo wp_kses_post( $text1 ); ?></span>
		</div>
		<div class="top-bar-item top-bar-item-dup">
			<i class="fa-solid fa-credit-card"></i>
			<span><?php echo wp_kses_post( $text2 ); ?></span>
		</div>
		<div class="top-bar-item top-bar-item-dup">
			<i class="fa-solid fa-bolt"></i>
			<span><?php echo wp_kses_post( $text3 ); ?></span>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Sticky Navbar Header -->
<header class="navbar-sticky">
	<div class="mobile-nav-backdrop" id="mobile-nav-backdrop"></div>
	<nav class="nav-container">
		<button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="<?php esc_attr_e( 'Abrir menú de navegación', 'franco-theme' ); ?>">
			<i class="fa-solid fa-bars"></i>
		</button>

		<?php franco_render_site_logo(); ?>

		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'nav-menu',
				'menu_id'        => 'nav-menu',
				'depth'          => 2,
			) );
		} else {
			franco_primary_menu_fallback();
		}
		?>

		<div class="nav-actions">
			<!-- Location / Postal Code Selector -->
			<div class="header-location-wrap" id="header-location-wrap">
				<button type="button" class="header-location-btn" id="header-location-btn" title="<?php esc_attr_e( 'Elegí tu código postal para envíos', 'franco-theme' ); ?>">
					<i class="fa-solid fa-location-dot"></i>
					<span class="location-label-text" id="header-location-text">
						<?php
						// Only prefill from session for logged-in users to keep anonymous HTML 100% cacheable
						$current_cp = ( is_user_logged_in() && class_exists( 'WooCommerce' ) && WC()->customer ) ? WC()->customer->get_shipping_postcode() : '';
						echo ! empty( $current_cp ) ? sprintf( esc_html__( 'CP %s', 'franco-theme' ), esc_html( $current_cp ) ) : esc_html__( 'CP', 'franco-theme' );
						?>
					</span>
				</button>
				<div class="header-location-popover is-hidden" id="header-location-popover">
					<div class="location-popover-title">
						<i class="fa-solid fa-truck-fast text-primary"></i>
						<span><?php esc_html_e( '¿Dónde recibís tu compra?', 'franco-theme' ); ?></span>
					</div>
					<p class="location-popover-desc"><?php esc_html_e( 'Ayudanos a localizarte para verificar disponibilidad y cobertura en tu zona', 'franco-theme' ); ?></p>
					<form class="location-popover-form" id="header-location-form">
						<input type="text" id="header-location-input" class="form-input" placeholder="Ej: 1650" maxlength="8" value="<?php echo esc_attr( $current_cp ); ?>" autocomplete="postal-code" required>
						<button type="submit" class="btn-primary"><?php esc_html_e( 'Guardar', 'franco-theme' ); ?></button>
					</form>
				</div>
			</div>

			<!-- Live Search Button -->
			<button class="action-btn mobile-search-btn" id="mobile-search-btn" title="<?php esc_attr_e( 'Buscar productos', 'franco-theme' ); ?>">
				<i class="fa-solid fa-magnifying-glass"></i>
			</button>

			<!-- Wishlist Link -->
			<a href="<?php echo esc_url( $favoritos_url ); ?>" class="action-btn" id="fav-btn-nav" title="<?php esc_attr_e( 'Mis Favoritos', 'franco-theme' ); ?>">
				<i class="fa-regular fa-heart"></i>
				<span class="badge-count" id="fav-badge-count">0</span>
			</a>

			<!-- User Account / Login Button -->
			<?php
			$account_url        = function_exists( 'franco_get_myaccount_url' ) ? franco_get_myaccount_url() : ( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/mi-cuenta' ) );
			$is_user_logged     = is_user_logged_in();
			$current_user       = $is_user_logged ? wp_get_current_user() : null;
			$display_first_name = $current_user ? ( $current_user->first_name ?: $current_user->display_name ) : '';
			?>
			<div class="header-user-wrap" id="header-user-wrap">
				<?php if ( $is_user_logged ) : ?>
					<button type="button" class="action-btn header-user-btn logged-in" id="header-user-btn" aria-label="<?php printf( esc_attr__( 'Mi Cuenta (%s)', 'franco-theme' ), esc_attr( $display_first_name ) ); ?>" title="<?php printf( esc_attr__( 'Mi Cuenta (%s)', 'franco-theme' ), esc_attr( $display_first_name ) ); ?>" aria-haspopup="true" aria-expanded="false">
						<i class="fa-solid fa-user-check"></i>
					</button>
					<div class="header-user-dropdown is-hidden" id="header-user-dropdown">
						<div class="user-dropdown-header">
							<strong><?php echo esc_html( $current_user->display_name ); ?></strong>
							<span><?php echo esc_html( $current_user->user_email ); ?></span>
						</div>
						<ul class="user-dropdown-menu">
							<li><a href="<?php echo esc_url( $account_url ); ?>"><i class="fa-solid fa-gauge-high"></i> <?php esc_html_e( 'Mi Cuenta', 'franco-theme' ); ?></a></li>
							<li><a href="<?php echo esc_url( function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : $account_url ); ?>"><i class="fa-solid fa-box-open"></i> <?php esc_html_e( 'Mis Compras', 'franco-theme' ); ?></a></li>
							<li><a href="<?php echo esc_url( function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-address' ) : $account_url ); ?>"><i class="fa-solid fa-location-dot"></i> <?php esc_html_e( 'Mis Direcciones', 'franco-theme' ); ?></a></li>
							<li><a href="<?php echo esc_url( function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'edit-account' ) : $account_url ); ?>"><i class="fa-solid fa-user-gear"></i> <?php esc_html_e( 'Datos y Contraseña', 'franco-theme' ); ?></a></li>
							<li class="dropdown-divider"></li>
							<li><a href="<?php echo esc_url( function_exists( 'wc_logout_url' ) ? wc_logout_url( home_url() ) : wp_logout_url( home_url() ) ); ?>" class="text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> <?php esc_html_e( 'Cerrar Sesión', 'franco-theme' ); ?></a></li>
						</ul>
					</div>
				<?php else : ?>
					<button type="button" class="action-btn header-user-btn" id="header-user-btn" aria-label="<?php esc_attr_e( 'Mi Cuenta / Ingresar', 'franco-theme' ); ?>" title="<?php esc_attr_e( 'Mi Cuenta / Ingresar', 'franco-theme' ); ?>" aria-haspopup="true" aria-expanded="false">
						<i class="fa-regular fa-user"></i>
					</button>
					<div class="header-user-dropdown is-hidden" id="header-user-dropdown">
						<div class="user-dropdown-guest">
							<p class="user-dropdown-welcome"><strong><?php esc_html_e( '¡Te damos la bienvenida!', 'franco-theme' ); ?></strong></p>
							<p class="user-dropdown-desc text-muted"><?php esc_html_e( 'Ingresá a tu cuenta para ver tus compras y gestionar tus datos.', 'franco-theme' ); ?></p>
							<a href="<?php echo esc_url( $account_url ); ?>" class="user-dropdown-btn-login">
								<i class="fa-solid fa-arrow-right-to-bracket"></i> <?php esc_html_e( 'Iniciar Sesión', 'franco-theme' ); ?>
							</a>
							<a href="<?php echo esc_url( add_query_arg( 'action', 'register', $account_url ) ); ?>" class="user-dropdown-btn-register">
								<i class="fa-solid fa-user-plus"></i> <?php esc_html_e( 'Crear Cuenta', 'franco-theme' ); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- Cart Button -->
			<button class="action-btn" id="cart-btn" title="<?php esc_attr_e( 'Carrito de compras', 'franco-theme' ); ?>">
				<i class="fa-solid fa-bag-shopping"></i>
				<span class="badge-count" id="cart-badge-count"><?php echo esc_html( $cart_count ); ?></span>
			</button>
		</div>
	</nav>
</header>

<?php get_template_part( 'template-parts/header/cart-drawer' ); ?>
<?php get_template_part( 'template-parts/header/search-modal' ); ?>
<?php endif; ?>
