<?php
require_once 'config.php';
require_once 'security.php';
requireLogin();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE user_id = ? OR user_id = 0");
$stmt->execute([$_SESSION['user_id']]);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT * FROM logs WHERE user_id = ? OR user_id = 0 ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$logs = $stmt->fetchAll();

// Get user for sidebar
$stmt = $pdo->prepare("SELECT username, full_name FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$userInitials = strtoupper(substr($user['full_name'] ?: $user['username'], 0, 2));

// Get action icons and colors
function getActionIcon($action) {
    switch($action) {
        case 'login_success': return 'fa-sign-in-alt';
        case 'logout': return 'fa-sign-out-alt';
        case 'failed_login': return 'fa-exclamation-triangle';
        case 'add_password': return 'fa-plus-circle';
        case 'edit_password': return 'fa-edit';
        case 'delete_password': return 'fa-trash-alt';
        case 'change_password': return 'fa-key';
        case 'change_master_key': return 'fa-fingerprint';
        case 'update_profile': return 'fa-user-edit';
        case 'update_profile_picture': return 'fa-camera';
        case 'remove_profile_picture': return 'fa-trash-alt';
        default: return 'fa-info-circle';
    }
}

function getActionColor($action) {
    switch($action) {
        case 'login_success': return '#10B981';
        case 'logout': return '#64748B';
        case 'failed_login': return '#EF4444';
        case 'add_password': return '#3B82F6';
        case 'edit_password': return '#F59E0B';
        case 'delete_password': return '#EF4444';
        case 'change_password': return '#8B5CF6';
        case 'change_master_key': return '#8B5CF6';
        case 'update_profile': return '#10B981';
        case 'update_profile_picture': return '#10B981';
        case 'remove_profile_picture': return '#EF4444';
        default: return '#64748B';
    }
}

function getActionBadgeColor($action) {
    switch($action) {
        case 'login_success': return '#D1FAE5';
        case 'logout': return '#F1F5F9';
        case 'failed_login': return '#FEE2E2';
        case 'add_password': return '#DBEAFE';
        case 'edit_password': return '#FEF3C7';
        case 'delete_password': return '#FEE2E2';
        case 'change_password': return '#EDE9FE';
        case 'change_master_key': return '#EDE9FE';
        case 'update_profile': return '#D1FAE5';
        case 'update_profile_picture': return '#D1FAE5';
        case 'remove_profile_picture': return '#FEE2E2';
        default: return '#F1F5F9';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs — <?php echo APP_NAME; ?></title>
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
        body { background: var(--surface); font-family:'Plus Jakarta Sans',sans-serif; color:var(--text); }

        /* Sidebar Styles */
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
            border-radius:10px; display:flex; align-items:center; justify-content:center;
            flex-shrink:0;
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
            position:relative;
        }
        .nav-item i { width:18px; font-size:.9rem; text-align:center; flex-shrink:0; }
        .nav-item:hover { background:rgba(255,255,255,.06); color:rgba(255,255,255,.9); }
        .nav-item.active {
            background:rgba(16,185,129,.12); color:var(--accent);
            box-shadow: inset 3px 0 0 var(--accent);
            margin-left:.75rem;
        }
        .nav-item .nav-badge {
            margin-left:auto; background:var(--accent); color:#fff;
            font-size:.6rem; font-weight:700; padding:2px 7px; border-radius:20px;
        }
        .sidebar-divider { border-color:rgba(255,255,255,.07); margin:.5rem 1rem; }
        .sidebar-footer {
            padding:1rem 1.25rem;
            border-top:1px solid rgba(255,255,255,.07);
        }
        .sidebar-user {
            display:flex; align-items:center; gap:10px;
        }
        .user-avatar-sidebar {
            width:34px; height:34px; background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:.8rem; font-weight:700; flex-shrink:0;
        }
        .user-info p { color:rgba(255,255,255,.85); font-size:.78rem; font-weight:600; margin:0; }
        .user-info span { color:var(--slate); font-size:.68rem; }

        /* Main Layout */
        .main-wrap {
            margin-left: var(--sidebar-w);
            min-height: 100vh; padding:1.5rem;
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
        }

        /* Top Bar */
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
        .page-title h1 { font-size:1.3rem; font-weight:800; color:var(--text); letter-spacing:-.3px; margin:0; }
        .page-title p { font-size:.75rem; color:var(--text-muted); margin-top:1px; }

        /* Stats Cards */
        .stats-row { margin-bottom:1.5rem; }
        .stat-card {
            background:var(--white); border-radius:var(--radius);
            border:1px solid var(--border); padding:1rem 1.25rem;
            text-align:center; transition:all .25s;
        }
        .stat-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
        .stat-number { font-size:1.8rem; font-weight:800; color:var(--text); line-height:1.2; }
        .stat-label { font-size:.7rem; color:var(--text-muted); font-weight:500; margin-top:.25rem; }
        .stat-icon { font-size:1.5rem; color:var(--accent); margin-bottom:.5rem; }

        /* Log Card */
        .log-card {
            background:var(--white); border-radius:var(--radius);
            border:1px solid var(--border); padding:1rem 1.25rem;
            margin-bottom:.75rem; transition:all .2s;
        }
        .log-card:hover { transform:translateX(4px); box-shadow:var(--shadow-sm); background:var(--surface); }
        .log-icon {
            width:40px; height:40px; border-radius:12px;
            display:inline-flex; align-items:center; justify-content:center;
            margin-right:12px; flex-shrink:0;
        }
        .log-action {
            font-weight:700; color:var(--text); font-size:.85rem;
        }
        .log-details {
            color:var(--text-muted); font-size:.7rem; margin-top:2px;
        }
        .log-time {
            font-size:.7rem; color:var(--slate); white-space:nowrap;
        }
        .log-ip {
            font-family:monospace; font-size:.7rem;
            background:var(--surface); padding:4px 10px; border-radius:20px;
            display:inline-flex; align-items:center; gap:5px;
        }
        .log-badge {
            display:inline-block; padding:4px 12px; border-radius:20px;
            font-size:.65rem; font-weight:600;
        }

        /* Pagination */
        .pagination { gap:.25rem; }
        .pagination .page-link {
            border-radius:10px; border:1px solid var(--border);
            color:var(--text); padding:.5rem .9rem; font-size:.8rem;
            transition:all .2s;
        }
        .pagination .page-link:hover { background:var(--accent); color:#fff; border-color:var(--accent); }
        .pagination .page-item.active .page-link {
            background:var(--accent); border-color:var(--accent); color:#fff;
        }

        /* Empty State */
        .empty-state { text-align:center; padding:3rem 1rem; }
        .empty-icon { font-size:3rem; color:#CBD5E1; margin-bottom:1rem; }
        .empty-state h4 { color:var(--slate); font-weight:700; margin-bottom:.5rem; }
        .empty-state p { color:var(--text-muted); font-size:.85rem; }

        /* Responsive */
        @media (max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .sidebar-overlay.open { display:block; }
            .main-wrap { margin-left:0; padding:1rem; }
            .mobile-menu-btn { display:flex; align-items:center; }
            .log-card .row > div { margin-bottom:.5rem; }
            .log-time { text-align:left !important; }
            .log-ip { display:inline-block; margin-top:.5rem; }
        }
        @media (max-width:576px) {
            .stat-number { font-size:1.4rem; }
            .stat-card { padding:.75rem; }
        }
    </style>
</head>
<body>

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar - Same as vault page -->
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
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>
        <a href="vault.php" class="nav-item">
            <i class="fas fa-lock"></i> Password Vault
        </a>

        <div class="nav-section-label" style="margin-top:.5rem;">Manage</div>
        <a href="settings.php" class="nav-item">
            <i class="fas fa-gear"></i> Settings
        </a>
        <a href="logs.php" class="nav-item active">
            <i class="fas fa-clock-rotate-left"></i> Activity Logs
        </a>

        <hr class="sidebar-divider">

        <a href="logout.php" class="nav-item" style="color:rgba(239,68,68,.7);">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar-sidebar"><?php echo $userInitials; ?></div>
            <div class="user-info">
                <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                <span>Vault Manager</span>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content -->
<div class="main-wrap" id="mainWrap">

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-menu-btn" onclick="openSidebar()" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-title">
                <h1><i class="fas fa-clock-rotate-left" style="color:var(--accent);margin-right:8px;font-size:1.1rem;"></i>Activity Logs</h1>
                <p>Complete audit trail of all system activities</p>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-database"></i></div>
                    <div class="stat-number"><?php echo $total; ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-sign-in-alt"></i></div>
                    <div class="stat-number">
                        <?php 
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE action = 'login_success' AND user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div class="stat-label">Successful Logins</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-key"></i></div>
                    <div class="stat-number">
                        <?php 
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE action IN ('add_password', 'edit_password', 'delete_password') AND user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div class="stat-label">Password Ops</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-number">
                        <?php 
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE action = 'failed_login' AND user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div class="stat-label">Failed Attempts</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs List -->
    <div class="log-list">
        <?php foreach($logs as $log): 
            $actionColor = getActionColor($log['action']);
            $badgeColor = getActionBadgeColor($log['action']);
        ?>
        <div class="log-card">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center">
                        <div class="log-icon" style="background: <?php echo $actionColor; ?>20;">
                            <i class="fas <?php echo getActionIcon($log['action']); ?>" style="color: <?php echo $actionColor; ?>; font-size:1rem;"></i>
                        </div>
                        <div>
                            <div class="log-action">
                                <span class="log-badge" style="background: <?php echo $badgeColor; ?>; color: <?php echo $actionColor; ?>;">
                                    <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                </span>
                            </div>
                            <div class="log-details"><?php echo sanitizeOutput($log['details']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="log-ip">
                        <i class="fas fa-globe" style="font-size:.65rem;"></i> 
                        <?php echo $log['ip_address']; ?>
                    </span>
                </div>
                <div class="col-md-2 text-md-end">
                    <div class="log-time">
                        <i class="far fa-clock"></i> <?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if(empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-clipboard-list"></i></div>
            <h4>No activity logs found</h4>
            <p>Activities will appear here as you use the system</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page-1; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>
            
            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if($startPage > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
            <?php if($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php for($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if($endPage < $totalPages): ?>
            <?php if($endPage < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
            <?php endif; ?>
            
            <?php if($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page+1; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script>
    // Sidebar functions - same as vault page
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
    
    // Close on swipe left (mobile)
    let touchStartX = 0;
    document.getElementById('sidebar').addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, {passive: true});
    document.getElementById('sidebar').addEventListener('touchend', function(e) {
        if (touchStartX - e.changedTouches[0].screenX > 60) closeSidebar();
    }, {passive: true});
</script>
</body>
</html>