<?php
/**
 * Search results template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>
<section class="uesp-section">
	<div class="uesp-container">
		<div class="uesp-card" style="margin-bottom:24px"><h1 class="uesp-entry-title"><?php printf( esc_html__( 'Search results for: %s', 'uesp-theme' ), esc_html( get_search_query() ) ); ?></h1><?php get_search_form(); ?></div>
		<?php if ( have_posts() ) : ?>
			<div class="uesp-grid uesp-grid-3"><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/blog/content' ); endwhile; ?></div>
			<div class="uesp-pagination"><?php the_posts_pagination(); ?></div>
		<?php else : get_template_part( 'template-parts/blog/content', 'none' ); endif; ?>
	</div>
</section>
<?php get_footer(); ?>
