<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HelpSphere | Sharing Surplus, Restoring Dignity</title>
  <meta name="description" content="HelpSphere connects donors, verified NGOs, volunteers and people in need to redistribute surplus food, money and resources — transparently." />
  <meta name="hs-icons" content="public/assets/img/icons.svg" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Inter+Tight:wght@500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="public/assets/css/base.css" />
  <link rel="stylesheet" href="public/assets/css/home.css" />
</head>
<body>

<!-- ============ Navbar ============ -->
<header class="nav" id="nav">
  <div class="container nav__inner">
    <a href="index.php" class="logo" aria-label="HelpSphere home">
      <span class="logo__mark"><svg><use href="public/assets/img/icons.svg#i-sprout"/></svg></span>
      HelpSphere
    </a>

    <nav class="nav__links" aria-label="Primary">
      <a href="#home" class="is-active">Home</a>
      <a href="#about">About</a>
      <a href="#donate">Donate</a>
      <a href="#how">How it works</a>
      <a href="#trust">Transparency</a>
    </nav>

    <div class="nav__actions">
      <div class="menu">
        <button type="button" class="menu__btn" aria-expanded="false" aria-controls="loginMenu">
          Log in <svg class="icon"><use href="public/assets/img/icons.svg#i-arrow"/></svg>
        </button>
        <div class="menu__panel" id="loginMenu" hidden>
          <a href="login-beneficiary.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-user"/></svg>Beneficiary</a>
          <a href="login-donor.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-heart"/></svg>Donor</a>
          <a href="login-ngo.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-building"/></svg>NGO</a>
          <a href="login-admin.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-shield"/></svg>Admin</a>
        </div>
      </div>
      <a href="login-donor.php" class="btn btn--dark btn--sm">Donate now</a>
      <button type="button" class="icon-btn nav__toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
        <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-menu"/></svg>
      </button>
    </div>
  </div>

  <!-- Mobile menu -->
  <div class="mobile-menu" id="mobileMenu" hidden>
    <nav aria-label="Mobile">
      <a href="#home">Home</a>
      <a href="#about">About</a>
      <a href="#donate">Donate</a>
      <a href="#how">How it works</a>
      <a href="#trust">Transparency</a>
    </nav>
    <p class="mobile-menu__label">Log in as</p>
    <div class="mobile-menu__cta">
      <a href="login-beneficiary.php" class="btn btn--outline">Beneficiary</a>
      <a href="login-donor.php" class="btn btn--dark">Donor</a>
      <a href="login-ngo.php" class="btn btn--outline">NGO</a>
      <a href="login-admin.php" class="btn btn--outline">Admin</a>
    </div>
  </div>
</header>

<main>

  <!-- ============ Hero ============ -->
  <section class="hero" id="home">
    <svg class="leaf leaf--blur leaf--h1" aria-hidden="true"><use href="public/assets/img/icons.svg#leaf"/></svg>
    <svg class="leaf leaf--blur leaf--h2" aria-hidden="true"><use href="public/assets/img/icons.svg#leaf"/></svg>

    <div class="container hero__grid">
      <div class="hero__copy">
        <span class="eyebrow">Transparent resource redistribution</span>
        <h1 class="hero__title">Sharing Surplus,<br />Restoring Dignity</h1>
        <p class="hero__lead">
          HelpSphere connects donors, verified NGOs, volunteers and people in need to redistribute
          surplus food, money and resources — so every rupee and every meal reaches the right hands.
        </p>
        <div class="hero__cta">
          <a href="login-donor.php" class="btn btn--dark">
            Start donating <svg class="icon icon--arrow"><use href="public/assets/img/icons.svg#i-arrow"/></svg>
          </a>
          <a href="login-beneficiary.php" class="btn btn--outline">Request help</a>
        </div>

        <ul class="hero__trust">
          <li><svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-shield"/></svg> Admin-verified NGOs</li>
          <li><svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-check"/></svg> Zero platform fees</li>
        </ul>
      </div>

      <div class="hero__visual" aria-hidden="true">
        <!-- Illustrated arch -->
        <div class="arch">
          <svg class="arch__scene" viewBox="0 0 400 500" preserveAspectRatio="xMidYMax slice">
            <defs>
              <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#eef2e6"/>
                <stop offset="1" stop-color="#cfdcbf"/>
              </linearGradient>
            </defs>
            <rect width="400" height="500" fill="url(#sky)"/>
            <g class="arch__sun">
              <circle cx="282" cy="150" r="54" fill="#f1e3a6"/>
              <circle cx="282" cy="150" r="80" fill="#f1e3a6" opacity=".25"/>
            </g>
            <path d="M0 330 C70 290 130 300 200 320 S330 290 400 300 V500 H0z" fill="#9fc27a"/>
            <path d="M0 370 C90 340 160 360 230 375 S350 350 400 360 V500 H0z" fill="#6e9f45"/>
            <path d="M0 420 C80 400 170 410 240 425 S360 405 400 415 V500 H0z" fill="#3f6f27"/>
            <g class="arch__sprout">
              <path d="M198 500 C198 450 200 400 202 330" stroke="#24401a" stroke-width="7" fill="none" stroke-linecap="round"/>
              <path d="M201 360 C150 362 112 330 104 280 C158 278 196 310 201 360z" fill="#4f8a2c"/>
              <path d="M201 360 C170 340 140 318 116 290" stroke="#c5dba8" stroke-width="2" fill="none" opacity=".7"/>
              <path d="M202 335 C206 282 246 246 300 240 C298 296 258 332 202 335z" fill="#6ea13c"/>
              <path d="M202 335 C232 304 262 276 290 252" stroke="#e1edcf" stroke-width="2" fill="none" opacity=".7"/>
              <path d="M202 300 C182 282 176 254 200 220 C224 254 220 282 202 300z" fill="#7fb04a"/>
            </g>
          </svg>
        </div>

        <!-- Floating cards: how an immediate donation works -->
        <div class="float-card float-card--alert">
          <span class="float-card__icon"><svg class="icon"><use href="public/assets/img/icons.svg#i-food"/></svg></span>
          <div>
            <strong>Immediate food alert</strong>
            <span>Sent to the 5 nearest verified NGOs</span>
          </div>
        </div>

        <div class="float-card float-card--accept">
          <span class="float-card__icon float-card__icon--good"><svg class="icon"><use href="public/assets/img/icons.svg#i-check"/></svg></span>
          <div>
            <strong>First NGO to accept gets it</strong>
            <span>Pickup within the donor’s time window</span>
          </div>
        </div>

        <div class="float-chip">
          <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-clock"/></svg> 7-minute response window
        </div>

        <svg class="leaf leaf--h3"><use href="public/assets/img/icons.svg#leaf"/></svg>
        <svg class="leaf leaf--h4"><use href="public/assets/img/icons.svg#leaf"/></svg>
        <svg class="leaf leaf--h5"><use href="public/assets/img/icons.svg#leaf"/></svg>
        <svg class="leaf leaf--h6"><use href="public/assets/img/icons.svg#leaf"/></svg>

        <!-- Rotating badge -->
        <div class="hero__badge badge-ring badge-ring--lg">
          <svg class="badge-ring__text" viewBox="0 0 200 200">
            <defs><path id="ringPath" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs>
            <text><textPath href="#ringPath" textLength="480">FOR PEOPLE · FOR THE PLANET · FOR TOMORROW ·</textPath></text>
          </svg>
          <div class="badge-ring__core"><svg><use href="public/assets/img/icons.svg#i-sprout"/></svg></div>
        </div>
      </div>
    </div>

    <!-- How the platform is set up (rules, not statistics) -->
    <div class="container">
      <dl class="facts reveal">
        <div class="facts__item"><dt>for nearby NGOs to accept an immediate food donation</dt><dd><span data-count="7">7</span><small>min</small></dd></div>
        <div class="facts__item"><dt>nearest verified NGOs alerted in each round</dt><dd><span data-count="5">5</span></dd></div>
        <div class="facts__item"><dt>platform fees — help goes straight to NGOs</dt><dd>₹0</dd></div>
        <div class="facts__item"><dt>roles on one platform</dt><dd><span data-count="4">4</span></dd></div>
      </dl>
    </div>
  </section>

  <!-- ============ Who it's for ============ -->
  <section class="section" id="about">
    <div class="container">
      <div class="section-head reveal">
        <div>
          <span class="eyebrow">One platform, four roles</span>
          <h2 class="section-title">Built for everyone<br />in the giving chain</h2>
        </div>
        <p class="section-lead">Each role gets its own secure space — from posting a need to tracking a pickup to verifying an NGO’s documents.</p>
      </div>

      <div class="roles">
        <a href="login-donor.php" class="role-card reveal">
          <span class="role-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-heart"/></svg></span>
          <h3>Donors</h3>
          <p>Give food, money or resources and follow every donation from pickup to delivery.</p>
          <span class="role-card__link">Donor sign in <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="login-ngo.php" class="role-card reveal">
          <span class="role-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-building"/></svg></span>
          <h3>NGOs</h3>
          <p>Post needs, accept nearby donations, run campaigns and support verified beneficiaries.</p>
          <span class="role-card__link">NGO portal <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="login-beneficiary.php" class="role-card reveal">
          <span class="role-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-user"/></svg></span>
          <h3>People in need</h3>
          <p>Request assistance with dignity, upload documents once and track every step of your request.</p>
          <span class="role-card__link">Request help <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="login-donor.php" class="role-card reveal">
          <span class="role-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-hand"/></svg></span>
          <h3>Volunteers</h3>
          <p>Sign up for NGO campaigns and help with logistics, verification and on-ground delivery.</p>
          <span class="role-card__link">Volunteer <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
      </div>
    </div>
  </section>

  <!-- ============ Ways to give ============ -->
  <section class="section section--card" id="donate">
    <div class="container">
      <div class="section-head reveal">
        <div>
          <span class="eyebrow">Ways to give</span>
          <h2 class="section-title">Give what you can,<br />where it matters most</h2>
        </div>
        <p class="section-lead">HelpSphere never touches your money — we connect you directly to verified NGOs and the people they serve.</p>
      </div>

      <div class="give">
        <article class="give-card give-card--feature reveal">
          <div class="give-card__top">
            <span class="give-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-food"/></svg></span>
            <span class="pill pill--warn"><svg class="icon"><use href="public/assets/img/icons.svg#i-clock"/></svg> 7-min response</span>
          </div>
          <h3>Food</h3>
          <p>Have surplus cooked, packaged or raw food? Post it once — the <strong>5 nearest verified NGOs</strong> are alerted instantly. If no one accepts in 7 minutes, the next 5 are notified automatically.</p>
          <ul class="give-card__list">
            <li><svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-check"/></svg> Immediate or scheduled pickup</li>
            <li><svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-check"/></svg> Respond to NGO food requests</li>
          </ul>
          <a href="login-donor.php" class="btn btn--light">Donate food <svg class="icon icon--arrow"><use href="public/assets/img/icons.svg#i-arrow"/></svg></a>
        </article>

        <article class="give-card reveal">
          <span class="give-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-coin"/></svg></span>
          <h3>Money</h3>
          <p>Pick a verified NGO by cause and contact them directly. No middlemen, no platform cut.</p>
          <a href="login-donor.php" class="give-card__link">Find an NGO <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow"/></svg></a>
        </article>

        <article class="give-card reveal">
          <span class="give-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-box"/></svg></span>
          <h3>Resources</h3>
          <p>Clothes, books, stationery and medical supplies — schedule a pickup at a time that suits you.</p>
          <a href="login-donor.php" class="give-card__link">Schedule pickup <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow"/></svg></a>
        </article>

        <article class="give-card reveal">
          <span class="give-card__icon"><svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-hand"/></svg></span>
          <h3>Time</h3>
          <p>Join an NGO’s ongoing campaign as a volunteer and lend your skills where they are needed.</p>
          <a href="login-donor.php" class="give-card__link">Volunteer <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow"/></svg></a>
        </article>
      </div>
    </div>
  </section>

  <!-- ============ How it works ============ -->
  <section class="section" id="how">
    <div class="container">
      <div class="section-head section-head--center reveal">
        <span class="eyebrow">How it works</span>
        <h2 class="section-title">From surplus to support<br />in four steps</h2>
      </div>

      <ol class="steps">
        <li class="step reveal">
          <span class="step__num">01</span>
          <svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-heart"/></svg>
          <h3>Offer or request</h3>
          <p>Donors post what they can give; beneficiaries describe what they need.</p>
        </li>
        <li class="step reveal">
          <span class="step__num">02</span>
          <svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-shield"/></svg>
          <h3>Verify &amp; match</h3>
          <p>Admins verify NGO documents. NGOs review beneficiary requests and documents.</p>
        </li>
        <li class="step reveal">
          <span class="step__num">03</span>
          <svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-truck"/></svg>
          <h3>Pick up &amp; deliver</h3>
          <p>NGOs accept, collect the donation and deliver aid to the people they support.</p>
        </li>
        <li class="step reveal">
          <span class="step__num">04</span>
          <svg class="icon icon--lg"><use href="public/assets/img/icons.svg#i-chart"/></svg>
          <h3>Track every step</h3>
          <p>Every status change is recorded, so donors and beneficiaries can see where help went.</p>
        </li>
      </ol>
    </div>
  </section>

  <!-- ============ Transparency ============ -->
  <section class="trust" id="trust">
    <svg class="leaf leaf--blur leaf--t1" aria-hidden="true"><use href="public/assets/img/icons.svg#leaf"/></svg>
    <svg class="leaf leaf--t2" aria-hidden="true"><use href="public/assets/img/icons.svg#leaf"/></svg>
    <div class="container trust__grid">
      <div class="reveal">
        <span class="eyebrow eyebrow--light">Transparency &amp; trust</span>
        <h2 class="section-title trust__title">Every donation leaves a trail you can follow</h2>
        <ul class="trust__list">
          <li><svg class="icon"><use href="public/assets/img/icons.svg#i-shield"/></svg><div><strong>Verified NGOs</strong><span>Admins review every NGO’s documents before it can receive food alerts.</span></div></li>
          <li><svg class="icon"><use href="public/assets/img/icons.svg#i-clock"/></svg><div><strong>Status at every step</strong><span>Donated → Accepted → Picked up, visible to both the donor and the NGO.</span></div></li>
          <li><svg class="icon"><use href="public/assets/img/icons.svg#i-flag"/></svg><div><strong>Documents that can be challenged</strong><span>NGOs verify beneficiary documents and can report anything that looks wrong.</span></div></li>
        </ul>
      </div>

      <!-- How an immediate food donation moves through the system -->
      <div class="tracker reveal">
        <div class="tracker__head">
          <div>
            <span class="tracker__label">How it moves</span>
            <strong>Immediate food donation</strong>
          </div>
          <span class="pill pill--good"><svg class="icon"><use href="public/assets/img/icons.svg#i-truck"/></svg> <span id="stageLabel">Posted</span></span>
        </div>
        <ol class="stages" id="stages">
          <li class="is-current"><span class="stages__dot"></span><div><strong>Posted</strong><span>A donor shares surplus food and a pickup address</span></div></li>
          <li><span class="stages__dot"></span><div><strong>NGOs alerted</strong><span>The 5 nearest verified NGOs get an email with an accept link</span></div></li>
          <li><span class="stages__dot"></span><div><strong>Accepted</strong><span>The first NGO to accept within 7 minutes gets the donation</span></div></li>
          <li><span class="stages__dot"></span><div><strong>Picked up</strong><span>The NGO collects the food and marks it picked up</span></div></li>
        </ol>
        <div class="tracker__foot">
          <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-info"/></svg> No answer in 7 minutes? The next 5 NGOs are alerted automatically.
        </div>
      </div>
    </div>
  </section>

  <!-- ============ Volunteer CTA ============ -->
  <section class="section">
    <div class="container">
      <div class="cta reveal">
        <div class="cta__copy">
          <h2>Be the change on the ground</h2>
          <p>Join an NGO campaign as a volunteer and help deliver aid where it’s needed most.</p>
          <div class="hero__cta">
            <a href="login-donor.php" class="btn btn--dark">Become a volunteer <svg class="icon icon--arrow"><use href="public/assets/img/icons.svg#i-arrow"/></svg></a>
            <a href="login-ngo.php" class="btn btn--outline">NGO portal</a>
          </div>
        </div>
        <div class="badge-ring badge-ring--md cta__badge">
          <svg class="badge-ring__text" viewBox="0 0 200 200">
            <defs><path id="ringPath2" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs>
            <text><textPath href="#ringPath2" textLength="480">VOLUNTEER · DONATE · DELIVER · REPEAT ·</textPath></text>
          </svg>
          <div class="badge-ring__core"><svg><use href="public/assets/img/icons.svg#i-sprout"/></svg></div>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- ============ Footer ============ -->
<footer class="footer">
  <div class="container footer__grid">
    <div>
      <a href="index.php" class="logo logo--light">
        <span class="logo__mark"><svg><use href="public/assets/img/icons.svg#i-sprout"/></svg></span>
        HelpSphere
      </a>
      <p class="footer__about">Connecting kindness with the people who need it — transparently, one donation at a time.</p>
    </div>
    <div>
      <h4>Platform</h4>
      <a href="#about">About</a>
      <a href="#how">How it works</a>
      <a href="#trust">Transparency</a>
    </div>
    <div>
      <h4>Get involved</h4>
      <a href="login-donor.php">Donate</a>
      <a href="login-beneficiary.php">Request help</a>
      <a href="login-ngo.php">NGO portal</a>
    </div>
    <div>
      <h4>Project</h4>
      <a href="https://github.com/soham-newjourney2/1.HelpSphere" rel="noopener"><svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-github"/></svg>Source on GitHub</a>
      <a href="login-admin.php">Admin login</a>
    </div>
  </div>
  <div class="container footer__bottom">
    <span>© 2026 HelpSphere</span>
    <span>Help goes directly to verified NGOs — no platform fees.</span>
  </div>
</footer>

<script src="public/assets/js/common.js"></script>
<script src="public/assets/js/home.js"></script>
</body>
</html>
