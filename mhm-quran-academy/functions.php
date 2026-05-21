<?php
if (!defined('ABSPATH')) { exit; }

define('MHM_QA_VER', '1.0.0');
define('MHM_QA_PATH', get_template_directory());
define('MHM_QA_URL', get_template_directory_uri());

require_once MHM_QA_PATH . '/inc/db/schema.php';
require_once MHM_QA_PATH . '/inc/ajax/handlers.php';
require_once MHM_QA_PATH . '/inc/api/routes.php';
require_once MHM_QA_PATH . '/inc/admin/dashboard.php';

add_action('wp_footer', function () {
    get_template_part('template-parts/premium-popups');
}, 25);

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption']);
    add_theme_support('custom-logo');
    add_theme_support('woocommerce');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_theme_support('automatic-feed-links');
    register_nav_menus([
        'primary' => __('Primary Menu', 'mhm-quran-academy'),
        'mobile'  => __('Mobile Bottom Nav', 'mhm-quran-academy')
    ]);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('mhm-style', get_stylesheet_uri(), [], MHM_QA_VER);
    wp_enqueue_style('mhm-main', MHM_QA_URL . '/assets/css/main.css', ['mhm-style'], MHM_QA_VER);
    wp_enqueue_script('gsap', 'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js', [], null, true);
    wp_enqueue_script('lottie', 'https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js', [], null, true);
    wp_enqueue_script('mhm-main', MHM_QA_URL . '/assets/js/main.js', ['jquery', 'gsap', 'lottie'], MHM_QA_VER, true);
    wp_enqueue_script('mhm-audio-engine', MHM_QA_URL . '/assets/js/audio-engine.js', [], MHM_QA_VER, true);
    wp_enqueue_script('mhm-mobile', MHM_QA_URL . '/assets/js/mobile.js', [], MHM_QA_VER, true);
    wp_enqueue_style('mhm-tajweed-style', MHM_QA_URL . '/tajweed-style.css', ['mhm-main'], MHM_QA_VER);
    wp_localize_script('mhm-main', 'mhmQA', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('mhm_qa_nonce'),
      'audioRest' => esc_url_raw(rest_url('mhm-audio/v1/'))
    ]);
});

add_action('init', function () {
    register_post_type('tajweed_lesson', [
      'label' => __('Tajweed Lessons', 'mhm-quran-academy'),
      'public' => true,
      'show_in_rest' => true,
      'supports' => ['title', 'editor', 'thumbnail', 'excerpt']
    ]);
});

require_once MHM_QA_PATH . '/quran-functions.php';
require_once MHM_QA_PATH . '/quran-api.php';
require_once MHM_QA_PATH . '/quran-shortcodes.php';
require_once MHM_QA_PATH . '/tajweed-shortcodes.php';
require_once MHM_QA_PATH . '/tajweed-admin.php';
require_once MHM_QA_PATH . '/audio-api.php';
require_once MHM_QA_PATH . '/audio-shortcodes.php';
require_once MHM_QA_PATH . '/inc/admin/admin-menu.php';
require_once MHM_QA_PATH . '/inc/admin/admin-dashboard.php';
require_once MHM_QA_PATH . '/inc/admin/admin-settings.php';
require_once MHM_QA_PATH . '/inc/admin/analytics.php';