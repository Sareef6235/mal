(() => {
  let startX = 0, startY = 0;
  const sideMenu = document.getElementById('sideMenu');
  document.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; startY = e.touches[0].clientY; }, { passive: true });
  document.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - startX;
    const dy = Math.abs(e.changedTouches[0].clientY - startY);
    if (dy > 80) return;
    if (dx > 70 && sideMenu && !sideMenu.classList.contains('is-open')) sideMenu.classList.add('is-open');
    if (dx < -70 && sideMenu && sideMenu.classList.contains('is-open')) sideMenu.classList.remove('is-open');
  }, { passive: true });

  // Lazy images
  document.querySelectorAll('img[loading="lazy"]').forEach((img) => {
    img.decoding = 'async';
  });
})();
