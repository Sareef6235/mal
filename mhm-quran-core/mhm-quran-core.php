<?php
/**
 * Plugin Name: MHM Quran Core
 * Description: Production Quran learning core (DB, API, AJAX, shortcodes, audio, tajweed, kids, security).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Text Domain: mhm-quran-core
 */
if (!defined('ABSPATH')) { exit; }

define('MHM_QC_PATH', plugin_dir_path(__FILE__));
define('MHM_QC_URL', plugin_dir_url(__FILE__));

require_once MHM_QC_PATH . 'security/security.php';
require_once MHM_QC_PATH . 'database/class-qc-db.php';
require_once MHM_QC_PATH . 'database/class-qc-migrations.php';
require_once MHM_QC_PATH . 'database/class-qc-seeder.php';
require_once MHM_QC_PATH . 'api/class-qc-rest.php';
require_once MHM_QC_PATH . 'includes/class-qc-ajax.php';
require_once MHM_QC_PATH . 'shortcodes/class-qc-shortcodes.php';
require_once MHM_QC_PATH . 'admin/class-qc-admin.php';

register_activation_hook(__FILE__, ['QC_DB','install']);
register_activation_hook(__FILE__, ['QC_Seeder','seed_surahs']);

add_action('plugins_loaded', function(){
  QC_Security::init();
  QC_Migrations::init();
  QC_REST::init();
  QC_AJAX::init();
  QC_Shortcodes::init();
  QC_Admin::init();
});

add_action('wp_enqueue_scripts', function(){
  wp_enqueue_style('mhm-qc', MHM_QC_URL.'assets/css/core.css', [], '1.0.0');
  wp_enqueue_script('mhm-qc', MHM_QC_URL.'assets/js/core.js', ['jquery'], '1.0.0', true);
  wp_localize_script('mhm-qc','mhmQC',['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('mhm_qc_nonce'),'rest'=>rest_url('mhm-qc/v1/')]);
});
