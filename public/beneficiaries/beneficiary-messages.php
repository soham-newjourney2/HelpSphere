<?php
require_once __DIR__ . '/../../inc/auth.php';
require_login();

$my_id   = (int) $_SESSION['beneficiary_id'];
$my_role = 'Beneficiary';

$tableMap = [
  'NGO' => ['table' => 'ngo_master', 'id' => 'id', 'name' => 'name'],
];

// ─── AJAX: Get contacts for a role ────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'contacts') {
  header('Content-Type: application/json');
  $role = $_GET['role'] ?? '';
  if (!isset($tableMap[$role])) { 
    echo json_encode([]); 
    exit; 
  }
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
  $message       = mb_substr(trim($_POST['message'] ?? ''), 0, 255);
  $receiver_role = trim($_POST['receiver_role'] ?? '');
  $receiver_id   = (int) ($_POST['receiver_id'] ?? 0);

  if (hash_equals(csrf_token(), (string)($_POST['csrf'] ?? '')) && $message !== '' && isset($tableMap[$receiver_role]) && $receiver_id > 0) {
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
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Messages · Beneficiary | HelpSphere</title>
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

    <span class="sidebar__tag">Beneficiary space</span>

    <nav class="sidebar__nav" aria-label="Beneficiary space">
      <a href="../dashboards/beneficiary-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../beneficiaries/beneficiary-request-management.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>My requests</a>
      <a href="../beneficiaries/beneficiary-document-upload.php"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
      <a href="../beneficiaries/beneficiary-documents.php"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
      <a href="../beneficiaries/beneficiary-messages.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../beneficiaries/beneficiary-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile</a>
    </nav>

    <div class="sidebar__foot">
      <a href="../../inc/logout.php" class="sidebar__logout"><svg class="icon"><use href="../assets/img/icons.svg#i-logout"/></svg>Sign out</a>
    </div>
  </aside>
  <div class="scrim" id="scrim" hidden></div>

  <!-- ============ Main ============ -->
  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn topbar__menu" id="menuBtn" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-menu"/></svg></button>
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Messages</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg></span>
          <div class="profile__text"><strong>Beneficiary</strong><span>Beneficiary space</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">Messages</p>
          <h1 class="page-head__title">Talk to NGOs</h1>
          <p class="page-head__sub">Ask about your request or share extra details. New messages appear automatically.</p>
        </div>
      </section>

      <section class="chat reveal" id="chat" data-my-role="Beneficiary">
        <div class="chat__contacts">
          <p class="chat__contacts-title">Contacts</p>
          <button type="button" class="group-btn" data-group="NGO" aria-expanded="false">
            <span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg></span>NGOs<svg class="icon chev"><use href="../assets/img/icons.svg#i-arrow"/></svg>
          </button>
          <div class="contact-list" data-list="NGO" hidden></div>
        </div>

        <div class="chat__panel">
          <div class="chat__head">
            <button type="button" class="icon-btn chat__back" id="chatBack" aria-label="Back to contacts"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow-left"/></svg></button>
            <span class="online-dot" id="chatDot"></span>
            <div>
              <strong id="chatName">Select a contact</strong><br>
              <span id="chatRole">Choose an NGO from the list</span>
            </div>
          </div>
          <div class="chat__window" id="chatWindow" aria-live="polite">
            <div class="chat__placeholder"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg><p>Select an NGO from your contacts to start chatting.</p></div>
          </div>
          <form class="chat__input" id="chatForm">
            <label class="sr-only" for="chatInput">Message</label>
            <textarea id="chatInput" rows="1" placeholder="Select a contact first…" disabled></textarea>
            <button type="submit" class="btn btn--dark btn--sm" id="chatSend" disabled><svg class="icon"><use href="../assets/img/icons.svg#i-send"/></svg>Send</button>
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
