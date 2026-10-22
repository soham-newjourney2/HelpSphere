/* ==========================================================================
   HelpSphere — Landing page (index.php)
   ========================================================================== */
(function () {
  'use strict';

  const nav = document.getElementById('nav');
  const toggle = document.getElementById('navToggle');
  const menu = document.getElementById('mobileMenu');
  const toggleUse = toggle.querySelector('use');

  /* ---------- Solid navbar after scrolling ---------- */
  const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 20);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---------- Mobile menu ---------- */
  function setMenu(open) {
    menu.hidden = !open;
    nav.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    toggleUse.setAttribute('href', HS.icon(open ? 'i-close' : 'i-menu'));
    document.body.style.overflow = open ? 'hidden' : '';
  }
  toggle.addEventListener('click', () => setMenu(menu.hidden));
  menu.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setMenu(false)));
  window.matchMedia('(min-width: 901px)').addEventListener('change', (e) => { if (e.matches) setMenu(false); });

  /* ---------- Dropdown menus (Log in) ---------- */
  document.querySelectorAll('.menu').forEach((m) => {
    const btn = m.querySelector('.menu__btn');
    const panel = m.querySelector('.menu__panel');
    const set = (open) => { panel.hidden = !open; btn.setAttribute('aria-expanded', String(open)); };
    btn.addEventListener('click', (e) => { e.stopPropagation(); set(panel.hidden); });
    document.addEventListener('click', (e) => { if (!m.contains(e.target)) set(false); });
    window.addEventListener('pageshow', () => set(false));
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (!menu.hidden) setMenu(false);
    document.querySelectorAll('.menu__panel').forEach((p) => {
      if (!p.hidden) { p.hidden = true; p.previousElementSibling.setAttribute('aria-expanded', 'false'); }
    });
  });

  /* ---------- Highlight the nav link of the section in view ---------- */
  const links = document.querySelectorAll('.nav__links a');
  const sections = [...links].map((a) => document.querySelector(a.getAttribute('href'))).filter(Boolean);
  if ('IntersectionObserver' in window) {
    const spy = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const id = '#' + entry.target.id;
        links.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === id));
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach((s) => spy.observe(s));
  }

  /* ---------- Tracker: walk through the four stages on a loop ---------- */
  const stages = [...document.querySelectorAll('#stages li')];
  const stageLabel = document.getElementById('stageLabel');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (stages.length && !reduceMotion) {
    let current = 0;
    const paint = () => {
      stages.forEach((li, i) => {
        li.classList.toggle('is-done', i < current);
        li.classList.toggle('is-current', i === current);
      });
      if (stageLabel) stageLabel.textContent = stages[current].querySelector('strong').textContent;
    };
    paint();
    setInterval(() => { current = (current + 1) % stages.length; paint(); }, 2400);
  }
})();
