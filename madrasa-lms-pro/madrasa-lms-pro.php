<?php
/**
 * Plugin Name: Madrasa
 * Plugin URI: https://example.com/madrasa-lms-pro
 * Description: Islamic Madrasa / School Management + LMS + Attendance + Monthly Planning System.
 * Version: 1.0.0
 * Author: Madrasa LMS Pro
 * Text Domain: madrasa-lms-pro
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 *
 * @package Madrasa_LMS_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MLP_VERSION', '1.0.0' );
define( 'MLP_FILE', __FILE__ );
define( 'MLP_PATH', plugin_dir_path( __FILE__ ) );
define( 'MLP_URL', plugin_dir_url( __FILE__ ) );
define( 'MLP_BASENAME', plugin_basename( __FILE__ ) );

require_once MLP_PATH . 'includes/class-loader.php';

register_activation_hook( __FILE__, array( 'MLP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MLP_Deactivator', 'deactivate' ) );

/**
 * Boots the plugin after all dependencies are available.
 */
function mlp_bootstrap(): void {
	$loader = new MLP_Loader();
	$loader->run();
}
add_action( 'plugins_loaded', 'mlp_bootstrap' );
