<?php
// auth.php - Authentication logic
require_once 'config.php';
require_once 'security.php';
require_once 'encryption.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $masterKey = $_POST['master_key'];
    
    // Check login attempts
    if (checkLoginAttempts($pdo, $username)) {
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Try again later.']);
        exit();
    }
    
    // Get admin user
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, master_key_hash FROM admins WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        if (Encryption::verifyMasterKey($masterKey, $user['master_key_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['master_key'] = $masterKey;
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            logActivity($pdo, $user['id'], 'login_success', 'User logged in successfully');
            echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
        } else {
            recordFailedAttempt($pdo, $username);
            echo json_encode(['success' => false, 'message' => 'Invalid master key']);
        }
    } else {
        recordFailedAttempt($pdo, $username);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
    exit();
}
?>