<?php
require_once 'config.php';
require_once 'security.php';
require_once 'encryption.php';
requireLogin();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM passwords WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalPasswords = $stmt->fetch()['total'];

// Get recent passwords - LIMITED TO 5 ONLY
$stmt = $pdo->prepare("SELECT * FROM passwords WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recentPasswords = $stmt->fetchAll();

// Get category distribution
$stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM passwords WHERE user_id = ? GROUP BY category");
$stmt->execute([$_SESSION['user_id']]);
$categories = $stmt->fetchAll();

// Get weak passwords
$weakCount = 0;
$stmt = $pdo->prepare("SELECT encrypted_password FROM passwords WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$allPasswords = $stmt->fetchAll();
foreach ($allPasswords as $pwd) {
    $decrypted = Encryption::decrypt($pwd['encrypted_password'], $_SESSION['master_key']);
    if (strlen($decrypted) < 8) $weakCount++;
}

// Calculate security score
$securityScore = $totalPasswords > 0 ? round((($totalPasswords - $weakCount) / $totalPasswords) * 100) : 100;

// Get user profile picture for dropdown
$stmt = $pdo->prepare("SELECT profile_picture, full_name FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();
$profilePicture = $userData['profile_picture'];
$fullName = $userData['full_name'];
$displayName = !empty($fullName) ? $fullName : $_SESSION['username'];
$userInitials = strtoupper(substr($displayName, 0, 2));

$profilePicturePath = '';
if (!empty($profilePicture) && file_exists('uploads/profile/' . $profilePicture)) {
    $profilePicturePath = 'uploads/profile/' . $profilePicture;
}

// Category metadata
$catMeta = [
    'Work'     => ['fas fa-briefcase',        '#3B82F6'],
    'Personal' => ['fas fa-user',              '#8B5CF6'],
    'Finance'  => ['fas fa-chart-line',        '#10B981'],
    'Social'   => ['fas fa-hashtag',           '#F59E0B'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dashboard — <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 260px;
            --accent: #10B981;
            --accent-dark: #059669;
            --accent-light: #D1FAE5;
            --navy: #0F172A;
            --navy-mid: #1E293B;
            --slate: #64748B;
            --border: #E2E8F0;
            --surface: #F8FAFC;
            --white: #ffffff;
            --text: #0F172A;
            --text-muted: #64748B;
            --danger: #EF4444;
            --info: #3B82F6;
            --warn: #F59E0B;
            --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: var(--surface); font-family:'Plus Jakarta Sans',sans-serif; color:var(--text); overflow-x:hidden; }

        /* ═══════════════════════════════════════
           SIDEBAR — identical to vault.php
        ═══════════════════════════════════════ */
        .sidebar {
            position: fixed; top:0; left:0; height:100vh; width:var(--sidebar-w);
            background: var(--navy); display:flex; flex-direction:column;
            z-index: 1000; transition: transform .3s cubic-bezier(.4,0,.2,1);
            box-shadow: 4px 0 24px rgba(0,0,0,.15);
        }
        .sidebar-overlay {
            display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
            z-index:999; backdrop-filter:blur(2px);
        }
        .sidebar-logo {
            padding: 1.5rem 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display:flex; align-items:center; gap:10px;
        }
        .logo-icon {
            width:38px; height:38px; background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .logo-icon i { color:#fff; font-size:1rem; }
        .logo-text h3 { color:#fff; font-size:1.05rem; font-weight:800; letter-spacing:-.3px; line-height:1.1; }
        .logo-text span { color:var(--slate); font-size:.7rem; font-weight:500; }

        .sidebar-nav { flex:1; overflow-y:auto; padding:.75rem 0; }
        .sidebar-nav::-webkit-scrollbar { width:0; }
        .nav-section-label {
            padding:.5rem 1.25rem .25rem;
            color:rgba(255,255,255,.25); font-size:.65rem; font-weight:700;
            letter-spacing:1px; text-transform:uppercase;
        }
        .nav-item {
            display:flex; align-items:center; gap:10px;
            padding:.7rem 1.25rem; margin:.1rem .75rem; border-radius:10px;
            color:rgba(255,255,255,.55); text-decoration:none;
            font-size:.84rem; font-weight:500; transition:all .2s;
        }
        .nav-item i { width:18px; font-size:.9rem; text-align:center; flex-shrink:0; }
        .nav-item:hover { background:rgba(255,255,255,.06); color:rgba(255,255,255,.9); }
        .nav-item.active {
            background:rgba(16,185,129,.12); color:var(--accent);
            box-shadow: inset 3px 0 0 var(--accent); margin-left:.75rem;
        }
        .sidebar-divider { border-color:rgba(255,255,255,.07); margin:.5rem 1rem; }
        .sidebar-footer {
            padding:1rem 1.25rem; border-top:1px solid rgba(255,255,255,.07);
        }
        .sidebar-user { display:flex; align-items:center; gap:10px; }
        .user-avatar-sidebar {
            width:34px; height:34px; background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:.8rem; font-weight:700; flex-shrink:0; overflow:hidden;
        }
        .user-avatar-sidebar img { width:100%; height:100%; object-fit:cover; }
        .user-info p { color:rgba(255,255,255,.85); font-size:.78rem; font-weight:600; }
        .user-info span { color:var(--slate); font-size:.68rem; }

        /* ═══════════════════════════════════════
           LAYOUT
        ═══════════════════════════════════════ */
        .main-wrap {
            margin-left: var(--sidebar-w); min-height:100vh; padding:1.5rem;
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
        }

        /* ═══════════════════════════════════════
           TOP BAR — identical pattern to vault.php
        ═══════════════════════════════════════ */
        .topbar {
            background:var(--white); border-radius:var(--radius); padding:.9rem 1.25rem;
            margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center;
            border:1px solid var(--border); box-shadow:var(--shadow-sm);
        }
        .topbar-left { display:flex; align-items:center; gap:.75rem; }
        .mobile-menu-btn {
            display:none; background:none; border:1px solid var(--border);
            border-radius:9px; padding:.4rem .55rem; cursor:pointer; color:var(--slate);
            font-size:1rem; transition:all .2s;
        }
        .mobile-menu-btn:hover { background:var(--surface); color:var(--text); }
        .page-title h1 { font-size:1.3rem; font-weight:800; color:var(--text); letter-spacing:-.3px; }
        .page-title p { font-size:.75rem; color:var(--text-muted); margin-top:1px; }

        /* ═══════════════════════════════════════
           PROFILE DROPDOWN
        ═══════════════════════════════════════ */
        .admin-dropdown { position:relative; z-index:2000; }
        .admin-trigger {
            display:flex; align-items:center; gap:9px; padding:5px 12px 5px 6px;
            background:var(--surface); border-radius:40px; cursor:pointer;
            border:1px solid var(--border); transition:all .2s;
        }
        .admin-trigger:hover { background:#F1F5F9; border-color:var(--accent); }
        .trigger-avatar {
            width:34px; height:34px; border-radius:50%; overflow:hidden;
            background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:.82rem; font-weight:700; flex-shrink:0;
        }
        .trigger-avatar img { width:100%; height:100%; object-fit:cover; }
        .trigger-name { font-size:.82rem; font-weight:600; color:var(--text); }
        .trigger-chevron { font-size:.65rem; color:var(--slate); transition:transform .2s; }
        .admin-dropdown.active .trigger-chevron { transform:rotate(180deg); }

        .dropdown-panel {
            position:absolute; top:calc(100% + 8px); right:0; width:270px;
            background:var(--white); border-radius:16px; border:1px solid var(--border);
            box-shadow:0 20px 40px -10px rgba(0,0,0,.18);
            opacity:0; visibility:hidden; transform:translateY(-6px);
            transition:all .2s ease; z-index:9999;
        }
        .admin-dropdown.active .dropdown-panel { opacity:1; visibility:visible; transform:translateY(0); }

        .dp-header {
            padding:14px 16px; background:var(--surface);
            border-radius:16px 16px 0 0; border-bottom:1px solid var(--border);
        }
        .dp-user {
            display:flex; align-items:center; gap:11px; padding:7px 8px;
            border-radius:10px; cursor:pointer; transition:background .15s;
        }
        .dp-user:hover { background:var(--white); }
        .dp-avatar {
            width:44px; height:44px; border-radius:50%; overflow:hidden;
            background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:1.1rem; font-weight:700; flex-shrink:0;
        }
        .dp-avatar img { width:100%; height:100%; object-fit:cover; }
        .dp-name { font-size:.92rem; font-weight:700; color:var(--text); }
        .dp-handle { font-size:.68rem; color:var(--text-muted); }
        .dp-divider { height:1px; background:var(--border); }
        .dp-item {
            display:flex; align-items:center; gap:11px; padding:11px 16px;
            color:var(--text); text-decoration:none; font-size:.82rem; font-weight:500;
            transition:background .15s;
        }
        .dp-item:hover { background:var(--surface); }
        .dp-item i { width:22px; font-size:.9rem; color:var(--accent); text-align:center; }
        .dp-item.dp-logout i { color:var(--danger); }
        .dp-item.dp-logout span { color:var(--danger); }
        .dp-item:last-child { border-radius:0 0 16px 16px; }

        /* ═══════════════════════════════════════
           STAT CARDS — vault.php card aesthetic
        ═══════════════════════════════════════ */
        .stat-card {
            background:var(--white); border:1px solid var(--border); border-radius:16px;
            padding:1.25rem; position:relative; overflow:hidden;
            transition:all .25s cubic-bezier(.4,0,.2,1); cursor:default;
        }
        .stat-card::after {
            content:''; position:absolute; inset:0; border-radius:16px;
            box-shadow:0 0 0 1.5px var(--accent); opacity:0; transition:opacity .2s;
        }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.09); }
        .stat-card:hover::after { opacity:1; }
        /* colored left accent bar */
        .stat-card .accent-bar {
            position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:16px 0 0 16px;
        }
        .stat-icon-wrap {
            width:46px; height:46px; border-radius:13px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.3rem; margin-bottom:.9rem;
        }
        .stat-number {
            font-size:1.9rem; font-weight:800; color:var(--text);
            letter-spacing:-.04em; line-height:1;
        }
        .stat-label { font-size:.75rem; font-weight:600; color:var(--text-muted); margin-top:.3rem; }
        .stat-sub { font-size:.68rem; color:var(--slate); margin-top:.15rem; }

        /* ═══════════════════════════════════════
           SECTION CARDS — same vault-card style
        ═══════════════════════════════════════ */
        .section-card {
            background:var(--white); border-radius:var(--radius);
            border:1px solid var(--border); box-shadow:var(--shadow-sm);
            margin-bottom:1.25rem; overflow:hidden;
        }
        .section-header {
            padding:.85rem 1.25rem; background:var(--surface);
            border-bottom:1px solid var(--border);
            display:flex; justify-content:space-between; align-items:center;
        }
        .section-header h4 {
            font-size:.9rem; font-weight:700; color:var(--text); margin:0;
            display:flex; align-items:center; gap:7px;
        }
        .section-header h4 i { color:var(--accent); font-size:.95rem; }
        .section-body { padding:1.25rem; }

        /* ═══════════════════════════════════════
           TABLE
        ═══════════════════════════════════════ */
        .table-custom { width:100%; border-collapse:collapse; }
        .table-custom th {
            color:var(--slate); font-weight:700; font-size:.65rem;
            text-transform:uppercase; letter-spacing:.6px;
            padding:.6rem .75rem; border-bottom:2px solid var(--border); white-space:nowrap;
        }
        .table-custom td {
            padding:.65rem .75rem; color:var(--text); font-size:.82rem;
            border-bottom:1px solid var(--border);
        }
        .table-custom tr:last-child td { border-bottom:none; }
        .clickable-row { cursor:pointer; transition:background .15s; }
        .clickable-row:hover { background:var(--surface); }

        /* site icon in table */
        .tbl-icon {
            width:30px; height:30px; border-radius:9px;
            display:inline-flex; align-items:center; justify-content:center;
            font-size:.9rem; margin-right:8px; vertical-align:middle; flex-shrink:0;
        }
        .tbl-name { display:flex; align-items:center; }
        .tbl-name strong { font-weight:700; font-size:.83rem; }

        /* category badge — same chip style */
        .cat-chip {
            display:inline-flex; align-items:center; gap:4px;
            background:var(--surface); border:1px solid var(--border);
            border-radius:20px; padding:3px 10px; font-size:.62rem; font-weight:700; color:var(--text);
        }
        .cat-chip i { font-size:.58rem; }

        /* strength tag */
        .strength-tag { font-size:.6rem; font-weight:700; padding:2px 9px; border-radius:20px; display:inline-flex; align-items:center; gap:3px; }

        /* ═══════════════════════════════════════
           CATEGORY GRID
        ═══════════════════════════════════════ */
        .cat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; }
        .cat-item {
            background:var(--surface); border-radius:12px; padding:.8rem 1rem;
            border:1px solid var(--border); display:flex; justify-content:space-between;
            align-items:center; transition:all .2s;
        }
        .cat-item:hover { border-color:var(--accent); transform:translateY(-2px); }
        .cat-item-left { display:flex; align-items:center; gap:9px; }
        .cat-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .cat-name { font-size:.8rem; font-weight:600; color:var(--text); }
        .cat-badge {
            background:var(--white); border:1px solid var(--border);
            padding:2px 10px; border-radius:20px; font-size:.68rem; font-weight:700; color:var(--accent);
        }

        /* security score bar */
        .score-bar-wrap { margin-top:.5rem; }
        .score-bar-track { background:#E2E8F0; border-radius:20px; height:6px; overflow:hidden; }
        .score-bar-fill { height:100%; border-radius:20px; transition:width .8s ease; }

        /* view all link button */
        .btn-view-all {
            background:var(--accent); color:#fff; border:none; border-radius:10px;
            padding:.4rem 1rem; font-size:.75rem; font-weight:700;
            display:inline-flex; align-items:center; gap:.35rem; cursor:pointer;
            text-decoration:none; transition:all .2s;
        }
        .btn-view-all:hover { background:var(--accent-dark); color:#fff; transform:translateY(-1px); box-shadow:0 4px 12px rgba(16,185,129,.3); }

        /* count pill */
        .count-pill {
            background:var(--surface); border:1px solid var(--border); border-radius:20px;
            padding:3px 12px; font-size:.72rem; font-weight:600; color:var(--slate);
            display:inline-flex; align-items:center; gap:5px;
        }
        .count-pill i { color:var(--accent); }

        /* empty state */
        .empty-state { text-align:center; padding:2.5rem 1rem; }
        .empty-state i { font-size:2.5rem; color:#CBD5E1; margin-bottom:.85rem; }
        .empty-state h4 { color:var(--slate); font-weight:700; font-size:.95rem; margin-bottom:.35rem; }
        .empty-state p { color:var(--text-muted); font-size:.8rem; }

        /* ═══════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════ */
        @media (max-width:1200px) { .cat-grid { grid-template-columns:repeat(2,1fr); } }

        @media (max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .sidebar-overlay.open { display:block; }
            .main-wrap { margin-left:0; padding:1rem; }
            .mobile-menu-btn { display:flex; align-items:center; }
            .cat-grid { grid-template-columns:1fr 1fr; }
            .topbar { padding:.75rem 1rem; }
            .page-title h1 { font-size:1.1rem; }
            .trigger-name { display:none; }
        }

        @media (max-width:480px) {
            .cat-grid { grid-template-columns:1fr; }
            .table-custom th:nth-child(4),
            .table-custom td:nth-child(4) { display:none; }
        }
    </style>
</head>
<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
        <div class="logo-text">
            <h3>PM System</h3>
            <span>Enterprise Password Manager</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="dashboard.php" class="nav-item active">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>
        <a href="vault.php" class="nav-item">
            <i class="fas fa-lock"></i> Password Vault
        </a>

        <div class="nav-section-label" style="margin-top:.5rem;">Manage</div>
        <a href="settings.php" class="nav-item">
            <i class="fas fa-gear"></i> Settings
        </a>
        <a href="logs.php" class="nav-item">
            <i class="fas fa-clock-rotate-left"></i> Activity Logs
        </a>

        <hr class="sidebar-divider">

        <a href="logout.php" class="nav-item" style="color:rgba(239,68,68,.7);">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar-sidebar">
                <?php if ($profilePicturePath): ?>
                    <img src="<?php echo $profilePicturePath; ?>?v=<?php echo time(); ?>" alt="Profile">
                <?php else: ?>
                    <?php echo $userInitials; ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                <span>Vault Manager</span>
            </div>
        </div>
    </div>
</aside>

<!-- ═══ MAIN ═══ -->
<div class="main-wrap" id="mainWrap">

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-menu-btn" onclick="openSidebar()" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-title">
                <h1><i class="fas fa-gauge-high" style="color:var(--accent);margin-right:8px;font-size:1.1rem;"></i>Dashboard</h1>
                <p>Welcome back, <?php echo sanitizeOutput($displayName); ?> 👋</p>
            </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="admin-dropdown" id="adminDropdown">
            <div class="admin-trigger" onclick="toggleDropdown(event)">
                <div class="trigger-avatar">
                    <?php if ($profilePicturePath): ?>
                        <img src="<?php echo $profilePicturePath; ?>?v=<?php echo time(); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo $userInitials; ?>
                    <?php endif; ?>
                </div>
                <span class="trigger-name"><?php echo sanitizeOutput($displayName); ?></span>
                <i class="fas fa-chevron-down trigger-chevron"></i>
            </div>

            <div class="dropdown-panel">
                <div class="dp-header">
                    <div class="dp-user" onclick="window.location.href='settings.php?tab=account'">
                        <div class="dp-avatar">
                            <?php if ($profilePicturePath): ?>
                                <img src="<?php echo $profilePicturePath; ?>?v=<?php echo time(); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo $userInitials; ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="dp-name"><?php echo sanitizeOutput($displayName); ?></div>
                            <div class="dp-handle">@<?php echo sanitizeOutput($_SESSION['username']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="dp-divider"></div>
                <a href="settings.php?tab=account" class="dp-item">
                    <i class="fas fa-user-circle"></i><span>Account Settings</span>
                </a>
                <a href="logout.php" class="dp-item dp-logout">
                    <i class="fas fa-right-from-bracket"></i><span>Log Out</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ═══ STAT CARDS ═══ -->
    <div class="row g-3 mb-3">
        <!-- Total Credentials -->
        <div class="col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="accent-bar" style="background:#10B981;"></div>
                <div class="stat-icon-wrap" style="background:rgba(16,185,129,.1);">
                    <i class="fas fa-key" style="color:var(--accent);"></i>
                </div>
                <div class="stat-number"><?php echo $totalPasswords; ?></div>
                <div class="stat-label">Total Credentials</div>
                <div class="stat-sub"><?php echo count($categories); ?> categor<?php echo count($categories)===1?'y':'ies'; ?></div>
            </div>
        </div>

        <!-- Security Score -->
        <div class="col-md-4 col-sm-6">
            <?php
            $scoreColor = $securityScore >= 80 ? '#10B981' : ($securityScore >= 50 ? '#F59E0B' : '#EF4444');
            $scoreLabel = $securityScore >= 80 ? 'Excellent' : ($securityScore >= 50 ? 'Moderate' : 'Needs Work');
            ?>
            <div class="stat-card">
                <div class="accent-bar" style="background:<?php echo $scoreColor; ?>;"></div>
                <div class="stat-icon-wrap" style="background:<?php echo $scoreColor; ?>1A;">
                    <i class="fas fa-shield-halved" style="color:<?php echo $scoreColor; ?>;"></i>
                </div>
                <div class="stat-number" style="color:<?php echo $scoreColor; ?>;"><?php echo $securityScore; ?>%</div>
                <div class="stat-label">Security Score</div>
                <div class="score-bar-wrap">
                    <div class="score-bar-track">
                        <div class="score-bar-fill" style="width:<?php echo $securityScore; ?>%;background:<?php echo $scoreColor; ?>;"></div>
                    </div>
                </div>
                <div class="stat-sub" style="margin-top:.4rem;"><?php echo $scoreLabel; ?><?php if($weakCount>0): ?> · <?php echo $weakCount; ?> weak<?php endif; ?></div>
            </div>
        </div>

        <!-- Categories -->
        <div class="col-md-4 col-sm-12">
            <div class="stat-card">
                <div class="accent-bar" style="background:#8B5CF6;"></div>
                <div class="stat-icon-wrap" style="background:rgba(139,92,246,.1);">
                    <i class="fas fa-folder-open" style="color:#8B5CF6;"></i>
                </div>
                <div class="stat-number"><?php echo count($categories); ?></div>
                <div class="stat-label">Active Categories</div>
                <div class="stat-sub">
                    <?php
                    $catNames = array_column($categories, 'category');
                    echo count($catNames) ? implode(', ', array_slice($catNames, 0, 3)) : 'None yet';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ RECENT CREDENTIALS ═══ -->
    <div class="section-card">
        <div class="section-header">
            <h4><i class="fas fa-clock-rotate-left"></i> Recently Added Credentials</h4>
            <div class="d-flex align-items-center gap-2">
                <?php if($totalPasswords > 5): ?>
                <div class="count-pill"><i class="fas fa-database"></i><?php echo $totalPasswords; ?> total</div>
                <?php endif; ?>
                <a href="vault.php" class="btn-view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        <div class="section-body p-0">
            <?php if(count($recentPasswords) > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Site / App</th>
                            <th>Username</th>
                            <th>Category</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentPasswords as $item):
                            // Get brand icon + color
                            $lower = strtolower(trim($item['name']));
                            $iconMap = [
                                'facebook'=>['fab fa-facebook','#1877F2'],
                                'instagram'=>['fab fa-instagram','#E1306C'],
                                'twitter'=>['fab fa-x-twitter','#000000'],
                                'tiktok'=>['fab fa-tiktok','#010101'],
                                'linkedin'=>['fab fa-linkedin','#0A66C2'],
                                'youtube'=>['fab fa-youtube','#FF0000'],
                                'whatsapp'=>['fab fa-whatsapp','#25D366'],
                                'discord'=>['fab fa-discord','#5865F2'],
                                'telegram'=>['fab fa-telegram','#26A5E4'],
                                'gmail'=>['fab fa-google','#EA4335'],
                                'google'=>['fab fa-google','#4285F4'],
                                'github'=>['fab fa-github','#24292F'],
                                'slack'=>['fab fa-slack','#4A154B'],
                                'trello'=>['fab fa-trello','#0052CC'],
                                'gcash'=>['fas fa-mobile-screen-button','#007DFF'],
                                'maya'=>['fas fa-mobile-screen-button','#2ECAD5'],
                                'paypal'=>['fab fa-paypal','#003087'],
                                'bdo'=>['fas fa-university','#003087'],
                                'bpi'=>['fas fa-landmark','#003087'],
                                'metrobank'=>['fas fa-university','#003087'],
                                'microsoft'=>['fab fa-microsoft','#0078D4'],
                                'teams'=>['fab fa-microsoft','#6264A7'],
                                'outlook'=>['fab fa-microsoft','#0078D4'],
                                'dropbox'=>['fab fa-dropbox','#0061FF'],
                                'figma'=>['fab fa-figma','#F24E1E'],
                                'shopify'=>['fab fa-shopify','#96BF48'],
                                'wordpress'=>['fab fa-wordpress','#21759B'],
                                'apple'=>['fab fa-apple','#555555'],
                                'icloud'=>['fab fa-apple','#3F8AE0'],
                                'steam'=>['fab fa-steam','#1B2838'],
                                'playstation'=>['fab fa-playstation','#003087'],
                                'reddit'=>['fab fa-reddit','#FF4500'],
                                'twitch'=>['fab fa-twitch','#9146FF'],
                                'viber'=>['fab fa-viber','#7360F2'],
                                'messenger'=>['fab fa-facebook-messenger','#0084FF'],
                                'snapchat'=>['fab fa-snapchat','#FFFC00'],
                                'binance'=>['fab fa-bitcoin','#F0B90B'],
                                'coins'=>['fas fa-coins','#F7B731'],
                                'pinterest'=>['fab fa-pinterest','#E60023'],
                            ];
                            $ic = ['fas fa-key','#64748B'];
                            foreach($iconMap as $k=>$v) { if(strpos($lower,$k)!==false){$ic=$v;break;} }
                            [$iClass,$iColor] = $ic;
                            $isBg = $iColor . '18';

                            $cm = ['Work'=>['fas fa-briefcase','#3B82F6'],'Personal'=>['fas fa-user','#8B5CF6'],'Finance'=>['fas fa-chart-line','#10B981'],'Social'=>['fas fa-hashtag','#F59E0B']];
                            [$cIcon,$cColor] = $cm[$item['category']] ?? ['fas fa-folder','#64748B'];
                        ?>
                        <tr class="clickable-row" onclick="window.location.href='vault.php'">
                            <td>
                                <div class="tbl-name">
                                    <div class="tbl-icon" style="background:<?php echo $isBg; ?>;">
                                        <i class="<?php echo $iClass; ?>" style="color:<?php echo $iColor; ?>;"></i>
                                    </div>
                                    <strong><?php echo sanitizeOutput($item['name']); ?></strong>
                                </div>
                            </td>
                            <td style="color:var(--slate);"><?php echo sanitizeOutput($item['username']); ?></td>
                            <td>
                                <div class="cat-chip">
                                    <i class="<?php echo $cIcon; ?>" style="color:<?php echo $cColor; ?>;"></i>
                                    <?php echo sanitizeOutput($item['category'] ?? 'Uncategorized'); ?>
                                </div>
                            </td>
                            <td style="color:var(--slate);white-space:nowrap;"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if($totalPasswords > 5): ?>
            <div style="padding:.65rem 1.25rem; border-top:1px solid var(--border); background:var(--surface);">
                <p style="font-size:.72rem; color:var(--slate); margin:0; display:flex; align-items:center; gap:5px;">
                    <i class="fas fa-circle-info" style="color:var(--accent);"></i>
                    Showing last 5 of <?php echo $totalPasswords; ?> credentials
                </p>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-vault"></i>
                <h4>No credentials yet</h4>
                <p>Head to the <a href="vault.php" style="color:var(--accent);font-weight:600;">Password Vault</a> to add your first one.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ CATEGORY DISTRIBUTION ═══ -->
    <?php if(!empty($categories)): ?>
    <div class="section-card">
        <div class="section-header">
            <h4><i class="fas fa-chart-pie"></i> Category Distribution</h4>
            <div class="count-pill"><i class="fas fa-layer-group"></i> <?php echo count($categories); ?> groups</div>
        </div>
        <div class="section-body">
            <div class="cat-grid">
                <?php foreach($categories as $cat):
                    $cm2 = ['Work'=>['fas fa-briefcase','#3B82F6'],'Personal'=>['fas fa-user','#8B5CF6'],'Finance'=>['fas fa-chart-line','#10B981'],'Social'=>['fas fa-hashtag','#F59E0B']];
                    [$cIcon2,$cColor2] = $cm2[$cat['category']] ?? ['fas fa-folder','#64748B'];
                ?>
                <div class="cat-item">
                    <div class="cat-item-left">
                        <div class="cat-dot" style="background:<?php echo $cColor2; ?>;"></div>
                        <div>
                            <div class="cat-name">
                                <i class="<?php echo $cIcon2; ?>" style="color:<?php echo $cColor2; ?>;margin-right:5px;font-size:.8rem;"></i>
                                <?php echo sanitizeOutput($cat['category'] ?: 'Uncategorized'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="cat-badge"><?php echo $cat['count']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /main-wrap -->

<script>
/* ── SIDEBAR ── */
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// Swipe-left to close
let _tx = 0;
document.getElementById('sidebar').addEventListener('touchstart', e=>{ _tx=e.changedTouches[0].screenX; },{passive:true});
document.getElementById('sidebar').addEventListener('touchend', e=>{
    if (_tx - e.changedTouches[0].screenX > 60) closeSidebar();
},{passive:true});

window.addEventListener('resize', ()=>{
    if (window.innerWidth >= 769) closeSidebar();
});

/* ── PROFILE DROPDOWN ── */
function toggleDropdown(e) {
    e.stopPropagation();
    document.getElementById('adminDropdown').classList.toggle('active');
}
document.addEventListener('click', ()=>{ document.getElementById('adminDropdown').classList.remove('active'); });
document.querySelector('.dropdown-panel').addEventListener('click', e=>e.stopPropagation());
</script>
</body>
</html>