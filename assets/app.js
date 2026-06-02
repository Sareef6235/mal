const state = { folder: 'inbox', page: 1, loading: false, query: '', account: document.querySelector('.app-shell').dataset.account };
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const mailList = $('#mailList');
const skeleton = $('#skeleton');
const loadMore = $('#loadMore');
let refreshTimer;

init();

function init() {
    bindEvents();
    loadAccounts();
    loadStats();
    loadMessages(true);
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('service-worker.js');
    refreshTimer = setInterval(() => { loadStats(); loadMessages(true, true); }, 25000);
}

function bindEvents() {
    $$('.folder-nav button[data-folder], .bottom-nav button[data-folder]').forEach(button => button.addEventListener('click', () => switchFolder(button.dataset.folder)));
    $('#refreshButton').addEventListener('click', () => { loadStats(); loadMessages(true); });
    loadMore.addEventListener('click', () => loadMessages(false));
    $('#searchInput').addEventListener('input', debounce(event => { state.query = event.target.value.trim(); loadMessages(true); }, 280));
    $('[data-notify]').addEventListener('click', enableNotifications);
    $('.mobile-menu').addEventListener('click', () => $('.sidebar').classList.toggle('open'));
    $$('[data-compose]').forEach(button => button.addEventListener('click', () => openModal('#composeBackdrop')));
    $$('[data-close-compose]').forEach(button => button.addEventListener('click', () => closeModal('#composeBackdrop')));
    $$('[data-open-admin]').forEach(button => button.addEventListener('click', () => openModal('#adminBackdrop')));
    $('[data-close-admin]').addEventListener('click', () => closeModal('#adminBackdrop'));
    $('[data-close-viewer]').addEventListener('click', () => closeModal('#viewerBackdrop'));
    $('#accountSwitch').addEventListener('change', event => { state.account = event.target.value; loadStats(); loadMessages(true); });
}

async function api(action, params = {}, options = {}) {
    const url = new URL('api.php', window.location.href);
    url.searchParams.set('action', action);
    url.searchParams.set('account', state.account);
    Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
    const response = await fetch(url, options);
    if (!response.ok) throw new Error('API request failed');
    return response.json();
}

async function loadMessages(reset = false, silent = false) {
    if (state.loading) return;
    state.loading = true;
    if (reset) { state.page = 1; if (!silent) mailList.innerHTML = ''; }
    skeleton.hidden = silent;
    try {
        const data = await api('messages', { folder: state.folder, page: state.page, limit: 20, q: state.query });
        if (reset) mailList.innerHTML = '';
        if (!data.messages?.length) mailList.append($('#emptyState').content.cloneNode(true));
        data.messages.forEach(message => mailList.append(renderMessage(message)));
        loadMore.hidden = !data.hasMore;
        $('[data-count="inbox"]').textContent = data.total ?? 0;
        state.page += 1;
        if (data.messages?.some(message => message.unread) && silent) notifyNewMail(data.messages[0]);
    } catch (error) {
        mailList.innerHTML = `<div class="empty-state"><strong>Mailbox unavailable</strong><span>${error.message}</span></div>`;
    } finally {
        skeleton.hidden = true;
        state.loading = false;
    }
}

async function loadStats() {
    try {
        const stats = await api('stats');
        $('#totalMails').textContent = stats.total;
        $('#unreadMails').textContent = stats.unread;
        $('#sentMails').textContent = stats.sent;
        $('#lastSync').textContent = stats.uptime;
        $('#badgeCounter').textContent = stats.unread;
        $('[data-count="sent"]').textContent = stats.sent;
    } catch (_) {}
}

async function loadAccounts() {
    const data = await api('accounts');
    const select = $('#accountSwitch');
    const list = $('#accountList');
    select.innerHTML = '';
    list.innerHTML = '';
    data.accounts.forEach(account => {
        select.add(new Option(account.email, account.email));
        list.insertAdjacentHTML('beforeend', `<p><strong>${escapeHtml(account.label)}</strong><br><small>${escapeHtml(account.email)}</small></p>`);
    });
}

function renderMessage(message) {
    const card = document.createElement('article');
    card.className = 'mail-card';
    card.dataset.uid = message.uid;
    card.innerHTML = `
        <div class="sender-avatar">${initials(message.sender)}</div>
        <div class="mail-meta">
            <div class="mail-head"><strong>${escapeHtml(message.sender)}</strong>${message.unread ? '<i class="blue-dot"></i>' : ''}${message.hasAttachments ? '<span title="Attachment">📎</span>' : ''}</div>
            <div class="mail-subject">${message.starred ? '⭐ ' : ''}${escapeHtml(message.subject)}</div>
            <div class="mail-preview">${escapeHtml(message.preview || '')}</div>
        </div>
        <div class="mail-side">
            <time>${escapeHtml(message.time || '')}</time>
            <div class="hover-actions">
                <button data-action="reply" title="Reply">↩</button>
                <button data-action="star" title="Star">⭐</button>
                <button data-action="delete" title="Delete">🗑</button>
            </div>
        </div>`;
    card.addEventListener('click', event => {
        const action = event.target.closest('button')?.dataset.action;
        if (action === 'delete') return deleteMessage(message.uid, card);
        if (action === 'star') return flagMessage(message.uid, 'star', !message.starred);
        openMessage(message.uid);
    });
    addSwipeDelete(card, message.uid);
    return card;
}

async function openMessage(uid) {
    openModal('#viewerBackdrop');
    $('#viewerContent').innerHTML = '<div class="skeleton-list"><i></i><i></i><i></i></div>';
    const message = await api('message', { folder: state.folder, uid });
    $('#viewerContent').innerHTML = `
        <header class="viewer-header">
            <h2 id="viewerSubject">${escapeHtml(message.subject)}</h2>
            <div class="sender-line"><div class="sender-avatar">${initials(message.sender)}</div><div><strong>${escapeHtml(message.sender)}</strong><br><span>${escapeHtml(message.senderEmail || '')} · ${escapeHtml(message.time || '')}</span></div></div>
        </header>
        <div class="message-body">${message.body || escapeHtml(message.text || '')}</div>
        <div class="attachments">${(message.attachments || []).map(file => `<div class="attachment"><span>📎 ${escapeHtml(file.name)}</span><button class="ghost">Preview</button></div>`).join('')}</div>
        <section class="reply-box"><h3>Quick reply</h3><textarea placeholder="Type your rich reply..."></textarea><br><br><button class="compose">Send reply</button></section>`;
    loadStats();
}

async function deleteMessage(uid, card) {
    card.style.opacity = '.45';
    await api('delete', { folder: state.folder, uid }, { method: 'POST' });
    card.remove();
    loadStats();
}

async function flagMessage(uid, flag, enabled) {
    await api('flag', { folder: state.folder, uid }, { method: 'POST', body: new URLSearchParams({ flag, enabled }) });
    loadMessages(true);
}

function switchFolder(folder) {
    state.folder = folder;
    $('#folderTitle').textContent = folder.charAt(0).toUpperCase() + folder.slice(1);
    $$('.folder-nav button, .bottom-nav button').forEach(button => button.classList.toggle('active', button.dataset.folder === folder));
    $('.sidebar').classList.remove('open');
    loadMessages(true);
}

async function enableNotifications() {
    if (!('Notification' in window)) return;
    await Notification.requestPermission();
}

function notifyNewMail(message) {
    if ('Notification' in window && Notification.permission === 'granted') new Notification('New mail', { body: `${message.sender}: ${message.subject}`, icon: 'assets/icon.svg' });
}

function addSwipeDelete(card, uid) {
    let startX = 0;
    card.addEventListener('touchstart', event => { startX = event.touches[0].clientX; }, { passive: true });
    card.addEventListener('touchend', event => { if (startX - event.changedTouches[0].clientX > 90) deleteMessage(uid, card); }, { passive: true });
}
function openModal(selector) { $(selector).hidden = false; document.body.style.overflow = 'hidden'; }
function closeModal(selector) { $(selector).hidden = true; document.body.style.overflow = ''; }
function initials(value = '') { return escapeHtml(value.trim().split(/\s+/).slice(0,2).map(word => word[0]).join('').toUpperCase() || 'M'); }
function escapeHtml(value = '') { return String(value).replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char])); }
function debounce(fn, wait) { let timer; return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), wait); }; }
window.addEventListener('beforeunload', () => clearInterval(refreshTimer));
