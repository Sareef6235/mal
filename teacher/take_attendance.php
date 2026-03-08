<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_auth('teacher');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Take Attendance</title><link rel="stylesheet" href="../assets/css/style.css"><script src="https://unpkg.com/html5-qrcode" defer></script><script src="../assets/js/qr_scan.js" defer></script></head>
<body><div class="container"><h1>Take Attendance</h1><div id="reader"></div><label>Manual Register No</label><input id="manual-register"><button onclick="markManual()">Mark by QR API</button><hr><video id="video" width="300" autoplay muted></video><br><input id="face-register" placeholder="Register No for face"><button onclick="markAttendance()">Mark Face Attendance</button><script src="https://cdn.jsdelivr.net/npm/face-api.js"></script><script src="../assets/js/face.js"></script></div></body></html>
