<?php
/**
 * 404 template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>
<section class="uesp-hero">
	<div class="uesp-container uesp-card" style="text-align:center">
		<p class="uesp-kicker"><?php esc_html_e( '404', 'uesp-theme' ); ?></p>
		<h1 class="uesp-entry-title"><?php esc_html_e( 'This learning path is unavailable', 'uesp-theme' ); ?></h1>
		<p><?php esc_html_e( 'The page may have moved, expired, or requires a different membership level.', 'uesp-theme' ); ?></p>
		<div class="uesp-hero-actions" style="justify-content:center"><a class="uesp-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to Home', 'uesp-theme' ); ?></a><a class="uesp-btn uesp-btn--ghost" href="<?php echo esc_url( home_url( '/exams/' ) ); ?>"><?php esc_html_e( 'Browse Exams', 'uesp-theme' ); ?></a></div>
	</div>
</section>
<?php get_footer(); ?>
