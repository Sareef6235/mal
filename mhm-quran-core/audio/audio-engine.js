(() => {
  const audio = new Audio();
  let tracks = [], idx = 0;
  const wrap = document.querySelector('[data-qc-audio-page]');
  if (!wrap) return;
  const list = wrap.querySelector('.js-track-list');
  const reciterSel = wrap.querySelector('.js-reciter');
  const speed = wrap.querySelector('.js-speed');
  const mini = wrap.querySelector('.js-mini-player');
  const wave = wrap.querySelector('.js-wave');

  const load = async () => {
    const surah = wrap.dataset.surah || '1';
    const rec = reciterSel?.value || '1';
    const r = await fetch(`${mhmQC.rest}audio/${surah}?reciter_id=${rec}`); tracks = await r.json();
    list.innerHTML = tracks.map((t,i)=>`<button class="qc-btn js-track" data-i="${i}">Ayah ${t.ayah_number}</button>`).join('');
  };
  const play = (i) => { if(!tracks[i]||!tracks[i].audio_url) return; idx=i; audio.src=tracks[i].audio_url; audio.play(); mini.hidden=false; };
  document.addEventListener('click',(e)=>{const b=e.target.closest('.js-track'); if(b) play(Number(b.dataset.i));});
  reciterSel?.addEventListener('change', load);
  speed?.addEventListener('input',()=>audio.playbackRate=Number(speed.value||1));
  audio.addEventListener('ended',()=>{ if(idx+1<tracks.length) play(idx+1); });
  audio.addEventListener('timeupdate',()=>{ if(wave) wave.style.opacity = String((Math.sin(audio.currentTime*8)+1)/2); localStorage.setItem('qc_audio_t', String(audio.currentTime)); });
  load();
})();
