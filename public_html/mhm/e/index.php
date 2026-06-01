<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$token = csrf_token();
$dbReady = true;
$dbError = '';
$history = [];
try {
    $history = recent_history(8);
    $rules = get_enabled_rules();
} catch (Throwable $e) {
    $dbReady = false;
    $dbError = $e->getMessage();
    $rules = [];
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h($token) ?>">
    <title><?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="index.php" aria-label="OCR Text Extractor Pro dashboard">
            <span class="brand-mark">OCR</span>
            <span><strong>Text Extractor</strong><small>Pro Dashboard</small></span>
        </a>
        <nav class="nav-menu" aria-label="Main navigation">
            <a href="#dashboard" class="active">Dashboard</a>
            <a href="#upload">Upload File</a>
            <a href="#extracted">Extracted Text</a>
            <a href="settings.php">Regex Settings</a>
            <a href="#history">History</a>
        </nav>
        <div class="sidebar-card">
            <span class="pulse"></span>
            <strong>Engine status</strong>
            <p><?= $dbReady ? 'Database connected. OCR utilities auto-detected at runtime.' : 'Database setup needed.' ?></p>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="icon-button mobile-only" type="button" data-toggle-sidebar aria-label="Open menu">☰</button>
            <label class="search-wrap">
                <span>⌕</span>
                <input type="search" id="globalSearch" placeholder="Search extracted text or history...">
            </label>
            <button class="theme-toggle" type="button" data-theme-toggle><span>☾</span> Dark</button>
            <div class="profile"><span>Admin</span><strong>A</strong></div>
        </header>

        <?php if (!$dbReady): ?>
            <section class="alert danger">
                <strong>Database connection failed.</strong>
                <p>Update credentials in <code>db.php</code> or set <code>OCR_DB_HOST</code>, <code>OCR_DB_NAME</code>, <code>OCR_DB_USER</code>, and <code>OCR_DB_PASS</code>. Details: <?= h($dbError) ?></p>
            </section>
        <?php endif; ?>

        <section class="hero" id="dashboard">
            <div>
                <span class="eyebrow">Premium OCR workflow</span>
                <h1>Extract, clean, search, and export text from PDFs and images.</h1>
                <p>Upload multiple files, run OCR or PDF parsing, apply database-powered regex rules, and deliver polished text in seconds.</p>
                <div class="hero-actions">
                    <button class="gradient-button" type="button" data-open-modal="uploadModal">Upload files</button>
                    <a class="ghost-button" href="settings.php">Manage regex rules</a>
                </div>
            </div>
            <div class="stats-grid">
                <article class="stat-card"><span><?= count($history) ?></span><small>Recent uploads</small></article>
                <article class="stat-card"><span><?= count($rules) ?></span><small>Active regex rules</small></article>
                <article class="stat-card"><span>15MB</span><small>Max per file</small></article>
            </div>
        </section>

        <section class="grid two-col" id="upload">
            <article class="glass-card upload-card">
                <div class="section-title"><span>01</span><div><h2>Upload File</h2><p>JPG, PNG, JPEG, WEBP, or PDF. Drag and drop is supported.</p></div></div>
                <form id="uploadForm" class="dropzone" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
                    <input type="file" id="fileInput" name="files[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple hidden>
                    <button type="button" class="dropzone-inner" data-browse-files>
                        <span class="upload-icon">⇪</span>
                        <strong>Drop files here or click to browse</strong>
                        <small>Secure upload, MIME validation, unique storage names.</small>
                    </button>
                    <div class="file-list" id="fileList"></div>
                    <button type="submit" class="gradient-button full">Start extraction</button>
                    <div class="progress-shell" aria-hidden="true"><div class="progress-bar" id="uploadProgress"></div></div>
                </form>
            </article>

            <article class="glass-card" id="extracted">
                <div class="section-title"><span>02</span><div><h2>Text Preview</h2><p>Raw OCR and cleaned output update after extraction.</p></div></div>
                <div class="tabs" role="tablist">
                    <button class="tab active" type="button" data-tab="rawText">Raw Text</button>
                    <button class="tab" type="button" data-tab="cleanText">Cleaned Text</button>
                </div>
                <textarea id="rawText" class="text-panel tab-panel active" placeholder="Raw extracted text appears here..."></textarea>
                <textarea id="cleanText" class="text-panel tab-panel" placeholder="Regex-cleaned text appears here..."></textarea>
                <div class="action-row">
                    <button class="ghost-button" type="button" data-copy-target="cleanText">Copy cleaned</button>
                    <button class="ghost-button" type="button" data-download-target="cleanText">Download .txt</button>
                    <button class="ghost-button" type="button" data-open-modal="textModal">Open popup</button>
                </div>
            </article>
        </section>

        <section class="grid two-col">
            <article class="glass-card">
                <div class="section-title"><span>03</span><div><h2>Regex Processing Engine</h2><p>Enabled rules run in admin-defined order.</p></div></div>
                <div id="ruleBadges" class="badge-list">
                    <?php foreach ($rules as $rule): ?><span><?= h($rule['name']) ?></span><?php endforeach; ?>
                    <?php if (!$rules): ?><small>No active rules yet. Add rules in settings.</small><?php endif; ?>
                </div>
                <label class="field"><span>Live sample / manual text</span><textarea id="sampleText" rows="7" placeholder="Paste text here to preview regex cleanup..."></textarea></label>
                <button class="gradient-button" type="button" id="previewRegex">Live preview cleaned text</button>
            </article>

            <article class="glass-card" id="history">
                <div class="section-title"><span>04</span><div><h2>History</h2><p>Recent uploads and extraction status.</p></div></div>
                <div class="history-list" id="historyList">
                    <?php foreach ($history as $item): ?>
                        <div class="history-item">
                            <strong><?= h($item['original_name']) ?></strong>
                            <span><?= h($item['status']) ?> · <?= number_format((int) $item['file_size'] / 1024, 1) ?> KB · <?= h($item['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$history): ?><p class="muted">No uploads yet.</p><?php endif; ?>
                </div>
            </article>
        </section>
    </main>
</div>

<div class="modal" id="uploadModal" aria-hidden="true">
    <div class="modal-card"><button class="modal-close" data-close-modal>&times;</button><h2>Upload files</h2><p>Use the dashboard drop zone to process multiple PDFs and images securely.</p><button class="gradient-button" data-close-modal>Got it</button></div>
</div>
<div class="modal" id="textModal" aria-hidden="true">
    <div class="modal-card wide"><button class="modal-close" data-close-modal>&times;</button><h2>Extracted Text</h2><pre id="modalTextPreview">No text extracted yet.</pre></div>
</div>
<div class="toast-stack" id="toastStack"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
