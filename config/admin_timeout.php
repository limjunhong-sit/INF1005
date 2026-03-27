<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signin.php");
    exit();
}

$timeout_duration = 600; // timeout duration in seconds,currently at 10minutes

if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) >= $timeout_duration) {
        session_unset();
        session_destroy();
        
        header("Location: ../signin.php?error=Session expired due to inactivity.");
        exit();
    }
}

$_SESSION['last_activity'] = time();
?>