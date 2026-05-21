<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="<?php echo esc_url(get_template_directory_uri() . '/pwa/manifest.json'); ?>">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="particles" aria-hidden="true"></div>
<aside class="side-menu glass" id="sideMenu" aria-hidden="true">
  <h3><?php esc_html_e('Academy Menu', 'mhm-quran-academy'); ?></h3>
  <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false]); ?>
</aside>
<header class="site-header layout">
  <div class="floating-nav glass">
    <button class="btn btn-premium btn-ripple" data-toggle-menu>☰</button>
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-mark">م</span> MHM Quran Academy</a>
    <button class="btn btn-premium btn-ripple" data-open-modal><?php esc_html_e('Assistant', 'mhm-quran-academy'); ?></button>
  </div>
</header>
<div class="modal" id="premiumModal"><div class="modal-card glass"><h3>AI Quran Assistant</h3><p>Ask tajweed, tafsir and memorization questions instantly.</p><button class="btn btn-premium btn-ripple" data-close-modal>Close</button></div></div>
