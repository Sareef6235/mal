<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_menu_page('Quran Platform', 'Quran Platform', 'manage_options', 'mhm-admin-dashboard', 'mhm_admin_dashboard_page', 'dashicons-chart-area', 2);
    add_submenu_page('mhm-admin-dashboard', 'Surah Management', 'Surahs', 'manage_options', 'mhm-surah-mgmt', 'mhm_admin_surah_page');
    add_submenu_page('mhm-admin-dashboard', 'Ayah Management', 'Ayahs', 'manage_options', 'mhm-ayah-mgmt', 'mhm_admin_ayah_page');
    add_submenu_page('mhm-admin-dashboard', 'Audio Management', 'Audio', 'manage_options', 'mhm-audio-mgmt', 'mhm_admin_audio_page');
    add_submenu_page('mhm-admin-dashboard', 'Tajweed Lessons', 'Tajweed', 'manage_options', 'mhm-tajweed-mgmt', 'mhm_admin_tajweed_page');
    add_submenu_page('mhm-admin-dashboard', 'Quiz Management', 'Quizzes', 'manage_options', 'mhm-quiz-mgmt', 'mhm_admin_quiz_page');
    add_submenu_page('mhm-admin-dashboard', 'Student Progress', 'Progress', 'manage_options', 'mhm-progress-mgmt', 'mhm_admin_progress_page');
    add_submenu_page('mhm-admin-dashboard', 'Analytics', 'Analytics', 'manage_options', 'mhm-admin-analytics', 'mhm_admin_analytics_page');
    add_submenu_page('mhm-admin-dashboard', 'Theme Customization', 'Theme', 'manage_options', 'mhm-admin-settings', 'mhm_admin_settings_page');
    add_submenu_page('mhm-admin-dashboard', 'User Management', 'Users', 'list_users', 'mhm-user-mgmt', 'mhm_admin_users_page');
    add_submenu_page('mhm-admin-dashboard', 'Notifications', 'Notifications', 'manage_options', 'mhm-admin-notify', 'mhm_admin_notify_page');
    add_submenu_page('mhm-admin-dashboard', 'Reports', 'Reports', 'manage_options', 'mhm-admin-reports', 'mhm_admin_reports_page');
});
