<?php
/**
 * Search form template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="uesp-form search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="uesp-field">
		<span><?php esc_html_e( 'Search exams, books, lessons', 'uesp-theme' ); ?></span>
		<input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Type keywords...', 'uesp-theme' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
	</label>
	<button class="uesp-btn" type="submit"><?php esc_html_e( 'Search', 'uesp-theme' ); ?></button>
</form>
