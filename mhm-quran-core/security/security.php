<?php
if (!defined('ABSPATH')) { exit; }
class QC_Security {
  public static function init(): void {
    add_filter('rest_authentication_errors',[self::class,'rate_limit']);
  }
  public static function rate_limit($result){
    if (!is_user_logged_in()) {
      $ip=$_SERVER['REMOTE_ADDR']??'0'; $k='mhm_qc_rl_'.md5($ip);
      $hits=(int)get_transient($k);
      if($hits>120){ return new WP_Error('rate_limited','Too many requests',['status'=>429]); }
      set_transient($k,$hits+1,60);
    }
    return $result;
  }
  public static function verify_ajax(): void { check_ajax_referer('mhm_qc_nonce','nonce'); }
}
