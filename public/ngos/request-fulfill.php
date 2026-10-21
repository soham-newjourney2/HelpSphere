<?php
//request-fulfill.php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$request_id = (int)($_GET['request_id'] ?? 0);
$error = '';

if (isset($_POST['submit-btn'])) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die("Invalid session token. Please go back and try again.");
    }

    $time     = trim((string)($_POST['time'] ?? ''));
    $type     = trim((string)($_POST['type'] ?? ''));
    $provider = trim((string)($_POST['provider'] ?? ''));
    $delivery = trim((string)($_POST['delivery_method'] ?? ''));

    if ($time === '') {
        $time = date('H:i:s');   // blank time -> current server time
    } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time)) {
        $error = 'Enter a valid time.';
    }
    if ($request_id <= 0) $error = 'Invalid request.';

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $ngo_id = (int)($_SESSION['ngo_id'] ?? 0);

            // Only a request this NGO approved, and only once
            $s = $pdo->prepare("select beneficiary_id from assistance_requests where id=? and status='Approved' and ngo_id=?");
            $s->execute([$request_id, $ngo_id]);
            $beneficiary_id = $s->fetchColumn();
            if ($beneficiary_id === false) throw new RuntimeException('not-allowed');

            $s = $pdo->prepare("update assistance_requests set status='Fulfilled' where id=? and status='Approved' and ngo_id=?");
            $s->execute([$request_id, $ngo_id]);
            if ($s->rowCount() === 0) throw new RuntimeException('not-allowed');

            $s = $pdo->prepare("insert into fulfillment_status (beneficiary_id, request_id, fulfilled_at, support_type, provider, delivery_method) values (?,?,?,?,?,?)");
            $s->execute([$beneficiary_id, $request_id, $time, $type, $provider, $delivery]);

            $pdo->commit();
            header("Location: ngo-beneficiaries-fulfillment.php");
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getMessage() === 'not-allowed') $error = 'Only the NGO that approved this request can fulfill it, and only once.';
            else { error_log('request-fulfill: ' . $e->getMessage()); $error = 'Could not save. Please try again.'; }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fulfill Request</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
</head>

<body>

  <div class="overlay" id="overlay"></div>

  <div class="sky">
    <div class="aurora a1"></div>
    <div class="aurora a2"></div>
    <div class="aurora a3"></div>
  </div>

  <!-- <div class="moon"></div> -->

  <div class="app">

    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="brand">NGO</div>
        <button class="sidebar-close" id="sidebarClose">✕</button>
      </div>

      <div class="tagline">Management & Operations</div>

      
    <nav class="sidebar-nav">
        <ul>

          <li class="nav-item active">
            <a href="../dashboards/ngo-dashboard.php">
              <span class="icon">📊</span>
              <span class="label">Dashboard Overview</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-profile.php">
              <span class="icon">🧾</span>
              <span class="label">Profile & Compliance</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-needs.php">
              <span class="icon">📌</span>
              <span class="label">Needs & Requests</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-beneficiaries.php">
              <span class="icon">👥</span>
              <span class="label">Beneficiaries</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-donations.php">
              <span class="icon">🎁</span>
              <span class="label">Donations & Resources</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-campaigns.php">
              <span class="icon">📣</span>
              <span class="label">Campaigns</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-volunteers.php">
              <span class="icon">🧑‍🤝‍🧑</span>
              <span class="label">Volunteers</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-communication.php">
              <span class="icon">💬</span>
              <span class="label">Communication</span>
            </a>
          </li>

          <li class="nav-item logout">
            <a href="../../inc/logout.php">
              <span class="icon">⎋</span>
              <span class="label">Sign Out</span>
            </a>
          </li>

        </ul>
      </nav>
    </aside>

    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header">
            <h3>Enter Fulfillment Details</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Campaign Form -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Fulfillment Details</h3>
        </div>

        <form method="POST">
        <div class="request-list">

          <div class="request-item">
            <label class="label">Time of Fulfillment</label>
            <input type="time" name="time">
          </div>

          <div class="request-item">
            <label class="label">Support Type</label>
            <input type="text" name="type">
          </div>

          <div class="request-item">
            <label class="label">Provider</label>
            <input type="text" name="provider">
          </div>

          <div class="request-item">
            <label class="label">Delivery Type</label>
            <input type="text" name="delivery_method">
          </div>

          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8'); ?>" />
          <?php if ($error !== ''): ?>
          <div style="color:#f87171;margin-bottom:12px"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif ?>

          <button class="menu" name="submit-btn" >
            Fulfill Request
          </button>

        </div>
        </form>
      </section>

      <div class="footer">
        © NGO Management System
      </div>

    </main>

  </div>

  <!-- <div class="snow">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>

  <div class="snow-accumulation"></div> -->

  <script src="../assets/js/sidebar.js"></script>

</body>
</html>
