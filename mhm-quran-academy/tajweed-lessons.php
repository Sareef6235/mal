<?php
/* Template Name: Tajweed Lessons */
get_header();
?>
<main class="layout">
  <section class="glass card">
    <h1><?php esc_html_e('Tajweed Lessons', 'mhm-quran-academy'); ?></h1>
    <?php echo do_shortcode('[mhm_tajweed_lessons level="all"]'); ?>
    <?php echo do_shortcode('[mhm_tajweed_quiz count="5"]'); ?>
  </section>
</main>
<?php get_footer(); ?>
