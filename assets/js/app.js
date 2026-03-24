document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.js-alert').forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 300);
    }, 4000);
  });

  const uploadForm = document.getElementById('uploadForm');
  if (uploadForm) {
    uploadForm.addEventListener('submit', (event) => {
      const fileInput = document.getElementById('uploaded_file');
      const file = fileInput?.files?.[0];
      if (!file) return;

      if (file.size > 2 * 1024 * 1024) {
        event.preventDefault();
        alert('File size must not exceed 2MB.');
      }
    });
  }
});
