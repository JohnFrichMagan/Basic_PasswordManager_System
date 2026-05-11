<?php
require_once 'config.php';
require_once 'security.php';
require_once 'encryption.php';
requireLogin();

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if CSRF token exists
if (!isset($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Missing CSRF token']);
    exit();
}

// Verify CSRF token
verifyCSRFToken($_POST['csrf_token']);

// Validate required fields
if (!isset($_POST['name']) || empty($_POST['name'])) {
    echo json_encode(['success' => false, 'message' => 'Site/App name is required']);
    exit();
}

if (!isset($_POST['username']) || empty($_POST['username'])) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit();
}

if (!isset($_POST['password']) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit();
}

if (!isset($_POST['category']) || empty($_POST['category'])) {
    echo json_encode(['success' => false, 'message' => 'Category is required']);
    exit();
}

// Get and sanitize data
$name = sanitizeInput($_POST['name']);
$url = isset($_POST['url']) ? sanitizeInput($_POST['url']) : '';
$username = sanitizeInput($_POST['username']);
$password = $_POST['password'];
$notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
$category = sanitizeInput($_POST['category']);

// Function to get icon based on site name
function getIconForSite($name) {
    $nameLower = strtolower($name);
    $icons = [
        // Social Media
        'facebook' => 'fab fa-facebook', 'fb' => 'fab fa-facebook',
        'instagram' => 'fab fa-instagram', 'ig' => 'fab fa-instagram',
        'twitter' => 'fab fa-twitter', 'x' => 'fab fa-twitter',
        'tiktok' => 'fab fa-tiktok',
        'linkedin' => 'fab fa-linkedin',
        'youtube' => 'fab fa-youtube', 'yt' => 'fab fa-youtube',
        'snapchat' => 'fab fa-snapchat',
        'telegram' => 'fab fa-telegram',
        'whatsapp' => 'fab fa-whatsapp',
        'discord' => 'fab fa-discord',
        'twitch' => 'fab fa-twitch',
        'viber' => 'fab fa-viber',
        'messenger' => 'fab fa-facebook-messenger',
        'reddit' => 'fab fa-reddit',
        'pinterest' => 'fab fa-pinterest',
        
        // Tech & Email
        'google' => 'fab fa-google', 'gmail' => 'fab fa-google',
        'microsoft' => 'fab fa-microsoft', 'outlook' => 'fab fa-microsoft', 'hotmail' => 'fab fa-microsoft', 'teams' => 'fab fa-microsoft', 'onedrive' => 'fab fa-microsoft',
        'apple' => 'fab fa-apple', 'icloud' => 'fab fa-apple',
        'yahoo' => 'fab fa-yahoo',
        'protonmail' => 'fas fa-envelope',
        'github' => 'fab fa-github', 'git' => 'fab fa-github',
        'gitlab' => 'fab fa-gitlab',
        'slack' => 'fab fa-slack',
        'zoom' => 'fab fa-zoom',
        
        // Finance & Banks (Philippines)
        'bdo' => 'fas fa-university', 'banco de oro' => 'fas fa-university',
        'bpi' => 'fas fa-university', 'bank of the philippine islands' => 'fas fa-university',
        'metrobank' => 'fas fa-university',
        'security bank' => 'fas fa-shield-alt',
        'rcbc' => 'fas fa-chart-line',
        'unionbank' => 'fas fa-building', 'ubp' => 'fas fa-building',
        'chinabank' => 'fas fa-dragon',
        'eastwest' => 'fas fa-globe-asia',
        'psbank' => 'fas fa-hand-holding-usd',
        'pbcom' => 'fas fa-chart-line',
        'maybank' => 'fas fa-leaf',
        'cimb' => 'fas fa-mobile-alt',
        'ing' => 'fas fa-globe',
        'komo' => 'fas fa-cloud',
        'tonik' => 'fas fa-mobile-alt',
        'landbank' => 'fas fa-landmark',
        'pnb' => 'fas fa-landmark',
        'aub' => 'fas fa-chart-line',
        'gcash' => 'fas fa-mobile-alt',
        'maya' => 'fas fa-mobile-alt', 'paymaya' => 'fas fa-mobile-alt',
        'paypal' => 'fab fa-paypal',
        'coins' => 'fas fa-coins',
        'binance' => 'fab fa-bitcoin',
        
        // Entertainment
        'netflix' => 'fab fa-netflix',
        'spotify' => 'fab fa-spotify',
        'prime video' => 'fab fa-amazon',
        'disney' => 'fas fa-film',
        'hbo' => 'fas fa-tv',
        'epic games' => 'fab fa-epic-games',
        'steam' => 'fab fa-steam',
        'nintendo' => 'fab fa-nintendo-switch',
        'playstation' => 'fab fa-playstation',
        
        // Productivity
        'asana' => 'fas fa-tasks',
        'trello' => 'fab fa-trello',
        'jira' => 'fab fa-jira',
        'salesforce' => 'fab fa-salesforce',
        'hubspot' => 'fab fa-hubspot',
        'dropbox' => 'fab fa-dropbox',
        'shopify' => 'fab fa-shopify',
        'wordpress' => 'fab fa-wordpress',
        'figma' => 'fab fa-figma',
        'notion' => 'fas fa-brain',
        'canva' => 'fab fa-canva',
        
        // Default
        'bank' => 'fas fa-university',
        'credit' => 'fas fa-credit-card',
        'email' => 'fas fa-envelope',
        'mail' => 'fas fa-envelope',
    ];
    
    foreach($icons as $key => $icon) {
        if(strpos($nameLower, $key) !== false) {
            return $icon;
        }
    }
    return 'fas fa-key';
}

$icon = getIconForSite($name);

// Encrypt the password
try {
    $encrypted = Encryption::encrypt($password, $_SESSION['master_key']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Encryption failed: ' . $e->getMessage()]);
    exit();
}

// Insert into database with icon
try {
    $stmt = $pdo->prepare("INSERT INTO passwords (user_id, name, url, username, encrypted_password, notes, category, icon) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([$_SESSION['user_id'], $name, $url, $username, $encrypted, $notes, $category, $icon]);
    
    if ($result) {
        logActivity($pdo, $_SESSION['user_id'], 'add_password', "Added password for: $name");
        echo json_encode(['success' => true, 'message' => 'Password added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: Could not insert password']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>