function onScanSuccess(decodedText) {
  fetch('../api/qr_mark.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'register_no=' + encodeURIComponent(decodedText)
  })
    .then((res) => res.json())
    .then((data) => alert(data.message))
    .catch(() => alert('QR request failed'));
}

function markManual() {
  const registerNo = document.getElementById('manual-register').value;
  onScanSuccess(registerNo);
}

window.addEventListener('DOMContentLoaded', () => {
  if (typeof Html5QrcodeScanner !== 'undefined' && document.getElementById('reader')) {
    const html5QrCode = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 });
    html5QrCode.render(onScanSuccess);
  }
});
