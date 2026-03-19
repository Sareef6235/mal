function appState() {
  return {
    darkMode: localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === null,
    persistTheme() {
      localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
    }
  };
}

function workspaceHub() {
  return {
    activePanel: 'convert',
    panelMeta: {
      convert: { title: 'Quick conversions', description: 'Upload and convert files instantly with smart presets.' },
      image: { title: 'Image lab', description: 'Edit, compress, resize, and enhance images.' },
      docs: { title: 'Document tools', description: 'Split, merge, and convert documents.' },
      video: { title: 'Video tools', description: 'Convert and optimize videos.' },
      seo: { title: 'SEO cockpit', description: 'Analyze and improve SEO performance.' },
      projects: { title: 'Saved projects', description: 'Access your saved work.' },
      downloads: { title: 'Downloads', description: 'Track your downloaded files.' }
    }
  };
}

function converterWidget() {
  return {
    files: [],
    progress: 0,
    sourceType: 'auto',
    targetFormat: 'webp',
    root: null,

    init() {
      this.root = this.$root || this.root;
      if (!this.root || this.root.dataset.converterBound === 'true') return;
      this.root.dataset.converterBound = 'true';

      const input = this.root.querySelector('[data-converter-input]');
      const btn = this.root.querySelector('[data-converter-trigger]');
      const source = this.root.querySelector('[data-converter-source]');
      const target = this.root.querySelector('[data-converter-target]');

      if (source) this.sourceType = source.value;
      if (target) this.targetFormat = target.value;

      input?.addEventListener('change', (e) => {
        this.files = Array.from(e.target.files || []);
        this.progress = 0;
        this.renderFiles();
      });

      source?.addEventListener('change', () => {
        this.sourceType = source.value;
        this.renderFiles();
      });

      target?.addEventListener('change', () => {
        this.targetFormat = target.value;
        this.renderFiles();
      });

      btn?.addEventListener('click', () => {
        this.startConversion();
      });

      this.renderFiles();
    },

    renderFiles() {
      if (!this.root) return;
      const list = this.root.querySelector('[data-converter-file-list]');
      const queue = this.root.querySelector('[data-converter-queue-count]');
      const preset = this.root.querySelector('[data-converter-preset]');
      const previewName = this.root.querySelector('[data-converter-preview-name]');
      const previewSize = this.root.querySelector('[data-converter-preview-size]');
      const previewFormat = this.root.querySelector('[data-converter-preview-format]');
      const progressLabel = this.root.querySelector('[data-converter-progress-label]');
      const progressBar = this.root.querySelector('[data-converter-progress-bar]');

      if (list) list.innerHTML = '';
      if (queue) queue.innerText = this.files.length || 0;
      if (preset) preset.innerText = this.sourceType === 'auto' ? 'Smart' : this.sourceType;
      if (previewFormat) previewFormat.innerText = this.targetFormat.toUpperCase();
      if (progressLabel) progressLabel.innerText = `${this.progress}%`;
      if (progressBar) progressBar.style.width = `${this.progress}%`;

      if (!this.files.length) {
        if (previewName) previewName.innerText = 'preview.jpg';
        if (previewSize) previewSize.innerText = '904 KB';
        return;
      }

      this.files.forEach(file => {
        if (list) {
          list.innerHTML += `
            <div class="p-3 border border-white/10 rounded-xl bg-slate-900/60">
              ${file.name} (${Math.round(file.size / 1024)} KB)
            </div>
          `;
        }
      });

      const file = this.files[0];
      if (previewName) previewName.innerText = file.name;
      if (previewSize) previewSize.innerText = Math.round(file.size / 1024) + ' KB';
    },

    startConversion() {
      if (!this.files.length) {
        alert('Upload files first');
        return;
      }

      this.progress = 0;
      this.renderFiles();

      const interval = setInterval(() => {
        this.progress += 5;
        this.renderFiles();

        if (this.progress >= 100) {
          clearInterval(interval);
          this.uploadFiles();
        }
      }, 200);
    },

    async uploadFiles() {
      const fd = new FormData();
      this.files.forEach(f => fd.append('files[]', f));
      fd.append('target', this.targetFormat);
      fd.append('source_type', this.sourceType);

      const results = this.root.querySelector('[data-converter-results]');
      if (results) results.innerHTML = "<p class='text-yellow-400'>Uploading and converting...</p>";

      try {
        const res = await fetch('upload.php', {
          method: 'POST',
          body: fd
        });

        const data = await res.json();

        if (!res.ok || data.error) {
          if (results) results.innerHTML = `<p class='text-red-400'>${data.error || 'Error processing upload'}</p>`;
          return;
        }

        if (results) {
          results.innerHTML = `
            <div class="space-y-3">
              <p class="text-green-400">✔ Converted ${data.files.length} files</p>
              ${data.files.map(file => `
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4">
                  <div class="font-medium text-white">${file.name}</div>
                  <div class="mt-1 text-sm text-slate-400">Stored as: ${file.stored_name}</div>
                  <div class="mt-1 text-sm text-slate-400">Target: ${file.target}</div>
                  <div class="mt-3"><a class="glass-button" href="${file.preview}" target="_blank">Preview file</a></div>
                </div>
              `).join('')}
            </div>`;
        }
      } catch (err) {
        if (results) results.innerHTML = "<p class='text-red-400'>Error processing request</p>";
      }
    }
  };
}

async function submitSeoForm(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const results = document.getElementById('seo-results');
  results.innerHTML = "<p class='text-yellow-400'>Analyzing...</p>";

  try {
    const res = await fetch('api/seo.php', {
      method: 'POST',
      body: new FormData(form)
    });

    const data = await res.json();
    if (!res.ok || data.error) {
      results.innerHTML = `<p class='text-red-400'>${data.error || 'Error processing request'}</p>`;
      return;
    }

    const payload = data.data || data;
    results.innerHTML = `
      <div class="space-y-3">
        <div><strong>SEO Score:</strong> ${payload.score}/100</div>
        <div><strong>Meta Description:</strong> ${payload.meta || payload.metaDescription}</div>
        <div><strong>Keyword Density:</strong> ${payload.density || payload.keywordDensity}%</div>
        <div><strong>Word Count:</strong> ${payload.words}</div>
        <div><strong>Suggestions:</strong> ${payload.suggestions}</div>
      </div>`;
  } catch (err) {
    results.innerHTML = "<p class='text-red-400'>Error processing request</p>";
  }
}

document.addEventListener('alpine:init', () => {
  if (window.Alpine) {
    Alpine.data('converterWidget', converterWidget);
  }
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-converter-root]').forEach((root) => {
    const instance = converterWidget();
    instance.root = root;
    instance.init();
  });

  const form = document.getElementById('seo-form');
  if (form) {
    form.addEventListener('submit', submitSeoForm);
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js').catch(() => {});
  }
});
