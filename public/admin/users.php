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
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Users · Admin | HelpSphere</title>
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
      <a href="../admin/users.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Users</a>
      <a href="../admin/ngo-verification.php"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>NGO verification</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../admin/index.php">Admin console</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Users</strong></nav>
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
          <p class="page-head__eyebrow">User management</p>
          <h1 class="page-head__title">All users</h1>
          <p class="page-head__sub">Manage beneficiaries, NGOs and donors across the platform. Passwords are never shown or edited here.</p>
        </div>
      </section>

      <section class="card reveal">
        <div class="filters">
          <nav class="filter-tabs" aria-label="Filter by role">
            <a class="filter-tab <?php if($type==='all') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'all','page'=>1])) ?>">All users <span class="filter-tab__count"><?php echo ($c + $c1 + $c2) ?></span></a>
            <a class="filter-tab <?php if($type==='beneficiary') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'beneficiary','page'=>1])) ?>">Beneficiaries <span class="filter-tab__count"><?php echo $c ?></span></a>
            <a class="filter-tab <?php if($type==='ngo') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'ngo','page'=>1])) ?>">NGOs <span class="filter-tab__count"><?php echo $c1 ?></span></a>
            <a class="filter-tab <?php if($type==='donor') echo 'active' ?>" href="<?php echo h(list_url($filters, ['type'=>'donor','page'=>1])) ?>">Donors <span class="filter-tab__count"><?php echo $c2 ?></span></a>
          </nav>
          <form method="get" action="users.php">
            <input type="hidden" name="type" value="<?php echo h($type) ?>" />
            <label class="search"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-search"/></svg><span class="sr-only">Search users</span>
              <input type="text" name="q" placeholder="Search name, email, location…" id="user-search" value="<?php echo h($q) ?>" maxlength="100" />
            </label>
            <button type="submit" class="btn btn--outline btn--sm">Apply</button>
            <?php if($filters_active): ?>
            <a class="btn btn--ghost btn--sm" href="users.php">Reset</a>
            <?php endif ?>
          </form>
        </div>

        <div class="table-wrap">
          <table class="table table--stack" id="users-table">
            <thead>
              <tr><th>User</th><th>Role</th><th>Email</th><th>Location</th><th>Joined</th><th>Actions</th></tr>
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
                  <div class="who">
                    <span class="avatar"><?php echo h($letter) ?></span>
                    <div>
                      <strong><?php echo h($name) ?></strong>
                      <span class="muted"><?php echo h($display_id) ?></span>
                    </div>
                  </div>
                </td>
                <td><span class="role-badge role-badge--<?php echo h($role) ?>"><?php echo h(ucfirst($role)) ?></span></td>
                <td class="text-cell"><?php echo h($rec['email']) ?></td>
                <td class="text-cell"><?php echo $rec['location']!=='' && $rec['location']!==null ? h($rec['location']) : '—' ?></td>
                <td class="date-cell"><?php echo h(nice_date($rec['joined'])) ?></td>
                <td>
                  <div class="actions">
                    <a class="act act--plain" href="<?php echo h(list_url($filters, ['action'=>'view','utype'=>$role,'uid'=>$rec['id']])) ?>" title="View" aria-label="View <?php echo h($name) ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-eye"/></svg></a>
                    <a class="act act--info" href="<?php echo h(list_url($filters, ['action'=>'edit','utype'=>$role,'uid'=>$rec['id']])) ?>" title="Edit" aria-label="Edit <?php echo h($name) ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-edit"/></svg></a>
                    <a class="act act--no" href="<?php echo h(list_url($filters, ['action'=>'delete','utype'=>$role,'uid'=>$rec['id']])) ?>" title="Delete" aria-label="Delete <?php echo h($name) ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-trash"/></svg></a>
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
                <td colspan="6">
                  <div class="empty"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg><strong><?php echo $filters_active ? 'No users match your filters.' : 'No users found.' ?></strong></div>
                </td>
              </tr>
            <?php
          }
          ?>
            </tbody>
          </table>
        </div>

        <div class="pagination" id="pagination">
          <div>
            Showing <strong><?php echo $page_start ?></strong>–<strong><?php echo $page_end ?></strong> of <strong><?php echo $total ?></strong> users
          </div>
          <div class="pagination__controls">
          <?php if($page>1): ?>
          <a class="pagination__btn" href="<?php echo h(list_url($filters, ['page'=>$page-1])) ?>" aria-label="Previous page">
          <?php else: ?>
          <span class="pagination__btn is-disabled" aria-disabled="true">
          <?php endif ?>
            <svg class="icon"><use href="../assets/img/icons.svg#i-arrow-left"/></svg>
          <?php echo $page>1 ? '</a>' : '</span>' ?>
            <div class="pagination__pages" id="page-numbers">
            <?php foreach($pages as $p): ?>
              <?php if($p==='...'): ?>
              <span class="pagination__page is-disabled">…</span>
              <?php else: ?>
              <a class="pagination__page <?php if($p===$page) echo 'active' ?>" href="<?php echo h(list_url($filters, ['page'=>$p])) ?>"><?php echo $p ?></a>
              <?php endif ?>
            <?php endforeach ?>
            </div>
          <?php if($page<$totalPages): ?>
          <a class="pagination__btn" href="<?php echo h(list_url($filters, ['page'=>$page+1])) ?>" aria-label="Next page">
          <?php else: ?>
          <span class="pagination__btn is-disabled" aria-disabled="true">
          <?php endif ?>
            <svg class="icon"><use href="../assets/img/icons.svg#i-arrow"/></svg>
          <?php echo $page<$totalPages ? '</a>' : '</span>' ?>
          </div>
        </div>
      </section>

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
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="userDialogTitle">
        <div class="modal__box">
          <div class="modal__head">
            <div class="who">
              <span class="avatar avatar--brand"><?php echo h($r_letter) ?></span>
              <div>
                <h2 id="userDialogTitle"><?php echo h($r_name) ?></h2>
                <p><?php echo h($r_id) ?></p>
              </div>
            </div>
            <a class="icon-btn" href="<?php echo h($close_url) ?>" aria-label="Close"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></a>
          </div>
      <?php if($action==='view'): ?>
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
            <a class="btn btn--ghost btn--sm" href="<?php echo h($close_url) ?>">Close</a>
            <a class="btn btn--dark btn--sm" href="<?php echo h(list_url($filters, ['action'=>'edit','utype'=>$utype,'uid'=>$record['id']])) ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-edit"/></svg>Edit user</a>
          </div>
      <?php elseif($action==='edit'): ?>
          <h3 class="modal__title">Edit <?php echo h($r_name) ?></h3>
      <?php if(!empty($form_errors)): ?>
          <div class="alert alert--error">
            <svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg>
            <div>
        <?php foreach($form_errors as $err): ?>
              <div><?php echo h($err) ?></div>
        <?php endforeach ?>
            </div>
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
              <select class="form-input" id="edit-<?php echo h($col) ?>" name="<?php echo h($col) ?>">
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
              <a class="btn btn--ghost btn--sm" href="<?php echo h($close_url) ?>">Cancel</a>
              <button type="submit" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Save changes</button>
            </div>
          </form>
      <?php elseif($action==='delete'): ?>
          <h3 class="modal__title">Delete this user?</h3>
          <p class="muted">This will permanently delete <strong><?php echo h($r_name) ?></strong> (<?php echo h($r_id) ?>). This cannot be undone.</p>
          <form method="post" action="users.php">
            <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']) ?>" />
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" name="utype" value="<?php echo h($utype) ?>" />
            <input type="hidden" name="uid" value="<?php echo (int)$record['id'] ?>" />
        <?php foreach($filters as $k=>$v): ?>
            <input type="hidden" name="<?php echo h($k) ?>" value="<?php echo h($v) ?>" />
        <?php endforeach ?>
            <div class="modal__actions">
              <a class="btn btn--ghost btn--sm" href="<?php echo h($close_url) ?>">Cancel</a>
              <button type="submit" class="btn btn--danger btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-trash"/></svg>Delete</button>
            </div>
          </form>
      <?php endif ?>
        </div>
      </div>
  <?php endif ?>
      <div class="toast-stack" id="toast-container" role="status" aria-live="polite">
    <?php if($flash): ?>
        <div class="toast toast--<?php echo $flash[0]==='success' ? 'success' : 'error' ?>">
          <svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>
          <span><?php echo h($flash[1]) ?></span>
          <a class="icon-btn" href="<?php echo h(list_url($filters)) ?>" aria-label="Dismiss"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></a>
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
