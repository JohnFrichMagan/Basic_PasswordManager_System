<?php
session_start();
require_once 'config.php';
require_once 'security.php';

if(isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'logout', 'User logged out');
}

session_unset();
session_destroy();
header('Location: index.php?logout=1');
exit();
?>