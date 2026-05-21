(() => {
  const $ = (sel, p = document) => p.querySelector(sel);
  const $$ = (sel, p = document) => [...p.querySelectorAll(sel)];

  if (window.gsap) {
    gsap.from('.floating-nav', { y: -24, opacity: 0, duration: 0.8, ease: 'power2.out' });
    gsap.from('.card', { opacity: 0, y: 20, stagger: 0.08, duration: 0.6 });
  }

  const particles = $('.particles');
  if (particles) for (let i = 0; i < 30; i++) {
    const p = document.createElement('span');
    p.className = 'particle';
    const size = Math.random() * 8 + 2;
    p.style.width = `${size}px`; p.style.height = `${size}px`; p.style.left = `${Math.random() * 100}%`;
    p.style.animationDuration = `${Math.random() * 16 + 12}s`; p.style.animationDelay = `${Math.random() * -18}s`;
    particles.appendChild(p);
  }

  $$('.btn-ripple').forEach((btn) => btn.addEventListener('click', () => {
    btn.classList.remove('rippling'); void btn.offsetWidth; btn.classList.add('rippling');
  }));

  const root = $('#mhmModalRoot');
  const overlay = $('.mhm-modal-overlay', root || document);
  const panels = $$('[data-modal-panel]');
  let activeModal = null;
  let lastFocused = null;

  const beep = () => {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator(); const g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination); o.frequency.value = 720; g.gain.value = 0.015;
      o.start(); o.stop(ctx.currentTime + 0.08);
    } catch (e) {}
  };

  const openModal = (type) => {
    const modal = $(`#mhm-modal-${type}`); if (!modal || !root) return;
    lastFocused = document.activeElement;
    root.classList.add('is-open'); overlay?.removeAttribute('hidden');
    panels.forEach((p) => { p.classList.remove('is-active'); p.setAttribute('aria-hidden', 'true'); });
    modal.classList.add('is-active'); modal.setAttribute('aria-hidden', 'false'); activeModal = modal;
    if (window.gsap) gsap.fromTo(modal, { opacity: 0, y: 16, scale: 0.96 }, { opacity: 1, y: 0, scale: 1, duration: 0.35, ease: 'power2.out' });
    beep();
    setTimeout(() => modal.focus(), 40);
  };

  const closeModal = () => {
    if (!root || !activeModal) return;
    const current = activeModal;
    const done = () => {
      current.classList.remove('is-active'); current.setAttribute('aria-hidden', 'true');
      root.classList.remove('is-open'); overlay?.setAttribute('hidden', 'hidden'); activeModal = null;
      if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    };
    if (window.gsap) gsap.to(current, { opacity: 0, y: 18, scale: 0.97, duration: 0.25, onComplete: done }); else done();
  };

  $$('[data-modal-open]').forEach((el) => el.addEventListener('click', () => openModal(el.dataset.modalOpen)));
  $$('[data-modal-close]').forEach((el) => el.addEventListener('click', closeModal));

  document.addEventListener('keydown', (e) => {
    if (!activeModal) return;
    if (e.key === 'Escape') closeModal();
    if (e.key === 'Tab') {
      const focusables = $$('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])', activeModal).filter((el) => !el.disabled);
      if (!focusables.length) return;
      const first = focusables[0], last = focusables[focusables.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  let touchStartY = 0;
  panels.forEach((panel) => {
    panel.addEventListener('touchstart', (e) => { touchStartY = e.touches[0].clientY; }, { passive: true });
    panel.addEventListener('touchmove', (e) => {
      const dy = e.touches[0].clientY - touchStartY;
      if (dy > 0) panel.style.transform = `translateY(${Math.min(dy, 140)}px)`;
    }, { passive: true });
    panel.addEventListener('touchend', (e) => {
      const dy = e.changedTouches[0].clientY - touchStartY;
      panel.style.transform = '';
      if (dy > 110) closeModal();
    });
  });

  const sideMenu = $('#sideMenu');
  $('[data-toggle-menu]')?.addEventListener('click', () => sideMenu?.classList.toggle('is-open'));

  const audio = new Audio();
  audio.addEventListener('timeupdate', () => {
    const active = $(`.ayah[data-ayah-id="${localStorage.getItem('mhm_last_ayah')}"] .progress > span`);
    if (!active || !audio.duration) return;
    active.style.width = `${(audio.currentTime / audio.duration) * 100}%`;
  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-play-ayah'); if (!btn) return;
    const data = new FormData(); data.append('action', 'mhm_get_ayah_audio'); data.append('nonce', mhmQA.nonce);
    data.append('ayah_id', btn.dataset.ayahId); data.append('reciter', 'Alafasy');
    const res = await fetch(mhmQA.ajaxUrl, { method: 'POST', body: data }); const json = await res.json();
    if (json.success && json.data?.audio_url) { audio.src = json.data.audio_url; localStorage.setItem('mhm_last_ayah', btn.dataset.ayahId); await audio.play(); }
  });

  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/wp-content/themes/mhm-quran-academy/pwa/sw.js');
})();
