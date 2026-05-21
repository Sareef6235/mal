<?php get_header(); ?>
<main class="layout">
  <section class="hero glass">
    <h1><?php esc_html_e('Billion-dollar Quran Learning Experience', 'mhm-quran-academy'); ?></h1>
    <p><?php esc_html_e('AI-powered recitation, tajweed mastery, and kids gamified Islamic education.', 'mhm-quran-academy'); ?></p>
    <a class="btn" href="<?php echo esc_url(home_url('/quran-reader')); ?>"><?php esc_html_e('Continue Reading', 'mhm-quran-academy'); ?></a>
  </section>
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <article class="glass" style="padding:1rem;margin:1rem 0;"><?php the_content(); ?></article>
  <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
