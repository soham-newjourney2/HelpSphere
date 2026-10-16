/* ==========================================================================
   HelpSphere — Sample UI · shared helpers
   ========================================================================== */
(function () {
  'use strict';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
    el.innerHTML =
      '<svg class="icon icon--sm" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
      '<span></span>';
    el.querySelector('span').textContent = message;
    stack.appendChild(el);

    setTimeout(() => {
      el.classList.add('is-leaving');
      el.addEventListener('animationend', () => el.remove(), { once: true });
    }, 3200);
  }

  /* ---------- Animated number counters: <span data-count="1200"> ---------- */
  function animateCount(el) {
    const target = Number(el.dataset.count) || 0;
    const fmt = new Intl.NumberFormat('en-IN');
    if (reduceMotion) { el.textContent = fmt.format(target); return; }

    const duration = 1600;
    const start = performance.now();
    function frame(now) {
      const t = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - t, 3);
      el.textContent = fmt.format(Math.round(target * eased));
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  /* ---------- Reveal on scroll + counters ---------- */
  function initObservers() {
    const revealEls = document.querySelectorAll('.reveal');
    const countEls = document.querySelectorAll('[data-count]');

    if (!('IntersectionObserver' in window)) {
      revealEls.forEach((el) => el.classList.add('is-visible'));
      countEls.forEach(animateCount);
      return;
    }

    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        if (el.classList.contains('reveal')) el.classList.add('is-visible');
        if (el.dataset.count !== undefined) animateCount(el);
        io.unobserve(el);
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach((el, i) => {
      el.style.transitionDelay = (i % 4) * 80 + 'ms';   // gentle stagger within a row
      io.observe(el);
    });
    countEls.forEach((el) => io.observe(el));
  }

  document.addEventListener('DOMContentLoaded', initObservers);

  window.HS = { toast, animateCount };
})();
