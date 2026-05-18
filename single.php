<?php
/**
 * Single content template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>
<section class="uesp-section">
	<div class="uesp-container uesp-grid uesp-grid-3">
		<div style="grid-column: span 2;">
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'uesp-card' ); ?>>
				<div class="uesp-meta"><?php uesp_theme_posted_on(); ?></div>
				<h1 class="uesp-entry-title"><?php the_title(); ?></h1>
				<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); endif; ?>
				<div class="uesp-entry-content"><?php the_content(); ?></div>
				<?php wp_link_pages( array( 'before' => '<div class="uesp-pagination">', 'after' => '</div>' ) ); ?>
			</article>
			<?php get_template_part( 'template-parts/sections/exam-ui' ); ?>
			<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>
		<?php endwhile; ?>
		</div>
		<?php get_sidebar(); ?>
	</div>
</section>
<?php get_footer(); ?>
