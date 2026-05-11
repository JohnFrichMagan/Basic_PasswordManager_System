<?php
// security.php - CSRF, sanitization, and security helpers

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit();
    }
    return true;
}

// Sanitize output for XSS prevention
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Sanitize input for database
function sanitizeInput($data) {
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate random password
function generatePassword($length = 16) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    return substr(str_shuffle($chars), 0, $length);
}

// Check password strength
function checkPasswordStrength($password) {
    $score = 0;
    if (strlen($password) >= 8) $score++;
    if (strlen($password) >= 12) $score++;
    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;
    
    if ($score <= 2) return 'Weak';
    if ($score <= 4) return 'Medium';
    return 'Strong';
}

// Get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

// Log activity
function logActivity($pdo, $user_id, $action, $details = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, getClientIP(), $_SERVER['HTTP_USER_AGENT'] ?? null]);
    } catch (Exception $e) {
        // Silent fail for logging errors
    }
}

// Check login attempts
function checkLoginAttempts($pdo, $username) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE action = 'failed_login' AND details LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute(["%$username%"]);
    return $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

// Record failed attempt
function recordFailedAttempt($pdo, $username) {
    logActivity($pdo, 0, 'failed_login', "Failed login attempt for username: $username");
}
?>