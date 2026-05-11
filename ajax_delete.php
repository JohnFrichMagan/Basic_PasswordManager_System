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

// Check if ID exists
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing password ID']);
    exit();
}

$id = $_POST['id'];

// Get the name for logging
$stmt = $pdo->prepare("SELECT name FROM passwords WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$password = $stmt->fetch();

if ($password) {
    $stmt = $pdo->prepare("DELETE FROM passwords WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$id, $_SESSION['user_id']]);
    
    if ($result && $stmt->rowCount() > 0) {
        logActivity($pdo, $_SESSION['user_id'], 'delete_password', "Deleted password: " . $password['name']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not delete password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Password not found']);
}
?>