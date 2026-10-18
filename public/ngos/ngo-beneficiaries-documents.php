<?php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();

// ─── Fetch context from URL ───────────────────────────────────────────────────
$beneficiary_id = isset($_GET['beneficiary_id']) ? (int) $_GET['beneficiary_id'] : 0;
$request_id     = isset($_GET['request_id'])     ? (int) $_GET['request_id']     : 0;

// ─── Fetch NGO name ───────────────────────────────────────────────────────────
$stmtNgo  = $pdo->prepare("SELECT name FROM ngo_master WHERE id = ?");
$stmtNgo->execute([$_SESSION['ngo_id']]);
$ngoRow   = $stmtNgo->fetch(PDO::FETCH_ASSOC);
$ngo_name = $ngoRow['name'];

// ─── Fetch beneficiary name ───────────────────────────────────────────────────
$stmtBen = $pdo->prepare("SELECT name FROM beneficiary_master WHERE id = ?");
$stmtBen->execute([$beneficiary_id]);
$benRow  = $stmtBen->fetch(PDO::FETCH_ASSOC);
$beneficiary_name = $benRow ? $benRow['name'] : 'Unknown';

$alert_message = '';
$alert_type    = '';
if (!empty($_SESSION['flash'])) {
  [$alert_type, $alert_message] = $_SESSION['flash'];
  unset($_SESSION['flash']);   // show it once
}

// ─── Handle Approve — sets status to Verified globally ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_doc'])) {
  $doc_id = (int) $_POST['approve_doc'];
  if ($doc_id > 0) {
    $stmtApprove = $pdo->prepare("UPDATE beneficiary_documents SET status = 'Verified' WHERE id = ? AND beneficiary_id = ?");
    $stmtApprove->execute([$doc_id, $beneficiary_id]);
    $_SESSION['flash'] = ['success', 'Document approved successfully.'];
}
  header("Location: ngo-beneficiaries-documents.php?beneficiary_id={$beneficiary_id}&request_id={$request_id}");
  exit;
}

// ─── Handle Reject — hides document for this NGO only (no DB status change) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_doc'])) {
  $doc_id = (int) $_POST['reject_doc'];
  if ($doc_id > 0) {
  // INSERT IGNORE so duplicate rejections by the same NGO are silently skipped
    $stmtReject = $pdo->prepare(
    "INSERT IGNORE INTO ngo_document_rejections (ngo_id, document_id) VALUES (?, ?)"
    );
    $stmtReject->execute([$_SESSION['ngo_id'], $doc_id]);
    $_SESSION['flash'] = ['success', 'Document rejected. It will no longer appear in your view.'];
}
  header("Location: ngo-beneficiaries-documents.php?beneficiary_id={$beneficiary_id}&request_id={$request_id}");
  exit;
}

// ─── Handle report submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['report']) && $_GET['report'] == '1') {
  $doc_id      = isset($_GET['doc_id']) ? (int) $_GET['doc_id'] : 0;
  $report_text = trim($_POST['report_text'] ?? '');
  $doc_name    = trim($_POST['doc_name']    ?? '');

  if ($doc_id > 0 && !empty($report_text)) {
  $stmtRep = $pdo->prepare(
    "INSERT INTO document_reports (ngo_id, ngo_name, beneficiary_name, document_name, report)
     VALUES (?, ?, ?, ?, ?)"
  );
  $stmtRep->execute([$_SESSION['ngo_id'], $ngo_name, $beneficiary_name, $doc_name, $report_text]);
  $_SESSION['flash'] = ['success', 'Report submitted successfully.'];
} else {
  $_SESSION['flash'] = ['error', 'Report text cannot be empty.'];
}
  header("Location: ngo-beneficiaries-documents.php?beneficiary_id={$beneficiary_id}&request_id={$request_id}");
  exit;
}

// ─── Fetch documents ──────────────────────────────────────────────────────────
// Exclude: (1) Verified documents (approved by any NGO)
//          (2) Documents this NGO has already rejected (via ngo_document_rejections)
$stmtDocs = $pdo->prepare(
  "SELECT bd.* FROM beneficiary_documents bd
   WHERE bd.beneficiary_id = ?
     AND bd.status != 'Verified'
     AND bd.id NOT IN (
       SELECT document_id FROM ngo_document_rejections WHERE ngo_id = ?
     )
   ORDER BY bd.uploaded_at DESC"
);
$stmtDocs->execute([$beneficiary_id, $_SESSION['ngo_id']]);
$documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Beneficiary Documents</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    .alert {
      padding: 12px 18px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-size: 0.95rem;
      font-weight: 500;
    }
    .alert-success {
      background: rgba(34, 197, 94, 0.15);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.3);
    }
    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      color: #dc2626;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .btn-view {
      display: inline-block;
      padding: 5px 14px;
      background: rgba(99, 179, 237, 0.2);
      color: #63b3ed;
      border: 1px solid rgba(99, 179, 237, 0.4);
      border-radius: 6px;
      font-size: 0.82rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-view:hover { background: rgba(99, 179, 237, 0.35); color: #fff; }

    .btn-approve {
      padding: 5px 12px;
      background: rgba(34, 197, 94, 0.15);
      color: #4ade80;
      border: 1px solid rgba(34, 197, 94, 0.35);
      border-radius: 6px;
      font-size: 0.82rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-approve:hover { background: rgba(34, 197, 94, 0.35); color: #fff; }

    .btn-reject {
      padding: 5px 12px;
      background: rgba(251, 191, 36, 0.15);
      color: #fbbf24;
      border: 1px solid rgba(251, 191, 36, 0.35);
      border-radius: 6px;
      font-size: 0.82rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-reject:hover { background: rgba(251, 191, 36, 0.35); color: #fff; }

    .btn-report {
      padding: 5px 12px;
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.35);
      border-radius: 6px;
      font-size: 0.82rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-report:hover { background: rgba(239, 68, 68, 0.35); color: #fff; }

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

    .action-group {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
    }

    .status-pending {
      display: inline-block;
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      background: rgba(251, 191, 36, 0.15);
      color: #fbbf24;
      border: 1px solid rgba(251, 191, 36, 0.3);
    }

    /* ── Modal ────────────────────────────────────────────────────────────── */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      z-index: 999;
      align-items: center;
      justify-content: center;
    }
    .modal-backdrop.active { display: flex; }

    .modal-box {
      background: #0f172a;
      border: 1px solid rgba(99, 179, 237, 0.25);
      border-radius: 12px;
      padding: 28px 32px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    .modal-box h4 {
      color: #e2e8f0;
      margin: 0 0 6px 0;
      font-size: 1.1rem;
    }
    .modal-box p.modal-sub {
      color: #94a3b8;
      font-size: 0.85rem;
      margin: 0 0 18px 0;
    }
    .modal-box textarea {
      width: 100%;
      min-height: 110px;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid rgba(99, 179, 237, 0.3);
      background: rgba(30, 41, 59, 0.8);
      color: #e2e8f0;
      font-size: 0.9rem;
      resize: vertical;
      box-sizing: border-box;
    }
    .modal-actions {
      display: flex;
      gap: 10px;
      margin-top: 16px;
      justify-content: flex-end;
    }
    .btn-modal-submit {
      padding: 8px 20px;
      background: rgba(239, 68, 68, 0.2);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.4);
      border-radius: 7px;
      font-size: 0.88rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-modal-submit:hover { background: rgba(239, 68, 68, 0.4); color: #fff; }
    .btn-modal-cancel {
      padding: 8px 20px;
      background: rgba(148, 163, 184, 0.1);
      color: #94a3b8;
      border: 1px solid rgba(148, 163, 184, 0.25);
      border-radius: 7px;
      font-size: 0.88rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-modal-cancel:hover { background: rgba(148, 163, 184, 0.25); color: #fff; }
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

  <!-- Report Modal -->
  <div class="modal-backdrop" id="reportModal">
    <div class="modal-box">
      <h4>🚩 Report Document</h4>
      <p class="modal-sub" id="modalDocLabel">Document: —</p>
      <form method="POST" id="reportForm">
        <textarea name="report_text" placeholder="Describe the issue (e.g. document appears fake, information is incorrect...)" required></textarea>
        <input type="text" name="doc_name" id="modalDocNameInput" style="display:none;">
        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-modal-submit">Submit Report</button>
        </div>
      </form>
    </div>
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
            <h3>Beneficiary Documents</h3>
          </div>
        </div>
        <div class="user-name">NGO Administrator</div>
      </div>

      <?php if ($alert_message): ?>
        <div class="alert alert-<?php echo $alert_type; ?>">
          <?php echo $alert_type === 'success' ? '✅' : '⚠️'; ?> <?php echo htmlspecialchars($alert_message); ?>
        </div>
      <?php endif; ?>

      <a href="ngo-beneficiaries-review.php" class="btn-back">← Back to Requests</a>

      <section class="documents">
        <div class="section-header">
          <h3>Documents for: <?php echo htmlspecialchars($beneficiary_name); ?></h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Document Name</th>
            <th>Uploaded At</th>
            <th>View</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
          <?php if (!empty($documents)): ?>
            <?php foreach ($documents as $doc): ?>
              <?php
                $doc_id   = (int) $doc['id'];
                $doc_name = htmlspecialchars($doc['document_name'] ?? '');
                $doc_file = htmlspecialchars($doc['file_path'] ?? '');
                $doc_date = isset($doc['uploaded_at']) ? date('M d, Y', strtotime($doc['uploaded_at'])) : '—';
                $file_url = '../../uploads/beneficiary_documents/' . $doc_file;
              ?>
              <tr>
                <td><?php echo $doc_name; ?></td>
                <td><?php echo $doc_date; ?></td>
                <td>
                  <?php if (!empty($doc_file)): ?>
                    <a href="<?php echo $file_url; ?>" target="_blank" class="btn-view">👁 View</a>
                  <?php else: ?>
                    <span style="color:#888; font-size:0.82rem;">N/A</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-pending">Pending</span>
                </td>
                <td>
                  <div class="action-group">

                    <!-- Approve — doc_id on the button itself, no hidden input -->
                    <form method="POST"
                          action="ngo-beneficiaries-documents.php?beneficiary_id=<?php echo $beneficiary_id; ?>&request_id=<?php echo $request_id; ?>"
                          style="display:inline;"
                          onsubmit="return confirm('Approve this document? It will be marked Verified for all NGOs.');">
                      <button type="submit" name="approve_doc" value="<?php echo $doc_id; ?>" class="btn-approve">✔ Approve</button>
                    </form>

                    <!-- Reject — hides document for this NGO only, no DB status change -->
                    <form method="POST"
                          action="ngo-beneficiaries-documents.php?beneficiary_id=<?php echo $beneficiary_id; ?>&request_id=<?php echo $request_id; ?>"
                          style="display:inline;"
                          onsubmit="return confirm('Reject this document? It will be hidden from your view only.');">
                      <button type="submit" name="reject_doc" value="<?php echo $doc_id; ?>" class="btn-reject">✖ Reject</button>
                    </form>

                    <!-- Report — opens modal -->
                    <button type="button" class="btn-report" data-doc-id="<?php echo $doc_id; ?>" data-doc-name="<?php echo $doc_name; ?>">
                    🚩 Report
                    </button>

                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align:center; color:#aaa; padding:20px;">
                No documents available (all may have been approved/rejected or none submitted).
              </td>
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
  <script>
    const beneficiaryId = <?php echo $beneficiary_id; ?>;
    const requestId     = <?php echo $request_id; ?>;

    function openModal(docId, docName) {
      document.getElementById('modalDocLabel').textContent = 'Document: ' + docName;
      document.getElementById('modalDocNameInput').value   = docName;
      document.getElementById('reportForm').action =
        'ngo-beneficiaries-documents.php?beneficiary_id=' + beneficiaryId +
        '&request_id=' + requestId +
        '&doc_id=' + docId +
        '&report=1';
      document.getElementById('reportModal').classList.add('active');
    }

    function closeModal() {
      document.getElementById('reportModal').classList.remove('active');
      document.getElementById('reportForm').reset();
    }

    document.querySelectorAll('.btn-report').forEach(function (btn) {
    btn.addEventListener('click', function () {
    openModal(this.dataset.docId, this.dataset.docName);
    });
    });

    document.getElementById('reportModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });
  </script>

</body>
</html>
