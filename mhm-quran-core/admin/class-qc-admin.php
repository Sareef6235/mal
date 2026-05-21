<?php
if (!defined('ABSPATH')) { exit; }
class QC_Admin {
  public static function init(): void { add_action('admin_menu',[self::class,'menu']); }
  public static function menu(): void { add_menu_page('MHM Quran Core','MHM Quran Core','manage_options','mhm-qc-admin',[self::class,'page'],'dashicons-book',3); }
  public static function page(): void {
    global $wpdb; $s=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quran_surahs"); $a=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quran_ayahs");
    echo '<div class="wrap"><h1>MHM Quran Core</h1><p>Surahs: '.esc_html((string)$s).' | Ayahs: '.esc_html((string)$a).'</p></div>';
  }
}
