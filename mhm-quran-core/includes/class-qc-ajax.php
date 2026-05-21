<?php
if (!defined('ABSPATH')) { exit; }
class QC_AJAX {
  public static function init(): void {
    add_action('wp_ajax_mhm_qc_bookmark',[self::class,'bookmark']);
    add_action('wp_ajax_mhm_qc_progress',[self::class,'progress']);
    add_action('wp_ajax_nopriv_mhm_qc_daily',[self::class,'daily']);
    add_action('wp_ajax_mhm_qc_daily',[self::class,'daily']);
  }
  public static function bookmark(): void { QC_Security::verify_ajax(); if(!is_user_logged_in()) wp_send_json_error([],401); global $wpdb; $ayah=absint($_POST['ayah_id']??0); $wpdb->insert($wpdb->prefix.'quran_bookmarks',['user_id'=>get_current_user_id(),'ayah_id'=>$ayah]); wp_send_json_success(['ok'=>1]); }
  public static function progress(): void { QC_Security::verify_ajax(); if(!is_user_logged_in()) wp_send_json_error([],401); global $wpdb; $wpdb->replace($wpdb->prefix.'quran_progress',['user_id'=>get_current_user_id(),'surah_id'=>absint($_POST['surah_id']??1),'ayah_number'=>absint($_POST['ayah']??1),'completion_pct'=>floatval($_POST['pct']??0)]); wp_send_json_success(['saved'=>1]); }
  public static function daily(): void { QC_Security::verify_ajax(); global $wpdb; $r=$wpdb->get_row("SELECT s.name_en,a.ayah_number,a.arabic_text FROM {$wpdb->prefix}quran_ayahs a JOIN {$wpdb->prefix}quran_surahs s ON s.id=a.surah_id ORDER BY RAND() LIMIT 1",ARRAY_A); wp_send_json_success($r); }
}
