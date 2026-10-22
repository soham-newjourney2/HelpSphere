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
  $message       = mb_substr(trim($_POST['message'] ?? ''), 0, 255);
  $receiver_role = trim($_POST['receiver_role'] ?? '');
  $receiver_id   = (int) ($_POST['receiver_id'] ?? 0);

  if (hash_equals(csrf_token(), (string)($_POST['csrf'] ?? '')) && $message !== ''
      && in_array($receiver_role, ['Beneficiary', 'Donor', 'NGO'], true) && $receiver_id > 0) {
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
  $message = mb_substr(trim($_POST['message'] ?? ''), 0, 255);

  if (hash_equals(csrf_token(), (string)($_POST['csrf'] ?? '')) && $message !== '') {
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
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Communication · NGO | HelpSphere</title>
  <meta name="hs-icons" content="../assets/img/icons.svg" />
  <meta name="csrf" content="<?php echo e(csrf_token()) ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Inter+Tight:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/base.css" />
  <link rel="stylesheet" href="../assets/css/app.css" />
</head>
<body>
<div class="app">

  <!-- ============ Sidebar ============ -->
  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="sidebar__head">
      <a href="../../index.php" class="logo logo--light">
        <span class="logo__mark"><svg ><use href="../assets/img/icons.svg#i-sprout"/></svg></span>
        HelpSphere
      </a>
      <button type="button" class="sidebar__close" data-sidebar-close aria-label="Close menu"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></button>
    </div>

    <span class="sidebar__tag">NGO workspace</span>

    <nav class="sidebar__nav" aria-label="NGO workspace">
      <a href="../ngos/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../ngos/ngo-needs.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>Needs &amp; requests</a>
      <a href="../ngos/ngo-beneficiaries.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
      <a href="../ngos/ngo-communication.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Communication</a>
      <a href="../ngos/ngo-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Profile &amp; compliance</a>
    </nav>

    <div class="sidebar__foot">
      <a href="../../logout.php" class="sidebar__logout"><svg class="icon"><use href="../assets/img/icons.svg#i-logout"/></svg>Sign out</a>
    </div>
  </aside>
  <div class="scrim" id="scrim" hidden></div>

  <!-- ============ Main ============ -->
  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn topbar__menu" id="menuBtn" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-menu"/></svg></button>
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../ngos/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Communication</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg></span>
          <div class="profile__text"><strong>NGO team</strong><span>NGO workspace</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">Communication</p>
          <h1 class="page-head__title">Communication centre</h1>
          <p class="page-head__sub">Message beneficiaries, donors and other NGOs, and reach the HelpSphere admin team. New messages appear automatically.</p>
        </div>
      </section>

      <section class="chat reveal" id="chat" data-my-role="NGO">
        <div class="chat__contacts">
          <p class="chat__contacts-title">Contacts</p>
          <button type="button" class="group-btn" data-group="Beneficiary" aria-expanded="false">
            <span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg></span>Beneficiaries<svg class="icon chev"><use href="../assets/img/icons.svg#i-arrow"/></svg>
          </button>
          <div class="contact-list" data-list="Beneficiary" hidden></div>
          <button type="button" class="group-btn" data-group="Donor" aria-expanded="false">
            <span class="avatar avatar--warm"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span>Donors<svg class="icon chev"><use href="../assets/img/icons.svg#i-arrow"/></svg>
          </button>
          <div class="contact-list" data-list="Donor" hidden></div>
          <button type="button" class="group-btn" data-group="NGO" aria-expanded="false">
            <span class="avatar avatar--blue"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg></span>Other NGOs<svg class="icon chev"><use href="../assets/img/icons.svg#i-arrow"/></svg>
          </button>
          <div class="contact-list" data-list="NGO" hidden></div>
        </div>

        <div class="chat__panel">
          <div class="chat__head">
            <button type="button" class="icon-btn chat__back" id="chatBack" aria-label="Back to contacts"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow-left"/></svg></button>
            <span class="online-dot" id="chatDot"></span>
            <div>
              <strong id="chatName">Select a contact</strong><br>
              <span id="chatRole">Choose someone from the list</span>
            </div>
          </div>
          <div class="chat__window" id="chatWindow" aria-live="polite">
            <div class="chat__placeholder"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg><p>Select a person from your contacts to start chatting.</p></div>
          </div>
          <form class="chat__input" id="chatForm">
            <label class="sr-only" for="chatInput">Message</label>
            <textarea id="chatInput" rows="1" placeholder="Select a contact first…" disabled></textarea>
            <button type="submit" class="btn btn--dark btn--sm" id="chatSend" disabled><svg class="icon"><use href="../assets/img/icons.svg#i-send"/></svg>Send</button>
          </form>
        </div>
      </section>

      <section class="reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Admin channel</h2>
            <p class="card__sub">Messages here are visible to all HelpSphere admins</p>
          </div>
        </header>
        <div class="channel" id="adminChannel">
          <div class="chat__head">
            <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg></span>
            <div>
              <strong>HelpSphere admins</strong><br>
              <span>Questions about verification, accounts or anything else</span>
            </div>
          </div>
          <div class="chat__window" id="adminWindow" aria-live="polite">
            <div class="chat__placeholder"><svg class="icon"><use href="../assets/img/icons.svg#i-history"/></svg><p>Loading…</p></div>
          </div>
          <form class="chat__input" id="adminForm">
            <label class="sr-only" for="adminInput">Message to admins</label>
            <textarea id="adminInput" rows="1" placeholder="Send a message to all admins…"></textarea>
            <button type="submit" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-send"/></svg>Send</button>
          </form>
        </div>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/chat.js"></script>
</body>
</html>
