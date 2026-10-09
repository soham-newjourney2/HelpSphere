<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$alert_message = '';
$alert_type    = '';

// ─── Handle upload ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
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
    $ext         = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents</title>
    <link rel="stylesheet" href="../assets/css/beneficiary.css">
    <style>
      .alert {
        padding: 12px 18px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 0.95rem;
        font-weight: 500;
      }
      .alert-success { background: rgba(34,197,94,0.15); color: #16a34a; border: 1px solid rgba(34,197,94,0.3); }
      .alert-warning  { background: rgba(251,191,36,0.15); color: #d97706; border: 1px solid rgba(251,191,36,0.3); }
      .alert-error   { background: rgba(239,68,68,0.15);  color: #dc2626; border: 1px solid rgba(239,68,68,0.3); }

      /* File row layout */
      .file-row {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        margin-bottom: 14px;
        flex-wrap: wrap;
      }
      .file-row .field-docname {
        flex: 1 1 200px;
        min-width: 160px;
      }
      .file-row .field-file {
        flex: 2 1 240px;
        min-width: 180px;
      }
      .file-row label {
        display: block;
        font-size: 0.85rem;
        margin-bottom: 5px;
        opacity: 0.8;
      }
      .file-row input[type="text"],
      .file-row input[type="file"] {
        width: 100%;
        box-sizing: border-box;
      }
      .btn-remove-row {
        padding: 8px 12px;
        background: rgba(239,68,68,0.15);
        color: #f87171;
        border: 1px solid rgba(239,68,68,0.35);
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s ease;
        align-self: flex-end;
        margin-bottom: 1px;
      }
      .btn-remove-row:hover { background: rgba(239,68,68,0.35); color: #fff; }

      .btn-add-row {
        display: inline-block;
        margin-bottom: 18px;
        padding: 8px 18px;
        background: rgba(99,179,237,0.15);
        color: #63b3ed;
        border: 1px solid rgba(99,179,237,0.35);
        border-radius: 6px;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .btn-add-row:hover { background: rgba(99,179,237,0.3); color: #fff; }

      #file-rows-container { margin-bottom: 4px; }
    </style>
</head>
<body>

<div id="overlay" class="overlay"></div>

<div class="app">

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">HelpSphere</div>
            <button id="sidebarClose" class="sidebar-close">✕</button>
        </div>

        <p class="tagline">Support that reaches you</p>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../dashboards/beneficiary-dashboard.php" class="nav-item">
                        <span class="icon">🏠</span>
                        <span class="label">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../beneficiaries/beneficiary-request-management.php" class="nav-item">
                        <span class="icon">📄</span>
                        <span class="label">Requests</span>
                    </a>
                </li>
                <li>
                    <a href="../beneficiaries/beneficiary-document-upload.php" class="nav-item active">
                        <span class="icon">📁</span>
                        <span class="label">Upload Documents</span>
                    </a>
                </li>
                <li>
                    <a href="../beneficiaries/beneficiary-documents.php" class="nav-item">
                        <span class="icon">📁</span>
                        <span class="label">Documents</span>
                    </a>
                </li>
                <li>
                    <a href="../beneficiaries/beneficiary-messages.php" class="nav-item">
                        <span class="icon">💬</span>
                        <span class="label">Messages</span>
                    </a>
                </li>
                <li>
                    <a href="../beneficiaries/beneficiary-profile.php" class="nav-item">
                        <span class="icon">👤</span>
                        <span class="label">Profile</span>
                    </a>
                </li>
                <li>
                    <a href="../../inc/logout.php" class="nav-item logout">
                        <span class="icon">🚪</span>
                        <span class="label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <button id="menuToggle" class="menu-toggle">☰</button>
                <div class="user-name">Upload Documents</div>
            </div>
        </header>

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

        <section class="active-requests">
            <div class="section-header">
                <h3>Upload Supporting Documents</h3>
            </div>

            <div class="request-item">
                <form class="request-form" action="" method="POST" enctype="multipart/form-data">

                    <!-- Dynamic file rows — each row = 1 document name + 1 file -->
                    <div id="file-rows-container">
                      <!-- First row (always present, cannot be removed) -->
                      <div class="file-row" id="row-0">
                        <div class="field-docname">
                          <label>Document Name</label>
                          <input type="text" name="docname[]" placeholder="e.g. ID Proof" required>
                        </div>
                        <div class="field-file">
                          <label>File <span style="opacity:0.6; font-size:0.78rem;">(PDF, JPG, PNG · max 5MB)</span></label>
                          <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <!-- No remove button on the first row -->
                      </div>
                    </div>

                    <button type="button" class="btn-add-row" onclick="addRow()">+ Add Another Document</button>

                    <div class="form-info">
                        Each file can have its own name. Accepted formats: PDF, JPG, PNG (Max 5MB per file).
                    </div>

                    <div class="action-row">
                        <button type="submit" class="action-btn">Upload Documents</button>
                        <a href="../dashboards/beneficiary-dashboard.php" class="action-btn secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </section>

        <footer class="footer">
            © 2026 HelpSphere · Beneficiary Portal
        </footer>

    </main>

</div>

<script src="../assets/js/beneficiary-dashboard.js"></script>
<script>
  let rowCount = 1;

  function addRow() {
    const container = document.getElementById('file-rows-container');
    const id = rowCount++;

    const row = document.createElement('div');
    row.className = 'file-row';
    row.id = 'row-' + id;

    row.innerHTML = `
      <div class="field-docname">
        <label>Document Name</label>
        <input type="text" name="docname[]" placeholder="e.g. Income Certificate" required>
      </div>
      <div class="field-file">
        <label>File <span style="opacity:0.6; font-size:0.78rem;">(PDF, JPG, PNG · max 5MB)</span></label>
        <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" required>
      </div>
      <button type="button" class="btn-remove-row" onclick="removeRow('row-${id}')">✕ Remove</button>
    `;

    container.appendChild(row);
  }

  function removeRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) row.remove();
  }
</script>

</body>
</html>