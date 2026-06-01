const regexRules = [
  {
    name: 'Lesson Plan Parser',
    pattern: '(Month|Class|Week|Total Period|Subject|Lesson Name|Lesson Details|Activities|Smart Date|Exam Date):\\s*(.+)',
    description: 'Extracts structured lesson planning fields from OCR text.',
    active: true,
  },
  {
    name: 'Exam Schedule Parser',
    pattern: '(Exam Date|Subject|Class|Week):\\s*(.+)',
    description: 'Finds exam schedule fields in scanned planning documents.',
    active: true,
  },
  {
    name: 'Generic Key Value Parser',
    pattern: '([A-Za-z ]+):\\s*(.+)',
    description: 'Fallback parser for mixed PDF layouts and image OCR output.',
    active: false,
  },
];

const parsedRows = [
  ['Ramadan', 'Hifz Level 3', '2', '6', 'Quran Recitation', 'Surah Al-Mulk Revision', 'Tajweed practice with makharij review', 'Pair recitation', '2026-03-11', '2026-03-18'],
  ['Shaaban', 'Alim 1', '4', '5', 'Fiqh', 'Purification', 'Wudu, ghusl, and practical rulings', 'Demonstration', '2026-02-20', '2026-02-27'],
  ['Rajab', 'Arabic 2', '1', '4', 'Arabic Grammar', 'Nominal Sentences', 'Mubtada and khabar examples', 'Worksheet', '2026-01-08', '2026-01-15'],
  ['Muharram', 'Hifz Level 1', '3', '6', 'Memorization', 'Surah An-Naba', 'Ayah 1-20 with fluency goals', 'Group revision', '2026-07-24', '2026-07-31'],
];

const historyItems = [
  ['2026-05-28', 'Admin Aisha', 'ramadan-plan.pdf', '42', 'Success'],
  ['2026-05-27', 'Ustadh Omar', 'hifz-scan.png', '16', 'Pending'],
  ['2026-05-25', 'Admin Zayd', 'exam-week.jpg', '28', 'Failed'],
];

let selectedRegex = 0;

const qs = (selector) => document.querySelector(selector);
const qsa = (selector) => [...document.querySelectorAll(selector)];

function escapeHtml(value) {
  return value.replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
}

function renderRegexList(filter = '') {
  const list = qs('#regexList');
  const filteredRules = regexRules
    .map((rule, index) => ({ ...rule, index }))
    .filter((rule) => `${rule.name} ${rule.description}`.toLowerCase().includes(filter.toLowerCase()));

  list.innerHTML = filteredRules.map((rule) => `
    <article class="regex-item ${rule.index === selectedRegex ? 'selected' : ''}" tabindex="0" data-index="${rule.index}">
      <header>
        <strong>${escapeHtml(rule.name)}</strong>
        <span class="badge ${rule.active ? 'text-bg-success' : 'text-bg-secondary'}">${rule.active ? 'Active' : 'Inactive'}</span>
      </header>
      <p>${escapeHtml(rule.description)}</p>
    </article>
  `).join('') || '<div class="empty-state">No regex rules found.</div>';
}

function loadRegex(index) {
  selectedRegex = index;
  const rule = regexRules[index];
  qs('#regexName').value = rule.name;
  qs('#regexPattern').value = rule.pattern;
  qs('#sandboxPattern').value = rule.pattern;
  qs('#regexDescription').value = rule.description;
  qs('#regexStatus').value = rule.active ? 'Active' : 'Inactive';
  renderRegexList(qs('#regexSearch').value);
}

function renderRows(rows = parsedRows) {
  qs('#previewTable tbody').innerHTML = rows.map((row) => `
    <tr>${row.map((cell) => `<td contenteditable="true">${escapeHtml(cell)}</td>`).join('')}
      <td><button class="btn btn-danger-soft btn-sm delete-row" type="button" aria-label="Delete row"><i class="fa-regular fa-trash-can"></i></button></td>
    </tr>
  `).join('');
}

function renderHistory() {
  qs('#historyList').innerHTML = historyItems.map(([date, user, file, records, status]) => `
    <article class="history-item">
      <span class="timeline-dot"></span>
      <div>
        <strong>${escapeHtml(file)}</strong>
        <small class="d-block text-muted">${date} · ${escapeHtml(user)} · ${records} records imported</small>
      </div>
      <div class="history-actions">
        <span class="badge ${status === 'Success' ? 'text-bg-success' : status === 'Failed' ? 'text-bg-danger' : 'text-bg-warning'}">${status}</span>
        <button class="btn btn-soft btn-sm" type="button">View</button>
        <button class="btn btn-soft btn-sm" type="button">Reprocess</button>
        <button class="btn btn-danger-soft btn-sm" type="button">Delete</button>
      </div>
    </article>
  `).join('');
}

function updateTextMetrics() {
  const text = qs('#extractedText').value;
  qs('#charCount').textContent = `${text.length.toLocaleString()} characters`;
  qs('#lineCount').textContent = `${text ? text.split(/\r\n|\r|\n/).length : 0} lines`;
}

function testRegex() {
  const text = qs('#sandboxText').value;
  const pattern = qs('#sandboxPattern').value;
  const highlighted = qs('#highlightedMatches');
  const captures = qs('#captureGroups');

  try {
    const regex = new RegExp(pattern, 'gim');
    const matches = [...text.matchAll(regex)];
    qs('#matchCount').textContent = `${matches.length} ${matches.length === 1 ? 'match' : 'matches'}`;
    highlighted.innerHTML = matches.length ? escapeHtml(text).replace(regex, (match) => `<mark>${escapeHtml(match)}</mark>`) : 'No matches found.';
    captures.innerHTML = matches.map((match, index) => `
      <div class="capture-group"><strong>Match ${index + 1}</strong>: ${match.slice(1).map((group, groupIndex) => `Group ${groupIndex + 1} = "${escapeHtml(group || '')}"`).join(' · ')}</div>
    `).join('');
  } catch (error) {
    qs('#matchCount').textContent = 'Invalid regex';
    highlighted.textContent = error.message;
    captures.innerHTML = '';
  }
}

function handleFile(file) {
  if (!file) return;
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
  const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
  const extension = file.name.split('.').pop().toLowerCase();
  const valid = allowedTypes.includes(file.type) || allowedExtensions.includes(extension);
  const preview = qs('#filePreview');

  if (!valid) {
    preview.innerHTML = '<div class="text-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>Unsupported file type. Please upload PDF, JPG, JPEG, or PNG.</div>';
    return;
  }

  const size = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
  preview.classList.remove('empty-state');
  preview.innerHTML = `
    <div class="file-card">
      <span class="file-icon"><i class="fa-solid ${extension === 'pdf' ? 'fa-file-pdf' : 'fa-file-image'}"></i></span>
      <div>
        <strong>${escapeHtml(file.name)}</strong>
        <div class="d-flex gap-2 flex-wrap mt-1"><span class="badge text-bg-light">${extension.toUpperCase()}</span><span class="text-muted small">${size}</span></div>
        <div class="progress mt-2" role="progressbar" aria-valuenow="84" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar progress-84"></div></div>
      </div>
      <button class="icon-btn" type="button" id="removeFile" aria-label="Remove file"><i class="fa-solid fa-xmark"></i></button>
    </div>`;

  const docPreview = qs('#documentPreview');
  if (file.type.startsWith('image/')) {
    const url = URL.createObjectURL(file);
    docPreview.innerHTML = `<img src="${url}" alt="Uploaded document preview"><p class="mt-2 mb-0">${escapeHtml(file.name)}</p>`;
  } else {
    docPreview.innerHTML = `<i class="fa-regular fa-file-pdf"></i><p>${escapeHtml(file.name)} ready for PDF text extraction.</p>`;
  }

  qs('#removeFile').addEventListener('click', () => {
    qs('#fileInput').value = '';
    preview.classList.add('empty-state');
    preview.innerHTML = '<i class="fa-regular fa-file-lines"></i><p>No file selected yet. Upload a document to preview OCR extraction.</p>';
    docPreview.innerHTML = '<i class="fa-regular fa-file-pdf"></i><p>Uploaded PDF/image preview appears here.</p>';
  });
}

function openModal(modal) {
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
  modal.querySelector('button, input, select, textarea')?.focus();
}

function closeModal(modal) {
  modal.hidden = true;
  document.body.style.overflow = '';
}

function initEvents() {
  const uploadZone = qs('#uploadZone');
  const fileInput = qs('#fileInput');
  uploadZone.addEventListener('click', () => fileInput.click());
  uploadZone.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') fileInput.click();
  });
  fileInput.addEventListener('change', (event) => handleFile(event.target.files[0]));
  ['dragenter', 'dragover'].forEach((eventName) => uploadZone.addEventListener(eventName, (event) => {
    event.preventDefault();
    uploadZone.classList.add('dragover');
  }));
  ['dragleave', 'drop'].forEach((eventName) => uploadZone.addEventListener(eventName, (event) => {
    event.preventDefault();
    uploadZone.classList.remove('dragover');
  }));
  uploadZone.addEventListener('drop', (event) => handleFile(event.dataTransfer.files[0]));

  qs('#extractedText').addEventListener('input', updateTextMetrics);
  qs('#copyText').addEventListener('click', async () => navigator.clipboard.writeText(qs('#extractedText').value));
  qs('#clearText').addEventListener('click', () => { qs('#extractedText').value = ''; updateTextMetrics(); });
  qs('#fullscreenText').addEventListener('click', () => qs('#extractedText').requestFullscreen?.());

  qs('#regexSearch').addEventListener('input', (event) => renderRegexList(event.target.value));
  qs('#regexList').addEventListener('click', (event) => {
    const item = event.target.closest('.regex-item');
    if (item) loadRegex(Number(item.dataset.index));
  });
  qs('#regexList').addEventListener('keydown', (event) => {
    const item = event.target.closest('.regex-item');
    if (item && (event.key === 'Enter' || event.key === ' ')) loadRegex(Number(item.dataset.index));
  });
  qs('#createRegex').addEventListener('click', () => {
    regexRules.push({ name: 'New Regex Rule', pattern: '(.+)', description: 'Describe this parsing rule.', active: false });
    loadRegex(regexRules.length - 1);
  });
  qs('#saveRegex').addEventListener('click', () => {
    regexRules[selectedRegex] = {
      name: qs('#regexName').value,
      pattern: qs('#regexPattern').value,
      description: qs('#regexDescription').value,
      active: qs('#regexStatus').value === 'Active',
    };
    qs('#sandboxPattern').value = qs('#regexPattern').value;
    renderRegexList(qs('#regexSearch').value);
  });
  qs('#cloneRegex').addEventListener('click', () => {
    regexRules.push({ ...regexRules[selectedRegex], name: `${regexRules[selectedRegex].name} Copy`, active: false });
    loadRegex(regexRules.length - 1);
  });
  qs('#toggleRegex').addEventListener('click', () => {
    regexRules[selectedRegex].active = !regexRules[selectedRegex].active;
    loadRegex(selectedRegex);
  });
  qs('#deleteRegex').addEventListener('click', () => {
    if (regexRules.length === 1) return;
    regexRules.splice(selectedRegex, 1);
    loadRegex(Math.max(0, selectedRegex - 1));
  });
  qs('#resetRegex').addEventListener('click', () => loadRegex(selectedRegex));
  qs('#testRegex').addEventListener('click', testRegex);

  qs('#addRow').addEventListener('click', () => {
    parsedRows.push(['New Month', 'New Class', '1', '0', 'Subject', 'Lesson Name', 'Details', 'Activities', 'YYYY-MM-DD', 'YYYY-MM-DD']);
    renderRows();
  });
  qs('#previewTable').addEventListener('click', (event) => {
    if (event.target.closest('.delete-row')) event.target.closest('tr').remove();
  });
  qs('#tableSearch').addEventListener('input', (event) => {
    const term = event.target.value.toLowerCase();
    renderRows(parsedRows.filter((row) => row.join(' ').toLowerCase().includes(term)));
  });

  qs('#menuToggle').addEventListener('click', () => {
    qs('#sidebar').classList.add('open');
    qs('#mobileBackdrop').classList.add('show');
  });
  qsa('[data-sidebar-close]').forEach((button) => button.addEventListener('click', () => {
    qs('#sidebar').classList.remove('open');
    qs('#mobileBackdrop').classList.remove('show');
  }));
  qs('#themeToggle').addEventListener('click', () => {
    const html = document.documentElement;
    const dark = html.dataset.bsTheme === 'dark';
    html.dataset.bsTheme = dark ? 'light' : 'dark';
    qs('#themeToggle i').className = dark ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
  });

  qsa('[data-modal-target]').forEach((trigger) => trigger.addEventListener('click', () => openModal(qs(`#${trigger.dataset.modalTarget}`))));
  qsa('[data-modal-close]').forEach((trigger) => trigger.addEventListener('click', () => closeModal(trigger.closest('.premium-modal'))));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') qsa('.premium-modal:not([hidden])').forEach(closeModal);
  });
}

renderRegexList();
renderRows();
renderHistory();
updateTextMetrics();
testRegex();
initEvents();
