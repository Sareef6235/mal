<?php
if (!defined('ABSPATH')) { exit; }
class QC_Seeder {
  public static function seed_surahs(): void {
    global $wpdb; $t=$wpdb->prefix.'quran_surahs';
    if((int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}")>0)return;
    $a=[7,286,200,176,120,165,206,75,129,109,123,111,43,52,99,128,111,110,98,135,112,78,118,64,77,227,93,88,69,60,34,30,73,54,45,83,182,88,75,85,54,53,89,59,37,35,38,29,18,45,60,49,62,55,78,96,29,22,24,13,14,11,11,18,12,12,30,52,52,44,28,28,20,56,40,31,50,40,46,42,29,19,36,25,22,17,19,26,30,20,15,21,11,8,8,19,5,8,8,11,11,8,3,9,5,4,7,3,6,3,5,4,5,6];
    foreach($a as $i=>$n){$id=$i+1;$wpdb->insert($t,['id'=>$id,'name_ar'=>'سورة '.$id,'name_en'=>'Surah '.$id,'total_ayahs'=>$n,'revelation_type'=>'Meccan']);}
    $r=$wpdb->prefix.'quran_reciters';
    $wpdb->insert($r,['slug'=>'alafasy','name'=>'Mishary Rashid Alafasy','base_url'=>'https://everyayah.com/data/Alafasy_128kbps/','language'=>'ar','bitrate'=>128]);
    $wpdb->insert($r,['slug'=>'mishary','name'=>'Mishary Rashid','base_url'=>'https://everyayah.com/data/Alafasy_64kbps/','language'=>'ar','bitrate'=>64]);
  }
}
