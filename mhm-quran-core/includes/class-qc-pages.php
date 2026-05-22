<?php
if (!defined('ABSPATH')) { exit; }
class QC_Pages {
  public static function init(): void { add_action('template_redirect',[self::class,'route']); }
  public static function route(): void {
    $map=[
      'quran-reader'=>'templates/frontend/quran-reader.php',
      'quran-search'=>'templates/frontend/quran-search.php',
      'archive-surah'=>'templates/frontend/archive-surah.php',
      'single-surah'=>'templates/frontend/single-surah.php',
      'audio-player'=>'audio/audio-player.php',
      'reciter-switcher'=>'audio/reciter-switcher.php',
      'tajweed-home'=>'tajweed/tajweed-home.php',
      'tajweed-rules'=>'tajweed/tajweed-rules.php',
      'tajweed-lessons'=>'tajweed/tajweed-lessons.php',
      'tajweed-quiz'=>'tajweed/tajweed-quiz.php',
      'kids-dashboard'=>'kids/kids-dashboard.php',
      'kids-progress'=>'kids/kids-progress.php',
      'memorization'=>'kids/memorization.php',
      'rewards'=>'kids/rewards.php',
      'quiz-engine'=>'quiz/quiz-engine.php',
      'quiz-results'=>'quiz/quiz-results.php'
    ];
    $slug = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
    if (isset($map[$slug])) { include MHM_QC_PATH . $map[$slug]; exit; }
  }
}
