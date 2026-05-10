const qs = (selector, scope = document) => scope.querySelector(selector);
const qsa = (selector, scope = document) => [...scope.querySelectorAll(selector)];

function toast(message, icon = 'success') {
    if (window.Swal) {
        Swal.fire({ toast: true, position: 'top-end', timer: 2600, showConfirmButton: false, icon, title: message, background: '#0f172a', color: '#fff' });
    } else {
        alert(message);
    }
}

qsa('.btn-premium, .btn-ghost').forEach((button) => {
    button.addEventListener('click', (event) => {
        const rect = button.getBoundingClientRect();
        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        ripple.style.width = ripple.style.height = `${Math.max(rect.width, rect.height)}px`;
        ripple.style.left = `${event.clientX - rect.left - rect.width / 2}px`;
        ripple.style.top = `${event.clientY - rect.top - rect.height / 2}px`;
        button.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});

const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => entry.isIntersecting && entry.target.classList.add('visible'));
}, { threshold: 0.12 });
qsa('.reveal').forEach((el) => observer.observe(el));

qs('[data-sidebar-toggle]')?.addEventListener('click', () => qs('.sidebar')?.classList.toggle('open'));

function materialCard(material) {
    const thumbnail = material.thumbnail_path || (material.file_type === 'image' ? material.file_path : '');
    const preview = thumbnail
        ? `<img src="${thumbnail}" alt="${material.title}">`
        : `<i class="bi ${material.icon} file-icon"></i>`;
    return `<div class="col"><article class="material-card glass p-3 reveal visible">
        <div class="preview-tile mb-3">${preview}</div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
            <span class="badge-soft" style="border-color:${material.subject_color}66">${material.subject_name}</span>
            <span class="badge-soft">${material.class_name}</span>
            ${material.is_featured == 1 ? '<span class="badge bg-warning text-dark rounded-pill">Featured</span>' : ''}
        </div>
        <h5 class="fw-bold mb-2">${material.title}</h5>
        <p class="text-muted-premium small mb-3">${material.description || 'Premium study material ready for preview and download.'}</p>
        <div class="d-flex justify-content-between text-muted-premium small mb-3">
            <span><i class="bi bi-eye"></i> ${material.views_count}</span>
            <span><i class="bi bi-download"></i> ${material.downloads_count}</span>
            <span>${material.created_at}</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-sm btn-premium" href="view.php?id=${material.id}"><i class="bi bi-eye"></i> View</a>
            <a class="btn btn-sm btn-ghost" href="download.php?id=${material.id}"><i class="bi bi-download"></i></a>
            <button class="btn btn-sm btn-ghost" data-favorite="${material.id}"><i class="bi bi-star"></i></button>
            <button class="btn btn-sm btn-ghost" data-share="${material.id}" data-title="${material.title}"><i class="bi bi-share"></i></button>
        </div>
    </article></div>`;
}

let searchTimer;
function runSearch() {
    const grid = qs('#materialsGrid');
    if (!grid) return;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        const params = new URLSearchParams(new FormData(qs('#filterForm')));
        grid.innerHTML = Array.from({ length: 6 }, () => '<div class="col"><div class="skeleton" style="height:390px"></div></div>').join('');
        const response = await fetch(`api/materials.php?${params.toString()}`);
        const data = await response.json();
        grid.innerHTML = data.materials.length ? data.materials.map(materialCard).join('') : '<div class="col-12"><div class="glass p-5 rounded-5 text-center text-muted-premium">No materials matched your filters.</div></div>';
    }, 260);
}
qsa('#filterForm input, #filterForm select').forEach((input) => input.addEventListener('input', runSearch));

async function postAction(url, formData = new FormData()) {
    const token = qs('meta[name="csrf-token"]')?.content;
    if (token) formData.append('csrf_token', token);
    const response = await fetch(url, { method: 'POST', body: formData });
    return response.json();
}

document.addEventListener('click', async (event) => {
    const fav = event.target.closest('[data-favorite]');
    if (fav) {
        const form = new FormData();
        form.append('material_id', fav.dataset.favorite);
        const data = await postAction('api/favorite.php', form);
        toast(data.message, data.ok ? 'success' : 'error');
    }
    const share = event.target.closest('[data-share]');
    if (share) {
        const url = `${location.origin}${location.pathname.replace(/[^/]*$/, '')}view.php?id=${share.dataset.share}`;
        if (navigator.share) await navigator.share({ title: share.dataset.title, url });
        else { await navigator.clipboard.writeText(url); toast('Share link copied'); }
    }
});

const dropZone = qs('#dropZone');
const fileInput = qs('#fileInput');
if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());
    ['dragenter', 'dragover'].forEach((name) => dropZone.addEventListener(name, (event) => { event.preventDefault(); dropZone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach((name) => dropZone.addEventListener(name, (event) => { event.preventDefault(); dropZone.classList.remove('dragover'); }));
    dropZone.addEventListener('drop', (event) => { fileInput.files = event.dataTransfer.files; renderFilePreview(fileInput.files); });
    fileInput.addEventListener('change', () => renderFilePreview(fileInput.files));
}
function renderFilePreview(files) {
    const list = qs('#filePreviewList');
    if (!list) return;
    list.innerHTML = [...files].map(file => `<div class="glass p-3 rounded-4 mb-2 d-flex justify-content-between"><span><i class="bi bi-file-earmark"></i> ${file.name}</span><span class="text-muted-premium">${(file.size / 1048576).toFixed(2)} MB</span></div>`).join('');
}

qs('#uploadForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const progress = qs('#uploadProgress');
    const bar = progress?.querySelector('.progress-bar');
    progress?.classList.remove('d-none');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);
    xhr.upload.addEventListener('progress', (event) => {
        if (event.lengthComputable && bar) bar.style.width = `${Math.round((event.loaded / event.total) * 100)}%`;
    });
    xhr.onload = () => {
        const data = JSON.parse(xhr.responseText || '{}');
        if (data.ok) {
            toast(data.message);
            Swal?.fire({ icon: 'success', title: 'Upload complete', text: data.message, background: '#0f172a', color: '#fff' });
            form.reset();
            qs('#filePreviewList').innerHTML = '';
        } else toast(data.message || 'Upload failed', 'error');
    };
    xhr.send(new FormData(form));
});
