<?php
require_once __DIR__. '/../../inc/auth.php';
admin_require_login();
$value=1;
$val=1;
$flag=0;

if(isset($_POST['submit-btn'])){
  // Function to remove leading and trailing whitespaces,'_','\', and prevent Cross Site Scripting (XSS) Attacks 
    function validateInput($data)
    {
        $data=trim($data,"_");
        $data=stripslashes($data);
        $data=htmlspecialchars($data);
        return $data;
    }

    // Function To Prevent Cross Site Scripting (XSS) Attacks
    function XSSPrevention($data)
    {
        $data=htmlspecialchars($data);
        return $data;
    }

    $fullname=validateInput($_POST['ngo-name']);
    $email=validateInput($_POST['email']);
    if(!$fullname)
    {
        $val=-1;        
    }

    // Hashing the Password before storing in Database 
    $password=password_hash($_POST['password'], PASSWORD_ARGON2ID); 
    $confirm_password=$_POST['cpassword'];

    // Checking if email exists
    $stmt=$pdo->prepare("Select * from ngo_master where email=?");
    $stmt->bindParam(1,$email);
    $stmt->execute();
    if($stmt->rowCount()>0)
    {
        $val=0;
    }
    
    // Password Verification
    if(password_verify($confirm_password,$password)==FALSE)
    {
        $value=0;
    }

    if($value==1 && $val==1)
    {
    // Insert data into table ngo_master and prevent SQL Injection Attacks
    $stmt=$pdo->prepare("insert into ngo_master (name, email, password, focus, description, registration_no, address, registration_date, status, phone) values (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bindParam(1, $fullname);
    $stmt->bindParam(2, $email);
    $stmt->bindParam(3, $password);
    $stmt->bindParam(4, $_POST['focus']);
    $stmt->bindParam(5, $_POST['description']);
    $stmt->bindParam(6, $_POST['reg-no']);
    $stmt->bindParam(7, $_POST['address']);
    $stmt->bindParam(8, $_POST['reg-date']);
    $stmt->bindValue(9, "Unverified");
    $stmt->bindParam(10, $_POST['phone']);
    $stmt->execute();
    $id=$pdo->lastInsertId();
    $stmt1=$pdo->prepare("insert into users (role_id, name, role, email) values (?,?,?,?)");
    $stmt1->bindParam(1,$id);
    $stmt1->bindParam(2,$fullname);
    $stmt1->bindValue(3, "NGO");
    $stmt1->bindParam(4,$email);
    $stmt1->execute();
    echo "<script>window.addEventListener('DOMContentLoaded',()=>showToast('NGO registered successfully!','success'));</script>";    
    }
    else if($value==0)
    {
        echo "<h1>Passwords Do Not Match. Please Enter Correct Password</h1>";
    }
    else if($val==0)
    {
        echo "<h1>Username or Email exists.</h1>";
    }
    else if($val==-1)
        echo "Full Name Required";
    else if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        echo "Valid Email ID Required";
    else if(strlen($password) < 6)
        echo "Password must be >= 6 chars";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add NGO — HelpSphere Admin</title>
  <meta name="description" content="Register new NGOs to the HelpSphere platform. Admin-only access." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <script>
    (function () {
      var t = localStorage.getItem('hs-theme');
      if (t !== 'light' && t !== 'dark') {
        t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
      }
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>
<body>

  <div class="liquid-bg" aria-hidden="true">
    <div class="liquid-bg__orb liquid-bg__orb--1"></div>
    <div class="liquid-bg__orb liquid-bg__orb--2"></div>
    <div class="liquid-bg__orb liquid-bg__orb--3"></div>
    <div class="liquid-bg__orb liquid-bg__orb--4"></div>
    <div class="liquid-bg__orb liquid-bg__orb--5"></div>
    <div class="liquid-bg__orb liquid-bg__orb--6"></div>
  </div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="sidebar__logo">
        <span class="sidebar__logo-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        </span>
        <div>
          <h1 class="sidebar__title">HelpSphere</h1>
          <span class="sidebar__subtitle">Admin Portal</span>
        </div>
      </div>
    </div>

    <nav class="sidebar__nav">
      <ul class="sidebar__menu">
        <li class="sidebar__menu-label">Main</li>
        <li>
          <a href="index.php" class="sidebar__link" id="nav-dashboard">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="sidebar__menu-label">Management</li>
        <li>
          <a href="add-ngo.php" class="sidebar__link active" id="nav-add-ngo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <span>Add NGO</span>
          </a>
        </li>
        <li>
          <a href="users.php" class="sidebar__link" id="nav-all-users">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>All Users</span>
          </a>
        </li>
        <li><a href="ngo-verification.php" class="sidebar__link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
          <span>NGO Verification</span></a></li>
        <li>
          <a href="../../inc/logout.php" class="sidebar__link" id="nav-all-users">
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </nav>

    <div class="sidebar__footer">
      <div class="sidebar__admin">
        <div class="sidebar__avatar">A</div>
        <div>
          <p class="sidebar__admin-name">Admin User</p>
          <p class="sidebar__admin-role">Super Admin</p>
        </div>
      </div>
    </div>
  </aside>

  <div class="overlay" id="overlay"></div>

  <main class="main" id="main">

    <header class="topbar">
      <button class="topbar__hamburger" id="hamburger" aria-label="Toggle sidebar">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topbar__actions">
        <button class="topbar__icon-btn theme-toggle" id="theme-toggle" aria-label="Toggle light or dark theme">
          <svg class="theme-toggle__sun" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
          <svg class="theme-toggle__moon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
      </div>
    </header>

    <section class="page active" id="page-add-ngo">
      <div class="page__header">
        <div>
          <h2 class="page__title">Register New NGO</h2>
          <p class="page__desc">Only administrators can add new NGOs to the HelpSphere platform.</p>
        </div>
      </div>

      <div class="form-layout">
        <div class="form-card" data-aos>
          <div class="form-card__badge">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Admin Only
          </div>

          <form class="ngo-form" id="ngo-form" action="" method="POST">
            <h3 class="form-section-title">Organization Details</h3>
            <div class="form-grid">
              <div class="form-group">
                <label for="ngo-name" class="form-label">NGO Name <span class="required">*</span></label>
                <input type="text" id="ngo-name" class="form-input" placeholder="e.g. Hope Foundation" name="ngo-name" required />
              </div>
              <div class="form-group">
                <label for="ngo-reg" class="form-label">Registration No. <span class="required">*</span></label>
                <input type="text" id="ngo-reg" class="form-input" placeholder="e.g. NGO-2026-XXXX" name="reg-no" required />
              </div>
              <div class="form-group">
                <label for="ngo-type" class="form-label">Focus <span class="required">*</span></label>
                <input type="text" id="ngo-reg" class="form-input" placeholder="e.g. Child Welfare" name="focus" required />
              </div>
              <div class="form-group">
                <label for="ngo-established" class="form-label">Registration Date</label>
                <input type="date" id="ngo-established" class="form-input" name="reg-date" />
              </div>
            </div>

            <h3 class="form-section-title">Contact Information</h3>
            <div class="form-grid">
              <div class="form-group">
                <label for="ngo-email" class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" id="ngo-email" class="form-input" placeholder="contact@ngo.org" name="email" required />
              </div>
              <div class="form-group">
                <label for="ngo-phone" class="form-label">Phone Number <span class="required">*</span></label>
                <input type="tel" id="ngo-phone" class="form-input" placeholder="+91 XXXXX XXXXX" name="phone" required />
              </div>
              <div class="form-group">
                <label for="ngo-website" class="form-label">Password</label>
                <input type="password" id="ngo-website" class="form-input" placeholder="your password" name="password" required />
              </div>
              <div class="form-group">
                <label for="ngo-website" class="form-label">Confirm Password</label>
                <input type="password" id="ngo-website" class="form-input" placeholder="confirm your password" name="cpassword" required />
              </div>
            </div>

            <h3 class="form-section-title">Address & Location</h3>
            <div class="form-grid">
              <div class="form-group form-group--full">
                <label for="ngo-address" class="form-label">Full Address <span class="required">*</span></label>
                <textarea id="ngo-address" class="form-input form-textarea" rows="3" placeholder="Street address, landmark..." name="address" required></textarea>
              </div>
            </div>

            <h3 class="form-section-title">Additional Info</h3>
            <div class="form-grid">
              <div class="form-group form-group--full">
                <label for="ngo-desc" class="form-label">Description / Mission</label>
                <textarea id="ngo-desc" class="form-input form-textarea" rows="4" placeholder="Briefly describe the NGO's mission and activities..." name="description"></textarea>
              </div>
              <div class="form-group">
                <div class="file-upload" id="file-upload-zone" style="visibility: hidden;">
                </div>
              </div>
              <div class="form-group">
              </div>
            </div>

            <div class="form-actions">
              <button type="reset" class="btn btn--ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                Reset Form
              </button>
              <button type="submit" class="btn btn--primary" id="btn-submit-ngo" name="submit-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Register NGO
              </button>
            </div>
          </form>
        </div>

        <div class="form-info">
          <div class="info-card" data-aos>
            <div class="info-card__icon info-card__icon--blue">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </div>
            <h4 class="info-card__title">Registration Guidelines</h4>
            <ul class="info-card__list">
              <li>NGO must have valid government registration</li>
              <li>Provide an active email for account verification</li>
              <li>All fields marked with <span class="required">*</span> are mandatory</li>
            </ul>
          </div>

          <div class="info-card" data-aos>
            <div class="info-card__icon info-card__icon--green">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h4 class="info-card__title">After Registration</h4>
            <ul class="info-card__list">
              <li>NGO receives login credentials</li>
              <li>Profile becomes visible on the public portal</li>
              <li>NGO can start receiving donor connections</li>
              <li>Admin can deactivate at any time</li>
            </ul>
          </div>

          <div class="info-card" data-aos>
            <div class="info-card__icon info-card__icon--amber">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h4 class="info-card__title">Important Notice</h4>
            <p class="info-card__text">Duplicate registrations are auto-detected. If an NGO with the same registration number exists, the form will flag it.</p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <div class="toast-container" id="toast-container"></div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {

      const root = document.documentElement;

      document.getElementById('theme-toggle')?.addEventListener('click', () => {
        const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('hs-theme', next); } catch (e) {}
      });

      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');

      document.getElementById('hamburger')?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
      });
      overlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
      });

      function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        const stroke = type === 'success' ? 'var(--clr-beneficiary)' : 'var(--clr-danger)';
        const icon = type === 'success'
          ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="${stroke}" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`
          : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="${stroke}" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;

        toast.innerHTML = `
          <span class="toast__icon">${icon}</span>
          <span class="toast__message">${message}</span>
          <span class="toast__close" role="button" aria-label="Dismiss">&#10005;</span>
        `;
        toast.querySelector('.toast__close').addEventListener('click', () => toast.remove());
        container.appendChild(toast);

        setTimeout(() => {
          toast.style.animation = 'toast-out .3s ease forwards';
          setTimeout(() => toast.remove(), 300);
        }, 4000);
      }

      const uploadZone = document.getElementById('file-upload-zone');
      ['dragenter', 'dragover'].forEach(evt => {
        uploadZone.addEventListener(evt, e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
      });
      ['dragleave', 'drop'].forEach(evt => {
        uploadZone.addEventListener(evt, e => { e.preventDefault(); uploadZone.classList.remove('dragover'); });
      });

      const revealEls = document.querySelectorAll('[data-aos]');
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
          entries.forEach((entry, i) => {
            if (!entry.isIntersecting) return;
            setTimeout(() => entry.target.classList.add('visible'), i * 70);
            obs.unobserve(entry.target);
          });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(el => observer.observe(el));
      } else {
        revealEls.forEach(el => el.classList.add('visible'));
      }

    });
  </script>
</body>
</html>
