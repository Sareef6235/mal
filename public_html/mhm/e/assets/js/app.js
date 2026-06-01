const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

function toast(message, type = 'success') {
    const stack = $('#toastStack');
    if (!stack) return alert(message);
    const item = document.createElement('div');
    item.className = `toast ${type === 'error' ? 'error' : ''}`;
    item.textContent = message;
    stack.appendChild(item);
    setTimeout(() => item.remove(), 4200);
}

function setTheme(theme) {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem('ocr-theme', theme);
}
setTheme(localStorage.getItem('ocr-theme') || 'light');

$$('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'));
});

$('[data-toggle-sidebar]')?.addEventListener('click', () => $('#sidebar')?.classList.toggle('open'));

document.addEventListener('click', (event) => {
    const openTarget = event.target.closest('[data-open-modal]');
    if (openTarget) {
        const modal = document.getElementById(openTarget.dataset.openModal);
        modal?.classList.add('show');
        modal?.setAttribute('aria-hidden', 'false');
    }
    if (event.target.closest('[data-close-modal]') || event.target.classList.contains('modal')) {
        event.target.closest('.modal')?.classList.remove('show');
        $$('.modal.show').forEach((modal) => modal.classList.remove('show'));
    }
});

const fileInput = $('#fileInput');
const dropzone = $('.dropzone');
$('[data-browse-files]')?.addEventListener('click', () => fileInput?.click());

function renderFiles(files) {
    const list = $('#fileList');
    if (!list) return;
    list.innerHTML = '';
    [...files].forEach((file) => {
        const pill = document.createElement('div');
        pill.className = 'file-pill';
        pill.textContent = `${file.name} · ${(file.size / 1024).toFixed(1)} KB`;
        list.appendChild(pill);
    });
}
fileInput?.addEventListener('change', () => renderFiles(fileInput.files));
['dragenter', 'dragover'].forEach((name) => dropzone?.addEventListener(name, (event) => {
    event.preventDefault();
    dropzone.classList.add('dragover');
}));
['dragleave', 'drop'].forEach((name) => dropzone?.addEventListener(name, (event) => {
    event.preventDefault();
    dropzone.classList.remove('dragover');
}));
dropzone?.addEventListener('drop', (event) => {
    if (!fileInput) return;
    fileInput.files = event.dataTransfer.files;
    renderFiles(fileInput.files);
});

$('#uploadForm')?.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!fileInput?.files?.length) return toast('Choose at least one file first.', 'error');
    const progress = $('#uploadProgress');
    const formData = new FormData(event.currentTarget);
    const request = new XMLHttpRequest();
    request.open('POST', 'upload.php');
    request.setRequestHeader('X-CSRF-Token', csrf);
    request.upload.addEventListener('progress', (progressEvent) => {
        if (progressEvent.lengthComputable && progress) progress.style.width = `${Math.round((progressEvent.loaded / progressEvent.total) * 100)}%`;
    });
    request.onload = () => {
        if (progress) progress.style.width = '100%';
        try {
            const data = JSON.parse(request.responseText);
            if (!data.success) throw new Error(data.message || 'Upload failed.');
            const successful = data.results.filter((item) => item.raw_text || item.cleaned_text);
            const latest = successful[successful.length - 1] || data.results[data.results.length - 1];
            if (latest) {
                $('#rawText').value = latest.raw_text || latest.message || '';
                $('#cleanText').value = latest.cleaned_text || latest.raw_text || '';
                $('#sampleText') && ($('#sampleText').value = latest.raw_text || '');
                $('#modalTextPreview') && ($('#modalTextPreview').textContent = latest.cleaned_text || latest.raw_text || latest.message || '');
            }
            if (data.history) renderHistory(data.history);
            data.results.forEach((item) => toast(`${item.file}: ${item.message}`, item.success ? 'success' : 'error'));
        } catch (error) {
            toast(error.message, 'error');
        }
        setTimeout(() => { if (progress) progress.style.width = '0%'; }, 1200);
    };
    request.onerror = () => toast('Network error while uploading.', 'error');
    request.send(formData);
});

function renderHistory(items) {
    const list = $('#historyList');
    if (!list) return;
    list.innerHTML = items.map((item) => `<div class="history-item"><strong>${escapeHtml(item.original_name)}</strong><span>${escapeHtml(item.status)} · ${(Number(item.file_size) / 1024).toFixed(1)} KB · ${escapeHtml(item.created_at)}</span></div>`).join('');
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

$$('.tab').forEach((tab) => tab.addEventListener('click', () => {
    $$('.tab').forEach((item) => item.classList.remove('active'));
    $$('.tab-panel').forEach((panel) => panel.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById(tab.dataset.tab)?.classList.add('active');
}));

$('[data-copy-target]')?.addEventListener('click', async (event) => {
    const text = document.getElementById(event.currentTarget.dataset.copyTarget)?.value || '';
    await navigator.clipboard.writeText(text);
    toast('Copied to clipboard.');
});

$('[data-download-target]')?.addEventListener('click', (event) => {
    const text = document.getElementById(event.currentTarget.dataset.downloadTarget)?.value || '';
    const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `extracted-text-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.txt`;
    link.click();
    URL.revokeObjectURL(link.href);
});

$('#previewRegex')?.addEventListener('click', async () => {
    const text = $('#sampleText')?.value || $('#rawText')?.value || '';
    const body = new URLSearchParams({ csrf_token: csrf, action: 'preview', text });
    const response = await fetch('extract.php', { method: 'POST', body });
    const data = await response.json();
    if (!data.success) return toast(data.message || 'Preview failed.', 'error');
    $('#cleanText').value = data.cleaned_text;
    $('#modalTextPreview').textContent = data.cleaned_text;
    toast('Regex preview applied.');
});

$('#globalSearch')?.addEventListener('input', (event) => {
    const term = event.target.value.toLowerCase();
    $$('.history-item').forEach((item) => item.style.display = item.textContent.toLowerCase().includes(term) ? '' : 'none');
});

$('[data-open-rule-modal]')?.addEventListener('click', () => openRuleModal());
function openRuleModal(rule = null) {
    $('#ruleModalTitle') && ($('#ruleModalTitle').textContent = rule ? 'Edit regex rule' : 'Add regex rule');
    $('#ruleId') && ($('#ruleId').value = rule?.id || '');
    $('#ruleName') && ($('#ruleName').value = rule?.name || '');
    $('#rulePattern') && ($('#rulePattern').value = rule?.pattern || '');
    $('#ruleReplacement') && ($('#ruleReplacement').value = rule?.replacement || '');
    $('#ruleFlags') && ($('#ruleFlags').value = rule?.flags || 'u');
    $('#ruleSort') && ($('#ruleSort').value = rule?.sort_order || 100);
    $('#ruleEnabled') && ($('#ruleEnabled').checked = rule ? Number(rule.enabled) === 1 : true);
    $('#ruleModal')?.classList.add('show');
}

$$('[data-edit-rule]').forEach((button) => button.addEventListener('click', () => {
    const row = button.closest('tr');
    openRuleModal(JSON.parse(row.dataset.rule));
}));

$$('[data-delete-rule]').forEach((button) => button.addEventListener('click', async () => {
    if (!confirm('Delete this regex rule?')) return;
    const body = new URLSearchParams({ csrf_token: csrf, action: 'delete', id: button.dataset.deleteRule });
    const response = await fetch('regex_save.php', { method: 'POST', body });
    const data = await response.json();
    toast(data.message, data.success ? 'success' : 'error');
    if (data.success) location.reload();
}));

$('#ruleForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const response = await fetch('regex_save.php', { method: 'POST', body: new FormData(event.currentTarget) });
    const data = await response.json();
    toast(data.message, data.success ? 'success' : 'error');
    if (data.success) location.reload();
});

$('#testRegex')?.addEventListener('click', async () => {
    const body = new URLSearchParams({
        csrf_token: csrf,
        action: 'test',
        pattern: $('#testPattern')?.value || '',
        replacement: $('#testReplacement')?.value || '',
        flags: $('#testFlags')?.value || 'u',
        sample: $('#testSample')?.value || '',
    });
    const response = await fetch('regex_save.php', { method: 'POST', body });
    const data = await response.json();
    if (!data.success) return toast(data.message || 'Regex test failed.', 'error');
    $('#testOutput').textContent = `${data.result}\n\nReplacements: ${data.count}`;
    toast('Regex test completed.');
});

$('#settingsSearch')?.addEventListener('input', (event) => {
    const term = event.target.value.toLowerCase();
    $$('#rulesTable tbody tr').forEach((row) => row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none');
});
