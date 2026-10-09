<?php
require_once __DIR__. '/../../inc/auth.php';
admin_require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — HelpSphere Admin</title>
  <meta name="description" content="HelpSphere NGO Admin Dashboard – Overview of beneficiaries, NGOs, donors, and platform activity." />
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
          <a href="index.html" class="sidebar__link active" id="nav-dashboard">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="sidebar__menu-label">Management</li>
        <li>
          <a href="add-ngo.php" class="sidebar__link" id="nav-add-ngo">
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
        <button class="topbar__icon-btn" aria-label="Notifications" id="btn-notifications">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="topbar__badge">3</span>
        </button>
        <button class="topbar__icon-btn" aria-label="Settings">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </button>
      </div>
    </header>

    <section class="page active" id="page-dashboard">
      <div class="page__header">
        <div>
          <h2 class="page__title">Dashboard</h2>
          <p class="page__desc">Welcome back, Admin. Here's your portal overview.</p>
        </div>
        <div class="page__header-actions">
          <span class="page__date" id="current-date"></span>
        </div>
      </div>

      <div class="kpi-grid">
        <div class="kpi-card kpi-card--beneficiary" data-aos>
          <div class="kpi-card__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div class="kpi-card__content">
            <p class="kpi-card__label">Total Beneficiaries</p>
            <h3 class="kpi-card__value" data-count="12480">0</h3>
            <p class="kpi-card__change kpi-card__change--up">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              +12.5% this month
            </p>
          </div>
          <div class="kpi-card__sparkline" id="spark-beneficiary"></div>
        </div>

        <div class="kpi-card kpi-card--ngo" data-aos>
          <div class="kpi-card__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </div>
          <div class="kpi-card__content">
            <p class="kpi-card__label">Registered NGOs</p>
            <h3 class="kpi-card__value" data-count="342">0</h3>
            <p class="kpi-card__change kpi-card__change--up">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              +8.3% this month
            </p>
          </div>
          <div class="kpi-card__sparkline" id="spark-ngo"></div>
        </div>

        <div class="kpi-card kpi-card--donor" data-aos>
          <div class="kpi-card__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </div>
          <div class="kpi-card__content">
            <p class="kpi-card__label">Active Donors</p>
            <h3 class="kpi-card__value" data-count="5765">0</h3>
            <p class="kpi-card__change kpi-card__change--up">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              +18.2% this month
            </p>
          </div>
          <div class="kpi-card__sparkline" id="spark-donor"></div>
        </div>

        <div class="kpi-card kpi-card--funds" data-aos>
          <div class="kpi-card__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="kpi-card__content">
            <p class="kpi-card__label">Total Funds Raised</p>
            <h3 class="kpi-card__value" data-count="2340000" data-prefix="₹" data-suffix="">0</h3>
            <p class="kpi-card__change kpi-card__change--up">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              +24.1% this month
            </p>
          </div>
          <div class="kpi-card__sparkline" id="spark-funds"></div>
        </div>
      </div>

      <div class="charts-grid">
        <div class="chart-card" data-aos>
          <div class="chart-card__header">
            <h3 class="chart-card__title">User Distribution</h3>
            <span class="chart-card__badge">Live</span>
          </div>
          <div class="chart-card__body">
            <div class="donut-chart" id="donut-chart">
              <svg viewBox="0 0 200 200" class="donut-chart__svg">
                <circle cx="100" cy="100" r="80" class="donut-chart__ring donut-chart__ring--bg" />
                <circle cx="100" cy="100" r="80" class="donut-chart__ring donut-chart__ring--beneficiary" stroke-dasharray="0 503" />
                <circle cx="100" cy="100" r="80" class="donut-chart__ring donut-chart__ring--ngo" stroke-dasharray="0 503" />
                <circle cx="100" cy="100" r="80" class="donut-chart__ring donut-chart__ring--donor" stroke-dasharray="0 503" />
              </svg>
              <div class="donut-chart__center">
                <span class="donut-chart__total">18,587</span>
                <span class="donut-chart__label">Total Users</span>
              </div>
            </div>
            <div class="donut-legend">
              <div class="donut-legend__item">
                <span class="donut-legend__dot donut-legend__dot--beneficiary"></span>
                <span>Beneficiaries</span>
                <strong>67%</strong>
              </div>
              <div class="donut-legend__item">
                <span class="donut-legend__dot donut-legend__dot--ngo"></span>
                <span>NGOs</span>
                <strong>2%</strong>
              </div>
              <div class="donut-legend__item">
                <span class="donut-legend__dot donut-legend__dot--donor"></span>
                <span>Donors</span>
                <strong>31%</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="chart-card" data-aos>
          <div class="chart-card__header">
            <h3 class="chart-card__title">Monthly Registrations</h3>
            <div class="chart-card__tabs">
              <button class="chart-card__tab active">6M</button>
              <button class="chart-card__tab">1Y</button>
            </div>
          </div>
          <div class="chart-card__body">
            <div class="bar-chart" id="bar-chart">
              <div class="bar-chart__bars">
                <div class="bar-chart__group" data-month="Apr">
                  <div class="bar-chart__bar bar-chart__bar--beneficiary" style="--bar-h:60%"></div>
                  <div class="bar-chart__bar bar-chart__bar--ngo" style="--bar-h:20%"></div>
                  <div class="bar-chart__bar bar-chart__bar--donor" style="--bar-h:45%"></div>
                </div>
                <div class="bar-chart__group" data-month="May">
                  <div class="bar-chart__bar bar-chart__bar--beneficiary" style="--bar-h:75%"></div>
                  <div class="bar-chart__bar bar-chart__bar--ngo" style="--bar-h:30%"></div>
                  <div class="bar-chart__bar bar-chart__bar--donor" style="--bar-h:55%"></div>
                </div>
                <div class="bar-chart__group" data-month="Jun">
                  <div class="bar-chart__bar bar-chart__bar--beneficiary" style="--bar-h:50%"></div>
                  <div class="bar-chart__bar bar-chart__bar--ngo" style="--bar-h:15%"></div>
                  <div class="bar-chart__bar bar-chart__bar--donor" style="--bar-h:40%"></div>
                </div>
                <div class="bar-chart__group" data-month="Jul">
                  <div class="bar-chart__bar bar-chart__bar--beneficiary" style="--bar-h:85%"></div>
                  <div class="bar-chart__bar bar-chart__bar--ngo" style="--bar-h:35%"></div>
                  <div class="bar-chart__bar bar-chart__bar--donor" style="--bar-h:65%"></div>
                </div>
                <div class="bar-chart__group" data-month="Aug">
                  <div class="bar-chart__bar bar-chart__bar--beneficiary" style="--bar-h:70%"></div>
                  <div class="bar-chart__bar bar-chart__bar--ngo" style="--bar-h:25%"></div>
                  <div class="bar-chart__bar bar-chart__bar--donor" style="--bar-h:50%"></div>
                </div>
                <div class="bar-chart__group" data-month="Sep">
                  <div class="bar-chart__bar bar-chart__bar--beneficiary" style="--bar-h:95%"></div>
                  <div class="bar-chart__bar bar-chart__bar--ngo" style="--bar-h:40%"></div>
                  <div class="bar-chart__bar bar-chart__bar--donor" style="--bar-h:72%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="bottom-grid">
        <div class="activity-card" data-aos>
          <div class="activity-card__header">
            <h3 class="activity-card__title">Recent Activity</h3>
            <a href="#" class="activity-card__link">View All</a>
          </div>
          <ul class="activity-list">
            <li class="activity-list__item">
              <div class="activity-list__dot activity-list__dot--green"></div>
              <div class="activity-list__content">
                <p class="activity-list__text"><strong>Aarav Sharma</strong> registered as a new Beneficiary</p>
                <span class="activity-list__time">2 minutes ago</span>
              </div>
            </li>
            <li class="activity-list__item">
              <div class="activity-list__dot activity-list__dot--blue"></div>
              <div class="activity-list__content">
                <p class="activity-list__text"><strong>Hope Foundation</strong> was approved and listed</p>
                <span class="activity-list__time">15 minutes ago</span>
              </div>
            </li>
            <li class="activity-list__item">
              <div class="activity-list__dot activity-list__dot--purple"></div>
              <div class="activity-list__content">
                <p class="activity-list__text"><strong>Priya Verma</strong> donated ₹25,000 to Child Care NGO</p>
                <span class="activity-list__time">1 hour ago</span>
              </div>
            </li>
            <li class="activity-list__item">
              <div class="activity-list__dot activity-list__dot--orange"></div>
              <div class="activity-list__content">
                <p class="activity-list__text"><strong>Green Earth Trust</strong> updated their profile</p>
                <span class="activity-list__time">3 hours ago</span>
              </div>
            </li>
            <li class="activity-list__item">
              <div class="activity-list__dot activity-list__dot--green"></div>
              <div class="activity-list__content">
                <p class="activity-list__text"><strong>Ravi Kumar</strong> completed beneficiary verification</p>
                <span class="activity-list__time">5 hours ago</span>
              </div>
            </li>
            <li class="activity-list__item">
              <div class="activity-list__dot activity-list__dot--purple"></div>
              <div class="activity-list__content">
                <p class="activity-list__text"><strong>Meera Patel</strong> set up a monthly recurring donation</p>
                <span class="activity-list__time">8 hours ago</span>
              </div>
            </li>
          </ul>
        </div>

        <div class="module-cards">
          <div class="module-card module-card--beneficiary" data-aos>
            <div class="module-card__header">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <h4>Beneficiary Module</h4>
            </div>
            <div class="module-card__stats">
              <div class="module-card__stat">
                <span class="module-card__stat-val">8,420</span>
                <span class="module-card__stat-label">Verified</span>
              </div>
              <div class="module-card__stat">
                <span class="module-card__stat-val">4,060</span>
                <span class="module-card__stat-label">Pending</span>
              </div>
              <div class="module-card__stat">
                <span class="module-card__stat-val">92%</span>
                <span class="module-card__stat-label">Satisfaction</span>
              </div>
            </div>
            <div class="module-card__progress">
              <div class="module-card__progress-bar" style="--progress:67%"></div>
            </div>
            <p class="module-card__progress-text">67% verified of total beneficiaries</p>
          </div>

          <div class="module-card module-card--ngo" data-aos>
            <div class="module-card__header">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              <h4>NGO Module</h4>
            </div>
            <div class="module-card__stats">
              <div class="module-card__stat">
                <span class="module-card__stat-val">285</span>
                <span class="module-card__stat-label">Active</span>
              </div>
              <div class="module-card__stat">
                <span class="module-card__stat-val">57</span>
                <span class="module-card__stat-label">Pending</span>
              </div>
              <div class="module-card__stat">
                <span class="module-card__stat-val">18</span>
                <span class="module-card__stat-label">Regions</span>
              </div>
            </div>
            <div class="module-card__progress">
              <div class="module-card__progress-bar" style="--progress:83%"></div>
            </div>
            <p class="module-card__progress-text">83% NGOs are currently active</p>
          </div>

          <div class="module-card module-card--donor" data-aos>
            <div class="module-card__header">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              <h4>Donor Module</h4>
            </div>
            <div class="module-card__stats">
              <div class="module-card__stat">
                <span class="module-card__stat-val">3,240</span>
                <span class="module-card__stat-label">One-time</span>
              </div>
              <div class="module-card__stat">
                <span class="module-card__stat-val">2,525</span>
                <span class="module-card__stat-label">Recurring</span>
              </div>
              <div class="module-card__stat">
                <span class="module-card__stat-val">₹23.4L</span>
                <span class="module-card__stat-label">This Month</span>
              </div>
            </div>
            <div class="module-card__progress">
              <div class="module-card__progress-bar" style="--progress:56%"></div>
            </div>
            <p class="module-card__progress-text">56% are recurring donors</p>
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

      document.getElementById('current-date').textContent =
        new Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

      document.querySelectorAll('.kpi-card__value[data-count]').forEach(el => {
        const target = +el.dataset.count;
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        const start = performance.now();

        function step(now) {
          const progress = Math.min((now - start) / 1800, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = prefix + Math.floor(eased * target).toLocaleString('en-IN') + suffix;
          if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
      });

      const circumference = 2 * Math.PI * 80;
      const rings = {
        beneficiary: document.querySelector('.donut-chart__ring--beneficiary'),
        ngo: document.querySelector('.donut-chart__ring--ngo'),
        donor: document.querySelector('.donut-chart__ring--donor'),
      };
      Object.values(rings).forEach(r => r.setAttribute('stroke-dasharray', `0 ${circumference}`));

      setTimeout(() => {
        const bLen = 0.67 * circumference;
        const nLen = 0.02 * circumference;
        const dLen = 0.31 * circumference;

        rings.beneficiary.setAttribute('stroke-dasharray', `${bLen} ${circumference}`);
        rings.beneficiary.setAttribute('stroke-dashoffset', '0');
        rings.ngo.setAttribute('stroke-dasharray', `${nLen} ${circumference}`);
        rings.ngo.setAttribute('stroke-dashoffset', `-${bLen}`);
        rings.donor.setAttribute('stroke-dasharray', `${dLen} ${circumference}`);
        rings.donor.setAttribute('stroke-dashoffset', `-${bLen + nLen}`);
      }, 200);

      document.querySelectorAll('.bar-chart__bar').forEach((bar, i) => {
        setTimeout(() => bar.classList.add('animated'), 100 + i * 60);
      });

      document.querySelectorAll('.module-card__progress-bar').forEach((bar, i) => {
        setTimeout(() => bar.classList.add('animated'), 300 + i * 150);
      });

      const sparkData = {
        'spark-beneficiary': [20, 35, 28, 45, 38, 55, 48, 60, 52, 68],
        'spark-ngo': [10, 15, 12, 22, 18, 25, 20, 30, 28, 35],
        'spark-donor': [15, 25, 30, 22, 40, 35, 50, 45, 55, 62],
        'spark-funds': [30, 45, 35, 55, 50, 65, 60, 75, 70, 85],
      };
      const sparkColors = {
        'spark-beneficiary': 'var(--clr-beneficiary)',
        'spark-ngo': 'var(--clr-ngo)',
        'spark-donor': 'var(--clr-donor)',
        'spark-funds': 'var(--clr-funds)',
      };

      Object.keys(sparkData).forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const data = sparkData[id];
        const max = Math.max(...data);
        const w = 100, h = 50;
        const step = w / (data.length - 1);
        const points = data.map((v, i) => `${i * step},${h - (v / max) * h}`).join(' ');
        el.innerHTML = `
          <svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" style="width:100%;height:100%">
            <polyline points="${points}" fill="none" stroke="${sparkColors[id]}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        `;
      });

      document.querySelectorAll('.chart-card__tab').forEach(tab => {
        tab.addEventListener('click', () => {
          tab.parentElement.querySelectorAll('.chart-card__tab').forEach(t => t.classList.remove('active'));
          tab.classList.add('active');
        });
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