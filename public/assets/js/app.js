/* ==========================================================================
   HelpSphere — portal behaviour (beneficiary, donor, NGO and admin pages)
   Everything is opt-in through data-* attributes, so pages need no inline JS.
   ========================================================================== */
(function () {
  'use strict';

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

  /* ---------- Sidebar (off-canvas below 1024px) ---------- */
  const sidebar = $('#sidebar');
  const scrim = $('#scrim');
  const menuBtn = $('#menuBtn');
  if (sidebar && scrim && menuBtn) {
    const setSidebar = (open) => {
      sidebar.classList.toggle('is-open', open);
      scrim.hidden = !open;
      menuBtn.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) { const first = $('.sidebar__nav a', sidebar); if (first) first.focus(); }
    };
    menuBtn.addEventListener('click', () => setSidebar(true));
    $$('[data-sidebar-close]').forEach((b) => b.addEventListener('click', () => { setSidebar(false); menuBtn.focus(); }));
    scrim.addEventListener('click', () => setSidebar(false));
    window.matchMedia('(min-width: 1025px)').addEventListener('change', (e) => { if (e.matches) setSidebar(false); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar.classList.contains('is-open')) setSidebar(false); });
  }

  /* ---------- Empty states: <div class="empty" data-empty-for="#list"> ---------- */
  const rowsIn = (el) => {
    if (!el) return 0;
    if (el.matches('table') || el.querySelector('tbody')) return $$('tbody tr', el).length;
    return [...el.children].filter((c) => !c.matches('template')).length;
  };
  function refreshEmpty() {
    $$('[data-empty-for]').forEach((empty) => {
      const target = $(empty.dataset.emptyFor);
      if (!target) return;
      const visible = target.matches('table, .table-wrap')
        ? $$('tbody tr', target).filter((tr) => !tr.hidden).length
        : [...target.children].filter((c) => !c.hidden).length;
      const none = visible === 0;
      empty.classList.toggle('is-shown', none);
      const wrap = target.closest('.table-wrap') || target;
      if (target.dataset.hideWhenEmpty !== undefined || wrap.dataset.hideWhenEmpty !== undefined) wrap.hidden = rowsIn(target) === 0;
    });
  }
  refreshEmpty();

  /* ---------- Data labels for stacked tables on phones ---------- */
  $$('table.table--stack').forEach((table) => {
    const heads = $$('thead th', table).map((th) => th.textContent.trim());
    $$('tbody tr', table).forEach((tr) => {
      [...tr.children].forEach((td, i) => { if (heads[i] && !td.hasAttribute('data-label')) td.setAttribute('data-label', heads[i]); });
    });
  });

  /* ---------- Filter tabs + search ----------
     <div class="tabs" data-filter-target="#list"> <button class="tabs__btn" data-filter="Pending">
     Rows carry data-status="Pending"; <input data-search-target="#list"> filters by text. */
  const filterState = new Map();
  function applyFilters(target) {
    const st = filterState.get(target) || {};
    const rows = target.querySelector('tbody') ? $$('tbody tr', target) : [...target.children];
    rows.forEach((row) => {
      const okStatus = !st.status || st.status === 'all' || row.dataset.status === st.status;
      const okText = !st.query || row.textContent.toLowerCase().includes(st.query);
      row.hidden = !(okStatus && okText);
    });
    refreshEmpty();
  }
  $$('[data-filter-target]').forEach((tabs) => {
    const target = $(tabs.dataset.filterTarget);
    if (!target) return;
    const rows = () => (target.querySelector('tbody') ? $$('tbody tr', target) : [...target.children]);
    const count = () => $$('.tabs__btn', tabs).forEach((b) => {
      const n = rows().filter((r) => b.dataset.filter === 'all' || r.dataset.status === b.dataset.filter).length;
      const span = $('span', b);
      if (span) span.textContent = n;
    });
    count();
    tabs.addEventListener('click', (e) => {
      const btn = e.target.closest('.tabs__btn');
      if (!btn) return;
      $$('.tabs__btn', tabs).forEach((b) => {
        const on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', String(on));
      });
      filterState.set(target, { ...(filterState.get(target) || {}), status: btn.dataset.filter });
      applyFilters(target);
    });
  });
  $$('[data-search-target]').forEach((input) => {
    const target = $(input.dataset.searchTarget);
    if (!target) return;
    input.addEventListener('input', () => {
      filterState.set(target, { ...(filterState.get(target) || {}), query: input.value.trim().toLowerCase() });
      applyFilters(target);
    });
  });

  /* ---------- Show one panel at a time ----------
     <button data-show-panel="history" data-panel-group="donor"> … <section data-panel="history" data-panel-group="donor" hidden> */
  $$('[data-show-panel]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.panelGroup;
      const name = btn.dataset.showPanel;
      const panels = $$(`[data-panel][data-panel-group="${group}"]`);
      const target = panels.find((p) => p.dataset.panel === name);
      const closing = btn.dataset.toggle !== undefined && target && !target.hidden;
      panels.forEach((p) => { p.hidden = closing || p.dataset.panel !== name; });
      $$(`[data-show-panel][data-panel-group="${group}"]`).forEach((b) => {
        const on = !closing && b.dataset.showPanel === name;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-expanded', String(on));
      });
      if (target && !closing) {
        refreshEmpty();
        target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  });

  /* ---------- Modals ----------
     <button data-modal-open="#volunteerModal" data-fill-campaign_id="12" data-fill-title="…">
     Inside the modal, [data-fill="title"] gets text, input[name="campaign_id"] gets value. */
  let lastOpener = null;
  function openModal(modal, opener) {
    lastOpener = opener || null;
    if (opener) {
      Object.entries(opener.dataset).forEach(([key, value]) => {
        if (!key.startsWith('fill')) return;
        const name = key.slice(4).replace(/^[A-Z]/, (c) => c.toLowerCase());
        $$(`[data-fill="${name}"]`, modal).forEach((el) => { el.textContent = value; });
        $$(`input[name="${name}"], input[data-fill-input="${name}"]`, modal).forEach((el) => { el.value = value; });
      });
    }
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    const focusable = $('input:not([type="hidden"]), textarea, select, button', $('.modal__box', modal) || modal);
    if (focusable) focusable.focus();
    modal.dispatchEvent(new CustomEvent('modal:open', { detail: { opener } }));
  }
  function closeModal(modal) {
    modal.hidden = true;
    document.body.style.overflow = '';
    $$('form', modal).forEach((f) => { if (f.dataset.keep === undefined) f.reset(); });
    modal.dispatchEvent(new CustomEvent('modal:close'));
    if (lastOpener) lastOpener.focus();
  }
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      const modal = $(opener.dataset.modalOpen);
      if (modal) { e.preventDefault(); openModal(modal, opener); }
      return;
    }
    const closer = e.target.closest('[data-modal-close]');
    if (closer) { closeModal(closer.closest('.modal')); return; }
    if (e.target.classList && e.target.classList.contains('modal')) closeModal(e.target);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    $$('.modal:not([hidden])').forEach(closeModal);
  });

  /* ---------- Document preview: <button data-preview-url="…" data-preview-name="…" data-preview-ext="pdf"> ---------- */
  const preview = $('#previewModal');
  if (preview) {
    preview.addEventListener('modal:open', (e) => {
      const opener = e.detail.opener;
      if (!opener) return;
      const url = opener.dataset.previewUrl;
      const name = opener.dataset.previewName || 'Document';
      const ext = (opener.dataset.previewExt || '').toLowerCase();
      const body = $('#previewBody', preview);
      $('#previewTitle', preview).textContent = name;
      $('#previewOpen', preview).href = url;
      body.textContent = '';
      if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
        const img = document.createElement('img');
        img.src = url;
        img.alt = name;
        body.appendChild(img);
      } else if (ext === 'pdf') {
        const frame = document.createElement('iframe');
        frame.src = url;
        frame.title = name;
        body.appendChild(frame);
      } else {
        const p = document.createElement('p');
        p.className = 'muted';
        p.textContent = 'This file type cannot be previewed here — use “Open in new tab” to download it.';
        body.appendChild(p);
      }
    });
    preview.addEventListener('modal:close', () => { $('#previewBody', preview).textContent = ''; });
  }

  /* ---------- Upload rows: add / remove (template#fileRowTemplate) ---------- */
  const rowsBox = $('#fileRows');
  const rowTpl = $('#fileRowTemplate');
  const addRow = $('#addFileRow');
  if (rowsBox && rowTpl && addRow) {
    addRow.addEventListener('click', () => {
      const frag = rowTpl.content.cloneNode(true);
      const row = frag.firstElementChild;
      rowsBox.appendChild(frag);
      const input = $('input[type="text"]', row);
      if (input) input.focus();
    });
    rowsBox.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-remove-row]');
      if (btn) btn.closest('.file-row').remove();
    });
  }

  /* ---------- Reveal fields for a chosen option: <select data-reveal> + [data-reveal-for="food"] ---------- */
  $$('select[data-reveal]').forEach((select) => {
    const sync = () => {
      $$('[data-reveal-for]').forEach((box) => {
        const on = box.dataset.revealFor === select.value;
        box.hidden = !on;
        $$('input, textarea, select', box).forEach((i) => {
          if (i.dataset.required !== undefined) i.required = on;
        });
      });
      $$('[data-reveal-submit]').forEach((b) => { b.hidden = !select.value; });
    };
    select.addEventListener('change', sync);
    sync();
  });

  /* ---------- Expandable rows: <button data-expand="#panel-3"> ---------- */
  $$('[data-expand]').forEach((btn) => {
    const panel = $(btn.dataset.expand);
    if (!panel) return;
    btn.setAttribute('aria-expanded', String(!panel.hidden));
    btn.addEventListener('click', () => {
      panel.hidden = !panel.hidden;
      btn.setAttribute('aria-expanded', String(!panel.hidden));
      const label = $('[data-expand-label]', btn);
      if (label) label.textContent = panel.hidden ? label.dataset.closed : label.dataset.open;
    });
  });

  /* ---------- Report modal on the NGO beneficiary-documents page ----------
     The form posts back to this page with &doc_id=…&report=1 added to the current query string. */
  const reportModal = $('#reportModal');
  if (reportModal) {
    reportModal.addEventListener('modal:open', (e) => {
      const opener = e.detail.opener;
      const form = $('form', reportModal);
      if (!opener || !form) return;
      const params = new URLSearchParams(location.search);
      params.delete('doc');
      params.set('doc_id', opener.dataset.docId);
      params.set('report', '1');
      form.action = location.pathname.split('/').pop() + '?' + params.toString();
    });
  }

  /* ---------- Greeting by time of day: <span data-greeting></span> ---------- */
  $$('[data-greeting]').forEach((el) => {
    const h = new Date().getHours();
    el.textContent = h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
  });
  $$('[data-today]').forEach((el) => {
    el.textContent = new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  });

  window.HSApp = { refreshEmpty };
})();
