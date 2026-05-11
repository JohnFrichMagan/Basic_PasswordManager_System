<?php
require_once 'config.php';
require_once 'security.php';
require_once 'encryption.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM passwords WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$passwords = $stmt->fetchAll();

foreach($passwords as &$pwd) {
    $pwd['decrypted_password'] = Encryption::decrypt($pwd['encrypted_password'], $_SESSION['master_key']);
    unset($pwd['encrypted_password']);
}
echo json_encode($passwords);
?>