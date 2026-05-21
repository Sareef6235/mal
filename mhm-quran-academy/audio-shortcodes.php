<?php
if (!defined('ABSPATH')) { exit; }

function mhm_audio_player_shortcode($atts): string {
    $a = shortcode_atts(['surah' => 1, 'reciter' => 'Alafasy'], $atts, 'mhm_audio_player');
    return '<div class="glass card mhm-audio-shortcode" data-audio-shortcode data-surah="' . esc_attr((string)absint($a['surah'])) . '" data-reciter="' . esc_attr((string)$a['reciter']) . '"></div>';
}
add_shortcode('mhm_audio_player', 'mhm_audio_player_shortcode');
