/* ==========================================================================
   HelpSphere — shared helpers (every page)
   ========================================================================== */
(function () {
  'use strict';

  document.documentElement.classList.remove('no-js');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const ICONS = document.querySelector('meta[name="hs-icons"]')?.content || '';

  /* ---------- Toast ---------- */
  function toast(message) {
    let stack = document.querySelector('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      stack.setAttribute('role', 'status');
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }
    const el = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = '<svg class="icon icon--sm" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg><span></span>';
    el.querySelector('span').textContent = message;
    stack.appendChild(el);
    setTimeout(() => {
      el.classList.add('is-leaving');
      el.addEventListener('animationend', () => el.remove(), { once: true });
    }, 3400);
  }

  /* ---------- Count-up numbers ----------
     <span data-count="12">0</span>  or  <span data-count>12</span>
     (the second form animates whatever number the server printed) */
  function animateCount(el) {
    const raw = el.dataset.count !== '' ? el.dataset.count : el.textContent;
    const target = Number(String(raw).replace(/[^\d.-]/g, ''));
    if (!Number.isFinite(target)) return;
    const fmt = new Intl.NumberFormat('en-IN');
    if (reduceMotion || target === 0) { el.textContent = fmt.format(target); return; }
    const duration = 1400;
    const start = performance.now();
    function frame(now) {
      const t = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - t, 3);
      el.textContent = fmt.format(Math.round(target * eased));
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  /* ---------- Progress bars: <div class="progress" data-value="68"><span></span></div> ---------- */
  function fillProgress(el) {
    const v = Math.max(0, Math.min(100, Number(el.dataset.value) || 0));
    const bar = el.querySelector('span');
    if (bar) bar.style.width = v + '%';
    el.setAttribute('role', 'progressbar');
    el.setAttribute('aria-valuenow', String(v));
    el.setAttribute('aria-valuemin', '0');
    el.setAttribute('aria-valuemax', '100');
  }

  /* ---------- Reveal on scroll + counters + progress ---------- */
  function initObservers() {
    const revealEls = document.querySelectorAll('.reveal');
    const countEls = document.querySelectorAll('[data-count]');
    const progressEls = document.querySelectorAll('.progress[data-value]');

    if (!('IntersectionObserver' in window)) {
      revealEls.forEach((el) => el.classList.add('is-visible'));
      countEls.forEach(animateCount);
      progressEls.forEach(fillProgress);
      return;
    }

    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        if (el.classList.contains('reveal')) el.classList.add('is-visible');
        if (el.dataset.count !== undefined) animateCount(el);
        if (el.classList.contains('progress')) fillProgress(el);
        io.unobserve(el);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });

    revealEls.forEach((el, i) => {
      el.style.transitionDelay = (i % 4) * 80 + 'ms';   // gentle stagger within a row
      io.observe(el);
    });
    countEls.forEach((el) => io.observe(el));
    progressEls.forEach((el) => io.observe(el));
  }

  /* ---------- Show / hide password: <button data-toggle-pass="inputId"> ---------- */
  function initPasswordToggles() {
    document.querySelectorAll('[data-toggle-pass]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.togglePass);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        const use = btn.querySelector('use');
        if (use) use.setAttribute('href', ICONS + (show ? '#i-eye-off' : '#i-eye'));
      });
    });
  }

  /* ---------- Confirm before submitting: <form data-confirm="Delete this?"> ---------- */
  function initConfirms() {
    document.addEventListener('submit', (e) => {
      const form = e.target;
      const msg = form.dataset && form.dataset.confirm;
      if (msg && !window.confirm(msg)) e.preventDefault();
    }, true);
  }

  /* ---------- Toast for messages the server put in the page: <div data-toast="..."> ---------- */
  function initServerToasts() {
    document.querySelectorAll('[data-toast]').forEach((el) => toast(el.dataset.toast));
  }

  document.addEventListener('DOMContentLoaded', () => {
    initObservers();
    initPasswordToggles();
    initConfirms();
    initServerToasts();
  });

  window.HS = { toast, animateCount, icon: (id) => ICONS + '#' + id };
})();
