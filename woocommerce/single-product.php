<?php
/**
 * Custom WooCommerce Single Product Template (producto.html Layout)
 *
 * @package FrancoTheme
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );

while ( have_posts() ) :
	the_post();
	global $product;

	if ( empty( $product ) ) continue;

	$product_id        = $product->get_id();
	$product_name      = $product->get_name();
	$item_code         = function_exists( 'franco_get_product_item_code' ) ? franco_get_product_item_code( $product ) : '';
	$customer_postcode = ( class_exists( 'WooCommerce' ) && WC()->customer ) ? WC()->customer->get_shipping_postcode() : '';
	if ( empty( $customer_postcode ) && ! empty( $_COOKIE['franco_postcode'] ) ) {
		$customer_postcode = sanitize_text_field( wp_unslash( $_COOKIE['franco_postcode'] ) );
	}
	$image_id          = $product->get_image_id();
	$price_current     = franco_clean_price( wc_price( $product->get_price() ) );
	$price_old     = $product->is_on_sale() ? franco_clean_price( wc_price( $product->get_regular_price() ) ) : '';

	// Description: ONLY use full content / description (do NOT use short description)
	$description = get_the_content() ?: $product->get_description();

	// Brand: Retrieve from filter taxonomies (pa_marca, marca, pa_brand, brand) or product attribute
	$brand = '';
	$brand_taxonomies = array( 'pa_marca', 'marca', 'pa_brand', 'brand' );
	foreach ( $brand_taxonomies as $btax ) {
		if ( taxonomy_exists( $btax ) ) {
			$bterms = get_the_terms( $product_id, $btax );
			if ( ! empty( $bterms ) && ! is_wp_error( $bterms ) ) {
				$brand = mb_strtoupper( $bterms[0]->name );
				break;
			}
		}
	}
	if ( empty( $brand ) ) {
		$brand_attr = $product->get_attribute( 'marca' ) ?: ( $product->get_attribute( 'pa_marca' ) ?: $product->get_attribute( 'brand' ) );
		if ( $brand_attr ) {
			$brand = mb_strtoupper( $brand_attr );
		}
	}
	if ( empty( $brand ) ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		$brand = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? mb_strtoupper( $terms[0]->name ) : 'FRANCO ATHLETICS';
	}

	// Sizes & Colors & Attributes
	$sizes      = franco_wc_get_product_sizes( $product );
	$colors     = franco_wc_get_product_colors( $product );
	$attributes = array_filter( $product->get_attributes(), function( $attr ) {
		// Color se mantiene como atributo en WordPress pero no se muestra en la web.
		$n = strtolower( $attr->get_name() );
		return ! in_array( $n, array( 'pa_color', 'color', 'pa_colour', 'colour' ), true );
	} );

	// Stock por talle: un talle está disponible si alguna variación con ese talle tiene stock.
	$size_stock = array();
	if ( ! empty( $sizes ) ) {
		foreach ( $product->get_visible_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation ) continue;
			$v_size = $variation->get_attribute( 'pa_talle' );
			$v_slug = get_post_meta( $child_id, 'attribute_pa_talle', true );
			$in     = $variation->is_in_stock() && $variation->is_purchasable();
			foreach ( $sizes as $s ) {
				if ( '' === $v_slug || $v_slug === $s['slug'] || franco_normalize_talle( $v_size ) === $s['name'] ) {
					$size_stock[ $s['slug'] ] = ! empty( $size_stock[ $s['slug'] ] ) || $in;
				}
			}
		}
	}
	$default_size = '';
	foreach ( $sizes as $s ) {
		if ( ! empty( $size_stock[ $s['slug'] ] ) ) {
			$default_size = $s['slug'];
			break;
		}
	}

	// Product schema (JSON-LD): this custom template never fires woocommerce_single_product_summary.
	if ( function_exists( 'WC' ) && isset( WC()->structured_data ) ) {
		WC()->structured_data->generate_product_data( $product );
	}

	// Notices output and plugin hooks.
	do_action( 'woocommerce_before_single_product' );
?>

<section class="section">
	<div class="product-detail-grid product-detail-container" id="product-detail-container">
		<!-- Left: Image Gallery (Square Main Image + Vertical Thumbs on RIGHT) -->
		<?php
		$attachment_ids = $product->get_gallery_image_ids();
		$gallery_images = array();
		if ( $image_id ) {
			$gallery_images[] = wp_get_attachment_image_url( $image_id, 'franco-product-gallery' ) ?: wp_get_attachment_image_url( $image_id, 'full' );
		} else {
			$gallery_images[] = wc_placeholder_img_src( 'franco-product-gallery' );
		}
		if ( ! empty( $attachment_ids ) ) {
			foreach ( $attachment_ids as $att_id ) {
				$att_url = wp_get_attachment_image_url( $att_id, 'franco-product-gallery' ) ?: wp_get_attachment_image_url( $att_id, 'full' );
				if ( $att_url && ! in_array( $att_url, $gallery_images, true ) ) {
					$gallery_images[] = $att_url;
				}
			}
		}
		?>
		<div class="product-detail-gallery">
			<div class="product-gallery-layout">
				<div class="product-gallery-main">
					<div class="product-gallery-viewport" onclick="openGalleryLightbox(window.singleProductActiveIdx || 0)" style="cursor: zoom-in;" title="<?php esc_attr_e( 'Clic para ampliar imagen', 'franco-theme' ); ?>">
						<img id="main-product-img" src="<?php echo esc_url( $gallery_images[0] ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" class="gallery-main-img" width="600" height="600" fetchpriority="high" decoding="async">
					</div>
					<?php if ( count( $gallery_images ) > 1 ) : ?>
						<button type="button" class="gallery-nav-btn prev" onclick="navigateProductGallery(-1)" aria-label="Anterior">
							<i class="fa-solid fa-chevron-left"></i>
						</button>
						<button type="button" class="gallery-nav-btn next" onclick="navigateProductGallery(1)" aria-label="Siguiente">
							<i class="fa-solid fa-chevron-right"></i>
						</button>
					<?php endif; ?>
				</div>

				<?php if ( count( $gallery_images ) > 1 ) : ?>
					<div class="product-gallery-thumbs" id="product-gallery-thumbs">
						<?php foreach ( $gallery_images as $idx => $img_url ) : ?>
							<div class="gallery-thumb-item <?php echo ( 0 === $idx ) ? 'active' : ''; ?>" onclick="selectProductGalleryImage(<?php echo $idx; ?>)">
								<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $product_name ); ?> mini <?php echo $idx + 1; ?>" width="72" height="72" loading="lazy" decoding="async">
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Right: Product Info & Actions -->
		<div class="product-detail-info">
			<span class="product-brand"><?php echo esc_html( $brand ); ?></span>
			<h1 class="product-name product-detail-title">
				<?php echo esc_html( $product_name ); ?>
			</h1>

			<?php if ( ! empty( $item_code ) ) : ?>
				<div class="product-detail-item-code">
					<span><?php printf( esc_html__( 'Item N° %s', 'franco-theme' ), esc_html( $item_code ) ); ?></span>
				</div>
			<?php endif; ?>

			<div class="product-price-row product-detail-price-row">
				<span class="price-current product-detail-price"><?php echo esc_html( $price_current ); ?></span>
				<?php if ( $price_old ) : ?>
					<span class="price-old product-detail-price-old"><?php echo esc_html( $price_old ); ?></span>
				<?php endif; ?>
			</div>

			<!-- <div class="product-stock-box">
				<div class="product-stock-status">
					<i class="fa-solid fa-bolt"></i> <?php esc_html_e( 'Stock disponible con Envío Inmediato', 'franco-theme' ); ?>
				</div>
				<div class="product-stock-note">
					<?php esc_html_e( 'Pagando con Transferencia tenés un 15% OFF Extra.', 'franco-theme' ); ?>
				</div>
			</div> -->


			<?php if ( ! empty( $sizes ) ) : ?>
			<!-- Size Selector (Curva de talles) -->
			<div class="product-size-block" style="margin-bottom: 20px;">
				<span class="filter-label product-size-label" style="display: block; margin-bottom: 8px; font-weight: 700;">
					<?php esc_html_e( 'Seleccionar Talle:', 'franco-theme' ); ?>
				</span>
				<div class="size-selector size-curve" id="detail-size-selector" role="radiogroup">
					<?php foreach ( $sizes as $size ) :
						$has_stock = ! empty( $size_stock[ $size['slug'] ] ); ?>
						<button type="button"
							class="size-btn<?php echo $size['slug'] === $default_size ? ' active' : ''; ?><?php echo $has_stock ? '' : ' out-of-stock'; ?>"
							data-size="<?php echo esc_attr( $size['slug'] ); ?>"
							<?php echo $has_stock ? '' : 'disabled aria-disabled="true" title="' . esc_attr__( 'Sin stock', 'franco-theme' ) . '"'; ?>>
							<?php echo esc_html( $size['name'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Quantity Selector & Add to Cart -->
			<div class="product-buy-row">
				<div class="product-qty-box">
					<button class="product-qty-btn" onclick="changeDetailQty(-1)">-</button>
					<span id="detail-qty-val" class="product-qty-value">1</span>
					<button class="product-qty-btn" onclick="changeDetailQty(1)">+</button>
				</div>

				<button class="btn-primary product-add-btn" onclick="francoAddToCartDetail(<?php echo absint( $product_id ); ?>)">
					<i class="fa-solid fa-cart-shopping"></i> <?php esc_html_e( 'Agregar al Carrito', 'franco-theme' ); ?>
				</button>
			</div>

			<!-- Shipping Calculator Box -->
			<div class="product-shipping-calc-box" id="product-shipping-calc-box">
				<div class="shipping-calc-header">
					<i class="fa-solid fa-truck-fast text-primary"></i>
					<span><?php esc_html_e( 'Medios de envío y tiempos de entrega', 'franco-theme' ); ?></span>
				</div>
				<div class="shipping-calc-input-group">
					<input type="text" id="detail-shipping-postcode" class="form-input" placeholder="<?php esc_attr_e( 'Tu código postal...', 'franco-theme' ); ?>" value="<?php echo esc_attr( $customer_postcode ); ?>" maxlength="8" autocomplete="postal-code">
					<button type="button" class="btn-secondary" id="detail-shipping-calc-btn" onclick="francoCalculateProductShipping(<?php echo absint( $product_id ); ?>)">
						<?php esc_html_e( 'Calcular', 'franco-theme' ); ?>
					</button>
				</div>
				<div id="detail-shipping-results" class="shipping-calc-results"></div>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $description ) || ! empty( $attributes ) ) : ?>
	<!-- Full Width Product Tabs Section -->
	<div class="product-tabs-section">
		<div class="product-tabs-container">
			<div class="product-tab-nav" role="tablist">
				<?php if ( ! empty( $description ) ) : ?>
					<button type="button" class="tab-btn active" onclick="switchProductTab(event, 'tab-description')" role="tab" aria-selected="true" aria-controls="tab-description">
						<i class="fa-solid fa-align-left"></i> <?php esc_html_e( 'Descripción del Producto', 'franco-theme' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( ! empty( $attributes ) ) : ?>
					<button type="button" class="tab-btn <?php echo empty( $description ) ? 'active' : ''; ?>" onclick="switchProductTab(event, 'tab-attributes')" role="tab" aria-selected="<?php echo empty( $description ) ? 'true' : 'false'; ?>" aria-controls="tab-attributes">
						<i class="fa-solid fa-list-check"></i> <?php esc_html_e( 'Atributos y Especificaciones', 'franco-theme' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<div class="product-tab-content">
				<?php if ( ! empty( $description ) ) : ?>
					<div id="tab-description" class="tab-pane active" role="tabpanel">
						<div class="product-full-description">
							<?php echo apply_filters( 'the_content', $description ); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $attributes ) ) : ?>
					<div id="tab-attributes" class="tab-pane <?php echo empty( $description ) ? 'active' : ''; ?>" role="tabpanel">
						<table class="product-attributes-table">
							<tbody>
								<?php foreach ( $attributes as $attribute ) : ?>
									<?php
									$name = wc_attribute_label( $attribute->get_name() );
									if ( $attribute->is_taxonomy() ) {
										$terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
										$value = apply_filters( 'woocommerce_attribute', wptexturize( implode( ', ', $terms ) ), $attribute, $terms );
									} else {
										$value = apply_filters( 'woocommerce_attribute', wptexturize( implode( ', ', $attribute->get_options() ) ), $attribute, $attribute->get_options() );
									}
									if ( empty( $value ) ) continue;
									?>
									<tr>
										<th><?php echo esc_html( $name ); ?></th>
										<td><?php echo wp_kses_post( $value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>
</section>

<!-- Related Products Section -->
<section class="section product-related-section">
	<div class="section-header">
		<h2 class="section-title">TAMBIÉN TE PUEDE <span>INTERESAR</span></h2>
	</div>
	<div class="products-grid" id="related-products-grid">
		<?php
		$related_ids = wc_get_related_products( $product_id, 4 );
		if ( ! empty( $related_ids ) ) {
			$related_query = new WP_Query( array(
				'post_type'      => 'product',
				'post__in'       => $related_ids,
				'posts_per_page' => 4,
				'no_found_rows'  => true,
			) );

			if ( $related_query->have_posts() ) {
				while ( $related_query->have_posts() ) {
					$related_query->the_post();
					wc_get_template_part( 'content', 'product' );
				}
				wp_reset_postdata();
			}
		}
		?>
	</div>
</section>

<!-- Gallery Lightbox Modal -->
<div class="gallery-lightbox-backdrop" id="gallery-lightbox-backdrop" onclick="if(event.target === this) closeGalleryLightbox();">
	<div class="gallery-lightbox-modal">
		<button type="button" class="gallery-lightbox-close" onclick="closeGalleryLightbox()" aria-label="<?php esc_attr_e( 'Cerrar', 'franco-theme' ); ?>"><i class="fa-solid fa-xmark"></i></button>
		
		<div class="gallery-lightbox-content">
			<img id="lightbox-main-img" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E" alt="<?php echo esc_attr( $product_name ); ?> ampliada" class="lightbox-img">
		</div>

		<?php if ( count( $gallery_images ) > 1 ) : ?>
			<button type="button" class="gallery-lightbox-btn prev" onclick="navigateLightbox(-1)" aria-label="<?php esc_attr_e( 'Anterior', 'franco-theme' ); ?>">
				<i class="fa-solid fa-chevron-left"></i>
			</button>
			<button type="button" class="gallery-lightbox-btn next" onclick="navigateLightbox(1)" aria-label="<?php esc_attr_e( 'Siguiente', 'franco-theme' ); ?>">
				<i class="fa-solid fa-chevron-right"></i>
			</button>
		<?php endif; ?>

		<div class="gallery-lightbox-counter" id="lightbox-counter">1 / <?php echo count( $gallery_images ); ?></div>
	</div>
</div>

<script>
window.selectedDetailSize = <?php echo wp_json_encode( $default_size ); ?>;
window.selectedDetailColor = '';
window.selectedDetailQty = 1;
window.singleProductGallery = <?php echo wp_json_encode( $gallery_images ); ?>;
window.singleProductActiveIdx = 0;

document.querySelectorAll('#detail-size-selector .size-btn').forEach(function (btn) {
	btn.addEventListener('click', function () {
		if (btn.disabled || btn.classList.contains('out-of-stock')) return;
		document.querySelectorAll('#detail-size-selector .size-btn').forEach(function (b) { b.classList.remove('active'); });
		btn.classList.add('active');
		window.selectedDetailSize = btn.getAttribute('data-size');
	});
});

function selectProductGalleryImage(index) {
	if (!window.singleProductGallery || !window.singleProductGallery[index]) return;
	window.singleProductActiveIdx = index;
	const mainImg = document.getElementById('main-product-img');
	if (mainImg) {
		mainImg.style.transformOrigin = 'center center';
		mainImg.style.transform = 'scale(1)';
		mainImg.style.opacity = '0.3';
		setTimeout(function() {
			mainImg.src = window.singleProductGallery[index];
			mainImg.style.opacity = '1';
		}, 150);
	}
	const thumbsContainer = document.getElementById('product-gallery-thumbs');
	if (thumbsContainer) {
		const thumbs = thumbsContainer.querySelectorAll('.gallery-thumb-item');
		thumbs.forEach(function(t, i) {
			if (i === index) {
				t.classList.add('active');
				if (thumbsContainer && typeof t.offsetTop !== 'undefined') {
					thumbsContainer.scrollTop = t.offsetTop - thumbsContainer.offsetTop;
				}
			} else {
				t.classList.remove('active');
			}
		});
	}
	updateLightboxView(index);
}

function navigateProductGallery(delta) {
	if (!window.singleProductGallery || window.singleProductGallery.length <= 1) return;
	let newIdx = (window.singleProductActiveIdx + delta + window.singleProductGallery.length) % window.singleProductGallery.length;
	selectProductGalleryImage(newIdx);
}

// Lightbox Modal Functions
function openGalleryLightbox(index) {
	if (index === undefined || index === null) index = window.singleProductActiveIdx || 0;
	const backdrop = document.getElementById('gallery-lightbox-backdrop');
	if (!backdrop) return;
	selectProductGalleryImage(index);
	backdrop.classList.add('active');
	document.body.style.overflow = 'hidden';
}

function closeGalleryLightbox() {
	const backdrop = document.getElementById('gallery-lightbox-backdrop');
	if (!backdrop) return;
	backdrop.classList.remove('active');
	document.body.style.overflow = '';
}

function updateLightboxView(index) {
	const lightboxImg = document.getElementById('lightbox-main-img');
	const counter = document.getElementById('lightbox-counter');
	if (lightboxImg && window.singleProductGallery && window.singleProductGallery[index]) {
		lightboxImg.style.opacity = '0.4';
		setTimeout(function() {
			lightboxImg.src = window.singleProductGallery[index];
			lightboxImg.style.opacity = '1';
		}, 100);
	}
	if (counter && window.singleProductGallery) {
		counter.textContent = (index + 1) + ' / ' + window.singleProductGallery.length;
	}
}

function navigateLightbox(delta) {
	navigateProductGallery(delta);
}

document.addEventListener('keydown', function(e) {
	const backdrop = document.getElementById('gallery-lightbox-backdrop');
	if (!backdrop || !backdrop.classList.contains('active')) return;
	if (e.key === 'Escape') {
		closeGalleryLightbox();
	} else if (e.key === 'ArrowLeft') {
		navigateLightbox(-1);
	} else if (e.key === 'ArrowRight') {
		navigateLightbox(1);
	}
});

// Interactive Image Zoom
function initImageZoom() {
	const viewport = document.querySelector('.product-gallery-viewport');
	const mainImg = document.getElementById('main-product-img');
	if (!viewport || !mainImg) return;

	viewport.addEventListener('mousemove', function(e) {
		const rect = viewport.getBoundingClientRect();
		const x = ((e.clientX - rect.left) / rect.width) * 100;
		const y = ((e.clientY - rect.top) / rect.height) * 100;
		mainImg.style.transformOrigin = x + '% ' + y + '%';
		mainImg.style.transform = 'scale(2.2)';
	});

	viewport.addEventListener('mouseleave', function() {
		mainImg.style.transformOrigin = 'center center';
		mainImg.style.transform = 'scale(1)';
	});
}

function initProductGalleryFeatures() {
	initImageZoom();
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initProductGalleryFeatures);
} else {
	initProductGalleryFeatures();
}

function changeDetailQty(delta) {
	const el = document.getElementById('detail-qty-val');
	let val = (window.selectedDetailQty || 1) + delta;
	if (val < 1) val = 1;
	window.selectedDetailQty = val;
	if (el) el.textContent = val;
}

function francoAddToCartDetail(productId) {
	francoAddToCart(productId, window.selectedDetailSize, window.selectedDetailQty, window.selectedDetailColor);
}

function switchProductTab(event, tabId) {
	const container = event.target.closest('.product-tabs-container');
	if (!container) return;

	container.querySelectorAll('.tab-btn').forEach(function(btn) {
		btn.classList.remove('active');
		btn.setAttribute('aria-selected', 'false');
	});

	container.querySelectorAll('.tab-pane').forEach(function(pane) {
		pane.classList.remove('active');
	});

	event.currentTarget.classList.add('active');
	event.currentTarget.setAttribute('aria-selected', 'true');

	const targetPane = document.getElementById(tabId);
	if (targetPane) {
		targetPane.classList.add('active');
	}
}
</script>

<?php
endwhile;

get_footer( 'shop' );
