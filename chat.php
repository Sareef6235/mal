<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$user = requireLogin();
$ticketId = (int) ($_GET['id'] ?? 0);
if ($ticketId <= 0) {
    flash('error', 'Invalid ticket selected.');
    redirect(($user['role'] ?? '') === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php');
}

if (function_exists('ticketWithMessages') && !ticketWithMessages($ticketId, $user)) {
    flash('error', 'Ticket not found.');
    redirect(($user['role'] ?? '') === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php');
}

$viewerRole = (($user['role'] ?? '') === 'admin') ? 'admin' : 'user';
$backUrl = $viewerRole === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php';
$websocketUrl = function_exists('config') ? trim((string) config('chat.websocket_url', '')) : '';

renderHead('Live Chat', 'Realtime support chat with unread alerts, seen markers, popup notifications, and live typing updates.');
?>
<body>
<style>
:root {
    --bg:#050816;
    --panel:#0f172a;
    --panel-soft:#172036;
    --accent:#22d3ee;
    --accent-strong:#38bdf8;
    --admin:#0f766e;
    --user:#1d4ed8;
    --text:#e2e8f0;
    --muted:#94a3b8;
    --success:#22c55e;
    --warning:#f59e0b;
    --danger:#fb7185;
}
*{box-sizing:border-box;cursor:none!important}
html,body{min-height:100%}
body{margin:0;font-family:Inter,Segoe UI,sans-serif;background:radial-gradient(circle at top,#0f172a 0%,#050816 60%,#020617 100%);color:var(--text);overflow:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(56,189,248,.12),transparent 35%),radial-gradient(circle at 80% 10%,rgba(34,197,94,.1),transparent 26%),radial-gradient(circle at 50% 90%,rgba(168,85,247,.08),transparent 30%);pointer-events:none}
.chat-shell{position:relative;z-index:1;min-height:100vh;display:grid;grid-template-rows:auto 1fr auto;max-width:1320px;margin:0 auto;padding:20px;gap:16px}
.chat-topbar,.composer,.glass-panel{background:rgba(15,23,42,.74);border:1px solid rgba(148,163,184,.16);backdrop-filter:blur(18px);box-shadow:0 22px 45px rgba(2,6,23,.45)}
.chat-topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 20px;border-radius:26px;position:sticky;top:20px;z-index:8}
.title-group{display:grid;gap:6px}
.eyebrow{font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#67e8f9}
.chat-status{display:flex;flex-wrap:wrap;gap:10px;font-size:13px;color:var(--muted)}
.status-pill,.control-pill,.link-btn,.send-btn,.mute-btn{display:inline-flex;align-items:center;gap:8px;border-radius:999px;border:1px solid rgba(148,163,184,.18);background:rgba(15,23,42,.68);padding:10px 14px;color:var(--text);text-decoration:none}
.status-pill.online{border-color:rgba(34,197,94,.35);color:#bbf7d0}
.status-pill.offline{border-color:rgba(251,113,133,.35);color:#fecdd3}
.control-cluster{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px;align-items:center}
.control-pill input[type="range"]{accent-color:var(--accent);width:110px}
.control-label{font-size:12px;color:var(--muted)}
.chat-main{display:grid;grid-template-columns:minmax(250px,320px) minmax(0,1fr);gap:16px;min-height:0}
.sidebar{padding:20px;border-radius:26px;display:grid;gap:16px;align-content:start}
.sidebar-card{padding:16px;border-radius:18px;background:rgba(15,23,42,.58);border:1px solid rgba(148,163,184,.12);display:grid;gap:10px}
.sidebar-card h2,.sidebar-card h3{margin:0;font-size:16px}
.sidebar-meta{display:grid;gap:10px}
.sidebar-meta div{display:flex;justify-content:space-between;gap:12px;font-size:13px;color:var(--muted)}
.sidebar-meta strong{color:var(--text)}
.chat-panel{display:grid;grid-template-rows:auto 1fr auto;min-height:0;padding:18px;border-radius:28px;gap:14px}
.panel-header{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
.live-badges{display:flex;flex-wrap:wrap;gap:10px}
.live-badge{padding:8px 12px;border-radius:999px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.28);font-size:12px}
.live-badge.hidden{display:none}
.chat-stream{overflow:auto;padding:8px 6px 18px;display:flex;flex-direction:column;gap:16px;scroll-behavior:smooth}
.message-row{display:flex;opacity:0;transform:translateY(10px);animation:rise .28s ease forwards}
.message-row.admin{justify-content:flex-end}
.message-row.user{justify-content:flex-start}
.message-bubble{position:relative;max-width:min(78%,740px);padding:14px 16px 28px;border-radius:24px;display:grid;gap:10px;border:1px solid rgba(148,163,184,.14)}
.message-row.admin .message-bubble{background:linear-gradient(135deg,rgba(15,118,110,.92),rgba(8,145,178,.9));border-top-right-radius:8px}
.message-row.user .message-bubble{background:linear-gradient(135deg,rgba(29,78,216,.94),rgba(30,64,175,.94));border-top-left-radius:8px}
.message-meta{display:flex;justify-content:space-between;gap:12px;font-size:12px;color:rgba(255,255,255,.84)}
.message-text{white-space:pre-wrap;word-break:break-word;line-height:1.6}
.message-footer{position:absolute;right:14px;bottom:8px;display:flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.82)}
.seen-indicator{margin-top:4px;font-size:11px;color:#bfdbfe;display:flex;align-items:center;gap:6px}
.system-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:rgba(15,23,42,.24);border:1px solid rgba(191,219,254,.25)}
.message-attachments{display:grid;gap:8px}
.attachment-card{background:rgba(15,23,42,.22);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:10px;display:grid;gap:8px}
.attachment-card img,.attachment-card video,.attachment-card audio{max-width:100%;border-radius:10px;background:rgba(15,23,42,.42)}
.attachment-link{color:#fff;font-size:13px;text-decoration:none}
.attachment-link:hover{text-decoration:underline}
.typing-indicator{min-height:24px;color:var(--muted);font-size:13px;padding-left:8px}
.typing-indicator.show{color:#c4b5fd}
.composer{padding:16px;border-radius:24px}
.composer form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end}
.composer-stack{display:grid;gap:10px}
.composer textarea{width:100%;min-height:66px;max-height:220px;resize:vertical;border:none;outline:none;border-radius:18px;padding:16px;background:rgba(15,23,42,.72);color:#fff;font-size:15px}
.composer-tools{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
.send-btn,.mute-btn,.link-btn{font-weight:700}
.send-btn{border:none;background:linear-gradient(135deg,var(--accent),var(--accent-strong));color:#082f49;padding:16px 22px;cursor:pointer}
.mute-btn.muted{opacity:.6}
.retry-banner{display:none;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:14px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);font-size:12px;color:#fde68a}
.retry-banner.show{display:flex}
.retry-btn{border:none;border-radius:999px;padding:8px 12px;background:rgba(245,158,11,.28);color:#fff;font-weight:700;cursor:pointer}
.toast-wrap{position:fixed;right:24px;bottom:24px;z-index:20;display:grid;gap:10px}
.toast{display:none;min-width:280px;max-width:360px;padding:16px 18px;border-radius:18px;background:rgba(15,23,42,.9);border:1px solid rgba(56,189,248,.28);box-shadow:0 20px 45px rgba(2,6,23,.45)}
.toast.show{display:grid}
.toast strong{font-size:14px}
.toast p{margin:4px 0 0;color:var(--muted);font-size:13px;line-height:1.45}
#cursor-core,#cursor-ring,.cursor-trail{position:fixed;top:0;left:0;pointer-events:none;border-radius:50%;z-index:9999;mix-blend-mode:screen;transform:translate(-50%,-50%)}
#cursor-core{width:10px;height:10px;background:#67e8f9;box-shadow:0 0 18px rgba(103,232,249,.9),0 0 28px rgba(34,211,238,.7)}
#cursor-ring{width:34px;height:34px;border:1px solid rgba(103,232,249,.9);box-shadow:0 0 24px rgba(34,211,238,.45)}
.cursor-trail{width:8px;height:8px;background:rgba(34,211,238,.45);box-shadow:0 0 14px rgba(34,211,238,.55);transition:transform .18s linear,opacity .25s ease}
.cursor-ripple{position:fixed;border:1px solid rgba(103,232,249,.65);border-radius:50%;pointer-events:none;transform:translate(-50%,-50%);animation:ripple .6s ease-out forwards;z-index:9998}
@keyframes rise{to{opacity:1;transform:translateY(0)}}
@keyframes ripple{from{width:0;height:0;opacity:.7}to{width:90px;height:90px;opacity:0}}
@media (max-width: 980px){body{overflow:auto}.chat-shell{padding:14px}.chat-main{grid-template-columns:1fr}.message-bubble{max-width:100%}.composer form{grid-template-columns:1fr}.control-cluster{justify-content:flex-start}}
</style>
<div class="chat-shell" data-ticket-id="<?= $ticketId ?>" data-viewer-role="<?= e($viewerRole) ?>" data-websocket-url="<?= e($websocketUrl) ?>">
    <header class="chat-topbar">
        <div class="title-group">
            <span class="eyebrow">Realtime support chat</span>
            <strong id="chat-title">Connecting…</strong>
            <div class="chat-status">
                <span class="status-pill" id="connection-pill">🟠 Reconnecting</span>
                <span class="status-pill offline" id="presence-pill">Offline</span>
                <span class="status-pill" id="typing-pill">No typing activity</span>
            </div>
        </div>
        <div class="control-cluster">
            <label class="control-pill" for="volume-control"><span class="control-label">🔥 Volume</span><input id="volume-control" type="range" min="0" max="1" step="0.05" value="0.75"></label>
            <button class="mute-btn" id="mute-toggle" type="button">🔊 Mute off</button>
            <a class="link-btn" href="/qwe1/ticket.php?id=<?= $ticketId ?>">📨 Ticket</a>
            <a class="link-btn" href="<?= e($backUrl) ?>">← Back</a>
        </div>
    </header>

    <main class="chat-main">
        <aside class="sidebar glass-panel">
            <div class="sidebar-card">
                <h2>Live system</h2>
                <div class="live-badges">
                    <span class="live-badge" id="unread-badge">Unread: 0</span>
                    <span class="live-badge" id="online-users-badge">Online users: 1</span>
                    <span class="live-badge hidden" id="websocket-badge">WebSocket active</span>
                    <span class="live-badge" id="viewer-badge"><?= $viewerRole === 'admin' ? 'Admin view' : 'Customer view' ?></span>
                </div>
            </div>
            <div class="sidebar-card">
                <h3>Seen record</h3>
                <div id="seen-summary">👁️ Seen ✔✔ system records will appear under delivered messages.</div>
            </div>
            <div class="sidebar-card">
                <h3>Ticket info</h3>
                <div class="sidebar-meta" id="ticket-meta">
                    <div><span>Code</span><strong>—</strong></div>
                    <div><span>Subject</span><strong>—</strong></div>
                    <div><span>Customer</span><strong>—</strong></div>
                    <div><span>Status</span><strong>—</strong></div>
                </div>
            </div>
        </aside>

        <section class="chat-panel glass-panel">
            <div class="panel-header">
                <div>
                    <strong>Silent auto refresh + popup alerts</strong>
                    <div class="chat-status">✔ ⚡ Auto refresh is running silently. Refresh happens in the background without a visible reload.</div>
                </div>
                <div class="live-badges">
                    <span class="live-badge hidden" id="popup-enabled">📱 Popup notification ready</span>
                </div>
            </div>
            <div class="chat-stream" id="chat-stream" aria-live="polite"></div>
            <div class="typing-indicator" id="typing-indicator"></div>
            <div class="composer">
                <div class="retry-banner" id="retry-banner"><span id="retry-copy">Message pending retry.</span><button class="retry-btn" id="retry-btn" type="button">Retry now</button></div>
                <form id="chat-form">
                    <div class="composer-stack">
                        <textarea id="chat-input" name="message" placeholder="Type your message…"></textarea>
                        <div class="composer-tools">
                            <span class="control-pill">⚡ Auto sync active</span>
                            <span class="control-pill" id="sound-mode-pill"><?= $viewerRole === 'admin' ? 'Admin sound profile' : 'Customer sound profile' ?></span>
                        </div>
                    </div>
                    <button class="send-btn" type="submit">Send</button>
                </form>
            </div>
        </section>
    </main>
</div>

<div class="toast-wrap">
    <div class="toast" id="chat-toast"><strong id="chat-toast-title">New activity</strong><p id="chat-toast-copy">A new message arrived.</p></div>
</div>
<audio id="admin-notification-sound" preload="auto" src="/qwe1/assets/notification.mp3"></audio>
<audio id="user-notification-sound" preload="auto" src="/qwe1/assets/user-notification.mp3"></audio>
<audio id="seen-notification-sound" preload="auto" src="/qwe1/assets/seen-notification.mp3"></audio>
<div id="cursor-core"></div>
<div id="cursor-ring"></div>
<script>
(() => {
  const shell = document.querySelector('.chat-shell');
  const ticketId = Number(shell?.dataset.ticketId || 0);
  const viewerRole = shell?.dataset.viewerRole || 'user';
  const websocketUrl = shell?.dataset.websocketUrl || '';
  const stream = document.getElementById('chat-stream');
  const form = document.getElementById('chat-form');
  const input = document.getElementById('chat-input');
  const chatTitle = document.getElementById('chat-title');
  const ticketMeta = document.getElementById('ticket-meta');
  const typingIndicator = document.getElementById('typing-indicator');
  const typingPill = document.getElementById('typing-pill');
  const presencePill = document.getElementById('presence-pill');
  const connectionPill = document.getElementById('connection-pill');
  const unreadBadge = document.getElementById('unread-badge');
  const websocketBadge = document.getElementById('websocket-badge');
  const onlineUsersBadge = document.getElementById('online-users-badge');
  const popupEnabled = document.getElementById('popup-enabled');
  const seenSummary = document.getElementById('seen-summary');
  const toast = document.getElementById('chat-toast');
  const toastTitle = document.getElementById('chat-toast-title');
  const toastCopy = document.getElementById('chat-toast-copy');
  const volumeControl = document.getElementById('volume-control');
  const muteToggle = document.getElementById('mute-toggle');
  const adminSound = document.getElementById('admin-notification-sound');
  const userSound = document.getElementById('user-notification-sound');
  const seenSound = document.getElementById('seen-notification-sound');
  const soundModePill = document.getElementById('sound-mode-pill');
  const retryBanner = document.getElementById('retry-banner');
  const retryCopy = document.getElementById('retry-copy');
  const retryBtn = document.getElementById('retry-btn');
  const cursorCore = document.getElementById('cursor-core');
  const cursorRing = document.getElementById('cursor-ring');
  let lastMessageId = 0;
  let lastSeenSignature = '';
  let pollTimer = null;
  let typingTimer = null;
  let stopTypingTimer = null;
  let websocketConnected = false;
  let settings = { volume: 0.75, muted: false };
  const pendingQueue = [];
  let sendingNow = false;
  const trails = [];
  const audioContext = window.AudioContext ? new AudioContext() : (window.webkitAudioContext ? new webkitAudioContext() : null);

  const escapeHtml = (value) => String(value ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  const getStorageKey = (key) => `live-chat-${viewerRole}-${key}`;
  const otherRoleLabel = () => viewerRole === 'admin' ? 'Customer' : 'Admin';
  const incomingSoundNode = () => viewerRole === 'admin' ? adminSound : userSound;
  const showToast = (title, copy) => {
    toastTitle.textContent = title;
    toastCopy.textContent = copy;
    toast.classList.add('show');
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => toast.classList.remove('show'), 3600);
  };
  const updateRetryUi = () => {
    if (!retryBanner || !retryCopy) return;
    if (!pendingQueue.length) {
      retryBanner.classList.remove('show');
      return;
    }
    const failed = pendingQueue.filter((item) => item.failed).length;
    retryCopy.textContent = failed ? `${failed} message(s) failed. Tap retry.` : `${pendingQueue.length} message(s) sending…`;
    retryBanner.classList.add('show');
  };
  const queueMessage = (text) => {
    pendingQueue.push({ id: Date.now() + Math.random(), text, failed: false, tries: 0 });
    updateRetryUi();
  };
  const processQueue = async () => {
    if (sendingNow || !pendingQueue.length) return;
    sendingNow = true;
    const current = pendingQueue[0];
    current.tries += 1;
    try {
      const response = await fetch('/qwe1/chat_send.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ticket_id: ticketId, message: current.text })
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      pendingQueue.shift();
      updateRetryUi();
      await sendState('typing', { is_typing: false });
      await loadMessages();
      if (pendingQueue.length) window.setTimeout(processQueue, 180);
    } catch (error) {
      current.failed = true;
      updateRetryUi();
      showToast('Send failed', 'Message queued. Retry will continue automatically.');
      window.setTimeout(() => { sendingNow = false; processQueue(); }, 3500);
      return;
    }
    sendingNow = false;
  };
  const loadSettings = () => {
    try {
      settings.volume = Number(localStorage.getItem(getStorageKey('volume')) || '0.75') || 0.75;
      settings.muted = localStorage.getItem(getStorageKey('muted')) === '1';
    } catch (error) {}
    volumeControl.value = String(settings.volume);
    muteToggle.classList.toggle('muted', settings.muted);
    muteToggle.textContent = settings.muted ? '🔇 Muted' : '🔊 Mute off';
    [adminSound, userSound, seenSound].forEach((node) => {
      if (node) node.volume = settings.volume;
    });
  };
  const persistSettings = () => {
    try {
      localStorage.setItem(getStorageKey('volume'), String(settings.volume));
      localStorage.setItem(getStorageKey('muted'), settings.muted ? '1' : '0');
    } catch (error) {}
  };
  const playSynth = async (frequency) => {
    if (!audioContext || settings.muted) return;
    try {
      if (audioContext.state === 'suspended') await audioContext.resume();
      const oscillator = audioContext.createOscillator();
      const gain = audioContext.createGain();
      oscillator.type = 'sine';
      oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
      gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
      gain.gain.exponentialRampToValueAtTime(Math.max(0.02, settings.volume * 0.08), audioContext.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.28);
      oscillator.connect(gain);
      gain.connect(audioContext.destination);
      oscillator.start();
      oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {}
  };
  const playAudio = async (node, fallbackFrequency) => {
    if (settings.muted) return;
    if (node) {
      try {
        node.volume = settings.volume;
        node.currentTime = 0;
        await node.play();
        return;
      } catch (error) {}
    }
    await playSynth(fallbackFrequency);
  };
  const sendState = async (action, extra = {}) => {
    try {
      await fetch('/qwe1/chat_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ticket_id: ticketId, action, ...extra })
      });
    } catch (error) {}
  };
  const notifyBrowser = (title, body) => {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try { new Notification(title, { body }); } catch (error) {}
  };
  const renderAttachment = (attachment) => {
    const type = String(attachment?.type || 'file').toLowerCase();
    const path = escapeHtml(attachment?.path || '#');
    const name = escapeHtml(attachment?.name || 'Attachment');
    const mime = escapeHtml(attachment?.mime || '');
    let media = '';
    if (type === 'image') media = `<img src="${path}" alt="${name}">`;
    if (type === 'video') media = `<video controls preload="metadata"><source src="${path}" type="${mime || 'video/mp4'}"></video>`;
    if (type === 'audio') media = `<audio controls preload="metadata"><source src="${path}" type="${mime || 'audio/mpeg'}"></audio>`;
    return `<div class="attachment-card"><strong>${name}</strong>${media}<a class="attachment-link" href="${path}" target="_blank" rel="noopener">Open attachment</a></div>`;
  };
  const updateMeta = (ticket = {}) => {
    const customerName = ticket.customer_name || ticket.name || ticket.customer_email || 'Customer';
    chatTitle.textContent = `${ticket.ticket_code || `#${ticketId}`} • ${ticket.subject || 'Support ticket'}`;
    ticketMeta.innerHTML = `
      <div><span>Code</span><strong>${escapeHtml(ticket.ticket_code || `#${ticketId}`)}</strong></div>
      <div><span>Subject</span><strong>${escapeHtml(ticket.subject || 'Support ticket')}</strong></div>
      <div><span>Customer</span><strong>${escapeHtml(customerName)}</strong></div>
      <div><span>Status</span><strong>${escapeHtml(ticket.status || 'Live')}</strong></div>`;
  };
  const render = async (payload) => {
    const ticket = payload.ticket || {};
    const messages = Array.isArray(payload.messages) ? payload.messages : [];
    const presence = payload.presence || {};
    const unreadCount = Number(payload?.meta?.unread_count || 0);
    const customerName = ticket.customer_name || ticket.name || ticket.customer_email || 'Customer';
    updateMeta(ticket);
    unreadBadge.textContent = `Unread: ${unreadCount}`;
    presencePill.textContent = `${otherRoleLabel()} ${presence.other_online ? 'online' : 'offline'}`;
    presencePill.className = `status-pill ${presence.other_online ? 'online' : 'offline'}`;
    typingIndicator.textContent = presence.typing_active ? (presence.typing_label || `${otherRoleLabel()} is typing…`) : '';
    typingIndicator.classList.toggle('show', Boolean(presence.typing_active));
    typingPill.textContent = presence.typing_active ? (presence.typing_label || `${otherRoleLabel()} is typing…`) : 'No typing activity';
    if (onlineUsersBadge) {
      const onlineCount = Number(presence.online_users || (presence.other_online ? 2 : 1));
      onlineUsersBadge.textContent = `Online users: ${onlineCount}`;
    }
    stream.innerHTML = messages.map((message) => {
      const isAdmin = message.sender_role === 'admin';
      const attachments = Array.isArray(message.attachments) && message.attachments.length
        ? `<div class="message-attachments">${message.attachments.map(renderAttachment).join('')}</div>`
        : '';
      const text = escapeHtml(message.message_text || '').replace(/\n/g,'<br>');
      const seenMarker = message.seen_label ? `<div class="seen-indicator"><span class="system-pill">${escapeHtml(message.seen_label)}</span></div>` : '';
      const footerTick = !message.sent_by_viewer ? '' : (message.seen_at ? '✔✔ Seen' : (message.delivered_at ? '✔✔' : '✔'));
      return `<div class="message-row ${isAdmin ? 'admin' : 'user'}"><div class="message-bubble"><div class="message-meta"><strong>${escapeHtml(message.sender_name || (isAdmin ? 'Admin' : customerName))}</strong><span>${escapeHtml(message.created_at || '')}</span></div>${text ? `<div class="message-text">${text}</div>` : ''}${attachments}${seenMarker}<div class="message-footer">${footerTick}</div></div></div>`;
    }).join('');
    stream.scrollTop = stream.scrollHeight;

    const latest = messages[messages.length - 1];
    if (latest) {
      const latestId = Number(latest.id || 0);
      if (lastMessageId && latestId > lastMessageId && latest.sender_role !== viewerRole) {
        await playAudio(incomingSoundNode(), viewerRole === 'admin' ? 960 : 720);
        showToast(`${otherRoleLabel()} sent a message`, latest.message_text || 'New attachment or message received.');
        notifyBrowser(`${otherRoleLabel()} message`, latest.message_text || 'New ticket activity.');
      }
      lastMessageId = Math.max(lastMessageId, latestId);
    }

    const seenSignature = messages.filter((message) => message.sent_by_viewer && message.seen_at).map((message) => `${message.id}:${message.seen_at}`).join('|');
    if (seenSignature && seenSignature !== lastSeenSignature) {
      lastSeenSignature = seenSignature;
      seenSummary.textContent = '👁️ Seen ✔✔ system: recipient opened your latest delivered messages.';
      await playAudio(seenSound, 540);
    }
  };
  const refreshConnectionState = () => {
    connectionPill.textContent = websocketConnected ? '🟢 WebSocket live' : '🟢 Silent auto refresh';
    websocketBadge.classList.toggle('hidden', !websocketConnected);
  };
  const loadMessages = async () => {
    try {
      const response = await fetch(`/qwe1/chat_feed.php?id=${ticketId}`, { cache: 'no-store', headers: { 'Accept': 'application/json' } });
      if (!response.ok) return;
      const payload = await response.json();
      await render(payload);
    } catch (error) {
      connectionPill.textContent = '🟠 Reconnecting';
    }
  };
  const startPolling = () => {
    window.clearInterval(pollTimer);
    pollTimer = window.setInterval(loadMessages, 2500);
  };
  const connectRealtime = () => {
    if (!websocketUrl || !('WebSocket' in window)) {
      refreshConnectionState();
      return;
    }
    try {
      const socket = new WebSocket(websocketUrl);
      socket.addEventListener('open', () => {
        websocketConnected = true;
        refreshConnectionState();
      });
      socket.addEventListener('message', () => loadMessages());
      socket.addEventListener('close', () => {
        websocketConnected = false;
        refreshConnectionState();
      });
      socket.addEventListener('error', () => {
        websocketConnected = false;
        refreshConnectionState();
        socket.close();
      });
    } catch (error) {
      websocketConnected = false;
      refreshConnectionState();
    }
  };
  const handleTypingInput = () => {
    window.clearTimeout(typingTimer);
    window.clearTimeout(stopTypingTimer);
    typingTimer = window.setTimeout(() => sendState('typing', { is_typing: true }), 140);
    stopTypingTimer = window.setTimeout(() => sendState('typing', { is_typing: false }), 1800);
  };
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = input.value.trim();
    if (!message) return;
    queueMessage(message);
    input.value = '';
    processQueue();
  });
  input.addEventListener('input', handleTypingInput);
  input.addEventListener('blur', () => sendState('typing', { is_typing: false }));
  volumeControl.addEventListener('input', () => {
    settings.volume = Number(volumeControl.value || 0.75);
    persistSettings();
    loadSettings();
  });
  muteToggle.addEventListener('click', () => {
    settings.muted = !settings.muted;
    persistSettings();
    loadSettings();
  });
  retryBtn?.addEventListener('click', () => {
    pendingQueue.forEach((item) => { item.failed = false; });
    updateRetryUi();
    processQueue();
  });
  document.addEventListener('click', async () => {
    if ('Notification' in window && Notification.permission === 'default') {
      try {
        const permission = await Notification.requestPermission();
        popupEnabled.classList.toggle('hidden', permission !== 'granted');
      } catch (error) {}
    } else if ('Notification' in window && Notification.permission === 'granted') {
      popupEnabled.classList.remove('hidden');
    }
    if (audioContext && audioContext.state === 'suspended') {
      audioContext.resume().catch(() => {});
    }
  }, { once: true });
  const moveCursor = (event) => {
    cursorCore.style.transform = `translate(${event.clientX}px, ${event.clientY}px)`;
    cursorRing.style.transform = `translate(${event.clientX}px, ${event.clientY}px)`;
    const trail = document.createElement('div');
    trail.className = 'cursor-trail';
    trail.style.transform = `translate(${event.clientX}px, ${event.clientY}px)`;
    document.body.appendChild(trail);
    trails.push(trail);
    window.setTimeout(() => { trail.style.opacity = '0'; trail.remove(); }, 280);
    if (trails.length > 14) {
      const oldest = trails.shift();
      oldest?.remove();
    }
  };
  document.addEventListener('mousemove', moveCursor, { passive: true });
  document.addEventListener('click', (event) => {
    const ripple = document.createElement('div');
    ripple.className = 'cursor-ripple';
    ripple.style.left = `${event.clientX}px`;
    ripple.style.top = `${event.clientY}px`;
    document.body.appendChild(ripple);
    window.setTimeout(() => ripple.remove(), 620);
  });

  loadSettings();
  refreshConnectionState();
  loadMessages();
  startPolling();
  connectRealtime();
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/qwe1/service-worker.js').catch(() => {});
  }
  window.addEventListener('unhandledrejection', (event) => {
    if (String(event.reason || '').includes('runtime.lastError')) {
      event.preventDefault();
    }
  });
})();
</script>
</body>
</html>
