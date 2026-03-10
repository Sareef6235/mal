<?php
declare(strict_types=1);
require_once __DIR__ . '/../modules/festival.php';
$db = fest_db();
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<div class="card card-soft p-3">
  <h4><i class="bi bi-shield-check me-2"></i>Feedback Moderation</h4>
  <p class="small text-muted">Approve, reject, hide comments and post admin replies.</p>
  <div id="adminMsg" class="small mb-2"></div>
  <div class="table-responsive">
    <table class="table table-modern" id="adminTable">
      <thead><tr><th>ID</th><th>User</th><th>Event</th><th>Rating</th><th>Comment</th><th>Status</th><th>Reply</th><th>Action</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>
<script>
const api='/api/festival_feedback_api.php';
const csrf='<?= e(fest_csrf_token()) ?>';
const msg=document.getElementById('adminMsg');

async function post(fd){ const r=await fetch(api,{method:'POST',body:fd}); return await r.json(); }

async function loadAdmin(){
  const data=await (await fetch(api+'?action=admin_list')).json();
  const tb=document.querySelector('#adminTable tbody'); tb.innerHTML='';
  (data.rows||[]).forEach(r=>{
    tb.insertAdjacentHTML('beforeend',`<tr><td>${r.id}</td><td>${Number(r.is_anonymous)===1?'Anonymous User':r.name}<div class="small text-muted">${r.role}</div></td><td>${r.festival_name||''}<div class="small text-muted">${r.event_name||''}</div></td><td>${'⭐'.repeat(Math.max(1,Math.min(5,Number(r.rating)||0)))}</td><td style="max-width:260px">${r.comment}</td><td><span class="badge bg-secondary">${r.status}</span></td><td><textarea class="form-control form-control-sm reply-txt" data-id="${r.id}" rows="2">${r.admin_reply||''}</textarea><button class="btn btn-sm btn-outline-primary mt-1 save-reply" data-id="${r.id}">Save Reply</button></td><td><div class="d-flex flex-column gap-1"><button class="btn btn-sm btn-success mod-btn" data-id="${r.id}" data-status="approved">Approve</button><button class="btn btn-sm btn-warning mod-btn" data-id="${r.id}" data-status="hidden">Hide</button><button class="btn btn-sm btn-danger mod-btn" data-id="${r.id}" data-status="rejected">Reject</button></div></td></tr>`);
  });

  document.querySelectorAll('.mod-btn').forEach(btn=>btn.onclick=async()=>{
    const fd=new FormData(); fd.append('action','moderate'); fd.append('csrf_token',csrf); fd.append('id',btn.dataset.id); fd.append('status',btn.dataset.status);
    const res=await post(fd); msg.className='small '+(res.ok?'text-success':'text-danger'); msg.textContent=res.message||'failed'; loadAdmin();
  });

  document.querySelectorAll('.save-reply').forEach(btn=>btn.onclick=async()=>{
    const txt=document.querySelector('.reply-txt[data-id="'+btn.dataset.id+'"]');
    const fd=new FormData(); fd.append('action','reply'); fd.append('csrf_token',csrf); fd.append('id',btn.dataset.id); fd.append('admin_reply',txt.value||'');
    const res=await post(fd); msg.className='small '+(res.ok?'text-success':'text-danger'); msg.textContent=res.message||'failed';
  });
}
loadAdmin();
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
