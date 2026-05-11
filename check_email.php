<?php
require_once 'config.php';
header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = $_GET['email'];
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    $available = !$stmt->fetch();
    echo json_encode(['available' => $available]);
} else {
    echo json_encode(['available' => true]);
}
?>