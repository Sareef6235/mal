function appState() {
  return {
    darkMode: JSON.parse(localStorage.getItem('fluxstudio-dark') ?? 'true'),
    persistTheme() {
      localStorage.setItem('fluxstudio-dark', JSON.stringify(this.darkMode));
    }
  };
}

function workspaceHub() {
  return {
    activePanel: 'convert',
    panelMeta: {
      convert: { title: 'Quick convert', description: 'Batch-ready conversion queue for images, PDFs, video, and audio files.' },
      image: { title: 'Image lab', description: 'Resize, compress, crop, remove backgrounds, and watermark branded visuals.' },
      docs: { title: 'Document center', description: 'Merge, split, compress, and export office and PDF documents.' },
      video: { title: 'Video tools', description: 'Prepare thumbnails, compress media, and extract audio with FFmpeg-ready flows.' },
      seo: { title: 'SEO cockpit', description: 'Generate metadata, analyze keyword usage, and prepare sitemap + robots outputs.' },
      projects: { title: 'Saved projects', description: 'Store repeat workflows and resume production work faster.' },
      downloads: { title: 'Download history', description: 'Track exports, delivery dates, and project output history.' }
    }
  };
}

function converterWidget() {
  return {
    files: [{ name: 'preview.jpg', size: 925696 }],
    sourceType: 'auto',
    targetFormat: 'webp',
    progress: 64,
    loadFiles(event) {
      this.files = [...event.target.files];
      this.progress = 0;
    },
    async startConversionRequest() {
      const formData = new FormData();
      formData.append('source_type', this.sourceType);
      formData.append('target_format', this.targetFormat);

      if (this.files.length && this.files[0] instanceof File) {
        this.files.forEach((file) => formData.append('files[]', file));
      } else {
        formData.append('demo_names', this.files.map((file) => file.name).join(','));
      }

      const resultBox = document.getElementById('conversion-results');
      if (resultBox) {
        resultBox.textContent = 'Preparing conversion queue, smart recommendation, and share links...';
      }

      this.progress = 10;
      const timer = setInterval(() => {
        if (this.progress < 88) this.progress += 11;
      }, 180);

      try {
        const response = await fetch('api/conversion.php', {
          method: 'POST',
          body: formData
        });
        const payload = await response.json();

        clearInterval(timer);
        this.progress = response.ok ? 100 : 0;

        if (!response.ok) {
          if (resultBox) resultBox.textContent = payload.error || 'Conversion failed.';
          return;
        }

        const data = payload.data;
        if (resultBox) {
          resultBox.innerHTML = `
            <div class="space-y-4">
              <div class="rounded-2xl border border-cyan-400/20 bg-cyan-400/10 p-4 text-sm text-cyan-100">${data.recommendation}</div>
              <div class="grid gap-3 md:grid-cols-3">
                <div class="metric-card"><p class="text-xs uppercase tracking-[0.25em] text-slate-400">Jobs</p><p class="mt-2 text-2xl font-bold text-white">${data.analytics.total_jobs}</p></div>
                <div class="metric-card"><p class="text-xs uppercase tracking-[0.25em] text-slate-400">Storage saved</p><p class="mt-2 text-2xl font-bold text-white">${data.analytics.saved_storage}</p></div>
                <div class="metric-card"><p class="text-xs uppercase tracking-[0.25em] text-slate-400">Mode</p><p class="mt-2 text-sm font-semibold text-white">${data.analytics.mode}</p></div>
              </div>
              ${data.results.map((item) => `
                <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                      <p class="text-lg font-semibold text-white">${item.name}</p>
                      <p class="mt-1 text-sm text-slate-400">${item.preview}</p>
                      <p class="mt-2 text-xs text-amber-200">${item.watermark}</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                      <a class="glass-button" href="${item.share_url}">Share link</a>
                      <a class="primary-button" href="${item.download_url}">Download ${item.target_format}</a>
                    </div>
                  </div>
                  <div class="mt-4 grid gap-3 md:grid-cols-3 text-sm text-slate-300">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3"><strong>Expires:</strong><div class="mt-1">${item.expires_in}</div></div>
                    <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3"><strong>QR text:</strong><div class="mt-1 break-all">${item.qr_text}</div></div>
                    <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3"><strong>Preview:</strong><div class="mt-1">${item.target_format} export ready</div></div>
                  </div>
                </div>`).join('')}
            </div>`;
        }
      } catch (error) {
        clearInterval(timer);
        this.progress = 0;
        if (resultBox) resultBox.textContent = 'Conversion request failed. Please try again.';
      }
    },
    startMockConversion() {
      this.startConversionRequest();
    }
  };
}

async function submitSeoForm(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const results = document.getElementById('seo-results');
  results.textContent = 'Analyzing content, metadata, keywords, and technical opportunities...';

  const response = await fetch('api/seo.php', {
    method: 'POST',
    body: new FormData(form)
  });

  const payload = await response.json();
  if (!response.ok) {
    results.textContent = payload.error || 'Unexpected error.';
    return;
  }

  const data = payload.data;
  results.innerHTML = `
    <div class="grid gap-3 md:grid-cols-2">
      <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong>SEO Score:</strong><div class="mt-2 text-2xl font-bold text-white">${data.score}/100</div></div>
      <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong>Keyword Density:</strong><div class="mt-2 text-2xl font-bold text-white">${data.keywordDensity}%</div></div>
      <div class="rounded-2xl border border-white/10 bg-white/5 p-4 md:col-span-2"><strong>Meta Description:</strong><div class="mt-2 text-slate-300">${data.metaDescription}</div></div>
      <div class="rounded-2xl border border-white/10 bg-white/5 p-4 md:col-span-2"><strong>Robots.txt:</strong><pre class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-950/80 p-3">${data.robots}</pre></div>
      <div class="rounded-2xl border border-white/10 bg-white/5 p-4 md:col-span-2"><strong>Sitemap Hints:</strong><div class="mt-2 text-slate-300">${data.sitemap.join(', ')}</div></div>
      <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong>Internal Links:</strong><div class="mt-2 text-white">${data.internalLinks}</div></div>
      <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong>AI Blog Outline:</strong><div class="mt-2 text-slate-300">${data.blogOutline.join(' • ')}</div></div>
    </div>`;
}

document.addEventListener('DOMContentLoaded', () => {
  const seoForm = document.getElementById('seo-form');
  if (seoForm) {
    seoForm.addEventListener('submit', submitSeoForm);
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js').catch(() => {});
  }
});
