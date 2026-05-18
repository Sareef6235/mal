<?php
/**
 * Sidebar template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<aside class="uesp-sidebar" aria-label="<?php esc_attr_e( 'Sidebar', 'uesp-theme' ); ?>">
	<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
	<?php else : ?>
		<section class="widget">
			<h2><?php esc_html_e( 'Exam Insights', 'uesp-theme' ); ?></h2>
			<p><?php esc_html_e( 'Track rank, progress, upcoming exams, and performance analytics from one premium dashboard.', 'uesp-theme' ); ?></p>
		</section>
	<?php endif; ?>
</aside>
