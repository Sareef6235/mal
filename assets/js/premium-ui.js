const $ = (selector, scope = document) => scope.querySelector(selector);
const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

const sidebar = $('#sidebar');
const menuToggle = $('#menuToggle');
const toastStack = $('#toastStack');

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast glass-panel';
    toast.textContent = message;
    toastStack.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
}

menuToggle?.addEventListener('click', () => {
    const isOpen = sidebar.classList.toggle('open');
    menuToggle.setAttribute('aria-expanded', String(isOpen));
});

$$('[data-nav-link], .mobile-bottom a').forEach((link) => {
    link.addEventListener('click', () => {
        $$('[data-nav-link]').forEach((item) => item.classList.remove('active'));
        $(`[data-nav-link][href="${link.getAttribute('href')}"]`)?.classList.add('active');
        sidebar.classList.remove('open');
        menuToggle?.setAttribute('aria-expanded', 'false');
    });
});

$('#themeToggle')?.addEventListener('click', () => {
    const root = document.documentElement;
    const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = nextTheme;
    $('#themeToggle i').className = nextTheme === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
    showToast(`${nextTheme === 'dark' ? 'Dark' : 'Light'} mode enabled`);
});

$$('[data-open-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.openModal);
        modal?.classList.add('active');
        modal?.querySelector('button, input')?.focus();
    });
});

$$('[data-close-modal], .modal-layer').forEach((element) => {
    element.addEventListener('click', (event) => {
        if (event.target.matches('[data-close-modal]') || event.target.classList.contains('modal-layer')) {
            event.target.closest('.modal-layer')?.classList.remove('active');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        $$('.modal-layer.active').forEach((modal) => modal.classList.remove('active'));
        sidebar.classList.remove('open');
    }
});

$$('.ripple').forEach((button) => {
    button.addEventListener('click', (event) => {
        const dot = document.createElement('span');
        const rect = button.getBoundingClientRect();
        dot.className = 'ripple-dot';
        dot.style.left = `${event.clientX - rect.left}px`;
        dot.style.top = `${event.clientY - rect.top}px`;
        button.appendChild(dot);
        setTimeout(() => dot.remove(), 700);
    });
});

$$('.option').forEach((option) => {
    option.addEventListener('click', () => {
        $$('.option').forEach((item) => item.classList.remove('selected'));
        option.classList.add('selected');
        showToast('Answer auto-saved securely');
    });
});

$('[data-next-question]')?.addEventListener('click', () => {
    const card = $('[data-question-card]');
    card.animate([{ opacity: 1, transform: 'translateX(0)' }, { opacity: 0, transform: 'translateX(-26px)' }, { opacity: 1, transform: 'translateX(0)' }], { duration: 520, easing: 'cubic-bezier(.2,.8,.2,1)' });
    showToast('Next encrypted question loaded');
});

const questionSearch = $('#questionSearch');
questionSearch?.addEventListener('input', () => {
    const term = questionSearch.value.trim().toLowerCase();
    let visible = 0;
    $$('#questionGrid .mcq-card').forEach((card) => {
        const match = card.dataset.title.includes(term);
        card.style.display = match ? '' : 'none';
        if (match) visible += 1;
    });
    $('#emptyState').hidden = visible !== 0;
});

let seconds = 24 * 60 + 59;
setInterval(() => {
    seconds = Math.max(0, seconds - 1);
    const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
    const secs = String(seconds % 60).padStart(2, '0');
    $('#quizTimer').textContent = `${minutes}:${secs}`;
    if (seconds === 60) showToast('One minute remaining');
}, 1000);

const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            entry.target.style.animationPlayState = 'running';
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.16 });
$$('.section-reveal').forEach((section) => observer.observe(section));

$$('.counter').forEach((counter) => {
    const target = Number(counter.dataset.count || '0');
    if (!target) return;
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 48));
    const tick = () => {
        current = Math.min(target, current + step);
        counter.textContent = counter.textContent.includes('%') ? `${current}%` : current.toLocaleString();
        if (current < target) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
});

$('#globalSearch')?.addEventListener('input', (event) => {
    if (event.target.value.length > 2) showToast('Universal search indexing dashboard content…');
});
