<?php
/**
 * Plugin Name: UESP - Online Exam System
 * Description: A secure online exam system with timed exams, category filters, AJAX submissions, and result tracking.
 * Version: 1.0.0
 * Author: UESP
 * Text Domain: uesp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'UESP_VERSION', '1.0.0' );
define( 'UESP_FILE', __FILE__ );
define( 'UESP_PATH', plugin_dir_path( __FILE__ ) );
define( 'UESP_URL', plugin_dir_url( __FILE__ ) );

require_once UESP_PATH . 'includes/class-uesp-db.php';
require_once UESP_PATH . 'includes/class-uesp-assets.php';
require_once UESP_PATH . 'includes/class-uesp-ajax.php';
require_once UESP_PATH . 'includes/class-uesp-shortcodes.php';
require_once UESP_PATH . 'includes/class-uesp-admin.php';

final class UESP_Plugin {
    /**
     * Boot the plugin.
     */
    public static function init() {
        UESP_Assets::init();
        UESP_Ajax::init();
        UESP_Shortcodes::init();
        UESP_Admin::init();
    }
}

register_activation_hook( __FILE__, array( 'UESP_DB', 'activate' ) );
add_action( 'plugins_loaded', array( 'UESP_Plugin', 'init' ) );
