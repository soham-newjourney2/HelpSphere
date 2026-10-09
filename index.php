<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>HelpSphere | Transparent Resource Redistribution Platform</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <link rel="stylesheet" href="./public/assets/css/style.css">
</head>
<body>

<!-- VIDEO BACKGROUND -->
<video class="bg-video" autoplay muted loop playsinline preload="auto">
  <source src="./public/uploads/VideoBg (1).mp4" type="video/mp4">
</video>
<div class="overlay"></div>

<!-- NAVBAR -->
<header class="navbar">
  <div class="logo">Help<span>Sphere</span></div>

  <nav>
    <ul class="nav-links">
      <li><a href="">Home</a></li>
      <li><a href="">About Platform</a></li>
      <li><a href="">How It Works</a></li>
      <li><a href="">Transparency & Trust</a></li>

      <li class="dropdown">
        <a id="dropBtn" class="donate-dropdown">Donate <i class="fa-solid fa-chevron-down"></i></a>
        <ul class="dropdown-menu" id="donation" style="display: none;">
          <li><a href="login-donor.php">Food</a></li>
          <li><a href="login-donor.php">Money</a></li>
          <li><a href="login-donor.php">Resources</a></li>
          <li><a href="login-donor.php">Blood</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a id="loginBtn" class="login-dropdown">Login/Register <i class="fa-solid fa-chevron-down"></i></a>
        <ul class="dropdown-menu" id="login" style="display: none;">
          <li><a href="login-beneficiary.php">Beneficiary</a></li>
          <li><a href="login-donor.php">Donor</a></li>
          <li><a href="login-ngo.php">NGO</a></li>
          <li><a href="login-admin.php">Admin</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <div class="menu-toggle"><i class="fa-solid fa-bars"></i></div>
</header>

<!-- HERO -->
<section class="hero">
  <h1>One Platform. <span>Millions Helped.</span></h1>
  <p>
    HelpSphere connects citizens, NGOs, volunteers, and authorities to
    redistribute surplus food, money, blood, and resources — transparently and in real time. HelpSphere never touches your money — we just connect you directly to verified NGOs so every rupee reaches the right hands.
  </p>

  <div class="hero-buttons">
    <a href="login-donor.php" class="btn-primary">Start Donating</a>
    <a href="login-beneficiary.php" class="btn-outline">Request Help</a>
  </div>

  <div class="stats">
    <div>
      <h3 data-count="10000">0</h3>
      <p>Donors</p>
    </div>
    <div>
      <h3 data-count="2000">0</h3>
      <p>Verified NGOs</p>
    </div>
    <div>
      <h3 data-count="5000">0</h3>
      <p>Volunteers</p>
    </div>
    <div>
      <h3 data-count="50000">0</h3>
      <p>Lives Impacted</p>
    </div>
  </div>
</section>

<!-- WHO IT IS FOR -->
<section class="roles">
  <div class="role-card">
    <i class="fa-solid fa-hand-holding-heart"></i>
    <h4>Donors</h4>
    <p>Donate food, money, blood, or resources with full tracking.</p>
  </div>

  <div class="role-card">
    <i class="fa-solid fa-people-group"></i>
    <h4>NGOs</h4>
    <p>Post needs, manage inventory, and deliver verified aid.</p>
  </div>

  <div class="role-card">
    <i class="fa-solid fa-user-shield"></i>
    <h4>People in Need</h4>
    <p>Request help with dignity and track assistance status.</p>
  </div>

  <div class="role-card">
    <i class="fa-solid fa-person-running"></i>
    <h4>Volunteers</h4>
    <p>Support logistics, verification, and on-ground execution.</p>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works">
  <h2>How HelpSphere Works</h2>
  <div class="steps">
    <div class="step">
      <span>1</span>
      <p>Offer resources or request help</p>
    </div>
    <div class="step">
      <span>2</span>
      <p>NGO verification & priority matching</p>
    </div>
    <div class="step">
      <span>3</span>
      <p>Pickup / donation / assistance delivery</p>
    </div>
    <div class="step">
      <span>4</span>
      <p>Transparent tracking & impact reports</p>
    </div>
  </div>
</section>

<!-- TRANSPARENCY -->
<section class="trust">
  <h2>Transparency & Trust</h2>
  <ul>
    <li>Verified NGOs & beneficiaries</li>
    <li>Real-time donation tracking</li>
    <li>Admin audits & activity logs</li>
    <li>Impact reports & analytics</li>
  </ul>
</section>

<!-- VOLUNTEER CTA -->
<section class="volunteer-cta">
  <h2>Be the Change on the Ground</h2>
  <p>Join as a volunteer and help deliver aid where it’s needed most.</p>
  <a href="#" class="btn-primary">Become a Volunteer</a>
</section>

<script src="./public/assets/js/script.js"></script>
</body>
</html>
