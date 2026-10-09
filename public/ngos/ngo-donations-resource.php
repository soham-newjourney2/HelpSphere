<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
$ngo_id = (int) $_SESSION['ngo_id'];

// ─── Handle Accept ────────────────────────────────────────────────────────────
if (isset($_POST['accept_id'])) {
    $res_id   = (int) $_POST['accept_id'];
    $donor_id = (int) $_POST['donor_id'];

    // 1. Mark resource_donations as Approved, record which NGO accepted
    $stmt = $pdo->prepare(
        "UPDATE resource_donations
         SET status = 'Approved', ngo_id = ?
         WHERE id = ? AND status = 'Pending'"
    );
    $stmt->execute([$ngo_id, $res_id]);

    // 2. Update the matching donations row: mark Donated, set target_id to this NGO
    //    Match on donor_id + donation_type='Resource' + status='Pending',
    //    most recently created first, limit 1
    $stmt2 = $pdo->prepare(
        "UPDATE donations
         SET status = 'Donated', target_id = ?
         WHERE donor_id = ? AND donation_type = 'Resource' AND status = 'Pending'
         ORDER BY created_at DESC
         LIMIT 1"
    );
    $stmt2->execute([$ngo_id, $donor_id]);

    header("Location: ngo-donations-resource.php");
    exit;
}

// ─── Fetch donations visible to this NGO ─────────────────────────────────────
// Show: all Pending rows + Approved rows where THIS NGO accepted
$stmt = $pdo->prepare(
    "SELECT rd.*, dm.name AS donor_name
     FROM resource_donations rd
     JOIN donor_master dm ON dm.id = rd.donor_id
     WHERE rd.status = 'Pending'
        OR (rd.status = 'Approved' AND rd.ngo_id = ?)
     ORDER BY rd.id DESC"
);
$stmt->execute([$ngo_id]);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resource Donations</title>
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
    .badge-approved { background: rgba(74,222,128,0.15);  color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }

    .btn-accept {
      display: inline-block;
      padding: 5px 16px;
      background: rgba(74,222,128,0.15);
      color: #4ade80;
      border: 1px solid rgba(74,222,128,0.35);
      border-radius: 6px;
      font-size: 0.83rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-accept:hover { background: rgba(74,222,128,0.35); color: #fff; }
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

    <!-- Sidebar -->
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

    <!-- Main Content -->
    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header"><h3>Resource Donations</h3></div>
        </div>
        <div class="user-name">NGO Administrator</div>
      </div>

      <section class="documents">
        <div class="section-header">
          <h3>List of all Donations</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Donor</th>
            <th>Resource Type</th>
            <th>Condition</th>
            <th>Quantity</th>
            <th>Pickup Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>

          <?php if (empty($donations)): ?>
          <tr>
            <td colspan="7" style="text-align:center; color:#64748b; padding:24px;">
              No resource donations available.
            </td>
          </tr>
          <?php else: ?>
            <?php foreach ($donations as $rec):
              $res_id    = (int) $rec['id'];
              $donor_id  = (int) $rec['donor_id'];
              $status    = $rec['status'];
            ?>
          <tr>
            <td><?php echo htmlspecialchars($rec['donor_name']); ?></td>
            <td><?php echo htmlspecialchars($rec['resource_type']); ?></td>
            <td><?php echo htmlspecialchars($rec['resource_condition']); ?></td>
            <td><?php echo htmlspecialchars($rec['quantity']); ?></td>
            <td><?php echo htmlspecialchars($rec['pickup_date']); ?></td>
            <td>
              <span class="badge <?php echo $status === 'Approved' ? 'badge-approved' : 'badge-pending'; ?>">
                <?php echo htmlspecialchars($status); ?>
              </span>
            </td>
            <td>
              <?php if ($status === 'Pending'): ?>
                <form method="POST" action=""
                      onsubmit="return confirm('Accept this resource donation?');">
                  <input type="hidden" name="accept_id" value="<?php echo $res_id; ?>">
                  <input type="hidden" name="donor_id"  value="<?php echo $donor_id; ?>">
                  <button type="submit" class="btn-accept">✔ Accept</button>
                </form>
              <?php else: ?>
                <span style="color:#4ade80; font-size:0.83rem;">✔ Accepted by you</span>
              <?php endif; ?>
            </td>
          </tr>
            <?php endforeach; ?>
          <?php endif; ?>

        </table>
      </section>

      <div class="footer">© NGO Management System</div>
    </main>

  </div>

  <script src="../assets/js/sidebar.js"></script>
</body>
</html>