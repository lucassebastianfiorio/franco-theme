<?php
/**
 * WooCommerce Loop Product Card Override
 *
 * @package FrancoTheme
 * @version 1.0.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

// Hide product if empty, invisible, or OUT OF STOCK
if ( empty( $product ) || ! $product->is_visible() || ! $product->is_in_stock() ) {
	return;
}

$product_id    = $product->get_id();
$product_name  = $product->get_name();
$item_code     = function_exists( 'franco_get_product_item_code' ) ? franco_get_product_item_code( $product ) : '';
$product_url   = get_permalink( $product_id );
$thumb_id      = $product->get_image_id();
$price_current = franco_clean_price( wc_price( $product->get_price() ) );
$price_old     = $product->is_on_sale() ? franco_clean_price( wc_price( $product->get_regular_price() ) ) : '';
$badge_text    = franco_get_product_badge( $product );

$terms = get_the_terms( $product_id, 'product_cat' );
$brand = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? mb_strtoupper( $terms[0]->name ) : 'FRANCO RUNNING';
?>

<div <?php wc_product_class( 'product-card', $product ); ?> data-product-id="<?php echo esc_attr( $product_id ); ?>">
	<span class="product-badge"><?php echo esc_html( $badge_text ); ?></span>

	<button class="fav-btn" onclick="toggleWishlist(<?php echo esc_attr( $product_id ); ?>)" title="<?php esc_attr_e( 'Mis Favoritos', 'franco-theme' ); ?>">
		<i class="fa-regular fa-heart"></i>
	</button>

	<div class="product-img-wrap">
		<a href="<?php echo esc_url( $product_url ); ?>">
			<?php
			if ( $thumb_id ) {
				echo wp_get_attachment_image( $thumb_id, 'franco-product-card', false, array(
					'alt'      => $product_name,
					'loading'  => 'lazy',
					'decoding' => 'async',
				) );
			} else {
				?>
				<img src="<?php echo esc_url( wc_placeholder_img_src( 'franco-product-card' ) ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" width="400" height="400" loading="lazy" decoding="async">
				<?php
			}
			?>
		</a>
		<div class="quick-view-overlay">
			<button class="quick-view-btn" onclick="openQuickView(<?php echo esc_attr( $product_id ); ?>)">
				<i class="fa-solid fa-eye"></i> <?php esc_html_e( 'Vista Rápida', 'franco-theme' ); ?>
			</button>
		</div>
	</div>

	<div class="product-details">
		<span class="product-brand"><?php echo esc_html( $brand ); ?></span>
		<h3 class="product-name">
			<a href="<?php echo esc_url( $product_url ); ?>" class="link-inherit">
				<?php echo esc_html( $product_name ); ?>
			</a>
		</h3>

		<?php if ( ! empty( $item_code ) ) : ?>
			<div class="product-card-item-code">
				<span><?php printf( esc_html__( 'Item N° %s', 'franco-theme' ), esc_html( $item_code ) ); ?></span>
			</div>
		<?php endif; ?>

		<div class="product-price-row">
			<?php if ( $price_old ) : ?>
				<span class="price-old"><?php echo esc_html( $price_old ); ?></span>
			<?php endif; ?>
			<span class="price-current"><?php echo esc_html( $price_current ); ?></span>
		</div>

		<button class="add-cart-btn" onclick="<?php echo $product->is_type( 'variable' ) ? 'openQuickView' : 'francoAddToCart'; ?>(<?php echo absint( $product_id ); ?>)">
			<i class="fa-solid fa-cart-shopping"></i> <?php esc_html_e( 'Agregar al Carrito', 'franco-theme' ); ?>
		</button>
	</div>
</div>
