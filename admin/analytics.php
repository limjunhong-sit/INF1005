<?php
require_once __DIR__ . '/../config/paths.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniClothes — Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<?php include ROOT . '/includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h2>Analytics</h2>
    </div>
    <div class="page-body">
        <div class="alert alert-info shadow-sm">
            Analytics dashboard coming soon.
        </div>
    </div>
</div>
</body>
</html>

