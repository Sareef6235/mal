<?php
declare(strict_types=1);
require_once __DIR__ . '/../modules/festival.php';
$db = fest_db();
$festivals = $db->query('SELECT id, festival_name FROM festivals ORDER BY id DESC')->fetchAll() ?: [];
$events = $db->query('SELECT id, event_name, festival_id FROM festival_events ORDER BY id DESC')->fetchAll() ?: [];
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$a = random_int(1, 9); $b = random_int(1, 9); $_SESSION['feedback_captcha'] = (string)($a + $b);
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card card-soft p-3">
      <h4><i class="bi bi-chat-left-text me-2"></i>Festival Feedback</h4>
      <p class="small text-muted">Share comments about Milad Fest, Art Fest and events.</p>
      <div id="fbMsg" class="small"></div>
      <form id="feedbackForm" class="row g-2">
        <input type="hidden" name="csrf_token" value="<?= e(fest_csrf_token()) ?>">
        <input type="hidden" name="action" value="submit">
        <div class="col-md-6"><input class="form-control" name="name" placeholder="Name"></div>
        <div class="col-md-6"><input class="form-control" name="email" placeholder="Email (optional)"></div>
        <div class="col-md-6"><select class="form-control" name="role"><option>Student</option><option>Visitor</option><option>Teacher</option><option>Parent</option></select></div>
        <div class="col-md-6 form-check mt-2 ms-2"><input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="anon"><label class="form-check-label" for="anon">Submit anonymously</label></div>
        <div class="col-md-6"><select class="form-control" name="festival_id" id="festivalSelect" required><option value="">Festival</option><?php foreach($festivals as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e((string)$f['festival_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><select class="form-control" name="event_id" id="eventSelect" required><option value="">Event</option><?php foreach($events as $e): ?><option data-festival="<?= (int)$e['festival_id'] ?>" value="<?= (int)$e['id'] ?>"><?= e((string)$e['event_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><select class="form-control" name="rating" required><option value="">Rating</option><option value="5">⭐⭐⭐⭐⭐</option><option value="4">⭐⭐⭐⭐</option><option value="3">⭐⭐⭐</option><option value="2">⭐⭐</option><option value="1">⭐</option></select></div>
        <div class="col-md-6"><label class="small">Captcha: What is <?= $a ?> + <?= $b ?> ?</label><input class="form-control" name="captcha_answer" required></div>
        <div style="display:none"><input name="website" autocomplete="off"></div>
        <div class="col-12"><textarea class="form-control" name="comment" rows="4" maxlength="600" placeholder="Your comment..." required></textarea></div>
        <div class="col-12"><button class="btn btn-primary w-100" type="submit">Submit Feedback</button></div>
      </form>
    </div>

    <div class="card card-soft p-3 mt-3">
      <h6>Feedback Statistics</h6>
      <div class="d-flex justify-content-between"><span>Average Rating</span><strong id="avgRating">0.0</strong></div>
      <div class="d-flex justify-content-between"><span>Total Comments</span><strong id="totalComments">0</strong></div>
      <div id="ratingBars" class="mt-2"></div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card card-soft p-3">
      <div class="d-flex gap-2 flex-wrap">
        <select id="filterFestival" class="form-control" style="max-width:180px"><option value="">All Festivals</option><?php foreach($festivals as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e((string)$f['festival_name']) ?></option><?php endforeach; ?></select>
        <select id="filterEvent" class="form-control" style="max-width:180px"><option value="">All Events</option><?php foreach($events as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e((string)$e['event_name']) ?></option><?php endforeach; ?></select>
        <select id="filterRating" class="form-control" style="max-width:140px"><option value="">All Ratings</option><option value="5">5★</option><option value="4">4★</option><option value="3">3★</option><option value="2">2★</option><option value="1">1★</option></select>
        <button class="btn btn-outline-primary" id="refreshComments" type="button">Refresh</button>
      </div>
      <hr>
      <div id="commentList" class="d-flex flex-column gap-2"></div>
    </div>
  </div>
</div>

<script>
const api='/api/festival_feedback_api.php';
const csrf='<?= e(fest_csrf_token()) ?>';
const form=document.getElementById('feedbackForm');
const msg=document.getElementById('fbMsg');
const eventSelect=document.getElementById('eventSelect');
const festivalSelect=document.getElementById('festivalSelect');

festivalSelect?.addEventListener('change',()=>{
  const fid=festivalSelect.value;
  [...eventSelect.options].forEach((opt,i)=>{ if(i===0){opt.hidden=false;return;} opt.hidden=(fid!=='' && opt.dataset.festival!==fid); });
  eventSelect.value='';
});

async function postForm(fd){
  const res=await fetch(api,{method:'POST',body:fd});
  return await res.json();
}

function star(n){return '⭐'.repeat(Math.max(1,Math.min(5,Number(n)||0)));}

async function loadStats(){
  const q=new URLSearchParams({action:'stats',festival_id:document.getElementById('filterFestival').value,event_id:document.getElementById('filterEvent').value});
  const data=await (await fetch(api+'?'+q.toString())).json();
  if(!data.ok) return;
  document.getElementById('avgRating').textContent = Number(data.summary.avg_rating||0).toFixed(1)+' ⭐';
  document.getElementById('totalComments').textContent = data.summary.total||0;
  const total=Math.max(1,Number(data.summary.total||0));
  const bars=document.getElementById('ratingBars');
  bars.innerHTML='';
  [5,4,3,2,1].forEach(r=>{
    const cnt=Number(data.distribution[r]||0);
    const p=Math.round((cnt/total)*100);
    bars.insertAdjacentHTML('beforeend',`<div class="mb-1 small">${r}★ <div class="progress" style="height:8px"><div class="progress-bar bg-info" style="width:${p}%"></div></div> <span>${cnt}</span></div>`);
  });
}

async function loadComments(){
  const q=new URLSearchParams({action:'list',festival_id:document.getElementById('filterFestival').value,event_id:document.getElementById('filterEvent').value,rating:document.getElementById('filterRating').value});
  const data=await (await fetch(api+'?'+q.toString())).json();
  const box=document.getElementById('commentList');
  box.innerHTML='';
  (data.rows||[]).forEach(c=>{
    const name = Number(c.is_anonymous)===1 ? 'Anonymous User' : c.name;
    box.insertAdjacentHTML('beforeend',`<div class="comment-card p-3 border rounded-3"><div class="d-flex justify-content-between"><div><strong>${name}</strong> <span class="text-muted">(${c.role})</span><div class="small text-muted">${c.festival_name||''} • ${c.event_name||''}</div></div><div>${star(c.rating)}</div></div><p class="mt-2 mb-1">"${c.comment}"</p><div class="small text-muted">${c.created_at}</div>${c.admin_reply?`<div class="alert alert-info py-1 px-2 mt-2 mb-0"><strong>Admin Reply:</strong> ${c.admin_reply}</div>`:''}<div class="mt-2 d-flex gap-2"><button class="btn btn-sm btn-outline-secondary react-btn" data-id="${c.id}" data-type="helpful">👍 Helpful (${c.helpful_count||0})</button><button class="btn btn-sm btn-outline-danger react-btn" data-id="${c.id}" data-type="like">❤️ Like (${c.like_count||0})</button></div></div>`);
  });
  box.querySelectorAll('.react-btn').forEach(btn=>btn.addEventListener('click',async()=>{
    const fd=new FormData(); fd.append('action','react'); fd.append('csrf_token',csrf); fd.append('comment_id',btn.dataset.id); fd.append('reaction_type',btn.dataset.type);
    const res=await postForm(fd); if(res.ok) loadComments();
  }));
}

form?.addEventListener('submit',async(e)=>{
  e.preventDefault();
  const fd=new FormData(form); fd.set('csrf_token',csrf); fd.set('action','submit');
  const res=await postForm(fd);
  msg.className='small '+(res.ok?'text-success':'text-danger');
  msg.textContent=res.message||'Failed';
  if(res.ok){ form.reset(); loadComments(); loadStats(); }
});

['filterFestival','filterEvent','filterRating'].forEach(id=>document.getElementById(id).addEventListener('change',()=>{loadComments();loadStats();}));
document.getElementById('refreshComments').addEventListener('click',()=>{loadComments();loadStats();});
loadComments(); loadStats(); setInterval(loadComments, 15000);
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
