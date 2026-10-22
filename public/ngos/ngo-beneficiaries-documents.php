<?php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();

// ─── Fetch context from URL ───────────────────────────────────────────────────
$beneficiary_id = isset($_GET['beneficiary_id']) ? (int) $_GET['beneficiary_id'] : 0;
$request_id     = isset($_GET['request_id'])     ? (int) $_GET['request_id']     : 0;

require_once __DIR__.'/../../inc/serve-upload.php';
if(isset($_GET['doc']))
{
    $stmt=$pdo->prepare("SELECT file_path FROM beneficiary_documents WHERE id=? AND beneficiary_id=?");
    $stmt->execute([(int)$_GET['doc'], $beneficiary_id]);
    serve_upload(__DIR__.'/../../uploads/beneficiary_documents', (string)$stmt->fetchColumn());
}

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
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Beneficiary documents · NGO | HelpSphere</title>
  <meta name="hs-icons" content="../assets/img/icons.svg" />
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
      <a href="../dashboards/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../ngos/ngo-needs.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>Needs &amp; requests</a>
      <a href="../ngos/ngo-beneficiaries.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
      <a href="../ngos/ngo-communication.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Communication</a>
      <a href="../ngos/ngo-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Profile &amp; compliance</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-beneficiaries-review.php">Review requests</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Documents</strong></nav>
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
          <p class="page-head__eyebrow">Beneficiary documents</p>
          <h1 class="page-head__title"><?php echo htmlspecialchars($beneficiary_name); ?></h1>
          <p class="page-head__sub">Approving marks a document Verified for every NGO. Rejecting only hides it from your view. Report anything that looks wrong.</p>
        </div>
        <div class="page-head__actions">
          <a href="ngo-beneficiaries-review.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-arrow-left"/></svg>Back to requests</a>
        </div>
      </section>

      <?php if ($alert_message): ?>
      <div class="alert alert-<?php echo $alert_type; ?>">
        <svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg><span><?php echo htmlspecialchars($alert_message); ?></span>
      </div>
      <?php endif; ?>

      <!-- Report modal -->
      <div class="modal" id="reportModal" hidden role="dialog" aria-modal="true" aria-labelledby="reportTitle">
        <div class="modal__box">
          <div class="modal__head">
            <div>
              <h2 id="reportTitle">Report document</h2>
              <p>Document: <span data-fill="doc_name">—</span></p>
            </div>
            <button type="button" class="icon-btn" data-modal-close aria-label="Close"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></button>
          </div>
          <form method="POST" id="reportForm" class="form">
            <div class="field">
              <label for="report_text">What is the issue?</label>
              <div class="field__control field__control--area">
                <textarea id="report_text" name="report_text" placeholder="e.g. the document appears altered, or the details don’t match the request" required></textarea>
              </div>
            </div>
            <input type="hidden" name="doc_name">
            <div class="modal__actions">
              <button type="button" class="btn btn--outline btn--sm" data-modal-close>Cancel</button>
              <button type="submit" class="btn btn--danger btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-flag"/></svg>Submit report</button>
            </div>
          </form>
        </div>
      </div>

      <article class="card reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Documents awaiting review</h2>
            <p class="card__sub">Verified documents and ones you rejected are not shown</p>
          </div>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="docTable">
            <thead>
              <tr><th>Document</th><th>Uploaded</th><th>File</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
          <?php if (!empty($documents)): ?>
            <?php foreach ($documents as $doc): ?>
              <?php
                $doc_id   = (int) $doc['id'];
                $doc_name = htmlspecialchars($doc['document_name'] ?? '');
                $doc_file = htmlspecialchars($doc['file_path'] ?? '');
                $doc_date = isset($doc['uploaded_at']) ? date('M d, Y', strtotime($doc['uploaded_at'])) : '—';
                $file_url = 'ngo-beneficiaries-documents.php?doc='.$doc_id.'&beneficiary_id='.$beneficiary_id;
              ?>
              <tr>
                <td><div class="who"><span class="doc-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg></span><strong><?php echo $doc_name; ?></strong></div></td>
                <td class="date-cell"><?php echo $doc_date; ?></td>
                <td>
                  <?php if (!empty($doc_file)): ?>
                    <a href="<?php echo $file_url; ?>" target="_blank" rel="noopener" class="act act--plain"><svg class="icon"><use href="../assets/img/icons.svg#i-eye"/></svg>View</a>
                  <?php else: ?>
                    <span class="muted">N/A</span>
                  <?php endif; ?>
                </td>
                <td><span class="status" data-status="Pending">Pending</span></td>
                <td>
                  <div class="actions">
                    <form method="POST"
                          action="ngo-beneficiaries-documents.php?beneficiary_id=<?php echo $beneficiary_id; ?>&request_id=<?php echo $request_id; ?>"
                          data-confirm="Approve this document? It will be marked Verified for all NGOs.">
                      <button type="submit" name="approve_doc" value="<?php echo $doc_id; ?>" class="act act--yes"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Approve</button>
                    </form>
                    <form method="POST"
                          action="ngo-beneficiaries-documents.php?beneficiary_id=<?php echo $beneficiary_id; ?>&request_id=<?php echo $request_id; ?>"
                          data-confirm="Reject this document? It will be hidden from your view only.">
                      <button type="submit" name="reject_doc" value="<?php echo $doc_id; ?>" class="act act--no"><svg class="icon"><use href="../assets/img/icons.svg#i-x"/></svg>Reject</button>
                    </form>
                    <button type="button" class="act act--warn" data-modal-open="#reportModal" data-doc-id="<?php echo $doc_id; ?>" data-fill-doc_name="<?php echo $doc_name; ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-flag"/></svg>Report</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#docTable">
          <svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>
          <strong>No documents to review</strong>
          <p>All of this beneficiary’s documents have been verified or rejected, or none were uploaded.</p>
        </div>
      </article>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
