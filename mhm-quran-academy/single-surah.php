<?php
get_header();
global $wpdb;
$surah_id = absint(get_query_var('pagename')) ?: absint(get_query_var('name'));
if ($surah_id < 1 && get_the_ID()) { $surah_id = (int) get_post_meta(get_the_ID(), 'surah_id', true); }
if ($surah_id < 1) { $surah_id = 1; }
$surah = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}quran_surahs WHERE id=%d", $surah_id), ARRAY_A);
$ayahs = $wpdb->get_results($wpdb->prepare("SELECT a.id,a.ayah_number,a.arabic_text,t.transliteration,tr.translation FROM {$wpdb->prefix}quran_ayahs a LEFT JOIN {$wpdb->prefix}quran_translations tr ON tr.ayah_id=a.id AND tr.lang='en' LEFT JOIN {$wpdb->prefix}quran_translations t ON t.ayah_id=a.id AND t.lang='translit' WHERE a.surah_id=%d ORDER BY a.ayah_number", $surah_id), ARRAY_A);
?>
<main class="layout"><section class="glass card"><h1><?php echo esc_html($surah['name_en'] ?? 'Surah'); ?></h1><h2 dir="rtl"><?php echo esc_html($surah['name_ar'] ?? ''); ?></h2>
<?php foreach ($ayahs as $ayah): ?>
  <article class="ayah glass card" dir="rtl" data-ayah-id="<?php echo esc_attr((string) $ayah['id']); ?>">
    <div class="ayah-text"><?php echo esc_html($ayah['arabic_text']); ?></div>
    <div class="ayah-meta" dir="ltr"><?php echo esc_html($ayah['transliteration'] ?? ''); ?></div>
    <p class="ayah-meta" dir="ltr"><?php echo esc_html($ayah['translation'] ?? ''); ?></p>
    <div class="audio-player"><button class="btn btn-premium btn-ripple js-play-ayah" data-ayah-id="<?php echo esc_attr((string) $ayah['id']); ?>">Play</button><button class="btn btn-ripple js-bookmark-ayah" data-ayah-id="<?php echo esc_attr((string) $ayah['id']); ?>">Bookmark</button></div>
  </article>
<?php endforeach; ?></section></main>
<?php get_footer(); ?>
