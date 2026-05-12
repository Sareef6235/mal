<?php
/**
 * Settings page.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mal-lms-settings">
	<h1><?php esc_html_e( 'MAL LMS Settings', 'mal-premium-lms' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'mal_lms_settings' ); ?>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="mal-currency"><?php esc_html_e( 'Currency', 'mal-premium-lms' ); ?></label></th><td><input id="mal-currency" name="mal_lms_settings[currency]" value="<?php echo esc_attr( $settings['currency'] ?? 'USD' ); ?>" /></td></tr>
			<tr><th scope="row"><label for="mal-certificate-title"><?php esc_html_e( 'Certificate Title', 'mal-premium-lms' ); ?></label></th><td><input id="mal-certificate-title" name="mal_lms_settings[certificate_title]" value="<?php echo esc_attr( $settings['certificate_title'] ?? get_bloginfo( 'name' ) ); ?>" class="regular-text" /></td></tr>
			<tr><th scope="row"><label for="mal-theme"><?php esc_html_e( 'Default Theme', 'mal-premium-lms' ); ?></label></th><td><select id="mal-theme" name="mal_lms_settings[theme]"><option value="dark" <?php selected( $settings['theme'] ?? 'dark', 'dark' ); ?>><?php esc_html_e( 'Dark', 'mal-premium-lms' ); ?></option><option value="light" <?php selected( $settings['theme'] ?? '', 'light' ); ?>><?php esc_html_e( 'Light', 'mal-premium-lms' ); ?></option><option value="neon" <?php selected( $settings['theme'] ?? '', 'neon' ); ?>><?php esc_html_e( 'Neon', 'mal-premium-lms' ); ?></option></select></td></tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
