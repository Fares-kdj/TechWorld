<?php
session_start();
require_once '../config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    global $pdo;
    logActivity($pdo, $_SESSION['admin_id'], 'logout', 'تسجيل خروج');
}

session_destroy();
header('Location: login.php');
exit();
?>
