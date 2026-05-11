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

// Get action icons
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
        default: return '#64748B';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #F8FAFC; font-family: 'Inter', sans-serif; }
        
        .sidebar {
            width: 280px;
            background: #0F172A;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-header h3 { color: #10B981; font-weight: 700; font-size: 1.4rem; }
        .sidebar-header p { color: #64748B; font-size: 0.75rem; }
        .sidebar-menu { padding: 1.5rem 0; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: #94A3B8;
            text-decoration: none;
            gap: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(16,185,129,0.08);
            color: #10B981;
            border-left: 3px solid #10B981;
        }
        .sidebar-menu hr { margin: 1rem 1.5rem; border-color: rgba(255,255,255,0.08); }
        
        .main-content { margin-left: 280px; padding: 2rem; }
        
        .top-nav {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #E2E8F0;
        }
        .page-title h1 { color: #0F172A; font-size: 1.5rem; font-weight: 700; margin: 0; }
        .page-title p { color: #64748B; font-size: 0.85rem; margin: 0; }
        
        .stats-row {
            margin-bottom: 2rem;
        }
        .stat-mini-card {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            border: 1px solid #E2E8F0;
        }
        .stat-mini-card .number {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0F172A;
        }
        
        .log-card {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            border: 1px solid #E2E8F0;
            transition: all 0.2s;
        }
        .log-card:hover {
            background: #F8FAFC;
            transform: translateX(4px);
        }
        .log-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        .log-action {
            font-weight: 700;
            color: #0F172A;
        }
        .log-details {
            color: #64748B;
            font-size: 0.85rem;
            margin-top: 4px;
        }
        .log-time {
            font-size: 0.75rem;
            color: #94A3B8;
        }
        .log-ip {
            font-family: monospace;
            font-size: 0.75rem;
            background: #F1F5F9;
            padding: 2px 8px;
            border-radius: 20px;
        }
        
        .pagination .page-link {
            border-radius: 10px;
            margin: 0 3px;
            color: #0F172A;
            border-color: #E2E8F0;
        }
        .pagination .page-item.active .page-link {
            background: #10B981;
            border-color: #10B981;
        }
        
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-shield-alt"></i> PM System</h3>
            <p>Enterprise Password Management</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="vault.php"><i class="fas fa-lock"></i> Password Vault</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logs.php" class="active"><i class="fas fa-history"></i> Activity Logs</a>
            <hr>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1>Activity Logs</h1>
                <p>Complete audit trail of all system activities</p>
            </div>
            <div class="user-avatar">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>
        
        <div class="stats-row">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-mini-card">
                        <div class="number"><?php echo $total; ?></div>
                        <div class="text-muted small">Total Events</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-mini-card">
                        <div class="number">
                            <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE action = 'login_success' AND user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                            ?>
                        </div>
                        <div class="text-muted small">Successful Logins</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-mini-card">
                        <div class="number">
                            <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE action IN ('add_password', 'edit_password', 'delete_password') AND user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                            ?>
                        </div>
                        <div class="text-muted small">Password Operations</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-mini-card">
                        <div class="number">
                            <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE action = 'failed_login'");
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                            ?>
                        </div>
                        <div class="text-muted small">Failed Attempts</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="log-list">
            <?php foreach($logs as $log): ?>
            <div class="log-card">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="log-icon" style="background: <?php echo getActionColor($log['action']); ?>20;">
                                <i class="fas <?php echo getActionIcon($log['action']); ?>" style="color: <?php echo getActionColor($log['action']); ?>;"></i>
                            </div>
                            <div>
                                <div class="log-action"><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></div>
                                <div class="log-details"><?php echo sanitizeOutput($log['details']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <span class="log-ip"><i class="fas fa-globe"></i> <?php echo $log['ip_address']; ?></span>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="log-time">
                            <i class="far fa-clock"></i> <?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($logs)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #CBD5E1;"></i>
                <h4 class="mt-3 text-muted">No activity logs found</h4>
                <p>Activities will appear here as you use the system</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <?php if($page < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</body>
</html>