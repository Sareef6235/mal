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
