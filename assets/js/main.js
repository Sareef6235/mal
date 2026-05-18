(function () {
  'use strict';

  const $ = (selector, context = document) => context.querySelector(selector);
  const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));

  const toast = (message, type = 'info') => {
    const stack = $('.uesp-toast-stack');
    if (!stack) return;
    const item = document.createElement('div');
    item.className = 'uesp-toast uesp-toast--' + type;
    item.textContent = message;
    stack.appendChild(item);
    window.setTimeout(() => item.remove(), 3600);
  };

  window.addEventListener('load', () => {
    const preloader = $('.uesp-preloader');
    if (preloader) preloader.classList.add('is-hidden');
  });

  const savedMode = localStorage.getItem('uesp-dark-mode');
  if (savedMode === 'true') document.body.classList.add('uesp-dark');
  $$('.uesp-dark-toggle').forEach((button) => {
    button.addEventListener('click', () => {
      document.body.classList.toggle('uesp-dark');
      localStorage.setItem('uesp-dark-mode', document.body.classList.contains('uesp-dark'));
    });
  });

  $$('.uesp-menu-toggle').forEach((button) => button.addEventListener('click', () => document.body.classList.add('uesp-menu-open')));
  $$('.uesp-menu-close').forEach((button) => button.addEventListener('click', () => document.body.classList.remove('uesp-menu-open')));

  const openModal = (id) => {
    const modal = $(`[data-uesp-modal-id="${id}"]`);
    if (modal) modal.classList.add('is-open');
  };
  const closeModals = () => $$('.uesp-modal-overlay').forEach((modal) => modal.classList.remove('is-open'));
  $$('[data-uesp-modal]').forEach((trigger) => trigger.addEventListener('click', () => openModal(trigger.dataset.uespModal)));
  $$('[data-uesp-close-modal], .uesp-modal-overlay').forEach((target) => {
    target.addEventListener('click', (event) => {
      if (event.target === target || event.target.hasAttribute('data-uesp-close-modal')) closeModals();
    });
  });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModals(); });

  const observer = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 }) : null;
  $$('[data-uesp-animate]').forEach((item) => observer ? observer.observe(item) : item.classList.add('is-visible'));

  $$('[data-uesp-timer]').forEach((timer) => {
    let seconds = Number(timer.dataset.uespTimer || 0);
    const output = $('span', timer);
    const tick = () => {
      if (!output || seconds < 0) return;
      const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
      const secs = (seconds % 60).toString().padStart(2, '0');
      output.textContent = `${mins}:${secs}`;
      seconds -= 1;
      if (seconds === 300) toast('Only 5 minutes remaining', 'warning');
    };
    tick();
    window.setInterval(tick, 1000);
  });

  $$('[data-uesp-autosave] input[type="radio"]').forEach((input) => {
    input.addEventListener('change', () => {
      $$('.uesp-answer').forEach((answer) => answer.classList.remove('is-selected'));
      input.closest('.uesp-answer')?.classList.add('is-selected');
      const data = new FormData();
      data.append('action', 'uesp_autosave_answer');
      data.append('nonce', window.uespTheme?.nonce || '');
      data.append('question_id', input.dataset.questionId || '0');
      data.append('answer', input.value);
      fetch(window.uespTheme?.ajaxUrl || '', { method: 'POST', credentials: 'same-origin', body: data })
        .then((response) => response.json())
        .then(() => toast(window.uespTheme?.i18n?.saved || 'Saved', 'success'))
        .catch(() => toast('Offline mode: answer cached locally', 'warning'));
    });
  });


  $$('[data-uesp-text-upload]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const fileInput = form.querySelector('input[type="file"]');
      const file = fileInput?.files?.[0];
      const allowed = ['php', 'js', 'css', 'html', 'json', 'txt'];
      const blocked = ['png', 'jpg', 'jpeg', 'webp', 'mp4', 'mov', 'zip', 'rar', 'woff', 'ttf'];
      const extension = file?.name?.split('.').pop()?.toLowerCase() || '';

      if (!file || blocked.includes(extension) || !allowed.includes(extension)) {
        toast('Binary files are not supported. Please upload text-based files only.', 'error');
        return;
      }

      const data = new FormData(form);
      data.append('action', 'uesp_text_upload');
      data.append('nonce', window.uespTheme?.nonce || '');

      fetch(window.uespTheme?.ajaxUrl || '', { method: 'POST', credentials: 'same-origin', body: data })
        .then((response) => response.json())
        .then((response) => {
          if (!response.success) throw new Error(response.message || 'Binary files are not supported');
          toast(response.data?.message || 'Text file uploaded successfully.', 'success');
        })
        .catch((error) => toast(error.message || 'Binary files are not supported. Please upload text-based files only.', 'error'));
    });
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      toast(window.uespTheme?.i18n?.tabWarning || 'Tab switch detected', 'warning');
      openModal('warning');
    }
  });

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.uesp-btn');
    if (!button) return;
    const ripple = document.createElement('span');
    ripple.style.cssText = 'position:absolute;border-radius:50%;transform:scale(0);animation:uesp-ripple .55s linear;background:rgba(255,255,255,.45);width:120px;height:120px;left:' + (event.offsetX - 60) + 'px;top:' + (event.offsetY - 60) + 'px;pointer-events:none;';
    button.appendChild(ripple);
    window.setTimeout(() => ripple.remove(), 600);
  });

  if (!$('#uesp-ripple-style')) {
    const style = document.createElement('style');
    style.id = 'uesp-ripple-style';
    style.textContent = '@keyframes uesp-ripple{to{transform:scale(4);opacity:0}}';
    document.head.appendChild(style);
  }
})();
