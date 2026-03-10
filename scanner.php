<?php declare(strict_types=1); require __DIR__ . '/bootstrap.php'; render_header('Camera Scanner'); ?>
<div class="card">
  <h3>QR Camera Scanner</h3>
  <p class="small">Use camera scan, token/MSR will be sent to attendance page.</p>
  <video id="preview" style="width:100%;max-width:560px;border-radius:10px;background:#000" autoplay muted playsinline></video>
  <p><button id="startBtn" type="button">Start Camera</button></p>
  <p id="scanOut"></p>
</div>
<script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
<script>
const v=document.getElementById('preview');
const out=document.getElementById('scanOut');
let stream=null, raf=null;
async function start(){
  try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}});v.srcObject=stream;tick();}
  catch(e){out.textContent='Camera access failed: '+e.message;}
}
function tick(){
  if(v.readyState>=2){
    const c=document.createElement('canvas'); c.width=v.videoWidth; c.height=v.videoHeight;
    const x=c.getContext('2d'); x.drawImage(v,0,0,c.width,c.height);
    const d=x.getImageData(0,0,c.width,c.height); const code=jsQR(d.data,c.width,c.height);
    if(code && code.data){ out.textContent='Scanned: '+code.data; window.location.href='attendance_qr.php?self_qr='+encodeURIComponent(code.data); return; }
  }
  raf=requestAnimationFrame(tick);
}
document.getElementById('startBtn').onclick=start;
</script>
<?php render_footer(); ?>
