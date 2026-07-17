document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  document.getElementById('menuBtn')?.addEventListener('click', () => sidebar.classList.toggle('open'));
  const file = document.getElementById('templateFile');
  const upload = document.getElementById('uploadButton');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  upload?.addEventListener('click', async () => {
    if (!file.files.length) { file.click(); return; }
    const original = upload.innerHTML;
    upload.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Scanning placeholders...';
    upload.disabled = true;
    try {
      const payload = new FormData();
      payload.append('action', 'upload'); payload.append('template', file.files[0]);
      payload.append('name', file.files[0].name.replace(/\.docx$/i, ''));
      const response = await fetch('api.php', { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: payload });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'Upload failed.');
      bootstrap.Modal.getInstance(document.getElementById('uploadModal'))?.hide();
      document.querySelector('#successToast b').textContent = 'Template uploaded and mapped';
      document.querySelector('#successToast span').textContent = `Template #${result.template_id} is ready for review.`;
      new bootstrap.Toast(document.getElementById('successToast')).show();
      setTimeout(() => window.location.reload(), 850);
    } catch (error) {
      window.alert(error.message || 'Unable to upload the template.');
    } finally { upload.innerHTML = original; upload.disabled = false; }
  });

  document.querySelectorAll('.btn, .quick-card, .create-template').forEach(button => button.addEventListener('pointerdown', event => {
    const ripple = document.createElement('span'); ripple.className = 'ripple'; const rect = button.getBoundingClientRect();
    ripple.style.left = `${event.clientX - rect.left}px`; ripple.style.top = `${event.clientY - rect.top}px`; button.append(ripple);
    setTimeout(() => ripple.remove(), 600);
  }));
});
