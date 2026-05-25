<?php
if (!defined('ABSPATH')) { exit; }

function mhm_quran_shortcode_reader($atts): string {
    $a = shortcode_atts(['surah' => 1], $atts, 'mhm_quran_reader');
    return '<div class="mhm-quran-reader" data-surah-id="' . esc_attr((string) absint($a['surah'])) . '"></div>';
}
add_shortcode('mhm_quran_reader', 'mhm_quran_shortcode_reader');

function mhm_quran_shortcode_surah($atts): string {
    $a = shortcode_atts(['id' => 1], $atts, 'mhm_surah');
    return '<div class="mhm-surah-view" data-surah-id="' . esc_attr((string) absint($a['id'])) . '"></div>';
}
add_shortcode('mhm_surah', 'mhm_quran_shortcode_surah');
