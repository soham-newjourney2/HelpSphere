# Project structure

HelpSphere is a plain PHP + MySQL/MariaDB web app — no framework, no Composer, no build step.
Every page is a `.php` file that handles its own form posts at the top and prints HTML below.
Styling and behaviour come from a few shared CSS and JavaScript files.

This document explains where everything lives and how the pieces fit together.

## Folder tree

```text
HelpSphere/
├── index.php                  Landing page
├── login-beneficiary.php      Sign in / create account — beneficiaries
├── login-donor.php            Sign in / create account — donors
├── login-ngo.php              Sign in — NGOs (accounts are created by admins)
├── login-admin.php            Sign in — admins
├── logout.php                 Ends the session for any role
├── .htaccess                  Apache: no folder listings; blocks inc/, database/, scripts/, uploads/
│
├── inc/                       Shared PHP, included by pages — never opened in a browser
│   ├── config.php             Starts the session, loads config.local.php, DB defaults, timezone, e()
│   ├── config.local.example.php   Template for your machine's settings (copy to config.local.php)
│   ├── config.local.php       Your DB login, site URL and mail settings (git-ignored)
│   ├── db.php                 Creates the PDO connection $pdo
│   ├── auth.php               Login guards per role, CSRF helpers, action_button()
│   ├── escalation.php         Immediate-food-donation rounds and alert emails
│   ├── smtp_mail.php          Minimal SMTP (STARTTLS) mail sender
│   └── serve-upload.php       Streams an uploaded file after checking it stays inside its folder
│
├── public/                    Everything a signed-in user sees
│   ├── assets/
│   │   ├── css/               base.css · home.css · auth.css · app.css
│   │   ├── js/                common.js · home.js · auth.js · app.js · chat.js
│   │   └── img/icons.svg      One SVG sprite with every icon
│   ├── beneficiaries/         Beneficiary portal (dashboard, requests, documents, messages, profile)
│   ├── donors/                Donor portal (dashboard, food, money, resources, volunteering, messages, profile)
│   ├── ngos/                  NGO portal (dashboard, needs, beneficiaries, donations, campaigns, volunteers, messages, compliance)
│   └── admin/                 Admin console (overview, users, NGO verification, add NGO)
│
├── database/
│   └── helpsphere.sql         The whole database structure — import this one file
│
├── scripts/
│   ├── run_escalation_loop.php   Command-line loop: checks immediate donations every 30 s
│   └── start_escalation.bat      Double-click launcher for the loop on Windows (uses XAMPP's PHP)
│
├── uploads/                   Created on the first upload (git-ignored)
│   ├── beneficiary_documents/ ID and supporting documents from beneficiaries
│   └── ngo_documents/         NGO compliance documents
│
├── docs/structure.md          This file
├── .gitignore · .gitattributes
└── README.md
```

## How a page is built

Every portal page follows the same shape:

```php
<?php
require_once __DIR__ . '/../../inc/auth.php';   // config + $pdo + helpers
ngo_require_login();                            // or require_login(), donor_require_login(), admin_require_login()

if (isset($_POST['save-btn'])) {                // the page handles its own form
    csrf_require_post();                        // reject posts without the session token
    // … validate, query with prepared statements, then redirect
}
?>
<!DOCTYPE html>
… sidebar, top bar, page content …
<?php echo e($value) ?>                          <!-- always escape what you print -->
<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
```

Rules every page follows:

- **Login guard first.** `require_login()` (beneficiary), `donor_require_login()`, `ngo_require_login()` or `admin_require_login()` send visitors without a session to the right sign-in page.
- **CSRF on every form that changes data.** Forms include `<input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">`; handlers call `csrf_require_post()`. Chat requests send the token from `<meta name="csrf">`.
- **Escape all output** with `e()` (or the page's `h()`, which does the same).
- **Prepared statements only** for SQL.
- **No CSS or JS inside pages.** Pages use classes from the shared stylesheets and `data-*` attributes that the shared scripts read.

The sidebar and top bar are written out in each page of a portal (same markup across the portal, only the active link changes).

### Session keys

| Role        | Signed-in check            | Name shown        |
|-------------|----------------------------|-------------------|
| Beneficiary | `$_SESSION['beneficiary_id']` | `$_SESSION['fullname']` |
| Donor       | `$_SESSION['donor_id']`    | `$_SESSION['donor_name']` |
| NGO         | `$_SESSION['ngo_id']`      | `$_SESSION['ngo_name']` |
| Admin       | `$_SESSION['admin_id']`    | `$_SESSION['ad_name']` |

## Pages by role

### Public

| File | What it does |
|------|--------------|
| `index.php` | Landing page |
| `login-beneficiary.php` | Sign in, or create a beneficiary account (name, email, date of birth, gender, phone, address) |
| `login-donor.php` | Sign in, or create a donor account (name, email, phone, location, preferred way to give) |
| `login-ngo.php` | Sign in; explains that admins create NGO accounts. Returns the NGO to an email accept link after sign-in |
| `login-admin.php` | Sign in (passwords are hashed; an old plain-text password is upgraded on first sign-in) |
| `logout.php` | Ends the session |

### Beneficiary — `public/beneficiaries/`

| File | What it does |
|------|--------------|
| `beneficiary-dashboard.php` | Request counts by status and the five latest requests |
| `beneficiary-request-management.php` | All own requests with filters; edit, cancel, timeline, fulfilment links |
| `beneficiary-request-create.php` | New request (Food or Resources) |
| `beneficiary-request-edit.php` | Edit a request while it is Pending |
| `request-cancel.php` | Form handler: deletes an own Pending request |
| `beneficiary-request-timeline.php` | Progress of one request |
| `beneficiary-request-fulfillment.php` | How the NGO fulfilled a request |
| `beneficiary-document-upload.php` | Upload several documents at once (PDF/JPG/PNG, 5 MB each) |
| `beneficiary-documents.php` | List, preview and delete own documents (`?doc=ID` streams the file) |
| `beneficiary-messages.php` | Chat with NGOs |
| `beneficiary-profile.php` | Edit own details |

### Donor — `public/donors/`

| File | What it does |
|------|--------------|
| `donor-dashboard.php` | Donation counts, latest five donations, shortcuts |
| `donor-food-selection.php` | Choose immediate or scheduled; NGO food requests; verified NGOs; history |
| `donor-food-immediate.php` | Food ready now → alerts the 5 nearest verified NGOs (see below) |
| `donor-food-scheduled.php` | Food with a pickup date/time — open to any NGO, or answering one NGO request (`?donation=requested_by_NGO&id=…`) |
| `donor-money-selection.php`, `donor-money-ngo-list.php` | Verified NGOs' contact details — HelpSphere handles no money |
| `donor-resource-donation.php` | Offer clothes, books, supplies; donation history |
| `donor-volunteer-registration.php` | Apply to volunteer for ongoing campaigns |
| `donor-messages.php` | Chat with NGOs |
| `donor-profile-preferences.php` | Edit details and preferences |

### NGO — `public/ngos/`

| File | What it does |
|------|--------------|
| `ngo-dashboard.php` | Pending-request, donation and pickup counts; newest pending requests |
| `ngo-needs.php` → `ngo-needs-post.php`, `ngo-needs-status.php` | Post a food need for donors; track posted needs |
| `ngo-beneficiaries.php` | Section hub |
| `ngo-beneficiaries-review.php` | Approve or reject pending requests; list beneficiaries |
| `request-edit.php` | Form handler: approve (claims the request) or reject (hides it from this NGO only) |
| `ngo-beneficiaries-documents.php` | Verify, reject or report a beneficiary's documents (`?doc=ID` streams the file) |
| `ngo-beneficiaries-reports.php` | All document reports |
| `ngo-beneficiaries-assigned.php` | Pending requests plus this NGO's approved/fulfilled ones |
| `ngo-beneficiaries-fulfillment.php` → `request-fulfill.php` | Record how an approved request was delivered |
| `ngo-donations.php` | Section hub |
| `ngo-donations-food.php` | Food donations: accept and mark picked up |
| `donation-accept.php`, `donation-pickup.php` | Form handlers for the two buttons above |
| `ngo-donations-resource.php` | Accept resource donations (first NGO wins) |
| `ngo-donations-view.php` | Log of all donations to this NGO |
| `ngo-food-accept.php` | Target of the emailed "Accept" link for immediate donations |
| `ngo-campaigns.php` → `-create`, `-progress`, `-closure` | Create, close and archive campaigns |
| `ngo-volunteers.php` | Approve or reject volunteer applications |
| `ngo-communication.php` | Chat with beneficiaries, donors, other NGOs; admin channel |
| `ngo-profile.php` → `-registration`, `-verification`, `-documents` | Organisation details, verification status, compliance uploads |

### Admin — `public/admin/`

| File | What it does |
|------|--------------|
| `index.php` | Overview of admin tools and the verification process |
| `users.php` | Search, view, edit and delete beneficiaries, donors and NGOs |
| `ngo-verification.php` | Review NGO documents; verify or reject (with a reason); keeps `ngo_master.status` in sync |
| `add-ngo.php` | Create an NGO account (starts Unverified) |

## Frontend assets — `public/assets/`

| File | Used by | Contents |
|------|---------|----------|
| `css/base.css` | every page | Colour tokens, typography, buttons, forms, tables, status pills, alerts, timeline, animations |
| `css/home.css` | `index.php` | Landing page sections |
| `css/auth.css` | sign-in pages | Two-panel sign-in layout, role picker, tabs |
| `css/app.css` | portal pages | Sidebar, top bar, cards, KPIs, lists, chat, modals, admin tables |
| `js/common.js` | every page | Toasts, scroll reveal, number count-up, progress bars, show/hide password, `data-confirm` |
| `js/home.js` | `index.php` | Navbar, mobile menu, login dropdown, animated tracker |
| `js/auth.js` | sign-in pages | Sign-in/create-account tabs, password strength and match hints |
| `js/app.js` | portal pages | Off-canvas sidebar, filter tabs and search, panels, modals, document preview, upload rows, empty states |
| `js/chat.js` | messages pages | Contacts, polling every 3 s, sending (with CSRF token) |
| `img/icons.svg` | every page | `<svg><use href="…/icons.svg#i-name"/></svg>` |

Behaviour is switched on with attributes rather than page scripts, for example:
`data-count` (animated number), `data-confirm="…"` (confirm before submit),
`data-filter-target` / `data-status` (filter tabs), `data-modal-open="#id"` (modals),
`data-show-panel` (show one panel at a time), `data-empty-for="#list"` (empty state).
Status pills are styled from their text: `<span class="status" data-status="Pending">`.

## Database — `database/helpsphere.sql`

| Area | Tables |
|------|--------|
| Accounts | `admin_master`, `beneficiary_master`, `donor_master`, `ngo_master`, `users` (one row per beneficiary/donor/NGO with name, email and role — used to show who a donation went to) |
| Requests for help | `assistance_requests`, `assistance_rejection` (an NGO hid a request), `fulfillment_status` |
| Beneficiary documents | `beneficiary_documents`, `ngo_document_rejections` (an NGO hid a beneficiary document), `document_reports` |
| NGO compliance | `ngo_documents`, `ngo_document_admin_rejections` (admin rejection with reason) |
| Donations | `donations` (log of every donation), `food_donations`, `food_donation_requests` (NGO needs), `food_donation_batches` + `food_donation_notifications` (immediate donations), `resource_donations` |
| Campaigns | `campaigns`, `campaign_volunteers` |
| Messages | `general_messages` |

### Status lifecycles

- **Assistance request:** `Pending` → `Approved` (an NGO claims it; `ngo_id` is set) → `Fulfilled` (a `fulfillment_status` row is added).
- **Scheduled food donation:** `Donated` → `Accepted` → `Picked Up`. The matching `donations` row moves in step; the two rows share the same `created_at`, which is how they are matched.
- **NGO food need** (`food_donation_requests`): `Pending` → `Donated` (a donor answered) → `Accepted` → `Picked Up`.
- **Resource donation:** `Pending` → `Approved` (an NGO accepted it).
- **NGO:** `Unverified` → `Verified` once every compliance document is verified by an admin.

## Immediate food donations

1. `donor-food-immediate.php` creates a `food_donation_batches` row, ranks verified NGOs by how many words their address shares with the pickup address, and creates round-1 rows in `food_donation_notifications` for the top 5, each with a random token and a 7-minute expiry. Emails with an Accept link go out after the database commit.
2. `scripts/run_escalation_loop.php` runs `runEscalationEngine()` every 30 seconds: it expires overdue notifications, and for a batch whose round has run out with no acceptance it notifies the next 5 NGOs. With nobody left, the batch becomes `Expired`.
3. `ngo-food-accept.php?notif_id=…&token=…` checks the token, that the signed-in NGO is the one notified, and that the window is still open. The first NGO to accept wins; the `food_donations` and `donations` rows are created at that moment.

Without the loop running, round 1 still works but donations are never passed on to the next NGOs.

## Uploaded files

Uploads are stored under `uploads/` with generated names and are never linked directly.
Pages stream them through PHP after checking who is asking (`beneficiary-documents.php?doc=`, `ngo-beneficiaries-documents.php?doc=`, `ngo-profile-documents.php?doc=`, `admin/ngo-verification.php?doc=`).
File type is checked from the file's contents, not its name.

## Configuration

`inc/config.php` loads `inc/config.local.php` if it exists. Settings you can put there:

| Constant | Purpose | Default |
|----------|---------|---------|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Database connection | `127.0.0.1`, `3306`, `helpsphere`, `root`, empty (XAMPP's defaults) |
| `APP_URL` | Site address used in emailed Accept links | `http://localhost/HelpSphere` |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `SMTP_NAME` | Outgoing mail for donation alerts | Gmail placeholders |

`ESCALATION_MINUTES` (7) and `BATCH_SIZE` (5) can also be overridden there.

## Web server notes

- The app is meant to run on XAMPP (Apache + MariaDB) — see the README for setup.
- The root `.htaccess` turns off folder listings and makes Apache refuse any request into `inc/`, `database/`, `scripts/` or `uploads/`.
- All links are relative, so the project works from any folder under `htdocs` (e.g. `http://localhost/HelpSphere/`).
- Works with MySQL/MariaDB strict SQL mode: every insert provides or defaults each required column.
