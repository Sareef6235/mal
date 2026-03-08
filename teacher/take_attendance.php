<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_auth('teacher');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>QR Attendance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h2>QR Attendance Scanner</h2>
    <p>QR code scan ചെയ്താൽ attendance ഉടൻ mark ചെയ്യും.</p>
    <div id="reader" style="width:320px; max-width:100%;"></div>
</div>

<script>
function onScanSuccess(decodedText) {
    fetch('../api/qr_mark.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'register_no=' + encodeURIComponent(decodedText)
    })
    .then(res => res.json())
    .then(data => alert(data.message));
}

const html5QrCode = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 });
html5QrCode.render(onScanSuccess);
</script>
</body>
</html>
