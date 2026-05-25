<?php
if (!defined('ABSPATH')) { exit; }

class MHM_QLP_API {
    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {
        register_rest_route('mhm-qlp/v1', '/surahs', ['methods' => 'GET', 'callback' => [self::class, 'surahs'], 'permission_callback' => '__return_true']);
        register_rest_route('mhm-qlp/v1', '/ayahs/(?P<surah_id>\d+)', ['methods' => 'GET', 'callback' => [self::class, 'ayahs'], 'permission_callback' => '__return_true']);
    }

    public static function surahs(): array {
        global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}qlp_surahs ORDER BY id ASC", ARRAY_A) ?: [];
    }
    public static function ayahs(WP_REST_Request $r): array {
        global $wpdb; $surah = absint($r['surah_id']);
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}qlp_ayahs WHERE surah_id=%d ORDER BY ayah_number", $surah), ARRAY_A) ?: [];
    }
}
