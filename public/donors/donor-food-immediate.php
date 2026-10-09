<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/smtp_mail.php';
donor_require_login();
$donor_id = (int) $_SESSION['donor_id'];

define('APP_URL',            'url of your app');
define('ESCALATION_MINUTES', 7);
define('BATCH_SIZE',         5);
define('MAIL_FROM',          'your email');

// ─── Location proximity scorer (word-overlap; swap for Haversine in production)
function locationScore(string $a, string $b): int {
    $words = fn($s) => array_filter(
        explode(' ', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $s)))
    );
    return count(array_intersect($words($a), $words($b)));
}

// ─── Send one round of emails + insert notification rows
function dispatchRound(PDO $pdo, int $batch_id, array $ngo_ids, int $round_no, array $b): void {
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . ESCALATION_MINUTES . ' minutes'));
    foreach ($ngo_ids as $ngo_id) {
        $token = bin2hex(random_bytes(32));
        $sn = $pdo->prepare(
            "INSERT INTO food_donation_notifications
               (batch_id, ngo_id, round_no, token, expires_at, status)
             VALUES (?,?,?,?,?,'Pending')"
        );
        $sn->execute([$batch_id, $ngo_id, $round_no, $token, $expires_at]);
        $notif_id = (int) $pdo->lastInsertId();

        $se = $pdo->prepare("SELECT name, email FROM ngo_master WHERE id = ?");
        $se->execute([$ngo_id]);
        $ngo = $se->fetch(PDO::FETCH_ASSOC);
        if (!$ngo || empty($ngo['email'])) continue;

        $accept_url = APP_URL . "/public/ngos/ngo-food-accept.php"
                    . "?notif_id={$notif_id}&token={$token}";
        $exp_label  = date('h:i A', strtotime($expires_at));

        $subject = "HelpSphere: Immediate Food Donation Near You – Accept by {$exp_label}";
        $body    =
            "Dear {$ngo['name']},\n\n"
          . "An urgent food donation is available near your area.\n\n"
          . "-------------------------------------------\n"
          . " DONATION DETAILS\n"
          . "-------------------------------------------\n"
          . " Food Type     : {$b['food_type']}\n"
          . " Quantity      : {$b['quantity']}\n"
          . " Pickup Address: {$b['pickup_address']}\n"
          . " Pickup Within : {$b['pickup_within']} hour(s)\n"
          . " Urgency       : High\n"
          . "-------------------------------------------\n\n"
          . "You have until {$exp_label} to accept.\n"
          . "After that, this request will be forwarded to the next batch of NGOs.\n\n"
          . ">>> ACCEPT HERE:\n{$accept_url}\n\n"
          . "- HelpSphere Platform";

        $headers = "From: HelpSphere <" . MAIL_FROM . ">\r\n"
                 . "Reply-To: " . MAIL_FROM . "\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n";
        smtp_mail($ngo['email'], $subject, $body);
    }
}

// ─── Handle submission
$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    $food_type     = $_POST['food_type']           ?? 'Cooked';
    $quantity      = trim($_POST['quantity']       ?? '');
    $pickup_addr   = trim($_POST['pickup_address'] ?? '');
    $pickup_within = (int)($_POST['pickup_within'] ?? 1);

    if (!in_array($food_type, ['Cooked','Packaged','Raw']))
                             $errors[] = "Invalid food type.";
    if (empty($quantity))    $errors[] = "Quantity is required.";
    if (empty($pickup_addr)) $errors[] = "Pickup address is required.";
    if ($pickup_within < 1 || $pickup_within > 8)
                             $errors[] = "Pickup within must be between 1 and 8 hours.";

    if (empty($errors)) {
        // 1. Insert batch record
        $sb = $pdo->prepare(
            "INSERT INTO food_donation_batches
               (donor_id, food_type, quantity, pickup_address, pickup_within, urgency, status)
             VALUES (?,?,?,?,?,'High','Pending')"
        );
        $sb->execute([$donor_id, $food_type, $quantity, $pickup_addr, $pickup_within]);
        $batch_id = (int) $pdo->lastInsertId();
        
        // 2. Donor location
        $dl = $pdo->prepare("SELECT location FROM donor_master WHERE id = ?");
        $dl->execute([$donor_id]);
        $dlRow     = $dl->fetch(PDO::FETCH_ASSOC);
        $donor_loc = $dlRow['location'] ?? '';

        // 3. Rank verified NGOs by proximity
        $ng = $pdo->prepare("SELECT id, address FROM ngo_master WHERE status = 'Verified'");
        $ng->execute();
        $ngos = $ng->fetchAll(PDO::FETCH_ASSOC);
        usort($ngos, fn($a, $b) =>
            locationScore($donor_loc, $b['address']) -
            locationScore($donor_loc, $a['address'])
        );
        $top5 = array_slice(array_column($ngos, 'id'), 0, BATCH_SIZE);

        // 4. Dispatch round 1
        dispatchRound($pdo, $batch_id, $top5, 1, [
            'food_type'      => $food_type,
            'quantity'       => $quantity,
            'pickup_address' => $pickup_addr,
            'pickup_within'  => $pickup_within,
        ]);
        $success = true;
    }
}
// Donation with status=Pending NOT inserted into food_donations or donations tables . Only if an NGO Accepts, the donation is inserted into these 2 tables with status=Donated. 
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