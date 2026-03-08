<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('teacher');

$students = $pdo->query('SELECT id, name, register_no FROM students ORDER BY name')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Face Attendance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js"></script>
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h2>Face Recognition Attendance</h2>
    <p>Student തിരഞ്ഞെടുക്കുക. മുഖം detect ആകുമ്പോൾ attendance mark ചെയ്യും (duplicate same-day തടയും).</p>

    <div class="grid-2">
        <div>
            <label for="student_id">Student</label>
            <select id="student_id" required>
                <option value="">-- Select Student --</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= (int) $student['id'] ?>">
                        <?= htmlspecialchars($student['name'] . ' (' . $student['register_no'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="status" class="alert" style="display:none;"></div>
            <button id="startBtn" type="button">Start Face Detection</button>
        </div>

        <div>
            <div class="video-wrap">
                <video id="video" width="420" height="320" autoplay muted></video>
            </div>
        </div>
    </div>
</div>

<script>
const video = document.getElementById('video');
const statusBox = document.getElementById('status');
const startBtn = document.getElementById('startBtn');
let lastMarkedAt = 0;

function showStatus(message, type = 'alert') {
    statusBox.style.display = 'block';
    statusBox.className = type;
    statusBox.textContent = message;
}

async function openCamera() {
    const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
    video.srcObject = stream;
}

async function loadModels() {
    await faceapi.nets.tinyFaceDetector.loadFromUri('../models');
}

function markAttendance(studentId) {
    return fetch('../api/face_mark.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'student_id=' + encodeURIComponent(studentId)
    })
    .then(res => res.json())
    .then(data => {
        showStatus(data.message, data.ok ? 'success' : 'alert');
    })
    .catch(() => showStatus('Face attendance API failed', 'alert'));
}

async function startDetection() {
    const studentId = document.getElementById('student_id').value;
    if (!studentId) {
        showStatus('Please select a student first', 'alert');
        return;
    }

    await openCamera();
    await loadModels();
    showStatus('Camera ready. Detecting face every 3 seconds...', 'success');

    setInterval(async () => {
        const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
        if (detections.length > 0) {
            const now = Date.now();
            if (now - lastMarkedAt > 7000) {
                lastMarkedAt = now;
                markAttendance(studentId);
            }
        }
    }, 3000);
}

startBtn.addEventListener('click', () => {
    startDetection().catch(() => showStatus('Camera/model load failed', 'alert'));
});
</script>
</body>
</html>
