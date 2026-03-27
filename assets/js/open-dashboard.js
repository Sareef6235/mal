(function () {
  const DASHBOARD_URL = 'https://mmhnu.online/qwe3/dashboard.php';

  function isSafeWebContext() {
    return window.location.protocol === 'http:' || window.location.protocol === 'https:';
  }

  function openDashboardSafely() {
    if (!isSafeWebContext()) {
      window.location.href = DASHBOARD_URL;
      return;
    }

    window.open(DASHBOARD_URL, '_blank', 'noopener,noreferrer');
  }

  window.openDashboardSafely = openDashboardSafely;

  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-open-dashboard]');
    if (!trigger) return;

    event.preventDefault();
    openDashboardSafely();
  });
})();
