<?php

declare(strict_types=1);

$registerNo = $_GET['register_no'] ?? 'EXAMPLE001';
$encoded = urlencode($registerNo);
$src = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={$encoded}";
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Generate QR</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><div class="container"><h1>QR Code</h1><img src="<?= htmlspecialchars($src) ?>" alt="QR"><p><?= htmlspecialchars($registerNo) ?></p></div></body></html>
