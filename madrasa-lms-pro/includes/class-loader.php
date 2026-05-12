<?php
/**
 * Plugin loader.
 *
 * @package Madrasa_LMS_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mlp_files = array(
	'includes/helpers.php',
	'includes/class-utils.php',
	'includes/class-security.php',
	'includes/class-db.php',
	'includes/class-roles.php',
	'includes/class-activator.php',
	'includes/class-deactivator.php',
	'includes/class-auth.php',
	'includes/class-rest-api.php',
	'includes/class-pwa.php',
	'includes/class-certificates.php',
	'admin/class-admin-menu.php',
	'admin/class-dashboard.php',
	'admin/class-students.php',
	'admin/class-teachers.php',
	'admin/class-courses.php',
	'admin/class-attendance.php',
	'admin/class-exams.php',
	'admin/class-results.php',
	'admin/class-settings.php',
	'public/class-shortcodes.php',
	'public/class-frontend.php',
);

foreach ( $mlp_files as $mlp_file ) {
	require_once MLP_PATH . $mlp_file;
}

/**
 * Registers all hooks for the plugin.
 */
final class MLP_Loader {
	/** Run plugin services. */
	public function run(): void {
		load_plugin_textdomain( 'madrasa-lms-pro', false, dirname( MLP_BASENAME ) . '/languages' );
		MLP_Roles::register_caps();
		( new MLP_Admin_Menu() )->hooks();
		( new MLP_Rest_API() )->hooks();
		( new MLP_PWA() )->hooks();
		( new MLP_Shortcodes() )->hooks();
		( new MLP_Frontend() )->hooks();
		( new MLP_Auth() )->hooks();
	}
}
