<?php
require_once 'config.php';
header('Content-Type: application/json');

if (isset($_GET['username'])) {
    $username = $_GET['username'];
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    $available = !$stmt->fetch();
    echo json_encode(['available' => $available]);
} else {
    echo json_encode(['available' => true]);
}
?>