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
    startMockConversion() {
      if (!this.files.length) return;
      this.progress = 3;
      const interval = setInterval(() => {
        this.progress += Math.floor(Math.random() * 16);
        if (this.progress >= 100) {
          this.progress = 100;
          clearInterval(interval);
        }
      }, 250);
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
