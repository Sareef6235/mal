function appState() {
  return {
    darkMode: JSON.parse(localStorage.getItem('fluxstudio-dark') ?? 'true'),
    persistTheme() {
      localStorage.setItem('fluxstudio-dark', JSON.stringify(this.darkMode));
    }
  };
}

function converterWidget() {
  return {
    files: [],
    sourceType: 'auto',
    targetFormat: 'webp',
    progress: 0,
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
    <div class="space-y-3">
      <div><strong>SEO Score:</strong> ${data.score}/100</div>
      <div><strong>Meta Description:</strong> ${data.metaDescription}</div>
      <div><strong>Robots.txt:</strong> <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-950/80 p-3">${data.robots}</pre></div>
      <div><strong>Sitemap Hints:</strong> ${data.sitemap.join(', ')}</div>
      <div><strong>Keyword Density:</strong> ${data.keywordDensity}%</div>
      <div><strong>Internal Links:</strong> ${data.internalLinks}</div>
      <div><strong>AI Blog Outline:</strong> ${data.blogOutline.join(' • ')}</div>
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
