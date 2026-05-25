(() => {
  const audio = new Audio(); audio.preload = 'metadata';
  let playlist = []; let idx = 0;
  const els = {
    list: document.getElementById('mhm-audio-playlist'),
    surah: document.getElementById('mhm-surah-select'),
    reciter: document.getElementById('mhm-reciter-select'),
    speed: document.getElementById('mhm-speed'),
    mini: document.getElementById('mhm-mini-player'),
    title: document.getElementById('mhm-mini-title'),
    progress: document.getElementById('mhm-mini-progress'),
    toggle: document.getElementById('mhm-mini-toggle')
  };
  const stateKey = 'mhm_audio_state';

  const save = () => localStorage.setItem(stateKey, JSON.stringify({idx, t:audio.currentTime, s:els.surah?.value, r:els.reciter?.value, rate:audio.playbackRate}));
  const restore = () => { try { return JSON.parse(localStorage.getItem(stateKey)||'{}'); } catch { return {}; } };

  async function loadPlaylist() {
    if (!els.surah || !els.list) return;
    const u = `${mhmQA.audioRest}surah/${els.surah.value}?reciter=${encodeURIComponent(els.reciter?.value||'Alafasy')}`;
    const res = await fetch(u); playlist = await res.json();
    els.list.innerHTML = playlist.map((p,i)=>`<button class="card glass span-4 ayah-track" data-i="${i}">Ayah ${p.ayah_number} <small>${p.reciter||''}</small> <a href="${p.audio_url||'#'}" download>⬇</a></button>`).join('');
  }

  function playAt(i){ if(!playlist[i]||!playlist[i].audio_url) return; idx=i; audio.src=playlist[i].audio_url; audio.play(); els.mini.hidden=false; els.title.textContent=`Surah ${els.surah.value} - Ayah ${playlist[i].ayah_number}`; save(); }

  document.addEventListener('click', (e)=>{ const b=e.target.closest('.ayah-track'); if(b) playAt(Number(b.dataset.i||0)); });
  els.toggle?.addEventListener('click', ()=>{ if(audio.paused){audio.play(); els.toggle.textContent='Pause';} else {audio.pause(); els.toggle.textContent='Play';} save(); });
  els.speed?.addEventListener('input', ()=>{ audio.playbackRate=Number(els.speed.value||1); save(); });
  els.surah?.addEventListener('change', loadPlaylist); els.reciter?.addEventListener('change', loadPlaylist);

  audio.addEventListener('timeupdate', ()=>{ if(audio.duration) els.progress.style.width=`${audio.currentTime/audio.duration*100}%`; save(); });
  audio.addEventListener('ended', ()=>{ if(idx+1<playlist.length) playAt(idx+1); else if(els.surah){ els.surah.value=String(Number(els.surah.value)+1); loadPlaylist().then(()=>playAt(0)); } });

  const st = restore();
  if (els.surah && st.s) els.surah.value = st.s;
  if (els.reciter && st.r) els.reciter.value = st.r;
  if (els.speed && st.rate) els.speed.value = st.rate;
  loadPlaylist().then(()=>{ if(Number.isInteger(st.idx)&&playlist[st.idx]){ playAt(st.idx); audio.currentTime = Number(st.t||0); audio.playbackRate = Number(st.rate||1);} });
})();
