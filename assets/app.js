(function () {
  'use strict';

  function showToast(message, type = 'success') {
    let toast = document.createElement('div');
    toast.className = 'mobile-toast ' + type;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 350);
    }, 2600);
  }

  function playTone(freq = 440, duration = 120) {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = freq;
      gain.gain.value = 0.03;
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      setTimeout(() => {
        osc.stop();
        ctx.close();
      }, duration);
    } catch (err) {
      // Silently ignore audio issues
    }
  }

  function setupTyping() {
    document.querySelectorAll('[data-typing]').forEach((el) => {
      const text = el.getAttribute('data-typing') || '';
      const speed = parseInt(el.getAttribute('data-speed') || '50', 10);
      let i = 0;
      el.textContent = '';
      const timer = setInterval(() => {
        el.textContent += text.charAt(i);
        i += 1;
        if (i >= text.length) clearInterval(timer);
      }, speed);
    });
  }

  function setupLiveLeaderboard() {
    const tableBody = document.querySelector('[data-live-leaderboard]');
    if (!tableBody) return;

    const render = (rows) => {
      if (!rows.length) {
        tableBody.innerHTML = '<tr><td colspan="6">No results yet.</td></tr>';
        return;
      }

      tableBody.innerHTML = rows
        .map((row, idx) => {
          const isTop = idx === 0;
          return `
            <tr class="${isTop ? 'top-row' : ''}">
              <td>${idx + 1}</td>
              <td>${row.name}</td>
              <td>${row.class}</td>
              <td>${row.scored_marks} / ${row.total_marks}</td>
              <td>${Number(row.percent).toFixed(2)}%</td>
              <td>${isTop ? '<span class="badge">Top scorer 🔥</span>' : '-'}</td>
            </tr>
          `;
        })
        .join('');
    };

    const load = async (silent = true) => {
      try {
        const response = await fetch('/leaderboard.php', { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (data.success) {
          render(data.rows || []);
          if (!silent) showToast('Leaderboard updated', 'success');
        }
      } catch (e) {
        // Ignore intermittent polling errors
      }
    };

    load(true);
    setInterval(load, 10000);
  }

  function setupQuiz() {
    const quizForm = document.querySelector('#quizForm');
    if (!quizForm) return;

    const totalQuestions = Number(quizForm.getAttribute('data-total') || '0');
    const answeredCounter = document.querySelector('#answeredCounter');
    const progressFill = document.querySelector('#progressFill');
    const timerEl = document.querySelector('#quizTimer');
    const submitBtn = document.querySelector('#submitBtn');
    const durationSeconds = Number(quizForm.getAttribute('data-duration') || '0');

    let remaining = durationSeconds;

    const updateProgress = () => {
      const answered = quizForm.querySelectorAll('input[type="radio"]:checked').length;
      const percent = totalQuestions ? Math.round((answered / totalQuestions) * 100) : 0;
      if (answeredCounter) answeredCounter.textContent = `${answered}/${totalQuestions} answered`;
      if (progressFill) progressFill.style.width = percent + '%';
    };

    quizForm.addEventListener('change', (e) => {
      if (e.target.matches('input[type="radio"]')) {
        updateProgress();
        playTone(700, 80);
      }
    });

    const formatTime = (sec) => {
      const m = String(Math.floor(sec / 60)).padStart(2, '0');
      const s = String(sec % 60).padStart(2, '0');
      return `${m}:${s}`;
    };

    if (timerEl && remaining > 0) {
      timerEl.textContent = formatTime(remaining);
      const timer = setInterval(() => {
        remaining -= 1;
        timerEl.textContent = formatTime(Math.max(remaining, 0));

        if (remaining === 10) {
          showToast('Only 10 seconds left!', 'error');
          playTone(300, 200);
        }

        if (remaining <= 0) {
          clearInterval(timer);
          showToast('Time is up! Submitting quiz...', 'error');
          submitQuiz(true);
        }
      }, 1000);
    }

    async function submitQuiz(auto = false) {
      if (submitBtn) submitBtn.disabled = true;
      playTone(840, 150);
      const formData = new FormData(quizForm);
      formData.append('ajax', '1');

      try {
        const response = await fetch('/submit.php', {
          method: 'POST',
          body: formData,
          headers: { Accept: 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
          showToast(auto ? 'Auto-submitted successfully' : 'Quiz submitted successfully', 'success');
          window.location.href = data.redirect || '/result.php';
        } else {
          showToast(data.message || 'Submission failed', 'error');
          if (submitBtn) submitBtn.disabled = false;
        }
      } catch (err) {
        showToast('Network error while submitting quiz', 'error');
        if (submitBtn) submitBtn.disabled = false;
      }
    }

    quizForm.addEventListener('submit', function (e) {
      e.preventDefault();
      submitQuiz(false);
    });

    updateProgress();
  }

  function setupFlashToast() {
    const flash = document.querySelector('[data-flash-message]');
    if (flash && window.innerWidth <= 768) {
      showToast(flash.getAttribute('data-flash-message'), flash.getAttribute('data-flash-type') || 'success');
    }
  }

  window.quizApp = { showToast, playTone };

  document.addEventListener('DOMContentLoaded', function () {
    setupTyping();
    setupLiveLeaderboard();
    setupQuiz();
    setupFlashToast();
  });
})();
