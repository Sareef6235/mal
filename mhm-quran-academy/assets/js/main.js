(() => {
  const $ = (sel, p = document) => p.querySelector(sel);
  const $$ = (sel, p = document) => [...p.querySelectorAll(sel)];

  if (window.gsap) {
    gsap.from('.floating-nav', { y: -24, opacity: 0, duration: 0.8, ease: 'power2.out' });
    gsap.from('.card', { opacity: 0, y: 20, stagger: 0.08, duration: 0.6 });
  }

  const particles = $('.particles');
  if (particles) {
    for (let i = 0; i < 30; i++) {
      const p = document.createElement('span');
      p.className = 'particle';
      const size = Math.random() * 8 + 2;
      p.style.width = `${size}px`;
      p.style.height = `${size}px`;
      p.style.left = `${Math.random() * 100}%`;
      p.style.animationDuration = `${Math.random() * 16 + 12}s`;
      p.style.animationDelay = `${Math.random() * -18}s`;
      particles.appendChild(p);
    }
  }

  $$('.btn-ripple').forEach((btn) => btn.addEventListener('click', () => {
    btn.classList.remove('rippling');
    void btn.offsetWidth;
    btn.classList.add('rippling');
  }));

  const sideMenu = $('#sideMenu');
  $('[data-toggle-menu]')?.addEventListener('click', () => sideMenu?.classList.toggle('is-open'));

  const modal = $('#premiumModal');
  $$('[data-open-modal]').forEach((el) => el.addEventListener('click', () => modal?.classList.add('is-open')));
  $('[data-close-modal]')?.addEventListener('click', () => modal?.classList.remove('is-open'));
  modal?.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('is-open'); });

  const audio = new Audio();
  audio.addEventListener('timeupdate', () => {
    const active = $(`.ayah[data-ayah-id="${localStorage.getItem('mhm_last_ayah')}"] .progress > span`);
    if (!active || !audio.duration) return;
    active.style.width = `${(audio.currentTime / audio.duration) * 100}%`;
  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-play-ayah');
    if (!btn) return;
    const data = new FormData();
    data.append('action', 'mhm_get_ayah_audio');
    data.append('nonce', mhmQA.nonce);
    data.append('ayah_id', btn.dataset.ayahId);
    data.append('reciter', 'Alafasy');
    const res = await fetch(mhmQA.ajaxUrl, { method: 'POST', body: data });
    const json = await res.json();
    if (json.success && json.data?.audio_url) {
      audio.src = json.data.audio_url;
      localStorage.setItem('mhm_last_ayah', btn.dataset.ayahId);
      await audio.play();
    }
  });

  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/wp-content/themes/mhm-quran-academy/pwa/sw.js');
})();
