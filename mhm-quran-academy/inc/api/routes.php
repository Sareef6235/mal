<?php
if (!defined('ABSPATH')) { exit; }

add_action('rest_api_init', function () {
  register_rest_route('mhm/v1', '/continue/(?P<user_id>\d+)', [
    'methods' => 'GET',
    'callback' => function ($request) {
      global $wpdb;
      $user = absint($request['user_id']);
      return $wpdb->get_row($wpdb->prepare("SELECT surah_id,ayah_number FROM {$wpdb->prefix}quran_progress WHERE user_id=%d ORDER BY updated_at DESC LIMIT 1", $user));
    },
    'permission_callback' => '__return_true'
  ]);
});
