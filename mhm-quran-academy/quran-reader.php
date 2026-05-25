<?php
/* Template Name: Quran Reader Full */
get_header();
global $wpdb;
$surahs = $wpdb->get_results("SELECT id,name_ar,name_en,total_ayahs FROM {$wpdb->prefix}quran_surahs ORDER BY id", ARRAY_A);
?>
<main class="layout">
  <section class="glass card"><h1><?php esc_html_e('Quran Reader', 'mhm-quran-academy'); ?></h1>
    <input type="search" id="mhm-quran-search" placeholder="Search Surah" aria-label="Search Surah" />
    <div class="grid cards" id="mhm-surah-archive">
      <?php foreach ($surahs as $s): ?>
        <a class="card glass span-4" href="<?php echo esc_url(home_url('/surah/' . $s['id'])); ?>"><strong><?php echo esc_html($s['name_en']); ?></strong><div dir="rtl"><?php echo esc_html($s['name_ar']); ?></div><small><?php echo esc_html((string) $s['total_ayahs']); ?> ayahs</small></a>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php get_footer(); ?>
