const state = { user: window.initialUser, madrasaId: 1, madrasas: [], students: [], results: [], announcements: [], teachers: [], attendance: [], markHistory: [], messages: [], payrolls: [] };
const $ = (s) => document.querySelector(s);
const $$ = (s) => [...document.querySelectorAll(s)];
const charts = {};

async function api(action, method='GET', data=null, multipart=false) {
  const opts = { method };
  if (data && !multipart) { opts.headers = { 'Content-Type':'application/json' }; opts.body = JSON.stringify(data); }
  if (data && multipart) opts.body = data;
  const suffix = method === 'GET' ? `&madrasa_id=${state.madrasaId}` : '';
  const res = await fetch(`api.php?action=${action}${suffix}`, opts);
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || 'Request failed');
  return json;
}

const role = () => state.user?.role || 'Viewer';
const canEdit = () => ['Admin','Teacher'].includes(role());
const adminOnly = () => role() === 'Admin';

function chart(id, type, labels, values, label='') {
  if (!document.getElementById(id)) return;
  if (charts[id]) charts[id].destroy();
  charts[id] = new Chart(document.getElementById(id), { type, data: { labels, datasets:[{ label, data: values }] }, options: { responsive:true } });
}

function applyRole() {
  $$('.role-edit').forEach(el => el.style.display = canEdit() ? 'inline-flex' : 'none');
  $$('.role-admin').forEach(el => el.style.display = adminOnly() ? 'inline-flex' : 'none');
  $('#profileInfo').textContent = state.user ? `${state.user.username} (${state.user.role})` : 'Guest Viewer';
  $('#authState').textContent = state.user ? `Logged in as ${state.user.username} (${state.user.role})` : 'Default: admin/madrasa123, teacher/teacher123, viewer/viewer123';
  $('#authToggle').textContent = state.user ? 'Logout' : 'Login';
}

function renderDashboard() {
  const boys = state.students.filter(s => s.gender === 'Boy').length;
  const girls = state.students.filter(s => s.gender === 'Girl').length;
  const pass = state.results.filter(r => r.grade !== 'F').length;
  const passPercent = state.results.length ? Math.round((pass / state.results.length) * 100) : 0;
  $('#dashboardCards').innerHTML = [
    ['Total Students', state.students.length], ['Boys', boys], ['Girls', girls], ['Pass %', `${passPercent}%`], ['Teachers', state.teachers.length], ['Messages', state.messages.length]
  ].map(([k, v]) => `<article class="stat-card"><h3>${k}</h3><strong>${v}</strong></article>`).join('');

  const classMap = {};
  state.students.forEach(s => classMap[s.class_name] = (classMap[s.class_name] || 0) + 1);
  chart('distributionChart', 'bar', Object.keys(classMap), Object.values(classMap), 'Students');
  chart('passChart', 'doughnut', ['Pass', 'Fail'], [passPercent, 100 - passPercent], 'Result');
  const present = state.attendance.filter(a => a.status === 'Present').length;
  chart('attendanceChart', 'pie', ['Present', 'Absent'], [present, Math.max(state.attendance.length - present, 0)], 'Attendance');
}

function renderStudents() {
  const q = $('#studentSearch').value.toLowerCase();
  const cls = $('#classFilter').value;
  const g = $('#genderFilter').value;
  const rows = state.students.filter(s => (!q || [s.full_name, s.student_uid, s.class_name].join(' ').toLowerCase().includes(q)) && (cls === 'All' || s.class_name === cls) && (g === 'All' || s.gender === g));
  $('#studentsTableBody').innerHTML = rows.map(s => `<tr><td>${s.student_uid}</td><td><img class="passport" src="${s.photo_path || 'https://placehold.co/36x45'}"></td><td>${s.full_name}</td><td>${s.class_name}</td><td>${s.gender}</td><td>${s.attendance_percent}%</td><td><button class="btn btn-outline" data-profile="${s.id}">Profile</button>${canEdit() ? `<button class="btn btn-outline" data-edit="${s.id}">Edit</button>` : ''}${adminOnly() ? `<button class="btn btn-outline" data-delete="${s.id}">Delete</button>` : ''}<button class="btn btn-outline" data-report="${s.id}">PDF</button></td></tr>`).join('');
  $('#classFilter').innerHTML = ['All', ...new Set(state.students.map(s => s.class_name))].map(c => `<option ${c===cls?'selected':''}>${c}</option>`).join('');
}

function renderResults() {
  const pass = state.results.filter(r => r.grade !== 'F').length;
  const passPercent = state.results.length ? Math.round((pass / state.results.length) * 100) : 0;
  $('#resultsSummary').innerHTML = `<article class="stat-card"><h3>Total Results</h3><strong>${state.results.length}</strong></article><article class="stat-card"><h3>Pass %</h3><strong>${passPercent}%</strong></article>`;
  const top = [...state.results].sort((a,b)=>b.total_marks-a.total_marks).slice(0,3);
  $('#topperCards').innerHTML = top.map((t,i)=>`<article class="stat-card"><h3>Top ${i+1}</h3><strong>${t.full_name || t.student_uid}</strong><p>${t.total_marks}</p></article>`).join('');
  $('#resultsWrap').innerHTML = `<table><thead><tr><th>Student</th><th>Exam</th><th>Math</th><th>Science</th><th>English</th><th>Total</th><th>Grade</th><th>Rank</th><th>Edit</th></tr></thead><tbody>${state.results.map(r => `<tr><td>${r.full_name} (${r.student_uid})</td><td>${r.exam_name} (${r.exam_type})</td><td>${r.marks_math}</td><td>${r.marks_science}</td><td>${r.marks_english}</td><td>${r.total_marks}</td><td>${r.grade}</td><td>${r.rank_position||'-'}</td><td>${canEdit()?`<button class='btn btn-outline' data-edit-result='${r.id}'>Edit</button>`:''}</td></tr>`).join('')}</tbody></table>`;
}

function renderAttendance() {
  const date = $('#attendanceDate').value || new Date().toISOString().slice(0,10);
  const todayMap = Object.fromEntries(state.attendance.filter(a => a.attendance_date === date).map(a => [a.student_id, a.status]));
  $('#attendanceWrap').innerHTML = `<table><thead><tr><th>Student</th><th>Status</th></tr></thead><tbody>${state.students.map(s => `<tr><td>${s.full_name} (${s.student_uid})</td><td><select ${canEdit()?'':'disabled'} data-att-student='${s.id}'><option ${todayMap[s.id]==='Present'?'selected':''}>Present</option><option ${todayMap[s.id]==='Absent'?'selected':''}>Absent</option></select></td></tr>`).join('')}</tbody></table>`;

  const month = ($('#attendanceMonth').value || date.slice(0,7));
  const monthRows = state.attendance.filter(a => (a.attendance_date || '').startsWith(month));
  const p = monthRows.filter(a=>a.status==='Present').length;
  const a = monthRows.length - p;
  $('#attendanceStats').innerHTML = `<article class='stat-card'><h3>Monthly Present</h3><strong>${p}</strong></article><article class='stat-card'><h3>Monthly Absent</h3><strong>${a}</strong></article><article class='stat-card'><h3>Total Entries</h3><strong>${monthRows.length}</strong></article>`;

  const byDay = {};
  monthRows.forEach(r=>{ byDay[r.attendance_date] ||= {p:0,a:0}; if(r.status==='Present') byDay[r.attendance_date].p++; else byDay[r.attendance_date].a++; });
  const labels = Object.keys(byDay).sort();
  chart('monthlyAttendanceChart','line',labels,labels.map(d=>byDay[d].p),'Present/day');
}

function renderMarkbook() {
  const map = {};
  state.results.forEach(r => {
    const st = state.students.find(s => s.student_uid === r.student_uid);
    const cls = st?.class_name || 'Unknown';
    if (!map[cls]) map[cls] = [];
    map[cls].push(r);
  });
  $('#markbookArea').innerHTML = Object.entries(map).map(([cls, rows]) => `<h3>${cls}</h3><table><thead><tr><th>Student</th><th>Exam</th><th>Total</th><th>Grade</th></tr></thead><tbody>${rows.map(r=>`<tr><td>${r.student_uid}</td><td>${r.exam_name}</td><td>${r.total_marks}</td><td>${r.grade}</td></tr>`).join('')}</tbody></table>`).join('') || '<p>No data</p>';
  $('#historyArea').innerHTML = `<table><thead><tr><th>Date</th><th>Result</th><th>Old</th><th>New</th><th>Note</th></tr></thead><tbody>${state.markHistory.map(h=>`<tr><td>${h.edited_at}</td><td>${h.result_id}</td><td>${h.old_total ?? '-'}</td><td>${h.new_total ?? '-'}</td><td>${h.edit_note ?? '-'}</td></tr>`).join('')}</tbody></table>`;
}
function renderTeachers(){ $('#teacherWrap').innerHTML = `<table><thead><tr><th>Name</th><th>Subject</th><th>Class</th><th>Attendance</th></tr></thead><tbody>${state.teachers.map(t=>`<tr><td>${t.full_name}</td><td>${t.subject_name}</td><td>${t.class_name}</td><td>${t.attendance_percent}%</td></tr>`).join('')}</tbody></table>`; }
function renderAnnouncements(){ const now = new Date().toISOString().slice(0,10); $('#announcementList').innerHTML = state.announcements.map(a=>`<article class='announcement ${a.is_important?'important':''} ${a.expiry_date&&a.expiry_date<now?'notice-expired':''}'><h4>${a.title}</h4><p>${a.body}</p><small>Expiry: ${a.expiry_date||'N/A'}</small></article>`).join('') || '<p>No announcements.</p>'; }
function renderMessages(){ $('#messageWrap').innerHTML = `<table><thead><tr><th>From</th><th>Message</th><th>Status</th><th>Reply</th></tr></thead><tbody>${state.messages.map(m=>`<tr><td>${m.sender_name} (${m.sender_role})</td><td>${m.body}</td><td>${m.status}</td><td>${m.admin_reply||''}${adminOnly()&&m.status!=='Replied'?`<button class='btn btn-outline' data-reply='${m.id}'>Reply</button>`:''}</td></tr>`).join('')}</tbody></table>`; }
function renderPayroll(){ const total=state.payrolls.reduce((a,b)=>a+Number(b.net_amount||0),0); const paid=state.payrolls.filter(p=>p.status==='Paid').length; $('#payrollSummary').innerHTML=`<article class='stat-card'><h3>Total Salary</h3><strong>${total.toFixed(2)}</strong></article><article class='stat-card'><h3>Paid Entries</h3><strong>${paid}</strong></article>`; $('#payrollWrap').innerHTML=`<table><thead><tr><th>Staff</th><th>Month</th><th>Basic</th><th>Allowance</th><th>Deduction</th><th>Net</th><th>Status</th><th>Action</th></tr></thead><tbody>${state.payrolls.map(p=>`<tr><td>${p.teacher_name||p.staff_name}</td><td>${p.month_key}</td><td>${p.basic_amount}</td><td>${p.allowance}</td><td>${p.deduction}</td><td>${p.net_amount}</td><td>${p.status}</td><td>${adminOnly()?`<button class='btn btn-outline' data-del-payroll='${p.id}'>Delete</button>`:''}</td></tr>`).join('')}</tbody></table>`; }

function refresh(){ renderDashboard(); renderStudents(); renderResults(); renderAttendance(); renderMarkbook(); renderTeachers(); renderAnnouncements(); renderMessages(); renderPayroll(); }
async function load(){ const r=await api('bootstrap'); Object.assign(state,{madrasas:r.madrasas,students:r.students,results:r.results,announcements:r.announcements,teachers:r.teachers,attendance:r.attendance,markHistory:r.markHistory,messages:r.messages,payrolls:r.payrolls}); if(!state.user) state.user=r.user; $('#madrasaSelect').innerHTML=state.madrasas.map(m=>`<option value='${m.id}' ${Number(m.id)===Number(state.madrasaId)?'selected':''}>${m.name}</option>`).join(''); applyRole(); refresh(); }
function modal(html){ const d=$('#entityDialog'); d.innerHTML=`<div class='modal'>${html}</div>`; d.showModal(); }

function bind(){
  $$('.tab-btn').forEach(b=>b.onclick=()=>{$$('.tab-btn').forEach(x=>x.classList.remove('active')); b.classList.add('active'); $$('.tab-panel').forEach(x=>x.classList.remove('active')); $('#'+b.dataset.tab).classList.add('active');});
  $('#themeToggle').onclick=()=>document.body.classList.toggle('dark');
  $('#madrasaSelect').onchange=async e=>{state.madrasaId=Number(e.target.value); await load();};
  $('#globalSearch').oninput=e=>{$('#studentSearch').value=e.target.value; renderStudents();};
  $('#studentSearch').oninput=renderStudents; $('#classFilter').onchange=renderStudents; $('#genderFilter').onchange=renderStudents;
  $('#attendanceDate').value=new Date().toISOString().slice(0,10); $('#attendanceMonth').value=new Date().toISOString().slice(0,7);
  $('#loadAttendanceBtn').onclick=renderAttendance;

  $('#authToggle').onclick=async()=>{ if(state.user){await api('logout'); state.user=null; applyRole();} else document.querySelector('[data-tab="admin"]').click(); };
  $('#loginForm').onsubmit=async e=>{ e.preventDefault(); try{const r=await api('login','POST',{role:$('#roleSelect').value,username:$('#username').value,password:$('#password').value}); state.user=r.user; applyRole(); }catch(err){alert(err.message);} };

  $('#addMadrasaBtn').onclick=()=>{ if(!adminOnly()) return; modal(`<h3>Add Madrasa</h3><form id='madForm'><input name='name' required placeholder='Madrasa name'><input name='location' placeholder='Location'><button class='btn btn-primary'>Save</button></form>`); $('#madForm').onsubmit=async e=>{e.preventDefault(); const f=new FormData(e.target); await api('madrasa_create','POST',{name:f.get('name'),location:f.get('location')}); $('#entityDialog').close(); await load();}; };

  $('#addStudentBtn').onclick=()=>{ if(!canEdit()) return; modal(`<h3>Add Student</h3><form id='studentForm'><input name='full_name' required placeholder='Full name'><input name='class_name' required placeholder='Class'><select name='gender'><option>Boy</option><option>Girl</option></select><input name='attendance_percent' type='number' min='0' max='100' value='0'><input name='photo' type='file' accept='image/*'><button class='btn btn-primary'>Save</button></form>`); $('#studentForm').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target);let photoPath=null;if(f.get('photo')?.size){const uf=new FormData();uf.append('photo',f.get('photo')); const u=await api('upload_photo','POST',uf,true); photoPath=u.path;} await api('student_save','POST',{madrasa_id:state.madrasaId,full_name:f.get('full_name'),class_name:f.get('class_name'),gender:f.get('gender'),attendance_percent:Number(f.get('attendance_percent')||0),photo_path:photoPath}); $('#entityDialog').close(); await load();}; };
  $('#addTeacherBtn').onclick=()=>{ if(!adminOnly()) return; modal(`<h3>Add Teacher</h3><form id='teacherForm'><input name='full_name' required><input name='subject_name' required><input name='class_name' required><input name='attendance_percent' type='number' value='100'><button class='btn btn-primary'>Save</button></form>`); $('#teacherForm').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target); await api('teacher_save','POST',{madrasa_id:state.madrasaId,full_name:f.get('full_name'),subject_name:f.get('subject_name'),class_name:f.get('class_name'),attendance_percent:Number(f.get('attendance_percent')||100)}); $('#entityDialog').close(); await load();}; };
  $('#addAnnouncementBtn').onclick=()=>{ if(!adminOnly()) return; modal(`<h3>Add Announcement</h3><form id='annForm'><input name='title' required><textarea name='body' required></textarea><input name='expiry_date' type='date'><label><input type='checkbox' name='is_important'> Important</label><button class='btn btn-primary'>Save</button></form>`); $('#annForm').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target); await api('announcement_save','POST',{madrasa_id:state.madrasaId,title:f.get('title'),body:f.get('body'),expiry_date:f.get('expiry_date'),is_important:!!f.get('is_important')}); $('#entityDialog').close(); await load();}; };
  $('#sendMessageBtn').onclick=()=>{ modal(`<h3>Send Message</h3><form id='msgForm'><textarea name='body' required placeholder='Type message to admin'></textarea><button class='btn btn-primary'>Send</button></form>`); $('#msgForm').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target); await api('message_send','POST',{madrasa_id:state.madrasaId,body:f.get('body')}); $('#entityDialog').close(); await load();}; };
  $('#addPayrollBtn').onclick=()=>{ if(!adminOnly()) return; modal(`<h3>Add Salary Entry</h3><form id='payForm'><input name='staff_name' required placeholder='Staff/Teacher Name'><input name='month_key' type='month' required><input name='basic_amount' type='number' required placeholder='Basic'><input name='allowance' type='number' value='0'><input name='deduction' type='number' value='0'><select name='status'><option>Unpaid</option><option>Paid</option></select><button class='btn btn-primary'>Save</button></form>`); $('#payForm').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target); await api('payroll_save','POST',{madrasa_id:state.madrasaId,teacher_id:null,staff_name:f.get('staff_name'),month_key:f.get('month_key'),basic_amount:Number(f.get('basic_amount')),allowance:Number(f.get('allowance')||0),deduction:Number(f.get('deduction')||0),status:f.get('status')}); $('#entityDialog').close(); await load();}; };
  $('#addResultBtn').onclick=()=>{ if(!canEdit()) return; modal(`<h3>Add / Edit Result</h3><form id='resultForm'><input name='exam_name' value='Main Exam' required><select name='exam_type'><option>Midterm</option><option>Annual</option></select><input name='student_uid' required placeholder='Student UID'><input name='marks_math' type='number' required><input name='marks_science' type='number' required><input name='marks_english' type='number' required><button class='btn btn-primary'>Save</button></form>`); $('#resultForm').onsubmit=async e=>{e.preventDefault();const f=new FormData(e.target);const student=state.students.find(s=>s.student_uid===f.get('student_uid')); if(!student) return alert('Student UID not found'); await api('result_save','POST',{madrasa_id:state.madrasaId,exam_name:f.get('exam_name'),exam_type:f.get('exam_type'),student_id:student.id,marks_math:Number(f.get('marks_math')),marks_science:Number(f.get('marks_science')),marks_english:Number(f.get('marks_english'))}); $('#entityDialog').close(); await load();}; };

  $('#saveAttendanceBtn').onclick=async()=>{ if(!canEdit()) return; const date=$('#attendanceDate').value || new Date().toISOString().slice(0,10); const rows=$$('[data-att-student]').map(el=>({student_id:Number(el.dataset.attStudent),status:el.value})); await api('attendance_save','POST',{madrasa_id:state.madrasaId,attendance_date:date,rows}); await load(); };

  $('#backupJson').onclick=async()=>{ const data=await api('backup_json'); const a=document.createElement('a'); a.href=URL.createObjectURL(new Blob([JSON.stringify(data,null,2)],{type:'application/json'})); a.download='madrasa-backup.json'; a.click(); };
  $('#exportCsvAll').onclick=()=>{ const rows=[['type','id','name_or_title','extra'],...state.students.map(s=>['student',s.id,s.full_name,s.class_name]),...state.teachers.map(t=>['teacher',t.id,t.full_name,t.subject_name]),...state.results.map(r=>['result',r.id,r.student_uid,`${r.total_marks}`]),...state.messages.map(m=>['message',m.id,m.sender_name,m.status]),...state.payrolls.map(p=>['salary',p.id,p.staff_name,p.net_amount])]; const csv=rows.map(r=>r.join(',')).join('\n'); const a=document.createElement('a'); a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv'})); a.download='portal-export-all.csv'; a.click(); };
  $('#importJson').onchange=async e=>{ const text=await e.target.files[0].text(); await api('import_json','POST',JSON.parse(text)); await load(); };
  $('#printMarkbook').onclick=()=>window.print();
  $('#pdfMarkbook').onclick=()=>html2pdf().set({filename:`markbook-${state.madrasaId}.pdf`}).from($('#markbookArea')).save();

  document.body.addEventListener('click', async e=>{
    if (e.target.dataset.profile) { const s=state.students.find(x=>Number(x.id)===Number(e.target.dataset.profile)); $('#studentProfileArea').innerHTML=`<article class='student-profile'><img class='avatar' src='${s.photo_path || 'https://placehold.co/90x90'}'><div><h3>${s.full_name}</h3><p><strong>ID:</strong> ${s.student_uid}</p><p><strong>Class:</strong> ${s.class_name}</p><p><strong>Attendance:</strong> ${s.attendance_percent}%</p></div></article>`; }
    if (e.target.dataset.edit && canEdit()) { const s=state.students.find(x=>Number(x.id)===Number(e.target.dataset.edit)); modal(`<h3>Edit Student</h3><form id='editForm'><input name='full_name' value='${s.full_name}' required><input name='class_name' value='${s.class_name}' required><select name='gender'><option ${s.gender==='Boy'?'selected':''}>Boy</option><option ${s.gender==='Girl'?'selected':''}>Girl</option></select><input name='attendance_percent' type='number' value='${s.attendance_percent}'><button class='btn btn-primary'>Update</button></form>`); $('#editForm').onsubmit=async ev=>{ev.preventDefault();const f=new FormData(ev.target); await api('student_save','POST',{id:s.id,full_name:f.get('full_name'),class_name:f.get('class_name'),gender:f.get('gender'),attendance_percent:Number(f.get('attendance_percent')),photo_path:s.photo_path}); $('#entityDialog').close(); await load();}; }
    if (e.target.dataset.delete && adminOnly() && confirm('Delete student?')) { await api('student_delete','POST',{id:Number(e.target.dataset.delete)}); await load(); }
    if (e.target.dataset.report) { const s=state.students.find(x=>Number(x.id)===Number(e.target.dataset.report)); const r=await fetch(`api.php?action=report_card&student_id=${s.id}`).then(x=>x.json()); const html=`<div><h2>Report Card</h2><p><strong>${s.full_name}</strong> (${s.student_uid})</p><table><thead><tr><th>Exam</th><th>Type</th><th>Total</th><th>Grade</th><th>Rank</th></tr></thead><tbody>${r.rows.map(v=>`<tr><td>${v.exam_name}</td><td>${v.exam_type}</td><td>${v.total_marks}</td><td>${v.grade}</td><td>${v.rank_position||'-'}</td></tr>`).join('')}</tbody></table></div>`; html2pdf().set({filename:`${s.student_uid}-report-card.pdf`}).from(html).save(); }
    if (e.target.dataset.reply && adminOnly()) { const id=Number(e.target.dataset.reply); modal(`<h3>Reply Message</h3><form id='replyForm'><textarea name='admin_reply' required></textarea><button class='btn btn-primary'>Reply</button></form>`); $('#replyForm').onsubmit=async ev=>{ev.preventDefault();const f=new FormData(ev.target); await api('message_reply','POST',{id,admin_reply:f.get('admin_reply')}); $('#entityDialog').close(); await load();}; }
    if (e.target.dataset.delPayroll && adminOnly()) { await api('payroll_delete','POST',{id:Number(e.target.dataset.delPayroll)}); await load(); }
    if (e.target.dataset.editResult && canEdit()) { const r=state.results.find(x=>Number(x.id)===Number(e.target.dataset.editResult)); modal(`<h3>Edit Result</h3><form id='editResultForm'><input name='marks_math' type='number' value='${r.marks_math}' required><input name='marks_science' type='number' value='${r.marks_science}' required><input name='marks_english' type='number' value='${r.marks_english}' required><input name='edit_note' placeholder='Edit note'><button class='btn btn-primary'>Update</button></form>`); $('#editResultForm').onsubmit=async ev=>{ev.preventDefault();const f=new FormData(ev.target); await api('result_save','POST',{id:r.id,exam_id:r.exam_id,marks_math:Number(f.get('marks_math')),marks_science:Number(f.get('marks_science')),marks_english:Number(f.get('marks_english')),edit_note:f.get('edit_note')||'Edited from result table'}); $('#entityDialog').close(); await load();}; }
  });
}

bind();
load();
