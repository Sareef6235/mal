const root = document.documentElement;
const preloader = document.getElementById('preloader');
const navLinks = document.getElementById('navLinks');
const hamburger = document.getElementById('hamburger');
const mouseGlow = document.querySelector('.mouse-glow');
const toast = document.getElementById('toast');
let soundEnabled = true;

window.addEventListener('load', () => {
  setTimeout(() => preloader?.classList.add('hidden'), 650);
  initChart();
  animateCounters();
});

hamburger?.addEventListener('click', () => {
  const open = navLinks.classList.toggle('open');
  hamburger.setAttribute('aria-expanded', String(open));
});

navLinks?.addEventListener('click', event => {
  if (event.target.matches('a')) {
    navLinks.classList.remove('open');
    hamburger?.setAttribute('aria-expanded', 'false');
  }
});

const themes = ['dark', 'light', 'neon'];
document.getElementById('themeSwitcher')?.addEventListener('click', () => {
  const current = root.dataset.theme || 'dark';
  root.dataset.theme = themes[(themes.indexOf(current) + 1) % themes.length];
  showToast(`Theme switched to ${root.dataset.theme}`);
});

document.getElementById('soundToggle')?.addEventListener('click', event => {
  soundEnabled = !soundEnabled;
  event.currentTarget.innerHTML = `<i class="bi bi-volume-${soundEnabled ? 'up' : 'mute'}"></i>`;
  showToast(soundEnabled ? 'Interface sounds enabled' : 'Interface sounds muted');
});

document.addEventListener('mousemove', event => {
  if (!mouseGlow) return;
  mouseGlow.style.left = `${event.clientX}px`;
  mouseGlow.style.top = `${event.clientY}px`;
});

document.addEventListener('click', event => {
  const target = event.target.closest('.btn, .feature-card, .icon-btn, .fab, .bottom-nav button');
  if (!target) return;
  const ripple = document.createElement('span');
  ripple.className = 'ripple';
  const rect = target.getBoundingClientRect();
  ripple.style.left = `${event.clientX - rect.left}px`;
  ripple.style.top = `${event.clientY - rect.top}px`;
  ripple.style.width = ripple.style.height = `${Math.max(rect.width, rect.height)}px`;
  target.appendChild(ripple);
  ripple.addEventListener('animationend', () => ripple.remove());
});

const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.14 });
document.querySelectorAll('.reveal').forEach(element => revealObserver.observe(element));

const sections = [...document.querySelectorAll('main section[id]')];
const menuItems = [...document.querySelectorAll('.nav-links a')];
const scrollSpy = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    menuItems.forEach(item => item.classList.toggle('active', item.getAttribute('href') === `#${entry.target.id}`));
  });
}, { rootMargin: '-45% 0px -45% 0px' });
sections.forEach(section => scrollSpy.observe(section));

function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(counter => {
    const target = Number(counter.dataset.count);
    const duration = 1400;
    const start = performance.now();
    const tick = now => {
      const progress = Math.min((now - start) / duration, 1);
      const value = target * (1 - Math.pow(1 - progress, 3));
      counter.textContent = target % 1 ? value.toFixed(1) : Math.round(value).toLocaleString('en-IN');
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });
}

function initChart() {
  const canvas = document.getElementById('financeChart');
  if (!canvas || !window.Chart) return;
  const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 260);
  gradient.addColorStop(0, 'rgba(34, 211, 238, .42)');
  gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');
  new Chart(canvas, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [{
        label: 'Receipts',
        data: [42000, 57000, 49000, 76000, 84500, 98000],
        fill: true,
        backgroundColor: gradient,
        borderColor: '#22d3ee',
        borderWidth: 3,
        tension: .42,
        pointRadius: 4,
        pointBackgroundColor: '#6366f1'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#a5b4fc' } },
        y: { grid: { color: 'rgba(148,163,184,.14)' }, ticks: { color: '#a5b4fc' } }
      }
    }
  });
}

document.querySelectorAll('[data-open-modal]').forEach(trigger => {
  trigger.addEventListener('click', () => openModal(trigger.dataset.openModal));
});

document.querySelectorAll('.modal').forEach(modal => {
  modal.addEventListener('click', event => {
    if (event.target === modal || event.target.closest('.close-modal')) closeModal(modal);
  });
});

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') document.querySelectorAll('.modal.open').forEach(closeModal);
});

function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add('open');
  document.body.classList.add('modal-open');
  modal.querySelector('button, input, textarea, a')?.focus();
}

function closeModal(modal) {
  modal.classList.remove('open');
  if (!document.querySelector('.modal.open')) document.body.classList.remove('modal-open');
  if (soundEnabled) playSoftClose();
}

function playSoftClose() {
  try {
    const context = new AudioContext();
    const oscillator = context.createOscillator();
    const gain = context.createGain();
    oscillator.frequency.value = 420;
    gain.gain.setValueAtTime(0.0001, context.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.035, context.currentTime + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.16);
    oscillator.connect(gain).connect(context.destination);
    oscillator.start();
    oscillator.stop(context.currentTime + 0.17);
  } catch (_) {
    // AudioContext may be blocked by browser autoplay policies; UI remains functional.
  }
}

function showToast(message) {
  toast.querySelector('span').textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2400);
}

document.getElementById('contactForm')?.addEventListener('submit', event => {
  event.preventDefault();
  showToast('Encrypted request sent successfully');
  event.currentTarget.reset();
});

document.getElementById('exportPdf')?.addEventListener('click', async () => {
  showToast('Secure PDF export preparing...');
  const target = document.querySelector('.dashboard-shell');
  if (!target || !window.html2canvas || !window.jspdf) return;
  const canvas = await html2canvas(target, { backgroundColor: '#020617', scale: 1 });
  const pdf = new window.jspdf.jsPDF('landscape', 'mm', 'a4');
  const width = pdf.internal.pageSize.getWidth();
  const height = (canvas.height * width) / canvas.width;
  pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, width, Math.min(height, pdf.internal.pageSize.getHeight()));
  pdf.save('noorops-dashboard-preview.pdf');
});

document.addEventListener('visibilitychange', () => {
  document.body.classList.toggle('tab-hidden', document.hidden);
});

document.addEventListener('copy', event => {
  event.preventDefault();
  showToast('Copy restricted in secure mode');
  openModal('securityModal');
});

const canvas = document.getElementById('particleCanvas');
const ctx = canvas?.getContext('2d');
let particles = [];
function resizeParticles() {
  if (!canvas) return;
  canvas.width = innerWidth * devicePixelRatio;
  canvas.height = innerHeight * devicePixelRatio;
  particles = Array.from({ length: Math.min(90, Math.floor(innerWidth / 16)) }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: (Math.random() * 2 + .5) * devicePixelRatio,
    vx: (Math.random() - .5) * .28 * devicePixelRatio,
    vy: (Math.random() - .5) * .28 * devicePixelRatio
  }));
}
function drawParticles() {
  if (!ctx || !canvas) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  particles.forEach(particle => {
    particle.x += particle.vx;
    particle.y += particle.vy;
    if (particle.x < 0 || particle.x > canvas.width) particle.vx *= -1;
    if (particle.y < 0 || particle.y > canvas.height) particle.vy *= -1;
    ctx.beginPath();
    ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(34, 211, 238, .46)';
    ctx.fill();
  });
  requestAnimationFrame(drawParticles);
}
resizeParticles();
drawParticles();
addEventListener('resize', resizeParticles);
