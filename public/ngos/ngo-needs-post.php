<?php 
require_once __DIR__. '/../../inc/auth.php'; // Contains PDO $pdo connection
ngo_require_login();

$message = "";

if (isset($_POST['submit-need'])) {
    $ngo_id = $_SESSION['ngo_id'];
    $need_type = $_POST['need_type'];

    try {
        if ($need_type === 'food') { 
            $sql = "INSERT INTO food_donation_requests (ngo_id, description, request_type, urgency) 
                    VALUES (:ngo_id, :description, :request_type, :urgency)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ngo_id'       => $ngo_id,
                ':description'  => $_POST['food_desc'],
                ':request_type' => "Scheduled",
                ':urgency'      => "N/A"
            ]);
            $message = "Food request submitted successfully!";
        } 
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Post New Need</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    /* Quick fix to ensure hidden forms don't take space */
    .hidden-form { display: none; }
    .status-msg { padding: 10px; margin-bottom: 10px; border-radius: 5px; background: rgba(255,255,255,0.1); color: #fff; }
    .type-select{ width:100%; padding:10px; background:#1a1a2e; color:white; border:1px solid #333; }
    .type-one{ width:100%; padding:10px; background:#1a1a2e; color:white; border:1px solid #333; }
    .urgency-type { width:100%; padding:10px; background:#1a1a2e; color:white; border:1px solid #333; }
    .date-avail { width:100%; padding:10px; background:#1a1a2e; color:white; border:1px solid #333; }
    .time-avail { width:100%; padding:10px; background:#1a1a2e; color:white; border:1px solid #333; }
    .submit-btn { display:none; margin-top:20px; }



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
            <a href="../dashboards/ngo-dashboard.php active">
              <span class="icon">📊</span>
              <span class="label">Dashboard Overview</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-profile.php">
              <span class="icon">🧾</span>
              <span class="label">Profile & Compliance</span>
            </a>
          </li>

          <li class="nav-item active">
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
          <div class="section-header"><h3>Post New Need</h3></div>
        </div>
        <div class="user-name">NGO Administrator</div>
      </div>

      <section class="active-requests">
        <?php if($message): ?>
            <div class="status-msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="section-header">
          <h3>Need Details</h3>
        </div>

        <div class="request-list">
          <form action="" method="POST">
            
            <div class="request-item">
              <label class="label">Type of Need</label>
              <br><br>
              <select name="need_type" id="needTypeSelector" class="request-item type-select"  required>
                <option value="" disabled selected>Select Category</option>
                <option value="food">Food</option>
              </select>
            </div>
            <br>

            <div id="foodFields" class="hidden-form">
                <div class="request-item">
                  <label class="label">Description</label>
                  <br><br>
                  <textarea name="food_desc" rows="4" placeholder="e.g. 50 meals for local shelter"></textarea>
                </div>
                <br>
            </div>

            <div id="bloodFields" class="hidden-form">
                <div class="request-item">
                  <label class="label">Description</label>
                  <br><br>
                  <textarea name="blood_desc" rows="4" placeholder="e.g. O+ blood required for emergency "></textarea>
                </div>
                <br>
                <div class="request-item">
                  <label class="label">Preferred Location</label>
                  <br><br>
                  <input type="text" name="location" placeholder="Hospital / City Name">
                </div>
                <br>
                <div class="request-item">
                  <label class="label">Available Date</label>
                  <br><br>
                  <input type="date" name="avail_date" class="date-avail">
                </div>
                <br>
                <div class="request-item">
                  <label class="label">Available Time</label>
                  <br><br>
                  <input type="time" name="avail_time" class="time-avail">
                </div>
                <br>
            </div>

            <button class="menu submit-button" type="submit" name="submit-need" id="submitBtn">
              Submit Need
            </button>
          </form>
        </div>
      </section>

      <div class="footer">© NGO Management System</div>
    </main>
  </div>

  <!-- <div class="snow">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div> -->

  <!-- <div class="snow-accumulation"></div> -->

  <script>
    const typeSelector = document.getElementById('needTypeSelector');
    const foodFields = document.getElementById('foodFields');
    const bloodFields = document.getElementById('bloodFields');
    const submitBtn = document.getElementById('submitBtn');

    typeSelector.addEventListener('change', function() {
        // Show button
        submitBtn.style.display = 'block';
        
        // Toggle fields
        if (this.value === 'food') {
            foodFields.classList.remove('hidden-form');
            bloodFields.classList.add('hidden-form');
            // Set required attributes
            setRequired(foodFields, true);
            setRequired(bloodFields, false);
        } else if (this.value === 'blood') {
            bloodFields.classList.remove('hidden-form');
            foodFields.classList.add('hidden-form');
            // Set required attributes
            setRequired(bloodFields, true);
            setRequired(foodFields, false);
        }
    });

    function setRequired(container, isRequired) {
        const inputs = container.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (isRequired) input.setAttribute('required', '');
            else input.removeAttribute('required');
        });
    }
  </script>
  <script src="../assets/js/sidebar.js"></script>

</body>
</html>
