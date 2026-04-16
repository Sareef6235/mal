<?php
// Static single-page app entry for cPanel shared hosting (PHP + Vanilla JS).
?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Auto UI Generator Pro</title>
  <meta name="description" content="Paste HTML/CSS/JS and auto-transform it into a premium responsive UI." />
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="app-shell min-h-screen antialiased">
  <div class="bg-orb orb-1" aria-hidden="true"></div>
  <div class="bg-orb orb-2" aria-hidden="true"></div>

  <header class="topbar glass-panel sticky top-0 z-40">
    <div class="topbar-inner">
      <button id="menuToggle" class="icon-btn lg:hidden" aria-label="Toggle sidebar">☰</button>
      <div>
        <h1 class="brand-title">Auto UI Generator Pro</h1>
        <p class="brand-sub">Paste code → Auto style → Export instantly</p>
      </div>
      <div class="topbar-actions">
        <button id="saveDraftBtn" class="btn btn-subtle">Save Draft</button>
        <button id="resetBtn" class="btn btn-danger">Reset</button>
      </div>
    </div>
  </header>

  <main class="app-grid">
    <aside id="sidebar" class="sidebar glass-panel">
      <div class="section-head">
        <h2>Templates</h2>
      </div>
      <div class="preset-list" id="presetList"></div>

      <div class="section-head mt-6">
        <h2>Themes</h2>
      </div>
      <div class="theme-list">
        <button class="btn btn-theme" data-theme="light">Light Mode</button>
        <button class="btn btn-theme" data-theme="dark">Dark Mode</button>
        <button class="btn btn-theme" data-theme="glass">Glass UI</button>
        <button class="btn btn-theme" data-theme="saas">SaaS UI</button>
      </div>

      <div class="section-head mt-6">
        <h2>Actions</h2>
      </div>
      <div class="action-list">
        <button id="applyStyleBtn" class="btn btn-primary">Apply Style</button>
        <button id="renderBtn" class="btn btn-subtle">Render Preview</button>
        <button id="copyBtn" class="btn btn-subtle">Copy Code</button>
        <button id="downloadBtn" class="btn btn-subtle">Download HTML</button>
        <button id="exportImageBtn" class="btn btn-subtle">Export Image</button>
      </div>
      <p id="statusText" class="status-text" role="status" aria-live="polite">Ready.</p>
    </aside>

    <section class="workspace">
      <div class="panes glass-panel">
        <div class="editor-pane" id="editorPane">
          <div class="tab-row" role="tablist" aria-label="Code editor tabs">
            <button class="tab-btn active" data-tab="html" role="tab" aria-selected="true">HTML</button>
            <button class="tab-btn" data-tab="css" role="tab" aria-selected="false">CSS</button>
            <button class="tab-btn" data-tab="js" role="tab" aria-selected="false">JS</button>
          </div>

          <label class="sr-only" for="htmlInput">HTML editor</label>
          <textarea id="htmlInput" class="code-input" data-panel="html" spellcheck="false"></textarea>

          <label class="sr-only" for="cssInput">CSS editor</label>
          <textarea id="cssInput" class="code-input hidden" data-panel="css" spellcheck="false"></textarea>

          <label class="sr-only" for="jsInput">JavaScript editor</label>
          <textarea id="jsInput" class="code-input hidden" data-panel="js" spellcheck="false"></textarea>
        </div>

        <div id="dragHandle" class="drag-handle" role="separator" aria-orientation="vertical" aria-label="Resize panes"></div>

        <div class="preview-pane">
          <div class="preview-head">
            <h2>Live Preview</h2>
            <span class="preview-pill">Sandboxed iframe</span>
          </div>
          <iframe
            id="previewFrame"
            title="Live code preview"
            sandbox="allow-same-origin"
            referrerpolicy="no-referrer"
          ></iframe>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
  <script src="app.js" defer></script>
  <script src="export.js" defer></script>
</body>
</html>
