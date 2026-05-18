<?php
/**
 * Page template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>
<section class="uesp-section">
	<div class="uesp-container">
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'uesp-card' ); ?>>
				<h1 class="uesp-entry-title"><?php the_title(); ?></h1>
				<div class="uesp-entry-content"><?php the_content(); ?></div>
			</article>
			<?php if ( is_page( 'dashboard' ) ) : get_template_part( 'template-parts/sections/dashboard' ); endif; ?>
			<?php if ( is_page( 'results' ) ) : get_template_part( 'template-parts/sections/results' ); endif; ?>
			<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>
		<?php endwhile; ?>
	</div>
</section>
<?php get_footer(); ?>
