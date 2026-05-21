<?php
/* Template Name: Audio Player */
get_header();
global $wpdb;
$surahs = $wpdb->get_results("SELECT id,name_en,name_ar,total_ayahs FROM {$wpdb->prefix}quran_surahs ORDER BY id", ARRAY_A);
$reciters = $wpdb->get_col("SELECT DISTINCT reciter FROM {$wpdb->prefix}quran_audio ORDER BY reciter ASC") ?: ['Alafasy'];
?>
<main class="layout">
  <section class="glass card">
    <h1>Quran Audio Studio</h1>
    <div class="audio-controls-row">
      <select id="mhm-surah-select"><?php foreach($surahs as $s): ?><option value="<?php echo esc_attr((string)$s['id']); ?>"><?php echo esc_html($s['name_en'].' ('.$s['name_ar'].')'); ?></option><?php endforeach; ?></select>
      <select id="mhm-reciter-select"><?php foreach($reciters as $r): ?><option value="<?php echo esc_attr($r); ?>"><?php echo esc_html($r); ?></option><?php endforeach; ?></select>
      <label>Speed <input id="mhm-speed" type="range" min="0.5" max="1.5" step="0.25" value="1"></label>
    </div>
    <div id="mhm-audio-playlist" class="grid cards"></div>
  </section>
  <aside id="mhm-mini-player" class="glass card mini-player" hidden>
    <strong id="mhm-mini-title">Now Playing</strong>
    <div class="waveform"><span></span><span></span><span></span><span></span><span></span></div>
    <div class="progress"><span id="mhm-mini-progress"></span></div>
    <button class="btn btn-premium btn-ripple" id="mhm-mini-toggle">Pause</button>
  </aside>
</main>
<?php get_footer(); ?>
