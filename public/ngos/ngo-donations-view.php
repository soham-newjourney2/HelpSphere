<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Donation Intake</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    .badge {
      display: inline-block;
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 600;
    }
    .badge-pending  { background: rgba(251,191,36,0.15);  color: #fbbf24; border: 1px solid rgba(251,191,36,0.3); }
    .badge-donated  { background: rgba(74,222,128,0.15);  color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }
  </style>
</head>
<body>

  <div class="overlay" id="overlay"></div>
  <div class="sky">
    <div class="aurora a1"></div>
    <div class="aurora a2"></div>
    <div class="aurora a3"></div>
  </div>

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
          <li class="nav-item active"><a href="../ngos/ngo-donations.php"><span class="icon">🎁</span><span class="label">Donations & Resources</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-campaigns.php"><span class="icon">📣</span><span class="label">Campaigns</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-volunteers.php"><span class="icon">🧑‍🤝‍🧑</span><span class="label">Volunteers</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-communication.php"><span class="icon">💬</span><span class="label">Communication</span></a></li>
          <li class="nav-item logout"><a href="../../inc/logout.php"><span class="icon">⎋</span><span class="label">Sign Out</span></a></li>
        </ul>
      </nav>
    </aside>

    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header"><h3>Donation List</h3></div>
        </div>
        <div class="user-name">NGO Administrator</div>
      </div>

      <section class="documents">
        <div class="section-header">
          <h3>Recent Donations To You</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Donor</th>
            <th>Type</th>
            <th>Date and Time</th>
            <th>Status</th>
          </tr>
          <?php
          $stmt = $pdo->prepare("SELECT * FROM donations WHERE target_id = ? AND target_role = ? ORDER BY created_at DESC");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->bindValue(2, 'NGO');
          $stmt->execute();

          if ($stmt->rowCount() > 0):
            while ($result = $stmt->fetch(PDO::FETCH_ASSOC)):
              $donor_id    = (int) $result['donor_id'];
              $type        = htmlspecialchars($result['donation_type']);
              $date        = $result['created_at'];
              $status      = $result['status'];

              // ── Donor name from donor_master ──────────────────────────────
              $donor = '—';
              if ($donor_id > 0) {
                $stmtD = $pdo->prepare("SELECT name FROM donor_master WHERE id = ?");
                $stmtD->execute([$donor_id]);
                $rowD = $stmtD->fetch(PDO::FETCH_ASSOC);
                if ($rowD !== false) $donor = htmlspecialchars($rowD['name']);
              }

              $badgeClass = $status === 'Donated' ? 'badge-donated' : 'badge-pending';
          ?>
          <tr>
            <td><?php echo $donor; ?></td>
            <td><?php echo $type; ?></td>
            <td><?php echo $date; ?></td>
            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
          </tr>
          <?php
            endwhile;
          else:
          ?>
          <tr>
            <td colspan="5" style="text-align:center; color:#64748b; padding:24px;">No donations recorded yet.</td>
          </tr>
          <?php endif; ?>
        </table>
      </section>

      <div class="footer">© NGO Management System</div>
    </main>

  </div>

  <script src="../assets/js/sidebar.js"></script>
</body>
</html>