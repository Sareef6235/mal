// settings.js: UI state, sound system, settings panel interactions.
const config = window.APP_CONFIG;

const state = {
  mute: localStorage.getItem('chat_mute') === '1',
  volume: Number(localStorage.getItem('chat_volume') ?? 0.8),
  autoSync: localStorage.getItem('chat_autosync') !== '0',
  selectedSound: localStorage.getItem('chat_sound') || 'bell',
  canPlayAudio: false
};

const builtInSounds = [
  { id: 'bell', name: 'Bell', file: 'https://actions.google.com/sounds/v1/alarms/digital_watch_alarm_long.ogg' },
  { id: 'soft_ping', name: 'Soft Ping', file: 'https://actions.google.com/sounds/v1/alarms/beep_short.ogg' },
  { id: 'whatsapp_tone', name: 'WhatsApp Tone', file: 'https://actions.google.com/sounds/v1/cartoon/pop.ogg' },
  { id: 'telegram_pop', name: 'Telegram Pop', file: 'https://actions.google.com/sounds/v1/cartoon/clang_and_wobble.ogg' }
];

const els = {
  settingsBtn: document.getElementById('settingsBtn'),
  settingsPanel: document.getElementById('settingsPanel'),
  overlay: document.getElementById('overlay'),
  autoSyncToggle: document.getElementById('autoSyncToggle'),
  autoSyncText: document.getElementById('autoSyncText'),
  muteToggle: document.getElementById('muteToggle'),
  volume: document.getElementById('volume'),
  soundList: document.getElementById('soundList'),
  toast: document.getElementById('toast'),
  uploadBtn: document.getElementById('uploadBtn'),
  customSound: document.getElementById('customSound'),
  uploadMsg: document.getElementById('uploadMsg')
};

function updateSettingsUI() {
  els.muteToggle.textContent = state.mute ? 'Unmute' : 'Mute';
  els.volume.value = String(state.volume);
  els.autoSyncText.textContent = state.autoSync ? 'Auto sync active' : 'Auto sync disabled';
}

function toast(message) {
  els.toast.textContent = message;
  els.toast.classList.add('show');
  setTimeout(() => els.toast.classList.remove('show'), 2200);
}

function playById(soundId) {
  if (!state.canPlayAudio || state.mute) return;
  const item = window.chatRuntime.availableSounds.find(s => s.id === soundId);
  if (!item) return;
  const audio = new Audio(item.file);
  audio.volume = state.volume;
  audio.play().catch(() => {
    // Gracefully ignore autoplay restrictions until a gesture unlocks audio.
  });
}

function requestDesktopNotification(msg) {
  if (!('Notification' in window)) return;
  if (Notification.permission === 'granted') {
    new Notification('New chat message', { body: msg.slice(0, 100) });
    return;
  }
  if (Notification.permission !== 'denied') {
    Notification.requestPermission().catch(() => {});
  }
}

function renderSoundList() {
  els.soundList.innerHTML = '';
  window.chatRuntime.availableSounds.forEach(sound => {
    const row = document.createElement('div');
    row.className = `sound-item ${sound.id === state.selectedSound ? 'active' : ''}`;
    row.innerHTML = `<strong>${sound.name}</strong>
      <div>
        <button class="btn preview">Preview</button>
        <button class="btn select">Select</button>
      </div>`;
    row.querySelector('.preview').addEventListener('click', () => {
      row.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.03)' }, { transform: 'scale(1)' }], { duration: 280 });
      playById(sound.id);
    });
    row.querySelector('.select').addEventListener('click', () => {
      state.selectedSound = sound.id;
      localStorage.setItem('chat_sound', sound.id);
      renderSoundList();
      toast(`Selected ${sound.name}`);
    });
    els.soundList.appendChild(row);
  });
}

async function loadUploadedSounds() {
  try {
    const res = await fetch(`${config.endpoints.upload}?action=list`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.ok) return;
    const uploaded = data.sounds.map(s => ({ id: `up_${s.id}`, name: s.name, file: s.file }));
    window.chatRuntime.availableSounds = [...builtInSounds, ...uploaded];
    renderSoundList();
  } catch {
    window.chatRuntime.availableSounds = [...builtInSounds];
    renderSoundList();
  }
}

function initPanel() {
  els.settingsBtn.addEventListener('click', () => {
    els.settingsPanel.classList.add('open');
    els.overlay.classList.add('open');
  });
  els.overlay.addEventListener('click', () => {
    els.settingsPanel.classList.remove('open');
    els.overlay.classList.remove('open');
  });

  els.autoSyncToggle.addEventListener('click', () => {
    state.autoSync = !state.autoSync;
    localStorage.setItem('chat_autosync', state.autoSync ? '1' : '0');
    updateSettingsUI();
    toast(state.autoSync ? 'Auto sync enabled' : 'Auto sync disabled');
  });

  els.muteToggle.addEventListener('click', () => {
    state.mute = !state.mute;
    localStorage.setItem('chat_mute', state.mute ? '1' : '0');
    updateSettingsUI();
  });

  els.volume.addEventListener('input', (e) => {
    state.volume = Number(e.target.value);
    localStorage.setItem('chat_volume', String(state.volume));
  });

  els.uploadBtn.addEventListener('click', async () => {
    const file = els.customSound.files[0];
    if (!file) return toast('Select an MP3 first');
    const fd = new FormData();
    fd.append('sound', file);
    fd.append('name', file.name.replace(/\.[^.]+$/, ''));
    try {
      const res = await fetch(config.endpoints.upload, { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      els.uploadMsg.textContent = data.message || 'Done';
      if (data.ok) {
        toast('Uploaded custom sound');
        loadUploadedSounds();
      }
    } catch {
      els.uploadMsg.textContent = 'Upload failed';
    }
  });
}

function initAudioUnlock() {
  const unlock = () => {
    state.canPlayAudio = true;
    document.removeEventListener('click', unlock);
    document.removeEventListener('keydown', unlock);
  };
  document.addEventListener('click', unlock);
  document.addEventListener('keydown', unlock);
}

function initCursorGlow() {
  const glow = document.getElementById('cursorGlow');
  document.addEventListener('mousemove', e => {
    glow.style.left = `${e.clientX}px`;
    glow.style.top = `${e.clientY}px`;
  });
}

window.chatRuntime = {
  availableSounds: [...builtInSounds],
  state,
  toast,
  playIncoming: () => playById(state.selectedSound),
  playOutgoing: () => playById('soft_ping'),
  playSeen: () => playById('telegram_pop'),
  notify: requestDesktopNotification
};

updateSettingsUI();
initPanel();
initAudioUnlock();
initCursorGlow();
loadUploadedSounds();
