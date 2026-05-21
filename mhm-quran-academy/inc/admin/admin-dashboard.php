<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos((string)$hook, 'mhm-') === false) { return; }
    wp_enqueue_style('mhm-admin-css', get_template_directory_uri() . '/inc/admin/assets/css/admin.css', [], '1.0.0');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', [], null, true);
    wp_enqueue_script('mhm-admin-js', get_template_directory_uri() . '/inc/admin/assets/js/admin.js', ['jquery','chart-js'], '1.0.0', true);
    wp_localize_script('mhm-admin-js', 'mhmAdmin', ['ajaxUrl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mhm_admin_nonce')]);
});

function mhm_admin_dashboard_page(): void {
    global $wpdb;
    $stats = [
      'surahs' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quran_surahs"),
      'ayahs' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quran_ayahs"),
      'audio' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quran_audio"),
      'quizzes' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quran_tajweed_quiz"),
      'bookmarks' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quran_bookmarks"),
      'users' => (int)count_users()['total_users'],
    ];
    ?>
    <div class="wrap mhm-admin-wrap"><h1>Quran Platform Dashboard</h1>
      <div class="mhm-stats-grid"><?php foreach ($stats as $k=>$v): ?><div class="mhm-stat-card"><h3><?php echo esc_html(ucfirst($k)); ?></h3><p><?php echo esc_html((string)$v); ?></p></div><?php endforeach; ?></div>
      <div class="mhm-admin-card"><h2>Activity Logs</h2><div id="mhm-activity-log">Loading...</div></div>
      <div class="mhm-admin-card"><h2>Drag & Drop Audio Upload</h2><div id="mhm-dropzone">Drop audio files here or click to upload<input id="mhm-audio-file" type="file" multiple accept="audio/*" hidden></div></div>
    </div>
    <?php
}

function mhm_admin_surah_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Surah Management</h1><p>Manage Surahs from database records and sync metadata.</p></div>'; }
function mhm_admin_ayah_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Ayah Management</h1><p>Manage ayah text, translations and transliteration.</p></div>'; }
function mhm_admin_audio_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Audio Management</h1><p>Manage reciters and per-ayah files.</p></div>'; }
function mhm_admin_tajweed_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Tajweed Lessons</h1><p>Manage tajweed rules and examples.</p></div>'; }
function mhm_admin_quiz_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Quiz Management</h1><p>Manage quiz items and levels.</p></div>'; }
function mhm_admin_progress_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Student Progress</h1><p>Track streaks and completion percentages.</p></div>'; }
function mhm_admin_users_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>User Management</h1><p>Navigate to WordPress Users for full user controls.</p></div>'; }
function mhm_admin_notify_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Notifications</h1><p>Configure platform notifications and reminders.</p></div>'; }
function mhm_admin_reports_page(): void { echo '<div class="wrap mhm-admin-wrap"><h1>Reports</h1><p>Export learning and audio usage reports.</p></div>'; }
