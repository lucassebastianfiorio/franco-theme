<?php
/**
 * Dedicated WordPress Admin Menu & Settings Panel for Franco Deportes
 *
 * @package FrancoTheme
 * @version 1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Top-Level Menu in WP Admin Sidebar
 */
function franco_add_admin_menu() {
	add_menu_page(
		__( 'Franco Deportes - Ajustes', 'franco-theme' ), // Page title
		__( 'Franco Deportes', 'franco-theme' ),           // Menu title
		'manage_options',                                  // Capability
		'franco-settings',                                 // Menu slug
		'franco_settings_page_render',                     // Callback function
		'dashicons-store',                                 // Icon
		30                                                 // Position
	);
}
add_action( 'admin_menu', 'franco_add_admin_menu' );

/**
 * Register Settings in WordPress Options API
 */
function franco_register_admin_settings() {
	$text_settings = array(
		'franco_enable_topbar',
		'franco_topbar_text_1',
		'franco_topbar_text_2',
		'franco_topbar_text_3',
		'franco_whatsapp_number',
		'franco_contact_phone',
		'franco_bank_cbu',
		'franco_bank_alias',
		'franco_bank_name',
		'franco_remove_last_word_product_title',
		'franco_business_hours',
	);

	foreach ( $text_settings as $setting ) {
		register_setting( 'franco_settings_group', $setting, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'franco_enable_topbar' === $setting ? '1' : '',
		) );
	}

	$url_settings = array(
		'franco_hero_banner_image',
		'franco_store_banner_image',
		'franco_instagram_url',
		'franco_facebook_url',
		'franco_tiktok_url',
		'franco_header_logo',
		'franco_footer_logo',
		'franco_checkout_logo',
	);

	foreach ( $url_settings as $setting ) {
		register_setting( 'franco_settings_group', $setting, array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		) );
	}

	register_setting( 'franco_settings_group', 'franco_contact_email', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_email',
		'default'           => '',
	) );

	$page_settings = array(
		'franco_page_nosotros',
		'franco_page_contacto',
		'franco_page_favoritos',
		'franco_page_tienda',
		'franco_page_carrito',
		'franco_page_checkout',
		'franco_page_myaccount',
	);

	foreach ( $page_settings as $setting ) {
		register_setting( 'franco_settings_group', $setting, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );
	}
}
add_action( 'admin_init', 'franco_register_admin_settings' );

/**
 * Synchronize Franco Page Settings with WooCommerce Core Options
 */
function franco_sync_wc_page_options( $option, $arg2 = null, $arg3 = null ) {
	if ( 0 !== strpos( $option, 'franco_page_' ) ) {
		return;
	}

	$new_value = ( null !== $arg3 ) ? (int) $arg3 : (int) $arg2;

	if ( 'franco_page_checkout' === $option && $new_value > 0 ) {
		update_option( 'woocommerce_checkout_page_id', $new_value );
	} elseif ( 'franco_page_carrito' === $option && $new_value > 0 ) {
		update_option( 'woocommerce_cart_page_id', $new_value );
	} elseif ( 'franco_page_tienda' === $option && $new_value > 0 ) {
		update_option( 'woocommerce_shop_page_id', $new_value );
	} elseif ( 'franco_page_myaccount' === $option && $new_value > 0 ) {
		update_option( 'woocommerce_myaccount_page_id', $new_value );
	}
}
add_action( 'updated_option', 'franco_sync_wc_page_options', 10, 3 );
add_action( 'added_option', 'franco_sync_wc_page_options', 10, 2 );

/**
 * Helper to Automatically Create Missing Pages & Assign Templates
 */
function franco_auto_create_pages() {
	$pages_to_create = array(
		'nosotros'  => array(
			'title'    => 'Nosotros',
			'template' => 'page-nosotros.php',
			'option'   => 'franco_page_nosotros',
		),
		'contacto'  => array(
			'title'    => 'Contacto',
			'template' => 'page-contacto.php',
			'option'   => 'franco_page_contacto',
		),
		'favoritos' => array(
			'title'    => 'Mis Favoritos',
			'template' => 'page-favoritos.php',
			'option'   => 'franco_page_favoritos',
		),
		'tienda'    => array(
			'title'    => 'Tienda',
			'template' => '',
			'option'   => 'franco_page_tienda',
			'wc_option'=> 'woocommerce_shop_page_id',
		),
		'carrito'   => array(
			'title'    => 'Carrito de Compras',
			'template' => '',
			'option'   => 'franco_page_carrito',
			'wc_option'=> 'woocommerce_cart_page_id',
		),
		'checkout'  => array(
			'title'    => 'Finalizar Compra',
			'template' => '',
			'option'   => 'franco_page_checkout',
			'wc_option'=> 'woocommerce_checkout_page_id',
		),
		'mi-cuenta' => array(
			'title'    => 'Mi Cuenta',
			'template' => '',
			'option'   => 'franco_page_myaccount',
			'wc_option'=> 'woocommerce_myaccount_page_id',
		),
	);

	$created_count = 0;

	foreach ( $pages_to_create as $slug => $data ) {
		$existing_id = get_option( $data['option'] );

		if ( ! $existing_id && ! empty( $data['wc_option'] ) ) {
			$existing_id = get_option( $data['wc_option'] );
		}

		if ( $existing_id && get_post( $existing_id ) ) {
			update_option( $data['option'], $existing_id );
			if ( ! empty( $data['wc_option'] ) ) {
				update_option( $data['wc_option'], $existing_id );
			}
			continue;
		}

		// Search page by title
		$found_pages = get_posts( array(
			'title'                  => $data['title'],
			'post_type'              => 'page',
			'post_status'            => array( 'publish', 'draft', 'private' ),
			'numberposts'            => 1,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		) );

		if ( ! empty( $found_pages ) ) {
			$page_id = $found_pages[0]->ID;
		} else {
			// Create the page
			$page_id = wp_insert_post( array(
				'post_title'  => $data['title'],
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
			) );
			$created_count++;
		}

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			if ( ! empty( $data['template'] ) ) {
				update_post_meta( $page_id, '_wp_page_template', $data['template'] );
			}
			update_option( $data['option'], $page_id );
			if ( ! empty( $data['wc_option'] ) ) {
				update_option( $data['wc_option'], $page_id );
			}
		}
	}

	return $created_count;
}

/**
 * Render Admin Settings Page HTML
 */
function franco_settings_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle Auto-Create Pages Form Submission
	if ( isset( $_POST['franco_action'] ) && 'create_pages' === $_POST['franco_action'] ) {
		check_admin_referer( 'franco_create_pages_nonce' );
		$created = franco_auto_create_pages();
		add_settings_error( 'franco_messages', 'franco_message', sprintf( __( 'Se han creado y verificado las páginas del sitio (%d páginas creadas/asociadas).', 'franco-theme' ), $created ), 'updated' );
	}

	// Handle Save Notification
	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error( 'franco_messages', 'franco_message', __( 'Ajustes guardados y sincronizados correctamente con WooCommerce.', 'franco-theme' ), 'updated' );
	}

	$enable_topbar = franco_get_setting( 'franco_enable_topbar', '1' );
	$topbar_1      = franco_get_setting( 'franco_topbar_text_1', 'Envíos a todo el país | GRATIS en compras > $80.000' );
	$topbar_2     = franco_get_setting( 'franco_topbar_text_2', '3 y 6 Cuotas Sin Interés' );
	$topbar_3     = franco_get_setting( 'franco_topbar_text_3', '15% OFF abonando por Transferencia / Efectivo' );
	$whatsapp     = franco_get_setting( 'franco_whatsapp_number', '5491112345678' );
	$phone        = franco_get_setting( 'franco_contact_phone', '+54 11 1234 5678' );
	$email        = franco_get_setting( 'franco_contact_email', 'ventas@francodeportes.com' );
	$hero_img         = franco_get_setting( 'franco_hero_banner_image', FRANCO_THEME_URI . '/assets/images/hero_banner.webp' );
	$store_img        = franco_get_setting( 'franco_store_banner_image', '' );
	$header_logo      = franco_get_setting( 'franco_header_logo', FRANCO_THEME_URI . '/assets/images/logo.webp' );
	$footer_logo      = franco_get_setting( 'franco_footer_logo', '' );
	$checkout_logo    = franco_get_setting( 'franco_checkout_logo', '' );
	$bank_cbu         = franco_get_setting( 'franco_bank_cbu', '' );
	$bank_alias       = franco_get_setting( 'franco_bank_alias', '' );
	$bank_name        = franco_get_setting( 'franco_bank_name', 'Franco Deportes S.R.L.' );
	$instagram        = franco_get_setting( 'franco_instagram_url', 'https://instagram.com' );
	$facebook         = franco_get_setting( 'franco_facebook_url', 'https://facebook.com' );
	$tiktok           = franco_get_setting( 'franco_tiktok_url', 'https://tiktok.com' );
	$remove_last_word = franco_get_setting( 'franco_remove_last_word_product_title', '0' );
	$business_hours   = franco_get_setting( 'franco_business_hours', 'Lunes a Sábados de 09:00 a 20:00 hs.' );

	$page_nosotros  = get_option( 'franco_page_nosotros', 0 );
	$page_contacto  = get_option( 'franco_page_contacto', 0 );
	$page_favoritos = get_option( 'franco_page_favoritos', 0 );
	$page_tienda    = get_option( 'franco_page_tienda', ( class_exists( 'WooCommerce' ) ? wc_get_page_id( 'shop' ) : 0 ) );
	$page_carrito   = get_option( 'franco_page_carrito', ( class_exists( 'WooCommerce' ) ? wc_get_page_id( 'cart' ) : 0 ) );
	$page_checkout  = get_option( 'franco_page_checkout', ( class_exists( 'WooCommerce' ) ? wc_get_page_id( 'checkout' ) : 0 ) );
	$page_myaccount = get_option( 'franco_page_myaccount', ( class_exists( 'WooCommerce' ) ? wc_get_page_id( 'myaccount' ) : 0 ) );
	?>

	<div class="wrap franco-admin">
		<h1 class="franco-admin-title">
			<span class="dashicons dashicons-store franco-admin-title-icon"></span>
			<?php esc_html_e( 'Panel de Configuración - Franco Deportes', 'franco-theme' ); ?>
		</h1>

		<?php settings_errors( 'franco_messages' ); ?>

		<!-- Auto Create Pages Quick Action Button -->
		<div class="franco-admin-notice">
			<div>
				<strong class="franco-admin-notice-title">⚡ Creación y Sincronización Automática de Páginas</strong>
				<span class="franco-admin-notice-text">Crea y sincroniza automáticamente las páginas de WooCommerce (Tienda, Carrito, Finalizar Compra, Mi Cuenta) y del theme (Nosotros, Contacto, Favoritos).</span>
			</div>
			<form method="post" action="">
				<?php wp_nonce_field( 'franco_create_pages_nonce' ); ?>
				<input type="hidden" name="franco_action" value="create_pages">
				<button type="submit" class="button button-primary franco-admin-create-btn">
					⚙️ Crear / Sincronizar Páginas
				</button>
			</form>
		</div>

		<form method="post" action="options.php" class="franco-admin-form">
			<?php settings_fields( 'franco_settings_group' ); ?>
			<?php do_settings_sections( 'franco_settings_group' ); ?>

			<h2 class="franco-admin-section-title">
				📄 1. Asignación de Páginas del Sitio y WooCommerce
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="franco_page_tienda">Página de Tienda (WooCommerce)</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'franco_page_tienda',
							'id'                => 'franco_page_tienda',
							'selected'          => $page_tienda,
							'show_option_none'  => __( '-- Seleccionar Página --', 'franco-theme' ),
							'option_none_value' => '0',
						) );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_page_checkout">Página de Finalizar Compra / Checkout (WooCommerce)</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'franco_page_checkout',
							'id'                => 'franco_page_checkout',
							'selected'          => $page_checkout,
							'show_option_none'  => __( '-- Seleccionar Página --', 'franco-theme' ),
							'option_none_value' => '0',
						) );
						?>
						<p class="description">Aplica automáticamente la plantilla personalizada de Checkout a la página seleccionada.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_page_carrito">Página de Carrito de Compras (WooCommerce)</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'franco_page_carrito',
							'id'                => 'franco_page_carrito',
							'selected'          => $page_carrito,
							'show_option_none'  => __( '-- Seleccionar Página --', 'franco-theme' ),
							'option_none_value' => '0',
						) );
						?>
						<p class="description">Aplica automáticamente la plantilla de Carrito Vacío y Carrito Personalizado a la página seleccionada.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_page_myaccount">Página de Mi Cuenta (WooCommerce)</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'franco_page_myaccount',
							'id'                => 'franco_page_myaccount',
							'selected'          => $page_myaccount,
							'show_option_none'  => __( '-- Seleccionar Página --', 'franco-theme' ),
							'option_none_value' => '0',
						) );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_page_nosotros">Página de Nosotros</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'franco_page_nosotros',
							'id'                => 'franco_page_nosotros',
							'selected'          => $page_nosotros,
							'show_option_none'  => __( '-- Seleccionar Página --', 'franco-theme' ),
							'option_none_value' => '0',
						) );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_page_contacto">Página de Contacto</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'franco_page_contacto',
							'id'                => 'franco_page_contacto',
							'selected'          => $page_contacto,
							'show_option_none'  => __( '-- Seleccionar Página --', 'franco-theme' ),
							'option_none_value' => '0',
						) );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_page_favoritos">Página de Favoritos</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'franco_page_favoritos',
							'id'                => 'franco_page_favoritos',
							'selected'          => $page_favoritos,
							'show_option_none'  => __( '-- Seleccionar Página --', 'franco-theme' ),
							'option_none_value' => '0',
						) );
						?>
					</td>
				</tr>
			</table>

			<h2 class="franco-admin-section-title-spaced">
				📢 2. Textos de la Barra Superior (Anuncios)
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Mostrar Barra Superior</th>
					<td>
						<label for="franco_enable_topbar" style="font-weight: 600;">
							<input type="hidden" name="franco_enable_topbar" value="0">
							<input type="checkbox" id="franco_enable_topbar" name="franco_enable_topbar" value="1" <?php checked( '1', $enable_topbar ); ?>>
							Mostrar la barra superior de anuncios en el sitio
						</label>
						<p class="description">Si se desmarca esta opción, la barra superior de anuncios estará oculta en todo el sitio.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_topbar_text_1">Anuncio 1</label></th>
					<td>
						<input type="text" id="franco_topbar_text_1" name="franco_topbar_text_1" value="<?php echo esc_attr( $topbar_1 ); ?>" class="regular-text">
						<p class="description">Ejemplo: Envíos a todo el país | GRATIS en compras > $80.000</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_topbar_text_2">Anuncio 2</label></th>
					<td>
						<input type="text" id="franco_topbar_text_2" name="franco_topbar_text_2" value="<?php echo esc_attr( $topbar_2 ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_topbar_text_3">Anuncio 3</label></th>
					<td>
						<input type="text" id="franco_topbar_text_3" name="franco_topbar_text_3" value="<?php echo esc_attr( $topbar_3 ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

			<h2 class="franco-admin-section-title-spaced">
				📞 3. Datos de Contacto y WhatsApp
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="franco_business_hours">Horarios de Atención</label></th>
					<td>
						<input type="text" id="franco_business_hours" name="franco_business_hours" value="<?php echo esc_attr( $business_hours ); ?>" class="regular-text">
						<p class="description">Ej: Lunes a Sábados de 09:00 a 20:00 hs.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_whatsapp_number">Número de WhatsApp</label></th>
					<td>
						<input type="text" id="franco_whatsapp_number" name="franco_whatsapp_number" value="<?php echo esc_attr( $whatsapp ); ?>" class="regular-text">
						<p class="description">Número con código de país sin espacio ni signo (+). Ejemplo: 5491112345678</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_contact_phone">Teléfono Visible</label></th>
					<td>
						<input type="text" id="franco_contact_phone" name="franco_contact_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_contact_email">Correo de Contacto</label></th>
					<td>
						<input type="email" id="franco_contact_email" name="franco_contact_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

			<h2 class="franco-admin-section-title-spaced">
				🎨 4. Logos del Sitio
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="franco_header_logo">Logo del Header (Cabecera)</label></th>
					<td>
						<div style="display: flex; gap: 10px; align-items: center; max-width: 650px;">
							<input type="text" id="franco_header_logo" name="franco_header_logo" value="<?php echo esc_attr( $header_logo ); ?>" class="large-text">
							<button type="button" class="button franco-upload-media-btn" data-target="#franco_header_logo">Subir / Seleccionar</button>
						</div>
						<p class="description">Logo principal para la cabecera del sitio.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_footer_logo">Logo del Footer (Pie de Página)</label></th>
					<td>
						<div style="display: flex; gap: 10px; align-items: center; max-width: 650px;">
							<input type="text" id="franco_footer_logo" name="franco_footer_logo" value="<?php echo esc_attr( $footer_logo ); ?>" class="large-text">
							<button type="button" class="button franco-upload-media-btn" data-target="#franco_footer_logo">Subir / Seleccionar</button>
						</div>
						<p class="description">Logo opcional para el pie de página. Si se deja en blanco, usará el logo del Header.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_checkout_logo">Logo del Checkout</label></th>
					<td>
						<div style="display: flex; gap: 10px; align-items: center; max-width: 650px;">
							<input type="text" id="franco_checkout_logo" name="franco_checkout_logo" value="<?php echo esc_attr( $checkout_logo ); ?>" class="large-text">
							<button type="button" class="button franco-upload-media-btn" data-target="#franco_checkout_logo">Subir / Seleccionar</button>
						</div>
						<p class="description">Logo opcional para el checkout. Si se deja en blanco, usará el logo del Header.</p>
					</td>
				</tr>
			</table>

			<h2 class="franco-admin-section-title-spaced">
				🖼️ 5. Banners e Imágenes
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="franco_hero_banner_image">Imagen de Banner Principal (Hero)</label></th>
					<td>
						<div style="display: flex; gap: 10px; align-items: center; max-width: 650px;">
							<input type="text" id="franco_hero_banner_image" name="franco_hero_banner_image" value="<?php echo esc_attr( $hero_img ); ?>" class="large-text">
							<button type="button" class="button franco-upload-media-btn" data-target="#franco_hero_banner_image">Subir / Seleccionar</button>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_store_banner_image">Imagen Banner Tienda (Opcional)</label></th>
					<td>
						<div style="display: flex; gap: 10px; align-items: center; max-width: 650px;">
							<input type="text" id="franco_store_banner_image" name="franco_store_banner_image" value="<?php echo esc_attr( $store_img ); ?>" class="large-text">
							<button type="button" class="button franco-upload-media-btn" data-target="#franco_store_banner_image">Subir / Seleccionar</button>
						</div>
					</td>
				</tr>
			</table>

			<h2 class="franco-admin-section-title-spaced">
				🏦 6. Datos para Transferencia Bancaria
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="franco_bank_cbu">CBU</label></th>
					<td>
						<input type="text" id="franco_bank_cbu" name="franco_bank_cbu" value="<?php echo esc_attr( $bank_cbu ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_bank_alias">Alias</label></th>
					<td>
						<input type="text" id="franco_bank_alias" name="franco_bank_alias" value="<?php echo esc_attr( $bank_alias ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_bank_name">Titular de la Cuenta</label></th>
					<td>
						<input type="text" id="franco_bank_name" name="franco_bank_name" value="<?php echo esc_attr( $bank_name ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

			<h2 class="franco-admin-section-title-spaced">
				🌐 7. Redes Sociales
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="franco_instagram_url">Instagram URL</label></th>
					<td>
						<input type="url" id="franco_instagram_url" name="franco_instagram_url" value="<?php echo esc_attr( $instagram ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_facebook_url">Facebook URL</label></th>
					<td>
						<input type="url" id="franco_facebook_url" name="franco_facebook_url" value="<?php echo esc_attr( $facebook ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="franco_tiktok_url">TikTok URL</label></th>
					<td>
						<input type="url" id="franco_tiktok_url" name="franco_tiktok_url" value="<?php echo esc_attr( $tiktok ); ?>" class="regular-text">
					</td>
				</tr>
			</table>

			<h2 class="franco-admin-section-title-spaced">
				🛍️ 8. Formato de Títulos de Productos
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Ocultar Código/Última Palabra</th>
					<td>
						<label for="franco_remove_last_word_product_title" style="font-weight: 600;">
							<input type="checkbox" id="franco_remove_last_word_product_title" name="franco_remove_last_word_product_title" value="1" <?php checked( '1', $remove_last_word ); ?>>
							Remover la última palabra del nombre de los productos en la tienda
						</label>
						<p class="description">
							Ejemplo: Si el producto se llama <code>ZAPATILLA TOPPER PROFESIONAL T89600</code> o <code>SUECO CROCS BAND KIDS C10998</code>, se mostrará como <code>ZAPATILLA TOPPER PROFESIONAL</code> o <code>SUECO CROCS BAND KIDS</code> en el catálogo, vista rápida y ficha de producto.
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Guardar Cambios', 'franco-theme' ), 'primary button-hero' ); ?>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($){
		$('.franco-upload-media-btn').on('click', function(e){
			e.preventDefault();
			var targetInput = $( $(this).data('target') );
			var mediaFrame = wp.media({
				title: 'Seleccionar Imagen',
				button: { text: 'Usar esta imagen' },
				multiple: false
			});
			mediaFrame.on('select', function(){
				var attachment = mediaFrame.state().get('selection').first().toJSON();
				targetInput.val(attachment.url);
			});
			mediaFrame.open();
		});
	});
	</script>
	<?php
}
