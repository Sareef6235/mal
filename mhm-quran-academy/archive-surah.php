<?php get_header(); ?>
<main class="layout">
  <section class="glass card">
    <h1><?php esc_html_e('Surah Archive', 'mhm-quran-academy'); ?></h1>
    <?php echo do_shortcode('[mhm_quran_reader]'); ?>
  </section>
</main>
<?php get_footer(); ?>
