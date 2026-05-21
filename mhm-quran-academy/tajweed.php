<?php
/* Template Name: Tajweed */
get_header();
global $wpdb;
$rules = $wpdb->get_results("SELECT id,rule_key,title,description,color_hex,difficulty,audio_url FROM {$wpdb->prefix}quran_tajweed_rules ORDER BY sort_order ASC, id ASC", ARRAY_A);
?>
<main class="layout">
  <section class="hero glass luxury-gradient">
    <h1 class="display-title"><?php esc_html_e('Tajweed Learning Studio', 'mhm-quran-academy'); ?></h1>
    <p class="subtitle"><?php esc_html_e('Interactive, kids-friendly tajweed system with colors, tooltips, pronunciation and quizzes.', 'mhm-quran-academy'); ?></p>
  </section>

  <section class="grid cards">
    <?php foreach ($rules as $rule) : ?>
      <article class="card glass span-4 tajweed-lesson-card" style="--rule-color:<?php echo esc_attr($rule['color_hex']); ?>">
        <h3><?php echo esc_html($rule['title']); ?></h3>
        <p><?php echo esc_html($rule['description']); ?></p>
        <small><?php echo esc_html(ucfirst((string) $rule['difficulty'])); ?></small>
        <div class="audio-player" dir="ltr">
          <?php if (!empty($rule['audio_url'])) : ?>
            <audio controls preload="none" src="<?php echo esc_url($rule['audio_url']); ?>"></audio>
          <?php endif; ?>
        </div>
        <a class="btn btn-premium btn-ripple" href="<?php echo esc_url(add_query_arg(['tajweed_rule' => (int) $rule['id']], home_url('/single-tajweed'))); ?>"><?php esc_html_e('Start Lesson', 'mhm-quran-academy'); ?></a>
      </article>
    <?php endforeach; ?>
  </section>
</main>
<?php get_footer(); ?>
