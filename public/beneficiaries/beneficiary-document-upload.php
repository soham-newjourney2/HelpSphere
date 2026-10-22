<?php
//beneficiary-document-upload.php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$alert_message = '';
$alert_type    = '';

// ─── Handle upload ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
  csrf_require_post();
  $doc_names  = $_POST['docname'] ?? [];   // array, one name per file row
  $files      = $_FILES['documents'];
  $file_count = count($files['name']);
  $allowed    = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
  $max_size   = 5 * 1024 * 1024; // 5MB
  $upload_dir = __DIR__ . '/../../uploads/beneficiary_documents/';

  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
  }

  $success_count = 0;
  $errors        = [];

  for ($i = 0; $i < $file_count; $i++) {
    // Skip rows where no file was chosen
    if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) 
      continue;

    $doc_name = trim($doc_names[$i] ?? '');

    if (empty($doc_name)) {
      $errors[] = "Row " . ($i + 1) . ": Document name is required.";
      continue;
    }

    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
      $errors[] = "'{$files['name'][$i]}': upload error.";
      continue;
    }

    // MIME check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $files['tmp_name'][$i]);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
      $errors[] = "'{$files['name'][$i]}': invalid file type. Only PDF, JPG, PNG allowed.";
      continue;
    }

    // Size check
    if ($files['size'][$i] > $max_size) {
      $errors[] = "'{$files['name'][$i]}': exceeds the 5MB size limit.";
      continue;
    }

    // Save file
    $ext_map     = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png'];
    $ext         = $ext_map[$mime];
    $unique_name = 'ben_' . $_SESSION['beneficiary_id'] . '_' . time() . '_' . uniqid() . '.' . $ext;
    $destination = $upload_dir . $unique_name;

    if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
      $stmt = $pdo->prepare(
        "INSERT INTO beneficiary_documents (beneficiary_id, document_name, file_path, status)
         VALUES (?, ?, ?, 'Uploaded')"
      );
      $stmt->execute([$_SESSION['beneficiary_id'], $doc_name, $unique_name]);
      $success_count++;
    } else {
      $errors[] = "'{$files['name'][$i]}': could not be saved. Check server permissions.";
    }
  }

  if ($success_count > 0 && empty($errors)) {
    $alert_message = $success_count . ' document(s) uploaded successfully!';
    $alert_type    = 'success';
  } elseif ($success_count > 0 && !empty($errors)) {
    $alert_message = $success_count . ' document(s) uploaded. ' . implode(' ', $errors);
    $alert_type    = 'warning';
  } else {
    $alert_message = implode(' ', $errors) ?: 'No files were uploaded.';
    $alert_type    = 'error';
  }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Upload documents · Beneficiary | HelpSphere</title>
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
      <a href="../beneficiaries/beneficiary-document-upload.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
      <a href="../beneficiaries/beneficiary-documents.php"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Upload documents</strong></nav>
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
          <h1 class="page-head__title">Upload supporting documents</h1>
          <p class="page-head__sub">Give each file a clear name, like “ID proof” or “Income certificate”. PDF, JPG or PNG, up to 5 MB each.</p>
        </div>
        <div class="page-head__actions">
          <a href="../beneficiaries/beneficiary-documents.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
        </div>
      </section>

        <?php if ($alert_message): ?>
          <div class="alert alert-<?php echo $alert_type; ?>">
            <?php
              $icon = match($alert_type) {
                'success' => '✅',
                'warning' => '⚠️',
                default => '❌'
              };
              echo $icon . ' ' . htmlspecialchars($alert_message);
            ?>
          </div>
        <?php endif; ?>

      <article class="card reveal">
        <form class="form" action="" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()) ?>">

          <div class="file-rows" id="fileRows">
            <div class="file-row">
              <div class="field">
                <label for="docname-0">Document name</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-file"/></svg>
                  <input type="text" id="docname-0" name="docname[]" placeholder="e.g. ID proof" required>
                </div>
              </div>
              <div class="field">
                <label for="document-0">File</label>
                <div class="field__control field__control--file">
                  <input type="file" id="document-0" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
              </div>
              <span></span>
            </div>
          </div>

          <template id="fileRowTemplate">
            <div class="file-row">
              <div class="field">
                <label>Document name</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-file"/></svg>
                  <input type="text" name="docname[]" placeholder="e.g. Income certificate" required>
                </div>
              </div>
              <div class="field">
                <label>File</label>
                <div class="field__control field__control--file">
                  <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
              </div>
              <button type="button" class="btn btn--danger btn--sm file-row__remove" data-remove-row><svg class="icon"><use href="../assets/img/icons.svg#i-x"/></svg>Remove</button>
            </div>
          </template>

          <div>
            <button type="button" class="btn btn--outline btn--sm" id="addFileRow"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>Add another document</button>
          </div>

          <div class="card__foot">
            <button type="submit" class="btn btn--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</button>
            <a href="../dashboards/beneficiary-dashboard.php" class="btn btn--outline">Cancel</a>
          </div>
        </form>
      </article>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
