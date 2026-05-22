<?php
if (!defined('ABSPATH')) { exit; }
class QC_Audio_API {
  public static function init(): void {
    add_action('rest_api_init', function(){
      register_rest_route('mhm-qc/v1','/reciters',['methods'=>'GET','callback'=>[self::class,'reciters'],'permission_callback'=>'__return_true']);
    });
  }
  public static function reciters(){global $wpdb;return $wpdb->get_results("SELECT id,name,slug,base_url FROM {$wpdb->prefix}quran_reciters ORDER BY id",ARRAY_A);}  
}
