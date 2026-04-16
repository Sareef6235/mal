(function () {
  'use strict';

  function getApp() {
    return window.AutoUiApp;
  }

  function bindExportActions() {
    const copyBtn = document.getElementById('copyBtn');
    const downloadBtn = document.getElementById('downloadBtn');
    const exportImageBtn = document.getElementById('exportImageBtn');

    copyBtn.addEventListener('click', async () => {
      const source = getApp().getPreviewDocument();
      try {
        await navigator.clipboard.writeText(source);
        getApp().setStatus('Compiled HTML copied to clipboard.');
      } catch (_e) {
        getApp().setStatus('Clipboard failed. Please copy manually.');
      }
    });

    downloadBtn.addEventListener('click', () => {
      const source = getApp().getPreviewDocument();
      const blob = new Blob([source], { type: 'text/html;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'auto-ui-export.html';
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
      getApp().setStatus('HTML file downloaded.');
    });

    exportImageBtn.addEventListener('click', async () => {
      const frame = document.getElementById('previewFrame');
      try {
        const doc = frame.contentDocument;
        const target = doc && doc.body;
        if (!target || typeof html2canvas !== 'function') {
          getApp().setStatus('Image export unavailable in this browser.');
          return;
        }
        const canvas = await html2canvas(target, {
          backgroundColor: null,
          scale: Math.min(2, window.devicePixelRatio || 1.2),
          useCORS: true
        });
        const url = canvas.toDataURL('image/png');
        const a = document.createElement('a');
        a.href = url;
        a.download = 'auto-ui-preview.png';
        document.body.appendChild(a);
        a.click();
        a.remove();
        getApp().setStatus('Preview exported as image.');
      } catch (_e) {
        getApp().setStatus('Unable to export image (sandbox/browser restriction).');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindExportActions);
  } else {
    bindExportActions();
  }
})();
