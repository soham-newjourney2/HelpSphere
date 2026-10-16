/* ==========================================================================
   HelpSphere — Sample UI · NGO dashboard
   ========================================================================== */
(function () {
  'use strict';

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const icon = (id, cls = 'icon') => `<svg class="${cls}"><use href="#${id}"/></svg>`;

  /* ======================================================================
     Greeting, date and role banner
     ====================================================================== */
  const now = new Date();
  const hour = now.getHours();
  $('#greeting').textContent = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
  $('#todayLabel').textContent = now.toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

  let role = new URLSearchParams(location.search).get('role');
  if (!role) { try { role = sessionStorage.getItem('hs-role'); } catch (e) { role = null; } }
  const roleNames = { donor: 'a donor', beneficiary: 'a beneficiary', admin: 'an admin' };
  if (roleNames[role]) {
    $('#roleBannerText').textContent = `You signed in as ${roleNames[role]}. This sample previews the NGO dashboard — the other roles would reuse the same layout.`;
    $('#roleBanner').hidden = false;
  }

  /* ======================================================================
     Sidebar (off-canvas below 1024px)
     ====================================================================== */
  const sidebar = $('#sidebar');
  const scrim = $('#scrim');
  const menuBtn = $('#menuBtn');

  function setSidebar(open) {
    sidebar.classList.toggle('is-open', open);
    scrim.hidden = !open;
    menuBtn.setAttribute('aria-expanded', String(open));
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) $('.sidebar__nav a', sidebar).focus();
  }
  menuBtn.addEventListener('click', () => setSidebar(true));
  $('#sidebarClose').addEventListener('click', () => { setSidebar(false); menuBtn.focus(); });
  scrim.addEventListener('click', () => setSidebar(false));
  window.matchMedia('(min-width: 1025px)').addEventListener('change', (e) => { if (e.matches) setSidebar(false); });

  $$('.sidebar__nav a').forEach((a) => a.addEventListener('click', (e) => {
    e.preventDefault();
    if (!a.classList.contains('is-active')) HS.toast(`“${a.textContent.trim().replace(/\d+$/, '').trim()}” is part of the full app — this sample shows the overview.`);
    if (sidebar.classList.contains('is-open')) setSidebar(false);
  }));

  /* ======================================================================
     Notifications
     ====================================================================== */
  const notifBtn = $('#notifBtn');
  const notifPanel = $('#notifPanel');

  function setNotif(open) {
    notifPanel.hidden = !open;
    notifBtn.setAttribute('aria-expanded', String(open));
  }
  notifBtn.addEventListener('click', (e) => { e.stopPropagation(); setNotif(notifPanel.hidden); });
  document.addEventListener('click', (e) => { if (!notifPanel.hidden && !e.target.closest('.notif')) setNotif(false); });
  $('#markRead').addEventListener('click', () => {
    $$('li', notifPanel).forEach((li) => li.classList.remove('is-unread'));
    const dot = $('.notif__dot', notifBtn);
    if (dot) dot.remove();
    notifBtn.setAttribute('aria-label', 'Notifications, none unread');
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { setNotif(false); if (sidebar.classList.contains('is-open')) setSidebar(false); }
    if (e.key === '/' && !e.target.matches('input, textarea, select')) { e.preventDefault(); $('#searchInput').focus(); }
  });

  /* ======================================================================
     Counters shared across the page
     ====================================================================== */
  const counts = { pending: 0, pickupsToday: 3, awaitingPickup: 5 };

  function setKpi(el, value) {
    el.dataset.count = value;
    el.textContent = new Intl.NumberFormat('en-IN').format(value);
  }
  function syncCounts() {
    $$('[data-pending]').forEach((el) => { el.textContent = counts.pending; });
    $$('[data-pickups-today]').forEach((el) => { el.textContent = counts.pickupsToday; });
  }

  /* ======================================================================
     Assistance requests
     ====================================================================== */
  const requests = [
    { id: 1042, name: 'Rekha Das',     cat: 'Food',      text: 'Monthly ration support for a family of five',  when: '2 h ago',    status: 'Pending' },
    { id: 1041, name: 'Imran Sheikh',  cat: 'Resources', text: 'School uniforms and books for two children',   when: '5 h ago',    status: 'Pending' },
    { id: 1039, name: 'Lakshmi Iyer',  cat: 'Money',     text: 'Help with hospital bill after knee surgery',   when: 'Yesterday',  status: 'Pending' },
    { id: 1038, name: 'Arjun Mondal',  cat: 'Food',      text: 'Cooked meals for elderly parents for 2 weeks', when: 'Yesterday',  status: 'Pending' },
    { id: 1036, name: 'Fatima Begum',  cat: 'Resources', text: 'Winter blankets for four family members',      when: '2 days ago', status: 'Pending' },
    { id: 1031, name: 'Suresh Pal',    cat: 'Food',      text: 'Dry ration after job loss',                    when: '3 days ago', status: 'Approved' },
    { id: 1027, name: 'Meena Kumari',  cat: 'Resources', text: 'Wheelchair for mobility',                      when: '5 days ago', status: 'Approved' },
    { id: 1019, name: 'Gopal Roy',     cat: 'Food',      text: 'Groceries for one month',                      when: '1 week ago', status: 'Fulfilled' },
  ];
  const CAT_ICON = { Food: 'i-food', Resources: 'i-box', Money: 'i-coin' };
  const STATUS_PILL = {
    Pending:   `<span class="pill pill--warn">${icon('i-clock')}Pending</span>`,
    Approved:  `<span class="pill pill--info">${icon('i-check')}Approved</span>`,
    Fulfilled: `<span class="pill pill--good">${icon('i-check')}Fulfilled</span>`,
  };
  const reqState = { filter: 'all', query: '' };
  const reqBody = $('#reqBody');
  const reqTabs = $('#reqTabs');

  const initials = (name) => name.split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase();

  function actionsFor(r) {
    if (r.status === 'Pending') {
      return `<button type="button" class="act act--yes" data-act="approve" data-id="${r.id}" aria-label="Approve request from ${esc(r.name)}" title="Approve">${icon('i-check')}</button>
              <button type="button" class="act act--no" data-act="reject" data-id="${r.id}" aria-label="Reject request from ${esc(r.name)}" title="Reject">${icon('i-x')}</button>`;
    }
    if (r.status === 'Approved') {
      return `<button type="button" class="act act--text" data-act="fulfill" data-id="${r.id}">Mark fulfilled</button>`;
    }
    return '<span class="req-date">Completed</span>';
  }

  function renderRequests(flashId) {
    const q = reqState.query.toLowerCase();
    const matchesQuery = (r) => !q || `${r.name} ${r.cat} ${r.text}`.toLowerCase().includes(q);

    // tab counts reflect the search query
    $$('.tabs__btn', reqTabs).forEach((b) => {
      const f = b.dataset.filter;
      const n = requests.filter((r) => matchesQuery(r) && (f === 'all' || r.status === f)).length;
      $('span', b).textContent = n;
    });

    const rows = requests.filter((r) => matchesQuery(r) && (reqState.filter === 'all' || r.status === reqState.filter));
    reqBody.innerHTML = rows.map((r) => `
      <tr data-row="${r.id}"${r.id === flashId ? ' class="is-flash"' : ''}>
        <td class="td-who"><div class="who"><span class="avatar">${initials(r.name)}</span><div><strong>${esc(r.name)}</strong><span>REQ-${r.id}</span></div></div></td>
        <td class="td-cat"><span class="pill pill--muted">${icon(CAT_ICON[r.cat])}${esc(r.cat)}</span></td>
        <td class="td-text"><div class="req-text">${esc(r.text)}</div></td>
        <td class="td-date"><span class="req-date">${esc(r.when)}</span></td>
        <td class="td-status">${STATUS_PILL[r.status]}</td>
        <td class="td-actions"><div class="actions">${actionsFor(r)}</div></td>
      </tr>`).join('');
    $('#reqEmpty').hidden = rows.length > 0;

    counts.pending = requests.filter((r) => r.status === 'Pending').length;
    syncCounts();
  }

  reqTabs.addEventListener('click', (e) => {
    const btn = e.target.closest('.tabs__btn');
    if (!btn) return;
    reqState.filter = btn.dataset.filter;
    $$('.tabs__btn', reqTabs).forEach((b) => {
      const on = b === btn;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-selected', String(on));
    });
    renderRequests();
  });

  reqBody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-act]');
    if (!btn) return;
    const idx = requests.findIndex((r) => r.id === Number(btn.dataset.id));
    if (idx < 0) return;
    const r = requests[idx];

    if (btn.dataset.act === 'approve') {
      r.status = 'Approved';
      HS.toast(`Approved ${r.name}’s ${r.cat.toLowerCase()} request`);
    } else if (btn.dataset.act === 'reject') {
      requests.splice(idx, 1);   // like assistance_rejection: hidden for this NGO, still open for others
      HS.toast(`Request passed — other NGOs can still help ${r.name}`);
    } else if (btn.dataset.act === 'fulfill') {
      r.status = 'Fulfilled';
      HS.toast(`Marked ${r.name}’s request as fulfilled`);
    }
    setKpi($('#kpiPending'), requests.filter((x) => x.status === 'Pending').length);
    renderRequests(r.id);
  });

  $('#searchInput').addEventListener('input', (e) => {
    reqState.query = e.target.value.trim();
    renderRequests();
  });

  renderRequests();

  /* ======================================================================
     Immediate food alert — 7-minute response window
     ====================================================================== */
  const WINDOW = 7 * 60;
  const CIRC = 2 * Math.PI * 19;
  const alertBody = $('#alertBody');
  const countdown = $('.countdown');
  const bar = $('#countdownBar');
  const cdText = $('#countdownText');
  let left = WINDOW;
  let timer = null;

  function paintCountdown() {
    const m = Math.floor(left / 60);
    const s = String(left % 60).padStart(2, '0');
    cdText.textContent = `${m}:${s}`;
    bar.style.strokeDashoffset = String(CIRC * (1 - left / WINDOW));
    countdown.classList.toggle('is-urgent', left <= 60);
    countdown.setAttribute('aria-label', `${m} minutes ${s} seconds left to respond`);
  }

  function finishAlert(kind) {
    clearInterval(timer);
    const card = $('#alertCard');
    const pill = $('.pill', card);
    countdown.hidden = true;

    if (kind === 'accepted') {
      pill.className = 'pill pill--good';
      pill.innerHTML = `${icon('i-check')}Accepted`;
      pill.style.cssText = 'background:rgba(127,176,74,.2);color:#cfe6b1';
      alertBody.outerHTML = `
        <div class="alert-card__done">
          <span class="alert-card__done-icon">${icon('i-truck', 'icon icon--lg')}</span>
          <h2>It’s yours — pickup added</h2>
          <p>40 cooked meals from Riya Sharma. Collect from Salt Lake Sector V within 2 hours. The donor has been notified.</p>
          <a href="#pickupList" class="btn btn--light">View pickup ${icon('i-arrow', 'icon icon--arrow')}</a>
        </div>`;
      addPickup();
      HS.toast('Donation accepted — added to today’s pickups');
    } else {
      pill.className = 'pill pill--muted';
      pill.innerHTML = `${icon('i-clock')}${kind === 'expired' ? 'Window closed' : 'Passed'}`;
      pill.style.cssText = 'background:rgba(238,242,232,.1);color:var(--ink-inverse-2)';
      alertBody.outerHTML = `
        <div class="alert-card__done">
          <span class="alert-card__done-icon">${icon('i-food', 'icon icon--lg')}</span>
          <h2>Forwarded to the next 5 NGOs</h2>
          <p>${kind === 'expired' ? 'The 7-minute window ended.' : 'You passed on this donation.'} We’ll alert you again when food is available near you.</p>
        </div>`;
      if (kind === 'passed') HS.toast('Passed — the next nearest NGOs will be notified');
    }
  }

  function addPickup() {
    const t = new Date(Date.now() + 2 * 60 * 60 * 1000);
    let h = t.getHours();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    const li = document.createElement('li');
    li.className = 'pickup is-new';
    li.innerHTML = `
      <span class="pickup__time">${h}:${String(t.getMinutes()).padStart(2, '0')}<small>${ampm}</small></span>
      <div class="pickup__body">
        <strong>Cooked meals · 40 portions</strong>
        <span>${icon('i-pin', 'icon icon--sm')}Salt Lake Sector V · by this time</span>
      </div>
      <span class="pill pill--warn">${icon('i-clock')}Urgent</span>`;
    $('#pickupList').prepend(li);
    counts.pickupsToday += 1;
    counts.awaitingPickup += 1;
    setKpi($('#kpiPickup'), counts.awaitingPickup);
    syncCounts();
  }

  $('#acceptBtn').addEventListener('click', () => finishAlert('accepted'));
  $('#passBtn').addEventListener('click', () => finishAlert('passed'));

  paintCountdown();
  timer = setInterval(() => {
    left -= 1;
    paintCountdown();
    if (left <= 0) finishAlert('expired');
  }, 1000);

  /* ======================================================================
     Donations chart — stacked bars, last 7 days
     ====================================================================== */
  const SERIES = [
    { key: 'food',      label: 'Food',      color: 'var(--series-1)', values: [6, 8, 5, 9, 7, 11, 8] },
    { key: 'resources', label: 'Resources', color: 'var(--series-2)', values: [2, 3, 1, 4, 2, 3, 2] },
    { key: 'money',     label: 'Money',     color: 'var(--series-3)', values: [1, 1, 1, 1, 2, 2, 1] },
  ];
  const DAYS = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(now);
    d.setDate(d.getDate() - (6 - i));
    return {
      short: i === 6 ? 'Today' : d.toLocaleDateString('en-IN', { weekday: 'short' }),
      long: d.toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'short' }),
    };
  });
  const totals = DAYS.map((_, i) => SERIES.reduce((sum, s) => sum + s.values[i], 0));
  const weekTotal = totals.reduce((a, b) => a + b, 0);
  setKpi($('#kpiDonations'), weekTotal);

  const chartEl = $('#chart');
  const tip = $('#chartTip');
  const card = $('#donationsCard');

  // Legend (always present for ≥ 2 series; text stays in ink colours)
  $('#chartLegend').innerHTML = SERIES.map((s) =>
    `<li><i style="--c:${s.color}"></i>${s.label} <b>${s.values.reduce((a, b) => a + b, 0)}</b></li>`
  ).join('') + `<li style="margin-left:auto">Total <b>${weekTotal}</b></li>`;

  function roundedTop(x, y, w, h, r) {
    r = Math.min(r, h, w / 2);
    return `M${x},${y + h}V${y + r}A${r},${r} 0 0 1 ${x + r},${y}H${x + w - r}A${r},${r} 0 0 1 ${x + w},${y + r}V${y + h}Z`;
  }

  function renderChart() {
    const W = chartEl.clientWidth;
    const H = chartEl.clientHeight;
    if (!W || !H) return;

    const m = { t: 24, r: 4, b: 30, l: 30 };
    const iw = W - m.l - m.r;
    const ih = H - m.t - m.b;
    const yMax = Math.ceil(Math.max(...totals) / 5) * 5 || 5;
    const y = (v) => m.t + ih - (v / yMax) * ih;
    const band = iw / DAYS.length;
    const bw = Math.min(40, band * 0.52);
    const GAP = 2;
    const peak = totals.indexOf(Math.max(...totals));

    let svg = `<svg viewBox="0 0 ${W} ${H}" role="group" aria-label="Stacked bar chart of donations received over the last 7 days. Use Tab to step through each day.">`;

    // grid + y ticks
    for (let v = 0; v <= yMax; v += 5) {
      const yy = Math.round(y(v)) + 0.5;
      svg += `<line class="${v === 0 ? 'baseline' : 'grid-line'}" x1="${m.l}" x2="${W - m.r}" y1="${yy}" y2="${yy}"/>`;
      svg += `<text class="tick" x="${m.l - 8}" y="${yy + 4}" text-anchor="end">${v}</text>`;
    }

    DAYS.forEach((d, i) => {
      const cx = m.l + band * (i + 0.5);
      const x = cx - bw / 2;
      let cursor = y(0);
      const topIdx = SERIES.reduce((top, s, k) => (s.values[i] > 0 ? k : top), -1);

      let bars = '';
      SERIES.forEach((s, k) => {
        const v = s.values[i];
        if (!v) return;
        const h = (v / yMax) * ih;
        const yTop = cursor - h;
        const gapBelow = cursor < y(0) ? GAP : 0;            // 2px surface gap between stacked segments
        const segH = Math.max(h - gapBelow, 1);
        bars += k === topIdx
          ? `<path d="${roundedTop(x, yTop, bw, segH, 4)}" fill="${s.color}"/>`
          : `<rect x="${x}" y="${yTop}" width="${bw}" height="${segH}" fill="${s.color}"/>`;
        cursor = yTop;
      });

      const label = `${d.long}: ${SERIES.map((s) => `${s.values[i]} ${s.label.toLowerCase()}`).join(', ')}, ${totals[i]} total`;
      svg += `<g class="col" data-i="${i}" tabindex="0" role="img" aria-label="${esc(label)}">
                <rect class="hit" x="${cx - band / 2}" y="${m.t}" width="${band}" height="${ih}"/>
                <g class="bars">${bars}</g>
              </g>`;

      if (i === peak) {
        svg += `<text class="peak-label" x="${cx}" y="${y(totals[i]) - 8}" text-anchor="middle">${totals[i]}</text>`;
      }
      svg += `<text class="tick${i === 6 ? ' tick--today' : ''}" x="${cx}" y="${H - 8}" text-anchor="middle">${d.short}</text>`;
    });

    svg += '</svg>';
    chartEl.innerHTML = svg;
    hideTip();

    $$('.col', chartEl).forEach((g) => {
      const i = Number(g.dataset.i);
      const show = () => showTip(i, m.l + band * (i + 0.5), y(totals[i]));
      g.addEventListener('mouseenter', show);
      g.addEventListener('focus', show);
      g.addEventListener('mouseleave', hideTip);
      g.addEventListener('blur', hideTip);
      g.addEventListener('touchstart', show, { passive: true });
    });
  }

  function showTip(i, cx, topY) {
    tip.innerHTML = `<strong>${esc(DAYS[i].long)}</strong>` +
      SERIES.slice().reverse().map((s) =>
        `<div class="row-line"><i style="--c:${s.color}"></i><span>${s.label}</span><b>${s.values[i]}</b></div>`
      ).join('') +
      `<div class="row-line total"><span>Total</span><b>${totals[i]}</b></div>`;
    tip.hidden = false;

    const half = tip.offsetWidth / 2;
    const left = Math.min(Math.max(chartEl.offsetLeft + cx, half + 8), card.clientWidth - half - 8);
    tip.style.left = left + 'px';
    tip.style.top = (chartEl.offsetTop + topY) + 'px';

    chartEl.classList.add('is-hovering');
    $$('.col', chartEl).forEach((g) => g.classList.toggle('is-active', Number(g.dataset.i) === i));
  }

  function hideTip() {
    tip.hidden = true;
    chartEl.classList.remove('is-hovering');
    $$('.col.is-active', chartEl).forEach((g) => g.classList.remove('is-active'));
  }
  document.addEventListener('touchstart', (e) => { if (!e.target.closest('#chart')) hideTip(); }, { passive: true });

  // Table view (the accessible alternative to the chart)
  $('#chartTable').innerHTML = `
    <table class="table">
      <caption class="sr-only">Donations received in the last 7 days, by type</caption>
      <thead><tr><th scope="col">Day</th>${SERIES.map((s) => `<th scope="col" class="num">${s.label}</th>`).join('')}<th scope="col" class="num">Total</th></tr></thead>
      <tbody>
        ${DAYS.map((d, i) => `<tr><th scope="row" style="text-transform:none;letter-spacing:0;font-size:14px;color:var(--ink);font-weight:500">${esc(d.long)}</th>${SERIES.map((s) => `<td class="num">${s.values[i]}</td>`).join('')}<td class="num"><b>${totals[i]}</b></td></tr>`).join('')}
        <tr><th scope="row" style="text-transform:none;letter-spacing:0;font-size:14px;color:var(--ink)">Week</th>${SERIES.map((s) => `<td class="num"><b>${s.values.reduce((a, b) => a + b, 0)}</b></td>`).join('')}<td class="num"><b>${weekTotal}</b></td></tr>
      </tbody>
    </table>`;

  $$('.seg__btn', card).forEach((btn) => btn.addEventListener('click', () => {
    const table = btn.dataset.view === 'table';
    $$('.seg__btn', card).forEach((b) => {
      const on = b === btn;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-pressed', String(on));
    });
    chartEl.hidden = table;
    $('#chartLegend').hidden = table;
    $('#chartTable').hidden = !table;
    hideTip();
    if (!table) renderChart();
  }));

  if ('ResizeObserver' in window) {
    let raf = 0;
    new ResizeObserver(() => { cancelAnimationFrame(raf); raf = requestAnimationFrame(renderChart); }).observe(chartEl);
  } else {
    window.addEventListener('resize', renderChart);
  }
  renderChart();
})();
