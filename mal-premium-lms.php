<?php
/**
 * Plugin Name: MAL Premium LMS
 * Plugin URI:  https://example.com/mal-premium-lms
 * Description: Production-ready LMS, monthly planning, attendance, exams, certificates, receipts, PWA, and REST dashboard for Admin, Ustad, and Student roles.
 * Version:     1.0.0
 * Author:      OpenAI Codex
 * Author URI:  https://openai.com
 * Text Domain: mal-premium-lms
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAL_LMS_VERSION', '1.0.0' );
define( 'MAL_LMS_FILE', __FILE__ );
define( 'MAL_LMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MAL_LMS_URL', plugin_dir_url( __FILE__ ) );
define( 'MAL_LMS_BASENAME', plugin_basename( __FILE__ ) );

require_once MAL_LMS_PATH . 'includes/class-mal-lms-activator.php';
require_once MAL_LMS_PATH . 'includes/class-mal-lms.php';

register_activation_hook( __FILE__, array( 'MAL_LMS_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MAL_LMS_Activator', 'deactivate' ) );

/**
 * Boot the plugin after all plugins have loaded.
 */
function mal_lms_bootstrap() {
	$plugin = new MAL_LMS();
	$plugin->run();
}
add_action( 'plugins_loaded', 'mal_lms_bootstrap' );
