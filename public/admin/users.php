<?php
require_once __DIR__. '/../../inc/auth.php';
admin_require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

/*
 * Role configuration (based on your actual tables).
 * Each editable field: 'column' => [label, type, max length OR list of options, required]
 * type: text | email | date | select | textarea
 * Passwords are never shown or edited here.
 */
$ROLES=[
    'beneficiary'=>[
        'table'=>'beneficiary_master',
        'prefix'=>'B',
        'label'=>'Beneficiary',
        'location'=>'address',
        'joined'=>'created_at',
        'fields'=>[
            'name'=>['Full Name','text',255,true],
            'email'=>['Email Address','email',255,true],
            'phone'=>['Phone','text',20,true],
            'address'=>['Address','text',255,true],
            'dob'=>['Date of Birth','date',10,true],
            'gender'=>['Gender','select',['Male','Female','Other'],true],
        ],
        'extra'=>['last_login'=>'Last Login'],
    ],
    'donor'=>[
        'table'=>'donor_master',
        'prefix'=>'D',
        'label'=>'Donor',
        'location'=>'location',
        'joined'=>null,
        'fields'=>[
            'name'=>['Full Name','text',255,true],
            'email'=>['Email Address','email',255,true],
            'phone'=>['Phone','text',255,true],
            'location'=>['Location','text',255,true],
            'preferred_type'=>['Preferred Donation Type','select',['Food','Money','Resources','Blood'],true],
            'preferred_ngo_cause'=>['Preferred NGO Cause','text',255,false],
        ],
        'extra'=>[],
    ],
    'ngo'=>[
        'table'=>'ngo_master',
        'prefix'=>'N',
        'label'=>'NGO',
        'location'=>'address',
        'joined'=>'registration_date',
        'fields'=>[
            'name'=>['NGO Name','text',255,true],
            'email'=>['Email Address','email',255,true],
            'phone'=>['Phone','text',255,true],
            'address'=>['Address','text',255,true],
            'focus'=>['Focus Area','text',255,true],
            'registration_no'=>['Registration No.','text',255,true],
            'description'=>['Description','textarea',255,false],
        ],
        'extra'=>[],
    ],
];

/* ---------- helpers ---------- */
if(!function_exists('h'))
{
    function h($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

function list_url($f, $over=[])
{
    $p=array_merge($f, $over);
    if(isset($p['type']) && $p['type']==='all')   
      unset($p['type']);
    if(isset($p['q']) && $p['q']==='')            
      unset($p['q']);
    if(isset($p['page']) && (int)$p['page']<=1)   
      unset($p['page']);
    return 'users.php'.($p ? '?'.http_build_query($p) : '');
}

function nice_date($d)
{
    if(empty($d)) 
      return '—';
    $t=strtotime($d);
    return $t ? date('d M Y', $t) : '—';
}

/* ---------- current list state (filters) ---------- */
$src=($_SERVER['REQUEST_METHOD']==='POST') ? $_POST : $_GET;

$type=$src['type'] ?? 'all';
if($type!=='all' && !isset($ROLES[$type]))
    $type='all';

$q=mb_substr(trim((string)($src['q'] ?? '')), 0, 100);
$page=max(1, (int)($src['page'] ?? 1));

$filters=['type'=>$type,'q'=>$q,'page'=>$page];

$action=(string)($src['action'] ?? '');
$utype=(string)($src['utype'] ?? '');
$uid=(int)($src['uid'] ?? 0);

$posted=null;
$form_errors=[];

/* ==================================================================
 * POST: update / delete
 * ================================================================== */
if($_SERVER['REQUEST_METHOD']==='POST')
{
    if(!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? '')))
    {
        $_SESSION['flash']=['error','Invalid session token. Please try again.'];
        header('Location: '.list_url($filters));
        exit;
    }

    if(!isset($ROLES[$utype]) || $uid<=0)
    {
        $_SESSION['flash']=['error','Invalid request.'];
        header('Location: '.list_url($filters));
        exit;
    }

    $cfg=$ROLES[$utype];

    /* ----- DELETE ----- */
    if($action==='delete')
    {
        try
        {
          $pdo->beginTransaction();

          $stmt=$pdo->prepare("delete from ".$cfg['table']." where id=?");
          $stmt->execute([$uid]);

          if($stmt->rowCount()>0)
          {
              $stmt=$pdo->prepare("delete from users where role_id=? and role=?");
              $stmt->execute([$uid, $cfg['label']]);

              $pdo->commit();
              $_SESSION['flash']=['success', $cfg['label'].' deleted successfully.'];
          }
          else
          {
              $pdo->rollBack();
              $_SESSION['flash']=['error','User not found.'];
          }
      }
      catch(PDOException $e)
      {
          if($pdo->inTransaction()) $pdo->rollBack();
          error_log('users.php delete: '.$e->getMessage());
          $_SESSION['flash']=['error','Could not delete this user. It may be linked to other records (e.g. assistance requests).'];
      }
        header('Location: '.list_url($filters));
        exit;
    }

    /* ----- UPDATE ----- */
    if($action==='update')
    {
        $posted=[];
        foreach($cfg['fields'] as $col=>$def)
        {
            $label=$def[0]; 
            $ftype=$def[1]; 
            $spec=$def[2]; 
            $req=$def[3];
            $val=trim((string)($_POST[$col] ?? ''));
            $posted[$col]=$val;

            if($val==='')
            {
                if($req) 
                  $form_errors[]=$label.' is required.';
                continue;
            }
            if($ftype==='select')
            {
                if(!in_array($val, $spec, true)) 
                  $form_errors[]='Invalid value for '.$label.'.';
            }
            elseif($ftype==='email')
            {
                if(!filter_var($val, FILTER_VALIDATE_EMAIL)) 
                  $form_errors[]='Enter a valid email address.';
                elseif(mb_strlen($val)>$spec) 
                  $form_errors[]=$label.' is too long.';
            }
            elseif($ftype==='date')
            {
                $d=DateTime::createFromFormat('Y-m-d', $val);
                if(!$d || $d->format('Y-m-d')!==$val) 
                  $form_errors[]='Enter a valid '.$label.'.';
            }
            else
            {
                if(mb_strlen($val)>$spec) 
                  $form_errors[]=$label.' must be at most '.$spec.' characters.';
            }
        }

        if(empty($form_errors))
        {
            $set=[]; 
            $params=[];
            foreach($posted as $col=>$val)
            {
                $set[]=$col."=?";
                $params[]=$val;
            }
            $params[]=$uid;
            try
{
    $pdo->beginTransaction();

    $stmt=$pdo->prepare("update ".$cfg['table']." set ".implode(', ', $set)." where id=?");
    $stmt->execute($params);

    $stmt=$pdo->prepare("update users set name=?, email=? where role_id=? and role=?");
    $stmt->execute([$posted['name'], $posted['email'], $uid, $cfg['label']]);

    $pdo->commit();
    $_SESSION['flash']=['success', $cfg['label'].' updated successfully.'];
    header('Location: '.list_url($filters));
    exit;
}
catch(PDOException $e)
{
    if($pdo->inTransaction()) $pdo->rollBack();
    error_log('users.php update: '.$e->getMessage());
    if($e->getCode()==='23000')
        $form_errors[]='That email address is already in use.';
    else
        $form_errors[]='Could not save changes. Please try again.';
}
        }
        $action='edit'; // re-show the form with the errors
    }
}

/* ==================================================================
 * Load a single record for view / edit / delete-confirm
 * ================================================================== */
$record=null;
if(in_array($action, ['view','edit','delete'], true))
{
    if(isset($ROLES[$utype]) && $uid>0)
    {
        $stmt=$pdo->prepare("select * from ".$ROLES[$utype]['table']." where id=?");
        $stmt->bindParam(1, $uid);
        $stmt->execute();
        if($stmt->rowCount()>0)
            $record=$stmt->fetch(PDO::FETCH_ASSOC);
    }
    if(!$record)
    {
        $_SESSION['flash']=['error','User not found.'];
        header('Location: '.list_url($filters));
        exit;
    }
}

/* ---------- flash message ---------- */
$flash=null;
if(!empty($_SESSION['flash']))
{
    $flash=$_SESSION['flash'];
    unset($_SESSION['flash']);
}

/* ==================================================================
 * Tab counts (whole tables)
 * ================================================================== */
$stmt=$pdo->prepare("select count(*) from beneficiary_master");
$stmt->execute();
$c=(int)$stmt->fetchColumn();

$stmt1=$pdo->prepare("select count(*) from ngo_master");
$stmt1->execute();
$c1=(int)$stmt1->fetchColumn();

$stmt2=$pdo->prepare("select count(*) from donor_master");
$stmt2->execute();
$c2=(int)$stmt2->fetchColumn();

/* ==================================================================
 * User list (search + role + pagination, all in SQL)
 * ================================================================== */
$base="select id, name, email, 'beneficiary' as role, address as location, created_at as joined from beneficiary_master
       union all
       select id, name, email, 'donor', location, null from donor_master
       union all
       select id, name, email, 'ngo', address, registration_date from ngo_master";

$where=[]; $params=[];
if($type!=='all')
{
    $where[]="role=?";
    $params[]=$type;
}
if($q!=='')
{
    $like='%'.addcslashes($q, '%_\\').'%';
    $where[]="(name like ? or email like ? or location like ?)";
    array_push($params, $like, $like, $like);
}
$wsql=$where ? " where ".implode(' and ', $where) : "";

$stmt=$pdo->prepare("select count(*) from ($base) u".$wsql);
$stmt->execute($params);
$total=(int)$stmt->fetchColumn();

$perPage=10;
$totalPages=max(1, (int)ceil($total/$perPage));
if($page>$totalPages) $page=$totalPages;
$filters['page']=$page;
$offset=($page-1)*$perPage;

$stmt=$pdo->prepare("select * from ($base) u".$wsql." order by joined desc, name asc, role asc, id asc limit ".(int)$perPage." offset ".(int)$offset);
$stmt->execute($params);
$page_start=$total===0 ? 0 : $offset+1;
$page_end=min($offset+$perPage, $total);

$avatarColors=[
    'linear-gradient(135deg,#2dd4a8,#6ee7b7)',
    'linear-gradient(135deg,#5eaaff,#93c5fd)',
    'linear-gradient(135deg,#b47aff,#c4b5fd)',
    'linear-gradient(135deg,#ff6b8a,#fda4af)',
    'linear-gradient(135deg,#f59e0b,#fcd34d)',
    'linear-gradient(135deg,#06b6d4,#67e8f9)',
];

/* pagination page numbers */
$pages=[];
if($totalPages<=7)
{
    for($i=1; $i<=$totalPages; $i++) $pages[]=$i;
}
else
{
    $pages[]=1;
    if($page>3) $pages[]='...';
    for($i=max(2,$page-1); $i<=min($totalPages-1,$page+1); $i++) $pages[]=$i;
    if($page<$totalPages-2) $pages[]='...';
    $pages[]=$totalPages;
}

$filters_active=($type!=='all' || $q!=='');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Users — HelpSphere Admin</title>
  <meta name="description" content="Manage all beneficiaries, NGOs, and donors on the HelpSphere platform." />
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
          <a href="add-ngo.php" class="sidebar__link" id="nav-add-ngo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <span>Add NGO</span>
          </a>
        </li>
        <li>
          <a href="users.php" class="sidebar__link active" id="nav-all-users">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>All Users</span>
          </a>
        </li>
        <li><a href="ngo-verification.php" class="sidebar__link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
          <span>NGO Verification</span></a></li>
        <li>
          <a href="../../inc/logout.php" class="sidebar__link" id="nav-logout">
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

    <section class="page active" id="page-all-users">
      <div class="page__header">
        <div>
          <h2 class="page__title">All Users</h2>
          <p class="page__desc">Manage beneficiaries, NGOs, and donors across the platform.</p>
        </div>
      </div>

      <div class="filters-bar">
        <div class="filter-tabs">
          <a class="filter-tab <?php if($type==='all') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'all','page'=>1])) ?>">All Users <span class="filter-tab__count"><?php echo ($c + $c1 + $c2) ?></span></a>
          <a class="filter-tab <?php if($type==='beneficiary') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'beneficiary','page'=>1])) ?>">Beneficiaries <span class="filter-tab__count"><?php echo $c ?></span></a>
          <a class="filter-tab <?php if($type==='ngo') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'ngo','page'=>1])) ?>">NGOs <span class="filter-tab__count"><?php echo $c1 ?></span></a>
          <a class="filter-tab <?php if($type==='donor') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'donor','page'=>1])) ?>">Donors <span class="filter-tab__count"><?php echo $c2 ?></span></a>
        </div>
        <form class="filters-bar__right" method="get" action="users.php">
          <input type="hidden" name="type" value="<?php echo h($type) ?>" />
          <div class="filter-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Search name, email, location..." class="filter-search__input" id="user-search" value="<?php echo h($q) ?>" maxlength="100" />
          </div>
          <button type="submit" class="btn btn--outline btn--sm">Apply</button>
          <?php if($filters_active): ?>
          <a class="btn btn--ghost btn--sm" href="users.php">Reset</a>
          <?php endif ?>
        </form>
      </div>

      <div class="table-wrapper">
        <table class="users-table" id="users-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Role</th>
              <th>Email</th>
              <th>Location</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="users-tbody">
          <?php
          $i=0;
          if($total > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $role=$rec['role'];
              $name=$rec['name'];
              $display_id='HS-'.$ROLES[$role]['prefix'].str_pad((string)$rec['id'], 4, '0', STR_PAD_LEFT);
              $letter=mb_strtoupper(mb_substr((string)$name, 0, 1)) ?: '?';
              $color=$avatarColors[$i % count($avatarColors)];
              $i++;
              ?>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-cell__avatar" style="background:<?php echo $color ?>"><?php echo h($letter) ?></div>
                  <div>
                    <div class="user-cell__name"><?php echo h($name) ?></div>
                    <div class="user-cell__id"><?php echo h($display_id) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="role-badge role-badge--<?php echo h($role) ?>"><?php echo h(ucfirst($role)) ?></span></td>
              <td style="color:var(--text-secondary)"><?php echo h($rec['email']) ?></td>
              <td style="color:var(--text-secondary)"><?php echo $rec['location']!=='' && $rec['location']!==null ? h($rec['location']) : '—' ?></td>
              <td style="color:var(--text-muted)"><?php echo h(nice_date($rec['joined'])) ?></td>
              <td>
                <div class="action-btns">
                  <a class="action-btn" href="<?php echo h(list_url($filters, ['action'=>'view','utype'=>$role,'uid'=>$rec['id']])) ?>" title="View" aria-label="View <?php echo h($name) ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <a class="action-btn" href="<?php echo h(list_url($filters, ['action'=>'edit','utype'=>$role,'uid'=>$rec['id']])) ?>" title="Edit" aria-label="Edit <?php echo h($name) ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <a class="action-btn action-btn--danger" href="<?php echo h(list_url($filters, ['action'=>'delete','utype'=>$role,'uid'=>$rec['id']])) ?>" title="Delete" aria-label="Delete <?php echo h($name) ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </a>
                </div>
              </td>
            </tr>
              <?php
            }
          }
          else
          {
            ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:40px 16px;color:var(--text-muted)">
                <?php echo $filters_active ? 'No users match your filters.' : 'No users found.' ?>
              </td>
            </tr>
            <?php
          }
          ?>
          </tbody>
        </table>
      </div>

      <div class="pagination" id="pagination">
        <div class="pagination__info">
          Showing <strong><?php echo $page_start ?></strong>–<strong><?php echo $page_end ?></strong> of <strong><?php echo $total ?></strong> users
        </div>
        <div class="pagination__controls">
          <?php if($page>1): ?>
          <a class="pagination__btn" href="<?php echo h(list_url($filters, ['page'=>$page-1])) ?>" aria-label="Previous page">
          <?php else: ?>
          <span class="pagination__btn" style="opacity:.4;pointer-events:none" aria-disabled="true">
          <?php endif ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          <?php echo $page>1 ? '</a>' : '</span>' ?>

          <div class="pagination__pages" id="page-numbers">
            <?php foreach($pages as $p): ?>
              <?php if($p==='...'): ?>
              <span class="pagination__page" style="pointer-events:none;opacity:.4">…</span>
              <?php else: ?>
              <a class="pagination__page <?php if($p===$page) echo 'active' ?>" href="<?php echo h(list_url($filters, ['page'=>$p])) ?>"><?php echo $p ?></a>
              <?php endif ?>
            <?php endforeach ?>
          </div>

          <?php if($page<$totalPages): ?>
          <a class="pagination__btn" href="<?php echo h(list_url($filters, ['page'=>$page+1])) ?>" aria-label="Next page">
          <?php else: ?>
          <span class="pagination__btn" style="opacity:.4;pointer-events:none" aria-disabled="true">
          <?php endif ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          <?php echo $page<$totalPages ? '</a>' : '</span>' ?>
        </div>
      </div>
    </section>
  </main>

  <?php
  /* ============================================================
   * Server-rendered dialogs: view / edit / delete confirmation
   * ============================================================ */
  if($record):
      $cfg=$ROLES[$utype];
      $r_name=$record['name'];
      $r_id='HS-'.$cfg['prefix'].str_pad((string)$record['id'], 4, '0', STR_PAD_LEFT);
      $r_letter=mb_strtoupper(mb_substr((string)$r_name, 0, 1)) ?: '?';
      $close_url=list_url($filters);
  ?>
  <div class="modal open" role="dialog" aria-modal="true">
    <div class="modal__card">
      <a class="modal__close" href="<?php echo h($close_url) ?>" aria-label="Close">&#10005;</a>

      <?php if($action==='view'): ?>

      <div class="modal__head">
        <div class="modal__avatar" style="background:<?php echo $avatarColors[0] ?>"><?php echo h($r_letter) ?></div>
        <div>
          <div class="modal__name"><?php echo h($r_name) ?></div>
          <div class="modal__id"><?php echo h($r_id) ?></div>
        </div>
      </div>
      <div class="modal__grid">
        <div class="modal__field">
          <span class="modal__field-label">Role</span>
          <span class="modal__field-value"><span class="role-badge role-badge--<?php echo h($utype) ?>"><?php echo h($cfg['label']) ?></span></span>
        </div>
        <?php foreach($cfg['fields'] as $col=>$def): ?>
          <?php if($col==='name') continue; ?>
        <div class="modal__field <?php if($def[1]==='email' || $def[1]==='textarea') echo 'modal__field--full' ?>">
          <span class="modal__field-label"><?php echo h($def[0]) ?></span>
          <span class="modal__field-value"><?php echo $record[$col]!=='' && $record[$col]!==null ? h($record[$col]) : '—' ?></span>
        </div>
        <?php endforeach ?>
        <?php if($cfg['joined']): ?>
        <div class="modal__field">
          <span class="modal__field-label">Joined</span>
          <span class="modal__field-value"><?php echo h(nice_date($record[$cfg['joined']])) ?></span>
        </div>
        <?php endif ?>
        <?php foreach($cfg['extra'] as $col=>$label): ?>
        <div class="modal__field">
          <span class="modal__field-label"><?php echo h($label) ?></span>
          <span class="modal__field-value"><?php echo !empty($record[$col]) ? h(nice_date($record[$col])) : '—' ?></span>
        </div>
        <?php endforeach ?>
      </div>
      <div class="modal__actions">
        <a class="btn btn--ghost" href="<?php echo h($close_url) ?>">Close</a>
        <a class="btn btn--primary" href="<?php echo h(list_url($filters, ['action'=>'edit','utype'=>$utype,'uid'=>$record['id']])) ?>">Edit User</a>
      </div>

      <?php elseif($action==='edit'): ?>

      <h3 class="modal__title">Edit <?php echo h($r_name) ?></h3>
      <?php if(!empty($form_errors)): ?>
      <div style="margin:0 0 16px;padding:12px 14px;border-radius:10px;border:1px solid var(--clr-danger);color:var(--clr-danger);font-size:.9rem;line-height:1.5">
        <?php foreach($form_errors as $err): ?>
          <div><?php echo h($err) ?></div>
        <?php endforeach ?>
      </div>
      <?php endif ?>
      <form method="post" action="users.php" class="form-grid">
        <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']) ?>" />
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="utype" value="<?php echo h($utype) ?>" />
        <input type="hidden" name="uid" value="<?php echo (int)$record['id'] ?>" />
        <?php foreach($filters as $k=>$v): ?>
        <input type="hidden" name="<?php echo h($k) ?>" value="<?php echo h($v) ?>" />
        <?php endforeach ?>

        <?php foreach($cfg['fields'] as $col=>$def):
            $val=($posted!==null) ? ($posted[$col] ?? '') : (string)$record[$col];
            $full=in_array($col, ['name','email','description'], true);
        ?>
        <div class="form-group <?php if($full) echo 'form-group--full' ?>">
          <label class="form-label" for="edit-<?php echo h($col) ?>"><?php echo h($def[0]) ?></label>
          <?php if($def[1]==='select'): ?>
          <select class="form-input form-select" id="edit-<?php echo h($col) ?>" name="<?php echo h($col) ?>">
            <?php foreach($def[2] as $opt): ?>
            <option value="<?php echo h($opt) ?>" <?php if($val===$opt) echo 'selected' ?>><?php echo h($opt) ?></option>
            <?php endforeach ?>
          </select>
          <?php elseif($def[1]==='textarea'): ?>
          <textarea class="form-input" id="edit-<?php echo h($col) ?>" name="<?php echo h($col) ?>" rows="3" maxlength="<?php echo (int)$def[2] ?>"><?php echo h($val) ?></textarea>
          <?php else: ?>
          <input class="form-input" id="edit-<?php echo h($col) ?>" name="<?php echo h($col) ?>" type="<?php echo h($def[1]) ?>" value="<?php echo h($val) ?>" <?php if($def[1]!=='date') echo 'maxlength="'.(int)$def[2].'"' ?> <?php if($def[3]) echo 'required' ?> />
          <?php endif ?>
        </div>
        <?php endforeach ?>

        <div class="form-group">
          <label class="form-label" for="edit-role">Role</label>
          <input class="form-input" id="edit-role" value="<?php echo h($cfg['label']) ?>" disabled />
        </div>

        <div class="modal__actions form-group--full">
          <a class="btn btn--ghost" href="<?php echo h($close_url) ?>">Cancel</a>
          <button type="submit" class="btn btn--primary">Save Changes</button>
        </div>
      </form>

      <?php elseif($action==='delete'): ?>

      <h3 class="modal__title">Delete user?</h3>
      <p style="color:var(--text-secondary);margin:8px 0 20px;line-height:1.5">
        This will permanently delete <strong><?php echo h($r_name) ?></strong> (<?php echo h($r_id) ?>). This cannot be undone.
      </p>
      <form method="post" action="users.php">
        <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']) ?>" />
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="utype" value="<?php echo h($utype) ?>" />
        <input type="hidden" name="uid" value="<?php echo (int)$record['id'] ?>" />
        <?php foreach($filters as $k=>$v): ?>
        <input type="hidden" name="<?php echo h($k) ?>" value="<?php echo h($v) ?>" />
        <?php endforeach ?>
        <div class="modal__actions">
          <a class="btn btn--ghost" href="<?php echo h($close_url) ?>">Cancel</a>
          <button type="submit" class="btn btn--primary" style="background:var(--clr-danger);border-color:var(--clr-danger)">Delete</button>
        </div>
      </form>

      <?php endif ?>
    </div>
  </div>
  <?php endif ?>

  <div class="toast-container" id="toast-container">
    <?php if($flash): ?>
    <div class="toast toast--<?php echo $flash[0]==='success' ? 'success' : 'error' ?>">
      <span class="toast__message"><?php echo h($flash[1]) ?></span>
      <a class="toast__close" href="<?php echo h(list_url($filters)) ?>" aria-label="Dismiss">&#10005;</a>
    </div>
    <?php endif ?>
  </div>

  <!-- UI only (theme + mobile sidebar). All user CRUD is handled by PHP above. -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var root = document.documentElement;
      var t = document.getElementById('theme-toggle');
      if (t) t.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('hs-theme', next); } catch (e) {}
      });
      var sidebar = document.getElementById('sidebar');
      var overlay = document.getElementById('overlay');
      var ham = document.getElementById('hamburger');
      if (ham) ham.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
      });
      if (overlay) overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
      });
    });
  </script>
</body>
</html>
