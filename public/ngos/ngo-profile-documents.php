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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Compliance Documents</title>
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
      padding: 6px 16px;
      background: rgba(99, 179, 237, 0.2);
      color: #63b3ed;
      border: 1px solid rgba(99, 179, 237, 0.4);
      border-radius: 6px;
      font-size: 0.85rem;
      text-decoration: none;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .btn-view:hover {
      background: rgba(99, 179, 237, 0.35);
      color: #fff;
    }
    .btn-delete {
      display: inline-block;
      padding: 6px 16px;
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.35);
      border-radius: 6px;
      font-size: 0.85rem;
      transition: all 0.2s ease;
      cursor: pointer;
      margin-left: 6px;
    }
    .btn-delete:hover {
      background: rgba(239, 68, 68, 0.35);
      color: #fff;
    }
    .status-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .status-uploaded {
      background: rgba(251, 191, 36, 0.15);
      color: #f59e0b;
      border: 1px solid rgba(251, 191, 36, 0.3);
    }
    .status-verified {
      background: rgba(34, 197, 94, 0.15);
      color: #16a34a;
      border: 1px solid rgba(34, 197, 94, 0.3);
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

    <!-- Sidebar -->
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

          <li class="nav-item active">
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

          <li class="nav-item">
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

    <!-- Main Content -->
    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header">
            <h3>Compliance Documents</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Alerts -->
      <?php if ($upload_success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($upload_success); ?></div>
      <?php endif; ?>
      <?php if ($upload_error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($upload_error); ?></div>
      <?php endif; ?>

      <!-- Uploaded Documents -->
      <section class="documents">
        <div class="section-header">
          <h3>Submitted Documents</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Document Name</th>
            <th>Uploaded At</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
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
            <td><?php echo $docname; ?></td>
            <td><?php echo $uploaded; ?></td>
            <td><span class="status-badge <?php echo $badge_class; ?>"><?php echo $status; ?></span></td>
            <td>
              <?php if (!empty($file_path)): ?>
                <a href="<?php echo $file_url; ?>" target="_blank" class="btn-view">👁 View</a>
              <?php else: ?>
                <span style="color: #888; font-size:0.85rem;">N/A</span>
              <?php endif; ?>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this document? This cannot be undone.');"> 
                <button type="submit" name="delete_doc_id" value="<?php echo $doc_id; ?>" class="btn-delete">🗑 Delete</button> 
              </form>
            </td>
          </tr>
          <?php
            endwhile;
          else:
          ?>
          <tr>
            <td colspan="4" style="text-align:center; color:#aaa; padding: 20px;">No documents submitted yet.</td>
          </tr>
          <?php endif; ?>
        </table>
      </section>

      <!-- Upload Section -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Upload New Document</h3>
        </div>

        <!-- enctype is required for file uploads -->
        <form action="" method="POST" enctype="multipart/form-data">
          <div class="request-list">

            <div class="request-item">
              <label class="label">Document Type</label>
              <input type="text" name="document_type" placeholder="e.g. Financial Report" required
                     value="<?php echo htmlspecialchars($_POST['document_type'] ?? ''); ?>">
            </div>

            <div class="request-item">
              <label class="label">Select File <span style="font-size:0.8rem; color:#aaa;">(PDF, JPG, PNG, DOC, DOCX — max 5MB)</span></label>
              <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
            </div>

            <button type="submit">
              Upload Document
            </button>

          </div>
        </form>
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
