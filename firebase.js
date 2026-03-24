// firebase.js: message rendering, send flow, Firebase realtime + polling fallback.
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js';
import { getDatabase, ref, onChildAdded, onValue, set } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-database.js';

const cfg = window.APP_CONFIG;
const runtime = window.chatRuntime;
const els = {
  list: document.getElementById('messages'),
  form: document.getElementById('chatForm'),
  input: document.getElementById('messageInput'),
  typing: document.getElementById('typingIndicator'),
  status: document.getElementById('onlineStatus')
};

let latestId = 0;
let seenMap = new Set();
let pollingTimer = null;

const escapeHtml = (s) => s
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;');

function addMessage(msg) {
  if (seenMap.has(msg.id)) return;
  seenMap.add(msg.id);
  latestId = Math.max(latestId, Number(msg.id) || 0);

  const own = Number(msg.user_id) === Number(cfg.userId);
  const wrap = document.createElement('article');
  wrap.className = `bubble ${own ? 'outgoing' : 'incoming'}`;
  wrap.innerHTML = `<div>${escapeHtml(msg.message)}</div>
    <div class="meta">
      <time>${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</time>
      ${own ? `<span class="ticks ${msg.seen ? 'seen' : ''}">${msg.seen ? '✔✔' : '✔'}</span>` : ''}
    </div>`;
  els.list.appendChild(wrap);
  els.list.scrollTop = els.list.scrollHeight;

  if (own) runtime.playOutgoing();
  else {
    runtime.playIncoming();
    runtime.toast('New message received');
    runtime.notify(msg.message);
  }

  if (own && msg.seen) runtime.playSeen();
}

async function sendMessage(message) {
  const fd = new FormData();
  fd.append('ticket_id', cfg.ticketId);
  fd.append('user_id', String(cfg.userId));
  fd.append('message', message);

  const res = await fetch(cfg.endpoints.send, { method: 'POST', body: fd, credentials: 'same-origin' });
  const data = await res.json();
  if (!data.ok) throw new Error(data.message || 'Send failed');
}

async function pollFetch() {
  if (!runtime.state.autoSync) return;
  try {
    const q = new URLSearchParams({ ticket_id: cfg.ticketId, since_id: String(latestId) });
    const res = await fetch(`${cfg.endpoints.fetch}?${q}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (data.ok && Array.isArray(data.messages)) data.messages.forEach(addMessage);
  } catch {
    // keep silent, next poll will retry
  }
}

function startPollingFallback(reason) {
  if (pollingTimer) return;
  els.status.textContent = `Realtime unavailable (${reason}), using polling every 2s`;
  pollingTimer = setInterval(pollFetch, 2000);
  pollFetch();
}

function initFirebaseRealtime() {
  try {
    const app = initializeApp(cfg.firebase);
    const db = getDatabase(app);

    const messagesRef = ref(db, `messages/${cfg.ticketId}`);
    onChildAdded(messagesRef, snapshot => {
      const val = snapshot.val();
      if (val) addMessage(val);
      els.status.textContent = 'Online · Firebase sync active';
    }, () => startPollingFallback('listener error'));

    const typingRef = ref(db, `typing/${cfg.ticketId}`);
    onValue(typingRef, snapshot => {
      const val = snapshot.val() || {};
      const othersTyping = Object.entries(val).some(([uid, typing]) => Number(uid) !== Number(cfg.userId) && typing);
      els.typing.textContent = othersTyping ? 'User is typing…' : '';
    }, () => startPollingFallback('typing sync error'));

    let typingTimer;
    els.input.addEventListener('input', async () => {
      try {
        await set(ref(db, `typing/${cfg.ticketId}/${cfg.userId}`), true);
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
          set(ref(db, `typing/${cfg.ticketId}/${cfg.userId}`), false).catch(() => {});
        }, 1200);
      } catch {
        // If typing sync fails, fallback handles UX.
      }
    });

    return true;
  } catch {
    startPollingFallback('init failure');
    return false;
  }
}

els.form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const message = els.input.value.trim();
  if (!message) return;
  els.input.value = '';
  try {
    await sendMessage(message);
  } catch (err) {
    runtime.toast(err.message || 'Failed to send message');
  }
});

// Initial load + presence ping.
pollFetch();
const ok = initFirebaseRealtime();
if (!ok) startPollingFallback('disabled');
