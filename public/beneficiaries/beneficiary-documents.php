<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();
$success = isset($_GET['success']) ? $_GET['success'] : 0;

// ─── Delete Document ──────────────────────────────────────────────────────────
if (isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    // Fetch file path — ensure it belongs to this beneficiary
    $stmtGet = $pdo->prepare("SELECT file_path FROM beneficiary_documents WHERE id = ? AND beneficiary_id = ?");
    $stmtGet->execute([$delete_id, $_SESSION['beneficiary_id']]);
    $doc = $stmtGet->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $full_path = __DIR__ . '/../../uploads/beneficiary_documents/' . $doc['file_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        $stmtDel = $pdo->prepare("DELETE FROM beneficiary_documents WHERE id = ? AND beneficiary_id = ?");
        $stmtDel->execute([$delete_id, $_SESSION['beneficiary_id']]);
    }

    header("Location: beneficiary-documents.php?deleted=1");
    exit;
}

$deleted = isset($_GET['deleted']) ? 1 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document History</title>
    <link rel="stylesheet" href="../assets/css/beneficiary.css">
    <style>
      .btn-view-doc {
        display: inline-block;
        padding: 6px 16px;
        background: rgba(99, 179, 237, 0.2);
        color: #63b3ed;
        border: 1px solid rgba(99, 179, 237, 0.4);
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .btn-view-doc:hover { background: rgba(99, 179, 237, 0.35); color: #fff; }

      .btn-delete-doc {
        display: inline-block;
        padding: 6px 16px;
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.35);
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .btn-delete-doc:hover { background: rgba(239, 68, 68, 0.35); color: #fff; }

      .doc-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        flex-wrap: wrap;
        align-items: center;
      }

      /* ── Preview Modal ───────────────────────────────────────────────────── */
      .preview-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
      }
      .preview-backdrop.active { display: flex; }

      .preview-box {
        background: #0f172a;
        border: 1px solid rgba(99, 179, 237, 0.25);
        border-radius: 12px;
        padding: 20px 24px;
        width: 100%;
        max-width: 860px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 24px 64px rgba(0,0,0,0.6);
      }

      .preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-shrink: 0;
      }
      .preview-header h4 {
        color: #e2e8f0;
        margin: 0;
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 72%;
      }
      .preview-header-actions { display: flex; gap: 10px; align-items: center; }

      .btn-open-tab {
        padding: 5px 14px;
        background: rgba(99, 179, 237, 0.15);
        color: #63b3ed;
        border: 1px solid rgba(99, 179, 237, 0.35);
        border-radius: 6px;
        font-size: 0.82rem;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s ease;
      }
      .btn-open-tab:hover { background: rgba(99, 179, 237, 0.3); color: #fff; }

      .btn-close-preview {
        padding: 5px 12px;
        background: rgba(148, 163, 184, 0.1);
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .btn-close-preview:hover { background: rgba(148, 163, 184, 0.25); color: #fff; }

      .preview-body {
        flex: 1;
        overflow: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.5);
      }
      .preview-body img {
        max-width: 100%;
        max-height: 70vh;
        border-radius: 6px;
        object-fit: contain;
      }
      .preview-body iframe {
        width: 100%;
        height: 70vh;
        border: none;
        border-radius: 6px;
      }
      .preview-unsupported {
        text-align: center;
        color: #94a3b8;
        padding: 40px 20px;
      }
      .preview-unsupported .file-icon { font-size: 3rem; margin-bottom: 12px; }
      .preview-unsupported p { margin: 6px 0; font-size: 0.9rem; }
    </style>
</head>
<body>

<div id="overlay" class="overlay"></div>

<!-- Document Preview Modal -->
<div class="preview-backdrop" id="previewModal">
  <div class="preview-box">
    <div class="preview-header">
      <h4 id="previewTitle">Document</h4>
      <div class="preview-header-actions">
        <a id="previewOpenTab" href="#" target="_blank" class="btn-open-tab">↗ Open in new tab</a>
        <button class="btn-close-preview" onclick="closePreview()">✕ Close</button>
      </div>
    </div>
    <div class="preview-body" id="previewBody"></div>
  </div>
</div>

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
                    <a href="../beneficiaries/beneficiary-document-upload.php" class="nav-item">
                        <span class="icon">📁</span>
                        <span class="label">Upload Documents</span>
                    </a>
                </li>
                <li>
                    <a href="../beneficiaries/beneficiary-documents.php" class="nav-item active">
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
                <div class="user-name">Document History</div>
            </div>
        </header>

        <section class="active-requests">
            <div class="section-header">
                <h3>Previously Uploaded Documents</h3>
            </div>

            <div class="request-list">

            <?php 
            $stmt = $pdo->prepare("SELECT * FROM beneficiary_documents WHERE beneficiary_id = ? ORDER BY uploaded_at DESC");
            $stmt->bindParam(1, $_SESSION['beneficiary_id']);
            $stmt->execute();
            if ($stmt->rowCount() > 0):
                while ($record = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $doc_id      = (int) $record['id'];
                    $name        = htmlspecialchars($record['document_name']);
                    $uploaded_at = $record['uploaded_at'];
                    $status      = htmlspecialchars($record['status']);
                    $file_path   = htmlspecialchars($record['file_path'] ?? '');
                    $file_url    = '../../uploads/beneficiary_documents/' . $file_path;
                    $ext         = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            ?>
                <div class="document-card">
                  <strong>Document: <?php echo $name ?></strong>
                  <p><strong>Uploaded:</strong> <?php echo $uploaded_at ?></p>
                  <div class="status review"><?php echo $status ?></div>

                  <div class="doc-actions">
                    <?php if (!empty($file_path)): ?>
                      <button class="btn-view-doc"
                              onclick="openPreview(
                                '<?php echo addslashes($name); ?>',
                                '<?php echo addslashes($file_url); ?>',
                                '<?php echo $ext; ?>'
                              )">
                        👁 View
                      </button>
                    <?php endif; ?>

                    <?php if($status!="Verified"): ?>
                    <form method="POST" action="" style="margin:0;"
                          onsubmit="return confirm('Delete \'<?php echo addslashes($name); ?>\'? This cannot be undone.');">
                      <input type="hidden" name="delete_id" value="<?php echo $doc_id; ?>">
                      <button type="submit" class="btn-delete-doc">🗑 Delete</button>
                    </form>
                    <?php endif ?>
                  </div>
                </div>
            <?php
                endwhile;
            else: ?>
                <p style="color:#64748b; padding: 16px;">No documents uploaded yet.</p>
            <?php endif; ?>

            </div>
        </section>

        <footer class="footer">
            © 2026 HelpSphere · Beneficiary Portal
        </footer>

    </main>

</div>

<?php if ($success == 1): ?>
    <script>alert("Document Uploaded Successfully");</script>
<?php endif; ?>
<?php if ($deleted == 1): ?>
    <script>alert("Document deleted successfully.");</script>
<?php endif; ?>

<script src="../assets/js/beneficiary-dashboard.js"></script>
<script>
  const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
  const pdfExts   = ['pdf'];

  function openPreview(docName, fileUrl, ext) {
    document.getElementById('previewTitle').textContent = docName;
    document.getElementById('previewOpenTab').href      = fileUrl;

    const body = document.getElementById('previewBody');
    body.innerHTML = '';

    if (imageExts.includes(ext)) {
      const img = document.createElement('img');
      img.src = fileUrl;
      img.alt = docName;
      body.appendChild(img);

    } else if (pdfExts.includes(ext)) {
      const iframe = document.createElement('iframe');
      iframe.src = fileUrl;
      body.appendChild(iframe);

    } else {
      body.innerHTML = `
        <div class="preview-unsupported">
          <div class="file-icon">📄</div>
          <p><strong>${docName}</strong></p>
          <p>This file type cannot be previewed in the browser.</p>
          <a href="${fileUrl}" download class="btn-open-tab" style="display:inline-block; margin-top:12px;">
            ⬇ Download File
          </a>
        </div>`;
    }

    document.getElementById('previewModal').classList.add('active');
  }

  function closePreview() {
    document.getElementById('previewModal').classList.remove('active');
    document.getElementById('previewBody').innerHTML = '';
  }

  document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) closePreview();
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePreview();
  });
</script>

</body>
</html>