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
    <title>Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #F8FAFC; font-family: 'Inter', sans-serif; }
        
        /* Sidebar Styles */
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
        
        /* ========== BEAUTIFUL NOTIFICATION STYLES ========== */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .notification {
            background: white;
            border-radius: 16px;
            padding: 0;
            margin-bottom: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            animation: slideInRight 0.3s ease forwards;
            overflow: hidden;
            position: relative;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification.fade-out {
            animation: slideOutRight 0.3s ease forwards;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            gap: 12px;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .notification-icon.success {
            background: #D1FAE5;
            color: #10B981;
        }
        
        .notification-icon.danger {
            background: #FEE2E2;
            color: #EF4444;
        }
        
        .notification-icon.warning {
            background: #FEF3C7;
            color: #F59E0B;
        }
        
        .notification-icon.info {
            background: #DBEAFE;
            color: #3B82F6;
        }
        
        .notification-text {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        
        .notification-title.success { color: #059669; }
        .notification-title.danger { color: #DC2626; }
        .notification-title.warning { color: #D97706; }
        .notification-title.info { color: #2563EB; }
        
        .notification-message {
            font-size: 0.8rem;
            color: #64748B;
            line-height: 1.4;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #94A3B8;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 4px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .notification-close:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        
        .notification-progress {
            height: 3px;
            width: 100%;
            animation: progress 4s linear forwards;
        }
        
        .notification-progress.success { background: #10B981; }
        .notification-progress.danger { background: #EF4444; }
        .notification-progress.warning { background: #F59E0B; }
        .notification-progress.info { background: #3B82F6; }
        
        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }
        
        /* Settings Card Styles */
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #E2E8F0;
            transition: all 0.3s;
        }
        .settings-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .settings-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #F1F5F9;
        }
        .settings-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .settings-header-left i {
            font-size: 1.5rem;
            color: #10B981;
        }
        .settings-header-left h4 {
            color: #0F172A;
            font-weight: 700;
            margin: 0;
        }
        
        /* Toggle Button Styles */
        .toggle-btn {
            background: #F1F5F9;
            border: none;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748B;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toggle-btn i {
            font-size: 0.8rem;
            transition: transform 0.3s;
        }
        .toggle-btn.active {
            background: #10B981;
            color: white;
        }
        .toggle-btn.active i {
            transform: rotate(180deg);
        }
        .toggle-btn:hover {
            background: #10B981;
            color: white;
        }
        
        /* Form Container */
        .form-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out, padding 0.3s ease;
            background: #F8FAFC;
            border-radius: 16px;
            margin-top: 0;
        }
        .form-container.show {
            max-height: 600px;
            padding: 1.25rem;
            margin-top: 1rem;
        }
        
        /* Profile Picture */
        .profile-preview {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10B981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            cursor: default;
        }
        .profile-avatar-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #10B981;
        }
        
        /* Button Group Centered */
        .btn-group-centered {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .form-label {
            font-weight: 600;
            color: #0F172A;
            margin-bottom: 0.5rem;
        }
        
        .password-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-input-group input {
            width: 100%;
            padding: 10px 45px 10px 14px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            transition: all 0.3s;
        }
        .password-input-group input:focus {
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
            outline: none;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94A3B8;
            transition: all 0.2s;
            background: transparent;
            border: none;
            font-size: 1rem;
        }
        .password-toggle:hover {
            color: #10B981;
        }
        
        .btn-primary {
            background: #10B981;
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
        }
        .btn-primary:hover { background: #059669; transform: translateY(-2px); }
        .btn-outline-primary {
            border-color: #10B981;
            color: #10B981;
            border-radius: 12px;
        }
        .btn-outline-primary:hover {
            background: #10B981;
            color: white;
        }
        
        /* Export Card */
        .export-card {
            text-align: center;
        }
        .export-icon {
            font-size: 3rem;
            color: #10B981;
            margin-bottom: 1rem;
        }
        
        /* Session Info */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #F1F5F9;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #0F172A;
        }
        .info-value {
            color: #64748B;
            font-family: monospace;
        }
        
        /* File Input Styling */
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .custom-file-btn {
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 10px 20px;
            color: #0F172A;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .custom-file-btn:hover {
            background: #10B981;
            color: white;
            border-color: #10B981;
        }
        
        /* Username availability indicator */
        .username-status {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .username-status.available {
            color: #10B981;
        }
        .username-status.taken {
            color: #EF4444;
        }
        .username-status.invalid {
            color: #F59E0B;
        }
        
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .main-content { margin-left: 0; }
            .notification-container { max-width: 90%; right: 5%; left: 5%; }
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
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
            <a href="logs.php"><i class="fas fa-history"></i> Activity Logs</a>
            <hr>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1>Settings</h1>
                <p>Manage your account and security preferences</p>
            </div>
            <div class="user-avatar">
            </div>
        </div>
        
        <!-- Beautiful Notification Container -->
        <div class="notification-container" id="notificationContainer"></div>
        
        <div class="row">
            <!-- ==================== ACCOUNT SETTINGS SECTION ==================== -->
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
                            <div class="profile-avatar">
                                <?php echo $userInitials; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    
                    <form method="POST" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Username *</label>
                                <input type="text" name="username" id="username" class="form-control" value="<?php echo sanitizeOutput($user['username']); ?>" required pattern="[a-zA-Z0-9_]{3,20}" title="3-20 characters, letters, numbers, underscore only">
                                <div id="usernameStatus" class="username-status"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user-tag"></i> Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo sanitizeOutput($user['full_name']); ?>" placeholder="Enter your full name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email Address *</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?php echo sanitizeOutput($user['email']); ?>" required>
                                <div id="emailStatus" class="username-status"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Member Since</label>
                                <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled>
                            </div>
                        </div>
                        <div class="btn-group-centered">
                            <button type="submit" class="btn-primary" id="saveProfileBtn">
                                <i class="fas fa-save"></i> Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- ==================== SECURITY SETTINGS SECTION ==================== -->
            <div class="col-md-12" id="security-section">
                <h4 class="mb-3 mt-2" style="color: #0F172A; font-weight: 700;"><i class="fas fa-shield-alt text-success"></i> Security Settings</h4>
            </div>
            
            <!-- Change Password Card -->
            <div class="col-md-6">
                <div class="settings-card">
                    <div class="settings-header">
                        <div class="settings-header-left">
                            <i class="fas fa-key"></i>
                            <h4>Change Login Password</h4>
                        </div>
                        <button class="toggle-btn" id="togglePasswordBtn">
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
                                    <input type="password" name="current_password" id="currentPassword" class="form-control" required placeholder="Enter your current password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-key"></i> New Password</label>
                                <div class="password-input-group">
                                    <input type="password" name="new_password" id="newPassword" class="form-control" required placeholder="Enter new password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted" id="pwdStrength"></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                                <div class="password-input-group">
                                    <input type="password" name="confirm_new_password" id="confirmNewPassword" class="form-control" required placeholder="Confirm your new password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirmNewPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted" id="confirmMatchMsg"></small>
                            </div>
                            <button type="submit" name="change_password" class="btn-primary w-100" id="submitPasswordBtn">
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
                        <button class="toggle-btn" id="toggleMasterKeyBtn">
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
                                    <input type="password" name="current_master_key" id="currentMasterKey" class="form-control" required placeholder="Enter your current master key">
                                    <button type="button" class="password-toggle" onclick="togglePassword('currentMasterKey')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-fingerprint"></i> New Master Key</label>
                                <div class="password-input-group">
                                    <input type="password" name="new_master_key" id="newMasterKey" class="form-control" required placeholder="Enter new master key">
                                    <button type="button" class="password-toggle" onclick="togglePassword('newMasterKey')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted"><i class="fas fa-info-circle"></i> This key is required to decrypt your passwords. Keep it safe!</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-check-circle"></i> Confirm New Master Key</label>
                                <div class="password-input-group">
                                    <input type="password" name="confirm_new_master_key" id="confirmNewMasterKey" class="form-control" required placeholder="Confirm your new master key">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirmNewMasterKey')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted" id="masterKeyMatchMsg"></small>
                            </div>
                            <button type="submit" name="change_master_key" class="btn-primary w-100" id="submitMasterKeyBtn">
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
                    <p class="text-muted">Export all your passwords as an encrypted JSON backup file.</p>
                    <form method="POST" id="exportForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" name="export_backup" class="btn-outline-primary w-100">
                            <i class="fas fa-download"></i> Export Encrypted Backup
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
                        <span class="info-value"><?php echo SESSION_TIMEOUT / 60; ?> minutes</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar-alt"></i> Last Activity:</span>
                        <span class="info-value"><?php echo date('Y-m-d H:i:s', $_SESSION['last_activity']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-network-wired"></i> IP Address:</span>
                        <span class="info-value"><?php echo getClientIP(); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user-shield"></i> Security Status:</span>
                        <span class="info-value"><span class="badge" style="background: #10B98120; color: #10B981;"><i class="fas fa-check-circle"></i> Active & Secure</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ========== BEAUTIFUL NOTIFICATION FUNCTION ==========
        function showNotification(message, type = 'success', title = '') {
            const container = document.getElementById('notificationContainer');
            const titles = {
                success: 'Success!',
                danger: 'Error!',
                warning: 'Warning!',
                info: 'Information'
            };
            
            const notificationTitle = title || titles[type] || 'Notification';
            const icons = {
                success: 'fa-check-circle',
                danger: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.innerHTML = `
                <div class="notification-content">
                    <div class="notification-icon ${type}">
                        <i class="fas ${icons[type]}"></i>
                    </div>
                    <div class="notification-text">
                        <div class="notification-title ${type}">${notificationTitle}</div>
                        <div class="notification-message">${message}</div>
                    </div>
                    <button class="notification-close" onclick="this.closest('.notification').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="notification-progress ${type}"></div>
            `;
            
            container.appendChild(notification);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.add('fade-out');
                    setTimeout(() => {
                        if (notification.parentNode) notification.remove();
                    }, 300);
                }
            }, 4000);
            
            // Progress bar animation
            const progress = notification.querySelector('.notification-progress');
            if (progress) {
                progress.style.animation = 'progress 4s linear forwards';
            }
        }
        
        // Check URL parameters for messages
        <?php if($message): ?>
        showNotification('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
        <?php endif; ?>
        
        // Handle form submissions with AJAX for better UX
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('settings.php', {
                method: 'POST',
                body: formData
            }).then(response => response.text()).then(() => {
                showNotification('Profile updated successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            }).catch(() => {
                showNotification('Failed to update profile', 'danger');
            });
        });
        
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('settings.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                showNotification('Password changed successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            }).catch(() => {
                showNotification('Failed to change password', 'danger');
            });
        });
        
        document.getElementById('masterKeyForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('settings.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                showNotification('Master key changed successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            }).catch(() => {
                showNotification('Failed to change master key', 'danger');
            });
        });
        
        document.getElementById('exportForm')?.addEventListener('submit', function(e) {
            showNotification('Preparing your backup file...', 'info');
        });
        
        // Toggle Password Function
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
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
                const text = this.querySelector('span');
                text.textContent = passwordFormContainer.classList.contains('show') ? 'Hide Form' : 'Show Form';
                if (passwordFormContainer.classList.contains('show')) {
                    setTimeout(() => {
                        const firstInput = passwordFormContainer.querySelector('input');
                        if (firstInput) firstInput.focus();
                    }, 100);
                }
            });
        }
        
        // Toggle Master Key Form
        const toggleMasterKeyBtn = document.getElementById('toggleMasterKeyBtn');
        const masterKeyFormContainer = document.getElementById('masterKeyFormContainer');
        
        if (toggleMasterKeyBtn) {
            toggleMasterKeyBtn.addEventListener('click', function() {
                masterKeyFormContainer.classList.toggle('show');
                this.classList.toggle('active');
                const text = this.querySelector('span');
                text.textContent = masterKeyFormContainer.classList.contains('show') ? 'Hide Form' : 'Show Form';
                if (masterKeyFormContainer.classList.contains('show')) {
                    setTimeout(() => {
                        const firstInput = masterKeyFormContainer.querySelector('input');
                        if (firstInput) firstInput.focus();
                    }, 100);
                }
            });
        }
        
        // Username Availability Check
        const usernameInput = document.getElementById('username');
        const usernameStatus = document.getElementById('usernameStatus');
        const emailInput = document.getElementById('email');
        const emailStatus = document.getElementById('emailStatus');
        const saveProfileBtn = document.getElementById('saveProfileBtn');
        let usernameValid = true;
        let emailValid = true;
        
        function checkUsernameAvailability() {
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
        document.getElementById('profilePicInput')?.addEventListener('change', function() {
            if (this.files.length > 0) {
                showNotification('Uploading profile picture...', 'info');
                this.form.submit();
            }
        });
        
        // Password Strength Checker
        const newPasswordInput = document.getElementById('newPassword');
        const confirmNewPassword = document.getElementById('confirmNewPassword');
        const confirmMatchMsg = document.getElementById('confirmMatchMsg');
        const submitPasswordBtn = document.getElementById('submitPasswordBtn');
        
        if (newPasswordInput) {
            newPasswordInput.addEventListener('keyup', function() {
                const pwd = this.value;
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
                
                document.getElementById('pwdStrength').innerHTML = `<i class="fas ${icon}" style="color: ${color};"></i> <span style="color: ${color}; font-weight: 500;">Strength: ${strength}</span>`;
                checkPasswordMatch();
            });
        }
        
        function checkPasswordMatch() {
            if (confirmNewPassword && newPasswordInput) {
                const newPwd = newPasswordInput.value;
                const confirmPwd = confirmNewPassword.value;
                
                if (confirmPwd.length > 0) {
                    if (newPwd === confirmPwd) {
                        confirmMatchMsg.innerHTML = `<i class="fas fa-check-circle" style="color: #10B981;"></i> <span style="color: #10B981;">Passwords match!</span>`;
                        if (submitPasswordBtn) submitPasswordBtn.disabled = false;
                    } else {
                        confirmMatchMsg.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: #EF4444;"></i> <span style="color: #EF4444;">Passwords do not match!</span>`;
                        if (submitPasswordBtn) submitPasswordBtn.disabled = true;
                    }
                } else {
                    confirmMatchMsg.innerHTML = '';
                    if (submitPasswordBtn) submitPasswordBtn.disabled = false;
                }
            }
        }
        
        if (confirmNewPassword) {
            confirmNewPassword.addEventListener('keyup', checkPasswordMatch);
        }
        
        // Master Key Match Checker
        const newMasterKey = document.getElementById('newMasterKey');
        const confirmNewMasterKey = document.getElementById('confirmNewMasterKey');
        const masterKeyMatchMsg = document.getElementById('masterKeyMatchMsg');
        const submitMasterKeyBtn = document.getElementById('submitMasterKeyBtn');
        
        function checkMasterKeyMatch() {
            if (confirmNewMasterKey && newMasterKey) {
                const newKey = newMasterKey.value;
                const confirmKey = confirmNewMasterKey.value;
                
                if (confirmKey.length > 0) {
                    if (newKey === confirmKey) {
                        masterKeyMatchMsg.innerHTML = `<i class="fas fa-check-circle" style="color: #10B981;"></i> <span style="color: #10B981;">Master keys match!</span>`;
                        if (submitMasterKeyBtn) submitMasterKeyBtn.disabled = false;
                    } else {
                        masterKeyMatchMsg.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: #EF4444;"></i> <span style="color: #EF4444;">Master keys do not match!</span>`;
                        if (submitMasterKeyBtn) submitMasterKeyBtn.disabled = true;
                    }
                } else {
                    masterKeyMatchMsg.innerHTML = '';
                    if (submitMasterKeyBtn) submitMasterKeyBtn.disabled = false;
                }
            }
        }
        
        if (confirmNewMasterKey) {
            confirmNewMasterKey.addEventListener('keyup', checkMasterKeyMatch);
        }
        
        validateForm();
    </script>
</body>
</html>