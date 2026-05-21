<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="mhm-modal-root" id="mhmModalRoot" aria-live="polite">
  <div class="mhm-modal-overlay" data-modal-close hidden></div>
  <?php $modals = [
    'tajweed' => ['icon' => '۞', 'title' => 'Tajweed Explanation', 'body' => 'Learn ghunnah, madd, ikhfa and qalqala with child-friendly examples.'],
    'tafsir' => ['icon' => '📖', 'title' => 'Tafsir Insight', 'body' => 'Contextual tafsir for deeper understanding of each ayah.'],
    'audio' => ['icon' => '🎧', 'title' => 'Audio Player', 'body' => 'Premium recitation controls with progress and reciter switching.'],
    'reminder' => ['icon' => '🌙', 'title' => 'Daily Islamic Reminder', 'body' => 'Small daily reminders to keep your heart connected to Quran.'],
    'login' => ['icon' => '🔐', 'title' => 'Welcome Back', 'body' => 'Login to sync bookmarks, progress, notes and achievements.'],
    'achievement' => ['icon' => '🏆', 'title' => 'Achievement Unlocked', 'body' => 'MashaAllah! You completed a learning milestone.'],
    'quiz' => ['icon' => '✅', 'title' => 'Quiz Result', 'body' => 'Great work. Review missed tajweed rules and improve fast.'],
    'notification' => ['icon' => '🔔', 'title' => 'Notification', 'body' => 'Important academy updates and teacher guidance.'],
  ]; foreach ($modals as $key => $content) : ?>
    <section class="mhm-modal glass" id="mhm-modal-<?php echo esc_attr($key); ?>" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="mhm-modal-title-<?php echo esc_attr($key); ?>" tabindex="-1" data-modal-panel>
      <div class="mhm-modal-glow" aria-hidden="true"></div>
      <header class="mhm-modal-header">
        <span class="mhm-modal-icon" aria-hidden="true"><?php echo esc_html($content['icon']); ?></span>
        <h3 id="mhm-modal-title-<?php echo esc_attr($key); ?>"><?php echo esc_html__($content['title'], 'mhm-quran-academy'); ?></h3>
        <button class="btn btn-premium btn-ripple mhm-close" type="button" data-modal-close aria-label="Close popup">✕</button>
      </header>
      <div class="mhm-modal-body"><p><?php echo esc_html__($content['body'], 'mhm-quran-academy'); ?></p><div class="mhm-progress"><span style="width:35%"></span></div></div>
      <footer class="mhm-modal-footer"><button class="btn btn-premium btn-ripple" data-modal-action="primary">Continue</button><button class="btn btn-ripple" data-modal-close>Close</button></footer>
    </section>
  <?php endforeach; ?>
</div>
