(() => {
  if (window.gsap) {
    gsap.from('.hero', {opacity:0, y:30, duration:1});
    gsap.from('.glass', {opacity:0, scale:.98, duration:.6, stagger:.06});
  }

  const audio = new Audio();
  let autoNext = true;
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-play-ayah');
    if (!btn) return;
    const data = new FormData();
    data.append('action', 'mhm_get_ayah_audio');
    data.append('nonce', mhmQA.nonce);
    data.append('ayah_id', btn.dataset.ayahId);
    data.append('reciter', 'Alafasy');
    const res = await fetch(mhmQA.ajaxUrl, {method:'POST', body:data});
    const json = await res.json();
    if (json.success && json.data?.audio_url) {
      audio.src = json.data.audio_url;
      audio.playbackRate = Number(localStorage.getItem('mhm_speed') || 1);
      await audio.play();
      localStorage.setItem('mhm_last_ayah', btn.dataset.ayahId);
    }
  });

  audio.addEventListener('ended', () => {
    if (!autoNext) return;
    const current = Number(localStorage.getItem('mhm_last_ayah') || 0);
    const nextBtn = document.querySelector(`.js-play-ayah[data-ayah-id="${current + 1}"]`);
    nextBtn?.click();
  });

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/wp-content/themes/mhm-quran-academy/pwa/sw.js');
  }
})();
