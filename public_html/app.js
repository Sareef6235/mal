const API_BASE = 'api';
const isLoginPage = window.location.pathname.endsWith('login.html') || window.location.pathname.endsWith('/');

const state = {
  user: null,
  items: [],
  categories: [],
  users: [],
  chart: null,
  confirmAction: null,
};

const qs = (s) => document.querySelector(s);
const qsa = (s) => [...document.querySelectorAll(s)];

const showToast = (message, type = 'success') => {
  const container = qs('#toastContainer');
  if (!container) return;
  const el = document.createElement('div');
  el.className = `toast-enter transition-all duration-300 rounded-xl shadow-soft px-4 py-3 text-sm font-medium ${type === 'error' ? 'bg-red-600 text-white' : 'bg-slate-900 text-white'}`;
  el.textContent = message;
  container.appendChild(el);
  requestAnimationFrame(() => el.classList.add('toast-show'));
  setTimeout(() => {
    el.classList.remove('toast-show');
    setTimeout(() => el.remove(), 300);
  }, 2600);
};

const api = async (path, method = 'GET', data = null) => {
  const options = { method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json' } };
  if (data) options.body = JSON.stringify(data);
  const res = await fetch(`${API_BASE}/${path}`, options);
  const json = await res.json();
  if (!res.ok || !json.success) throw new Error(json.message || 'Request failed');
  return json;
};

const applyTheme = () => {
  const stored = localStorage.getItem('theme') || 'light';
  document.documentElement.classList.toggle('dark', stored === 'dark');
};

const registerPwa = async () => {
  if ('serviceWorker' in navigator) {
    try {
      await navigator.serviceWorker.register('./service-worker.js');
    } catch (e) {
      console.warn('SW registration failed', e);
    }
  }
};

const setupLogin = () => {
  applyTheme();
  registerPwa();
  const form = qs('#loginForm');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const button = qs('#loginButton');
    const error = qs('#loginError');
    button.disabled = true;
    button.textContent = 'Signing in...';
    error.classList.add('hidden');
    try {
      const payload = { email: qs('#email').value.trim(), password: qs('#password').value };
      await api('login.php', 'POST', payload);
      showToast('Login successful');
      window.location.href = 'dashboard.html';
    } catch (err) {
      error.textContent = err.message;
      error.classList.remove('hidden');
      showToast(err.message, 'error');
    } finally {
      button.disabled = false;
      button.textContent = 'Sign In';
    }
  });
};

const setLoadingSkeletons = () => {
  const stats = qs('#statsGrid');
  if (!stats) return;
  stats.innerHTML = Array.from({ length: 4 }).map(() => '<div class="glass rounded-2xl p-4 shadow-soft"><div class="skeleton h-6 w-20 rounded"></div><div class="skeleton h-8 w-16 mt-3 rounded"></div></div>').join('');
};

const renderStats = () => {
  const total = state.items.length;
  const done = state.items.filter((i) => i.status === 'done').length;
  const pending = state.items.filter((i) => i.status === 'pending').length;
  const inProgress = state.items.filter((i) => i.status === 'in_progress').length;
  const cards = [
    ['Total Items', total],
    ['Completed', done],
    ['Pending', pending],
    ['In Progress', inProgress],
  ];
  qs('#statsGrid').innerHTML = cards.map(([label, value]) => `<div class="glass rounded-2xl p-4 shadow-soft"><p class="text-sm text-slate-500 dark:text-slate-300">${label}</p><p class="text-3xl font-bold mt-2">${value}</p></div>`).join('');
};

const renderActivities = () => {
  const items = state.items.slice(0, 6);
  qs('#activityList').innerHTML = items.map((it) => `<li class="border-b border-slate-200 dark:border-slate-700 pb-2"><p class="font-medium">${it.title}</p><p class="text-slate-500">${it.status.replace('_', ' ')} · ${it.updated_at}</p></li>`).join('');
};

const renderChart = () => {
  const ctx = qs('#progressChart');
  if (!ctx) return;
  const monthly = Array(12).fill(0);
  state.items.forEach((it) => {
    const m = new Date(it.due_date).getMonth();
    if (!Number.isNaN(m)) monthly[m] += 1;
  });
  if (state.chart) state.chart.destroy();
  state.chart = new Chart(ctx, {
    type: 'line',
    data: { labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], datasets: [{ label: 'Items', data: monthly, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.16)', fill: true, tension: 0.35 }] },
    options: { responsive: true, plugins: { legend: { display: false } } }
  });
};

const filteredItems = () => {
  const search = qs('#searchInput').value.toLowerCase().trim();
  const category = qs('#categoryFilter').value;
  const status = qs('#statusFilter').value;
  return state.items.filter((i) => (!search || i.title.toLowerCase().includes(search)) && (!category || String(i.category_id) === category) && (!status || i.status === status));
};

const renderCategoryOptions = () => {
  const options = ['<option value="">All categories</option>', ...state.categories.map((c) => `<option value="${c.id}">${c.name}</option>`)];
  qs('#categoryFilter').innerHTML = options.join('');
  qs('#itemCategory').innerHTML = state.categories.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
};

const renderItems = () => {
  const tbody = qs('#itemsTbody');
  const rows = filteredItems().map((it, idx) => `<tr data-id="${it.id}" class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-100/60 dark:hover:bg-slate-800/60">
      <td class="py-2 cursor-move">☰ ${idx + 1}</td>
      <td contenteditable="true" class="editable" data-field="title">${it.title}</td>
      <td>${it.category_name}</td>
      <td contenteditable="true" class="editable" data-field="status">${it.status}</td>
      <td contenteditable="true" class="editable" data-field="due_date">${it.due_date}</td>
      <td><button class="edit-btn text-blue-600">Edit</button> | <button class="delete-btn text-red-600">Delete</button></td>
    </tr>`).join('');
  tbody.innerHTML = rows || '<tr><td colspan="6" class="py-4 text-center text-slate-500">No data found</td></tr>';
  setupRowEvents();
  setupSortable();
};

const setupRowEvents = () => {
  qsa('#itemsTbody .delete-btn').forEach((btn) => {
    btn.onclick = () => {
      const id = btn.closest('tr').dataset.id;
      confirmDialog('Delete this item?', async () => {
        await api('delete_data.php', 'POST', { id });
        showToast('Item deleted');
        await loadData();
      });
    };
  });

  qsa('#itemsTbody .edit-btn').forEach((btn) => {
    btn.onclick = () => openItemModal(state.items.find((i) => String(i.id) === btn.closest('tr').dataset.id));
  });

  qsa('#itemsTbody .editable').forEach((cell) => {
    cell.onblur = async () => {
      const tr = cell.closest('tr');
      const item = state.items.find((i) => String(i.id) === tr.dataset.id);
      const field = cell.dataset.field;
      if (!item || !field) return;
      const value = cell.textContent.trim();
      if (value === String(item[field])) return;
      try {
        await api('update_data.php', 'POST', { id: item.id, field, value });
        showToast('Updated');
        await loadData();
      } catch (e) {
        showToast(e.message, 'error');
      }
    };
  });
};

const setupSortable = () => {
  const tbody = qs('#itemsTbody');
  if (!tbody || tbody.dataset.sortable) return;
  tbody.dataset.sortable = '1';
  new Sortable(tbody, {
    handle: 'td:first-child',
    animation: 150,
    onEnd: async () => {
      const ids = qsa('#itemsTbody tr[data-id]').map((tr) => Number(tr.dataset.id));
      try {
        await api('save_data.php', 'POST', { action: 'reorder', ids });
        showToast('Order saved');
        await loadData();
      } catch (e) {
        showToast(e.message, 'error');
      }
    }
  });
};

const openModal = (id) => qs(`#${id}`).classList.remove('hidden');
const closeModal = (id) => qs(`#${id}`).classList.add('hidden');

const openItemModal = (item = null) => {
  qs('#itemForm').reset();
  qs('#itemId').value = item?.id || '';
  qs('#itemTitle').value = item?.title || '';
  qs('#itemCategory').value = item?.category_id || state.categories[0]?.id || '';
  qs('#itemStatus').value = item?.status || 'pending';
  qs('#itemDueDate').value = item?.due_date || new Date().toISOString().slice(0, 10);
  openModal('itemModal');
};

const renderUsers = () => {
  const tbody = qs('#usersTbody');
  if (!tbody) return;
  tbody.innerHTML = state.users.map((u) => `<tr class="border-b border-slate-200 dark:border-slate-700"><td class="py-2">${u.name}</td><td>${u.email}</td><td>${u.role}</td><td>${u.id === state.user.id ? '-' : `<button class="delete-user text-red-600" data-id="${u.id}">Delete</button>`}</td></tr>`).join('');
  qsa('.delete-user').forEach((b) => {
    b.onclick = () => confirmDialog('Delete this user?', async () => {
      await api('auth.php', 'POST', { action: 'delete_user', id: b.dataset.id });
      showToast('User deleted');
      await loadData();
    });
  });
};

const confirmDialog = (text, callback) => {
  state.confirmAction = callback;
  qs('#confirmText').textContent = text;
  openModal('confirmDialog');
};

const bindUi = () => {
  qs('#openSidebar')?.addEventListener('click', () => qs('#sidebar').classList.remove('-translate-x-full'));
  qs('#closeSidebar')?.addEventListener('click', () => qs('#sidebar').classList.add('-translate-x-full'));
  qs('#themeToggle')?.addEventListener('click', () => {
    const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    localStorage.setItem('theme', next);
    applyTheme();
  });
  qs('#profileButton')?.addEventListener('click', () => qs('#profileMenu').classList.toggle('hidden'));
  qs('#logoutBtn')?.addEventListener('click', async () => {
    await api('auth.php', 'POST', { action: 'logout' });
    window.location.href = 'login.html';
  });

  qsa('[data-close-modal]').forEach((btn) => btn.addEventListener('click', () => closeModal(btn.dataset.closeModal)));
  qs('#openItemModal')?.addEventListener('click', () => openItemModal());
  qs('#openUserModal')?.addEventListener('click', () => openModal('userModal'));

  ['#searchInput', '#categoryFilter', '#statusFilter'].forEach((s) => qs(s)?.addEventListener('input', renderItems));

  qs('#itemForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = qs('#itemId').value;
    const payload = {
      id,
      title: qs('#itemTitle').value.trim(),
      category_id: qs('#itemCategory').value,
      status: qs('#itemStatus').value,
      due_date: qs('#itemDueDate').value,
    };
    await api(id ? 'update_data.php' : 'save_data.php', 'POST', payload);
    closeModal('itemModal');
    showToast(id ? 'Item updated' : 'Item created');
    await loadData();
  });

  qs('#userForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    await api('register.php', 'POST', {
      name: qs('#userName').value.trim(),
      email: qs('#userEmail').value.trim(),
      password: qs('#userPassword').value,
      role: qs('#userRole').value,
    });
    closeModal('userModal');
    showToast('User created');
    qs('#userForm').reset();
    await loadData();
  });

  qs('#confirmCancel')?.addEventListener('click', () => closeModal('confirmDialog'));
  qs('#confirmOk')?.addEventListener('click', async () => {
    if (typeof state.confirmAction === 'function') await state.confirmAction();
    state.confirmAction = null;
    closeModal('confirmDialog');
  });

  qs('#exportPdfBtn')?.addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF();
    const rows = filteredItems().map((i) => [i.sort_order, i.title, i.category_name, i.status, i.due_date]);
    pdf.text('Plans / Tasks', 14, 12);
    pdf.autoTable({ startY: 16, head: [['Order', 'Title', 'Category', 'Status', 'Due']], body: rows });
    pdf.save('items.pdf');
  });
  qs('#printBtn')?.addEventListener('click', () => window.print());
};

const loadData = async () => {
  setLoadingSkeletons();
  const data = await api('get_data.php');
  state.user = data.user;
  state.items = data.items;
  state.categories = data.categories;
  state.users = data.users || [];
  qs('#profileName').textContent = state.user.name;

  if (state.user.role === 'admin') {
    qs('#usersSection').classList.remove('hidden');
    qs('#usersNav').classList.remove('hidden');
  }

  renderCategoryOptions();
  renderStats();
  renderActivities();
  renderChart();
  renderItems();
  renderUsers();
};

const initDashboard = async () => {
  applyTheme();
  registerPwa();
  bindUi();
  try {
    await loadData();
  } catch (e) {
    showToast(e.message, 'error');
    setTimeout(() => (window.location.href = 'login.html'), 800);
  }
};

if (isLoginPage) {
  setupLogin();
} else {
  initDashboard();
}
