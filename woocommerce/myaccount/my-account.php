<?php
/**
 * My Account page layout
 *
 * @package FrancoTheme
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
?>

<div class="franco-account-wrapper">
	<div class="container">
		<div class="franco-account-grid">
			<aside class="franco-account-sidebar">
				<?php
				/**
				 * My Account navigation.
				 *
				 * @since 2.6.0
				 */
				do_action( 'woocommerce_account_navigation' );
				?>
			</aside>

			<main class="franco-account-main">
				<div class="woocommerce-MyAccount-content franco-account-content-card">
					<?php
					/**
					 * My Account content.
					 *
					 * @since 2.6.0
					 */
					do_action( 'woocommerce_account_content' );
					?>
				</div>
			</main>
		</div>
	</div>
</div>
