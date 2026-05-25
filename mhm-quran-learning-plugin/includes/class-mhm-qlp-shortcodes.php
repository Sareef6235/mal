<?php
if (!defined('ABSPATH')) { exit; }

class MHM_QLP_Shortcodes {
    public static function init(): void {
        $map = [
            'mhm_quran_reader' => 'quran_reader', 'mhm_surah' => 'surah', 'mhm_ayah' => 'ayah', 'mhm_audio_player' => 'audio',
            'mhm_tajweed_lessons' => 'tajweed', 'mhm_tafsir_popup' => 'tafsir', 'mhm_daily_ayah' => 'daily',
            'mhm_islamic_quiz' => 'quiz', 'mhm_user_progress' => 'progress', 'mhm_kids_learning' => 'kids'
        ];
        foreach ($map as $tag => $fn) { add_shortcode($tag, [self::class, 'render_' . $fn]); }
    }
    private static function box(string $c): string { return '<div class="mhm-qlp-box" dir="rtl">' . $c . '</div>'; }

    public static function render_quran_reader($a): string { return self::box('<div class="mhm-qlp-reader" data-shortcode="reader"></div>'); }
    public static function render_surah($atts): string {
        $a = shortcode_atts(['id' => 1], $atts, 'mhm_surah');
        return self::box('<div class="mhm-qlp-surah" data-surah-id="' . esc_attr((string) absint($a['id'])) . '"></div>');
    }
    public static function render_ayah($atts): string {
        $a = shortcode_atts(['surah' => 1, 'ayah' => 1], $atts, 'mhm_ayah');
        return self::box('<div class="mhm-qlp-ayah" data-surah="' . esc_attr((string) absint($a['surah'])) . '" data-ayah="' . esc_attr((string) absint($a['ayah'])) . '"></div>');
    }
    public static function render_audio($atts): string { return self::box('<div class="mhm-qlp-audio" data-reciter="alafasy">🎧 Audio Player</div>'); }
    public static function render_tajweed($atts): string { return self::box('<div class="mhm-qlp-tajweed">✨ Tajweed Lessons</div>'); }
    public static function render_tafsir($atts): string { return self::box('<button class="mhm-qlp-btn" data-modal-open="tafsir">Open Tafsir Popup</button>'); }
    public static function render_daily($atts): string { return self::box('<div class="mhm-qlp-daily"><button class="mhm-qlp-btn js-mhm-daily">Load Daily Ayah</button><div class="js-mhm-daily-output"></div></div>'); }
    public static function render_quiz($atts): string { return self::box('<div class="mhm-qlp-quiz">🧠 Islamic Quiz</div>'); }
    public static function render_progress($atts): string { return self::box('<div class="mhm-qlp-progress"><span>Progress</span><div class="bar"><i style="width:45%"></i></div></div>'); }
    public static function render_kids($atts): string { return self::box('<div class="mhm-qlp-kids">🌈 Kids Learning Zone</div>'); }
}
