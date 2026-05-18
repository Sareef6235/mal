<?php
/**
 * Post card/content template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'uesp-card uesp-post-card' ); ?> data-uesp-animate>
	<?php if ( has_post_thumbnail() ) : ?>
		<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?></a>
	<?php endif; ?>
	<div class="uesp-meta"><?php uesp_theme_posted_on(); ?></div>
	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<div class="uesp-entry-summary"><?php the_excerpt(); ?></div>
	<a class="uesp-btn uesp-btn--ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Continue Learning', 'uesp-theme' ); ?></a>
</article>
