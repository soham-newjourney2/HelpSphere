<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();
$donor_id = (int) $_SESSION['donor_id'];

// ─── Handle Volunteer Application ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_id'])) {
    $campaign_id = (int) $_POST['campaign_id'];
    $skills      = trim($_POST['skills'] ?? '');

    if ($campaign_id > 0) {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO campaign_volunteers (campaign_id, donor_id, skills, status)
             VALUES (?, ?, ?, 'Pending')"
        );
        $stmt->execute([$campaign_id, $donor_id, $skills]);
    }
    header("Location: donor-volunteer-registration.php");
    exit;
}

// ─── Fetch all ongoing campaigns ─────────────────────────────────────────────
$stmtC = $pdo->prepare(
    "SELECT c.id, c.title, c.description, c.st_date, c.end_date, n.name AS ngo_name
     FROM campaigns c
     JOIN ngo_master n ON n.id = c.ngo_id
     WHERE c.status = 'Ongoing'
     ORDER BY c.st_date ASC"
);
$stmtC->execute();
$campaigns = $stmtC->fetchAll(PDO::FETCH_ASSOC);

// ─── Fetch campaigns this donor already applied for ───────────────────────────
$stmtA = $pdo->prepare("SELECT campaign_id, status FROM campaign_volunteers WHERE donor_id = ?");
$stmtA->execute([$donor_id]);
$applied = [];
foreach ($stmtA->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $applied[$row['campaign_id']] = $row['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Registration</title>
    <link rel="stylesheet" href="../assets/css/donor.css">
    <style>
        /* ── Campaign Cards ── */
        .campaign-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 10px;
        }
        .campaign-card {
            background: rgba(30,41,59,0.7);
            border: 1px solid rgba(99,179,237,0.15);
            border-radius: 12px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            transition: border-color 0.2s;
        }
        .campaign-card:hover { border-color: rgba(99,179,237,0.35); }
        .campaign-info { flex: 1; min-width: 0; }
        .campaign-title {
            font-size: 0.97rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 4px;
        }
        .campaign-meta {
            font-size: 0.8rem;
            color: #64748b;
        }
        .campaign-meta span { margin-right: 14px; }

        .btn-volunteer {
            padding: 8px 20px;
            background: rgba(99,179,237,0.2);
            color: #63b3ed;
            border: 1px solid rgba(99,179,237,0.4);
            border-radius: 8px;
            font-size: 0.87rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .btn-volunteer:hover { background: rgba(99,179,237,0.35); color: #fff; }

        .applied-badge {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            flex-shrink: 0;
        }
        .badge-applied {
            padding: 6px 16px;
            background: rgba(74,222,128,0.12);
            color: #4ade80;
            border: 1px solid rgba(74,222,128,0.3);
            border-radius: 8px;
            font-size: 0.87rem;
            font-weight: 600;
        }
        .badge-status {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .badge-status .s-pending  { color: #fbbf24; }
        .badge-status .s-approved { color: #4ade80; }
        .badge-status .s-rejected { color: #f87171; }

        .no-campaigns {
            text-align: center;
            color: #475569;
            padding: 40px 20px;
            font-size: 0.9rem;
        }

        /* ── Modal ── */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-backdrop.active { display: flex; }

        .modal-box {
            background: #0f172a;
            border: 1px solid rgba(99,179,237,0.25);
            border-radius: 14px;
            padding: 28px 30px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.6);
        }
        .modal-box h3 {
            margin: 0 0 20px;
            font-size: 1.1rem;
            color: #e2e8f0;
        }
        .modal-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 20px;
            margin-bottom: 20px;
        }
        .modal-detail-grid .full { grid-column: 1 / -1; }
        .modal-detail-grid p {
            margin: 0;
            font-size: 0.85rem;
            color: #94a3b8;
        }
        .modal-detail-grid p strong { color: #cbd5e1; display: block; margin-bottom: 2px; }

        .modal-divider {
            border: none;
            border-top: 1px solid rgba(99,179,237,0.1);
            margin: 18px 0;
        }
        .modal-box label {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .modal-box input[type="text"] {
            width: 100%;
            padding: 10px 13px;
            border-radius: 8px;
            border: 1px solid rgba(99,179,237,0.25);
            background: rgba(30,41,59,0.8);
            color: #e2e8f0;
            font-size: 0.87rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .modal-box input[type="text"]:focus { outline: none; border-color: rgba(99,179,237,0.5); }
        .modal-box input[type="text"]::placeholder { color: #475569; }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        .btn-modal-cancel {
            padding: 9px 20px;
            background: rgba(148,163,184,0.1);
            color: #94a3b8;
            border: 1px solid rgba(148,163,184,0.25);
            border-radius: 8px;
            font-size: 0.87rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-modal-cancel:hover { background: rgba(148,163,184,0.2); color: #fff; }
        .btn-modal-submit {
            padding: 9px 24px;
            background: rgba(99,179,237,0.2);
            color: #63b3ed;
            border: 1px solid rgba(99,179,237,0.4);
            border-radius: 8px;
            font-size: 0.87rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-modal-submit:hover { background: rgba(99,179,237,0.35); color: #fff; }
    </style>
</head>
<body>

<!-- ── Volunteer Modal ── -->
<div class="modal-backdrop" id="volunteerModal">
    <div class="modal-box">
        <h3 id="modalCampaignName">Campaign Name</h3>

        <div class="modal-detail-grid">
            <p class="full">
                <strong>Description</strong>
                <span id="modalDescription">—</span>
            </p>
            <p>
                <strong>Start Date</strong>
                <span id="modalStartDate">—</span>
            </p>
            <p>
                <strong>End Date</strong>
                <span id="modalEndDate">—</span>
            </p>
            <p>
                <strong>Organised by</strong>
                <span id="modalNgoName">—</span>
            </p>
        </div>

        <hr class="modal-divider">

        <form method="POST" action="" id="volunteerForm">
            <input type="hidden" name="campaign_id" id="modalCampaignId">
            <label for="skillsInput">Your Skills <span style="color:#475569">(e.g. Teaching, Medical Aid)</span></label>
            <input type="text" id="skillsInput" name="skills" placeholder="Enter your skills…" required>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-modal-submit">🤝 Volunteer</button>
            </div>
        </form>
    </div>
</div>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">HelpSphere</div>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="../dashboards/donor-dashboard.php" class="nav-item"><span class="icon">🏠</span><span class="label">Dashboard</span></a></li>
                <li><a href="../donors/donor-profile-preferences.php" class="nav-item"><span class="icon">👤</span><span class="label">Profile & Preferences</span></a></li>
                <li><a href="../donors/donor-food-selection.php" class="nav-item"><span class="icon">🍱</span><span class="label">Food Donation</span></a></li>
                <li><a href="../donors/donor-money-selection.php" class="nav-item"><span class="icon">💰</span><span class="label">Money Donation</span></a></li>
                <li><a href="../donors/donor-resource-donation.php" class="nav-item"><span class="icon">📦</span><span class="label">Resource Donation</span></a></li>
                <li><a href="../donors/donor-volunteer-registration.php" class="nav-item active"><span class="icon">🤝</span><span class="label">Volunteering</span></a></li>
                <li><a href="../donors/donor-messages.php" class="nav-item"><span class="icon">💬</span><span class="label">Communication</span></a></li>
                <li><a href="../../inc/logout.php" class="nav-item"><span class="icon">🚪</span><span class="label">Logout</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="user-name">Volunteer for Campaigns</div>
            <div class="menu-toggle" id="menuToggle">☰</div>
        </header>

        <section class="active-requests">
            <div class="section-header">
                <h3>Ongoing Campaigns</h3>
            </div>

            <div class="campaign-list">
            <?php if (empty($campaigns)): ?>
                <div class="no-campaigns">No ongoing campaigns at the moment. Check back soon!</div>
            <?php else: ?>
                <?php foreach ($campaigns as $c):
                    $cid         = (int) $c['id'];
                    $title       = htmlspecialchars($c['title']);
                    $description = htmlspecialchars($c['description']);
                    $st_date     = htmlspecialchars($c['st_date']);
                    $end_date    = htmlspecialchars($c['end_date']);
                    $ngo_name    = htmlspecialchars($c['ngo_name']);
                    $isApplied   = isset($applied[$cid]);
                    $appStatus   = $isApplied ? $applied[$cid] : '';
                    $statusClass = 's-' . strtolower($appStatus);
                ?>
                <div class="campaign-card">
                    <div class="campaign-info">
                        <div class="campaign-title"><?php echo $title; ?></div>
                        <div class="campaign-meta">
                            <span>🏢 <?php echo $ngo_name; ?></span>
                            <span>📅 <?php echo $st_date; ?> → <?php echo $end_date; ?></span>
                        </div>
                    </div>

                    <?php if ($isApplied): ?>
                        <div class="applied-badge">
                            <span class="badge-applied">✔ Applied</span>
                            <span class="badge-status">Status: <span class="<?php echo $statusClass; ?>"><?php echo $appStatus; ?></span></span>
                        </div>
                    <?php else: ?>
                        <button class="btn-volunteer"
                                onclick="openModal(<?php echo $cid; ?>, '<?php echo addslashes($title); ?>', '<?php echo addslashes($description); ?>', '<?php echo $st_date; ?>', '<?php echo $end_date; ?>', '<?php echo addslashes($ngo_name); ?>')">
                            🤝 Volunteer
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </section>

    </main>
    <script src="../assets/js/donor.js"></script>
</div>

<script>
    function openModal(id, title, description, startDate, endDate, ngoName) {
        document.getElementById('modalCampaignId').value   = id;
        document.getElementById('modalCampaignName').textContent = '🤝 ' + title;
        document.getElementById('modalDescription').textContent  = description;
        document.getElementById('modalStartDate').textContent    = startDate;
        document.getElementById('modalEndDate').textContent      = endDate;
        document.getElementById('modalNgoName').textContent      = ngoName;
        document.getElementById('skillsInput').value = '';
        document.getElementById('volunteerModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('volunteerModal').classList.remove('active');
    }

    // Close on backdrop click
    document.getElementById('volunteerModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
</script>

</body>
</html>