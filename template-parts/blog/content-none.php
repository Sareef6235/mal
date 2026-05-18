<?php
/**
 * Empty state template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="uesp-card">
	<h1><?php esc_html_e( 'No content found', 'uesp-theme' ); ?></h1>
	<p><?php esc_html_e( 'Try another search, explore exams, or publish your first learning resource.', 'uesp-theme' ); ?></p>
	<?php get_search_form(); ?>
</section>
