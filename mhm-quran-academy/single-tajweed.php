<?php
get_header();
global $wpdb;
$rule_id = absint(get_query_var('tajweed_rule') ?: ($_GET['tajweed_rule'] ?? 0));
if ($rule_id < 1) { $rule_id = 1; }
$rule = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}quran_tajweed_rules WHERE id=%d", $rule_id), ARRAY_A);
$examples = $wpdb->get_results($wpdb->prepare("SELECT ayah_id,sample_text,explanation,start_pos,end_pos FROM {$wpdb->prefix}quran_tajweed_examples WHERE rule_id=%d ORDER BY id ASC", $rule_id), ARRAY_A);
?>
<main class="layout">
  <section class="glass card">
    <h1><?php echo esc_html($rule['title'] ?? 'Tajweed Rule'); ?></h1>
    <p><?php echo esc_html($rule['description'] ?? ''); ?></p>
    <div class="kids-pill">👧 Kids Tip: <?php echo esc_html($rule['kids_tip'] ?? 'Read slowly and smile while learning!'); ?></div>
    <div class="tajweed-pronounce"><?php echo esc_html($rule['pronunciation_guide'] ?? ''); ?></div>
    <?php if (!empty($rule['audio_url'])) : ?><audio controls preload="none" src="<?php echo esc_url($rule['audio_url']); ?>"></audio><?php endif; ?>
  </section>

  <section class="grid cards">
    <?php foreach ($examples as $ex) :
      $text = $ex['sample_text'] ?? '';
      $start = max(0, (int) $ex['start_pos']);
      $end = max($start, (int) $ex['end_pos']);
      $len = mb_strlen($text);
      if ($start > $len) { $start = 0; }
      if ($end > $len) { $end = $len; }
      $pre = mb_substr($text, 0, $start);
      $hit = mb_substr($text, $start, $end - $start);
      $post = mb_substr($text, $end);
      ?>
      <article class="card glass span-6">
        <h3><?php esc_html_e('Color-coded Example', 'mhm-quran-academy'); ?></h3>
        <p class="ayah" dir="rtl"><?php echo esc_html($pre); ?><span class="tajweed-hit" data-tooltip="<?php echo esc_attr($ex['explanation']); ?>"><?php echo esc_html($hit); ?></span><?php echo esc_html($post); ?></p>
        <p><?php echo esc_html($ex['explanation']); ?></p>
      </article>
    <?php endforeach; ?>
  </section>
</main>
<?php get_footer(); ?>
