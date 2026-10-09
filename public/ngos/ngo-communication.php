<?php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();

$my_id   = (int) $_SESSION['ngo_id'];
$my_role = 'NGO';

// Individual chat contacts (Beneficiaries, Donors, other NGOs only)
$tableMap = [
  'Beneficiary' => ['table' => 'beneficiary_master', 'id' => 'id', 'name' => 'name'],
  'Donor'       => ['table' => 'donor_master',        'id' => 'id', 'name' => 'name'],
  // NGO handled separately to exclude self
];

// ─── AJAX: Get individual contacts for a role ─────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'contacts') {
  header('Content-Type: application/json');
  $role = $_GET['role'] ?? '';

  if ($role === 'NGO') {
    // Exclude self
    $stmt = $pdo->prepare("SELECT id, name FROM ngo_master WHERE id != ? ORDER BY name ASC");
    $stmt->execute([$my_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
  }

  if (!isset($tableMap[$role])) { echo json_encode([]); exit; }
  $t    = $tableMap[$role];
  $stmt = $pdo->prepare("SELECT {$t['id']} AS id, {$t['name']} AS name FROM {$t['table']} ORDER BY {$t['name']} ASC");
  $stmt->execute();
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

// ─── AJAX: Poll direct messages (Beneficiary / Donor / NGO) ───────────────────
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

// ─── AJAX: Poll Admin broadcast channel ───────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'poll_admin') {
  header('Content-Type: application/json');
  $last_id = (int) ($_GET['last_id'] ?? 0);

  // Show: messages this NGO sent to Admins, AND messages Admins sent to NGOs (broadcast)
  $stmt = $pdo->prepare(
    "SELECT * FROM general_messages
     WHERE id > ?
       AND (
         (sender_id = ? AND sender_role = 'NGO'   AND receiver_role = 'Admin')
         OR
         (sender_role = 'Admin' AND receiver_role = 'NGO')
       )
     ORDER BY sent_at ASC"
  );
  $stmt->execute([$last_id, $my_id]);
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

// ─── AJAX: Send direct message ────────────────────────────────────────────────
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

// ─── AJAX: Send broadcast message to all Admins ───────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'send_admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $message = trim($_POST['message'] ?? '');

  if (!empty($message)) {
    $stmt = $pdo->prepare(
      "INSERT INTO general_messages (sender_id, sender_role, receiver_id, receiver_role, message)
       VALUES (?, 'NGO', 0, 'Admin', ?)"
    );
    $stmt->execute([$my_id, $message]);
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
  <title>Communication Center</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    .chat-layout {
      display: flex;
      gap: 0;
      height: 560px;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid rgba(99,179,237,0.15);
    }

    /* ── Contact Panel ── */
    .chat-contacts {
      width: 210px;
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
      font-size: 0.75rem;
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
      font-size: 0.88rem;
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
      width: 30px; height: 30px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.9rem;
      background: rgba(99,179,237,0.15);
      flex-shrink: 0;
    }
    .group-btn .chevron { font-size: 0.65rem; transition: transform 0.2s; }
    .group-btn.open .chevron { transform: rotate(90deg); }

    .contact-sublist {
      display: none;
      flex-direction: column;
      background: rgba(8,15,30,0.4);
      border-bottom: 1px solid rgba(99,179,237,0.07);
    }
    .contact-sublist.open { display: flex; }
    .sublist-loading { padding: 10px 14px 10px 30px; font-size: 0.78rem; color: #475569; }
    .person-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 9px 14px 9px 28px;
      background: none;
      border: none;
      color: #7a91a8;
      font-size: 0.84rem;
      cursor: pointer;
      text-align: left;
      width: 100%;
      transition: all 0.15s ease;
      border-left: 3px solid transparent;
    }
    .person-btn:hover { background: rgba(99,179,237,0.08); color: #e2e8f0; }
    .person-btn.active { background: rgba(99,179,237,0.14); color: #93c5fd; border-left-color: #93c5fd; }
    .person-btn .p-dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(99,179,237,0.3); flex-shrink: 0; }
    .person-btn.active .p-dot { background: #4ade80; box-shadow: 0 0 5px #4ade80; }

    /* ── Chat Panel ── */
    .chat-panel {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: rgba(8,15,30,0.6);
      min-width: 0;
    }
    .chat-header {
      padding: 14px 18px;
      border-bottom: 1px solid rgba(99,179,237,0.1);
      font-size: 0.93rem;
      color: #e2e8f0;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .chat-placeholder { flex: 1; display: flex; align-items: center; justify-content: center; color: #334155; font-size: 0.88rem; text-align: center; padding: 30px; }
    .chat-window { flex: 1; overflow-y: auto; padding: 18px 16px; display: flex; flex-direction: column; gap: 10px; }
    .chat-window::-webkit-scrollbar { width: 4px; }
    .chat-window::-webkit-scrollbar-thumb { background: rgba(99,179,237,0.2); border-radius: 4px; }

    .bubble-row { display: flex; align-items: flex-end; gap: 8px; }
    .bubble-row.mine   { flex-direction: row-reverse; }
    .bubble-row.theirs { flex-direction: row; }
    .bubble { max-width: 68%; padding: 9px 14px; border-radius: 16px; font-size: 0.87rem; line-height: 1.45; word-break: break-word; }
    .bubble-row.mine .bubble { background: rgba(99,179,237,0.25); color: #e2e8f0; border-bottom-right-radius: 4px; }
    .bubble-row.theirs .bubble { background: rgba(30,41,59,0.9); color: #cbd5e1; border-bottom-left-radius: 4px; border: 1px solid rgba(99,179,237,0.12); }
    .bubble-meta { font-size: 0.7rem; color: #475569; margin-top: 3px; white-space: nowrap; }
    .bubble-row.mine  .bubble-meta { text-align: right; }
    .bubble-row.theirs .bubble-meta { text-align: left; }
    .bubble-wrap { display: flex; flex-direction: column; max-width: 70%; }
    .bubble-row.mine  .bubble-wrap { align-items: flex-end; }
    .bubble-row.theirs .bubble-wrap { align-items: flex-start; }
    .mini-avatar { width: 26px; height: 26px; border-radius: 50%; background: rgba(99,179,237,0.2); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0; }
    .no-messages { text-align: center; color: #475569; font-size: 0.87rem; margin: auto; padding: 30px; }

    .chat-input-row {
      display: flex;
      gap: 10px;
      padding: 12px 16px;
      border-top: 1px solid rgba(99,179,237,0.1);
      flex-shrink: 0;
      background: rgba(15,23,42,0.6);
    }
    .chat-input-row textarea {
      flex: 1;
      padding: 10px 13px;
      border-radius: 10px;
      border: 1px solid rgba(99,179,237,0.25);
      background: rgba(30,41,59,0.8);
      color: #e2e8f0;
      font-size: 0.87rem;
      resize: none;
      height: 42px;
      line-height: 1.4;
      font-family: inherit;
      transition: border-color 0.2s;
    }
    .chat-input-row textarea:focus { outline: none; border-color: rgba(99,179,237,0.5); }
    .btn-send {
      padding: 0 20px;
      background: rgba(99,179,237,0.2);
      color: #63b3ed;
      border: 1px solid rgba(99,179,237,0.4);
      border-radius: 10px;
      font-size: 0.87rem;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-send:hover { background: rgba(99,179,237,0.35); color: #fff; }
    .btn-send:disabled { opacity: 0.4; cursor: not-allowed; }

    /* ── Admin Broadcast Section ── */
    .admin-channel {
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid rgba(251,191,36,0.2);
      margin-top: 28px;
      display: flex;
      flex-direction: column;
      height: 380px;
    }
    .admin-channel-header {
      padding: 13px 18px;
      background: rgba(251,191,36,0.08);
      border-bottom: 1px solid rgba(251,191,36,0.15);
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }
    .admin-channel-header h4 { margin: 0; font-size: 0.92rem; color: #fbbf24; font-weight: 600; }
    .admin-channel-header p  { margin: 0; font-size: 0.76rem; color: #92400e; }
    .admin-broadcast-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #fbbf24;
      box-shadow: 0 0 6px #fbbf24;
      flex-shrink: 0;
    }
    .admin-window {
      flex: 1;
      overflow-y: auto;
      padding: 16px 16px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      background: rgba(8,15,30,0.55);
    }
    .admin-window::-webkit-scrollbar { width: 4px; }
    .admin-window::-webkit-scrollbar-thumb { background: rgba(251,191,36,0.2); border-radius: 4px; }
    .admin-input-row {
      display: flex;
      gap: 10px;
      padding: 12px 16px;
      border-top: 1px solid rgba(251,191,36,0.12);
      flex-shrink: 0;
      background: rgba(15,23,42,0.65);
    }
    .admin-input-row textarea {
      flex: 1;
      padding: 10px 13px;
      border-radius: 10px;
      border: 1px solid rgba(251,191,36,0.25);
      background: rgba(30,41,59,0.8);
      color: #e2e8f0;
      font-size: 0.87rem;
      resize: none;
      height: 42px;
      line-height: 1.4;
      font-family: inherit;
      transition: border-color 0.2s;
    }
    .admin-input-row textarea:focus { outline: none; border-color: rgba(251,191,36,0.5); }
    .btn-send-admin {
      padding: 0 20px;
      background: rgba(251,191,36,0.15);
      color: #fbbf24;
      border: 1px solid rgba(251,191,36,0.35);
      border-radius: 10px;
      font-size: 0.87rem;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-send-admin:hover { background: rgba(251,191,36,0.3); color: #fff; }

    /* Admin bubbles use amber tint */
    .admin-bubble-row { display: flex; align-items: flex-end; gap: 8px; }
    .admin-bubble-row.mine   { flex-direction: row-reverse; }
    .admin-bubble-row.theirs { flex-direction: row; }
    .admin-bubble { max-width: 70%; padding: 9px 14px; border-radius: 16px; font-size: 0.87rem; line-height: 1.45; word-break: break-word; }
    .admin-bubble-row.mine .admin-bubble { background: rgba(251,191,36,0.18); color: #fef3c7; border-bottom-right-radius: 4px; }
    .admin-bubble-row.theirs .admin-bubble { background: rgba(30,41,59,0.9); color: #cbd5e1; border-bottom-left-radius: 4px; border: 1px solid rgba(251,191,36,0.15); }
    .admin-bubble-meta { font-size: 0.7rem; color: #78716c; margin-top: 3px; white-space: nowrap; }
    .admin-bubble-row.mine  .admin-bubble-meta { text-align: right; }
    .admin-bubble-row.theirs .admin-bubble-meta { text-align: left; }
    .admin-bubble-wrap { display: flex; flex-direction: column; max-width: 72%; }
    .admin-bubble-row.mine  .admin-bubble-wrap { align-items: flex-end; }
    .admin-bubble-row.theirs .admin-bubble-wrap { align-items: flex-start; }
    .admin-mini-avatar { width: 26px; height: 26px; border-radius: 50%; background: rgba(251,191,36,0.15); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0; }
  </style>
</head>
<body>

  <div class="overlay" id="overlay"></div>
  <div class="sky">
    <div class="aurora a1"></div><div class="aurora a2"></div><div class="aurora a3"></div>
  </div>
  <div class="moon"></div>

  <div class="app">
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="brand">NGO</div>
        <button class="sidebar-close" id="sidebarClose">✕</button>
      </div>
      <div class="tagline">Management & Operations</div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item"><a href="../dashboards/ngo-dashboard.php"><span class="icon">📊</span><span class="label">Dashboard Overview</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-profile.php"><span class="icon">🧾</span><span class="label">Profile & Compliance</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-needs.php"><span class="icon">📌</span><span class="label">Needs & Requests</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-beneficiaries.php"><span class="icon">👥</span><span class="label">Beneficiaries</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-donations.php"><span class="icon">🎁</span><span class="label">Donations & Resources</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-campaigns.php"><span class="icon">📣</span><span class="label">Campaigns</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-volunteers.php"><span class="icon">🧑‍🤝‍🧑</span><span class="label">Volunteers</span></a></li>
          <li class="nav-item active"><a href="../ngos/ngo-communication.php"><span class="icon">💬</span><span class="label">Communication</span></a></li>
          <li class="nav-item logout"><a href="../../inc/logout.php"><span class="icon">⎋</span><span class="label">Sign Out</span></a></li>
        </ul>
      </nav>
    </aside>

    <main class="main-content">
      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header"><h3>Communication Center</h3></div>
        </div>
        <div class="user-name">NGO Administrator</div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════ -->
      <!-- SECTION 1 — Direct Chat (Beneficiaries, Donors, Other NGOs) -->
      <!-- ═══════════════════════════════════════════════════════════ -->
      <section class="active-requests">
        <div class="section-header"><h3>Direct Messages</h3></div>

        <div class="chat-layout">

          <!-- Contact panel -->
          <div class="chat-contacts" id="contactPanel">
            <h4>Contacts</h4>

            <button class="group-btn" onclick="toggleGroup('Beneficiary', this)">
              <div class="g-left"><div class="avatar">👥</div> Beneficiaries</div>
              <span class="chevron">▶</span>
            </button>
            <div class="contact-sublist" id="sublist-Beneficiary">
              <div class="sublist-loading">Loading…</div>
            </div>

            <button class="group-btn" onclick="toggleGroup('Donor', this)">
              <div class="g-left"><div class="avatar">💛</div> Donors</div>
              <span class="chevron">▶</span>
            </button>
            <div class="contact-sublist" id="sublist-Donor">
              <div class="sublist-loading">Loading…</div>
            </div>

            <button class="group-btn" onclick="toggleGroup('NGO', this)">
              <div class="g-left"><div class="avatar">🏢</div> Other NGOs</div>
              <span class="chevron">▶</span>
            </button>
            <div class="contact-sublist" id="sublist-NGO">
              <div class="sublist-loading">Loading…</div>
            </div>
          </div>

          <!-- Chat area -->
          <div class="chat-panel">
            <div class="chat-header">
              <div class="status-dot" id="statusDot" style="background:#475569;box-shadow:none;"></div>
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

      <!-- ═══════════════════════════════════════════════════════════ -->
      <!-- SECTION 2 — General Message Channel to Admins              -->
      <!-- ═══════════════════════════════════════════════════════════ -->
      <section class="active-requests" style="margin-top: 30px;">
        <div class="section-header"><h3>Admin Channel</h3></div>

        <div class="admin-channel">
          <div class="admin-channel-header">
            <div class="admin-broadcast-dot"></div>
            <div>
              <h4>🛡️ Messages to Admins</h4>
              <p>Messages here are visible to all Admins</p>
            </div>
          </div>

          <div class="admin-window" id="adminWindow">
            <div class="no-messages" id="adminNoMsg">Loading…</div>
          </div>

          <div class="admin-input-row">
            <textarea id="adminInput" placeholder="Send a message to all Admins…" rows="1"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendAdminMessage();}"></textarea>
            <button class="btn-send-admin" onclick="sendAdminMessage()">Send ➤</button>
          </div>
        </div>
      </section>

      <div class="footer">© NGO Management System</div>
    </main>
  </div>

  <div class="snow">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>
  <div class="snow-accumulation"></div>
  <script src="../assets/js/sidebar.js"></script>
  <script>
    const MY_ROLE    = 'NGO';
    const ROLE_EMOJI = { Beneficiary:'👥', Admin:'🛡️', NGO:'🏢', Donor:'💛' };

    // ── Direct chat state ──────────────────────────────────────────────────────
    let currentContactId   = null;
    let currentContactRole = null;
    let currentContactName = null;
    let lastId    = 0;
    let pollTimer = null;
    const loadedGroups = {};

    // ── Admin channel state ────────────────────────────────────────────────────
    let adminLastId    = 0;
    let adminPollTimer = null;

    // ── Toggle a role group open/closed ───────────────────────────────────────
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
            btn.dataset.id = p.id; btn.dataset.role = role; btn.dataset.name = p.name;
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
      document.getElementById('statusDot').style.cssText =
        'background:#4ade80;box-shadow:0 0 6px #4ade80;width:8px;height:8px;border-radius:50%;flex-shrink:0;';

      document.getElementById('chatPlaceholder').style.display = 'none';
      const win = document.getElementById('chatWindow');
      win.style.display = 'flex';
      win.innerHTML = '<div class="no-messages">Loading…</div>';

      const input = document.getElementById('msgInput');
      input.disabled = false;
      input.placeholder = `Message ${name}…`;
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
                  <div class="bubble-meta">${isMine ? 'You' : escHtml(currentContactName)} · ${fmtTime(m.sent_at)}</div>
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
      fetch('?action=send', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) pollMessages(); })
        .catch(() => {});
    }

    // ── Admin broadcast channel ────────────────────────────────────────────────
    function pollAdminMessages() {
      fetch(`?action=poll_admin&last_id=${adminLastId}`)
        .then(r => r.json())
        .then(msgs => {
          const win = document.getElementById('adminWindow');
          if (msgs.length) {
            const noMsg = win.querySelector('.no-messages');
            if (noMsg) noMsg.remove();
            msgs.forEach(m => {
              const isMine = (m.sender_role === 'NGO');
              const row = document.createElement('div');
              row.className = 'admin-bubble-row ' + (isMine ? 'mine' : 'theirs');
              const senderLabel = isMine ? 'You' : '🛡️ Admin';
              row.innerHTML = `
                <div class="admin-mini-avatar">${isMine ? '🏢' : '🛡️'}</div>
                <div class="admin-bubble-wrap">
                  <div class="admin-bubble">${escHtml(m.message)}</div>
                  <div class="admin-bubble-meta">${senderLabel} · ${fmtTime(m.sent_at)}</div>
                </div>`;
              win.appendChild(row);
              if (+m.id > adminLastId) adminLastId = +m.id;
            });
            win.scrollTop = win.scrollHeight;
          } else if (!win.querySelector('.admin-bubble-row') && adminLastId === 0) {
            win.innerHTML = '<div class="no-messages" id="adminNoMsg">No messages yet.</div>';
          }
        })
        .catch(() => {});
    }

    function sendAdminMessage() {
      const input = document.getElementById('adminInput');
      const msg = input.value.trim();
      if (!msg) return;
      const fd = new FormData();
      fd.append('message', msg);
      input.value = ''; input.style.height = 'auto';
      fetch('?action=send_admin', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => { if (res.success) pollAdminMessages(); })
        .catch(() => {});
    }

    // ── Shared helpers ─────────────────────────────────────────────────────────
    function fmtTime(ts) {
      const d = new Date(ts);
      return d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) +
             ' · ' + d.toLocaleDateString([],{month:'short',day:'numeric'});
    }
    function escHtml(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Auto-resize textareas
    ['msgInput','adminInput'].forEach(id => {
      document.getElementById(id).addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
      });
    });

    // Start Admin channel polling immediately on page load
    pollAdminMessages();
    adminPollTimer = setInterval(pollAdminMessages, 3000);
  </script>
</body>
</html>