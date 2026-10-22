<?php
//ngo-profile-documents.php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();

require_once __DIR__.'/../../inc/serve-upload.php';
if(isset($_GET['doc']))
{
    $stmt=$pdo->prepare("SELECT file_path FROM ngo_documents WHERE id=? AND ngo_id=?");
    $stmt->execute([(int)$_GET['doc'], (int)$_SESSION['ngo_id']]);
    serve_upload(__DIR__.'/../../uploads/ngo_documents', (string)$stmt->fetchColumn());
}

$upload_error = '';
$upload_success = '';

// Every POST on this page (upload, delete) needs the session token
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_require_post();

// Handle document deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc_id'])) {
  $delete_id = (int) $_POST['delete_doc_id'];

  // Fetch the file path first so we can delete the physical file
  $stmt = $pdo->prepare("SELECT file_path FROM ngo_documents WHERE id = ? AND ngo_id = ?");
  $stmt->execute([$delete_id, $_SESSION['ngo_id']]);
  $doc = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($doc) {
    $physical_path = __DIR__ . '/../../' . $doc['file_path'];
    if (file_exists($physical_path)) {
      unlink($physical_path);
    }
    $stmt = $pdo->prepare("DELETE FROM ngo_documents WHERE id = ? AND ngo_id = ?");
    $stmt->execute([$delete_id, $_SESSION['ngo_id']]);
    $upload_success = 'Document deleted successfully.';
  }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
  $document_name = trim($_POST['document_type'] ?? '');
  $file = $_FILES['document_file'];

  if (empty($document_name)) {
    $upload_error = 'Please enter a document type/name.';
  } elseif ($file['error'] !== UPLOAD_ERR_OK) {
    $upload_error = 'File upload failed. Please try again.';
  } else {
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg',
                      'application/msword',
                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
      $upload_error = 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX.';
    } else {
      $max_size = 5 * 1024 * 1024; // 5MB
      if ($file['size'] > $max_size) {
        $upload_error = 'File size exceeds the 5MB limit.';
      } else {
        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/../../uploads/ngo_documents/';
        if (!is_dir($upload_dir)) {
          mkdir($upload_dir, 0755, true);
        }

        $ext_map=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','application/msword'=>'doc','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx'];
        $ext=$ext_map[$mime];
        $unique_name = 'ngo_' . $_SESSION['ngo_id'] . '_' . time() . '_' . uniqid() . '.' . $ext;
        $destination = $upload_dir . $unique_name;
        $file_path = 'uploads/ngo_documents/' . $unique_name;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
          $stmt = $pdo->prepare("INSERT INTO ngo_documents (ngo_id, document_name, file_path, status) VALUES (?, ?, ?, 'Uploaded')");
          $stmt->execute([$_SESSION['ngo_id'], $document_name, $file_path]);
          $upload_success = 'Document uploaded successfully!';
        } else {
          $upload_error = 'Failed to save the file. Please check server permissions.';
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Compliance documents · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-beneficiaries.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
      <a href="../ngos/ngo-communication.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Communication</a>
      <a href="../ngos/ngo-profile.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Profile &amp; compliance</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-profile.php">Profile &amp; compliance</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Compliance documents</strong></nav>
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
          <p class="page-head__eyebrow">Profile &amp; compliance</p>
          <h1 class="page-head__title">Compliance documents</h1>
          <p class="page-head__sub">Upload your registration certificate, financial reports and other documents for admin review.</p>
        </div>
        <div class="page-head__actions">
          <a href="../ngos/ngo-profile-verification.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Verification status</a>
        </div>
      </section>

      <?php if ($upload_success): ?>
      <div class="alert alert-success"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg><span><?php echo htmlspecialchars($upload_success); ?></span></div>
      <?php endif; ?>
      <?php if ($upload_error): ?>
      <div class="alert alert-error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo htmlspecialchars($upload_error); ?></span></div>
      <?php endif; ?>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Submitted documents</h2>
              <p class="card__sub">Newest first</p>
            </div>
          </header>
          <div class="table-wrap">
            <table class="table table--stack" id="docsTable">
              <thead>
                <tr><th>Document</th><th>Uploaded</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
          <?php
          $stmt = $pdo->prepare("SELECT * FROM ngo_documents WHERE ngo_id = ? ORDER BY uploaded_at DESC");
          $stmt->execute([$_SESSION['ngo_id']]);
          if ($stmt->rowCount() > 0):
            while ($res = $stmt->fetch(PDO::FETCH_ASSOC)):
              $doc_id     = (int) $res['id'];
              $docname    = htmlspecialchars($res['document_name'] ?? '');
              $status     = htmlspecialchars($res['status'] ?? '');
              $uploaded   = isset($res['uploaded_at']) ? date('M d, Y', strtotime($res['uploaded_at'])) : '—';
              $file_path  = htmlspecialchars($res['file_path'] ?? '');
              // Build URL relative to web root — adjust base path to match your project structure
              $file_url = 'ngo-profile-documents.php?doc=' . $doc_id;
              $badge_class = strtolower($status) === 'verified' ? 'status-verified' : 'status-uploaded';
          ?>
                <tr>
                  <td><div class="who"><span class="doc-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg></span><strong><?php echo $docname; ?></strong></div></td>
                  <td class="date-cell"><?php echo e($uploaded); ?></td>
                  <td><span class="status <?php echo e($badge_class); ?>" data-status="<?php echo $status; ?>"><?php echo $status; ?></span></td>
                  <td>
                    <div class="actions">
              <?php if (!empty($file_path)): ?>
                      <a href="<?php echo e($file_url); ?>" target="_blank" rel="noopener" class="act act--plain"><svg class="icon"><use href="../assets/img/icons.svg#i-eye"/></svg>View</a>
              <?php else: ?>
                      <span class="muted">N/A</span>
              <?php endif; ?>
                      <form method="POST" data-confirm="Delete this document? This cannot be undone.">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                        <button type="submit" name="delete_doc_id" value="<?php echo $doc_id; ?>" class="act act--no"><svg class="icon"><use href="../assets/img/icons.svg#i-trash"/></svg>Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
          <?php
            endwhile;
          else:
          ?>
          <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="empty" data-empty-for="#docsTable">
            <svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>
            <strong>No documents submitted yet</strong>
            <p>Upload your first compliance document using the form.</p>
          </div>
        </article>

        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Upload a document</h2>
              <p class="card__sub">PDF, JPG, PNG, DOC or DOCX — max 5 MB</p>
            </div>
          </header>
          <form class="form" action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <div class="field">
              <label for="document_type">Document type</label>
              <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-file"/></svg><input type="text" id="document_type" name="document_type" placeholder="e.g. Registration certificate" required
                     value="<?php echo htmlspecialchars($_POST['document_type'] ?? ''); ?>"></div>
            </div>
            <div class="field">
              <label for="document_file">File</label>
              <div class="field__control field__control--file"><input type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required></div>
            </div>
            <button type="submit" class="btn btn--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload document</button>
          </form>
        </article>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
