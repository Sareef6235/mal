<?php require_once __DIR__ . '/config.php'; $csrf = csrf_token(); ?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <title><?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="assets/styles.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar glass" id="sidebar">
    <div class="brand"><span class="brand-mark"><i class="fa-solid fa-mosque"></i></span><div><strong>Madrasa ERP</strong><small>Import Intelligence</small></div></div>
    <nav class="menu">
      <a href="#dashboard"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
      <a href="#students"><i class="fa-solid fa-user-graduate"></i><span>Students</span></a>
      <a href="#classes"><i class="fa-solid fa-school"></i><span>Classes</span></a>
      <a class="active" href="#ocr"><i class="fa-solid fa-file-import"></i><span>OCR Import Center</span></a>
      <a href="#plans"><i class="fa-solid fa-calendar-days"></i><span>Lesson Plans</span></a>
      <a href="#reports"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a>
      <button class="menu-button" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="fa-solid fa-gear"></i><span>Settings</span></button>
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </nav>
  </aside>
  <main class="content">
    <header class="topbar glass">
      <button class="icon-btn" id="mobileMenu"><i class="fa-solid fa-bars"></i></button>
      <button class="icon-btn d-none d-lg-inline-grid" id="collapseMenu"><i class="fa-solid fa-sidebar"></i></button>
      <div><p class="eyebrow">Enterprise OCR</p><h1>Intelligent OCR PDF & Image Import Center</h1></div>
      <button class="btn btn-premium ripple" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="fa-solid fa-sliders"></i> Settings</button>
    </header>

    <section class="stats-grid" id="statsGrid">
      <article class="stat-card glass"><i class="fa-solid fa-cloud-arrow-up"></i><span>Total Imports</span><strong data-stat="imports">0</strong></article>
      <article class="stat-card glass"><i class="fa-solid fa-table-list"></i><span>Imported Rows</span><strong data-stat="imported_rows">0</strong></article>
      <article class="stat-card glass"><i class="fa-solid fa-code"></i><span>Active Regex</span><strong data-stat="active_rules">0</strong></article>
      <article class="stat-card glass"><i class="fa-solid fa-triangle-exclamation"></i><span>Failed Imports</span><strong data-stat="failed_imports">0</strong></article>
    </section>

    <section class="grid-2 fade-in" id="ocr">
      <div class="panel glass">
        <div class="panel-head"><div><p class="eyebrow">Upload</p><h2>Drag & Drop Source File</h2></div><span class="badge text-bg-primary">PDF · JPG · JPEG · PNG</span></div>
        <form id="uploadForm" class="dropzone">
          <input type="file" id="fileInput" name="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" hidden>
          <i class="fa-solid fa-file-arrow-up"></i><h3>Drop file here or click to browse</h3><p>Validated by extension, MIME type, and upload limit.</p>
          <div id="fileMeta" class="file-meta"></div>
          <div class="progress mt-3 d-none" id="uploadProgress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div></div>
        </form>
        <div class="preview-wrap"><img id="imagePreview" alt="Preview thumbnail"><embed id="pdfPreview" type="application/pdf"></div>
      </div>
      <div class="panel glass">
        <div class="panel-head"><div><p class="eyebrow">OCR Preview</p><h2>Extracted Text</h2></div><div class="btn-group"><button class="btn btn-sm btn-light" id="copyText"><i class="fa-regular fa-copy"></i></button><button class="btn btn-sm btn-light" id="downloadText"><i class="fa-solid fa-download"></i></button><button class="btn btn-sm btn-light" id="fullscreenText"><i class="fa-solid fa-expand"></i></button></div></div>
        <textarea id="ocrText" class="ocr-text" placeholder="Extracted OCR text appears here and remains editable."></textarea>
        <div class="text-metrics"><span><b id="charCount">0</b> characters</span><span><b id="lineCount">0</b> lines</span></div>
        <button class="btn btn-premium w-100 ripple" id="parseText"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto Parse With Active Regex</button>
      </div>
    </section>

    <section class="panel glass slide-up">
      <div class="panel-head"><div><p class="eyebrow">Regex Management Center</p><h2>No-code Regex Rules</h2></div><button class="btn btn-premium ripple" id="newRegex"><i class="fa-solid fa-plus"></i> Create Regex</button></div>
      <div class="row g-4">
        <div class="col-lg-5"><form id="regexForm" class="regex-form"><input type="hidden" id="regexId"><div class="row g-3"><div class="col-md-8"><label>Name</label><input class="form-control" id="regexName" required></div><div class="col-md-4"><label>Flags</label><input class="form-control" id="regexFlags" value="miu"></div><div class="col-12"><label>Description</label><input class="form-control" id="regexDescription"></div><div class="col-12"><label>Pattern</label><textarea class="form-control code" id="regexPattern" rows="6" required></textarea></div><div class="col-md-4"><label>Priority</label><input type="number" class="form-control" id="regexPriority" value="100"></div><div class="col-md-4 form-check switch"><input class="form-check-input" type="checkbox" id="regexActive" checked><label>Active</label></div><div class="col-md-4 form-check switch"><input class="form-check-input" type="checkbox" id="regexDefault"><label>Default</label></div><div class="col-12"><button class="btn btn-premium w-100 ripple"><i class="fa-solid fa-floppy-disk"></i> Save Regex</button></div></div></form></div>
        <div class="col-lg-7"><div class="table-responsive"><table class="table premium-table" id="regexTable"><thead><tr><th>Name</th><th>Priority</th><th>Status</th><th>Default</th><th>Actions</th></tr></thead><tbody></tbody></table></div></div>
      </div>
    </section>

    <section class="panel glass slide-up">
      <div class="panel-head"><div><p class="eyebrow">Regex Testing Sandbox</p><h2>Live Regex Test & Capture Groups</h2></div><button class="btn btn-dark ripple" id="runRegexTest"><i class="fa-solid fa-play"></i> Run Test</button></div>
      <div class="row g-3"><div class="col-lg-6"><label>OCR Text</label><textarea id="sandboxText" class="form-control code" rows="8"></textarea></div><div class="col-lg-6"><label>Regex Pattern</label><textarea id="sandboxPattern" class="form-control code" rows="8"></textarea></div></div>
      <div id="testResult" class="test-result mt-3"></div>
    </section>

    <section class="panel glass slide-up">
      <div class="panel-head"><div><p class="eyebrow">Import Confirmation</p><h2>Editable Parsed Monthly Plan</h2></div><div class="summary-pills"><span>Found <b id="rowsFound">0</b></span><span>Ready <b id="rowsReady">0</b></span><span>Failed <b id="rowsFailed">0</b></span></div></div>
      <div class="table-responsive"><table class="table premium-table" id="parsedTable"><thead><tr><th>Month</th><th>Class</th><th>Week</th><th>Total Period</th><th>Subject</th><th>Lesson Name</th><th>Lesson Details</th><th>Activities</th><th>Smart Date</th><th>Exam Date</th></tr></thead><tbody></tbody></table></div>
      <div class="d-flex gap-2 justify-content-end flex-wrap"><button class="btn btn-outline-secondary" id="cancelImport">Cancel</button><button class="btn btn-light" id="saveDraft">Save Draft</button><button class="btn btn-premium ripple" id="importAll">Import All</button></div>
    </section>

    <section class="panel glass slide-up">
      <div class="panel-head"><div><p class="eyebrow">Import History</p><h2>Audit Trail</h2></div><input id="historySearch" class="form-control history-search" placeholder="Search file name..."></div>
      <div class="table-responsive"><table class="table premium-table" id="historyTable"><thead><tr><th>File Name</th><th>Upload Date</th><th>Rows</th><th>Status</th><th>Size</th></tr></thead><tbody></tbody></table></div><nav><ul class="pagination" id="historyPager"></ul></nav>
    </section>
  </main>
</div>

<div class="modal fade" id="settingsModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content glass modal-premium"><div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-gear"></i> OCR Settings</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="settingsForm"><ul class="nav nav-pills mb-4"><li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#general" type="button">General</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#ocrset" type="button">OCR Settings</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#regexset" type="button">Regex Settings</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#importset" type="button">Import Settings</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#advanced" type="button">Advanced</button></li></ul><div class="tab-content settings-grid"><div class="tab-pane fade show active" id="general"><input name="app_label" class="form-control" value="Madrasa ERP"></div><div class="tab-pane fade" id="ocrset"><select name="ocr_engine" class="form-select"><option value="tesseract">Tesseract OCR</option></select><label><input name="auto_cleanup" type="checkbox" checked> Auto Cleanup</label><label><input name="remove_extra_spaces" type="checkbox" checked> Remove Extra Spaces</label><label><input name="auto_detect_tables" type="checkbox" checked> Auto Detect Tables</label><input name="confidence_level" type="number" class="form-control" value="70" min="1" max="100"></div><div class="tab-pane fade" id="regexset"><input name="default_regex_id" class="form-control" placeholder="Default Regex ID"><input name="fallback_regex_id" class="form-control" placeholder="Fallback Regex ID"><label><input name="multi_regex_processing" type="checkbox" checked> Multi Regex Processing</label></div><div class="tab-pane fade" id="importset"><input name="file_retention_days" type="number" class="form-control" value="30"><input name="maximum_upload_size" type="number" class="form-control" value="10485760"></div><div class="tab-pane fade" id="advanced"><input name="webhook_url" class="form-control" placeholder="Webhook URL"><label><input name="debug_mode" type="checkbox"> Debug Mode</label></div></div></form></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-premium" id="saveSettings">Save Settings</button></div></div></div></div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastBox"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
