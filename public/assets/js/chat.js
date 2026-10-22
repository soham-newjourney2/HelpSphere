/* ==========================================================================
   HelpSphere — messages (beneficiary, donor and NGO pages)
   Talks to the page's own PHP endpoints:
     GET  ?action=contacts&role=…           → [{id, name}]
     GET  ?action=poll&contact_role=…&contact_id=…&last_id=…
     POST ?action=send       (message, receiver_role, receiver_id)
     GET  ?action=poll_admin&last_id=…      (NGO admin channel)
     POST ?action=send_admin (message)
   ========================================================================== */
(function () {
  'use strict';

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const POLL_MS = 3000;
  const CSRF = document.querySelector('meta[name="csrf"]')?.content || '';
  const ROLE_ICON = { NGO: 'i-building', Beneficiary: 'i-user', Donor: 'i-heart', Admin: 'i-shield' };

  const initials = (name) => String(name || '?').trim().split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase();
  function fmtTime(ts) {
    const d = new Date(String(ts).replace(' ', 'T'));
    if (isNaN(d)) return '';
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' · ' +
           d.toLocaleDateString([], { month: 'short', day: 'numeric' });
  }
  function svg(id, cls) {
    const s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    s.setAttribute('class', cls || 'icon');
    const u = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    u.setAttribute('href', HS.icon(id));
    s.appendChild(u);
    return s;
  }
  function bubble(text, meta, mine, role) {
    const row = document.createElement('div');
    row.className = 'bubble-row' + (mine ? ' is-mine' : '');
    const av = document.createElement('span');
    av.className = 'avatar' + (mine ? ' avatar--brand' : '');
    av.appendChild(svg(ROLE_ICON[role] || 'i-chat'));
    const wrap = document.createElement('div');
    const b = document.createElement('div');
    b.className = 'bubble';
    b.textContent = text;
    const m = document.createElement('div');
    m.className = 'bubble-meta';
    m.textContent = meta;
    wrap.append(b, m);
    row.append(av, wrap);
    return row;
  }
  function placeholder(win, text, iconId) {
    win.textContent = '';
    const p = document.createElement('div');
    p.className = 'chat__placeholder';
    p.appendChild(svg(iconId || 'i-chat'));
    const t = document.createElement('p');
    t.textContent = text;
    p.appendChild(t);
    win.appendChild(p);
  }
  function autoGrow(ta) {
    ta.addEventListener('input', () => { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 120) + 'px'; });
  }

  /* ======================================================================
     Direct messages
     ====================================================================== */
  const chat = $('#chat');
  if (chat) {
    const MY_ROLE = chat.dataset.myRole;
    const win = $('#chatWindow');
    const form = $('#chatForm');
    const input = $('#chatInput');
    const sendBtn = $('#chatSend');
    const nameEl = $('#chatName');
    const roleEl = $('#chatRole');
    const dot = $('#chatDot');
    const loaded = {};
    let current = null;   // { id, role, name }
    let lastId = 0;
    let timer = null;

    $$('.group-btn', chat).forEach((btn) => {
      btn.addEventListener('click', () => {
        const role = btn.dataset.group;
        const list = $(`.contact-list[data-list="${role}"]`, chat);
        const open = list.hidden;
        list.hidden = !open;
        btn.setAttribute('aria-expanded', String(open));
        if (open && !loaded[role]) loadContacts(role, list);
      });
    });

    function loadContacts(role, list) {
      list.innerHTML = '<p class="contact-list__note">Loading…</p>';
      fetch(`?action=contacts&role=${encodeURIComponent(role)}`, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((people) => {
          loaded[role] = true;
          list.textContent = '';
          if (!Array.isArray(people) || !people.length) {
            list.innerHTML = '<p class="contact-list__note">No contacts yet.</p>';
            return;
          }
          people.forEach((p) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'person-btn';
            const av = document.createElement('span');
            av.className = 'avatar';
            av.textContent = initials(p.name);
            const nm = document.createElement('span');
            nm.textContent = p.name;
            b.append(av, nm);
            b.addEventListener('click', () => openConversation({ id: Number(p.id), role, name: p.name }, b));
            list.appendChild(b);
          });
        })
        .catch(() => { list.innerHTML = '<p class="contact-list__note">Could not load contacts.</p>'; });
    }

    function openConversation(contact, btn) {
      $$('.person-btn', chat).forEach((b) => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      current = contact;
      lastId = 0;
      nameEl.textContent = contact.name;
      roleEl.textContent = contact.role;
      dot.classList.add('is-on');
      placeholder(win, 'Loading conversation…', 'i-history');
      input.disabled = false;
      sendBtn.disabled = false;
      input.placeholder = `Message ${contact.name}…`;
      chat.classList.add('is-talking');
      input.focus();
      if (timer) clearInterval(timer);
      poll();
      timer = setInterval(poll, POLL_MS);
    }

    function isMine(m) {
      if (m.sender_role !== MY_ROLE) return false;
      // NGO ↔ NGO: both sides have role "NGO", so compare ids too
      return !(current && current.role === MY_ROLE && Number(m.sender_id) === current.id);
    }

    function poll() {
      if (!current) return;
      const c = current;
      fetch(`?action=poll&contact_role=${encodeURIComponent(c.role)}&contact_id=${c.id}&last_id=${lastId}`, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((msgs) => {
          if (c !== current || !Array.isArray(msgs)) return;
          if (msgs.length) {
            if (lastId === 0) win.textContent = '';
            msgs.forEach((m) => {
              const mine = isMine(m);
              win.appendChild(bubble(m.message, (mine ? 'You' : c.name) + ' · ' + fmtTime(m.sent_at), mine, m.sender_role));
              if (Number(m.id) > lastId) lastId = Number(m.id);
            });
            win.scrollTop = win.scrollHeight;
          } else if (lastId === 0) {
            placeholder(win, `No messages yet — say hello to ${c.name}.`, 'i-chat');
          }
        })
        .catch(() => {});
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const text = input.value.trim();
      if (!current || !text) return;
      const fd = new FormData();
      fd.append('message', text);
      fd.append('csrf', CSRF);
      fd.append('receiver_role', current.role);
      fd.append('receiver_id', current.id);
      input.value = '';
      input.style.height = 'auto';
      fetch('?action=send', { method: 'POST', body: fd })
        .then((r) => r.json())
        .then((res) => { if (res && res.success) poll(); else HS.toast('Message could not be sent.'); })
        .catch(() => HS.toast('Message could not be sent.'));
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });
    autoGrow(input);

    const back = $('#chatBack');
    if (back) back.addEventListener('click', () => chat.classList.remove('is-talking'));
  }

  /* ======================================================================
     NGO → Admin channel
     ====================================================================== */
  const channel = $('#adminChannel');
  if (channel) {
    const win = $('#adminWindow');
    const form = $('#adminForm');
    const input = $('#adminInput');
    let lastId = 0;

    function poll() {
      fetch(`?action=poll_admin&last_id=${lastId}`, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((msgs) => {
          if (!Array.isArray(msgs)) return;
          if (msgs.length) {
            if (lastId === 0) win.textContent = '';
            msgs.forEach((m) => {
              const mine = m.sender_role === 'NGO';
              win.appendChild(bubble(m.message, (mine ? 'You' : 'Admin') + ' · ' + fmtTime(m.sent_at), mine, m.sender_role));
              if (Number(m.id) > lastId) lastId = Number(m.id);
            });
            win.scrollTop = win.scrollHeight;
          } else if (lastId === 0) {
            placeholder(win, 'No messages with the admin team yet.', 'i-shield');
          }
        })
        .catch(() => {});
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const text = input.value.trim();
      if (!text) return;
      const fd = new FormData();
      fd.append('message', text);
      fd.append('csrf', CSRF);
      input.value = '';
      input.style.height = 'auto';
      fetch('?action=send_admin', { method: 'POST', body: fd })
        .then((r) => r.json())
        .then((res) => { if (res && res.success) poll(); else HS.toast('Message could not be sent.'); })
        .catch(() => HS.toast('Message could not be sent.'));
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });
    autoGrow(input);

    poll();
    setInterval(poll, POLL_MS);
  }
})();
