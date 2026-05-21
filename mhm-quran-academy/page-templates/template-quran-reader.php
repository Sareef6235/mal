<?php
/* Template Name: Quran Reader */
get_header();
global $wpdb;
$ayahs = $wpdb->get_results("SELECT a.id,a.surah_id,a.ayah_number,a.arabic_text,t.transliteration,tr.translation FROM {$wpdb->prefix}quran_ayahs a LEFT JOIN {$wpdb->prefix}quran_translations tr ON tr.ayah_id=a.id AND tr.lang='en' LEFT JOIN {$wpdb->prefix}quran_translations t ON t.ayah_id=a.id AND t.lang='translit' WHERE a.surah_id=1 ORDER BY a.ayah_number ASC LIMIT 20");
?>
<main class="layout quran-reader">
  <?php foreach ($ayahs as $ayah) : ?>
    <article class="ayah glass" dir="rtl" data-ayah-id="<?php echo esc_attr($ayah->id); ?>">
      <?php echo esc_html($ayah->arabic_text); ?>
      <div dir="ltr" style="font-size:.9rem;color:var(--muted)"><?php echo esc_html($ayah->transliteration ?? ''); ?></div>
      <p dir="ltr"><?php echo esc_html($ayah->translation ?? ''); ?></p>
      <button class="btn js-play-ayah" data-ayah-id="<?php echo esc_attr($ayah->id); ?>">▶</button>
    </article>
  <?php endforeach; ?>
</main>
<?php get_footer(); ?>
