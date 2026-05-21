<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_submenu_page('mhm-quran', 'Tajweed Rules DB', 'Tajweed Rules DB', 'manage_options', 'mhm-tajweed-rules-db', 'mhm_tajweed_rules_db_page');
});

function mhm_tajweed_rules_db_page(): void {
    global $wpdb;
    if (isset($_POST['mhm_tajweed_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mhm_tajweed_nonce'])), 'mhm_tajweed_save')) {
        $wpdb->insert($wpdb->prefix . 'quran_tajweed_rules', [
            'rule_key' => sanitize_key($_POST['rule_key'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'color_hex' => sanitize_hex_color($_POST['color_hex'] ?? '#19c997') ?: '#19c997',
            'difficulty' => sanitize_text_field($_POST['difficulty'] ?? 'beginner'),
            'kids_tip' => sanitize_text_field($_POST['kids_tip'] ?? ''),
            'pronunciation_guide' => sanitize_textarea_field($_POST['pronunciation_guide'] ?? ''),
            'audio_url' => esc_url_raw($_POST['audio_url'] ?? ''),
            'sort_order' => absint($_POST['sort_order'] ?? 0),
        ]);
        echo '<div class="updated"><p>Rule saved.</p></div>';
    }
    $rules = $wpdb->get_results("SELECT id,rule_key,title,difficulty,color_hex FROM {$wpdb->prefix}quran_tajweed_rules ORDER BY sort_order ASC, id ASC", ARRAY_A);
    ?>
    <div class="wrap"><h1>Tajweed Rules Database</h1>
      <form method="post">
        <?php wp_nonce_field('mhm_tajweed_save', 'mhm_tajweed_nonce'); ?>
        <table class="form-table"><tbody>
          <tr><th>Rule Key</th><td><input name="rule_key" required/></td></tr>
          <tr><th>Title</th><td><input name="title" required/></td></tr>
          <tr><th>Description</th><td><textarea name="description" required></textarea></td></tr>
          <tr><th>Color</th><td><input name="color_hex" value="#19c997"/></td></tr>
          <tr><th>Difficulty</th><td><select name="difficulty"><option>beginner</option><option>intermediate</option><option>advanced</option></select></td></tr>
          <tr><th>Kids Tip</th><td><input name="kids_tip"/></td></tr>
          <tr><th>Pronunciation Guide</th><td><textarea name="pronunciation_guide"></textarea></td></tr>
          <tr><th>Audio URL</th><td><input name="audio_url" type="url"/></td></tr>
          <tr><th>Sort Order</th><td><input name="sort_order" type="number" value="0"/></td></tr>
        </tbody></table>
        <p><button class="button button-primary">Save Rule</button></p>
      </form>
      <h2>Existing Rules</h2>
      <table class="widefat"><thead><tr><th>ID</th><th>Key</th><th>Title</th><th>Difficulty</th><th>Color</th></tr></thead><tbody>
      <?php foreach ($rules as $r): ?><tr><td><?php echo esc_html((string) $r['id']); ?></td><td><?php echo esc_html($r['rule_key']); ?></td><td><?php echo esc_html($r['title']); ?></td><td><?php echo esc_html($r['difficulty']); ?></td><td><span style="background:<?php echo esc_attr($r['color_hex']); ?>;padding:2px 8px;border-radius:6px;display:inline-block"></span> <?php echo esc_html($r['color_hex']); ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <?php
}
