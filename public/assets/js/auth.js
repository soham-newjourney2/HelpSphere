/* ==========================================================================
   HelpSphere — Sign in / register (login-*.php)
   Switches between the panels of one page (sign in ↔ register / get verified).
   Forms submit normally to the page's PHP; nothing here replaces that.
   ========================================================================== */
(function () {
  'use strict';

  const tabs = document.getElementById('modeTabs');
  if (tabs) {
    const panels = [...document.querySelectorAll('[data-panel]')];

    function setMode(mode) {
      tabs.dataset.mode = mode;
      tabs.querySelectorAll('.mode-tab').forEach((t) => {
        const on = t.dataset.mode === mode;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', String(on));
      });
      panels.forEach((p) => { p.hidden = p.dataset.panel !== mode; });
      const title = document.getElementById('formTitle');
      const sub = document.getElementById('formSubtitle');
      const active = tabs.querySelector(`.mode-tab[data-mode="${mode}"]`);
      if (title && active && active.dataset.title) title.textContent = active.dataset.title;
      if (sub && active && active.dataset.subtitle) sub.textContent = active.dataset.subtitle;
      if (history.replaceState) history.replaceState(null, '', mode === 'signin' ? location.pathname : '#' + mode);
    }

    tabs.querySelectorAll('.mode-tab').forEach((t) => t.addEventListener('click', () => setMode(t.dataset.mode)));
    document.querySelectorAll('[data-goto]').forEach((b) => b.addEventListener('click', () => setMode(b.dataset.goto)));

    // The server asks for a panel (e.g. after a failed sign-up); otherwise follow the URL hash
    const start = tabs.dataset.start && tabs.dataset.start !== 'signin' ? tabs.dataset.start : location.hash.replace('#', '');
    if (start && tabs.querySelector(`.mode-tab[data-mode="${start}"]`)) setMode(start);
  }

  /* ---------- Password strength (visual hint) ---------- */
  const strength = document.getElementById('strength');
  const newPass = document.getElementById('rg-pass');
  if (strength && newPass) {
    const label = strength.querySelector('.strength__label');
    newPass.addEventListener('input', () => {
      const v = newPass.value;
      let score = 0;
      if (v.length >= 8) score++;
      if (/\d/.test(v)) score++;
      if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
      if (!v) score = 0;
      strength.dataset.level = score;
      label.textContent = ['At least 6 characters', 'Weak', 'Fair', 'Good', 'Strong'][score];
    });
  }

  /* ---------- Confirm-password match ---------- */
  const confirmPass = document.getElementById('rg-cpass');
  const matchHint = document.getElementById('matchHint');
  if (newPass && confirmPass && matchHint) {
    const check = () => {
      const bad = confirmPass.value !== '' && confirmPass.value !== newPass.value;
      matchHint.hidden = !bad;
      confirmPass.setCustomValidity(bad ? 'Passwords do not match.' : '');
    };
    newPass.addEventListener('input', check);
    confirmPass.addEventListener('input', check);
  }

  /* ---------- Spinner on submit ---------- */
  document.querySelectorAll('.auth form').forEach((form) => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('.form__submit');
      if (btn && form.checkValidity()) btn.classList.add('is-loading');
    });
  });
})();
