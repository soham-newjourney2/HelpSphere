<?php
require_once __DIR__ . '/../../inc/auth.php';
donor_require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$id       = (int)($_GET['id'] ?? 0);
$ngo_id   = (int)($_GET['ngo_id'] ?? 0);
$donation = (string)($_GET['donation'] ?? '');
$by_ngo   = ($donation === 'requested_by_NGO');

$TYPES = ['Cooked', 'Packaged', 'Raw'];
$error = '';
$form  = ['type' => 'Cooked', 'quantity' => '', 'date' => '', 'time' => '', 'address' => ''];

if ($by_ngo) {
    $stmt = $pdo->prepare("select * from food_donation_requests where request_type=? and id=?");
    $stmt->execute(['Scheduled', $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);   // false if not found
}

if (isset($_POST['donate-btn'])) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die("Invalid session token. Please go back and try again.");
    }

    $form['type']     = trim((string)($_POST['type'] ?? ''));
    $form['quantity'] = trim((string)($_POST['quantity'] ?? ''));
    $form['date']     = trim((string)($_POST['date'] ?? ''));
    $form['time']     = trim((string)($_POST['time'] ?? ''));
    $form['address']  = trim((string)($_POST['address'] ?? ''));

    // ----- validation (fixes findings 21-24) -----
    if (!in_array($form['type'], $TYPES, true)) {
        $error = 'Please choose a valid food type.';
    } elseif ($form['date'] === '') {
        $error = 'Please choose a pickup date.';
    } elseif (!($d = DateTime::createFromFormat('Y-m-d', $form['date'])) || $d->format('Y-m-d') !== $form['date']) {
        $error = 'Enter a valid pickup date.';
    } elseif ($form['time'] === '') {
        $error = 'Please choose a pickup time.';
    } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $form['time'])) {
        $error = 'Enter a valid pickup time.';
    }

    if ($error === '') {
        $donor_id = (int)($_SESSION['donor_id'] ?? 0);
        $run = function (PDOStatement $s, array $params) {
            if (!$s->execute($params)) throw new RuntimeException('query-failed');
        };

        try {
            $pdo->beginTransaction();

            $run($pdo->prepare("insert into donations (donor_id, target_id, target_role, status, donation_type) values (?,?,?,?,?)"),
                 [$donor_id, $ngo_id, 'NGO', 'Donated', 'Food']);

            if ($by_ngo) {
                $run($pdo->prepare("update food_donation_requests set donor_id=?, food_type=?, quantity=?, status=?, donated_at=NOW() where id=?"),
                     [$donor_id, $form['type'], $form['quantity'], 'Donated', $id]);

                $run($pdo->prepare("insert into food_donations (donor_id, food_type, target_type, target_id, quantity, status, created_at, pickup_address, pickup_date, pickup_time, urgency, donation_type, is_requested_by_ngo) values (?,?,?,?,?,?,NOW(),?,?,?,?,?,?)"),
                     [$donor_id, $form['type'], 'NGO', $ngo_id, $form['quantity'], 'Donated',
                      $form['address'], $form['date'], $form['time'], 'N/A', 'Scheduled', 'Yes']);
            } else {
                $run($pdo->prepare("insert into food_donations (donor_id, food_type, target_type, quantity, status, created_at, pickup_address, pickup_date, pickup_time, urgency, donation_type, is_requested_by_ngo) values (?,?,?,?,?,NOW(),?,?,?,?,?,?)"),
                     [$donor_id, $form['type'], 'NGO', $form['quantity'], 'Donated',
                      $form['address'], $form['date'], $form['time'], 'N/A', 'Scheduled', 'No']);
            }

            $pdo->commit();
            header("Location: ../donors/donor-food-selection.php");
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('donor-food-scheduled: ' . $e->getMessage());
            $error = 'Could not save your donation. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Food Donation</title>
    <link rel="stylesheet" href="../assets/css/donor.css">
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
                    <a href="../donors/donor-food-selection.php" class="nav-item active">
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
                    <a href="../donors/donor-resource-donation.php" class="nav-item">
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

    <!-- MAIN CONTENT -->
    <main class="main-content">

       <header class="topbar">
        <div class="user-name">Book Donation Slot</div>
        <div class="menu-toggle" id="menuToggle">☰</div>
       </header>

       <form action="" method="POST">
        <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']) ?>" />

        <?php if ($error !== ''): ?>
        <div style="color:#f87171;margin:0 0 16px"><?php echo h($error) ?></div>
        <?php endif ?>

        <!-- SCHEDULE DETAILS -->
        <section>
            <div class="section-header">
                <h3>Donation Schedule</h3>
            </div>

            <div class="card">

                <div class="form-group">
                    <label>Food Type</label>
                    <select name="type">
                        <?php foreach ($TYPES as $t): ?>
                        <option value="<?php echo h($t) ?>" <?php if ($form['type'] === $t) echo 'selected' ?>><?php echo h($t) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Estimated Quantity (Do mention the unit)</label>
                    <input type="text" placeholder="e.g. 50 gram" name="quantity" value="<?php echo h($form['quantity']) ?>">
                </div>

                <div class="form-group">
                    <label>Pickup Date</label>
                    <input type="date" name="date" value="<?php echo h($form['date']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Pickup Time Window</label>
                    <input type="time" name="time" value="<?php echo h($form['time']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Pickup Address</label>
                    <textarea rows="3" placeholder="Exact pickup location" name="address"><?php echo h($form['address']) ?></textarea>
                </div>

            </div>
        </section>

        <!-- PICKUP COORDINATION -->
        <section>
            <div class="section-header">
                <h3>Pickup Coordination</h3>
            </div>

            <div class="card">
                <div class="form-group">
                    <label>Urgency Level</label>
                    <input type="text" value="N/A" readonly>
                </div>
            </div>
        </section>

        <!-- CONFIRM -->
        <section>
            <div class="section-header">
                <h3>Confirm Scheduled Donation</h3>
            </div>

            <div class="card">
                <p>
                    Your donation will be scheduled and coordinated
                    with the selected NGO closer to the pickup time.
                </p>

                <button class="btn" type="submit" name="donate-btn">
                 Submit Scheduled Food Donation
                </button>
            </div>
        </section>
        </form>

    </main>
<script src="../assets/js/donor.js"></script>
</div>

</body>
</html>
