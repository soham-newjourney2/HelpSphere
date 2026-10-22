<?php 
require_once __DIR__. '/../../inc/auth.php';
require_login();

$stmt=$pdo->prepare("select * from beneficiary_master where id=?");
$stmt->bindParam(1,$_SESSION['beneficiary_id']);
$stmt->execute();
if($stmt->rowCount()>0)
{
    $res=$stmt->fetch(PDO::FETCH_ASSOC);
    $name=isset($res['name'])?$res['name']:'';
    $email=isset($res['email'])?$res['email']:'';
    $dob=isset($res['dob'])?$res['dob']:'';
    $gender=isset($res['gender'])?$res['gender']:'';
    $address=isset($res['address'])?$res['address']:'';
    $phone=isset($res['phone'])?$res['phone']:'';
}

if(isset($_POST['update-btn']))
{
    $stmt=$pdo->prepare("update users set name=?,email=? where role_id=? and role=?");
    $stmt->bindParam(1,$_POST['name']);
    $stmt->bindParam(2,$_POST['email']);
    $stmt->bindParam(3,$_SESSION['beneficiary_id']);
    $stmt->bindValue(4, "Beneficiary");
    $stmt->execute();
    
    $stmt=$pdo->prepare("update beneficiary_master set name=?,email=?,dob=?,gender=?,address=?,phone=? where id=?");
    $stmt->bindParam(1,$_POST['name']);
    $stmt->bindParam(2,$_POST['email']);
    $stmt->bindParam(3,$_POST['dob']);
    $stmt->bindParam(4,$_POST['gender']);
    $stmt->bindParam(5,$_POST['address']);
    $stmt->bindParam(6,$_POST['phone']);
    $stmt->bindParam(7,$_SESSION['beneficiary_id']);
    $stmt->execute();
    if($stmt){
        header("Location: beneficiary-profile.php");
        exit;
    }        
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile · Beneficiary | HelpSphere</title>
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

    <span class="sidebar__tag">Beneficiary space</span>

    <nav class="sidebar__nav" aria-label="Beneficiary space">
      <a href="../dashboards/beneficiary-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../beneficiaries/beneficiary-request-management.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>My requests</a>
      <a href="../beneficiaries/beneficiary-document-upload.php"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
      <a href="../beneficiaries/beneficiary-documents.php"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
      <a href="../beneficiaries/beneficiary-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../beneficiaries/beneficiary-profile.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Profile</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg></span>
          <div class="profile__text"><strong>Beneficiary</strong><span>Beneficiary space</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">Profile &amp; settings</p>
          <h1 class="page-head__title">Your details</h1>
          <p class="page-head__sub">NGOs use these details to contact you and to deliver help.</p>
        </div>
      </section>

      <form class="form" action="" method="POST">
        <section class="row row--2">
          <article class="card reveal">
            <header class="card__head">
              <div>
                <h2 class="card__title">Personal details</h2>
                <p class="card__sub">Your name, date of birth and gender</p>
              </div>
            </header>
            <div class="form">
              <div class="field">
                <label for="pf-name">Full name</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-user"/></svg><input type="text" id="pf-name" value="<?php echo isset($name)?$name:'' ?>" name="name" autocomplete="name"></div>
              </div>
              <div class="field">
                <label for="pf-dob">Date of birth</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-calendar"/></svg><input type="date" id="pf-dob" value="<?php echo isset($dob)?$dob:'' ?>" name="dob"></div>
              </div>
                    <?php if(isset($gender)): ?>
              <div class="field">
                <label for="pf-gender">Gender</label>
                <div class="field__control field__control--select">
                  <select id="pf-gender" name="gender">
                            <option value="Male" <?php if($gender=='Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if($gender=='Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if($gender=='Other') echo 'selected'; ?>>Other</option>
                  </select>
                </div>
              </div>
                    <?php endif ?>
            </div>
          </article>

          <article class="card reveal">
            <header class="card__head">
              <div>
                <h2 class="card__title">Address &amp; contact</h2>
                <p class="card__sub">Where and how NGOs can reach you</p>
              </div>
            </header>
            <div class="form">
              <div class="field">
                <label for="pf-address">Address</label>
                <div class="field__control field__control--area"><textarea id="pf-address" rows="3" name="address" autocomplete="street-address"><?php echo isset($address) ? htmlspecialchars($address) : '' ?></textarea></div>
              </div>
              <div class="field">
                <label for="pf-phone">Phone number</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-phone"/></svg><input type="tel" id="pf-phone" value="<?php echo isset($phone)?$phone:'' ?>" name="phone" autocomplete="tel"></div>
              </div>
              <div class="field">
                <label for="pf-email">Email</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-mail"/></svg><input type="email" id="pf-email" value="<?php echo isset($email)?$email:'' ?>" name="email" autocomplete="email"></div>
              </div>
            </div>
          </article>
        </section>

        <div class="card reveal">
          <div class="actions">
            <button type="submit" name="update-btn" class="btn btn--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Save changes</button>
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
