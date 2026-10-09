<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
$ngo_id = (int) $_SESSION['ngo_id'];

// ─── Handle Approve / Reject ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['volunteer_id'], $_POST['new_status'])) {
    $volunteer_id = (int) $_POST['volunteer_id'];
    $new_status   = $_POST['new_status'] === 'Approved' ? 'Approved' : 'Rejected';
    // Security: only update if the campaign belongs to this NGO
    $stmt = $pdo->prepare(
        "UPDATE campaign_volunteers cv
         JOIN campaigns c ON c.id = cv.campaign_id
         SET cv.status = ?
         WHERE cv.id = ? AND c.ngo_id = ?"
    );
    $stmt->execute([$new_status, $volunteer_id, $ngo_id]);
    header("Location: ngo-volunteers.php");
    exit;
}

// ─── Fetch ongoing campaigns for this NGO ─────────────────────────────────────
$stmtOngoing = $pdo->prepare(
    "SELECT id, title, st_date, end_date FROM campaigns
     WHERE ngo_id = ? AND status = 'Ongoing'
     ORDER BY st_date ASC"
);
$stmtOngoing->execute([$ngo_id]);
$ongoingCampaigns = $stmtOngoing->fetchAll(PDO::FETCH_ASSOC);

// ─── Fetch completed campaigns for this NGO ───────────────────────────────────
$stmtDone = $pdo->prepare(
    "SELECT id, title, st_date, end_date FROM campaigns
     WHERE ngo_id = ? AND status = 'Completed'
     ORDER BY end_date DESC"
);
$stmtDone->execute([$ngo_id]);
$doneCampaigns = $stmtDone->fetchAll(PDO::FETCH_ASSOC);

// ─── Helper: fetch volunteers for a campaign ──────────────────────────────────
function getVolunteers($pdo, $campaign_id) {
    $stmt = $pdo->prepare(
        "SELECT cv.id AS vol_id, cv.skills, cv.status, cv.applied_at,
                d.name AS donor_name
         FROM campaign_volunteers cv
         JOIN donor_master d ON d.id = cv.donor_id
         WHERE cv.campaign_id = ?
         ORDER BY cv.applied_at ASC"
    );
    $stmt->execute([(int)$campaign_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Volunteer Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    /* ── Campaign Row ── */
    .campaign-row {
      background: rgba(30,41,59,0.6);
      border: 1px solid rgba(99,179,237,0.13);
      border-radius: 10px;
      margin-bottom: 10px;
      overflow: hidden;
      transition: border-color 0.2s;
    }
    .campaign-row:hover { border-color: rgba(99,179,237,0.3); }

    .campaign-row-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 18px;
      gap: 16px;
      flex-wrap: wrap;
    }
    .campaign-row-info { flex: 1; min-width: 0; }
    .campaign-row-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #e2e8f0;
      margin-bottom: 3px;
    }
    .campaign-row-meta {
      font-size: 0.78rem;
      color: #64748b;
    }

    .btn-view-vol {
      padding: 7px 18px;
      background: rgba(99,179,237,0.15);
      color: #63b3ed;
      border: 1px solid rgba(99,179,237,0.35);
      border-radius: 7px;
      font-size: 0.84rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .btn-view-vol:hover { background: rgba(99,179,237,0.3); color: #fff; }
    .btn-view-vol.open  { background: rgba(99,179,237,0.25); color: #fff; }

    /* ── Volunteer Sub-Panel ── */
    .volunteer-panel {
      display: none;
      border-top: 1px solid rgba(99,179,237,0.1);
      background: rgba(8,15,30,0.45);
      padding: 14px 18px 18px;
    }
    .volunteer-panel.open { display: block; }

    .vol-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }
    .vol-table th {
      text-align: left;
      padding: 8px 12px;
      color: #64748b;
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border-bottom: 1px solid rgba(99,179,237,0.1);
    }
    .vol-table td {
      padding: 10px 12px;
      color: #cbd5e1;
      border-bottom: 1px solid rgba(99,179,237,0.06);
      vertical-align: middle;
    }
    .vol-table tr:last-child td { border-bottom: none; }

    .status-badge {
      display: inline-block;
      padding: 3px 11px;
      border-radius: 20px;
      font-size: 0.76rem;
      font-weight: 600;
    }
    .status-badge.pending  { background: rgba(251,191,36,0.15); color: #fbbf24; border: 1px solid rgba(251,191,36,0.3); }
    .status-badge.approved { background: rgba(74,222,128,0.15); color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }
    .status-badge.rejected { background: rgba(239,68,68,0.15);  color: #f87171; border: 1px solid rgba(239,68,68,0.3); }

    .btn-approve, .btn-reject {
      padding: 5px 13px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
    }
    .btn-approve {
      background: rgba(74,222,128,0.15);
      color: #4ade80;
      border: 1px solid rgba(74,222,128,0.3);
      margin-right: 6px;
    }
    .btn-approve:hover { background: rgba(74,222,128,0.3); color: #fff; }
    .btn-reject {
      background: rgba(239,68,68,0.12);
      color: #f87171;
      border: 1px solid rgba(239,68,68,0.3);
    }
    .btn-reject:hover { background: rgba(239,68,68,0.3); color: #fff; }

    .no-vol {
      color: #475569;
      font-size: 0.84rem;
      padding: 10px 0;
    }

    /* Section divider */
    .section-divider {
      margin: 32px 0 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .section-divider h3 { margin: 0; font-size: 1rem; color: #94a3b8; white-space: nowrap; }
    .section-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(99,179,237,0.1);
    }
    .no-campaigns {
      color: #475569;
      font-size: 0.87rem;
      padding: 14px 0;
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
          <li class="nav-item active"><a href="../ngos/ngo-volunteers.php"><span class="icon">🧑‍🤝‍🧑</span><span class="label">Volunteers</span></a></li>
          <li class="nav-item"><a href="../ngos/ngo-communication.php"><span class="icon">💬</span><span class="label">Communication</span></a></li>
          <li class="nav-item logout"><a href="../../inc/logout.php"><span class="icon">⎋</span><span class="label">Sign Out</span></a></li>
        </ul>
      </nav>
    </aside>

    <main class="main-content">
      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header"><h3>Volunteer Management</h3></div>
        </div>
        <div class="user-name">NGO Administrator</div>
      </div>

      <!-- ══════════════════════════════════════════════ -->
      <!-- ONGOING CAMPAIGNS                              -->
      <!-- ══════════════════════════════════════════════ -->
      <section class="documents">
        <div class="section-header"><h3>Ongoing Campaigns</h3></div>

        <?php if (empty($ongoingCampaigns)): ?>
          <p class="no-campaigns">No ongoing campaigns at the moment.</p>
        <?php else: ?>
          <?php foreach ($ongoingCampaigns as $c):
            $cid  = (int) $c['id'];
            $vols = getVolunteers($pdo, $cid);
          ?>
          <div class="campaign-row">
            <div class="campaign-row-header">
              <div class="campaign-row-info">
                <div class="campaign-row-title"><?php echo htmlspecialchars($c['title']); ?></div>
                <div class="campaign-row-meta">
                  📅 <?php echo htmlspecialchars($c['st_date']); ?> → <?php echo htmlspecialchars($c['end_date']); ?>
                  &nbsp;·&nbsp; <?php echo count($vols); ?> applicant<?php echo count($vols) !== 1 ? 's' : ''; ?>
                </div>
              </div>
              <button class="btn-view-vol" onclick="togglePanel('panel-<?php echo $cid; ?>', this)">
                👥 View Volunteers
              </button>
            </div>

            <div class="volunteer-panel" id="panel-<?php echo $cid; ?>">
              <?php if (empty($vols)): ?>
                <p class="no-vol">No volunteers have applied for this campaign yet.</p>
              <?php else: ?>
                <table class="vol-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Volunteer Name</th>
                      <th>Skills</th>
                      <th>Applied On</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($vols as $i => $v):
                      $statusClass = strtolower($v['status']);
                      $isPending   = $v['status'] === 'Pending';
                    ?>
                    <tr>
                      <td><?php echo $i + 1; ?></td>
                      <td><?php echo htmlspecialchars($v['donor_name']); ?></td>
                      <td><?php echo htmlspecialchars($v['skills'] ?: '—'); ?></td>
                      <td><?php echo date('d M Y', strtotime($v['applied_at'])); ?></td>
                      <td>
                        <span class="status-badge <?php echo $statusClass; ?>">
                          <?php echo htmlspecialchars($v['status']); ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($isPending): ?>
                          <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="volunteer_id" value="<?php echo (int)$v['vol_id']; ?>">
                            <input type="hidden" name="new_status"   value="Approved">
                            <button type="submit" class="btn-approve">✔ Approve</button>
                          </form>
                          <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="volunteer_id" value="<?php echo (int)$v['vol_id']; ?>">
                            <input type="hidden" name="new_status"   value="Rejected">
                            <button type="submit" class="btn-reject">✖ Reject</button>
                          </form>
                        <?php else: ?>
                          <span style="color:#475569; font-size:0.8rem;">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <!-- ══════════════════════════════════════════════ -->
      <!-- COMPLETED CAMPAIGNS                            -->
      <!-- ══════════════════════════════════════════════ -->
      <div class="section-divider"><h3>Completed Campaigns</h3></div>

      <section class="documents">
        <?php if (empty($doneCampaigns)): ?>
          <p class="no-campaigns">No completed campaigns yet.</p>
        <?php else: ?>
          <?php foreach ($doneCampaigns as $c):
            $cid  = (int) $c['id'];
            $vols = getVolunteers($pdo, $cid);
          ?>
          <div class="campaign-row" style="border-color: rgba(148,163,184,0.12);">
            <div class="campaign-row-header">
              <div class="campaign-row-info">
                <div class="campaign-row-title" style="color:#94a3b8;">
                  <?php echo htmlspecialchars($c['title']); ?>
                  <span style="font-size:0.72rem; color:#475569; font-weight:400; margin-left:8px;">COMPLETED</span>
                </div>
                <div class="campaign-row-meta">
                  📅 <?php echo htmlspecialchars($c['st_date']); ?> → <?php echo htmlspecialchars($c['end_date']); ?>
                  &nbsp;·&nbsp; <?php echo count($vols); ?> participant<?php echo count($vols) !== 1 ? 's' : ''; ?>
                </div>
              </div>
              <button class="btn-view-vol" style="border-color:rgba(148,163,184,0.3); color:#94a3b8;"
                      onclick="togglePanel('done-panel-<?php echo $cid; ?>', this)">
                👥 View Volunteers
              </button>
            </div>

            <div class="volunteer-panel" id="done-panel-<?php echo $cid; ?>">
              <?php if (empty($vols)): ?>
                <p class="no-vol">No volunteers participated in this campaign.</p>
              <?php else: ?>
                <table class="vol-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Volunteer Name</th>
                      <th>Skills</th>
                      <th>Applied On</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($vols as $i => $v):
                      $statusClass = strtolower($v['status']);
                    ?>
                    <tr>
                      <td><?php echo $i + 1; ?></td>
                      <td><?php echo htmlspecialchars($v['donor_name']); ?></td>
                      <td><?php echo htmlspecialchars($v['skills'] ?: '—'); ?></td>
                      <td><?php echo date('d M Y', strtotime($v['applied_at'])); ?></td>
                      <td>
                        <span class="status-badge <?php echo $statusClass; ?>">
                          <?php echo htmlspecialchars($v['status']); ?>
                        </span>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <div class="footer">© NGO Management System</div>
    </main>
  </div>

  <script src="../assets/js/sidebar.js"></script>
  <script>
    function togglePanel(panelId, btn) {
      const panel = document.getElementById(panelId);
      const isOpen = panel.classList.contains('open');
      // Close all panels first
      document.querySelectorAll('.volunteer-panel').forEach(p => p.classList.remove('open'));
      document.querySelectorAll('.btn-view-vol').forEach(b => b.classList.remove('open'));
      if (!isOpen) {
        panel.classList.add('open');
        btn.classList.add('open');
        btn.textContent = '✖ Hide Volunteers';
      } else {
        btn.textContent = '👥 View Volunteers';
      }
    }
  </script>
</body>
</html>