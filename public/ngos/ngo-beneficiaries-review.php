<?php 
//ngo-beneficiaries-review.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Beneficiary Request Review</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    .btn-documents {
      display: inline-block;
      padding: 6px 14px;
      background: rgba(167, 139, 250, 0.2);
      color: #a78bfa;
      border: 1px solid rgba(167, 139, 250, 0.4);
      border-radius: 6px;
      font-size: 0.85rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-documents:hover {
      background: rgba(167, 139, 250, 0.35);
      color: #fff;
    }
    .btn-reports {
      display: inline-block;
      margin-top: 16px;
      padding: 10px 22px;
      background: rgba(251, 191, 36, 0.15);
      color: #f59e0b;
      border: 1px solid rgba(251, 191, 36, 0.35);
      border-radius: 8px;
      font-size: 0.9rem;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s ease;
    }
    .btn-reports:hover {
      background: rgba(251, 191, 36, 0.3);
      color: #fff;
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

          <li class="nav-item active">
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
            <h3>Request Review</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 1 — Beneficiaries & Their Documents                        -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <section class="documents">
        <div class="section-header">
          <h3>Beneficiary Documents</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Beneficiary Name</th>
            <th>Address</th>
            <th>Documents</th>
          </tr>
          <?php
          $stmtBen = $pdo->prepare("SELECT * FROM beneficiary_master ORDER BY name ASC");
          $stmtBen->execute();
          if ($stmtBen->rowCount() > 0):
            while ($ben = $stmtBen->fetch(PDO::FETCH_ASSOC)):
              $ben_id      = (int) $ben['id'];
              $ben_name    = htmlspecialchars($ben['name'] ?? '');
              $ben_address = htmlspecialchars($ben['address'] ?? '—');
          ?>
          <tr>
            <td><?php echo $ben_name; ?></td>
            <td><?php echo $ben_address; ?></td>
            <td>
              <a href="../ngos/ngo-beneficiaries-documents.php?beneficiary_id=<?php echo $ben_id; ?>"
                 class="btn-documents">📄 View Documents</a>
            </td>
          </tr>
          <?php
            endwhile;
          else:
          ?>
          <tr>
            <td colspan="3" style="text-align:center; color:#aaa; padding:20px;">No beneficiaries registered yet.</td>
          </tr>
          <?php endif; ?>
        </table>

        <a href="../ngos/ngo-beneficiaries-reports.php" class="btn-reports">📋 View all Reports</a>
      </section>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 2 — Pending Requests with Approve / Reject                 -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <section class="documents" style="margin-top: 32px;">
        <div class="section-header">
          <h3>Pending Beneficiary Requests</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Applicant Name</th>
            <th>Requested Need</th>
            <th>Submitted On</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
          <?php 
          $name="";
          $description="";
          $status="";
          $created_at="";
          $stmt = $pdo->prepare("SELECT * FROM assistance_requests WHERE status = ?");
          $stmt->bindValue(1, "Pending");
          $stmt->execute();
          if ($stmt->rowCount() > 0):
            while ($rec = $stmt->fetch(PDO::FETCH_ASSOC)):
              $stmt1=$pdo->prepare("SELECT * FROM assistance_rejection WHERE request_id=? and rejected_by_ngo_id=?");
              $stmt1->bindParam(1, $rec['id']);
              $stmt1->bindParam(2, $_SESSION['ngo_id']);
              $stmt1->execute();
              if($stmt1->rowCount() == 0){
                $id          = $rec['beneficiary_id'];
              $request_id  = $rec['id'];
              $description = htmlspecialchars($rec['description']);
              $status      = htmlspecialchars($rec['status']);
              $created_at  = $rec['created_at'];

              $stmt1 = $pdo->prepare("SELECT * FROM beneficiary_master WHERE id = ?");
              $stmt1->bindParam(1, $id);
              $stmt1->execute();
              $name = '—';
              if ($stmt1->rowCount() > 0) {
                $res  = $stmt1->fetch(PDO::FETCH_ASSOC);
                $name = htmlspecialchars($res['name']);
              }            
          ?>
          <tr>
            <td><?php echo $name; ?></td>
            <td><?php echo $description; ?></td>
            <td><?php echo $created_at; ?></td>
            <td><?php echo $status; ?></td>
            <td>
              <?php echo action_button('../ngos/request-edit.php', ['value'=>'Approved','request_id'=>$request_id], 'Approve') ?><br>
              <?php echo action_button('../ngos/request-edit.php', ['value'=>'Rejected','request_id'=>$request_id], 'Reject') ?>
            </td>
          </tr>
          <?php
              }
            endwhile;
          else:
          ?>
          <tr>
            <td colspan="5" style="text-align:center; color:#aaa; padding:20px;">No pending requests at the moment.</td>
          </tr>
          <?php endif; ?>
        </table>
      </section>

      <!-- Review Guidance -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Review Guidance</h3>
        </div>

        <div class="request-list">
          <div class="request-item">
            Verify beneficiary identity and eligibility before approval.
          </div>
          <div class="request-item">
            Cross-check request details with available resources.
          </div>
          <div class="request-item">
            Approved requests will move to beneficiary assignment.
          </div>
          <div class="request-item">
            Rejected requests should include documented reasons.
          </div>
        </div>
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
