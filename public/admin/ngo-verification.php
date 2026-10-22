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
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NGO verification · Admin | HelpSphere</title>
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

    <span class="sidebar__tag">Admin console</span>

    <nav class="sidebar__nav" aria-label="Admin console">
      <a href="../admin/index.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../admin/users.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Users</a>
      <a href="../admin/ngo-verification.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>NGO verification</a>
      <a href="../admin/add-ngo.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user-plus"/></svg>Add NGO</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../admin/index.php">Admin console</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>NGO verification</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg></span>
          <div class="profile__text"><strong>Administrator</strong><span>Admin console</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">NGO management</p>
          <h1 class="page-head__title">NGO verification</h1>
          <p class="page-head__sub">Review the documents NGOs have submitted, then verify or reject them. Only verified NGOs receive immediate food alerts.</p>
        </div>
      </section>

      <section class="card reveal">
        <div class="filters">
          <nav class="filter-tabs" aria-label="Filter by status">
          <?php foreach (['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'all' => 'All NGOs'] as $k => $lbl): ?>
            <a class="filter-tab <?php if ($status === $k) echo 'active' ?>" href="<?php echo h(list_url($filters, ['status' => $k, 'page' => 1])) ?>"><?php echo $lbl ?> <span class="filter-tab__count"><?php echo $counts[$k] ?></span></a>
          <?php endforeach ?>
          </nav>
          <form method="get" action="ngo-verification.php">
            <input type="hidden" name="status" value="<?php echo h($status) ?>" />
            <label class="search"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-search"/></svg><span class="sr-only">Search NGOs</span>
              <input type="text" name="q" placeholder="Search name, email, reg. no…" value="<?php echo h($q) ?>" maxlength="100" />
            </label>
            <button type="submit" class="btn btn--outline btn--sm">Apply</button>
            <?php if ($filters_active): ?><a class="btn btn--ghost btn--sm" href="ngo-verification.php">Reset</a><?php endif ?>
          </form>
        </div>

        <div class="table-wrap">
          <table class="table table--stack">
            <thead>
              <tr><th>NGO</th><th>Registration no.</th><th>Documents</th><th>Registered</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
          <?php $i = 0; if ($total > 0): while ($n = $stmt->fetch(PDO::FETCH_ASSOC)):
              $st = $n['vstatus'];
              $waiting = (int)$n['total'] - (int)$n['ver'] - (int)$n['rej']; ?>
              <tr>
                <td>
                  <div class="who">
                    <span class="avatar avatar--blue"><?php echo h(mb_strtoupper(mb_substr((string)$n['name'], 0, 1)) ?: '?') ?></span>
                    <div>
                      <strong><?php echo h($n['name']) ?></strong>
                      <span class="muted">HS-N<?php echo str_pad((string)$n['id'], 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                  </div>
                </td>
                <td><?php echo h($n['registration_no']) ?: '—' ?></td>
                <td class="text-cell">
                <?php if ((int)$n['total'] === 0): ?>None uploaded
                <?php else: echo (int)$n['ver'] . ' of ' . (int)$n['total'] . ' verified';
                    if ($waiting > 0) echo ' · ' . $waiting . ' to review';
                    if ((int)$n['rej'] > 0) echo ' · ' . (int)$n['rej'] . ' rejected'; endif ?>
                </td>
                <td class="date-cell"><?php echo h(nice_date($n['registration_date'])) ?></td>
                <td><span class="status-badge status-badge--<?php echo h($BADGE[$st]) ?>"><?php echo h(ucfirst($st)) ?></span></td>
                <td>
                  <a class="act act--dark" href="<?php echo h(list_url($filters, ['action' => 'review', 'uid' => $n['id']])) ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Review</a>
                </td>
              </tr>
          <?php endwhile; else: ?>
              <tr><td colspan="6">
                <div class="empty"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg><strong><?php echo $filters_active ? 'No NGOs match your filters.' : 'No NGOs are waiting for verification.' ?></strong></div>
              </td></tr>
          <?php endif ?>
            </tbody>
          </table>
        </div>

        <div class="pagination">
          <div>Showing <strong><?php echo $page_start ?></strong>–<strong><?php echo $page_end ?></strong> of <strong><?php echo $total ?></strong> NGOs</div>
          <div class="pagination__controls">
          <?php if ($page > 1): ?><a class="pagination__btn" href="<?php echo h(list_url($filters, ['page' => $page - 1])) ?>" aria-label="Previous page">
          <?php else: ?><span class="pagination__btn is-disabled" aria-disabled="true"><?php endif ?>
            <svg class="icon"><use href="../assets/img/icons.svg#i-arrow-left"/></svg>
          <?php echo $page > 1 ? '</a>' : '</span>' ?>
            <div class="pagination__pages"><span class="pagination__page active"><?php echo $page ?> / <?php echo $totalPages ?></span></div>
          <?php if ($page < $totalPages): ?><a class="pagination__btn" href="<?php echo h(list_url($filters, ['page' => $page + 1])) ?>" aria-label="Next page">
          <?php else: ?><span class="pagination__btn is-disabled" aria-disabled="true"><?php endif ?>
            <svg class="icon"><use href="../assets/img/icons.svg#i-arrow"/></svg>
          <?php echo $page < $totalPages ? '</a>' : '</span>' ?>
          </div>
        </div>
      </section>

  <?php if ($record):
      $st = $record['vstatus'];
      $close_url = list_url($filters);
      $field = function ($label, $val, $full = false) {
          echo '<div class="modal__field' . ($full ? ' modal__field--full' : '') . '"><span class="modal__field-label">' . h($label)
             . '</span><span class="modal__field-value">' . (trim((string)$val) !== '' ? h($val) : '—') . '</span></div>';
      };
  ?>
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ngoDialogTitle">
        <div class="modal__box modal__box--wide">
          <div class="modal__head">
            <div class="who">
              <span class="avatar avatar--blue"><?php echo h(mb_strtoupper(mb_substr((string)$record['name'], 0, 1)) ?: '?') ?></span>
              <div>
                <h2 id="ngoDialogTitle"><?php echo h($record['name']) ?></h2>
                <p>HS-N<?php echo str_pad((string)$record['id'], 4, '0', STR_PAD_LEFT) ?> · <span class="status-badge status-badge--<?php echo h($BADGE[$st]) ?>"><?php echo h(ucfirst($st)) ?></span></p>
              </div>
            </div>
            <a class="icon-btn" href="<?php echo h($close_url) ?>" aria-label="Close"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></a>
          </div>
          <div class="modal__grid">
        <?php $field('Registration No.', $record['registration_no']);
              $field('Focus Area', $record['focus'] ?? '');
              $field('Email', $record['email'], true);
              $field('Phone', $record['phone'] ?? '');
              $field('Address', $record['address'] ?? '');
              $field('Description', $record['description'] ?? '', true); ?>
          </div>

          <h3 class="modal__title modal__title--spaced">Submitted documents</h3>
      <?php if (!$docs): ?>
          <p class="muted">This NGO has not uploaded any documents yet, so there is nothing to verify.</p>
      <?php else: ?>
          <div class="doc-review">
        <?php foreach ($docs as $d):
            $ds = $d['status'] === 'Verified' ? 'verified' : ($d['rejected'] ? 'rejected' : 'pending'); ?>
            <div class="doc-review__item">
              <div class="doc-review__top">
                <div>
                  <div class="doc-review__name"><?php echo h($d['document_name'] ?: 'Document') ?></div>
                  <div class="doc-review__meta"><?php echo h(basename((string)$d['file_path'])) ?> · <?php echo h(nice_date($d['uploaded_at'])) ?></div>
                </div>
                <span class="status-badge status-badge--<?php echo h($BADGE[$ds]) ?>"><?php echo $ds === 'pending' ? 'To review' : h(ucfirst($ds)) ?></span>
              </div>
            <?php if ($ds === 'rejected'): ?>
              <div class="doc-review__reason">Reason: <?php echo trim((string)$d['reject_reason']) !== '' ? h($d['reject_reason']) : 'No reason recorded' ?></div>
            <?php endif ?>
              <div class="doc-review__actions">
                <a class="btn btn--outline btn--sm" href="ngo-verification.php?doc=<?php echo (int)$d['id'] ?>" target="_blank" rel="noopener"><svg class="icon"><use href="../assets/img/icons.svg#i-external"/></svg>Open</a>
              <?php if ($ds !== 'verified') act_form($filters, 'verify_doc', $record['id'], 'Verify', 'btn btn--primary btn--sm', ['doc_id' => $d['id']]); ?>
              <?php if ($ds !== 'pending') act_form($filters, 'reset_doc', $record['id'], 'Reset', 'btn btn--ghost btn--sm', ['doc_id' => $d['id']]); ?>
              <?php if ($ds !== 'rejected') reject_form($filters, 'reject_doc', $record['id'], ['doc_id' => $d['id']], 'Reason to reject this document (required)', 'Reject'); ?>
              </div>
            </div>
        <?php endforeach ?>
          </div>
      <?php endif ?>
      <?php if ((int)$record['total'] > (int)$record['rej']): ?>
          <div class="danger-zone">
            <strong>Reject this NGO (rejects all its documents)</strong>
            <?php reject_form($filters, 'reject_ngo', $record['id'], [], 'Reason to reject this NGO (required)', 'Reject NGO'); ?>
          </div>
      <?php elseif ((int)$record['total'] === 0): ?>
          <p class="muted">An NGO can only be rejected once it has submitted at least one document.</p>
      <?php endif ?>
          <div class="modal__actions">
            <a class="btn btn--ghost btn--sm" href="<?php echo h($close_url) ?>">Close</a>
        <?php if ((int)$record['ver'] > 0 || (int)$record['rej'] > 0) act_form($filters, 'reset_ngo', $record['id'], 'Move NGO to pending', 'btn btn--outline'); ?>
        <?php if ($st !== 'verified' && $docs) act_form($filters, 'verify_ngo', $record['id'], 'Verify NGO (all documents)', 'btn btn--primary'); ?>
          </div>
        </div>
      </div>
  <?php endif ?>
      <div class="toast-stack" id="toast-container" role="status" aria-live="polite">
    <?php if ($flash): ?>
        <div class="toast toast--<?php echo $flash[0] === 'success' ? 'success' : 'error' ?>">
          <svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>
          <span><?php echo h($flash[1]) ?></span>
          <a class="icon-btn" href="<?php echo h(list_url($filters, $record ? ['action' => 'review', 'uid' => $record['id']] : [])) ?>" aria-label="Dismiss"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></a>
        </div>
    <?php endif ?>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
