const pages = document.querySelectorAll('.page');
const navLinks = document.querySelectorAll('.nav-link');
const todayDateEl = document.getElementById('todayDate');
const addClassBtn = document.getElementById('addClassBtn');
const classSelect = document.getElementById('classSelect');
const classMessage = document.getElementById('classMessage');

function formatDate(date) {
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function showPage(pageId) {
  pages.forEach((page) => {
    page.classList.toggle('active', page.id === pageId);
  });

  navLinks.forEach((link) => {
    link.classList.toggle('active', link.dataset.page === pageId);
  });
}

function resolveRoute() {
  const hash = window.location.hash.replace('#/', '') || 'home';
  const validPage = Array.from(pages).find((page) => page.id === hash) ? hash : 'home';
  showPage(validPage);
}

if (todayDateEl) {
  todayDateEl.textContent = formatDate(new Date());
}

if (addClassBtn && classSelect && classMessage) {
  addClassBtn.addEventListener('click', () => {
    const next = String(classSelect.options.length + 1);
    const option = document.createElement('option');
    option.value = next;
    option.textContent = next;
    classSelect.appendChild(option);
    classSelect.value = next;
    classMessage.textContent = `Class ${next} added.`;
  });
}

window.addEventListener('hashchange', resolveRoute);
resolveRoute();
