<?php
require_once 'config.php';
require_once 'security.php';
require_once 'encryption.php';
requireLogin();

$message = '';
$messageType = '';

// Get user data for account settings
$stmt = $pdo->prepare("SELECT id, username, email, full_name, profile_picture, created_at FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$userInitials = strtoupper(substr($user['full_name'] ?: $user['username'], 0, 2));

// Handle Profile Update (including username)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verifyCSRFToken($_POST['csrf_token']);
    
    $username = sanitizeInput($_POST['username']);
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and can only contain letters, numbers, and underscore";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $stmt->execute([$username, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Username already taken. Please choose another.";
        }
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Email already in use";
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE admins SET username = ?, full_name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$username, $full_name, $email, $_SESSION['user_id']])) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $message = "Profile updated successfully!";
            $messageType = "success";
            logActivity($pdo, $_SESSION['user_id'], 'update_profile', 'Updated profile information');
            // Refresh user data
            $stmt = $pdo->prepare("SELECT id, username, email, full_name, profile_picture, created_at FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            $userInitials = strtoupper(substr($user['full_name'] ?: $user['username'], 0, 2));
        } else {
            $message = "Failed to update profile";
            $messageType = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_picture'])) {
    verifyCSRFToken($_POST['csrf_token']);
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if (!is_dir('uploads/profile')) {
                mkdir('uploads/profile', 0777, true);
            }
            
            if ($user['profile_picture'] && file_exists('uploads/profile/' . $user['profile_picture'])) {
                unlink('uploads/profile/' . $user['profile_picture']);
            }
            
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/profile/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $stmt = $pdo->prepare("UPDATE admins SET profile_picture = ? WHERE id = ?");
                if ($stmt->execute([$new_filename, $_SESSION['user_id']])) {
                    $message = "Profile picture updated successfully!";
                    $messageType = "success";
                    $user['profile_picture'] = $new_filename;
                    logActivity($pdo, $_SESSION['user_id'], 'update_profile_picture', 'Changed profile picture');
                } else {
                    $message = "Failed to update profile picture in database";
                    $messageType = "danger";
                }
            } else {
                $message = "Failed to upload image file";
                $messageType = "danger";
            }
        } else {
            $message = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
            $messageType = "danger";
        }
    } else {
        $message = "Please select an image file to upload";
        $messageType = "danger";
    }
}

// Handle Remove Profile Picture
if (isset($_GET['remove_pic']) && $_GET['remove_pic'] == 1) {
    verifyCSRFToken($_GET['csrf_token']);
    if ($user['profile_picture'] && file_exists('uploads/profile/' . $user['profile_picture'])) {
        unlink('uploads/profile/' . $user['profile_picture']);
    }
    $stmt = $pdo->prepare("UPDATE admins SET profile_picture = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user['profile_picture'] = null;
    $message = "Profile picture removed successfully";
    $messageType = "success";
    logActivity($pdo, $_SESSION['user_id'], 'remove_profile_picture', 'Removed profile picture');
    header("Location: settings.php?msg=" . urlencode($message) . "&type=success");
    exit();
}

// Handle Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    verifyCSRFToken($_POST['csrf_token']);
    
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_new_password'];
    
    if ($new !== $confirm) {
        $message = "New passwords do not match";
        $messageType = "danger";
    } elseif (strlen($new) < 8) {
        $message = "Password must be at least 8 characters";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (password_verify($current, $stmt->fetch()['password_hash'])) {
            $new_hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
                $message = "Login password changed successfully!";
                $messageType = "success";
                logActivity($pdo, $_SESSION['user_id'], 'change_password', 'Changed login password');
            } else {
                $message = "Failed to update password";
                $messageType = "danger";
            }
        } else {
            $message = "Current password is incorrect";
            $messageType = "danger";
        }
    }
}

// Handle Change Master Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_master_key'])) {
    verifyCSRFToken($_POST['csrf_token']);
    
    $current_master = $_POST['current_master_key'];
    $new_master = $_POST['new_master_key'];
    $confirm_master = $_POST['confirm_new_master_key'];
    
    if ($new_master !== $confirm_master) {
        $message = "New master keys do not match";
        $messageType = "danger";
    } elseif (strlen($new_master) < 8) {
        $message = "Master key must be at least 8 characters";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("SELECT master_key_hash FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (Encryption::verifyMasterKey($current_master, $stmt->fetch()['master_key_hash'])) {
            $new_master_hash = Encryption::hashMasterKey($new_master);
            $stmt = $pdo->prepare("UPDATE admins SET master_key_hash = ? WHERE id = ?");
            if ($stmt->execute([$new_master_hash, $_SESSION['user_id']])) {
                $_SESSION['master_key'] = $new_master;
                $message = "Master key changed successfully!";
                $messageType = "success";
                logActivity($pdo, $_SESSION['user_id'], 'change_master_key', 'Changed master encryption key');
            } else {
                $message = "Failed to update master key";
                $messageType = "danger";
            }
        } else {
            $message = "Current master key is incorrect";
            $messageType = "danger";
        }
    }
}

// Handle Export Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_backup'])) {
    $stmt = $pdo->prepare("SELECT * FROM passwords WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $passwords = $stmt->fetchAll();
    
    foreach($passwords as &$pwd) {
        $pwd['decrypted_password'] = Encryption::decrypt($pwd['encrypted_password'], $_SESSION['master_key']);
        unset($pwd['encrypted_password']);
    }
    
    $backup = json_encode([
        'export_date' => date('Y-m-d H:i:s'),
        'version' => APP_VERSION,
        'user' => $_SESSION['username'],
        'passwords' => $passwords
    ], JSON_PRETTY_PRINT);
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="password_backup_' . date('Y-m-d') . '.json"');
    echo $backup;
    exit();
}

$csrf_token = generateCSRFToken();

// Check for URL parameters for message
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — <?php echo APP_NAME; ?></title>
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

        /* Sidebar Styles - matching vault page */
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

        /* Settings Cards */
        .settings-card {
            background:var(--white); border-radius:var(--radius);
            border:1px solid var(--border); box-shadow:var(--shadow-sm);
            padding:1.5rem; margin-bottom:1.5rem;
            transition:all .25s;
        }
        .settings-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
        
        .settings-header {
            display:flex; align-items:center; justify-content:space-between;
            margin-bottom:1.25rem; padding-bottom:1rem;
            border-bottom:2px solid var(--surface);
        }
        .settings-header-left {
            display:flex; align-items:center; gap:12px;
        }
        .settings-header-left i {
            font-size:1.3rem; color:var(--accent);
            width:32px; height:32px; background:rgba(16,185,129,.1);
            border-radius:10px; display:flex; align-items:center; justify-content:center;
        }
        .settings-header-left h4 {
            font-weight:700; color:var(--text); margin:0; font-size:1rem;
        }

        /* Toggle Button */
        .toggle-btn {
            background:var(--surface); border:1px solid var(--border);
            padding:6px 14px; border-radius:30px; font-size:.7rem; font-weight:600;
            color:var(--slate); cursor:pointer; transition:all .2s;
            display:flex; align-items:center; gap:6px;
        }
        .toggle-btn i { font-size:.7rem; transition:transform .3s; }
        .toggle-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .toggle-btn.active i { transform:rotate(180deg); }

        /* Form Container */
        .form-container {
            max-height:0; overflow:hidden; transition:max-height .4s ease-out;
            background:var(--surface); border-radius:16px; margin-top:0;
        }
        .form-container.show {
            max-height:600px; padding:1.25rem; margin-top:1rem;
        }

        /* Profile Picture */
        .profile-preview { text-align:center; margin-bottom:1.5rem; }
        .profile-avatar-img {
            width:100px; height:100px; border-radius:50%; object-fit:cover;
            border:3px solid var(--accent); box-shadow:var(--shadow-sm);
        }
        .profile-avatar-placeholder {
            width:100px; height:100px; background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            margin:0 auto; font-size:2.5rem; font-weight:700; color:#fff;
            border:3px solid rgba(255,255,255,.2);
        }
        .avatar-actions { display:flex; justify-content:center; gap:.5rem; margin-top:1rem; flex-wrap:wrap; }
        .avatar-actions .btn-sm { padding:4px 12px; font-size:.7rem; border-radius:20px; }

        /* Form Elements */
        .form-label { font-weight:600; color:var(--text); margin-bottom:.5rem; font-size:.8rem; }
        .form-label i { color:var(--accent); margin-right:5px; }
        .form-control, .form-select {
            border-radius:10px; border:1px solid var(--border);
            padding:.55rem .85rem; font-size:.83rem; font-family:inherit;
        }
        .form-control:focus, .form-select:focus {
            border-color:var(--accent); box-shadow:0 0 0 3px rgba(16,185,129,.1); outline:none;
        }

        /* Password Input Group */
        .password-input-group { position:relative; display:flex; align-items:center; }
        .password-input-group input { width:100%; padding-right:40px; }
        .password-toggle {
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:var(--slate); cursor:pointer;
            font-size:.9rem; transition:color .2s;
        }
        .password-toggle:hover { color:var(--accent); }

        /* Buttons */
        .btn-primary {
            background:var(--accent); border:none; border-radius:10px;
            padding:.55rem 1.25rem; font-size:.83rem; font-weight:700; color:#fff;
            cursor:pointer; transition:all .2s;
        }
        .btn-primary:hover { background:var(--accent-dark); transform:translateY(-1px); }
        .btn-outline-primary {
            background:transparent; border:1px solid var(--accent);
            border-radius:10px; padding:.55rem 1.25rem; font-size:.83rem;
            font-weight:600; color:var(--accent); cursor:pointer; transition:all .2s;
        }
        .btn-outline-primary:hover { background:var(--accent); color:#fff; }
        .btn-secondary {
            background:var(--surface); border:1px solid var(--border);
            border-radius:10px; padding:.55rem 1.25rem; font-size:.83rem;
            font-weight:600; color:var(--slate); cursor:pointer;
        }
        .btn-secondary:hover { background:var(--border); }

        /* Info Rows */
        .info-row {
            display:flex; justify-content:space-between; align-items:center;
            padding:.75rem 0; border-bottom:1px solid var(--border);
        }
        .info-row:last-child { border-bottom:none; }
        .info-label { font-weight:600; color:var(--text); font-size:.8rem; }
        .info-value { color:var(--text-muted); font-size:.8rem; }
        .badge-secure { background:rgba(16,185,129,.1); color:var(--accent); padding:4px 10px; border-radius:20px; font-size:.7rem; font-weight:600; }

        /* Export Card */
        .export-card { text-align:center; }
        .export-icon { font-size:2.5rem; color:var(--accent); margin-bottom:.75rem; }

        /* Username Status */
        .username-status { font-size:.7rem; margin-top:.25rem; }
        .username-status.available { color:var(--accent); }
        .username-status.taken { color:var(--danger); }
        .username-status.invalid { color:var(--warn); }

        /* Toast Notification */
        .toast-msg {
            position:fixed; top:20px; right:20px; padding:10px 20px; border-radius:12px;
            color:#fff; font-size:.83rem; font-weight:600; z-index:9999;
            display:none; animation:slideInToast .3s ease; box-shadow:var(--shadow-md);
        }
        @keyframes slideInToast { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }

        /* Responsive */
        @media (max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .sidebar-overlay.open { display:block; }
            .main-wrap { margin-left:0; padding:1rem; }
            .mobile-menu-btn { display:flex; align-items:center; }
            .settings-header { flex-wrap:wrap; gap:.5rem; }
            .info-row { flex-direction:column; align-items:flex-start; gap:.25rem; }
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
        <a href="settings.php" class="nav-item active">
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
                <h1><i class="fas fa-gear" style="color:var(--accent);margin-right:8px;font-size:1.1rem;"></i>Settings</h1>
                <p>Manage your account and security preferences</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Account Settings Card -->
        <div class="col-md-12">
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-header-left">
                        <i class="fas fa-user-circle"></i>
                        <h4>Account Settings</h4>
                    </div>
                </div>

                <!-- Profile Picture -->
                <div class="profile-preview">
                    <?php if ($user['profile_picture'] && file_exists('uploads/profile/' . $user['profile_picture'])): ?>
                        <img src="uploads/profile/<?php echo $user['profile_picture']; ?>?t=<?php echo time(); ?>" class="profile-avatar-img" alt="Profile">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <?php echo $userInitials; ?>
                        </div>
                    <?php endif; ?>
                    <div class="avatar-actions mt-2">
                        <form method="POST" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="update_picture" value="1">
                            <label class="btn-secondary btn-sm" style="cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                <i class="fas fa-camera"></i> Change
                                <input type="file" name="profile_picture" id="profilePicInput" style="display:none;" accept="image/*">
                            </label>
                        </form>
                        <?php if ($user['profile_picture']): ?>
                            <a href="settings.php?remove_pic=1&csrf_token=<?php echo $csrf_token; ?>" class="btn-secondary btn-sm" style="text-decoration:none;" onclick="return confirm('Remove profile picture?')">
                                <i class="fas fa-trash-alt"></i> Remove
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Username *</label>
                            <input type="text" name="username" id="username" class="form-control" 
                                   value="<?php echo sanitizeOutput($user['username']); ?>" 
                                   required pattern="[a-zA-Z0-9_]{3,20}" 
                                   title="3-20 characters, letters, numbers, underscore only">
                            <div id="usernameStatus" class="username-status"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-user-tag"></i> Full Name</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo sanitizeOutput($user['full_name']); ?>" 
                                   placeholder="Enter your full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email Address *</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                   value="<?php echo sanitizeOutput($user['email']); ?>" required>
                            <div id="emailStatus" class="username-status"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Member Since</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn-primary" id="saveProfileBtn">
                            <i class="fas fa-save"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Settings Section Header -->
        <div class="col-md-12">
            <h4 class="mb-3 mt-2" style="color: var(--text); font-weight: 700;">
                <i class="fas fa-shield-alt" style="color: var(--accent); margin-right: 8px;"></i> Security Settings
            </h4>
        </div>

        <!-- Change Password Card -->
        <div class="col-md-6">
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-header-left">
                        <i class="fas fa-key"></i>
                        <h4>Change Login Password</h4>
                    </div>
                    <button class="toggle-btn" id="togglePasswordBtn" type="button">
                        <i class="fas fa-chevron-down"></i>
                        <span>Show Form</span>
                    </button>
                </div>
                <div class="form-container" id="passwordFormContainer">
                    <form method="POST" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-lock"></i> Current Password</label>
                            <div class="password-input-group">
                                <input type="password" name="current_password" id="currentPassword" class="form-control" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> New Password</label>
                            <div class="password-input-group">
                                <input type="password" name="new_password" id="newPassword" class="form-control" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="pwdStrength"></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" name="confirm_new_password" id="confirmNewPassword" class="form-control" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmNewPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="confirmMatchMsg"></small>
                        </div>
                        <button type="submit" name="change_password" class="btn-primary w-100">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Master Key Card -->
        <div class="col-md-6">
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-header-left">
                        <i class="fas fa-fingerprint"></i>
                        <h4>Change Master Key</h4>
                    </div>
                    <button class="toggle-btn" id="toggleMasterKeyBtn" type="button">
                        <i class="fas fa-chevron-down"></i>
                        <span>Show Form</span>
                    </button>
                </div>
                <div class="form-container" id="masterKeyFormContainer">
                    <form method="POST" id="masterKeyForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-shield-alt"></i> Current Master Key</label>
                            <div class="password-input-group">
                                <input type="password" name="current_master_key" id="currentMasterKey" class="form-control" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('currentMasterKey')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-fingerprint"></i> New Master Key</label>
                            <div class="password-input-group">
                                <input type="password" name="new_master_key" id="newMasterKey" class="form-control" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('newMasterKey')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Required to decrypt your passwords. Keep it safe!</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-check-circle"></i> Confirm New Master Key</label>
                            <div class="password-input-group">
                                <input type="password" name="confirm_new_master_key" id="confirmNewMasterKey" class="form-control" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmNewMasterKey')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="masterKeyMatchMsg"></small>
                        </div>
                        <button type="submit" name="change_master_key" class="btn-primary w-100">
                            <i class="fas fa-save"></i> Update Master Key
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Export Backup Card -->
        <div class="col-md-6">
            <div class="settings-card export-card">
                <div class="settings-header">
                    <div class="settings-header-left">
                        <i class="fas fa-download"></i>
                        <h4>Export Backup</h4>
                    </div>
                </div>
                <div class="export-icon">
                    <i class="fas fa-database"></i>
                </div>
                <p class="text-muted" style="font-size:.8rem;">Export all your passwords as an encrypted JSON backup file.</p>
                <form method="POST" id="exportForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" name="export_backup" class="btn-outline-primary w-100">
                        <i class="fas fa-download"></i> Export Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- Session Info Card -->
        <div class="col-md-6">
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-header-left">
                        <i class="fas fa-clock"></i>
                        <h4>Session Information</h4>
                    </div>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-hourglass-half"></i> Session Timeout:</span>
                    <span class="info-value"><?php echo defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT / 60 : '30'; ?> minutes</span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar-alt"></i> Last Activity:</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i:s', $_SESSION['last_activity']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-network-wired"></i> IP Address:</span>
                    <span class="info-value"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user-shield"></i> Security Status:</span>
                    <span class="info-value"><span class="badge-secure"><i class="fas fa-check-circle"></i> Active & Secure</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Message -->
<div id="toastMsg" class="toast-msg"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar functions
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

    // Toast notification
    function toast(msg, ok=true) {
        const t = $('#toastMsg');
        t.css('background', ok ? '#10B981' : '#EF4444').text(msg).fadeIn(250);
        setTimeout(() => t.fadeOut(300), 2800);
    }

    <?php if($message): ?>
    toast('<?php echo addslashes($message); ?>', <?php echo $messageType === 'success' ? 'true' : 'false'; ?>);
    <?php endif; ?>

    // Toggle Password Visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Toggle Password Form
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordFormContainer = document.getElementById('passwordFormContainer');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function() {
            passwordFormContainer.classList.toggle('show');
            this.classList.toggle('active');
            const span = this.querySelector('span');
            span.textContent = passwordFormContainer.classList.contains('show') ? 'Hide Form' : 'Show Form';
        });
    }

    // Toggle Master Key Form
    const toggleMasterKeyBtn = document.getElementById('toggleMasterKeyBtn');
    const masterKeyFormContainer = document.getElementById('masterKeyFormContainer');
    if (toggleMasterKeyBtn) {
        toggleMasterKeyBtn.addEventListener('click', function() {
            masterKeyFormContainer.classList.toggle('show');
            this.classList.toggle('active');
            const span = this.querySelector('span');
            span.textContent = masterKeyFormContainer.classList.contains('show') ? 'Hide Form' : 'Show Form';
        });
    }

    // Password Strength Checker
    const newPassword = document.getElementById('newPassword');
    const confirmNewPassword = document.getElementById('confirmNewPassword');
    const confirmMatchMsg = document.getElementById('confirmMatchMsg');

    function checkPasswordStrength() {
        if (!newPassword) return;
        const pwd = newPassword.value;
        let score = 0;
        if(pwd.length >= 8) score++;
        if(pwd.length >= 12) score++;
        if(/[A-Z]/.test(pwd)) score++;
        if(/[a-z]/.test(pwd)) score++;
        if(/[0-9]/.test(pwd)) score++;
        if(/[^A-Za-z0-9]/.test(pwd)) score++;
        
        let strength = score <= 2 ? 'Weak' : (score <= 4 ? 'Medium' : 'Strong');
        let color = strength === 'Strong' ? '#10B981' : (strength === 'Medium' ? '#F59E0B' : '#EF4444');
        let icon = strength === 'Strong' ? 'fa-check-circle' : (strength === 'Medium' ? 'fa-chart-line' : 'fa-exclamation-triangle');
        
        const strengthEl = document.getElementById('pwdStrength');
        if (strengthEl) {
            strengthEl.innerHTML = `<i class="fas ${icon}" style="color: ${color};"></i> <span style="color: ${color}; font-weight: 500;">Strength: ${strength}</span>`;
        }
        checkPasswordMatch();
    }

    function checkPasswordMatch() {
        if (confirmNewPassword && newPassword) {
            const newPwd = newPassword.value;
            const confirmPwd = confirmNewPassword.value;
            if (confirmPwd.length > 0) {
                if (newPwd === confirmPwd) {
                    confirmMatchMsg.innerHTML = `<i class="fas fa-check-circle" style="color: #10B981;"></i> <span style="color: #10B981;">Passwords match!</span>`;
                } else {
                    confirmMatchMsg.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: #EF4444;"></i> <span style="color: #EF4444;">Passwords do not match!</span>`;
                }
            } else {
                confirmMatchMsg.innerHTML = '';
            }
        }
    }

    if (newPassword) newPassword.addEventListener('keyup', checkPasswordStrength);
    if (confirmNewPassword) confirmNewPassword.addEventListener('keyup', checkPasswordMatch);

    // Master Key Match Checker
    const newMasterKey = document.getElementById('newMasterKey');
    const confirmNewMasterKey = document.getElementById('confirmNewMasterKey');
    const masterKeyMatchMsg = document.getElementById('masterKeyMatchMsg');

    function checkMasterKeyMatch() {
        if (confirmNewMasterKey && newMasterKey) {
            const newKey = newMasterKey.value;
            const confirmKey = confirmNewMasterKey.value;
            if (confirmKey.length > 0) {
                if (newKey === confirmKey) {
                    masterKeyMatchMsg.innerHTML = `<i class="fas fa-check-circle" style="color: #10B981;"></i> <span style="color: #10B981;">Master keys match!</span>`;
                } else {
                    masterKeyMatchMsg.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: #EF4444;"></i> <span style="color: #EF4444;">Master keys do not match!</span>`;
                }
            } else {
                masterKeyMatchMsg.innerHTML = '';
            }
        }
    }

    if (confirmNewMasterKey) confirmNewMasterKey.addEventListener('keyup', checkMasterKeyMatch);

    // Username Availability Check
    const usernameInput = document.getElementById('username');
    const usernameStatus = document.getElementById('usernameStatus');
    const emailInput = document.getElementById('email');
    const emailStatus = document.getElementById('emailStatus');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    let usernameValid = true;
    let emailValid = true;

    function checkUsernameAvailability() {
        if (!usernameInput) return;
        const username = usernameInput.value;
        const originalUsername = '<?php echo $user['username']; ?>';
        
        if (username === originalUsername) {
            usernameStatus.innerHTML = '<i class="fas fa-check-circle"></i> Current username';
            usernameStatus.className = 'username-status available';
            usernameValid = true;
            validateForm();
            return;
        }
        
        if (username.length < 3) {
            usernameStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Username must be at least 3 characters';
            usernameStatus.className = 'username-status invalid';
            usernameValid = false;
            validateForm();
            return;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            usernameStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Only letters, numbers, and underscore allowed';
            usernameStatus.className = 'username-status invalid';
            usernameValid = false;
            validateForm();
            return;
        }
        
        fetch('check_username.php?username=' + encodeURIComponent(username))
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    usernameStatus.innerHTML = '<i class="fas fa-check-circle"></i> Username is available!';
                    usernameStatus.className = 'username-status available';
                    usernameValid = true;
                } else {
                    usernameStatus.innerHTML = '<i class="fas fa-times-circle"></i> Username already taken';
                    usernameStatus.className = 'username-status taken';
                    usernameValid = false;
                }
                validateForm();
            })
            .catch(() => {
                usernameValid = true;
                validateForm();
            });
    }

    function checkEmailAvailability() {
        if (!emailInput) return;
        const email = emailInput.value;
        const originalEmail = '<?php echo $user['email']; ?>';
        
        if (email === originalEmail) {
            emailStatus.innerHTML = '';
            emailValid = true;
            validateForm();
            return;
        }
        
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            emailStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Invalid email format';
            emailStatus.className = 'username-status invalid';
            emailValid = false;
            validateForm();
            return;
        }
        
        fetch('check_email.php?email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    emailStatus.innerHTML = '<i class="fas fa-check-circle"></i> Email is available!';
                    emailStatus.className = 'username-status available';
                    emailValid = true;
                } else {
                    emailStatus.innerHTML = '<i class="fas fa-times-circle"></i> Email already in use';
                    emailStatus.className = 'username-status taken';
                    emailValid = false;
                }
                validateForm();
            })
            .catch(() => {
                emailValid = true;
                validateForm();
            });
    }

    function validateForm() {
        if (saveProfileBtn) {
            saveProfileBtn.disabled = !(usernameValid && emailValid);
        }
    }

    if (usernameInput) {
        usernameInput.addEventListener('keyup', checkUsernameAvailability);
        usernameInput.addEventListener('blur', checkUsernameAvailability);
    }
    if (emailInput) {
        emailInput.addEventListener('keyup', checkEmailAvailability);
        emailInput.addEventListener('blur', checkEmailAvailability);
    }

    // Auto submit file input when selected
    const profilePicInput = document.getElementById('profilePicInput');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                toast('Uploading profile picture...', true);
                this.closest('form').submit();
            }
        });
    }

    // Form submit handlers
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        $.post('settings.php', $(this).serialize())
            .done(() => { toast('Profile updated successfully!'); setTimeout(() => location.reload(), 1200); })
            .fail(() => toast('Failed to update profile', false));
    });

    $('#passwordForm').on('submit', function(e) {
        e.preventDefault();
        $.post('settings.php', $(this).serialize())
            .done(() => { toast('Password changed successfully!'); setTimeout(() => location.reload(), 1200); })
            .fail(() => toast('Failed to change password', false));
    });

    $('#masterKeyForm').on('submit', function(e) {
        e.preventDefault();
        $.post('settings.php', $(this).serialize())
            .done(() => { toast('Master key changed successfully!'); setTimeout(() => location.reload(), 1200); })
            .fail(() => toast('Failed to change master key', false));
    });

    $('#exportForm').on('submit', function(e) {
        toast('Preparing your backup file...', true);
    });

    validateForm();
</script>
</body>
</html>