<footer class="site-footer layout glass" style="margin:1rem auto 7rem;padding:1rem;border-radius:16px;">
  <p>&copy; <?php echo esc_html(date('Y')); ?> MHM Quran Academy</p>
</footer>
<button class="fab" aria-label="Quick play" data-modal-open="audio">✦</button>
<nav class="bottom-nav glass" aria-label="Mobile Navigation">
  <a href="<?php echo esc_url(home_url('/')); ?>" class="is-active">🏠<span>Home</span></a>
  <a href="<?php echo esc_url(home_url('/quran-reader')); ?>">📖<span>Quran</span></a>
  <a href="<?php echo esc_url(home_url('/tajweed-academy')); ?>">✨<span>Tajweed</span></a>
  <a href="<?php echo esc_url(home_url('/audio-library')); ?>">🎧<span>Audio</span></a>
  <a href="#" data-modal-open="login">👤<span>Dashboard</span></a>
</nav>
<?php wp_footer(); ?>
</body></html>
