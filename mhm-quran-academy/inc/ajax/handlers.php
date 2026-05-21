<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_ajax_mhm_get_ayah_audio', 'mhm_get_ayah_audio');
add_action('wp_ajax_nopriv_mhm_get_ayah_audio', 'mhm_get_ayah_audio');
function mhm_get_ayah_audio() {
  check_ajax_referer('mhm_qa_nonce', 'nonce');
  global $wpdb;
  $ayah_id = absint($_POST['ayah_id'] ?? 0);
  $reciter = sanitize_text_field($_POST['reciter'] ?? 'Alafasy');
  $row = $wpdb->get_row($wpdb->prepare("SELECT audio_url,duration,waveform FROM {$wpdb->prefix}quran_audio WHERE ayah_id=%d AND reciter=%s", $ayah_id, $reciter));
  wp_send_json_success($row);
}

add_action('wp_ajax_mhm_bookmark_ayah', 'mhm_bookmark_ayah');
function mhm_bookmark_ayah() {
  check_ajax_referer('mhm_qa_nonce', 'nonce');
  if (!is_user_logged_in()) { wp_send_json_error(['message' => 'Login required'], 401); }
  global $wpdb;
  $ayah_id = absint($_POST['ayah_id'] ?? 0);
  $wpdb->insert($wpdb->prefix . 'quran_bookmarks', ['user_id' => get_current_user_id(), 'ayah_id' => $ayah_id], ['%d', '%d']);
  wp_send_json_success(['bookmarked' => true]);
}

add_action('wp_ajax_mhm_tajweed_quiz', 'mhm_tajweed_quiz');
add_action('wp_ajax_nopriv_mhm_tajweed_quiz', 'mhm_tajweed_quiz');
function mhm_tajweed_quiz() {
  check_ajax_referer('mhm_qa_nonce', 'nonce');
  global $wpdb;
  $count = max(1, min(20, absint($_POST['count'] ?? 5)));
  $rows = $wpdb->get_results($wpdb->prepare("SELECT id,question,options,correct_index,rule_key FROM {$wpdb->prefix}quran_tajweed_quiz ORDER BY RAND() LIMIT %d", $count), ARRAY_A);
  foreach ($rows as &$r) { $r['options'] = json_decode((string) $r['options'], true) ?: []; }
  wp_send_json_success($rows);
}

add_action('wp_ajax_mhm_get_surah_playlist', 'mhm_get_surah_playlist');
add_action('wp_ajax_nopriv_mhm_get_surah_playlist', 'mhm_get_surah_playlist');
function mhm_get_surah_playlist() {
  check_ajax_referer('mhm_qa_nonce', 'nonce');
  global $wpdb;
  $surah_id = absint($_POST['surah_id'] ?? 1);
  $reciter = sanitize_text_field($_POST['reciter'] ?? 'Alafasy');
  $rows = $wpdb->get_results($wpdb->prepare("SELECT a.id AS ayah_id,a.ayah_number,q.audio_url,q.duration,q.reciter FROM {$wpdb->prefix}quran_ayahs a LEFT JOIN {$wpdb->prefix}quran_audio q ON q.ayah_id=a.id AND q.reciter=%s WHERE a.surah_id=%d ORDER BY a.ayah_number", $reciter, $surah_id), ARRAY_A);
  wp_send_json_success($rows);
}


add_action('wp_ajax_mhm_admin_activity', 'mhm_admin_activity');
function mhm_admin_activity() {
  check_ajax_referer('mhm_admin_nonce', 'nonce');
  global $wpdb;
  $rows = $wpdb->get_results("SELECT user_id,ayah_id,created_at FROM {$wpdb->prefix}quran_bookmarks ORDER BY id DESC LIMIT 8", ARRAY_A);
  $out = [];
  foreach ($rows as $r) { $out[] = sprintf('User %d bookmarked Ayah %d at %s', (int)$r['user_id'], (int)$r['ayah_id'], esc_html((string)$r['created_at'])); }
  wp_send_json_success($out);
}

add_action('wp_ajax_mhm_admin_audio_upload', 'mhm_admin_audio_upload');
function mhm_admin_audio_upload() {
  check_ajax_referer('mhm_admin_nonce', 'nonce');
  if (!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Unauthorized'],403); }
  if (empty($_FILES['audio_files'])) { wp_send_json_error(['message'=>'No files'],400); }
  require_once ABSPATH . 'wp-admin/includes/file.php';
  $uploaded = 0;
  foreach ($_FILES['audio_files']['name'] as $i => $name) {
    $file = [
      'name' => $_FILES['audio_files']['name'][$i],
      'type' => $_FILES['audio_files']['type'][$i],
      'tmp_name' => $_FILES['audio_files']['tmp_name'][$i],
      'error' => $_FILES['audio_files']['error'][$i],
      'size' => $_FILES['audio_files']['size'][$i],
    ];
    $move = wp_handle_upload($file, ['test_form' => false]);
    if (!isset($move['error'])) { $uploaded++; }
  }
  wp_send_json_success(['uploaded' => $uploaded]);
}
