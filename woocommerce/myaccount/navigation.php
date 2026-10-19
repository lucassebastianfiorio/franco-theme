<?php
/**
 * My Account navigation
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$user_name    = trim( $current_user->first_name . ' ' . $current_user->last_name );
if ( empty( $user_name ) ) {
	$user_name = $current_user->display_name ?: $current_user->user_login;
}

$first_letter = strtoupper( mb_substr( $user_name, 0, 1, 'UTF-8' ) );

$endpoint_icons = array(
	'dashboard'       => 'fas fa-th-large',
	'orders'          => 'fas fa-box-open',
	'downloads'       => 'fas fa-download',
	'edit-address'    => 'fas fa-map-marker-alt',
	'edit-account'    => 'fas fa-user-cog',
	'customer-logout' => 'fas fa-sign-out-alt',
);

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation franco-account-nav-card" aria-label="<?php esc_attr_e( 'Navegación de Mi Cuenta', 'franco' ); ?>">
	<div class="franco-account-profile-header">
		<div class="franco-account-avatar" aria-hidden="true">
			<?php echo esc_html( $first_letter ); ?>
		</div>
		<div class="franco-account-profile-info">
			<span class="franco-account-greeting"><?php esc_html_e( 'Hola,', 'franco' ); ?></span>
			<h3 class="franco-account-name"><?php echo esc_html( $user_name ); ?></h3>
			<span class="franco-account-email"><?php echo esc_html( $current_user->user_email ); ?></span>
		</div>
	</div>

	<ul class="franco-account-nav-list">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) :
			$icon_class   = isset( $endpoint_icons[ $endpoint ] ) ? $endpoint_icons[ $endpoint ] : 'fas fa-chevron-right';
			$item_classes = wc_get_account_menu_item_classes( $endpoint );
			$is_active    = ( false !== strpos( $item_classes, 'is-active' ) );
			$is_logout    = ( 'customer-logout' === $endpoint );
			?>
			<li class="<?php echo esc_attr( $item_classes ); ?><?php echo $is_logout ? ' is-logout' : ''; ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" class="franco-account-nav-link<?php echo $is_active ? ' is-active' : ''; ?>">
					<i class="<?php echo esc_attr( $icon_class ); ?> nav-icon" aria-hidden="true"></i>
					<span class="nav-label"><?php echo esc_html( $label ); ?></span>
					<i class="fas fa-chevron-right nav-arrow" aria-hidden="true"></i>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
