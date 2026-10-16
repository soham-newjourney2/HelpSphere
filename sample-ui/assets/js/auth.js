/* ==========================================================================
   HelpSphere — Sample UI · Login / Register
   Mirrors the real roles: beneficiary & donor can self-register,
   NGO accounts are created by admins after verification, admin is sign-in only.
   ========================================================================== */
(function () {
  'use strict';

  const ROLES = {
    beneficiary: {
      label: 'beneficiary',
      signinSub: 'Sign in to track your assistance requests.',
      registerSub: 'Create an account to request help with dignity.',
      artTitle: 'Help is closer than you think.',
      artText: 'Request food, money or resources, upload your documents once and follow every step of your request.',
      canRegister: true,
      addressLabel: 'Address',
    },
    donor: {
      label: 'donor',
      signinSub: 'Sign in to continue to your donor space.',
      registerSub: 'Join thousands of donors giving where it matters most.',
      artTitle: 'Every login is a step toward someone’s next meal.',
      artText: 'Give food, money, blood or resources and follow every donation from pickup to delivery.',
      canRegister: true,
      addressLabel: 'Location',
    },
    ngo: {
      label: 'NGO',
      signinSub: 'Sign in to manage needs, donations and campaigns.',
      registerSub: 'Get your organisation verified to start receiving donations.',
      artTitle: 'Verified NGOs, trusted by every donor.',
      artText: 'Accept nearby food donations, review beneficiary requests and run volunteer campaigns from one place.',
      canRegister: false,
    },
    admin: {
      label: 'admin',
      signinSub: 'Restricted access for HelpSphere administrators.',
      artTitle: 'Keeping every donation accountable.',
      artText: 'Verify NGO documents, manage users and audit platform activity.',
      canRegister: false,
      signinOnly: true,
    },
  };

  /* ---------- Elements ---------- */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

  const art = $('.auth__art');
  const artTitle = $('#artTitle');
  const artText = $('#artText');
  const formTitle = $('#formTitle');
  const formSubtitle = $('#formSubtitle');
  const tabs = $('#modeTabs');
  const signinForm = $('#signinForm');
  const registerForm = $('#registerForm');
  const ngoNotice = $('#ngoNotice');
  const signinSwitch = $('#signinSwitch');
  const addressLabel = $('#rg-address-label');

  const params = new URLSearchParams(location.search);
  const state = {
    role: ROLES[params.get('role')] ? params.get('role') : 'donor',
    mode: params.get('mode') === 'register' ? 'register' : 'signin',
  };

  /* ---------- Render ---------- */
  function render(animateArt) {
    const cfg = ROLES[state.role];
    if (cfg.signinOnly) state.mode = 'signin';

    $(`input[name="role"][value="${state.role}"]`).checked = true;

    // Tabs
    tabs.classList.toggle('is-single', !!cfg.signinOnly);
    tabs.dataset.mode = state.mode;
    $$('.mode-tab', tabs).forEach((t) => {
      const on = t.dataset.mode === state.mode;
      t.classList.toggle('is-active', on);
      t.setAttribute('aria-selected', String(on));
    });
    $('[data-mode="register"]', tabs).textContent = cfg.canRegister ? 'Create account' : 'Get verified';

    // Panels
    const showRegister = state.mode === 'register';
    signinForm.hidden = showRegister;
    registerForm.hidden = !(showRegister && cfg.canRegister);
    ngoNotice.hidden = !(showRegister && !cfg.canRegister);
    signinSwitch.hidden = !!cfg.signinOnly;

    // Role-specific register fields (disabled fields are skipped by validation)
    $$('[data-for]', registerForm).forEach((f) => {
      const on = f.dataset.for === state.role;
      f.hidden = !on;
      $$('input, select', f).forEach((i) => { i.disabled = !on; });
    });
    if (cfg.addressLabel) addressLabel.textContent = cfg.addressLabel;

    // Copy
    formTitle.textContent = showRegister
      ? (cfg.canRegister ? 'Create your account' : 'Register your NGO')
      : (state.role === 'admin' ? 'Admin sign in' : 'Welcome back');
    formSubtitle.textContent = showRegister ? cfg.registerSub : cfg.signinSub;
    document.title = (showRegister ? 'Create account' : 'Log in') + ' | HelpSphere';

    if (art) {
      const swap = () => { artTitle.textContent = cfg.artTitle; artText.textContent = cfg.artText; };
      if (animateArt) {
        art.classList.add('is-swapping');
        setTimeout(() => { swap(); art.classList.remove('is-swapping'); }, 250);
      } else {
        swap();
      }
    }

    clearErrors(signinForm);
    clearErrors(registerForm);
  }

  function setRole(role) { state.role = role; render(true); syncUrl(); }
  function setMode(mode) { state.mode = mode; render(false); syncUrl(); }
  function syncUrl() {
    const p = new URLSearchParams({ role: state.role });
    if (state.mode === 'register') p.set('mode', 'register');
    history.replaceState(null, '', '?' + p.toString());
  }

  $$('input[name="role"]').forEach((r) => r.addEventListener('change', () => setRole(r.value)));
  $$('.mode-tab', tabs).forEach((t) => t.addEventListener('click', () => setMode(t.dataset.mode)));
  $$('[data-goto]').forEach((b) => b.addEventListener('click', () => setMode(b.dataset.goto)));

  /* ---------- Show / hide password ---------- */
  $$('[data-toggle-pass]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.togglePass);
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      $('use', btn).setAttribute('href', show ? '#i-eye-off' : '#i-eye');
    });
  });

  /* ---------- Password strength ---------- */
  const strength = $('#strength');
  const strengthLabel = $('.strength__label', strength);
  $('#rg-pass').addEventListener('input', (e) => {
    const v = e.target.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/\d/.test(v)) score++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
    if (!v) score = 0;
    strength.dataset.level = score;
    strengthLabel.textContent = ['Use 8+ characters with a number', 'Weak', 'Fair', 'Good', 'Strong'][score];
  });

  /* ---------- Validation ---------- */
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  function messageFor(input) {
    const v = input.value.trim();
    if (input.type === 'checkbox') return input.checked ? '' : 'Please accept the terms to continue.';
    if (input.required && !v) return 'This field is required.';
    if (input.type === 'email' && !EMAIL_RE.test(v)) return 'Enter a valid email address.';
    if (input.type === 'tel' && !/^\d{10}$/.test(v)) return 'Enter a 10-digit mobile number.';
    if (input.type === 'date') {
      const d = new Date(v);
      if (isNaN(d) || d > new Date()) return 'Enter a valid date of birth.';
    }
    if (input.minLength > 0 && input.value.length < input.minLength) return `Use at least ${input.minLength} characters.`;
    if (input.name === 'cpassword' && input.value !== $('#rg-pass').value) return 'Passwords do not match.';
    return '';
  }

  function showError(input, msg) {
    const field = input.closest('.field');
    const errEl = field ? $('.field__error', field) : document.getElementById(input.id + '-err');
    if (field) field.classList.toggle('is-invalid', !!msg);
    if (errEl) {
      errEl.textContent = msg;
      errEl.classList.toggle('is-shown', !!msg);
      if (!errEl.id) errEl.id = input.id + '-err';
      input.setAttribute('aria-describedby', errEl.id);
    }
    input.setAttribute('aria-invalid', msg ? 'true' : 'false');
  }

  function validate(form) {
    let firstBad = null;
    $$('input, select', form).forEach((input) => {
      if (input.disabled || input.type === 'radio' || input.name === 'remember') return;
      const msg = messageFor(input);
      showError(input, msg);
      if (msg && !firstBad) firstBad = input;
    });
    if (firstBad) firstBad.focus();
    form.dataset.touched = 'true';
    return !firstBad;
  }

  function clearErrors(form) {
    delete form.dataset.touched;
    $$('input, select', form).forEach((i) => {
      if (i.type !== 'radio') showError(i, '');
    });
  }

  // Live re-validation after the first submit attempt
  [signinForm, registerForm].forEach((form) => {
    form.addEventListener('input', (e) => {
      if (form.dataset.touched && e.target.matches('input, select') && e.target.type !== 'radio') {
        showError(e.target, messageFor(e.target));
      }
    });
    form.addEventListener('change', (e) => {
      if (form.dataset.touched && e.target.matches('select, [type="checkbox"]')) {
        showError(e.target, messageFor(e.target));
      }
    });
  });

  function setLoading(form, on, label) {
    const btn = $('.form__submit', form);
    btn.classList.toggle('is-loading', on);
    if (label) $('.btn__label', btn).textContent = label;
  }

  /* ---------- Sign in (demo) ---------- */
  signinForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!validate(signinForm)) return;

    setLoading(signinForm, true, 'Signing in…');
    try { sessionStorage.setItem('hs-role', state.role); } catch (err) { /* storage unavailable */ }

    setTimeout(() => {
      HS.toast(`Signed in as ${ROLES[state.role].label} — opening your dashboard`);
      setTimeout(() => { location.href = 'dashboard.html?role=' + state.role; }, 700);
    }, 700);
  });

  /* ---------- Register (demo) ---------- */
  registerForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!validate(registerForm)) return;

    const email = $('#rg-email').value.trim();
    setLoading(registerForm, true, 'Creating account…');

    setTimeout(() => {
      setLoading(registerForm, false, 'Create account');
      registerForm.reset();
      $('#strength').dataset.level = 0;
      HS.toast('Account created — please sign in');
      setMode('signin');
      $('#si-email').value = email;
      $('#si-pass').focus();
    }, 900);
  });

  /* ---------- Forgot password ---------- */
  $('#forgotLink').addEventListener('click', (e) => {
    e.preventDefault();
    const email = $('#si-email');
    if (!EMAIL_RE.test(email.value.trim())) {
      showError(email, 'Enter your email first, then tap “Forgot password”.');
      email.focus();
      return;
    }
    showError(email, '');
    HS.toast(`Reset link sent to ${email.value.trim()}`);
  });

  render(false);
})();
