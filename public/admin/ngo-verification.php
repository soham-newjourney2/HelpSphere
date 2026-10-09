<?php
ob_start();   // buffer everything so stray output (BOM, whitespace, notices) can be thrown away before sending a file
require_once __DIR__ . '/../../inc/auth.php';
admin_require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$UPLOAD_DIR = realpath(__DIR__ . '/../../uploads/ngo_documents');   // project_root/uploads/ngo_documents
$STATUSES   = ['pending', 'verified', 'rejected'];
$BADGE      = ['pending' => 'pending', 'verified' => 'active', 'rejected' => 'inactive']; // reuses existing status-badge CSS

/* One row per NGO with document counts and the derived status */
$BASE = "select y.*, case
            when y.total = 0 then 'pending'
            when y.total - y.ver - y.rej > 0 then 'pending'
            when y.rej > 0 then 'rejected'
            else 'verified' end as vstatus
         from (
            select n.*,
              (select count(*) from ngo_documents d where d.ngo_id = n.id) as total,
              (select count(*) from ngo_documents d where d.ngo_id = n.id and d.status = 'Verified') as ver,
              (select count(*) from ngo_documents d where d.ngo_id = n.id and d.status <> 'Verified'
                  and exists (select 1 from ngo_document_admin_rejections r where r.document_id = d.id)) as rej
            from ngo_master n
         ) y";

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function nice_date($d) { $t = $d ? strtotime($d) : false; return $t ? date('d M Y', $t) : '—'; }
function list_url($f, $over = [])
{
    $p = array_merge($f, $over);
    if (($p['status'] ?? '') === 'pending') unset($p['status']);   // pending is the default tab
    if (($p['q'] ?? '') === '') unset($p['q']);
    if ((int)($p['page'] ?? 1) <= 1) unset($p['page']);
    return 'ngo-verification.php' . ($p ? '?' . http_build_query($p) : '');
}
/** Small POST form for one action button */
function act_form($filters, $action, $uid, $label, $cls, $extra = [], $style = '')
{
    echo '<form method="post" action="ngo-verification.php" style="display:inline;margin:0">'
       . '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '" />'
       . '<input type="hidden" name="action" value="' . h($action) . '" />'
       . '<input type="hidden" name="uid" value="' . (int)$uid . '" />';
    foreach (array_merge($filters, $extra) as $k => $v) echo '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '" />';
    echo '<button type="submit" class="' . h($cls) . '"' . ($style ? ' style="' . h($style) . '"' : '') . '>' . h($label) . '</button></form>';
}

/** Reject form: a required "reason" field + red Reject button */
function reject_form($filters, $action, $uid, $extra, $placeholder, $label)
{
    echo '<form method="post" action="ngo-verification.php" style="display:flex;gap:8px;flex:1 1 100%;margin:0">'
       . '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '" />'
       . '<input type="hidden" name="action" value="' . h($action) . '" />'
       . '<input type="hidden" name="uid" value="' . (int)$uid . '" />';
    foreach (array_merge($filters, $extra) as $k => $v) echo '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '" />';
    echo '<input type="text" name="reason" class="form-input" placeholder="' . h($placeholder) . '" maxlength="500" required style="flex:1;min-width:0" />'
       . '<button type="submit" class="btn btn--primary btn--sm" style="background:var(--clr-danger);border-color:var(--clr-danger);white-space:nowrap">' . h($label) . '</button></form>';
}

/* ==================================================================
 * Secure document viewer: ?doc=ID  (files are never linked directly)
 * ================================================================== */
if (isset($_GET['doc'])) {
    $stmt = $pdo->prepare("select file_path from ngo_documents where id=?");
    $stmt->execute([(int)$_GET['doc']]);
    $stored = (string)$stmt->fetchColumn();
    $path = false;
    if ($UPLOAD_DIR && $stored !== '') {
        foreach ([ltrim(str_replace('\\', '/', $stored), '/'), basename($stored)] as $rel) {
            $try = realpath($UPLOAD_DIR . DIRECTORY_SEPARATOR . $rel);
            if ($try && strpos($try, $UPLOAD_DIR . DIRECTORY_SEPARATOR) === 0 && is_file($try)) { $path = $try; break; }
        }
        // file_path may also be stored as "uploads/ngo_documents/xyz.pdf" relative to the project root
        if (!$path) {
            $try = realpath(__DIR__ . '/../../' . ltrim(str_replace('\\', '/', $stored), '/'));
            if ($try && strpos($try, $UPLOAD_DIR . DIRECTORY_SEPARATOR) === 0 && is_file($try)) $path = $try;
        }
    }
    if (!$path) { http_response_code(404); exit('Document file not found on server.'); }

    // Throw away anything already printed; even one stray byte or warning corrupts a PDF.
    while (ob_get_level() > 0) ob_end_clean();
    @ini_set('zlib.output_compression', 'Off');
    session_write_close();

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    $inline = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode(basename($path)) . '"');
    readfile($path);
    exit;
}

/* ---------- list state ---------- */
$src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$status = (string)($src['status'] ?? 'pending');
if ($status !== 'all' && !in_array($status, $STATUSES, true)) $status = 'pending';
$q = mb_substr(trim((string)($src['q'] ?? '')), 0, 100);
$page = max(1, (int)($src['page'] ?? 1));
$filters = ['status' => $status, 'q' => $q, 'page' => $page];
$action = (string)($src['action'] ?? '');
$uid = (int)($src['uid'] ?? 0);

/* ==================================================================
 * POST: verify / reject documents, verify NGO, move NGO back to pending
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 500);
    $back_to_review = false;

    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        $_SESSION['flash'] = ['error', 'Invalid session token. Please try again.'];
    } elseif ($uid <= 0 || !in_array($action, ['verify_doc', 'reject_doc', 'reset_doc', 'verify_ngo', 'reject_ngo', 'reset_ngo'], true)
              || (substr($action, -4) === '_doc' && $doc_id <= 0)) {
        $_SESSION['flash'] = ['error', 'Invalid request.'];
    } elseif (in_array($action, ['reject_doc', 'reject_ngo'], true) && $reason === '') {
        // A reason is mandatory for every rejection
        $_SESSION['flash'] = ['error', 'Please enter a reason before rejecting.'];
        $back_to_review = true;
    } else {
        try {
            $pdo->beginTransaction();
            $msg = '';
            switch ($action) {
                case 'verify_doc':
                    $s = $pdo->prepare("update ngo_documents set status='Verified' where id=? and ngo_id=?");
                    $s->execute([$doc_id, $uid]);
                    $pdo->prepare("delete from ngo_document_admin_rejections where document_id=? and ngo_id=?")->execute([$doc_id, $uid]);
                    $msg = 'Document verified.'; $back_to_review = true; break;

                case 'reject_doc':
                    $pdo->prepare("update ngo_documents set status='Uploaded' where id=? and ngo_id=?")->execute([$doc_id, $uid]);
                    $pdo->prepare("delete from ngo_document_admin_rejections where document_id=? and ngo_id=?")->execute([$doc_id, $uid]);
                    $pdo->prepare("insert into ngo_document_admin_rejections (ngo_id, document_id, reason)
                                   select ngo_id, id, ? from ngo_documents where id=? and ngo_id=?")
                        ->execute([$reason, $doc_id, $uid]);
                    $msg = 'Document rejected.'; $back_to_review = true; break;

                case 'reject_ngo':
                    // Rejects every document of this NGO. Documents that were already rejected keep their own reason.
                    $s = $pdo->prepare("update ngo_documents set status='Uploaded' where ngo_id=?");
                    $s->execute([$uid]);
                    if ($s->rowCount() === 0) {
                        $c = $pdo->prepare("select count(*) from ngo_documents where ngo_id=?");
                        $c->execute([$uid]);
                        if ((int)$c->fetchColumn() === 0) throw new RuntimeException('no-docs');
                    }
                    $pdo->prepare("insert into ngo_document_admin_rejections (ngo_id, document_id, reason)
                                   select d.ngo_id, d.id, ? from ngo_documents d
                                   where d.ngo_id=? and not exists (select 1 from ngo_document_admin_rejections r where r.document_id = d.id)")
                        ->execute([$reason, $uid]);
                    $msg = 'NGO rejected. All its documents are marked as rejected.'; break;

                case 'reset_doc':
                    $pdo->prepare("update ngo_documents set status='Uploaded' where id=? and ngo_id=?")->execute([$doc_id, $uid]);
                    $pdo->prepare("delete from ngo_document_admin_rejections where document_id=? and ngo_id=?")->execute([$doc_id, $uid]);
                    $msg = 'Document moved back to review.'; $back_to_review = true; break;

                case 'verify_ngo':
                    $s = $pdo->prepare("update ngo_documents set status='Verified' where ngo_id=?");
                    $s->execute([$uid]);
                    if ($s->rowCount() === 0) throw new RuntimeException('no-docs');
                    $pdo->prepare("delete from ngo_document_admin_rejections where ngo_id=?")->execute([$uid]);
                    $msg = 'NGO verified. All its documents are now marked Verified.'; break;

                case 'reset_ngo':
                    $pdo->prepare("update ngo_documents set status='Uploaded' where ngo_id=?")->execute([$uid]);
                    $pdo->prepare("delete from ngo_document_admin_rejections where ngo_id=?")->execute([$uid]);
                    $msg = 'NGO moved back to pending.'; break;
            }
            // Keep ngo_master.status in sync (the food-donation pages only notify NGOs with status 'Verified').
            // An NGO is 'Verified' only when it has documents and every one of them is Verified.
            $chk = $pdo->prepare("select count(*) as total, coalesce(sum(status='Verified'),0) as ver from ngo_documents where ngo_id=?");
            $chk->execute([$uid]);
            $cnt = $chk->fetch(PDO::FETCH_ASSOC);
            $new_status = ((int)$cnt['total'] > 0 && (int)$cnt['ver'] === (int)$cnt['total']) ? 'Verified' : 'Unverified';
            $pdo->prepare("update ngo_master set status=? where id=?")->execute([$new_status, $uid]);

            $pdo->commit();
            $_SESSION['flash'] = ['success', $msg];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getMessage() === 'no-docs') {
                $_SESSION['flash'] = ['error', 'This NGO has no documents to ' . ($action === 'reject_ngo' ? 'reject.' : 'verify.')];
            } else {
                error_log('ngo-verification: ' . $e->getMessage());
                $_SESSION['flash'] = ['error', 'Could not save this change. Please try again.'];
            }
            $back_to_review = false;
        }
    }
    header('Location: ' . ($back_to_review ? list_url($filters, ['action' => 'review', 'uid' => $uid]) : list_url($filters)));
    exit;
}

/* ---------- review dialog data ---------- */
$record = null; $docs = [];
if ($action === 'review' && $uid > 0) {
    $stmt = $pdo->prepare("select * from ($BASE) x where x.id=?");
    $stmt->execute([$uid]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) { $_SESSION['flash'] = ['error', 'NGO not found.']; header('Location: ' . list_url($filters)); exit; }

    $stmt = $pdo->prepare("select d.id, d.document_name, d.file_path, d.uploaded_at, d.status,
            exists(select 1 from ngo_document_admin_rejections r where r.document_id = d.id) as rejected,
            (select r.reason from ngo_document_admin_rejections r where r.document_id = d.id order by r.id desc limit 1) as reject_reason
        from ngo_documents d where d.ngo_id=? order by d.uploaded_at desc, d.id desc");
    $stmt->execute([$uid]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

/* ---------- tab counts ---------- */
$counts = ['pending' => 0, 'verified' => 0, 'rejected' => 0];
foreach ($pdo->query("select vstatus s, count(*) c from ($BASE) x group by vstatus") as $r)
    if (isset($counts[$r['s']])) $counts[$r['s']] = (int)$r['c'];
$counts['all'] = array_sum($counts);

/* ---------- list ---------- */
$where = []; $params = [];
if ($status !== 'all') { $where[] = 'x.vstatus=?'; $params[] = $status; }
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(x.name like ? or x.email like ? or x.registration_no like ?)';
    array_push($params, $like, $like, $like);
}
$wsql = $where ? ' where ' . implode(' and ', $where) : '';

$stmt = $pdo->prepare("select count(*) from ($BASE) x" . $wsql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$perPage = 10;
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$filters['page'] = $page;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("select x.id, x.name, x.email, x.registration_no, x.registration_date, x.total, x.ver, x.rej, x.vstatus
    from ($BASE) x" . $wsql . " order by x.registration_date desc, x.id desc limit " . (int)$perPage . " offset " . (int)$offset);
$stmt->execute($params);
$page_start = $total ? $offset + 1 : 0;
$page_end = min($offset + $perPage, $total);
$filters_active = ($status !== 'pending' || $q !== '');
$colors = ['linear-gradient(135deg,#2dd4a8,#6ee7b7)', 'linear-gradient(135deg,#5eaaff,#93c5fd)', 'linear-gradient(135deg,#b47aff,#c4b5fd)', 'linear-gradient(135deg,#ff6b8a,#fda4af)'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NGO Verification — HelpSphere Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <script>
    (function () {
      var t = localStorage.getItem('hs-theme');
      if (t !== 'light' && t !== 'dark') t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>
<body>
  <div class="liquid-bg" aria-hidden="true">
    <div class="liquid-bg__orb liquid-bg__orb--1"></div><div class="liquid-bg__orb liquid-bg__orb--2"></div>
    <div class="liquid-bg__orb liquid-bg__orb--3"></div><div class="liquid-bg__orb liquid-bg__orb--4"></div>
    <div class="liquid-bg__orb liquid-bg__orb--5"></div><div class="liquid-bg__orb liquid-bg__orb--6"></div>
  </div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <div class="sidebar__logo">
        <span class="sidebar__logo-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        </span>
        <div><h1 class="sidebar__title">HelpSphere</h1><span class="sidebar__subtitle">Admin Portal</span></div>
      </div>
    </div>
    <nav class="sidebar__nav">
      <ul class="sidebar__menu">
        <li class="sidebar__menu-label">Main</li>
        <li><a href="index.php" class="sidebar__link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          <span>Dashboard</span></a></li>
        <li class="sidebar__menu-label">Management</li>
        <li><a href="add-ngo.php" class="sidebar__link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          <span>Add NGO</span></a></li>
        <li><a href="ngo-verification.php" class="sidebar__link active">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
          <span>NGO Verification</span></a></li>
        <li><a href="users.php" class="sidebar__link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>All Users</span></a></li>
        <li>
          <a href="../../inc/logout.php" class="sidebar__link" id="nav-all-users">
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </nav>
    <div class="sidebar__footer">
      <div class="sidebar__admin"><div class="sidebar__avatar">A</div>
        <div><p class="sidebar__admin-name">Admin User</p><p class="sidebar__admin-role">Super Admin</p></div></div>
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

    <section class="page active">
      <div class="page__header">
        <div>
          <h2 class="page__title">NGO Verification</h2>
          <p class="page__desc">Review the documents NGOs have submitted, then verify or reject them.</p>
        </div>
      </div>

      <div class="filters-bar">
        <div class="filter-tabs">
          <?php foreach (['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'all' => 'All NGOs'] as $k => $lbl): ?>
          <a class="filter-tab <?php if ($status === $k) echo 'active' ?>" href="<?php echo h(list_url($filters, ['status' => $k, 'page' => 1])) ?>"><?php echo $lbl ?> <span class="filter-tab__count"><?php echo $counts[$k] ?></span></a>
          <?php endforeach ?>
        </div>
        <form class="filters-bar__right" method="get" action="ngo-verification.php">
          <input type="hidden" name="status" value="<?php echo h($status) ?>" />
          <div class="filter-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Search name, email, reg. no..." class="filter-search__input" value="<?php echo h($q) ?>" maxlength="100" />
          </div>
          <button type="submit" class="btn btn--outline btn--sm">Apply</button>
          <?php if ($filters_active): ?><a class="btn btn--ghost btn--sm" href="ngo-verification.php">Reset</a><?php endif ?>
        </form>
      </div>

      <div class="table-wrapper">
        <table class="users-table">
          <thead>
            <tr><th>NGO</th><th>Registration No.</th><th>Documents</th><th>Registered</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php $i = 0; if ($total > 0): while ($n = $stmt->fetch(PDO::FETCH_ASSOC)):
              $st = $n['vstatus'];
              $waiting = (int)$n['total'] - (int)$n['ver'] - (int)$n['rej']; ?>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-cell__avatar" style="background:<?php echo $colors[$i++ % count($colors)] ?>"><?php echo h(mb_strtoupper(mb_substr((string)$n['name'], 0, 1)) ?: '?') ?></div>
                  <div>
                    <div class="user-cell__name"><?php echo h($n['name']) ?></div>
                    <div class="user-cell__id">HS-N<?php echo str_pad((string)$n['id'], 4, '0', STR_PAD_LEFT) ?></div>
                  </div>
                </div>
              </td>
              <td style="color:var(--text-secondary)"><?php echo h($n['registration_no']) ?: '—' ?></td>
              <td style="color:var(--text-secondary)">
                <?php if ((int)$n['total'] === 0): ?>None uploaded
                <?php else: echo (int)$n['ver'] . ' of ' . (int)$n['total'] . ' verified';
                    if ($waiting > 0) echo ' · ' . $waiting . ' to review';
                    if ((int)$n['rej'] > 0) echo ' · ' . (int)$n['rej'] . ' rejected'; endif ?>
              </td>
              <td style="color:var(--text-muted)"><?php echo h(nice_date($n['registration_date'])) ?></td>
              <td><span class="status-badge status-badge--<?php echo h($BADGE[$st]) ?>"><?php echo h(ucfirst($st)) ?></span></td>
              <td>
                <div class="action-btns">
                  <a class="btn btn--outline btn--sm" href="<?php echo h(list_url($filters, ['action' => 'review', 'uid' => $n['id']])) ?>">Review</a>
                </div>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="6" style="text-align:center;padding:40px 16px;color:var(--text-muted)">
              <?php echo $filters_active ? 'No NGOs match your filters.' : 'No NGOs are waiting for verification.' ?>
            </td></tr>
          <?php endif ?>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div class="pagination__info">Showing <strong><?php echo $page_start ?></strong>–<strong><?php echo $page_end ?></strong> of <strong><?php echo $total ?></strong> NGOs</div>
        <div class="pagination__controls">
          <?php if ($page > 1): ?><a class="pagination__btn" href="<?php echo h(list_url($filters, ['page' => $page - 1])) ?>" aria-label="Previous page">
          <?php else: ?><span class="pagination__btn" style="opacity:.4;pointer-events:none"><?php endif ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          <?php echo $page > 1 ? '</a>' : '</span>' ?>
          <div class="pagination__pages"><span class="pagination__page active"><?php echo $page ?> / <?php echo $totalPages ?></span></div>
          <?php if ($page < $totalPages): ?><a class="pagination__btn" href="<?php echo h(list_url($filters, ['page' => $page + 1])) ?>" aria-label="Next page">
          <?php else: ?><span class="pagination__btn" style="opacity:.4;pointer-events:none"><?php endif ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          <?php echo $page < $totalPages ? '</a>' : '</span>' ?>
        </div>
      </div>
    </section>
  </main>

  <?php if ($record):
      $st = $record['vstatus'];
      $close_url = list_url($filters);
      $field = function ($label, $val, $full = false) {
          echo '<div class="modal__field' . ($full ? ' modal__field--full' : '') . '"><span class="modal__field-label">' . h($label)
             . '</span><span class="modal__field-value">' . (trim((string)$val) !== '' ? h($val) : '—') . '</span></div>';
      };
  ?>
  <div class="modal open" role="dialog" aria-modal="true">
    <div class="modal__card">
      <a class="modal__close" href="<?php echo h($close_url) ?>" aria-label="Close">&#10005;</a>

      <div class="modal__head">
        <div class="modal__avatar" style="background:<?php echo $colors[0] ?>"><?php echo h(mb_strtoupper(mb_substr((string)$record['name'], 0, 1)) ?: '?') ?></div>
        <div>
          <div class="modal__name"><?php echo h($record['name']) ?></div>
          <div class="modal__id">HS-N<?php echo str_pad((string)$record['id'], 4, '0', STR_PAD_LEFT) ?> · <span class="status-badge status-badge--<?php echo h($BADGE[$st]) ?>"><?php echo h(ucfirst($st)) ?></span></div>
        </div>
      </div>

      <div class="modal__grid">
        <?php $field('Registration No.', $record['registration_no']);
              $field('Focus Area', $record['focus'] ?? '');
              $field('Email', $record['email'], true);
              $field('Phone', $record['phone'] ?? '');
              $field('Address', $record['address'] ?? '');
              $field('Description', $record['description'] ?? '', true); ?>
      </div>

      <h3 class="modal__title" style="margin:20px 0 10px;font-size:1rem">Submitted documents</h3>
      <?php if (!$docs): ?>
        <p style="color:var(--text-muted);margin:0 0 16px">This NGO has not uploaded any documents yet, so there is nothing to verify.</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">
        <?php foreach ($docs as $d):
            $ds = $d['status'] === 'Verified' ? 'verified' : ($d['rejected'] ? 'rejected' : 'pending'); ?>
          <div style="padding:10px 12px;border:1px solid var(--border,rgba(128,128,128,.25));border-radius:10px">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
              <div style="min-width:0">
                <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo h($d['document_name'] ?: 'Document') ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo h(basename((string)$d['file_path'])) ?> · <?php echo h(nice_date($d['uploaded_at'])) ?></div>
              </div>
              <span class="status-badge status-badge--<?php echo h($BADGE[$ds]) ?>"><?php echo $ds === 'pending' ? 'To review' : h(ucfirst($ds)) ?></span>
            </div>
            <?php if ($ds === 'rejected'): ?>
            <div style="margin-top:8px;font-size:.85rem;color:var(--clr-danger)">Reason: <?php echo trim((string)$d['reject_reason']) !== '' ? h($d['reject_reason']) : 'No reason recorded' ?></div>
            <?php endif ?>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px">
              <a class="btn btn--outline btn--sm" href="ngo-verification.php?doc=<?php echo (int)$d['id'] ?>" target="_blank" rel="noopener">Open</a>
              <?php if ($ds !== 'verified') act_form($filters, 'verify_doc', $record['id'], 'Verify', 'btn btn--primary btn--sm', ['doc_id' => $d['id']]); ?>
              <?php if ($ds !== 'pending') act_form($filters, 'reset_doc', $record['id'], 'Reset', 'btn btn--ghost btn--sm', ['doc_id' => $d['id']]); ?>
              <?php if ($ds !== 'rejected') reject_form($filters, 'reject_doc', $record['id'], ['doc_id' => $d['id']], 'Reason to reject this document (required)', 'Reject'); ?>
            </div>
          </div>
        <?php endforeach ?>
        </div>
      <?php endif ?>

      <?php if ((int)$record['total'] > (int)$record['rej']): ?>
      <div style="margin:0 0 16px;padding:12px;border:1px solid var(--clr-danger);border-radius:10px">
        <div style="font-size:.85rem;font-weight:600;margin-bottom:8px">Reject this NGO (rejects all its documents)</div>
        <div style="display:flex"><?php reject_form($filters, 'reject_ngo', $record['id'], [], 'Reason to reject this NGO (required)', 'Reject NGO'); ?></div>
      </div>
      <?php elseif ((int)$record['total'] === 0): ?>
      <p style="color:var(--text-muted);font-size:.85rem;margin:0 0 16px">An NGO can only be rejected once it has submitted at least one document.</p>
      <?php endif ?>

      <div class="modal__actions">
        <a class="btn btn--ghost" href="<?php echo h($close_url) ?>">Close</a>
        <?php if ((int)$record['ver'] > 0 || (int)$record['rej'] > 0) act_form($filters, 'reset_ngo', $record['id'], 'Move NGO to pending', 'btn btn--outline'); ?>
        <?php if ($st !== 'verified' && $docs) act_form($filters, 'verify_ngo', $record['id'], 'Verify NGO (all documents)', 'btn btn--primary'); ?>
      </div>
    </div>
  </div>
  <?php endif ?>

  <div class="toast-container" id="toast-container">
    <?php if ($flash): ?>
    <div class="toast toast--<?php echo $flash[0] === 'success' ? 'success' : 'error' ?>">
      <span class="toast__message"><?php echo h($flash[1]) ?></span>
      <a class="toast__close" href="<?php echo h(list_url($filters, $record ? ['action' => 'review', 'uid' => $record['id']] : [])) ?>" aria-label="Dismiss">&#10005;</a>
    </div>
    <?php endif ?>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var root = document.documentElement, t = document.getElementById('theme-toggle');
      if (t) t.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('hs-theme', next); } catch (e) {}
      });
      var sb = document.getElementById('sidebar'), ov = document.getElementById('overlay'), hm = document.getElementById('hamburger');
      if (hm) hm.addEventListener('click', function () { sb.classList.toggle('open'); ov.classList.toggle('show'); });
      if (ov) ov.addEventListener('click', function () { sb.classList.remove('open'); ov.classList.remove('show'); });
    });
  </script>
</body>
</html>