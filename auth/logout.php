<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/paths.php';
    require_once ROOT . '/config/db_connect.php';
    $stmt = $pdo->prepare("UPDATE users SET session_id = NULL WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

session_unset();
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: ../index.php");
exit();
?>