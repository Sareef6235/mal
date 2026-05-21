<?php
if (!defined('ABSPATH')) { exit; }

add_action('rest_api_init', function (): void {
    register_rest_route('mhm-quran/v1', '/surahs', [
        'methods' => 'GET',
        'callback' => function () {
            global $wpdb;
            return $wpdb->get_results("SELECT id,name_ar,name_en,total_ayahs,revelation_type FROM {$wpdb->prefix}quran_surahs ORDER BY id", ARRAY_A);
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('mhm-quran/v1', '/surah/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            global $wpdb;
            $id = absint($request['id']);
            return [
                'surah' => $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}quran_surahs WHERE id=%d", $id), ARRAY_A),
                'ayahs' => $wpdb->get_results($wpdb->prepare("SELECT id,ayah_number,arabic_text FROM {$wpdb->prefix}quran_ayahs WHERE surah_id=%d ORDER BY ayah_number", $id), ARRAY_A),
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});
