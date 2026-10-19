<?php
/**
 * Single Post Template
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

<section class="section">
	<article class="single-post-card">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="single-post-thumb">
				<?php the_post_thumbnail( 'large' ); ?>
			</div>
		<?php endif; ?>

		<span class="single-post-date"><?php echo esc_html( get_the_date() ); ?></span>
		<h1 class="single-post-title">
			<?php the_title(); ?>
		</h1>

		<div class="entry-content single-post-content">
			<?php the_content(); ?>
		</div>
	</article>
</section>

<?php
endwhile;

get_footer();
