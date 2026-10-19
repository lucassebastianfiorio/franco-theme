<?php
/**
 * Main Index Template
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="section">
	<div class="index-content-wrap">
		<h1 class="index-title">
			<?php single_post_title( '', true ); ?>
		</h1>

		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				echo '<article class="index-post-card">';
				echo '<h2 class="index-post-title"><a href="' . esc_url( get_permalink() ) . '" class="link-inherit">' . esc_html( get_the_title() ) . '</a></h2>';
				echo '<div class="index-post-excerpt">';
				the_excerpt();
				echo '</div>';
				echo '</article>';
			}

			the_posts_pagination();
		} else {
			echo '<p class="text-muted">' . esc_html__( 'No se encontraron publicaciones.', 'franco-theme' ) . '</p>';
		}
		?>
	</div>
</section>

<?php
get_footer();
