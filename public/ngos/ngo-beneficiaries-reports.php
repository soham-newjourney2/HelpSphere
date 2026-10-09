<?php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login(); 

/*
  ── Required DB table (run once) ────────────────────────────────────────────
  CREATE TABLE document_reports (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    ngo_id           INT          NOT NULL,
    ngo_name         VARCHAR(255) NOT NULL,
    beneficiary_name VARCHAR(255) NOT NULL,
    document_name    VARCHAR(255) NOT NULL,
    report           TEXT         NOT NULL,
    reported_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
  );
  ─────────────────────────────────────────────────────────────────────────────
*/

$stmtReports = $pdo->prepare("SELECT * FROM document_reports ORDER BY reported_at DESC");
$stmtReports->execute();
$reports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document Reports</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    .btn-back {
      display: inline-block;
      margin-bottom: 16px;
      padding: 8px 18px;
      background: rgba(148, 163, 184, 0.15);
      color: #94a3b8;
      border: 1px solid rgba(148, 163, 184, 0.3);
      border-radius: 6px;
      font-size: 0.88rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-back:hover { background: rgba(148, 163, 184, 0.3); color: #fff; }

    .report-text {
      max-width: 320px;
      white-space: pre-wrap;
      word-break: break-word;
      color: #cbd5e1;
      font-size: 0.88rem;
    }
    .badge-ngo {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 600;
      background: rgba(167, 139, 250, 0.15);
      color: #a78bfa;
      border: 1px solid rgba(167, 139, 250, 0.3);
    }
  </style>
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
          <li class="nav-item">
            <a href="../dashboards/ngo-dashboard.php">
              <span class="icon">📊</span><span class="label">Dashboard Overview</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../ngos/ngo-profile.php">
              <span class="icon">🧾</span><span class="label">Profile & Compliance</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../ngos/ngo-needs.php">
              <span class="icon">📌</span><span class="label">Needs & Requests</span>
            </a>
          </li>
          <li class="nav-item active">
            <a href="../ngos/ngo-beneficiaries.php">
              <span class="icon">👥</span><span class="label">Beneficiaries</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../ngos/ngo-donations.php">
              <span class="icon">🎁</span><span class="label">Donations & Resources</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../ngos/ngo-campaigns.php">
              <span class="icon">📣</span><span class="label">Campaigns</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../ngos/ngo-volunteers.php">
              <span class="icon">🧑‍🤝‍🧑</span><span class="label">Volunteers</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../ngos/ngo-communication.php">
              <span class="icon">💬</span><span class="label">Communication</span>
            </a>
          </li>
          <li class="nav-item logout">
            <a href="../../inc/logout.php">
              <span class="icon">⎋</span><span class="label">Sign Out</span>
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
            <h3>Document Reports</h3>
          </div>
        </div>
        <div class="user-name">NGO Administrator</div>
      </div>

      <a href="ngo-beneficiaries-review.php" class="btn-back">← Back to Requests</a>

      <section class="documents">
        <div class="section-header">
          <h3>All Submitted Reports</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Reported By</th>
            <th>Beneficiary</th>
            <th>Document Name</th>
            <th>Report</th>
            <th>Reported At</th>
          </tr>
          <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $report): ?>
              <tr>
                <td><span class="badge-ngo"><?php echo htmlspecialchars($report['ngo_name']); ?></span></td>
                <td><?php echo htmlspecialchars($report['beneficiary_name']); ?></td>
                <td><?php echo htmlspecialchars($report['document_name']); ?></td>
                <td><div class="report-text"><?php echo htmlspecialchars($report['report']); ?></div></td>
                <td><?php echo isset($report['reported_at']) ? date('M d, Y H:i', strtotime($report['reported_at'])) : '—'; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align:center; color:#aaa; padding:20px;">No reports submitted yet.</td>
            </tr>
          <?php endif; ?>
        </table>
      </section>

      <div class="footer">© NGO Management System</div>

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