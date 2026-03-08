<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_auth('teacher');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Face Attendance (QR + Face)</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js"></script>
    <style>
        .step-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .status-text { font-weight: 700; margin-bottom: 8px; }
    </style>
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h2>Face Attendance (Step-by-Step)</h2>
    <p>Step 1: QR scan ചെയ്യുക → Step 2: മുഖം detect ആകുമ്പോൾ attendance save ചെയ്യും.</p>

    <div class="grid-2">
        <div>
            <div class="step-box">
                <h3>Step 1 — QR Scan</h3>
                <div id="reader" style="width: 320px; max-width: 100%;"></div>
                <p><strong>Scanned Register:</strong> <span id="scannedRegister">-</span></p>
                <p><strong>Matched Student:</strong> <span id="studentName">-</span></p>
            </div>

            <div class="step-box">
                <h3>System Status</h3>
                <p id="status" class="status-text">Waiting for QR scan...</p>
                <div id="message" class="alert" style="display:none;"></div>
            </div>
        </div>

        <div>
            <div class="step-box">
                <h3>Step 2 — Face Detection</h3>
                <div class="video-wrap">
                    <video id="video" width="420" height="320" autoplay muted></video>
                </div>
                <p style="margin-top:8px;">QR scan complete ആകുന്നതിന് ശേഷം camera + model start ചെയ്യും.</p>
            </div>
        </div>
    </div>
</div>

<script>
const statusEl = document.getElementById('status');
const messageEl = document.getElementById('message');
const scannedRegisterEl = document.getElementById('scannedRegister');
const studentNameEl = document.getElementById('studentName');
const video = document.getElementById('video');

let selectedStudentId = null;
let selectedRegister = null;
let detectionStarted = false;
let lastMarkedAt = 0;

function setStatus(text) {
    statusEl.textContent = text;
}

function showMessage(text, ok = false) {
    messageEl.style.display = 'block';
    messageEl.className = ok ? 'success' : 'alert';
    messageEl.textContent = text;
}

async function ensureModelAvailable() {
    setStatus('Loading AI model...');
    try {
        await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
        setStatus('Model loaded');
        return;
    } catch (_) {
        // fallback when hosted in subfolder
    }

    await faceapi.nets.tinyFaceDetector.loadFromUri('../models');
    setStatus('Model loaded (subfolder path)');
}

async function startCamera() {
    setStatus('Starting camera...');
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    video.srcObject = stream;
    setStatus('Camera started');
}

async function startDetectionLoop() {
    if (detectionStarted) return;
    detectionStarted = true;

    await ensureModelAvailable();
    await startCamera();
    setStatus('Face detection started');

    setInterval(async () => {
        if (!selectedStudentId) {
            return;
        }

        const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
        if (detections.length > 0) {
            setStatus('Face detected');

            const now = Date.now();
            if (now - lastMarkedAt < 7000) {
                return;
            }
            lastMarkedAt = now;

            markAttendance(selectedStudentId, selectedRegister);
        }
    }, 2000);
}

async function resolveStudentByRegister(registerNo) {
    const response = await fetch('../api/get_students.php');
    const data = await response.json();
    if (!data.success || !Array.isArray(data.students)) {
        throw new Error('Unable to load students');
    }

    const student = data.students.find((s) => String(s.register_no).trim() === String(registerNo).trim());
    if (!student) {
        throw new Error('Student not found for scanned QR');
    }

    return student;
}

function markAttendance(studentId, registerNo) {
    fetch('../api/face_mark.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'student_id=' + encodeURIComponent(studentId) + '&register_no=' + encodeURIComponent(registerNo)
    })
    .then((res) => res.json())
    .then((data) => {
        showMessage(data.message || 'Unknown response', !!data.ok);
    })
    .catch(() => {
        showMessage('Attendance API failed', false);
    });
}

async function handleQrScan(decodedText) {
    try {
        scannedRegisterEl.textContent = decodedText;
        setStatus('Resolving student from QR...');

        const student = await resolveStudentByRegister(decodedText);
        selectedStudentId = student.id;
        selectedRegister = student.register_no;
        studentNameEl.textContent = student.name + ' (' + student.register_no + ')';

        showMessage('QR matched. Starting face detection...', true);
        await startDetectionLoop();
    } catch (error) {
        showMessage(error.message || 'QR processing failed', false);
        setStatus('Waiting for valid QR scan...');
    }
}

function onScanSuccess(decodedText) {
    handleQrScan(decodedText);
}

const html5QrCode = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 });
html5QrCode.render(onScanSuccess);
</script>
</body>
</html>
