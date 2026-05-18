<?php
/**
 * Main fallback template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( is_home() && ! is_paged() ) {
	get_template_part( 'template-parts/sections/hero' );
	get_template_part( 'template-parts/sections/dashboard' );
	get_template_part( 'template-parts/sections/exam-ui' );
	get_template_part( 'template-parts/sections/results' );
}
?>
<section class="uesp-section">
	<div class="uesp-container uesp-grid uesp-grid-3">
		<div style="grid-column: span 2;">
			<?php if ( have_posts() ) : ?>
				<div class="uesp-grid uesp-grid-2">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php get_template_part( 'template-parts/blog/content' ); ?>
					<?php endwhile; ?>
				</div>
				<div class="uesp-pagination"><?php the_posts_pagination(); ?></div>
			<?php else : ?>
				<?php get_template_part( 'template-parts/blog/content', 'none' ); ?>
			<?php endif; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</section>
<?php get_footer(); ?>
