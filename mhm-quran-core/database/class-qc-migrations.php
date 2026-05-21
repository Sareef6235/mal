<?php
if (!defined('ABSPATH')) { exit; }
class QC_Migrations {
  public static function init(): void { add_action('init',[self::class,'run']); }
  public static function run(): void {
    global $wpdb; $v=get_option('mhm_qc_db_version','0');
    if(version_compare($v,'1.0.1','<')){
      $wpdb->query("ALTER TABLE {$wpdb->prefix}quran_ayahs ADD FULLTEXT KEY ft_arabic(arabic_text)");
      $wpdb->query("ALTER TABLE {$wpdb->prefix}quran_tafsir ADD FULLTEXT KEY ft_tafsir(tafsir_text)");
      update_option('mhm_qc_db_version','1.0.1');
    }
  }
}
