<?php
/**
 * Generic Page Template
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
?>

<?php if ( ! ( function_exists( 'franco_is_checkout_page' ) && franco_is_checkout_page() ) && ! ( function_exists( 'is_checkout' ) && is_checkout() ) ) : ?>
<section class="page-banner">
	<div class="page-banner-content">
		<?php franco_render_breadcrumbs(); ?>
		<h1 class="page-banner-title">
			<?php the_title(); ?>
		</h1>
	</div>
</section>
<?php endif; ?>

<?php if ( function_exists( 'franco_is_account_page' ) && franco_is_account_page() ) : ?>
	<div class="entry-content account-page-entry">
		<?php the_content(); ?>
	</div>
<?php else : ?>
<section class="section">
	<div class="page-content-card">
		<div class="entry-content page-content-body">
			<?php the_content(); ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
endwhile;

get_footer();
