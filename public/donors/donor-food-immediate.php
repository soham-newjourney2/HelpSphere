<?php
//donor-food-immediate.php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/escalation.php';   // shared: APP_URL, BATCH_SIZE, ESCALATION_MINUTES, engine functions

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

donor_require_login();
$donor_id = (int) $_SESSION['donor_id'];

$errors  = [];
$success = isset($_GET['sent']);   // set after redirect, so a refresh can't resubmit

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die("Invalid session token. Please go back and try again.");
    }

    $food_type     = (string)($_POST['food_type'] ?? 'Cooked');
    $quantity      = trim((string)($_POST['quantity'] ?? ''));
    $pickup_addr   = trim((string)($_POST['pickup_address'] ?? ''));
    $pickup_within = (int)($_POST['pickup_within'] ?? 1);

    if (!in_array($food_type, ['Cooked', 'Packaged', 'Raw'], true)) $errors[] = "Invalid food type.";
    if ($quantity === '')    $errors[] = "Quantity is required.";
    if ($pickup_addr === '') $errors[] = "Pickup address is required.";
    if ($pickup_within < 1 || $pickup_within > 8) $errors[] = "Pickup within must be between 1 and 8 hours.";

    if (empty($errors)) {
        $round = null;
        try {
            $pdo->beginTransaction();

            // 1. Batch record
            $sb = $pdo->prepare(
                "INSERT INTO food_donation_batches
                   (donor_id, food_type, quantity, pickup_address, pickup_within, urgency, status)
                 VALUES (?,?,?,?,?,'High','Pending')"
            );
            $sb->execute([$donor_id, $food_type, $quantity, $pickup_addr, $pickup_within]);
            $batch_id = (int) $pdo->lastInsertId();

            // 2. Rank verified NGOs by proximity to the pickup address (same rule the later rounds use)
            $ngos = $pdo->query("SELECT id, address FROM ngo_master WHERE status = 'Verified'")
                        ->fetchAll(PDO::FETCH_ASSOC);
            if (!$ngos) throw new RuntimeException('no-ngos');
            usort($ngos, fn($x, $y) =>
                locationScore($pickup_addr, (string)$y['address']) -
                locationScore($pickup_addr, (string)$x['address'])
            );
            $top5 = array_slice(array_column($ngos, 'id'), 0, BATCH_SIZE);

            // 3. Round 1 notifications (expiry from the MySQL clock, like every later round)
            $round = createRoundNotifications($pdo, $batch_id, $top5, 1);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $round = null;
            if ($e->getMessage() === 'no-ngos') {
                $errors[] = "No verified NGOs are available right now.";
            } else {
                error_log('donor-food-immediate: ' . $e->getMessage());
                $errors[] = "Could not submit your donation. Please try again.";
            }
        }

        // Emails go out after the commit
        if ($round !== null) {
            sendRoundEmails($pdo, $round, [
                'food_type'      => $food_type,
                'quantity'       => $quantity,
                'pickup_address' => $pickup_addr,
                'pickup_within'  => $pickup_within,
            ], 1);
            header('Location: donor-food-immediate.php?sent=1');
            exit;
        }
    }
}
// A Pending donation is NOT inserted into food_donations / donations.
// Only when an NGO accepts (ngo-food-accept.php) are the rows created with status=Donated.
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Immediate Food Donation</title>
    <link rel="stylesheet" href="../assets/css/donor.css">
    <style>
        .form-card {
            background: rgba(30,41,59,0.7);
            border: 1px solid rgba(99,179,237,0.15);
            border-radius: 14px;
            padding: 28px 30px;
            max-width: 560px;
        }
        .urgency-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            background: rgba(239,68,68,0.15);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 22px;
        }
        .alert-success {
            background: rgba(74,222,128,0.12);
            border: 1px solid rgba(74,222,128,0.3);
            border-radius: 10px;
            padding: 16px 20px;
            color: #4ade80;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 10px;
            padding: 14px 20px;
            color: #f87171;
            font-size: 0.87rem;
            margin-bottom: 20px;
        }
        .alert-error ul { margin: 6px 0 0 16px; padding: 0; }
        .hours-hint     { font-size: 0.76rem; color: #64748b; margin-top: 4px; }
        .hours-hint span { color: #f87171; }
        .info-note {
            background: rgba(251,191,36,0.08);
            border: 1px solid rgba(251,191,36,0.2);
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 0.83rem;
            color: #d97706;
            margin-top: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<div class="app">

    <aside class="sidebar">
        <div class="sidebar-header"><div class="brand">HelpSphere</div></div>
        <nav class="sidebar-nav"><ul>
            <li><a href="../dashboards/donor-dashboard.php" class="nav-item"><span class="icon">🏠</span><span class="label">Dashboard</span></a></li>
            <li><a href="../donors/donor-profile-preferences.php" class="nav-item"><span class="icon">👤</span><span class="label">Profile & Preferences</span></a></li>
            <li><a href="../donors/donor-food-selection.php" class="nav-item active"><span class="icon">🍱</span><span class="label">Food Donation</span></a></li>
            <li><a href="../donors/donor-money-selection.php" class="nav-item"><span class="icon">💰</span><span class="label">Money Donation</span></a></li>
            <li><a href="../donors/donor-resource-donation.php" class="nav-item"><span class="icon">📦</span><span class="label">Resource Donation</span></a></li>
            <li><a href="../donors/donor-volunteer-registration.php" class="nav-item"><span class="icon">🤝</span><span class="label">Volunteering</span></a></li>
            <li><a href="../donors/donor-messages.php" class="nav-item"><span class="icon">💬</span><span class="label">Communication</span></a></li>
            <li><a href="../../inc/logout.php" class="nav-item"><span class="icon">🚪</span><span class="label">Logout</span></a></li>
        </ul></nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="user-name">Immediate Food Donation</div>
            <div class="menu-toggle" id="menuToggle">☰</div>
        </header>

        <section class="active-requests">
            <div class="section-header"><h3>🍱 Immediate Food Donation</h3></div>

            <?php if ($success): ?>
                <div class="alert-success">
                    ✅ <strong>Donation submitted!</strong><br>
                    The nearest <?php echo BATCH_SIZE; ?> verified NGOs have been notified by email.
                    They have <?php echo ESCALATION_MINUTES; ?> minutes to accept.
                    If none respond, the next <?php echo BATCH_SIZE; ?> NGOs are contacted automatically.
                </div>
                <a href="donor-food-selection.php" class="btn secondary">← Back to Food Donations</a>

            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert-error">
                        <strong>Please fix the following:</strong>
                        <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <div class="form-card">
                    <div class="urgency-badge">🔴 URGENCY: HIGH (Fixed for all immediate donations)</div>

                    <form method="POST" action="">

                        <div class="form-group">
                            <label>Food Type</label>
                            <select name="food_type" required>
                                <?php foreach (['Cooked','Packaged','Raw'] as $ft): ?>
                                <option value="<?php echo $ft; ?>"
                                    <?php echo (($_POST['food_type'] ?? '') === $ft) ? 'selected' : ''; ?>>
                                    <?php echo $ft; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Approximate Quantity (with unit)</label>
                            <input type="text" name="quantity"
                                   placeholder="e.g. 5 kg, 20 packs, 3 trays"
                                   value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>"
                                   required>
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Pickup Address</label>
                            <textarea name="pickup_address" rows="3"
                                      placeholder="Full address where food can be picked up…"
                                      required><?php echo htmlspecialchars($_POST['pickup_address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Pickup Within (hours)</label>
                            <input type="number" name="pickup_within"
                                   min="1" max="8"
                                   value="<?php echo (int)($_POST['pickup_within'] ?? 2); ?>"
                                   required>
                            <div class="hours-hint">Must be between 1 and <span>8 hours</span>.</div>
                        </div>

                        <div class="form-group">
                            <label>Urgency Level</label>
                            <input type="text" value="High" disabled
                                   style="background:rgba(239,68,68,0.1);color:#f87171;
                                          border-color:rgba(239,68,68,0.3);cursor:not-allowed;">
                        </div>

                        <div class="card-action">
                            <button type="submit" name="submit_donation" class="btn">
                                🚀 Submit &amp; Notify NGOs
                            </button>
                        </div>
                    </form>

                    <div class="info-note">
                        ⚡ Once submitted, the <strong><?php echo BATCH_SIZE; ?> nearest verified NGOs</strong>
                        receive an email with an Accept link. If none accept within
                        <strong><?php echo ESCALATION_MINUTES; ?> minutes</strong>, the window closes for them
                        and the <strong>next <?php echo BATCH_SIZE; ?> NGOs</strong> are automatically
                        contacted — until someone accepts.
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="../assets/js/donor.js"></script>
</div>
</body>
</html>
