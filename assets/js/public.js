(function () {
  'use strict';

  if ('serviceWorker' in navigator && window.MALLMSPublic?.swUrl) {
    navigator.serviceWorker.register(MALLMSPublic.swUrl).catch(() => {});
  }

  document.querySelectorAll('.mal-lms-receipt-upload').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const body = new FormData(form);
      body.append('action', 'mal_lms_upload_receipt');
      body.append('nonce', MALLMSPublic.nonce);
      const response = await fetch(MALLMSPublic.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
      const json = await response.json();
      if (json.success) window.location.reload();
      else alert(json.data?.message || 'Upload failed');
    });
  });
})();
