<?php
if (!defined('ABSPATH')) { exit; }

function mhm_quran_seed_core_data(): void {
    global $wpdb;
    $rec = $wpdb->prefix . 'quran_reciters';
    if ((int)$wpdb->get_var("SELECT COUNT(*) FROM {$rec}") === 0) {
      $wpdb->insert($rec, ['slug'=>'alafasy','name'=>'Mishary Alafasy','language'=>'ar','bitrate'=>128]);
      $wpdb->insert($rec, ['slug'=>'sudais','name'=>'Abdur Rahman As-Sudais','language'=>'ar','bitrate'=>128]);
    }
    $quiz = $wpdb->prefix . 'quran_tajweed_quiz';
    if ((int)$wpdb->get_var("SELECT COUNT(*) FROM {$quiz}") === 0) {
      $wpdb->insert($quiz,['question'=>'Which rule includes nasal sound?','options'=>wp_json_encode(['Ghunnah','Qalqalah','Madd','Ikhfa']),'correct_index'=>0,'rule_key'=>'ghunnah','difficulty'=>'beginner']);
      $wpdb->insert($quiz,['question'=>'Which rule is echo/bounce sound?','options'=>wp_json_encode(['Ikhfa','Qalqalah','Idgham','Madd']),'correct_index'=>1,'rule_key'=>'qalqalah','difficulty'=>'beginner']);
    }
}
add_action('after_switch_theme', 'mhm_quran_seed_core_data', 30);
