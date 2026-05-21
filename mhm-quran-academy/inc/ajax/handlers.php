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
