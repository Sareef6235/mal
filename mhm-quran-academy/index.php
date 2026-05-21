<?php get_header(); ?>
<main class="layout">
  <section class="hero luxury-gradient glass">
    <div class="pattern-layer"></div>
    <h1 class="display-title"><?php esc_html_e('Future of Quran Learning', 'mhm-quran-academy'); ?></h1>
    <p class="subtitle"><?php esc_html_e('Premium app-like Islamic platform with synchronized audio, tajweed intelligence, and child-friendly guided learning.', 'mhm-quran-academy'); ?></p>
    <a class="btn btn-premium btn-ripple" href="<?php echo esc_url(home_url('/quran-reader')); ?>"><?php esc_html_e('Start Reading', 'mhm-quran-academy'); ?></a>
  </section>

  <section class="grid cards">
    <article class="card glass span-4"><h3>Audio Sync Reader</h3><p>Word-synced recitation with smooth progress animations.</p></article>
    <article class="card glass span-4"><h3>Tajweed Academy</h3><p>Color-coded rules with interactive cards for children and adults.</p></article>
    <article class="card glass span-4"><h3>Kids Rewards</h3><p>Gamified streaks, badges and daily motivation.</p></article>
    <article class="card glass span-6"><h3>Interactive Player</h3><div class="progress"><span style="width:62%"></span></div></article>
    <article class="card glass span-6"><h3>Smart Continue</h3><p>Auto-resume your last ayah across devices with premium continuity.</p></article>
  </section>
</main>
<?php get_footer(); ?>
