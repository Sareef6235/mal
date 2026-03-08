async function detectFace() {
  const video = document.getElementById('video');
  if (!video || typeof faceapi === 'undefined') return;
  const detection = await faceapi.detectSingleFace(video);
  if (detection) {
    markAttendance();
  }
}

function markAttendance() {
  const registerNo = document.getElementById('face-register').value;
  const formData = new FormData();
  formData.append('register_no', registerNo);

  fetch('../api/face_mark.php', {
    method: 'POST',
    body: formData
  })
    .then((res) => res.json())
    .then((data) => alert(data.message))
    .catch(() => alert('Face attendance request failed'));
}

window.addEventListener('DOMContentLoaded', async () => {
  const video = document.getElementById('video');
  if (!video) return;

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
  } catch (error) {
    console.warn('Camera access denied', error);
  }
});
