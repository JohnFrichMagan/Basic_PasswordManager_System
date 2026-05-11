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

// Get user profile picture for dropdown
$stmt = $pdo->prepare("SELECT profile_picture, full_name FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();
$profilePicture = $userData['profile_picture'];
$fullName = $userData['full_name'];
$displayName = !empty($fullName) ? $fullName : $_SESSION['username'];
$userInitials = strtoupper(substr($displayName, 0, 2));

// Check if profile picture exists
$profilePicturePath = '';
if (!empty($profilePicture) && file_exists('uploads/profile/' . $profilePicture)) {
    $profilePicturePath = 'uploads/profile/' . $profilePicture;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
        .sidebar-menu a i {
            width: 20px;
            font-size: 1.1rem;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #E2E8F0;
        }
        .page-title h1 { color: #0F172A; font-size: 1.5rem; font-weight: 700; margin: 0; }
        .page-title p { color: #64748B; font-size: 0.85rem; margin: 0; }
        
        /* Admin Dropdown Styles */
        .admin-dropdown {
            position: relative;
        }
        
        .admin-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 8px;
            background: transparent;
            border-radius: 40px;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .admin-trigger:hover {
            background: #F1F5F9;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #10B981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .user-avatar-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .admin-name-small {
            font-weight: 600;
            color: #0F172A;
            font-size: 0.85rem;
        }
        
        /* Dropdown Menu */
        .fb-dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2), 0 2px 4px rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s ease;
            z-index: 1000;
        }
        
        .admin-dropdown.active .fb-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .fb-dropdown-header {
            padding: 16px;
            background: white;
            border-radius: 16px 16px 0 0;
        }
        
        .fb-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 12px;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .fb-user-info:hover {
            background: #F1F5F9;
        }
        
        .fb-user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #10B981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .fb-user-avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .fb-user-details h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #0F172A;
            margin: 0 0 4px 0;
        }
        
        .fb-user-details p {
            font-size: 0.75rem;
            color: #64748B;
            margin: 0;
        }
        
        .fb-divider {
            height: 1px;
            background: #E2E8F0;
            margin: 8px 0;
        }
        
        .fb-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #0F172A;
            text-decoration: none;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .fb-dropdown-item:hover {
            background: #F1F5F9;
        }
        
        .fb-dropdown-item i {
            width: 24px;
            font-size: 1.1rem;
            color: #10B981;
        }
        
        .fb-dropdown-item span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .fb-dropdown-item.logout-item i {
            color: #EF4444;
        }
        
        .fb-dropdown-item.logout-item span {
            color: #EF4444;
        }
        
        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #E2E8F0;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2rem;
            color: #10B981;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #0F172A;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            color: #64748B;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .section-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #E2E8F0;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #F1F5F9;
        }
        .section-header h4 {
            color: #0F172A;
            font-weight: 700;
            margin: 0;
        }
        
        .table-custom {
            width: 100%;
        }
        .table-custom th {
            color: #64748B;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.5rem;
            border-bottom: 2px solid #F1F5F9;
        }
        .table-custom td {
            padding: 1rem 0.5rem;
            color: #334155;
            border-bottom: 1px solid #F1F5F9;
        }
        .badge-category {
            background: #F1F5F9;
            color: #0F172A;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .clickable-row {
            cursor: pointer;
            transition: background 0.2s;
        }
        .clickable-row:hover {
            background: #F8FAFC;
        }
        
        .btn-primary {
            background: #10B981;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .main-content { margin-left: 0; }
            .fb-dropdown-menu { width: 300px; right: -20px; }
            .admin-name-small { display: none; }
            .admin-trigger { padding: 5px 8px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-shield-alt"></i> PM System</h3>
            <p>Enterprise Password Management</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="vault.php"><i class="fas fa-lock"></i> Password Vault</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logs.php"><i class="fas fa-history"></i> Activity Logs</a>
            <hr>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Overview of your secure password vault</p>
            </div>
            
            <!-- Admin Dropdown with Profile Picture -->
            <div class="admin-dropdown" id="adminDropdown">
                <div class="admin-trigger" onclick="toggleDropdown(event)">
                    <?php if ($profilePicturePath): ?>
                        <img src="<?php echo $profilePicturePath; ?>?v=<?php echo time(); ?>" class="user-avatar-img" alt="Profile">
                    <?php else: ?>
                        <div class="user-avatar">
                            <?php echo $userInitials; ?>
                        </div>
                    <?php endif; ?>
                    <span class="admin-name-small"><?php echo sanitizeOutput($_SESSION['username']); ?></span>
                </div>
                
                <div class="fb-dropdown-menu">
                    <div class="fb-dropdown-header">
                        <div class="fb-user-info" onclick="window.location.href='settings.php?tab=account'">
                            <?php if ($profilePicturePath): ?>
                                <img src="<?php echo $profilePicturePath; ?>?v=<?php echo time(); ?>" class="fb-user-avatar-img" alt="Profile">
                            <?php else: ?>
                                <div class="fb-user-avatar">
                                    <?php echo $userInitials; ?>
                                </div>
                            <?php endif; ?>
                            <div class="fb-user-details">
                                <h4><?php echo sanitizeOutput($displayName); ?></h4>
                                <p>@<?php echo sanitizeOutput($_SESSION['username']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="fb-divider"></div>
                    
                    <a href="settings.php?tab=account" class="fb-dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Account Settings</span>
                    </a>
                    
                    <a href="logout.php" class="fb-dropdown-item logout-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Log Out</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Row -->
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-database"></i></div>
                    <div class="stat-number"><?php echo $totalPasswords; ?></div>
                    <div class="stat-label">Total Credentials</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-number"><?php echo $weakCount; ?></div>
                    <div class="stat-label">Weak Passwords</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-folder"></i></div>
                    <div class="stat-number"><?php echo count($categories); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Entries -->
        <div class="section-card">
            <div class="section-header">
                <h4><i class="fas fa-clock"></i> Recently Added Credentials</h4>
                <a href="vault.php" class="btn btn-primary btn-sm">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if(count($recentPasswords) > 0): ?>
            <table class="table-custom">
                <thead>
                    <tr><th>Name</th><th>Username</th><th>Category</th><th>Created</th></tr>
                </thead>
                <tbody>
                    <?php foreach($recentPasswords as $item): ?>
                    <tr class="clickable-row" onclick="window.location.href='vault.php'">
                        <td><strong><?php echo sanitizeOutput($item['name']); ?></strong></td>
                        <td><?php echo sanitizeOutput($item['username']); ?></td>
                        <td><span class="badge-category"><?php echo sanitizeOutput($item['category'] ?? 'Uncategorized'); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
             
             <?php if($totalPasswords > 5): ?>
             <div class="text-center mt-3">
                 <small class="text-muted">
                     <i class="fas fa-info-circle"></i> Showing last 5 entries. 
                     <a href="vault.php">View all <?php echo $totalPasswords; ?> credentials</a>
                 </small>
             </div>
             <?php endif; ?>
             
            <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-lock" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                No passwords added yet. Click "Add New Credential" in the Vault page to get started.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Category Distribution -->
        <?php if(!empty($categories)): ?>
        <div class="section-card">
            <div class="section-header">
                <h4><i class="fas fa-chart-pie"></i> Category Distribution</h4>
            </div>
            <div class="row">
                <?php foreach($categories as $cat): ?>
                <div class="col-md-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center p-2" style="background: #F8FAFC; border-radius: 12px;">
                        <span><i class="fas fa-tag"></i> <?php echo sanitizeOutput($cat['category'] ?: 'Uncategorized'); ?></span>
                        <span class="badge-category"><?php echo $cat['count']; ?> items</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Toggle Dropdown
        function toggleDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('adminDropdown');
            dropdown.classList.toggle('active');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('adminDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        // Prevent event propagation on dropdown menu
        document.querySelector('.fb-dropdown-menu')?.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>