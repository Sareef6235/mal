(() => {
  const flashes = document.querySelectorAll('[data-autohide]');
  flashes.forEach(el => setTimeout(() => el.remove(), 2600));
})();
