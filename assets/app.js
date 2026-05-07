(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function toast(message, variant = 'info') {
    const host = $('#toastHost');
    if (!host) return;
    const node = document.createElement('div');
    node.className = `toast toast-${variant}`;
    node.textContent = message;
    host.appendChild(node);
    setTimeout(() => node.remove(), 3200);
  }

  function setupParticles() {
    const canvas = $('#particleCanvas');
    if (!canvas || prefersReducedMotion) return;
    const ctx = canvas.getContext('2d');
    const particles = Array.from({ length: Math.min(90, Math.floor(window.innerWidth / 16)) }, () => ({
      x: Math.random() * window.innerWidth,
      y: Math.random() * window.innerHeight,
      r: Math.random() * 1.8 + 0.4,
      vx: (Math.random() - 0.5) * 0.25,
      vy: (Math.random() - 0.5) * 0.25,
      hue: [190, 245, 275][Math.floor(Math.random() * 3)]
    }));
    const resize = () => {
      canvas.width = window.innerWidth * devicePixelRatio;
      canvas.height = window.innerHeight * devicePixelRatio;
      ctx.setTransform(devicePixelRatio, 0, 0, devicePixelRatio, 0, 0);
    };
    const tick = () => {
      ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
      particles.forEach((p, index) => {
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0 || p.x > window.innerWidth) p.vx *= -1;
        if (p.y < 0 || p.y > window.innerHeight) p.vy *= -1;
        ctx.beginPath();
        ctx.fillStyle = `hsla(${p.hue}, 95%, 70%, .72)`;
        ctx.shadowColor = `hsla(${p.hue}, 95%, 70%, .55)`;
        ctx.shadowBlur = 12;
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
        for (let j = index + 1; j < particles.length; j += 1) {
          const q = particles[j];
          const d = Math.hypot(p.x - q.x, p.y - q.y);
          if (d < 120) {
            ctx.beginPath();
            ctx.strokeStyle = `rgba(99,102,241,${(1 - d / 120) * 0.12})`;
            ctx.moveTo(p.x, p.y);
            ctx.lineTo(q.x, q.y);
            ctx.stroke();
          }
        }
      });
      requestAnimationFrame(tick);
    };
    resize();
    tick();
    window.addEventListener('resize', resize);
  }

  function setupLottie() {
    const target = $('#lockLottie');
    if (!target || !window.lottie) return;
    try {
      window.lottie.loadAnimation({
        container: target,
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: 'https://assets4.lottiefiles.com/packages/lf20_touohxv0.json'
      });
    } catch (_) {
      $('.fallback-lottie')?.style?.setProperty('display', 'block');
    }
  }

  function setupReveal() {
    const nodes = $$('.reveal');
    if (!nodes.length) return;
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.14 });
    nodes.forEach(node => observer.observe(node));
  }

  function setupCounters() {
    const run = node => {
      const target = Number(node.dataset.count || 0);
      const money = target > 10000;
      let current = 0;
      const steps = 48;
      const increment = target / steps;
      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        node.textContent = money ? `₹${Math.round(current).toLocaleString('en-IN')}` : Math.round(current).toLocaleString('en-IN');
      }, 24);
    };
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          run(entry.target);
          observer.unobserve(entry.target);
        }
      });
    });
    $$('[data-count]').forEach(node => observer.observe(node));
  }

  function setupRipple() {
    document.addEventListener('click', event => {
      const target = event.target.closest('.ripple');
      if (!target) return;
      const rect = target.getBoundingClientRect();
      const circle = document.createElement('span');
      const size = Math.max(rect.width, rect.height);
      circle.className = 'ripple-circle';
      circle.style.width = circle.style.height = `${size}px`;
      circle.style.left = `${event.clientX - rect.left - size / 2}px`;
      circle.style.top = `${event.clientY - rect.top - size / 2}px`;
      target.appendChild(circle);
      setTimeout(() => circle.remove(), 650);
    });
  }

  function setupTilt() {
    if (prefersReducedMotion || window.matchMedia('(pointer: coarse)').matches) return;
    $$('[data-tilt]').forEach(card => {
      card.addEventListener('mousemove', event => {
        const rect = card.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / rect.width - 0.5) * 8;
        const y = ((event.clientY - rect.top) / rect.height - 0.5) * -8;
        card.style.transform = `perspective(900px) rotateY(${x}deg) rotateX(${y}deg) translateY(-4px)`;
      });
      card.addEventListener('mouseleave', () => { card.style.transform = ''; });
    });
  }

  function setupNavigation() {
    $('#menuToggle')?.addEventListener('click', () => $('#sidebar')?.classList.toggle('open'));
    $$('.side-nav a').forEach(link => link.addEventListener('click', () => $('#sidebar')?.classList.remove('open')));
  }

  function setupModals() {
    document.addEventListener('click', event => {
      const opener = event.target.closest('[data-modal]');
      if (opener) {
        const modal = document.getElementById(opener.dataset.modal);
        modal?.classList.add('show');
        modal?.setAttribute('aria-hidden', 'false');
      }
      if (event.target.matches('[data-close]') || event.target.classList.contains('modal-backdrop')) {
        const modal = event.target.closest('.modal-backdrop') || event.target;
        modal?.classList.remove('show');
        modal?.setAttribute('aria-hidden', 'true');
      }
      const success = event.target.closest('[data-success]');
      if (success) {
        toast(success.dataset.success, 'success');
        success.closest('.modal-backdrop')?.classList.remove('show');
      }
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') $$('.modal-backdrop.show').forEach(modal => modal.classList.remove('show'));
    });
  }

  function setupTheme() {
    const themes = ['theme-dark', 'theme-light', 'theme-neon'];
    const saved = localStorage.getItem('astra-theme');
    if (saved && themes.includes(saved)) document.body.className = document.body.className.replace(/theme-\w+/, saved);
    $('#themeToggle')?.addEventListener('click', () => {
      const current = themes.findIndex(theme => document.body.classList.contains(theme));
      const next = themes[(current + 1) % themes.length];
      document.body.classList.remove(...themes);
      document.body.classList.add(next);
      localStorage.setItem('astra-theme', next);
      toast(`Theme switched to ${next.replace('theme-', '')}`);
    });
  }

  function setupSessionTimer() {
    const node = $('#sessionCountdown');
    if (!node) return;
    let seconds = 300;
    setInterval(() => {
      seconds -= 1;
      const mm = String(Math.floor(seconds / 60)).padStart(2, '0');
      const ss = String(seconds % 60).padStart(2, '0');
      node.textContent = `${mm}:${ss}`;
      if (seconds === 60) {
        $('#secureSessionModal')?.classList.add('show');
        beep();
        toast('Auto logout warning: 60 seconds remaining', 'warning');
      }
      if (seconds <= 0) seconds = 300;
    }, 1000);
  }

  function beep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.frequency.value = 220;
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.03);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.45);
      osc.connect(gain).connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.5);
    } catch (_) {
      // Audio is best-effort and may be blocked until user interaction.
    }
  }

  function setupDashboard() {
    const canvas = $('#analyticsChart');
    if (canvas && window.Chart) {
      new Chart(canvas, {
        type: 'line',
        data: {
          labels: ['Muharram', 'Safar', 'Rabi I', 'Rabi II', 'Jumada I', 'Jumada II'],
          datasets: [{
            label: 'Receipts',
            data: [240, 310, 420, 390, 520, 680],
            borderColor: '#22d3ee',
            backgroundColor: 'rgba(34,211,238,.14)',
            tension: .42,
            fill: true
          }, {
            label: 'Verified quizzes',
            data: [180, 280, 360, 470, 510, 640],
            borderColor: '#a855f7',
            backgroundColor: 'rgba(168,85,247,.12)',
            tension: .42,
            fill: true
          }]
        },
        options: { responsive: true, plugins: { legend: { labels: { color: '#cbd5e1' } } }, scales: { x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.12)' } }, y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.12)' } } } }
      });
    }
    const feed = $('#activityFeed');
    if (feed) {
      ['Receipt #1021 approved', 'Telegram OTP verified', 'Class 5 monthly plan exported', 'Deleted lesson restored from trash', 'Secure review auto-close armed'].forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'activity-item skeleton';
        feed.appendChild(row);
        setTimeout(() => { row.classList.remove('skeleton'); row.textContent = item; }, 350 + index * 160);
      });
    }
    const search = $('#tableSearch');
    const filter = $('#statusFilter');
    const apply = () => {
      const term = (search?.value || '').toLowerCase();
      const status = filter?.value || '';
      $$('#opsTable tbody tr').forEach(tr => {
        const matchTerm = tr.textContent.toLowerCase().includes(term);
        const matchStatus = !status || tr.textContent.includes(status);
        tr.style.display = matchTerm && matchStatus ? '' : 'none';
      });
    };
    search?.addEventListener('input', apply);
    filter?.addEventListener('change', apply);
    $('#exportPdfBtn')?.addEventListener('click', () => {
      if (window.jspdf && window.html2canvas) toast('PDF export pipeline ready');
      else toast('Export libraries are still loading', 'warning');
    });
    if (window.Sortable && $('#activityFeed')) Sortable.create($('#activityFeed'), { animation: 180 });
  }

  function setupGlobalSearch() {
    $('#globalSearch')?.addEventListener('input', event => {
      if (event.target.value.length > 2) toast(`Searching for “${event.target.value}”`);
    });
  }

  function setupPwa() {
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(() => {});
    let deferredPrompt;
    const installBtn = $('#installBtn');
    window.addEventListener('beforeinstallprompt', event => {
      event.preventDefault();
      deferredPrompt = event;
      if (installBtn) installBtn.hidden = false;
    });
    installBtn?.addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      installBtn.hidden = true;
      deferredPrompt = null;
    });
  }

  function setupFocusSecurity() {
    const warning = $('#focusWarning');
    const show = () => { document.body.classList.add('tab-inactive'); warning?.classList.add('show'); };
    const hide = () => { document.body.classList.remove('tab-inactive'); warning?.classList.remove('show'); };
    document.addEventListener('visibilitychange', () => document.hidden ? show() : hide());
    window.addEventListener('blur', show);
    window.addEventListener('focus', hide);
  }

  function labelMobileTable() {
    $$('.premium-table').forEach(table => {
      const headers = $$('thead th', table).map(th => th.textContent.trim());
      $$('tbody tr', table).forEach(row => $$('td', row).forEach((cell, index) => cell.dataset.label = headers[index] || 'Field'));
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    setupParticles();
    setupLottie();
    setupReveal();
    setupCounters();
    setupRipple();
    setupTilt();
    setupNavigation();
    setupModals();
    setupTheme();
    setupSessionTimer();
    setupDashboard();
    setupGlobalSearch();
    setupPwa();
    setupFocusSecurity();
    labelMobileTable();
    setTimeout(() => toast('AstraEdu secure UI loaded', 'success'), 600);
  });
})();
