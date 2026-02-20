<?php
session_start();

/* ── Auth Guard ── */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../db_connect.php';

/* ── Actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id      = (int)($_POST['appt_id'] ?? 0);
    $allowed = ['Pending','Approved','Completed','Cancelled'];
    $new     = in_array($_POST['new_status'] ?? '', $allowed) ? $_POST['new_status'] : 'Pending';
    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->bind_param("si", $new, $id);
    $stmt->execute(); $stmt->close();
    header('Location: dashboard.php?tab=appointments&msg=status_updated'); exit;
}
if (isset($_GET['delete_appt'])) {
    $id = (int)$_GET['delete_appt'];
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
    header('Location: dashboard.php?tab=appointments&msg=appt_deleted'); exit;
}
if (isset($_GET['delete_inq'])) {
    $id = (int)$_GET['delete_inq'];
    $stmt = $conn->prepare("DELETE FROM inquiries WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
    header('Location: dashboard.php?tab=inquiries&msg=inq_deleted'); exit;
}

/* ── Stats ── */
$total_appts    = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$total_inqs     = $conn->query("SELECT COUNT(*) AS c FROM inquiries")->fetch_assoc()['c'];
$pending_appts  = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='Pending'")->fetch_assoc()['c'];
$approved_appts = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status='Approved'")->fetch_assoc()['c'];

/* ── Search & Tab ── */
$search     = trim($_GET['search'] ?? '');
$active_tab = $_GET['tab'] ?? 'appointments';

/* ── Fetch Appointments ── */
if ($search) {
    $like = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE full_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM appointments ORDER BY created_at DESC");
}
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ── Fetch Inquiries ── */
if ($search) {
    $like = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM inquiries WHERE full_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM inquiries ORDER BY created_at DESC");
}
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ── Helpers ── */
function statusBadge($s) {
    $map = [
        'Pending'   => ['bg:#fef3c7;color:#92400e;border:1px solid #fcd34d', '⏳'],
        'Approved'  => ['bg:#d1fae5;color:#065f46;border:1px solid #6ee7b7', '✅'],
        'Completed' => ['bg:#dbeafe;color:#1e40af;border:1px solid #93c5fd', '🎓'],
        'Cancelled' => ['bg:#fee2e2;color:#991b1b;border:1px solid #fca5a5', '❌'],
    ];
    [$style, $icon] = $map[$s] ?? $map['Pending'];
    return "<span style='$style;padding:3px 10px;border-radius:100px;font-size:.7rem;font-weight:700;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;'>$icon $s</span>";
}

$flash = match($_GET['msg'] ?? '') {
    'status_updated' => ['✅ Appointment status updated successfully.', 'ok'],
    'appt_deleted'   => ['🗑 Appointment deleted successfully.',        'warn'],
    'inq_deleted'    => ['🗑 Inquiry deleted successfully.',            'warn'],
    default          => ['', ''],
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard | Amore Academy</title>
  <link rel="stylesheet" href="../1HCI.CSS"/>
  <link rel="icon" type="image/png" href="../icon.png"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet"/>

  <style>
  /* ════════════════════════════════════════
     DASHBOARD — ENHANCED ADMIN UI
  ════════════════════════════════════════ */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --sb:       256px;
    --sb-bg:    #060f2a;
    --sb-mid:   #091640;
    --topbar-h: 64px;
    --gold:     #c9a227;
    --gold-lt:  #ffe169;
    --radius:   12px;
    --trans:    .18s ease;
  }

  body {
    display: flex;
    background: #eaecf3;
    font-family: 'DM Sans', sans-serif;
    color: #1a2540;
    overflow-x: hidden;
    min-height: 100vh;
  }

  /* ════════════════════════
     SIDEBAR
  ════════════════════════ */
  .sb {
    width: var(--sb);
    flex-shrink: 0;
    background: var(--sb-bg);
    position: fixed; top: 0; left: 0;
    height: 100vh;
    display: flex; flex-direction: column;
    z-index: 300;
    box-shadow: 4px 0 32px rgba(0,0,0,.35);
    overflow: hidden;
  }
  /* Subtle right-edge glow */
  .sb::after {
    content: '';
    position: absolute; top: 0; right: 0;
    width: 1px; height: 100%;
    background: linear-gradient(180deg,
      transparent, rgba(201,162,39,.25) 30%, rgba(201,162,39,.25) 70%, transparent);
  }

  /* Brand area */
  .sb-brand {
    padding: 1.5rem 1.25rem 1.375rem;
    display: flex; align-items: center; gap: .875rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    position: relative;
  }
  .sb-brand img {
    width: 44px; height: 44px;
    border-radius: 10px;
    object-fit: contain;
    background: rgba(255,255,255,.06);
    padding: 4px;
  }
  .sb-brand-name {
    font-family: 'Crimson Pro', serif;
    font-size: 1rem; font-weight: 700;
    color: #fff; line-height: 1.2;
  }
  .sb-brand-sub {
    font-size: .64rem; font-weight: 600;
    color: var(--gold);
    letter-spacing: 1px; text-transform: uppercase;
    margin-top: .18rem;
  }

  /* Nav section label */
  .sb-section-label {
    font-size: .58rem; font-weight: 700;
    letter-spacing: 1.4px; text-transform: uppercase;
    color: rgba(255,255,255,.28);
    padding: 1.25rem 1.25rem .4rem;
  }

  .sb-nav { flex: 1; padding: .5rem .625rem; display: flex; flex-direction: column; gap: .15rem; overflow-y: auto; }
  .sb-nav::-webkit-scrollbar { width: 4px; }
  .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

  .sb-link {
    display: flex; align-items: center; gap: .75rem;
    padding: .7rem 1rem;
    border-radius: 10px;
    color: rgba(255,255,255,.58);
    font-size: .845rem; font-weight: 500;
    text-decoration: none;
    transition: background var(--trans), color var(--trans);
    border: none; background: none; cursor: pointer; width: 100%;
    text-align: left;
    position: relative;
  }
  .sb-link:hover {
    background: rgba(255,255,255,.07);
    color: rgba(255,255,255,.9);
  }
  .sb-link.active {
    background: rgba(201,162,39,.14);
    color: var(--gold-lt);
  }
  .sb-link.active::before {
    content: '';
    position: absolute; left: 0; top: 20%; bottom: 20%;
    width: 3px; border-radius: 0 3px 3px 0;
    background: var(--gold);
  }
  .sb-ico { width: 20px; text-align: center; font-size: .95rem; flex-shrink: 0; }
  .sb-badge {
    margin-left: auto;
    background: rgba(201,162,39,.2);
    color: var(--gold-lt);
    font-size: .6rem; font-weight: 700;
    padding: 2px 7px; border-radius: 100px;
    border: 1px solid rgba(201,162,39,.3);
  }

  .sb-div { height: 1px; background: rgba(255,255,255,.07); margin: .5rem .625rem; }

  /* User / footer */
  .sb-foot {
    padding: .875rem .625rem 1.25rem;
    border-top: 1px solid rgba(255,255,255,.07);
  }
  .sb-user {
    display: flex; align-items: center; gap: .75rem;
    padding: .6rem 1rem .75rem;
  }
  .sb-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold) 0%, #bf6f00 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; font-weight: 800;
    color: #060f2a;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(201,162,39,.3);
  }
  .sb-uname { font-size: .84rem; font-weight: 600; color: #fff; }
  .sb-urole { font-size: .65rem; color: rgba(255,255,255,.42); margin-top: .08rem; }
  .sb-logout {
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    width: 100%; padding: .65rem;
    background: rgba(239,68,68,.1);
    color: #fca5a5;
    border: 1px solid rgba(239,68,68,.22);
    border-radius: 10px;
    font-size: .8rem; font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer; text-decoration: none;
    transition: background var(--trans);
  }
  .sb-logout:hover { background: rgba(239,68,68,.2); }

  /* ════════════════════════
     MAIN WRAPPER
  ════════════════════════ */
  .mw {
    margin-left: var(--sb);
    flex: 1; min-width: 0;
    display: flex; flex-direction: column;
  }

  /* Topbar */
  .topbar {
    height: var(--topbar-h);
    background: #fff;
    padding: 0 2rem;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 200;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 12px rgba(10,36,99,.06);
  }
  .topbar__title {
    font-family: 'Crimson Pro', serif;
    font-size: 1.25rem; font-weight: 700;
    color: #060f2a;
  }
  .topbar__sub { font-size: .72rem; color: #8899b4; margin-top: .06rem; }
  .topbar__right { display: flex; align-items: center; gap: .75rem; }
  .topbar__date {
    display: flex; align-items: center; gap: .45rem;
    background: #f6f8fc;
    border: 1px solid #e2e8f0;
    border-radius: 100px;
    padding: .35rem 1rem;
    font-size: .76rem; color: #5a6a7e;
  }
  .topbar__viewsite {
    padding: .45rem 1.1rem;
    background: linear-gradient(128deg, #060f2a, #0a2463);
    color: #fff;
    border-radius: 10px;
    font-size: .78rem; font-weight: 600;
    text-decoration: none;
    transition: opacity var(--trans), transform var(--trans);
    box-shadow: 0 2px 10px rgba(10,36,99,.25);
  }
  .topbar__viewsite:hover { opacity: .88; transform: translateY(-1px); }

  /* Content */
  .content { padding: 1.875rem 2rem 3.5rem; }

  /* Flash message */
  .flash {
    display: flex; align-items: center; gap: .75rem;
    padding: .9rem 1.2rem;
    border-radius: 12px;
    font-size: .87rem; font-weight: 500;
    margin-bottom: 1.875rem;
    animation: flashIn .3s ease;
  }
  @keyframes flashIn { from{opacity:0;transform:translateY(-5px)} to{opacity:1;transform:translateY(0)} }
  .flash--ok   { background: #ecfdf5; border: 1px solid #86efac; color: #14532d; }
  .flash--warn { background: #fef3c7; border: 1px solid #fcd34d; color: #78350f; }

  /* ════════════════════════
     STAT CARDS
  ════════════════════════ */
  .stats {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 1.125rem;
    margin-bottom: 2rem;
  }
  .stat {
    background: #fff;
    border-radius: 16px;
    padding: 1.4rem 1.5rem;
    display: flex; align-items: center; gap: 1.1rem;
    border: 1px solid #e8edf5;
    box-shadow: 0 1px 4px rgba(0,0,0,.04), 0 4px 16px rgba(10,36,99,.06);
    transition: transform .25s ease, box-shadow .25s ease;
    position: relative; overflow: hidden;
  }
  .stat::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: 16px 16px 0 0;
    opacity: .75;
  }
  .stat:hover { transform: translateY(-3px); box-shadow: 0 2px 8px rgba(0,0,0,.06), 0 12px 32px rgba(10,36,99,.12); }

  .stat--navy::before   { background: linear-gradient(90deg, #0a2463, #1e3a8a); }
  .stat--amber::before  { background: linear-gradient(90deg, #bf6f00, #f08000); }
  .stat--yellow::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
  .stat--green::before  { background: linear-gradient(90deg, #059669, #34d399); }

  .stat__ico {
    width: 50px; height: 50px;
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
  }
  .ico-navy   { background: rgba(10,36,99,.09); }
  .ico-amber  { background: rgba(191,111,0,.11); }
  .ico-yellow { background: rgba(217,119,6,.1); }
  .ico-green  { background: rgba(5,150,105,.1); }

  .stat__val { font-size: 1.875rem; font-weight: 700; color: #060f2a; line-height: 1; }
  .stat__lbl { font-size: .74rem; color: #8899b4; margin-top: .22rem; font-weight: 500; }

  /* ════════════════════════
     DATA CARD
  ════════════════════════ */
  .dcard {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(0,0,0,.04), 0 4px 20px rgba(10,36,99,.06);
    overflow: hidden;
  }

  /* Tab bar */
  .tab-bar {
    display: flex;
    border-bottom: 1px solid #e8edf5;
    padding: 0 1.75rem;
    background: #fff;
  }
  .tab-btn {
    padding: 1rem 1.375rem;
    font-size: .85rem; font-weight: 600;
    color: #8899b4;
    text-decoration: none;
    border-bottom: 2.5px solid transparent;
    margin-bottom: -1px;
    display: flex; align-items: center; gap: .5rem;
    transition: color var(--trans), border-color var(--trans);
  }
  .tab-btn:hover { color: #0a2463; }
  .tab-btn.active { color: #060f2a; border-bottom-color: var(--gold); }
  .tab-count {
    padding: 1px 7px;
    border-radius: 100px;
    font-size: .67rem; font-weight: 700;
    background: #eaecf3; color: #5a6a7e;
  }
  .tab-btn.active .tab-count { background: rgba(201,162,39,.18); color: #7a5c00; }

  /* Toolbar */
  .toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.75rem;
    border-bottom: 1px solid #e8edf5;
    flex-wrap: wrap; gap: .75rem;
    background: #fafbfd;
  }
  .search-wrap { position: relative; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
  .search-box  { position: relative; }
  .search-box input {
    padding: .58rem 1rem .58rem 2.25rem;
    border: 1.5px solid #dde3ef;
    border-radius: 10px;
    font-size: .84rem; font-family: 'DM Sans', sans-serif;
    color: #1a2540;
    background: #f6f8fc;
    width: 264px; outline: none;
    transition: border-color var(--trans), background var(--trans);
  }
  .search-box input:focus { border-color: #0a2463; background: #fff; }
  .search-icon {
    position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
    color: #aab4cc; font-size: .85rem;
    pointer-events: none;
  }
  .btn-search {
    padding: .58rem 1.125rem;
    background: #0a2463; color: #fff;
    border: none; border-radius: 10px;
    font-size: .82rem; font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: background var(--trans);
  }
  .btn-search:hover { background: #060f2a; }
  .btn-clear {
    font-size: .8rem; color: #8899b4;
    text-decoration: none;
    display: flex; align-items: center; gap: .3rem;
  }
  .btn-clear:hover { color: #0a2463; }
  .result-note { font-size: .79rem; color: #8899b4; }

  /* ════════════════════════
     TABLE
  ════════════════════════ */
  .table-scroll { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; min-width: 900px; }

  thead tr { background: #f6f8fc; }
  th {
    padding: .75rem 1rem;
    text-align: left;
    font-size: .66rem; font-weight: 700;
    color: #8899b4;
    text-transform: uppercase; letter-spacing: .7px;
    white-space: nowrap;
    border-bottom: 1px solid #e8edf5;
  }
  td {
    padding: .9rem 1rem;
    font-size: .835rem;
    border-bottom: 1px solid #f0f3f8;
    vertical-align: middle;
    color: #1a2540;
  }
  tr:last-child td { border-bottom: none; }
  tbody tr { transition: background var(--trans); }
  tbody tr:hover td { background: rgba(10,36,99,.025); }

  .c-id    { font-size: .75rem; font-weight: 600; color: #aab4cc; }
  .c-name  { font-weight: 700; color: #060f2a; font-size: .87rem; }
  .c-note  { font-size: .7rem; color: #aab4cc; margin-top: .14rem; }
  .c-email { font-size: .8rem; color: #7a8aaa; }
  .c-light { font-size: .8rem; color: #7a8aaa; white-space: nowrap; }
  .c-trunc { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .8rem; color: #5a6a7e; }

  .level-pill {
    background: rgba(10,36,99,.08);
    color: #0a2463;
    padding: 3px 9px; border-radius: 100px;
    font-size: .7rem; font-weight: 700;
    white-space: nowrap;
    border: 1px solid rgba(10,36,99,.12);
  }

  /* Action buttons */
  .actions { display: flex; gap: .35rem; flex-wrap: wrap; }
  .act {
    padding: 4px 10px; border-radius: 7px;
    font-size: .72rem; font-weight: 700;
    cursor: pointer; border: none;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 3px;
    transition: background var(--trans), transform var(--trans);
    white-space: nowrap;
    font-family: 'DM Sans', sans-serif;
  }
  .act:hover { transform: translateY(-1px); }
  .act-approve  { background: rgba(5,150,105,.1);  color: #065f46; }
  .act-approve:hover  { background: rgba(5,150,105,.2); }
  .act-complete { background: rgba(37,99,235,.1);  color: #1e40af; }
  .act-complete:hover { background: rgba(37,99,235,.2); }
  .act-cancel   { background: rgba(217,119,6,.1);  color: #92400e; }
  .act-cancel:hover   { background: rgba(217,119,6,.2); }
  .act-delete   { background: rgba(220,38,38,.09); color: #991b1b; }
  .act-delete:hover   { background: rgba(220,38,38,.18); }

  .inline-form { display: inline; }

  /* Empty state */
  .empty {
    text-align: center; padding: 4rem 2rem;
    color: #aab4cc;
  }
  .empty__ico { font-size: 2.75rem; margin-bottom: 1rem; opacity: .6; }
  .empty p { font-size: .9rem; }

  /* ── Responsive ── */
  @media (max-width: 1200px) { .stats { grid-template-columns: repeat(2,1fr); } }
  @media (max-width: 900px)  { .sb { display: none; } .mw { margin-left: 0; } }
  @media (max-width: 640px)  {
    .content { padding: 1.25rem 1rem 3rem; }
    .stats { grid-template-columns: 1fr 1fr; }
    .topbar { padding: 0 1rem; }
    .topbar__date { display: none; }
  }
  </style>
</head>
<body>

<!-- ════════════════════ SIDEBAR ════ -->
<aside class="sb">

  <div class="sb-brand">
    <img src="../Amore_Academy_Logo.png" alt="Amore Academy"/>
    <div>
      <div class="sb-brand-name">Amore Academy</div>
      <div class="sb-brand-sub">Admin Panel</div>
    </div>
  </div>

  <nav class="sb-nav">

    <div class="sb-section-label">Management</div>

    <a href="dashboard.php?tab=appointments" class="sb-link <?= $active_tab==='appointments'?'active':'' ?>">
      <span class="sb-ico">📅</span>
      Appointments
      <span class="sb-badge"><?= $total_appts ?></span>
    </a>
    <a href="dashboard.php?tab=inquiries" class="sb-link <?= $active_tab==='inquiries'?'active':'' ?>">
      <span class="sb-ico">✉️</span>
      Inquiries
      <span class="sb-badge"><?= $total_inqs ?></span>
    </a>

    <div class="sb-div"></div>
    <div class="sb-section-label">Quick Links</div>

    <a href="../schedule.php" target="_blank" class="sb-link">
      <span class="sb-ico">🌐</span> View Frontend
    </a>
    <a href="../1HCI.HTML" target="_blank" class="sb-link">
      <span class="sb-ico">🏫</span> Main Website
    </a>
    <a href="../admissions.html" target="_blank" class="sb-link">
      <span class="sb-ico">📋</span> Admissions Page
    </a>

  </nav>

  <div class="sb-foot">
    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($_SESSION['admin_username'],0,1)) ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars($_SESSION['admin_username']) ?></div>
        <div class="sb-urole">Administrator</div>
      </div>
    </div>
    <a href="logout.php" class="sb-logout">🔒&nbsp; Sign Out</a>
  </div>

</aside>

<!-- ════════════════════ MAIN ════ -->
<div class="mw">

  <!-- Topbar -->
  <div class="topbar">
    <div>
      <div class="topbar__title">Dashboard Overview</div>
      <div class="topbar__sub">Appointments &amp; Inquiries Management</div>
    </div>
    <div class="topbar__right">
      <div class="topbar__date">📅&nbsp; <?= date('F j, Y') ?></div>
      <a href="../schedule.php" target="_blank" class="topbar__viewsite">🌐&nbsp; View Site</a>
    </div>
  </div>

  <div class="content">

    <!-- Flash -->
    <?php if ($flash[0]): ?>
      <div class="flash flash--<?= $flash[1] ?>"><?= htmlspecialchars($flash[0]) ?></div>
    <?php endif; ?>

    <!-- ── Stat Cards ── -->
    <div class="stats">
      <div class="stat stat--navy">
        <div class="stat__ico ico-navy">📅</div>
        <div>
          <div class="stat__val"><?= $total_appts ?></div>
          <div class="stat__lbl">Total Appointments</div>
        </div>
      </div>
      <div class="stat stat--amber">
        <div class="stat__ico ico-amber">✉️</div>
        <div>
          <div class="stat__val"><?= $total_inqs ?></div>
          <div class="stat__lbl">Total Inquiries</div>
        </div>
      </div>
      <div class="stat stat--yellow">
        <div class="stat__ico ico-yellow">⏳</div>
        <div>
          <div class="stat__val"><?= $pending_appts ?></div>
          <div class="stat__lbl">Pending Appointments</div>
        </div>
      </div>
      <div class="stat stat--green">
        <div class="stat__ico ico-green">✅</div>
        <div>
          <div class="stat__val"><?= $approved_appts ?></div>
          <div class="stat__lbl">Approved Appointments</div>
        </div>
      </div>
    </div>

    <!-- ── Data Card ── -->
    <div class="dcard">

      <!-- Tabs -->
      <div class="tab-bar">
        <a href="dashboard.php?tab=appointments<?= $search?'&search='.urlencode($search):'' ?>"
           class="tab-btn <?= $active_tab==='appointments'?'active':'' ?>">
          📅 Appointments <span class="tab-count"><?= count($appointments) ?></span>
        </a>
        <a href="dashboard.php?tab=inquiries<?= $search?'&search='.urlencode($search):'' ?>"
           class="tab-btn <?= $active_tab==='inquiries'?'active':'' ?>">
          ✉️ Inquiries <span class="tab-count"><?= count($inquiries) ?></span>
        </a>
      </div>

      <!-- Search toolbar -->
      <div class="toolbar">
        <form method="GET" action="dashboard.php" style="display:contents;">
          <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
          <div class="search-wrap">
            <div class="search-box">
              <span class="search-icon">🔍</span>
              <input type="text" name="search"
                     placeholder="Search by name or email…"
                     value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-search">Search</button>
            <?php if ($search): ?>
              <a href="dashboard.php?tab=<?= $active_tab ?>" class="btn-clear">✕ Clear</a>
            <?php endif; ?>
          </div>
        </form>
        <?php if ($search): ?>
          <div class="result-note">Showing results for "<strong><?= htmlspecialchars($search) ?></strong>"</div>
        <?php endif; ?>
      </div>

      <!-- ═══ APPOINTMENTS TABLE ═══ -->
      <?php if ($active_tab === 'appointments'): ?>
        <div class="table-scroll">
          <?php if (empty($appointments)): ?>
            <div class="empty">
              <div class="empty__ico">📭</div>
              <p><?= $search ? 'No appointments match your search.' : 'No appointments submitted yet.' ?></p>
            </div>
          <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Level</th>
                <th>Date</th>
                <th>Time</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $a): ?>
              <tr>
                <td class="c-id">#<?= $a['id'] ?></td>
                <td>
                  <div class="c-name"><?= htmlspecialchars($a['full_name']) ?></div>
                  <?php if ($a['notes']): ?>
                    <div class="c-note" title="<?= htmlspecialchars($a['notes']) ?>">📝 Has note</div>
                  <?php endif; ?>
                </td>
                <td class="c-email"><?= htmlspecialchars($a['email']) ?></td>
                <td class="c-light"><?= htmlspecialchars($a['contact_number']) ?></td>
                <td><span class="level-pill"><?= htmlspecialchars($a['applicant_level']) ?></span></td>
                <td class="c-light"><?= date('M j, Y', strtotime($a['preferred_date'])) ?></td>
                <td class="c-light"><?= date('g:i A', strtotime($a['preferred_time'])) ?></td>
                <td class="c-light"><?= htmlspecialchars($a['purpose']) ?></td>
                <td><?= statusBadge($a['status']) ?></td>
                <td class="c-light"><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
                <td>
                  <div class="actions">
                    <?php if ($a['status'] !== 'Approved'): ?>
                    <form method="POST" class="inline-form">
                      <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="new_status" value="Approved">
                      <button type="submit" name="update_status" class="act act-approve">✅ Approve</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($a['status'] !== 'Completed'): ?>
                    <form method="POST" class="inline-form">
                      <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="new_status" value="Completed">
                      <button type="submit" name="update_status" class="act act-complete">🎓 Done</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($a['status'] !== 'Cancelled'): ?>
                    <form method="POST" class="inline-form">
                      <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="new_status" value="Cancelled">
                      <button type="submit" name="update_status" class="act act-cancel">❌ Cancel</button>
                    </form>
                    <?php endif; ?>
                    <a href="dashboard.php?delete_appt=<?= $a['id'] ?>&tab=appointments"
                       class="act act-delete"
                       onclick="return confirm('Permanently delete this appointment?')">🗑 Delete</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

      <!-- ═══ INQUIRIES TABLE ═══ -->
      <?php elseif ($active_tab === 'inquiries'): ?>
        <div class="table-scroll">
          <?php if (empty($inquiries)): ?>
            <div class="empty">
              <div class="empty__ico">📭</div>
              <p><?= $search ? 'No inquiries match your search.' : 'No inquiries submitted yet.' ?></p>
            </div>
          <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message Preview</th>
                <th>Date Submitted</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($inquiries as $inq): ?>
              <tr>
                <td class="c-id">#<?= $inq['id'] ?></td>
                <td class="c-name"><?= htmlspecialchars($inq['full_name']) ?></td>
                <td class="c-email"><?= htmlspecialchars($inq['email']) ?></td>
                <td><?= htmlspecialchars($inq['subject']) ?></td>
                <td>
                  <div class="c-trunc" title="<?= htmlspecialchars($inq['message']) ?>">
                    <?= htmlspecialchars($inq['message']) ?>
                  </div>
                </td>
                <td class="c-light"><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
                <td>
                  <a href="dashboard.php?delete_inq=<?= $inq['id'] ?>&tab=inquiries"
                     class="act act-delete"
                     onclick="return confirm('Permanently delete this inquiry?')">🗑 Delete</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div><!-- /.dcard -->
  </div><!-- /.content -->
</div><!-- /.mw -->

</body>
</html>