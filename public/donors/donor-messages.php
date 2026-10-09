<?php
require_once __DIR__ . '/../../inc/auth.php';
donor_require_login();

$my_id   = (int) $_SESSION['donor_id'];
$my_role = 'Donor';

$tableMap = [
  'NGO' => ['table' => 'ngo_master', 'id' => 'id', 'name' => 'name'],
];

// ─── AJAX: Get contacts for a role ────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'contacts') {
  header('Content-Type: application/json');
  $role = $_GET['role'] ?? '';
  if (!isset($tableMap[$role])) { echo json_encode([]); exit; }
  $t    = $tableMap[$role];
  $stmt = $pdo->prepare("SELECT {$t['id']} AS id, {$t['name']} AS name FROM {$t['table']} ORDER BY {$t['name']} ASC");
  $stmt->execute();
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

// ─── AJAX: Poll messages between me and a specific person ─────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'poll') {
  header('Content-Type: application/json');
  $contact_role = $_GET['contact_role'] ?? '';
  $contact_id   = (int) ($_GET['contact_id'] ?? 0);
  $last_id      = (int) ($_GET['last_id'] ?? 0);

  $stmt = $pdo->prepare(
    "SELECT * FROM general_messages
     WHERE id > ?
       AND (
         (sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?)
         OR
         (sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?)
       )
     ORDER BY sent_at ASC"
  );
  $stmt->execute([
    $last_id,
    $my_id,      $my_role,      $contact_id, $contact_role,
    $contact_id, $contact_role, $my_id,      $my_role
  ]);
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

// ─── AJAX: Send message ───────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $message       = trim($_POST['message'] ?? '');
  $receiver_role = trim($_POST['receiver_role'] ?? '');
  $receiver_id   = (int) ($_POST['receiver_id'] ?? 0);

  if (!empty($message) && !empty($receiver_role) && $receiver_id > 0) {
    $stmt = $pdo->prepare(
      "INSERT INTO general_messages (sender_id, sender_role, receiver_id, receiver_role, message)
       VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$my_id, $my_role, $receiver_id, $receiver_role, $message]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  } else {
    echo json_encode(['success' => false]);
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Messages</title>
    <link rel="stylesheet" href="../assets/css/donor.css">
    <style>
      .chat-layout {
        display: flex;
        gap: 0;
        height: 540px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(99,179,237,0.15);
        margin-top: 10px;
      }
      .chat-contacts {
        width: 200px;
        flex-shrink: 0;
        background: rgba(15,23,42,0.7);
        border-right: 1px solid rgba(99,179,237,0.12);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
      }
      .chat-contacts::-webkit-scrollbar { width: 3px; }
      .chat-contacts::-webkit-scrollbar-thumb { background: rgba(99,179,237,0.15); border-radius: 3px; }
      .chat-contacts h4 {
        padding: 16px 14px 10px;
        margin: 0;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        flex-shrink: 0;
      }
      .group-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 14px;
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 0.87rem;
        cursor: pointer;
        text-align: left;
        width: 100%;
        transition: all 0.15s ease;
        border-left: 3px solid transparent;
      }
      .group-btn:hover { background: rgba(99,179,237,0.07); color: #e2e8f0; }
      .group-btn.open  { color: #63b3ed; border-left-color: #63b3ed; background: rgba(99,179,237,0.08); }
      .group-btn .g-left { display: flex; align-items: center; gap: 9px; }
      .group-btn .avatar {
        width: 29px; height: 29px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.88rem;
        background: rgba(99,179,237,0.15);
        flex-shrink: 0;
      }
      .group-btn .chevron { font-size: 0.62rem; transition: transform 0.2s; }
      .group-btn.open .chevron { transform: rotate(90deg); }

      .contact-sublist {
        display: none;
        flex-direction: column;
        background: rgba(8,15,30,0.4);
        border-bottom: 1px solid rgba(99,179,237,0.07);
      }
      .contact-sublist.open { display: flex; }
      .sublist-loading { padding: 9px 14px 9px 30px; font-size: 0.77rem; color: #475569; }

      .person-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px 9px 26px;
        background: none;
        border: none;
        color: #7a91a8;
        font-size: 0.83rem;
        cursor: pointer;
        text-align: left;
        width: 100%;
        transition: all 0.15s ease;
        border-left: 3px solid transparent;
      }
      .person-btn:hover { background: rgba(99,179,237,0.08); color: #e2e8f0; }
      .person-btn.active { background: rgba(99,179,237,0.14); color: #93c5fd; border-left-color: #93c5fd; }
      .p-dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(99,179,237,0.3); flex-shrink: 0; }
      .person-btn.active .p-dot { background: #4ade80; box-shadow: 0 0 5px #4ade80; }

      .chat-panel { flex: 1; display: flex; flex-direction: column; background: rgba(8,15,30,0.6); min-width: 0; }
      .chat-header {
        padding: 13px 18px;
        border-bottom: 1px solid rgba(99,179,237,0.1);
        font-size: 0.91rem;
        color: #e2e8f0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
      }
      .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
      .chat-placeholder { flex: 1; display: flex; align-items: center; justify-content: center; color: #334155; font-size: 0.87rem; text-align: center; padding: 30px; }
      .chat-window { flex: 1; overflow-y: auto; padding: 16px 14px; display: flex; flex-direction: column; gap: 10px; }
      .chat-window::-webkit-scrollbar { width: 4px; }
      .chat-window::-webkit-scrollbar-thumb { background: rgba(99,179,237,0.2); border-radius: 4px; }
      .bubble-row { display: flex; align-items: flex-end; gap: 8px; }
      .bubble-row.mine   { flex-direction: row-reverse; }
      .bubble-row.theirs { flex-direction: row; }
      .bubble { max-width: 68%; padding: 8px 13px; border-radius: 16px; font-size: 0.86rem; line-height: 1.45; word-break: break-word; }
      .bubble-row.mine .bubble { background: rgba(99,179,237,0.25); color: #e2e8f0; border-bottom-right-radius: 4px; }
      .bubble-row.theirs .bubble { background: rgba(30,41,59,0.9); color: #cbd5e1; border-bottom-left-radius: 4px; border: 1px solid rgba(99,179,237,0.12); }
      .bubble-meta { font-size: 0.69rem; color: #475569; margin-top: 3px; white-space: nowrap; }
      .bubble-row.mine  .bubble-meta { text-align: right; }
      .bubble-row.theirs .bubble-meta { text-align: left; }
      .bubble-wrap { display: flex; flex-direction: column; max-width: 70%; }
      .bubble-row.mine  .bubble-wrap { align-items: flex-end; }
      .bubble-row.theirs .bubble-wrap { align-items: flex-start; }
      .mini-avatar { width: 25px; height: 25px; border-radius: 50%; background: rgba(99,179,237,0.15); display: flex; align-items: center; justify-content: center; font-size: 0.74rem; flex-shrink: 0; }
      .no-messages { text-align: center; color: #475569; font-size: 0.86rem; margin: auto; padding: 30px; }

      .chat-input-row { display: flex; gap: 10px; padding: 12px 14px; border-top: 1px solid rgba(99,179,237,0.1); flex-shrink: 0; background: rgba(15,23,42,0.6); }
      .chat-input-row textarea { flex: 1; padding: 9px 12px; border-radius: 10px; border: 1px solid rgba(99,179,237,0.25); background: rgba(30,41,59,0.8); color: #e2e8f0; font-size: 0.86rem; resize: none; height: 40px; line-height: 1.4; font-family: inherit; transition: border-color 0.2s; }
      .chat-input-row textarea:focus { outline: none; border-color: rgba(99,179,237,0.5); }
      .btn-send { padding: 0 18px; background: rgba(99,179,237,0.2); color: #63b3ed; border: 1px solid rgba(99,179,237,0.4); border-radius: 10px; font-size: 0.86rem; cursor: pointer; font-weight: 600; transition: all 0.2s ease; }
      .btn-send:hover { background: rgba(99,179,237,0.35); color: #fff; }
      .btn-send:disabled { opacity: 0.4; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="app">
    <aside class="sidebar">
        <div class="sidebar-header"><div class="brand">HelpSphere</div></div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="../dashboards/donor-dashboard.php" class="nav-item"><span class="icon">🏠</span><span class="label">Dashboard</span></a></li>
                <li><a href="../donors/donor-profile-preferences.php" class="nav-item"><span class="icon">👤</span><span class="label">Profile & Preferences</span></a></li>
                <li><a href="../donors/donor-food-selection.php" class="nav-item"><span class="icon">🍱</span><span class="label">Food Donation</span></a></li>
                <li><a href="../donors/donor-money-selection.php" class="nav-item"><span class="icon">💰</span><span class="label">Money Donation</span></a></li>
                <li><a href="../donors/donor-resource-donation.php" class="nav-item"><span class="icon">📦</span><span class="label">Resource Donation</span></a></li>
                <li><a href="../donors/donor-volunteer-registration.php" class="nav-item"><span class="icon">🤝</span><span class="label">Volunteering</span></a></li>
                <li><a href="../donors/donor-messages.php" class="nav-item active"><span class="icon">💬</span><span class="label">Communication</span></a></li>
                <li><a href="../../inc/logout.php" class="nav-item"><span class="icon">🚪</span><span class="label">Logout</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button id="menuToggle" class="menu-toggle">☰</button>
                <div class="user-name">Messages</div>
            </div>
        </header>

        <section class="active-requests">
            <div class="section-header"><h3>Chat</h3></div>

            <div class="chat-layout">
              <div class="chat-contacts" id="contactPanel">
                <h4>Contacts</h4>

                <button class="group-btn" onclick="toggleGroup('NGO', this)">
                  <div class="g-left"><div class="avatar">🏢</div> NGOs</div>
                  <span class="chevron">▶</span>
                </button>
                <div class="contact-sublist" id="sublist-NGO">
                  <div class="sublist-loading">Loading…</div>
                </div>
              </div>

              <div class="chat-panel">
                <div class="chat-header">
                  <div class="status-dot" id="statusDot" style="background:#475569;"></div>
                  <span id="chatHeaderLabel">Select a contact to start chatting</span>
                </div>
                <div id="chatPlaceholder" class="chat-placeholder">← Select a person from the contacts list</div>
                <div class="chat-window" id="chatWindow" style="display:none;"></div>
                <div class="chat-input-row">
                  <textarea id="msgInput" placeholder="Select a contact first…" rows="1" disabled
                            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
                  <button class="btn-send" id="sendBtn" onclick="sendMessage()" disabled>Send ➤</button>
                </div>
              </div>
            </div>
        </section>

        <footer class="footer">© 2026 HelpSphere · Donor Portal</footer>
    </main>
</div>

<script src="../assets/js/donor.js"></script>
<script>
  const MY_ROLE    = 'Donor';
  const ROLE_EMOJI = { NGO:'🏢', Admin:'🛡️', Beneficiary:'👥', Donor:'💛' };

  let currentContactId   = null;
  let currentContactRole = null;
  let currentContactName = null;
  let lastId = 0;
  let pollTimer = null;
  const loadedGroups = {};

  function toggleGroup(role, btn) {
    const sublist = document.getElementById('sublist-' + role);
    const isOpen  = sublist.classList.contains('open');
    document.querySelectorAll('.group-btn').forEach(b => b.classList.remove('open'));
    document.querySelectorAll('.contact-sublist').forEach(s => s.classList.remove('open'));
    if (!isOpen) {
      btn.classList.add('open');
      sublist.classList.add('open');
      if (!loadedGroups[role]) loadContacts(role, sublist);
    }
  }

  function loadContacts(role, sublist) {
    fetch(`?action=contacts&role=${role}`)
      .then(r => r.json())
      .then(people => {
        loadedGroups[role] = people;
        sublist.innerHTML = '';
        if (!people.length) { sublist.innerHTML = '<div class="sublist-loading">No contacts found.</div>'; return; }
        people.forEach(p => {
          const btn = document.createElement('button');
          btn.className = 'person-btn';
          btn.innerHTML = `<span class="p-dot"></span>${escHtml(p.name)}`;
          btn.onclick = () => openConversation(p.id, role, p.name, btn);
          sublist.appendChild(btn);
        });
      })
      .catch(() => { sublist.innerHTML = '<div class="sublist-loading">Failed to load.</div>'; });
  }

  function openConversation(id, role, name, btn) {
    document.querySelectorAll('.person-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentContactId = id; currentContactRole = role; currentContactName = name;
    lastId = 0;

    document.getElementById('chatHeaderLabel').textContent = name + '  ·  ' + role;
    const dot = document.getElementById('statusDot');
    dot.style.cssText = 'width:8px;height:8px;border-radius:50%;flex-shrink:0;background:#4ade80;box-shadow:0 0 6px #4ade80;';

    document.getElementById('chatPlaceholder').style.display = 'none';
    const win = document.getElementById('chatWindow');
    win.style.display = 'flex'; win.innerHTML = '<div class="no-messages">Loading…</div>';

    const input = document.getElementById('msgInput');
    input.disabled = false; input.placeholder = `Message ${name}…`;
    document.getElementById('sendBtn').disabled = false;

    if (pollTimer) clearInterval(pollTimer);
    pollMessages();
    pollTimer = setInterval(pollMessages, 3000);
  }

  function pollMessages() {
    if (!currentContactId) return;
    fetch(`?action=poll&contact_role=${currentContactRole}&contact_id=${currentContactId}&last_id=${lastId}`)
      .then(r => r.json())
      .then(msgs => {
        const win = document.getElementById('chatWindow');
        if (msgs.length) {
          const noMsg = win.querySelector('.no-messages');
          if (noMsg) noMsg.remove();
          msgs.forEach(m => {
            const isMine = m.sender_role === MY_ROLE;
            const row = document.createElement('div');
            row.className = 'bubble-row ' + (isMine ? 'mine' : 'theirs');
            row.innerHTML = `
              <div class="mini-avatar">${ROLE_EMOJI[m.sender_role]||'💬'}</div>
              <div class="bubble-wrap">
                <div class="bubble">${escHtml(m.message)}</div>
                <div class="bubble-meta">${isMine?'You':escHtml(currentContactName)} · ${fmtTime(m.sent_at)}</div>
              </div>`;
            win.appendChild(row);
            if (+m.id > lastId) lastId = +m.id;
          });
          win.scrollTop = win.scrollHeight;
        } else if (!win.querySelector('.bubble-row') && lastId === 0) {
          win.innerHTML = '<div class="no-messages">No messages yet. Say hello! 👋</div>';
        }
      })
      .catch(() => {});
  }

  function sendMessage() {
    if (!currentContactId) return;
    const input = document.getElementById('msgInput');
    const msg = input.value.trim();
    if (!msg) return;
    const fd = new FormData();
    fd.append('message', msg);
    fd.append('receiver_role', currentContactRole);
    fd.append('receiver_id',   currentContactId);
    input.value = ''; input.style.height = 'auto';
    fetch('?action=send', {method:'POST', body:fd})
      .then(r=>r.json())
      .then(res=>{ if(res.success) pollMessages(); })
      .catch(()=>{});
  }

  function fmtTime(ts) {
    const d = new Date(ts);
    return d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) + ' · ' + d.toLocaleDateString([],{month:'short',day:'numeric'});
  }
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  document.getElementById('msgInput').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
  });
</script>
</body>
</html>