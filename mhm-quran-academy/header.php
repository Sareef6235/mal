<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="<?php echo esc_url(get_template_directory_uri() . '/pwa/manifest.json'); ?>">
  <?php wp_head(); ?>
</head>
<body <?php body_class('mhm-theme-dark'); ?>>
<?php wp_body_open(); ?>
<div class="particles" aria-hidden="true"></div>

<aside class="side-menu glass" id="sideMenu" aria-hidden="true">
  <div class="drawer-head">
    <strong>Navigation</strong>
    <button class="btn btn-ripple" data-toggle-menu aria-label="Close menu">✕</button>
  </div>
  <nav class="drawer-nav">
    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
    <a href="<?php echo esc_url(home_url('/quran-reader')); ?>">Quran</a>
    <a href="<?php echo esc_url(home_url('/tajweed-academy')); ?>">Tajweed</a>
    <a href="#" data-modal-open="tafsir">Tafsir</a>
    <a href="<?php echo esc_url(home_url('/audio-library')); ?>">Audio</a>
    <a href="<?php echo esc_url(home_url('/kids-learning')); ?>">Kids Learning</a>
    <a href="<?php echo esc_url(home_url('/islamic-articles')); ?>">Articles</a>
    <a href="<?php echo esc_url(home_url('/teacher-profile')); ?>">Teachers</a>
    <a href="#" data-modal-open="login">Dashboard</a>
    <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a>
  </nav>
</aside>

<header class="site-header layout">
  <div class="floating-nav glass">
    <button class="hamburger btn btn-ripple" data-toggle-menu aria-label="Open menu"><span></span><span></span><span></span></button>
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-mark">☪</span> MHM Quran Academy</a>
    <nav class="top-menu" aria-label="Primary Navigation">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="is-active">Home</a>
      <a href="<?php echo esc_url(home_url('/quran-reader')); ?>">Quran</a>
      <a href="<?php echo esc_url(home_url('/tajweed-academy')); ?>">Tajweed</a>
      <a href="#" data-modal-open="tafsir">Tafsir</a>
      <a href="<?php echo esc_url(home_url('/audio-library')); ?>">Audio</a>
      <a href="<?php echo esc_url(home_url('/kids-learning')); ?>">Kids</a>
      <a href="<?php echo esc_url(home_url('/islamic-articles')); ?>">Articles</a>
      <a href="<?php echo esc_url(home_url('/teacher-profile')); ?>">Teachers</a>
      <a href="#" data-modal-open="login">Dashboard</a>
      <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a>
    </nav>
    <div class="nav-actions">
      <button class="btn btn-premium btn-ripple" data-toggle-theme aria-label="Toggle theme">◐</button>
      <button class="btn btn-premium btn-ripple" data-modal-open="notification">🔔</button>
    </div>
  </div>

  <div class="mega-menu glass" id="megaMenu">
    <div><h4>Quran Hub</h4><a href="<?php echo esc_url(home_url('/quran-reader')); ?>">Reader</a><a href="<?php echo esc_url(home_url('/audio-library')); ?>">Audio</a></div>
    <div><h4>Learning</h4><a href="<?php echo esc_url(home_url('/tajweed-academy')); ?>">Tajweed</a><a href="#" data-modal-open="quiz">Quiz</a></div>
    <div><h4>Community</h4><a href="<?php echo esc_url(home_url('/teacher-profile')); ?>">Teachers</a><a href="<?php echo esc_url(home_url('/islamic-articles')); ?>">Articles</a></div>
  </div>
</header>
