<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

if(isset($_POST['confirm-btn']))
{
    $stmt=$pdo->prepare("insert into resource_donations (donor_id, resource_type, quantity, description, resource_condition, pickup_address, pickup_date, pickup_time, status) values (?,?,?,?,?,?,?,?,?)");
    $stmt->bindParam(1, $_SESSION['donor_id']);
    $stmt->bindParam(2, $_POST['type']);
    $stmt->bindParam(3, $_POST['quantity']);
    $stmt->bindParam(4, $_POST['description']);
    $stmt->bindParam(5, $_POST['condition']);
    $stmt->bindParam(6, $_POST['address']);
    $stmt->bindParam(7, $_POST['date']);
    $stmt->bindParam(8, $_POST['time']);
    $stmt->bindValue(9, "Pending");
    $stmt->execute();

    $stmt1=$pdo->prepare("insert into donations (donor_id, target_role, status, donation_type) values (?,?,?,?)");
    $stmt1->bindParam(1, $_SESSION['donor_id']);
    $stmt1->bindValue(2, "NGO");
    $stmt1->bindValue(3, "Pending");
    $stmt1->bindValue(4, "Resource");
    $stmt1->execute();
    if($stmt && $stmt1)
    {
        header("Location: donor-resource-donation.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Donation</title>
    <link rel="stylesheet" href="../assets/css/donor.css">

    <style>
        #formDiv    { display: none; }
        #historyDiv { display: none; }

        .donation-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(99, 179, 237, 0.15);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 16px;
            transition: border-color 0.2s ease;
        }
        .donation-card:hover {
            border-color: rgba(99, 179, 237, 0.35);
        }
        .donation-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .donation-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #e2e8f0;
        }
        .donation-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .donation-status.pending  { background: rgba(251,191,36,0.15);  color: #fbbf24; border: 1px solid rgba(251,191,36,0.3); }
        .donation-status.approved { background: rgba(74,222,128,0.15);  color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }
        .donation-status.rejected { background: rgba(239,68,68,0.15);   color: #f87171; border: 1px solid rgba(239,68,68,0.3); }

        .donation-card-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 20px;
            font-size: 0.88rem;
            color: #94a3b8;
        }
        .donation-card-body p { margin: 0; }
        .donation-card-body strong { color: #cbd5e1; }

        .no-history {
            text-align: center;
            color: #475569;
            padding: 40px 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">HelpSphere</div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../dashboards/donor-dashboard.php" class="nav-item">
                        <span class="icon">🏠</span>
                        <span class="label">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../donors/donor-profile-preferences.php" class="nav-item">
                        <span class="icon">👤</span>
                        <span class="label">Profile & Preferences</span>
                    </a>
                </li>
                <li>
                    <a href="../donors/donor-food-selection.php" class="nav-item">
                        <span class="icon">🍱</span>
                        <span class="label">Food Donation</span>
                    </a>
                </li>
                <li>
                    <a href="../donors/donor-money-selection.php" class="nav-item">
                        <span class="icon">💰</span>
                        <span class="label">Money Donation</span>
                    </a>
                </li>
                <li>
                    <a href="../donors/donor-resource-donation.php" class="nav-item active">
                        <span class="icon">📦</span>
                        <span class="label">Resource Donation</span>
                    </a>
                </li>
                <li>
                    <a href="../donors/donor-volunteer-registration.php" class="nav-item">
                        <span class="icon">🤝</span>
                        <span class="label">Volunteering</span>
                    </a>
                </li>
                <li>
                    <a href="../donors/donor-messages.php" class="nav-item">
                        <span class="icon">💬</span>
                        <span class="label">Communication</span>
                    </a>
                </li> 
                <li>
                    <a href="../../inc/logout.php" class="nav-item">
                        <span class="icon">🚪</span>
                        <span class="label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="user-name">Book Donation Slot</div>
            <div class="menu-toggle" id="menuToggle">☰</div>
        </header>

        <section>
            <div class="section-header">
                <button class="btn" id="donateBtn">Donate Resources</button>
                <button class="btn" id="viewBtn">View Donation History</button>
            </div>

            <br><br>

            <!-- ── Donation Form ── -->
            <div id="formDiv">
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Resource Type</label>
                        <select name="type">
                            <option>Clothes</option>
                            <option>Books</option>
                            <option>Medical Supplies</option>
                            <option>Stationery</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" placeholder="e.g. 20" name="quantity">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea rows="7" placeholder="Give a description" name="description"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition">
                            <option>New</option>
                            <option>Gently Used</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Pickup Address</label>
                        <textarea rows="3" name="address"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Pickup Date</label>
                        <input type="date" name="date">
                    </div>

                    <div class="form-group">
                        <label>Pickup Time</label>
                        <input type="time" name="time">
                    </div>

                    <div class="card-action">
                        <button type="submit" name="confirm-btn" class="btn">
                            Confirm Resource Donation
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ── Donation History ── -->
        <div id="historyDiv">
            <?php 
            $stmt = $pdo->prepare("SELECT * FROM resource_donations WHERE donor_id = ? ORDER BY pickup_date DESC");
            $stmt->bindParam(1, $_SESSION['donor_id']);
            $stmt->execute();
            if ($stmt->rowCount() > 0):
                while ($record = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $resource_type   = htmlspecialchars($record['resource_type']);
                    $quantity        = htmlspecialchars($record['quantity']);
                    $description     = htmlspecialchars($record['description'] ?? '—');
                    $condition       = htmlspecialchars($record['resource_condition'] ?? '—');
                    $pickup_address  = htmlspecialchars($record['pickup_address']);
                    $pickup_date     = $record['pickup_date'];
                    $pickup_time     = $record['pickup_time'];
                    $status          = $record['status'];
                    $status_class    = strtolower($status);
            ?>
                <div class="donation-card">
                    <div class="donation-card-header">
                        <div class="donation-card-title">📦 <?php echo $resource_type; ?> &nbsp;·&nbsp; Qty: <?php echo $quantity; ?></div>
                        <span class="donation-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </div>
                    <div class="donation-card-body">
                        <p><strong>Condition:</strong> <?php echo $condition; ?></p>
                        <p><strong>Pickup Date:</strong> <?php echo $pickup_date; ?></p>
                        <p><strong>Pickup Time:</strong> <?php echo $pickup_time; ?></p>
                        <p><strong>Pickup Address:</strong> <?php echo $pickup_address; ?></p>
                        <?php if ($description !== '—'): ?>
                        <p style="grid-column: 1 / -1;"><strong>Description:</strong> <?php echo $description; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <div class="no-history">No resource donations made yet.</div>
            <?php endif; ?>
        </div>

    </main>

    <script>
        document.getElementById('donateBtn').addEventListener('click', () => {
            document.getElementById('formDiv').style.display    = 'block';
            document.getElementById('historyDiv').style.display = 'none';
        });
        document.getElementById('viewBtn').addEventListener('click', () => {
            document.getElementById('historyDiv').style.display = 'block';
            document.getElementById('formDiv').style.display    = 'none';
        });
    </script>
    <script src="../assets/js/donor.js"></script>
</div>

</body>
</html>
