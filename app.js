(function () {
  'use strict';

  const storeKey = 'auto-ui-generator-pro-v1';
  const defaultState = {
    html: `<div class="max-w-3xl mx-auto p-6 space-y-5">\n  <h1>Product Analytics Dashboard</h1>\n  <p>Track growth, activation, and revenue from one elegant interface.</p>\n  <div class="card">\n    <h2>Quick Form</h2>\n    <form class="space-y-3">\n      <input type="text" placeholder="Project name" />\n      <input type="email" placeholder="Email" />\n      <button type="button">Create Project</button>\n    </form>\n  </div>\n  <div class="table-wrap">\n    <table>\n      <thead><tr><th>Metric</th><th>Value</th><th>Trend</th></tr></thead>\n      <tbody><tr><td>MRR</td><td>$24,490</td><td>+12.5%</td></tr><tr><td>Churn</td><td>2.1%</td><td>-0.4%</td></tr></tbody>\n    </table>\n  </div>\n</div>`,
    css: `/* Optional CSS overrides */\n.card{transition:transform .22s ease;}\n.card:hover{transform:translateY(-2px);}`,
    js: `// Optional JS\nconsole.log('Preview loaded');`,
    theme: 'saas'
  };

  const presets = [
    {
      name: 'Hero Landing',
      html: `<section class="max-w-5xl mx-auto p-8">\n  <div class="card">\n    <p class="uppercase tracking-wider">New Launch</p>\n    <h1>Build faster with a premium starter UI.</h1>\n    <p>Launch a beautiful product interface in minutes with responsive sections and conversion-focused components.</p>\n    <button>Start Free Trial</button>\n  </div>\n</section>`,
      css: '',
      js: ''
    },
    {
      name: 'Pricing Grid',
      html: `<section class="max-w-6xl mx-auto p-6">\n  <h1>Simple Pricing</h1>\n  <div class="grid md:grid-cols-3 gap-4">\n    <div class="card"><h3>Starter</h3><p>$19/mo</p><button>Choose</button></div>\n    <div class="card"><h3>Pro</h3><p>$49/mo</p><button>Choose</button></div>\n    <div class="card"><h3>Scale</h3><p>$99/mo</p><button>Choose</button></div>\n  </div>\n</section>`,
      css: '',
      js: ''
    },
    {
      name: 'Form + Table',
      html: `<div class="max-w-4xl mx-auto p-6 space-y-6">\n  <div class="card">\n    <h2>Lead Capture</h2>\n    <form class="grid md:grid-cols-2 gap-3">\n      <input type="text" placeholder="Name" />\n      <input type="email" placeholder="Email" />\n      <textarea placeholder="Notes"></textarea>\n      <button type="button">Submit</button>\n    </form>\n  </div>\n  <div class="table-wrap">\n    <table>\n      <thead><tr><th>Lead</th><th>Status</th><th>Value</th></tr></thead>\n      <tbody><tr><td>Acme Inc.</td><td>Qualified</td><td>$12,500</td></tr></tbody>\n    </table>\n  </div>\n</div>`,
      css: '',
      js: ''
    }
  ];

  const htmlInput = document.getElementById('htmlInput');
  const cssInput = document.getElementById('cssInput');
  const jsInput = document.getElementById('jsInput');
  const previewFrame = document.getElementById('previewFrame');
  const statusText = document.getElementById('statusText');
  const sidebar = document.getElementById('sidebar');

  const state = loadState();
  htmlInput.value = state.html;
  cssInput.value = state.css;
  jsInput.value = state.js;

  wireTabs();
  wireSidebar();
  wireThemeButtons();
  wirePresets();
  wireActions();
  wireAutoSave();
  wireDragResize();

  renderPreview(false);

  function loadState() {
    try {
      const raw = localStorage.getItem(storeKey);
      if (!raw) return { ...defaultState };
      const parsed = JSON.parse(raw);
      return {
        html: typeof parsed.html === 'string' ? parsed.html : defaultState.html,
        css: typeof parsed.css === 'string' ? parsed.css : defaultState.css,
        js: typeof parsed.js === 'string' ? parsed.js : defaultState.js,
        theme: typeof parsed.theme === 'string' ? parsed.theme : defaultState.theme
      };
    } catch (_e) {
      return { ...defaultState };
    }
  }

  function saveState() {
    const payload = {
      html: htmlInput.value,
      css: cssInput.value,
      js: jsInput.value,
      theme: state.theme
    };
    localStorage.setItem(storeKey, JSON.stringify(payload));
  }

  function wireTabs() {
    document.querySelectorAll('.tab-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach((b) => {
          b.classList.remove('active');
          b.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');

        const target = btn.dataset.tab;
        document.querySelectorAll('.code-input').forEach((panel) => {
          panel.classList.toggle('hidden', panel.dataset.panel !== target);
        });
      });
    });
  }

  function wireSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  function wireThemeButtons() {
    const buttons = document.querySelectorAll('.btn-theme');
    buttons.forEach((btn) => {
      if (btn.dataset.theme === state.theme) btn.classList.add('active');
      btn.addEventListener('click', () => {
        state.theme = btn.dataset.theme;
        buttons.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        setStatus(`Theme switched to ${btn.dataset.theme}.`);
        renderPreview(false);
        saveState();
      });
    });
  }

  function wirePresets() {
    const presetList = document.getElementById('presetList');
    presets.forEach((preset) => {
      const button = document.createElement('button');
      button.className = 'btn btn-subtle';
      button.textContent = preset.name;
      button.addEventListener('click', () => {
        htmlInput.value = preset.html;
        cssInput.value = preset.css;
        jsInput.value = preset.js;
        setStatus(`Loaded preset: ${preset.name}.`);
        renderPreview(false);
        saveState();
      });
      presetList.appendChild(button);
    });
  }

  function wireActions() {
    document.getElementById('renderBtn').addEventListener('click', () => renderPreview(false));
    document.getElementById('applyStyleBtn').addEventListener('click', () => renderPreview(true));
    document.getElementById('saveDraftBtn').addEventListener('click', () => {
      saveState();
      setStatus('Draft saved to localStorage.');
    });
    document.getElementById('resetBtn').addEventListener('click', () => {
      htmlInput.value = defaultState.html;
      cssInput.value = defaultState.css;
      jsInput.value = defaultState.js;
      state.theme = defaultState.theme;
      document.querySelectorAll('.btn-theme').forEach((b) => {
        b.classList.toggle('active', b.dataset.theme === state.theme);
      });
      renderPreview(false);
      saveState();
      setStatus('Reset to defaults.');
    });
  }

  function wireAutoSave() {
    [htmlInput, cssInput, jsInput].forEach((el) => {
      el.addEventListener('input', () => saveState());
    });
  }

  function wireDragResize() {
    const handle = document.getElementById('dragHandle');
    const editorPane = document.getElementById('editorPane');
    const panes = document.querySelector('.panes');
    if (!handle || !editorPane || !panes) return;

    let dragging = false;
    handle.addEventListener('mousedown', () => (dragging = true));
    window.addEventListener('mouseup', () => (dragging = false));
    window.addEventListener('mousemove', (event) => {
      if (!dragging || window.innerWidth < 821) return;
      const bounds = panes.getBoundingClientRect();
      const width = event.clientX - bounds.left;
      const min = Math.max(260, bounds.width * 0.25);
      const max = bounds.width * 0.75;
      const next = Math.min(Math.max(width, min), max);
      editorPane.style.width = `${next}px`;
    });
  }

  function sanitizeInput(input, type) {
    if (typeof input !== 'string') return '';
    let cleaned = input.replace(/<\/?script\b[^>]*>/gi, '');
    if (type !== 'js') {
      cleaned = cleaned
        .replace(/\son\w+\s*=\s*"[^"]*"/gi, '')
        .replace(/\son\w+\s*=\s*'[^']*'/gi, '')
        .replace(/\son\w+\s*=\s*[^\s>]+/gi, '');
    }
    return cleaned;
  }

  function getThemeTokens() {
    switch (state.theme) {
      case 'dark':
        return {
          body: 'bg-slate-950 text-slate-100',
          card: 'bg-slate-900/80 border border-slate-700',
          button: 'bg-indigo-500 hover:bg-indigo-400 text-white',
          input: 'bg-slate-900 border border-slate-700 text-slate-100',
          tableHead: 'bg-slate-900',
          link: 'text-cyan-300'
        };
      case 'light':
        return {
          body: 'bg-slate-50 text-slate-800',
          card: 'bg-white border border-slate-200',
          button: 'bg-indigo-600 hover:bg-indigo-500 text-white',
          input: 'bg-white border border-slate-300 text-slate-800',
          tableHead: 'bg-slate-100',
          link: 'text-indigo-700'
        };
      case 'glass':
        return {
          body: 'bg-gradient-to-br from-cyan-100 via-white to-violet-100 text-slate-800',
          card: 'bg-white/55 backdrop-blur-xl border border-white/70',
          button: 'bg-white/80 hover:bg-white text-slate-900 border border-white',
          input: 'bg-white/70 border border-white text-slate-900',
          tableHead: 'bg-white/50',
          link: 'text-violet-700'
        };
      case 'saas':
      default:
        return {
          body: 'bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 text-slate-100',
          card: 'bg-slate-900/80 border border-slate-700 shadow-2xl shadow-indigo-900/20',
          button: 'bg-gradient-to-r from-indigo-500 to-cyan-500 hover:opacity-95 text-white',
          input: 'bg-slate-900/80 border border-slate-700 text-slate-100',
          tableHead: 'bg-slate-800/70',
          link: 'text-cyan-300'
        };
    }
  }

  function buildSrcDoc({ html, css, js, enhance }) {
    const safeHtml = sanitizeInput(html, 'html');
    const safeCss = sanitizeInput(css, 'css');
    const safeJs = sanitizeInput(js, 'js');
    const theme = getThemeTokens();

    const enhancementCss = enhance
      ? `
      :root{color-scheme:light dark;}
      body{min-height:100vh;}
      .autoui-wrap{max-width:1200px;margin:0 auto;padding:clamp(16px,3vw,38px);} 
      h1,h2,h3,h4,h5,h6{font-weight:700;letter-spacing:-0.02em;line-height:1.2;margin:0 0 .55rem;}
      p,li,label{line-height:1.62;margin:0 0 .6rem;}
      .card{border-radius:1rem;padding:1.15rem;transition:transform .22s ease,box-shadow .22s ease;}
      .card:hover{transform:translateY(-2px);}
      button,.btn{border-radius:.75rem;padding:.58rem .92rem;font-weight:600;transition:all .2s ease;}
      input,textarea,select{border-radius:.72rem;padding:.58rem .72rem;width:100%;max-width:100%;}
      table{width:100%;border-collapse:collapse;min-width:580px;}
      th,td{padding:.66rem .72rem;border:1px solid rgba(148,163,184,.25);text-align:left;}
      .table-wrap{overflow:auto;border-radius:.9rem;}
      img,video{max-width:100%;height:auto;border-radius:.6rem;}
      a{text-decoration:none}
      .grid{display:grid;gap:1rem}
      @media (max-width: 768px){
        .autoui-wrap{padding:16px;}
      }
    `
      : '';

    return `<!doctype html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body{margin:0;padding:0;}
    .autoui-body{@apply ${theme.body};}
    .card{@apply ${theme.card};}
    button,.btn{@apply ${theme.button};}
    input,textarea,select{@apply ${theme.input};}
    thead{@apply ${theme.tableHead};}
    a{@apply ${theme.link};}
    ${enhancementCss}
    ${safeCss}
  </style>
</head>
<body class="autoui-body">
  <div class="autoui-wrap">
    ${safeHtml}
  </div>
  <script>
    try {
      ${safeJs}
    } catch (e) {
      console.warn('User JS error:', e);
    }
  <\/script>
</body>
</html>`;
  }

  function renderPreview(enhance) {
    const srcdoc = buildSrcDoc({
      html: htmlInput.value,
      css: cssInput.value,
      js: jsInput.value,
      enhance
    });
    previewFrame.srcdoc = srcdoc;
    window.__AUTO_UI_LAST_DOC__ = srcdoc;
    setStatus(enhance ? 'Premium auto styling applied.' : 'Preview rendered.');
  }

  function setStatus(message) {
    statusText.textContent = message;
  }

  window.AutoUiApp = {
    getPreviewDocument: () => window.__AUTO_UI_LAST_DOC__ || '',
    setStatus,
    rerenderWithEnhance: () => renderPreview(true),
    renderPlain: () => renderPreview(false)
  };
})();
