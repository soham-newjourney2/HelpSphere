<?php
require_once __DIR__ . '/../../inc/auth.php';
require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$UPLOAD_DIR = realpath(__DIR__ . '/../../uploads/beneficiary_documents');
$beneficiary_id = (int)($_SESSION['beneficiary_id'] ?? 0);

// ─── Secure document viewer: ?doc=ID (files are served only to their owner) ───
if (isset($_GET['doc'])) {
    $stmt = $pdo->prepare("SELECT file_path FROM beneficiary_documents WHERE id = ? AND beneficiary_id = ?");
    $stmt->execute([(int)$_GET['doc'], $beneficiary_id]);
    $stored = (string)$stmt->fetchColumn();

    $path = false;
    if ($UPLOAD_DIR && $stored !== '') {
        $try = realpath($UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($stored));
        if ($try && strpos($try, $UPLOAD_DIR . DIRECTORY_SEPARATOR) === 0 && is_file($try)) $path = $try;
    }
    if (!$path) { http_response_code(404); exit('Document file not found.'); }

    while (ob_get_level() > 0) ob_end_clean();   // stray output would corrupt the file
    @ini_set('zlib.output_compression', 'Off');
    session_write_close();

    $mime   = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    $inline = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode(basename($path)) . '"');
    readfile($path);
    exit;
}

$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;

// ─── Delete Document ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die("Invalid session token. Please go back and try again.");
    }

    $delete_id = (int)$_POST['delete_id'];
    $result = 'error';

    // Only the owner's documents, and never a Verified one
    $stmtGet = $pdo->prepare("SELECT file_path FROM beneficiary_documents WHERE id = ? AND beneficiary_id = ? AND status <> 'Verified'");
    $stmtGet->execute([$delete_id, $beneficiary_id]);
    $doc = $stmtGet->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $stmtDel = $pdo->prepare("DELETE FROM beneficiary_documents WHERE id = ? AND beneficiary_id = ? AND status <> 'Verified'");
        $stmtDel->execute([$delete_id, $beneficiary_id]);

        if ($stmtDel->rowCount() > 0) {
            $result = 'deleted';
            // Remove the file only after the row is gone, and only from inside the upload folder
            if ($UPLOAD_DIR && (string)$doc['file_path'] !== '') {
                $full = realpath($UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($doc['file_path']));
                if ($full && strpos($full, $UPLOAD_DIR . DIRECTORY_SEPARATOR) === 0 && is_file($full)) {
                    unlink($full);
                }
            }
        }
    }

    header("Location: beneficiary-documents.php?" . ($result === 'deleted' ? 'deleted=1' : 'error=1'));
    exit;
}

$deleted = isset($_GET['deleted']) ? 1 : 0;
$error   = isset($_GET['error']) ? 1 : 0;
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My documents · Beneficiary | HelpSphere</title>
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

    <span class="sidebar__tag">Beneficiary space</span>

    <nav class="sidebar__nav" aria-label="Beneficiary space">
      <a href="../dashboards/beneficiary-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../beneficiaries/beneficiary-request-management.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>My requests</a>
      <a href="../beneficiaries/beneficiary-document-upload.php"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
      <a href="../beneficiaries/beneficiary-documents.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
      <a href="../beneficiaries/beneficiary-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>My documents</strong></nav>
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
          <p class="page-head__eyebrow">Documents</p>
          <h1 class="page-head__title">Your uploaded documents</h1>
          <p class="page-head__sub">NGOs review these before approving help. Verified documents are locked and can no longer be deleted.</p>
        </div>
        <div class="page-head__actions">
          <a href="../beneficiaries/beneficiary-document-upload.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload more</a>
        </div>
      </section>

      <div class="cards-grid">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM beneficiary_documents WHERE beneficiary_id = ? ORDER BY uploaded_at DESC");
            $stmt->execute([$beneficiary_id]);
            if ($stmt->rowCount() > 0):
                while ($record = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $doc_id     = (int)$record['id'];
                    $name_raw   = (string)$record['document_name'];
                    $status_raw = (string)$record['status'];
                    $file_raw   = (string)($record['file_path'] ?? '');
                    $file_url   = 'beneficiary-documents.php?doc=' . $doc_id;
                    $ext        = strtolower(pathinfo($file_raw, PATHINFO_EXTENSION));
            ?>
        <article class="mini-card">
          <div class="mini-card__top">
            <span class="doc-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg></span>
            <span class="status" data-status="<?php echo h($status_raw) ?>"><?php echo h($status_raw) ?></span>
          </div>
          <h3><?php echo h($name_raw) ?></h3>
          <div class="mini-card__meta"><span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg>Uploaded <?php echo h($record['uploaded_at']) ?></span></div>
          <div class="mini-card__foot">
                    <?php if ($file_raw !== ''): ?>
            <button type="button" class="btn btn--outline btn--xs" data-modal-open="#previewModal"
                    data-preview-name="<?php echo h($name_raw) ?>"
                    data-preview-url="<?php echo h($file_url) ?>"
                    data-preview-ext="<?php echo h($ext) ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-eye"/></svg>View</button>
                    <?php endif; ?>
                    <?php if ($status_raw !== 'Verified'): ?>
            <form method="POST" action="" data-confirm="Delete “<?php echo h($name_raw) ?>”? This cannot be undone.">
              <input type="hidden" name="delete_id" value="<?php echo $doc_id; ?>">
              <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']) ?>" />
              <button type="submit" class="btn btn--danger btn--xs"><svg class="icon"><use href="../assets/img/icons.svg#i-trash"/></svg>Delete</button>
            </form>
                    <?php endif; ?>
          </div>
        </article>
            <?php
                endwhile;
            else: ?>
        <div class="card empty">
          <svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>
          <strong>No documents uploaded yet</strong>
          <p>Upload your ID and supporting documents so NGOs can verify your requests.</p>
          <a href="../beneficiaries/beneficiary-document-upload.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
        </div>
            <?php endif; ?>
      </div>

      <!-- Document preview -->
      <div class="modal" id="previewModal" hidden role="dialog" aria-modal="true" aria-labelledby="previewTitle">
        <div class="modal__box modal__box--wide">
          <div class="modal__head">
            <div>
              <h2 id="previewTitle">Document</h2>
            </div>
            <div class="actions">
              <a id="previewOpen" href="#" target="_blank" rel="noopener" class="btn btn--outline btn--xs"><svg class="icon"><use href="../assets/img/icons.svg#i-external"/></svg>Open in new tab</a>
              <button type="button" class="btn btn--dark btn--xs" data-modal-close><svg class="icon"><use href="../assets/img/icons.svg#i-x"/></svg>Close</button>
            </div>
          </div>
          <div class="preview" id="previewBody"></div>
        </div>
      </div>

<?php if ($success === 1): ?>
      <div data-toast="Document uploaded successfully"></div>
<?php endif; ?>
<?php if ($deleted === 1): ?>
      <div data-toast="Document deleted"></div>
<?php endif; ?>
<?php if ($error === 1): ?>
      <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span>This document could not be deleted. Verified documents cannot be deleted.</span></p>
<?php endif; ?>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
