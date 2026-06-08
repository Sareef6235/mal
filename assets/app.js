const state = { csrf: window.MHM_CSRF, notices: [], activeNoticeId: null, students: [], orientation: 'landscape' };
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

const blankNotice = () => ({
  id: '', title_malayalam: 'MHM SKSBV വാർഷിക സംഗമം', title_arabic: 'الحفل السنوي', sub_title: 'Islamic Educational Institution', main_topic: 'വിദ്യാർത്ഥി കലാ പരിപാടി', notice_date: '', notice_time: '', venue: 'MHM SKSBV Campus', qiraat: '', welcome: '', president: '', inaugurator: '', translation_speech: '', speakers: '', singers: '', thanks: '', association_stamp: 'MHM SKSBV', bg_style: 'emerald', design_style: 'classic', font_style_malayalam: 'manjari', font_style_english: 'inter', font_style_arabic: 'amiri', font_size_settings: 'normal', students: []
});

function api(action, body = new FormData()) {
  body.append('csrf_token', state.csrf);
  return fetch(`index.php?api=${action}`, { method: 'POST', body, headers: { 'X-CSRF-Token': state.csrf } }).then(r => r.json());
}

function showPanel(name) {
  $$('[data-panel]').forEach(p => p.classList.toggle('active-panel', p.dataset.panel === name));
  $$('#mainMenu button').forEach(b => b.classList.toggle('active', b.dataset.section === name));
  $('#sidebar').classList.remove('open');
  if (name === 'preview') renderPreview();
  if (name === 'reports') renderReports();
}

function formDataObject() {
  const form = $('#noticeForm');
  const data = Object.fromEntries(new FormData(form).entries());
  data.students = state.students;
  return data;
}

function fillForm(notice) {
  const data = { ...blankNotice(), ...notice };
  Object.entries(data).forEach(([key, value]) => {
    const field = $(`[name="${key}"]`);
    if (field) field.value = value ?? '';
  });
  state.activeNoticeId = data.id ? Number(data.id) : null;
  state.students = Array.isArray(data.students) ? data.students.map(s => ({...s})) : [];
  saveLocalStudents();
  renderStudents();
  renderPreview();
}

function resetForm() { fillForm(blankNotice()); showPanel('create'); }

function saveLocalStudents() { localStorage.setItem('mhm_students_draft', JSON.stringify(state.students)); }
function restoreLocalStudents() {
  try { state.students = JSON.parse(localStorage.getItem('mhm_students_draft') || '[]'); } catch { state.students = []; }
}

function renderStudents() {
  const search = ($('#studentSearch')?.value || '').toLowerCase();
  const sort = $('#studentSort')?.value || 'desc';
  const rows = [...state.students].sort((a,b) => sort === 'asc' ? Number(a.class_num) - Number(b.class_num) : Number(b.class_num) - Number(a.class_num));
  $('#studentRows').innerHTML = rows.map((s, i) => {
    const originalIndex = state.students.indexOf(s);
    const text = `${s.class_num} ${s.student_name} ${s.item_type}`.toLowerCase();
    if (search && !text.includes(search)) return '';
    return `<div class="student-row-edit" data-index="${originalIndex}">
      <input placeholder="Class" value="${escapeHtml(s.class_num || '')}" data-student="class_num">
      <input placeholder="Student name" value="${escapeHtml(s.student_name || '')}" data-student="student_name">
      <input placeholder="Item" value="${escapeHtml(s.item_type || '')}" data-student="item_type">
      <input placeholder="Icon" value="${escapeHtml(s.item_icon || '🟢')}" data-student="item_icon">
      <button class="premium-btn danger" type="button" data-remove-student>Remove</button>
    </div>`;
  }).join('') || '<p class="muted">No students yet. Add a row to begin.</p>';
}

function renderNoticeList() {
  const query = ($('#noticeSearch')?.value || '').toLowerCase();
  const filter = $('#noticeFilter')?.value || 'all';
  const cards = state.notices.filter(n => {
    const matchesSearch = !query || `${n.title_malayalam} ${n.title_arabic} ${n.main_topic} ${n.venue}`.toLowerCase().includes(query);
    const matchesFilter = filter === 'all' || n.bg_style === filter;
    return matchesSearch && matchesFilter;
  }).map(n => `<article class="notice-card">
    <span class="premium-chip">${escapeHtml(n.bg_style || 'emerald')}</span>
    <h3>${escapeHtml(n.title_malayalam)}</h3>
    <p>${escapeHtml(n.main_topic || n.sub_title || 'Premium notice')}</p>
    <p>📅 ${escapeHtml(n.notice_date || '-')} • 🎓 ${Number(n.student_count || (n.students || []).length)} Students</p>
    <div class="notice-actions">
      <button class="premium-btn emerald" data-edit="${n.id}">Edit</button>
      <button class="premium-btn gold" data-preview-id="${n.id}">Preview</button>
      <button class="premium-btn slate" data-duplicate="${n.id}">Duplicate</button>
      <button class="premium-btn danger" data-delete="${n.id}">Delete</button>
    </div>
  </article>`).join('');
  $('#noticeList').innerHTML = cards || '<div class="glass-panel">No notices match your search.</div>';
}

function renderStats(stats = null) {
  const notices = state.notices.length;
  const students = state.notices.reduce((t,n) => t + Number(n.student_count || (n.students || []).length), 0);
  const programs = state.notices.reduce((t,n) => t + ['qiraat','welcome','president','inaugurator','translation_speech','speakers','singers','thanks'].filter(k => n[k]).length, 0);
  $('#statNotices').textContent = stats?.notices ?? notices;
  $('#statStudents').textContent = stats?.students ?? students;
  $('#statPrograms').textContent = stats?.programs ?? programs;
  $('#activityFeed').innerHTML = state.notices.slice(0, 6).map(n => `<div>✨ ${escapeHtml(n.title_malayalam)} updated for ${escapeHtml(n.notice_date || 'upcoming date')}</div>`).join('') || '<div>Ready to create the first premium notice.</div>';
}

function previewPages(data) {
  const students = data.students || [];
  const classGroups = students.reduce((acc, s) => ((acc[s.class_num] ||= []).push(s), acc), {});
  const classes = Object.entries(classGroups).sort((a,b) => Number(b[0]) - Number(a[0]));
  const chunks = [];
  for (let i = 0; i < classes.length; i += 4) chunks.push(classes.slice(i, i + 4));
  const themeClass = data.bg_style === 'gold' ? 'gold-theme' : data.bg_style === 'midnight' ? 'midnight-theme' : data.bg_style === 'royal' ? 'royal-theme' : data.bg_style === 'bw' ? 'bw-theme' : '';
  const fontClass = `${data.font_style_malayalam === 'gayathri' ? 'mal-gayathri' : data.font_style_malayalam === 'chilanka' ? 'mal-chilanka' : ''} ${data.font_style_arabic === 'noto' ? 'arabic-noto' : data.font_style_arabic === 'kufi' ? 'arabic-kufi' : ''} font-${data.font_size_settings || 'normal'}`;
  const program = [['🤲','Qiraat',data.qiraat],['🌿','Welcome',data.welcome],['🏛️','President',data.president],['🎙️','Inaugurator',data.inaugurator],['🌐','Translation',data.translation_speech],['📖','Speakers',data.speakers],['🎵','Singers',data.singers],['🤝','Thanks',data.thanks]].filter(x => x[2]);
  const cover = `<article class="a4-page ${themeClass} ${fontClass}"><p class="arabic-title bismillah">بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْمِ</p><h1 class="notice-title">${escapeHtml(data.title_malayalam)}</h1><h2 class="arabic-title">${escapeHtml(data.title_arabic)}</h2><p class="notice-sub">${escapeHtml(data.sub_title)}</p><div class="topic">${escapeHtml(data.main_topic)}</div><div class="info-grid"><div class="info-box">📅<br>${escapeHtml(data.notice_date)}</div><div class="info-box">⏰<br>${escapeHtml(data.notice_time)}</div><div class="info-box">📍<br>${escapeHtml(data.venue)}</div></div><footer class="page-footer"><span>MHM SKSBV</span><span>${escapeHtml(data.association_stamp)}</span><span>Page 1</span></footer></article>`;
  const schedule = `<article class="a4-page ${themeClass} ${fontClass}"><h2 class="notice-title">Programme Schedule</h2><div class="program-grid">${program.map(p => `<div class="program-item"><strong>${p[0]} ${escapeHtml(p[1])}</strong><br>${escapeHtml(p[2])}</div>`).join('')}</div><footer class="page-footer"><span>MHM SKSBV</span><span>۞</span><span>Page 2</span></footer></article>`;
  const studentPages = chunks.map((chunk, i) => `<article class="a4-page ${themeClass} ${fontClass}"><h2 class="notice-title">Student Items</h2><div class="student-grid">${chunk.map(([cls, list]) => `<div class="class-card"><h3>CLASS ${escapeHtml(cls)}</h3>${list.map(s => `<div class="student-item-line"><span>${escapeHtml(s.item_icon || '🟢')} ${escapeHtml(s.student_name)}</span><strong>${escapeHtml(s.item_type)}</strong></div>`).join('')}</div>`).join('')}</div><footer class="page-footer"><span>MHM SKSBV</span><span>۞</span><span>Page ${i + 3} of ${chunks.length + 2}</span></footer></article>`);
  return [cover, schedule, ...studentPages].join('');
}

function renderPreview() { $('#pdfPreview').innerHTML = previewPages(formDataObject()); }
function renderReports() {
  const byTheme = state.notices.reduce((a,n) => (a[n.bg_style || 'emerald'] = (a[n.bg_style || 'emerald'] || 0) + 1, a), {});
  $('#reportsPanel').innerHTML = Object.entries(byTheme).map(([k,v]) => `<article class="stat-card"><span>📊</span><p>${escapeHtml(k)} Notices</p><strong>${v}</strong></article>`).join('') || '<div class="glass-panel">No reports yet.</div>';
}

function openModal({icon='✨', title='Premium Notice', text='', buttons=[]}) {
  $('#modalIcon').textContent = icon; $('#modalTitle').textContent = title; $('#modalText').textContent = text;
  $('#modalActions').innerHTML = ''; buttons.forEach(b => $('#modalActions').appendChild(b));
  $('#modal').classList.add('open'); $('#modal').setAttribute('aria-hidden','false');
}
function closeModal() { $('#modal').classList.remove('open'); $('#modal').setAttribute('aria-hidden','true'); }
function modalBtn(label, cls, fn) { const b = document.createElement('button'); b.type = 'button'; b.className = `premium-btn ${cls}`; b.textContent = label; b.addEventListener('click', fn); return b; }
function toast(icon, title, text) { openModal({icon, title, text, buttons:[modalBtn('Close','gold',closeModal)]}); }

function escapeHtml(value) { return String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c])); }
function exportCsv() {
  const rows = [['Class','Student','Item','Icon'], ...state.students.map(s => [s.class_num, s.student_name, s.item_type, s.item_icon])];
  const csv = rows.map(r => r.map(v => `"${String(v || '').replace(/"/g,'""')}"`).join(',')).join('\n');
  const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob([csv], {type:'text/csv'})); link.download = 'mhm-sksbv-students.csv'; link.click(); URL.revokeObjectURL(link.href);
}
function applyPrintOrientation(mode) {
  state.orientation = mode;
  let style = $('#printOrientationStyle');
  if (!style) { style = document.createElement('style'); style.id = 'printOrientationStyle'; document.head.appendChild(style); }
  style.textContent = `@media print{@page{size:A4 ${mode};margin:0}}`;
}
function printPreview() { $$('[data-panel]').forEach(p => p.classList.toggle('printing', p.dataset.panel === 'preview')); renderPreview(); applyPrintOrientation(state.orientation); window.print(); }

async function bootstrap() {
  const res = await fetch('index.php?api=bootstrap').then(r => r.json());
  if (res.success) { state.csrf = res.csrf; state.notices = res.notices || []; renderStats(res.stats); renderNoticeList(); fillForm(state.notices[0] || blankNotice()); }
  $('#loadingScreen').classList.add('hidden');
}

document.addEventListener('input', e => {
  if (e.target.matches('[data-preview]')) renderPreview();
  if (e.target.matches('[data-student]')) { const row = e.target.closest('[data-index]'); state.students[Number(row.dataset.index)][e.target.dataset.student] = e.target.value; saveLocalStudents(); renderPreview(); }
  if (e.target.id === 'noticeSearch' || e.target.id === 'noticeFilter') renderNoticeList();
  if (e.target.id === 'studentSearch' || e.target.id === 'studentSort') renderStudents();
});
document.addEventListener('click', async e => {
  const menu = e.target.closest('#mainMenu button'); if (menu) showPanel(menu.dataset.section);
  if (e.target.closest('[data-quick="create"]')) resetForm();
  if (e.target.id === 'mobileMenuButton') $('#sidebar').classList.toggle('open');
  if (e.target.id === 'collapseSidebar') { $('#sidebar').classList.toggle('collapsed'); $('.app-shell').classList.toggle('expanded'); }
  if (e.target.id === 'resetFormBtn') resetForm();
  if (e.target.id === 'addStudentRow') { state.students.push({class_num:'',student_name:'',item_type:'',item_icon:'🟢'}); saveLocalStudents(); renderStudents(); }
  if (e.target.matches('[data-remove-student]')) { state.students.splice(Number(e.target.closest('[data-index]').dataset.index),1); saveLocalStudents(); renderStudents(); renderPreview(); }
  const id = e.target.dataset.edit || e.target.dataset.previewId; if (id) { const notice = state.notices.find(n => String(n.id) === String(id)); if (notice) fillForm(notice); showPanel(e.target.dataset.previewId ? 'preview' : 'create'); }
  if (e.target.dataset.delete) openModal({icon:'🗑️', title:'Delete Confirmation', text:'Delete this notice permanently?', buttons:[modalBtn('Cancel','slate',closeModal), modalBtn('Delete','danger',async()=>{ const fd=new FormData(); fd.append('id', e.target.dataset.delete); const r=await api('delete_notice',fd); closeModal(); if(r.success){state.notices=state.notices.filter(n=>String(n.id)!==String(e.target.dataset.delete)); renderNoticeList(); renderStats(); toast('✅','Success',r.message);} })]});
  if (e.target.dataset.duplicate) { const fd = new FormData(); fd.append('id', e.target.dataset.duplicate); const r = await api('duplicate_notice', fd); if (r.success) { state.notices.unshift(r.notice); renderNoticeList(); renderStats(); toast('✅','Success',r.message); } }
  if (e.target.id === 'exportStudents') exportCsv();
  if (['printActiveBtn','previewPrintBtn','portraitBtn','landscapeBtn'].includes(e.target.id)) { if (e.target.id === 'portraitBtn') applyPrintOrientation('portrait'); if (e.target.id === 'landscapeBtn') applyPrintOrientation('landscape'); showPanel('preview'); openModal({icon:'🖨️',title:'Print Confirmation',text:'Open browser print preview now?',buttons:[modalBtn('Cancel','slate',closeModal),modalBtn('Print Now','gold',()=>{closeModal();printPreview();})]}); }
});
$('#noticeForm').addEventListener('submit', async e => {
  e.preventDefault(); renderPreview(); const fd = new FormData(e.target); fd.set('students_json', JSON.stringify(state.students)); const r = await api('save_notice', fd);
  if (r.success) { const index = state.notices.findIndex(n => String(n.id) === String(r.notice.id)); if (index >= 0) state.notices[index] = r.notice; else state.notices.unshift(r.notice); fillForm(r.notice); renderNoticeList(); renderStats(); toast('✅','Success',r.message); } else toast('⚠️','Error',r.message || 'Unable to save notice.');
});
$('#modal').addEventListener('click', e => { if (e.target.id === 'modal') closeModal(); });
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
  const blocked = e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I','J'].includes(e.key.toUpperCase())) || (e.ctrlKey && ['U','S'].includes(e.key.toUpperCase()));
  if (blocked) { e.preventDefault(); toast('⚠️','Protected Dashboard','This shortcut is disabled for session protection.'); }
});
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('dragstart', e => e.preventDefault());
document.body.style.userSelect = 'none';
applyPrintOrientation('landscape');
restoreLocalStudents();
bootstrap();
