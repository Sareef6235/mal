<?php
if (!defined('ABSPATH')) { exit; }
class QC_Shortcodes {
  public static function init(): void {
    add_shortcode('quran_reader',[self::class,'reader']); add_shortcode('quran_surah',[self::class,'surah']); add_shortcode('quran_audio',[self::class,'audio']);
    add_shortcode('tajweed_rules',[self::class,'tajweed']); add_shortcode('kids_learning',[self::class,'kids']); add_shortcode('daily_ayah',[self::class,'daily']);
    add_shortcode('quran_search',[self::class,'search']); add_shortcode('user_progress',[self::class,'progress']);
  }
  public static function reader(){return '<div class="qc-reader" data-qc-reader></div>';}
  public static function surah($a){$a=shortcode_atts(['id'=>1],$a);return '<div class="qc-surah" data-surah="'.esc_attr((string)absint($a['id'])).'"></div>';}
  public static function audio(){return '<div class="qc-audio" data-qc-audio><button class="qc-btn js-qc-load-audio">Load Audio</button><div class="qc-audio-list"></div></div>';}
  public static function tajweed(){return '<div class="qc-tajweed">Ghunnah • Madd • Ikhfa • Idgham • Qalqalah</div>';}
  public static function kids(){return '<div class="qc-kids">⭐ Daily Challenge • 🏅 Badges • 📈 Progress</div>';}
  public static function daily(){return '<div class="qc-daily"><button class="qc-btn js-qc-daily">Daily Ayah</button><div class="qc-daily-out"></div></div>';}
  public static function search(){return '<input type="search" class="qc-search" placeholder="Search Quran" data-qc-search />';}
  public static function progress(){return '<div class="qc-progress"><div class="bar"><i style="width:0%"></i></div></div>';}
}
