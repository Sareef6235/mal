<?php
if (!defined('ABSPATH')) { exit; }

function mhm_tajweed_lessons_shortcode($atts): string {
    global $wpdb;
    $a = shortcode_atts(['level' => 'all'], $atts, 'mhm_tajweed_lessons');
    $sql = "SELECT id, title, description, color_hex, difficulty, kids_tip FROM {$wpdb->prefix}quran_tajweed_rules";
    $params = [];
    if ($a['level'] !== 'all') {
        $sql .= ' WHERE difficulty=%s';
        $params[] = sanitize_text_field((string) $a['level']);
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $rows = empty($params) ? $wpdb->get_results($sql, ARRAY_A) : $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);

    ob_start();
    echo '<div class="grid cards tajweed-shortcode-grid">';
    foreach ($rows as $row) {
        echo '<article class="card glass span-4" style="border-top:4px solid ' . esc_attr($row['color_hex']) . '">';
        echo '<h3>' . esc_html($row['title']) . '</h3><p>' . esc_html($row['description']) . '</p>';
        echo '<div class="kids-pill">' . esc_html($row['kids_tip']) . '</div>';
        echo '<a class="btn btn-premium btn-ripple" href="' . esc_url(add_query_arg(['tajweed_rule' => (int) $row['id']], home_url('/single-tajweed'))) . '">Learn</a>';
        echo '</article>';
    }
    echo '</div>';
    return (string) ob_get_clean();
}
add_shortcode('mhm_tajweed_lessons', 'mhm_tajweed_lessons_shortcode');

function mhm_tajweed_quiz_shortcode($atts): string {
    $a = shortcode_atts(['count' => 5], $atts, 'mhm_tajweed_quiz');
    $count = max(1, min(20, absint($a['count'])));
    return '<div class="glass card" data-tajweed-quiz data-count="' . esc_attr((string) $count) . '"><h3>Tajweed Quiz</h3><div class="quiz-body"></div><button class="btn btn-premium btn-ripple js-tajweed-next">Start Quiz</button></div>';
}
add_shortcode('mhm_tajweed_quiz', 'mhm_tajweed_quiz_shortcode');
