<?php
if (!defined('ABSPATH')) { exit; }

class MHM_QLP_AJAX {
    public static function init(): void {
        add_action('wp_ajax_mhm_qlp_daily_ayah', [self::class, 'daily_ayah']);
        add_action('wp_ajax_nopriv_mhm_qlp_daily_ayah', [self::class, 'daily_ayah']);
    }

    public static function daily_ayah(): void {
        check_ajax_referer('mhm_qlp_nonce', 'nonce');
        global $wpdb;
        $row = $wpdb->get_row("SELECT a.arabic_text,a.translation,s.name_en,s.id AS surah_id,a.ayah_number FROM {$wpdb->prefix}qlp_ayahs a JOIN {$wpdb->prefix}qlp_surahs s ON s.id=a.surah_id ORDER BY RAND() LIMIT 1", ARRAY_A);
        wp_send_json_success($row);
    }
}
