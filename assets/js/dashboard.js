const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const $ = (selector, scope = document) => scope.querySelector(selector);
const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];

function toast(message, type = 'success') {
  const host = $('#toastHost');
  const node = document.createElement('div');
  node.className = `toast ${type}`;
  node.textContent = message;
  host.appendChild(node);
  setTimeout(() => node.remove(), 4200);
}

async function postForm(url, form, loadingText = 'Saving…') {
  const submit = form.querySelector('[type="submit"]');
  const original = submit?.textContent;
  if (submit) { submit.disabled = true; submit.textContent = loadingText; }
  try {
    const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: new FormData(form) });
    const json = await response.json();
    if (!response.ok || !json.success) throw new Error(json.message || 'Request failed');
    toast(json.message || 'Saved successfully');
    if (window.confetti) confetti({ particleCount: 90, spread: 70, origin: { y: .72 } });
    return json;
  } catch (error) {
    toast(error.message, 'error');
    if (window.Swal) Swal.fire({ icon: 'error', title: 'Action needed', text: error.message, background: '#0f172a', color: '#fff' });
  } finally {
    if (submit) { submit.disabled = false; submit.textContent = original; }
  }
}

function openModal(id) { const modal = document.getElementById(id); if (modal) { modal.classList.add('active'); document.body.classList.add('modal-open'); } }
function closeModals() { $$('.modal.active').forEach(m => m.classList.remove('active')); document.body.classList.remove('modal-open'); }

function initModals() {
  $$('[data-modal]').forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.modal)));
  $$('[data-close]').forEach(btn => btn.addEventListener('click', closeModals));
  $$('.modal').forEach(modal => modal.addEventListener('click', e => { if (e.target === modal) closeModals(); }));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModals(); });
}

function initTheme() {
  $('.theme-toggle')?.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
    document.documentElement.dataset.theme = next;
    localStorage.setItem('dashboardTheme', next);
    toast(`${next[0].toUpperCase() + next.slice(1)} theme enabled`);
  });
  const saved = localStorage.getItem('dashboardTheme');
  if (saved) document.documentElement.dataset.theme = saved;
}

function initClockGreeting() {
  const clock = $('#liveClock');
  const greeting = $('#greeting');
  const update = () => {
    const now = new Date();
    clock.textContent = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(now);
    const hour = now.getHours();
    greeting.textContent = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
  };
  update(); setInterval(update, 1000);
}

function initCounters() {
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el = entry.target; const target = Number(el.dataset.counter || 0); let value = 0;
    const step = Math.max(1, Math.ceil(target / 42));
    const timer = setInterval(() => { value = Math.min(target, value + step); el.textContent = value; if (value >= target) clearInterval(timer); }, 24);
    observer.unobserve(el);
  }), { threshold: .6 });
  $$('[data-counter]').forEach(el => observer.observe(el));
}

function initReveal() {
  const observer = new IntersectionObserver(entries => entries.forEach(e => e.target.classList.toggle('visible', e.isIntersecting)), { threshold: .12 });
  $$('.reveal').forEach(el => observer.observe(el));
}

function initCursor() {
  const glow = $('.mouse-glow'); const dot = $('.cursor-dot');
  document.addEventListener('pointermove', e => {
    if (glow) { glow.style.left = `${e.clientX}px`; glow.style.top = `${e.clientY}px`; }
    if (dot) { dot.style.left = `${e.clientX}px`; dot.style.top = `${e.clientY}px`; }
  });
}

function initForms() {
  $('#profileForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const json = await postForm('api/update_profile.php', e.currentTarget);
    if (json?.success) localStorage.removeItem('profileDraft');
  });
  $('#passwordForm')?.addEventListener('submit', async e => { e.preventDefault(); await postForm('api/change_password.php', e.currentTarget, 'Updating…'); });
  $('#saveSettings')?.addEventListener('click', () => toast('Preferences saved in this UI. Wire api/update_settings.php for persistence.'));
}

function initPasswordStrength() {
  const input = $('#newPassword'); const bar = $('.strength span'); const label = $('.strength b');
  input?.addEventListener('input', () => {
    const value = input.value;
    let score = Math.min(100, value.length * 8 + (/[A-Z]/.test(value) ? 14 : 0) + (/\d/.test(value) ? 14 : 0) + (/[^A-Za-z0-9]/.test(value) ? 18 : 0));
    bar.style.width = `${score}%`; bar.style.background = score > 75 ? '#22c55e' : score > 45 ? '#f59e0b' : '#ef4444';
    label.textContent = score > 75 ? 'Strong' : score > 45 ? 'Medium' : 'Weak';
  });
}

function initAutosave() {
  $$('form[data-autosave]').forEach(form => {
    const key = form.dataset.autosave;
    const saved = JSON.parse(localStorage.getItem(key) || '{}');
    Object.entries(saved).forEach(([name, value]) => { const field = form.elements[name]; if (field && !field.value) field.value = value; });
    form.addEventListener('input', () => {
      const data = Object.fromEntries(new FormData(form)); delete data.csrf_token;
      localStorage.setItem(key, JSON.stringify(data));
      clearTimeout(form._draftTimer); form._draftTimer = setTimeout(() => toast('Draft autosaved'), 400);
    });
  });
}

function initUpload() {
  const form = $('#uploadForm'); const input = form?.querySelector('input[type="file"]'); const preview = $('#imagePreview'); const drop = $('#dropzone');
  $$('.upload-trigger').forEach(trigger => trigger.addEventListener('click', () => { $('#uploadType').value = trigger.dataset.target || 'avatar'; openModal('uploadModal'); }));
  input?.addEventListener('change', () => previewFile(input.files[0]));
  ['dragenter', 'dragover'].forEach(eventName => drop?.addEventListener(eventName, e => { e.preventDefault(); drop.classList.add('drag'); }));
  ['dragleave', 'drop'].forEach(eventName => drop?.addEventListener(eventName, e => { e.preventDefault(); drop.classList.remove('drag'); }));
  drop?.addEventListener('drop', e => { input.files = e.dataTransfer.files; previewFile(input.files[0]); });
  form?.addEventListener('submit', async e => { e.preventDefault(); const json = await postForm('api/upload_image.php', form, 'Uploading…'); if (json?.url) setTimeout(() => location.reload(), 700); });
  function previewFile(file) {
    if (!file) return; const reader = new FileReader();
    reader.onload = () => { preview.src = reader.result; preview.style.display = 'block'; };
    reader.readAsDataURL(file);
  }
}

function initDeleteLogout() {
  const confirm = $('#deleteConfirm'); const button = $('#deleteAccountBtn');
  confirm?.addEventListener('input', () => { button.disabled = confirm.value !== 'DELETE'; });
  button?.addEventListener('click', () => Swal.fire({ title: 'Account deletion queued', text: 'This demo UI does not delete data until backend policy approval.', icon: 'warning', background: '#0f172a', color: '#fff' }));
  $$('[data-nav="Logout"]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); openModal('logoutModal'); }));
}

function initChart() {
  const canvas = $('#performanceChart');
  if (!canvas || !window.Chart) return;
  new Chart(canvas, { type: 'line', data: { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], datasets: [{ label: 'Score', data: [72, 78, 81, 87, 84, 91, 96], borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,.14)', tension: .45, fill: true }, { label: 'Focus', data: [62, 66, 70, 74, 80, 86, 88], borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.1)', tension: .45, fill: true }] }, options: { responsive: true, plugins: { legend: { labels: { color: '#cbd5e1' } } }, scales: { x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.12)' } }, y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.12)' } } } } });
}

function initMisc() {
  $('.menu-toggle')?.addEventListener('click', () => $('.sidebar')?.classList.toggle('open'));
  $('.top-link')?.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
  $$('[data-toast]').forEach(el => el.addEventListener('click', () => toast(el.dataset.toast)));
  $$('.primary-btn,.ghost-btn,.danger-btn,.fab,.icon-btn').forEach(btn => btn.addEventListener('pointerdown', e => {
    const ripple = document.createElement('span'); ripple.style.cssText = `position:absolute;left:${e.offsetX}px;top:${e.offsetY}px;width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.45);transform:translate(-50%,-50%);animation:ripple .55s ease-out forwards;pointer-events:none`; btn.appendChild(ripple); setTimeout(() => ripple.remove(), 600);
  }));
  const style = document.createElement('style'); style.textContent = '@keyframes ripple{to{width:220px;height:220px;opacity:0}}'; document.head.appendChild(style);
}

document.addEventListener('DOMContentLoaded', () => {
  initModals(); initTheme(); initClockGreeting(); initCounters(); initReveal(); initCursor(); initForms(); initPasswordStrength(); initAutosave(); initUpload(); initDeleteLogout(); initChart(); initMisc();
  setTimeout(() => toast('Premium profile dashboard loaded'), 500);
});
