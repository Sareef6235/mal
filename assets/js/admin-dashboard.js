(function ($) {
  'use strict';

  const root = document.querySelector('.mal-lms-shell');
  const ajax = (action, data = {}) => $.post(MALLMS.ajaxUrl, { action, nonce: MALLMS.nonce, ...data });

  function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('mal-lms-modal-open');
  }

  function closeModals() {
    document.querySelectorAll('.mal-lms-modal').forEach((modal) => modal.setAttribute('aria-hidden', 'true'));
    document.body.classList.remove('mal-lms-modal-open');
  }

  document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-open-modal]');
    if (opener) openModal(opener.dataset.openModal);
    if (event.target.matches('[data-close-modal]') || event.target.classList.contains('mal-lms-modal')) closeModals();
    if (event.target.closest('.mal-lms-hamburger')) root?.classList.toggle('is-sidebar-open');
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeModals();
  });

  document.querySelectorAll('[data-counter]').forEach((counter) => {
    const target = Number(counter.dataset.counter || 0);
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 40));
    const tick = () => {
      current = Math.min(target, current + step);
      counter.textContent = current.toLocaleString();
      if (current < target) requestAnimationFrame(tick);
    };
    tick();
  });

  $('[data-ajax-form]').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    ajax(form.data('ajax-form'), form.serializeArray().reduce((acc, item) => ({ ...acc, [item.name]: item.value }), {}))
      .done(() => window.location.reload())
      .fail((xhr) => alert(xhr.responseJSON?.data?.message || 'Save failed'));
  });

  document.querySelectorAll('[data-media-upload]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = button.dataset.mediaUpload;
      const frame = wp.media({ title: 'Select media', multiple: false });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        document.querySelector(`[data-media-target="${target}"]`).value = attachment.id;
        button.textContent = attachment.filename || 'Selected';
      });
      frame.open();
    });
  });

  const board = document.querySelector('[data-sortable-plans]');
  if (board && window.Sortable) {
    new Sortable(board, {
      animation: 180,
      ghostClass: 'is-dragging',
      onEnd() {
        const ids = Array.from(board.querySelectorAll('[data-plan-id]')).map((item) => item.dataset.planId);
        ajax('mal_lms_reorder_plans', { ids });
      },
    });
  }

  $('[data-receipt-status]').on('click', function () {
    ajax('mal_lms_receipt_status', { id: $(this).data('id'), status: $(this).data('receipt-status') }).done(() => window.location.reload());
  });

  $('[data-bulk-delete]').on('click', function () {
    const ids = $('[data-receipt-check]:checked').map((_, el) => el.value).get();
    if (!ids.length || !confirm(MALLMS.i18n.confirmDelete)) return;
    ajax('mal_lms_delete_receipts', { ids }).done(() => window.location.reload());
  });

  $('[data-check-all]').on('change', function () {
    $('[data-receipt-check]').prop('checked', this.checked);
  });

  $('[data-preview-receipt]').on('click', function () {
    const receipt = $(this).data('preview-receipt');
    $('[data-receipt-preview]').html(`<h2>Receipt #${receipt.id}</h2><p><strong>Status:</strong> ${receipt.status}</p><p><strong>Amount:</strong> ${receipt.currency} ${receipt.amount}</p>`);
    openModal('receipt-modal');
  });

  $('[data-print-modal]').on('click', () => window.print());
  $('[data-share-modal]').on('click', async () => {
    if (navigator.share) await navigator.share({ title: document.title, url: window.location.href });
  });

  $('[data-theme-toggle]').on('click', () => {
    const themes = ['dark', 'light', 'neon'];
    const next = themes[(themes.indexOf(root?.dataset.theme || 'dark') + 1) % themes.length];
    root.dataset.theme = next;
    localStorage.setItem('mal_lms_theme', next);
  });
  if (root && localStorage.getItem('mal_lms_theme')) root.dataset.theme = localStorage.getItem('mal_lms_theme');

  $('[data-live-filter]').on('input', function () {
    const needle = this.value.toLowerCase();
    $('[data-search-text]').each(function () {
      $(this).toggle(($(this).data('search-text') || '').includes(needle));
    });
  });
})(jQuery);
