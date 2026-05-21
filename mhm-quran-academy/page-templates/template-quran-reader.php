<?php
/* Template Name: Quran Reader */
get_header();
global $wpdb;
$ayahs = $wpdb->get_results("SELECT a.id,a.surah_id,a.ayah_number,a.arabic_text,t.transliteration,tr.translation FROM {$wpdb->prefix}quran_ayahs a LEFT JOIN {$wpdb->prefix}quran_translations tr ON tr.ayah_id=a.id AND tr.lang='en' LEFT JOIN {$wpdb->prefix}quran_translations t ON t.ayah_id=a.id AND t.lang='translit' WHERE a.surah_id=1 ORDER BY a.ayah_number ASC LIMIT 20");
?>
<main class="layout">
  <section class="hero glass luxury-gradient" dir="rtl"><h1 class="display-title" style="font-family:'Noto Naskh Arabic',serif;">القرآن الكريم</h1><p class="subtitle" dir="ltr">Elegant Arabic typography, synchronized audio, and immersive tajweed learning.</p></section>
  <section class="quran-reader">
    <?php if (empty($ayahs)) : for($i=0;$i<4;$i++): ?><div class="skeleton"></div><?php endfor; endif; ?>
    <?php foreach ($ayahs as $ayah) : ?>
      <article class="ayah glass card" dir="rtl" data-ayah-id="<?php echo esc_attr($ayah->id); ?>">
        <div><?php echo esc_html($ayah->arabic_text); ?></div>
        <div class="ayah-meta" dir="ltr"><?php echo esc_html($ayah->transliteration ?? ''); ?></div>
        <p class="ayah-meta" dir="ltr"><?php echo esc_html($ayah->translation ?? ''); ?></p>
        <div class="audio-player" dir="ltr"><button class="btn btn-premium btn-ripple js-play-ayah" data-ayah-id="<?php echo esc_attr($ayah->id); ?>">Play Ayah</button><div class="progress"><span></span></div></div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
<?php get_footer(); ?>
