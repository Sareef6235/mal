document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const table = document.getElementById('pendingTable');
  const rows = table ? Array.from(table.querySelectorAll('tbody tr')) : [];
  const liveSearch = document.getElementById('liveSearch');
  const entriesSelect = document.getElementById('entriesPerPage');
  const prevBtn = document.getElementById('prevPage');
  const nextBtn = document.getElementById('nextPage');
  const pageInfo = document.getElementById('pageInfo');
  let currentPage = 1;
  let filteredRows = rows.slice();

  document.querySelectorAll('.counter').forEach(counter => {
    const target = Number(counter.dataset.target || 0);
    const step = Math.max(1, Math.ceil(target / 40));
    let value = 0;
    const timer = setInterval(() => {
      value += step;
      if (value >= target) { value = target; clearInterval(timer); }
      counter.textContent = value.toLocaleString('en-IN');
    }, 25);
  });

  document.getElementById('themeToggle')?.addEventListener('click', () => {
    body.classList.toggle('pending-dark');
  });

  document.getElementById('drawerBtn')?.addEventListener('click', () => {
    document.getElementById('pendingSidebar')?.classList.toggle('open');
  });

  document.querySelectorAll('.month-chip').forEach(button => {
    button.addEventListener('click', () => button.nextElementSibling?.classList.toggle('open'));
  });

  function renderPage() {
    const perPage = Number(entriesSelect?.value || 10);
    const pages = Math.max(1, Math.ceil(filteredRows.length / perPage));
    currentPage = Math.min(currentPage, pages);
    rows.forEach(row => row.style.display = 'none');
    filteredRows.slice((currentPage - 1) * perPage, currentPage * perPage).forEach(row => row.style.display = '');
    if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${pages}`;
    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= pages;
  }

  liveSearch?.addEventListener('input', () => {
    const term = liveSearch.value.toLowerCase().trim();
    filteredRows = rows.filter(row => row.textContent.toLowerCase().includes(term));
    currentPage = 1;
    renderPage();
  });
  entriesSelect?.addEventListener('change', () => { currentPage = 1; renderPage(); });
  prevBtn?.addEventListener('click', () => { currentPage--; renderPage(); });
  nextBtn?.addEventListener('click', () => { currentPage++; renderPage(); });

  table?.querySelectorAll('thead th').forEach((th, index) => {
    th.addEventListener('click', () => {
      const asc = th.dataset.sort !== 'asc';
      th.dataset.sort = asc ? 'asc' : 'desc';
      filteredRows.sort((a, b) => {
        const av = a.children[index].textContent.trim();
        const bv = b.children[index].textContent.trim();
        return asc ? av.localeCompare(bv, undefined, {numeric:true}) : bv.localeCompare(av, undefined, {numeric:true});
      });
      const tbody = table.querySelector('tbody');
      filteredRows.forEach(row => tbody.appendChild(row));
      currentPage = 1;
      renderPage();
    });
  });

  document.getElementById('resetFilters')?.addEventListener('click', () => {
    if (confirm('Reset all filters?')) {
      document.getElementById('loadingOverlay').style.display = 'flex';
      window.location.href = 'pending_fees_export.php';
    }
  });

  document.querySelectorAll('.export-confirm').forEach(button => {
    button.addEventListener('click', () => {
      const url = button.dataset.url;
      const type = button.dataset.type;
      const modalEl = document.getElementById('confirmModal');
      document.getElementById('confirmText').textContent = `Export the currently filtered pending records as ${type}?`;
      document.getElementById('confirmExportBtn').href = url;
      if (window.bootstrap && modalEl) new bootstrap.Modal(modalEl).show();
      else if (confirm(`Export ${type}?`)) window.location.href = url;
    });
  });

  document.getElementById('confirmExportBtn')?.addEventListener('click', () => {
    document.getElementById('loadingOverlay').style.display = 'flex';
    setTimeout(() => document.getElementById('loadingOverlay').style.display = 'none', 2500);
  });

  renderPage();
});
