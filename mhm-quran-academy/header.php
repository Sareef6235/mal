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
<header class="site-header glass layout">
  <div class="nav-grid">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">MHM Quran Academy</a>
    <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false]); ?>
  </div>
</header>
