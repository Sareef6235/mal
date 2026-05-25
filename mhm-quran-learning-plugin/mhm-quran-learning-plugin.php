<?php
/**
 * Plugin Name: MHM Quran Learning Platform
 * Description: Complete Quran learning plugin with Surahs, Ayahs, audio, tajweed, tafsir, progress, quizzes, REST API and shortcodes.
 * Version: 1.0.0
 * Requires PHP: 8.0
 * Text Domain: mhm-quran-learning
 */
if (!defined('ABSPATH')) { exit; }

define('MHM_QLP_PATH', plugin_dir_path(__FILE__));
define('MHM_QLP_URL', plugin_dir_url(__FILE__));

require_once MHM_QLP_PATH . 'includes/class-mhm-qlp-db.php';
require_once MHM_QLP_PATH . 'includes/class-mhm-qlp-api.php';
require_once MHM_QLP_PATH . 'includes/class-mhm-qlp-ajax.php';
require_once MHM_QLP_PATH . 'includes/class-mhm-qlp-shortcodes.php';

register_activation_hook(__FILE__, ['MHM_QLP_DB', 'activate']);

add_action('plugins_loaded', function () {
    MHM_QLP_API::init();
    MHM_QLP_AJAX::init();
    MHM_QLP_Shortcodes::init();
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('mhm-qlp', MHM_QLP_URL . 'assets/css/shortcodes.css', [], '1.0.0');
    wp_enqueue_script('mhm-qlp', MHM_QLP_URL . 'assets/js/shortcodes.js', ['jquery'], '1.0.0', true);
    wp_localize_script('mhm-qlp', 'mhmQlp', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('mhm_qlp_nonce'),
        'rest'    => esc_url_raw(rest_url('mhm-qlp/v1/')),
    ]);
});
