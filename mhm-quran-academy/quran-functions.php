<?php
if (!defined('ABSPATH')) { exit; }

function mhm_quran_register_surah_cpt(): void {
    register_post_type('surah', [
        'labels' => ['name' => __('Surahs', 'mhm-quran-academy'), 'singular_name' => __('Surah', 'mhm-quran-academy')],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'surah'],
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'excerpt'],
        'menu_icon' => 'dashicons-book-alt',
    ]);
}
add_action('init', 'mhm_quran_register_surah_cpt');

function mhm_quran_activate_seed_surahs(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'quran_surahs';
    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    if ($count > 0) { return; }
    $ayahs=[7,286,200,176,120,165,206,75,129,109,123,111,43,52,99,128,111,110,98,135,112,78,118,64,77,227,93,88,69,60,34,30,73,54,45,83,182,88,75,85,54,53,89,59,37,35,38,29,18,45,60,49,62,55,78,96,29,22,24,13,14,11,11,18,12,12,30,52,52,44,28,28,20,56,40,31,50,40,46,42,29,19,36,25,22,17,19,26,30,20,15,21,11,8,8,19,5,8,8,11,11,8,3,9,5,4,7,3,6,3,5,4,5,6];
    foreach ($ayahs as $i => $total) {
        $id = $i + 1;
        $wpdb->insert($table, ['id'=>$id,'name_ar'=>'سورة '.$id,'name_en'=>'Surah '.$id,'total_ayahs'=>$total,'revelation_type'=>'Meccan'], ['%d','%s','%s','%d','%s']);
    }
}
add_action('after_switch_theme', 'mhm_quran_activate_seed_surahs', 20);

function mhm_quran_get_continue_reading(int $user_id): ?array {
    if ($user_id < 1) { return null; }
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT surah_id,ayah_number FROM {$wpdb->prefix}quran_progress WHERE user_id=%d ORDER BY updated_at DESC LIMIT 1", $user_id), ARRAY_A);
    return $row ?: null;
}
