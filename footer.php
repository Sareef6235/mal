<?php
/**
 * Footer template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="uesp-site-footer">
	<div class="uesp-container">
		<div class="uesp-footer-grid">
			<div>
				<div class="uesp-brand"><span class="uesp-brand-mark">UE</span><span><?php bloginfo( 'name' ); ?></span></div>
				<p><?php esc_html_e( 'Enterprise-grade LMS, exam, quiz, membership, and analytics WordPress theme for modern learning teams.', 'uesp-theme' ); ?></p>
			</div>
			<div><h2 class="uesp-footer-title"><?php esc_html_e( 'Platform', 'uesp-theme' ); ?></h2><ul class="uesp-footer-links"><li><a href="<?php echo esc_url( home_url( '/exams/' ) ); ?>"><?php esc_html_e( 'Exams', 'uesp-theme' ); ?></a></li><li><a href="<?php echo esc_url( home_url( '/books/' ) ); ?>"><?php esc_html_e( 'Books', 'uesp-theme' ); ?></a></li><li><a href="<?php echo esc_url( home_url( '/leaderboard/' ) ); ?>"><?php esc_html_e( 'Leaderboard', 'uesp-theme' ); ?></a></li></ul></div>
			<div><h2 class="uesp-footer-title"><?php esc_html_e( 'Company', 'uesp-theme' ); ?></h2><?php dynamic_sidebar( 'footer-2' ); ?></div>
			<div><h2 class="uesp-footer-title"><?php esc_html_e( 'Support', 'uesp-theme' ); ?></h2><?php dynamic_sidebar( 'footer-3' ); ?></div>
		</div>
		<p class="uesp-copyright">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'uesp-theme' ); ?></p>
	</div>
</footer>
<?php get_template_part( 'template-parts/sections/modals' ); ?>
<a class="uesp-fab" href="<?php echo esc_url( home_url( '/exams/' ) ); ?>" aria-label="<?php esc_attr_e( 'Start exam', 'uesp-theme' ); ?>">＋</a>
<div class="uesp-toast-stack" aria-live="polite" aria-atomic="true"></div>
<?php wp_footer(); ?>
</body>
</html>
