<?php
if (!defined('ABSPATH')) { exit; }

add_action('rest_api_init', function (): void {
    register_rest_route('mhm-audio/v1', '/surah/(?P<surah_id>\d+)', [
        'methods' => 'GET',
        'callback' => function(WP_REST_Request $r){
            global $wpdb;
            $sid = absint($r['surah_id']);
            $reciter = sanitize_text_field((string)($r->get_param('reciter') ?: 'Alafasy'));
            return $wpdb->get_results($wpdb->prepare("SELECT a.id AS ayah_id,a.ayah_number,q.audio_url,q.duration,q.reciter FROM {$wpdb->prefix}quran_ayahs a LEFT JOIN {$wpdb->prefix}quran_audio q ON q.ayah_id=a.id AND q.reciter=%s WHERE a.surah_id=%d ORDER BY a.ayah_number", $reciter, $sid), ARRAY_A);
        },
        'permission_callback' => '__return_true'
    ]);
});
