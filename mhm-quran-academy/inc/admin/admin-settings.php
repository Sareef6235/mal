<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_init', function () {
    register_setting('mhm_admin_settings_group', 'mhm_admin_settings', ['type' => 'array', 'sanitize_callback' => 'mhm_admin_settings_sanitize', 'default' => []]);
});

function mhm_admin_settings_sanitize($input): array {
    return [
        'admin_theme' => in_array(($input['admin_theme'] ?? 'dark'), ['dark','light'], true) ? $input['admin_theme'] : 'dark',
        'default_reciter' => sanitize_text_field($input['default_reciter'] ?? 'Alafasy'),
        'notify_email' => sanitize_email($input['notify_email'] ?? get_option('admin_email')),
    ];
}

function mhm_admin_settings_page(): void {
    $opts = get_option('mhm_admin_settings', []);
    ?>
    <div class="wrap mhm-admin-wrap"><h1>Theme Customization</h1>
      <form method="post" action="options.php" class="mhm-admin-card">
        <?php settings_fields('mhm_admin_settings_group'); ?>
        <table class="form-table"><tbody>
          <tr><th>Admin Theme</th><td><select name="mhm_admin_settings[admin_theme]"><option value="dark" <?php selected($opts['admin_theme'] ?? 'dark','dark'); ?>>Dark</option><option value="light" <?php selected($opts['admin_theme'] ?? 'dark','light'); ?>>Light</option></select></td></tr>
          <tr><th>Default Reciter</th><td><input name="mhm_admin_settings[default_reciter]" value="<?php echo esc_attr($opts['default_reciter'] ?? 'Alafasy'); ?>" class="regular-text" /></td></tr>
          <tr><th>Notification Email</th><td><input name="mhm_admin_settings[notify_email]" value="<?php echo esc_attr($opts['notify_email'] ?? get_option('admin_email')); ?>" class="regular-text" type="email" /></td></tr>
        </tbody></table>
        <?php submit_button('Save Settings'); ?>
      </form>
    </div>
    <?php
}
