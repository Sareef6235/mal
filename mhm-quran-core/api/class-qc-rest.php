<?php
if (!defined('ABSPATH')) { exit; }
class QC_REST {
  public static function init(): void { add_action('rest_api_init',[self::class,'routes']); }
  public static function routes(): void {
    register_rest_route('mhm-qc/v1','/surahs',['methods'=>'GET','callback'=>[self::class,'surahs'],'permission_callback'=>'__return_true']);
    register_rest_route('mhm-qc/v1','/surah/(?P<id>\d+)',['methods'=>'GET','callback'=>[self::class,'surah'],'permission_callback'=>'__return_true']);
    register_rest_route('mhm-qc/v1','/audio/(?P<surah_id>\d+)',['methods'=>'GET','callback'=>[self::class,'audio'],'permission_callback'=>'__return_true']);
  }
  public static function surahs(){global $wpdb;return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}quran_surahs ORDER BY id",ARRAY_A);}
  public static function surah(WP_REST_Request $r){global $wpdb;$id=absint($r['id']);return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}quran_ayahs WHERE surah_id=%d ORDER BY ayah_number",$id),ARRAY_A);}  
  public static function audio(WP_REST_Request $r){global $wpdb;$id=absint($r['surah_id']);$rec=absint($r->get_param('reciter_id')?:1);return $wpdb->get_results($wpdb->prepare("SELECT ay.ayah_number,audio.audio_url,audio.duration FROM {$wpdb->prefix}quran_ayahs ay LEFT JOIN {$wpdb->prefix}quran_audio audio ON audio.ayah_id=ay.id AND audio.reciter_id=%d WHERE ay.surah_id=%d ORDER BY ay.ayah_number",$rec,$id),ARRAY_A);}  
}
