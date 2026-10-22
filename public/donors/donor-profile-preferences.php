<?php
//donor-profile-preferences.php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

$stmt=$pdo->prepare("select * from donor_master where id=?");
$stmt->execute([(int)($_SESSION['donor_id'] ?? 0)]);
$result=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$result)
{
    echo "Donor profile not found";
    exit;
}
$name=$result['name'];
$email=$result['email'];
$phone=$result['phone'];
$preferred_type=$result['preferred_type'];
$preferred_ngo_cause=$result['preferred_ngo_cause'];
$error='';
$allowed_types=['Food','Money','Resources','Blood'];
if(isset($_POST['save-btn']))
{
    $donor_id=(int)($_SESSION['donor_id'] ?? 0);
    $new_name=trim($_POST['name'] ?? '');
    $new_email=trim($_POST['email'] ?? '');
    $new_phone=trim($_POST['phone'] ?? '');
    $new_type=$_POST['type'] ?? '';
    $new_cause=trim($_POST['preferred_ngo_cause'] ?? '');

    if(!in_array($new_type, $allowed_types, true))
    {
        $error='Please choose a valid donation type.';
    }
    else
    {
        try
        {
            $pdo->beginTransaction();

            $stmt1=$pdo->prepare("update donor_master set name=?, email=?, phone=?, preferred_type=?, preferred_ngo_cause=? where id=?");
            $stmt1->execute([$new_name, $new_email, $new_phone, $new_type, $new_cause, $donor_id]);

            $stmt2=$pdo->prepare("update users set name=?, email=? where role_id=? and role=?");
            $stmt2->execute([$new_name, $new_email, $donor_id, 'Donor']);

            $pdo->commit();
            header("Location: donor-profile-preferences.php");
            exit;
        }
        catch(PDOException $e)
        {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('donor-profile-preferences: '.$e->getMessage());
            $error = ($e->getCode()==='23000')
                ? 'That email address is already in use.'
                : 'Could not save changes. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile &amp; preferences · Donor | HelpSphere</title>
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

    <span class="sidebar__tag">Donor space</span>

    <nav class="sidebar__nav" aria-label="Donor space">
      <a href="../dashboards/donor-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../donors/donor-food-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Food donation</a>
      <a href="../donors/donor-money-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-coin"/></svg>Money donation</a>
      <a href="../donors/donor-resource-donation.php"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg>Resource donation</a>
      <a href="../donors/donor-volunteer-registration.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteering</a>
      <a href="../donors/donor-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../donors/donor-profile-preferences.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile &amp; preferences</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Profile &amp; preferences</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span>
          <div class="profile__text"><strong>Donor</strong><span>Donor space</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">Profile</p>
          <h1 class="page-head__title">Profile &amp; preferences</h1>
          <p class="page-head__sub">Keep your contact details current so NGOs can reach you about pickups.</p>
        </div>
      </section>

<?php if($error!==''): ?>
      <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo htmlspecialchars($error); ?></span></p>
<?php endif; ?>

      <form class="form" action="" method="POST">
        <section class="row row--2">
          <article class="card reveal">
            <header class="card__head">
              <div>
                <h2 class="card__title">Personal details</h2>
                <p class="card__sub">How NGOs can contact you</p>
              </div>
            </header>
            <div class="form">
              <div class="field">
                <label for="pf-name">Full name</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-user"/></svg><input type="text" id="pf-name" value="<?php echo htmlspecialchars((string)$name) ?>" name="name" autocomplete="name"></div>
              </div>
              <div class="field">
                <label for="pf-email">Email address</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-mail"/></svg><input type="email" id="pf-email" value="<?php echo htmlspecialchars((string)$email) ?>" name="email" autocomplete="email"></div>
              </div>
              <div class="field">
                <label for="pf-phone">Phone number</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-phone"/></svg><input type="tel" id="pf-phone" value="<?php echo htmlspecialchars((string)$phone) ?>" name="phone" autocomplete="tel"></div>
              </div>
            </div>
          </article>

          <article class="card reveal">
            <header class="card__head">
              <div>
                <h2 class="card__title">Donation preferences</h2>
                <p class="card__sub">What you like to give, and the causes you care about</p>
              </div>
            </header>
            <div class="form">
              <div class="field">
                <label for="pf-type">Preferred donation type</label>
                <div class="field__control field__control--select">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-gift"/></svg>
                  <select id="pf-type" name="type">
                        <option value="Food" <?php echo ($preferred_type=="Food")?"selected":'' ?>>Food</option>
                        <option value="Money" <?php echo ($preferred_type=="Money")?"selected":'' ?>>Money</option>
                        <option value="Resources" <?php echo ($preferred_type=="Resources")?"selected":'' ?>>Resources</option>
                        <option value="Blood" <?php echo ($preferred_type=="Blood")?"selected":'' ?>>Blood</option>
                  </select>
                </div>
              </div>
              <div class="field">
                <label for="pf-cause">Preferred NGOs / causes</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-heart"/></svg><input type="text" id="pf-cause" value="<?php echo htmlspecialchars((string)$preferred_ngo_cause) ?>" name="preferred_ngo_cause" placeholder="e.g. Child education, hunger relief"></div>
              </div>
            </div>
          </article>
        </section>

        <div class="card reveal">
          <div class="actions">
            <button class="btn btn--dark" type="submit" name="save-btn"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Save profile &amp; preferences</button>
            <span class="muted">Changes apply immediately.</span>
          </div>
        </div>
      </form>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
