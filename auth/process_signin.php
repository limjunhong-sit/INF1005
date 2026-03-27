<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
session_start();

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../signin.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Invalid request.");
}

$success = true;
$errorMsg = "";

$email = trim($_POST['email'] ?? '');
$pwd = $_POST['pwd'] ?? '';

if (empty($email) || empty($pwd)) {
    $errorMsg = "Email and password are required.";
    $success = false;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMsg = "Please enter a valid email address.";
    $success = false;
} else {
    try {
        $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, password, role, session_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pwd, $user['password'])) {
            session_regenerate_id(true); 
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            $stmt = $pdo->prepare("UPDATE users SET session_id = ? WHERE user_id = ?");
            $stmt->execute([session_id(), $user['user_id']]);

            if ($_SESSION['role'] === 'admin') {
                header('Location: ../admin/analytics.php');
            } elseif (!empty($_POST['redirect']) && strpos($_POST['redirect'], '/') === 0) {
                header('Location: ../' . ltrim($_POST['redirect'], '/'));
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $errorMsg = "Invalid email or password.";
            $success = false;
        }
    } catch (PDOException $e) {
        $errorMsg = "An unexpected error occurred. Please try again later.";
        error_log("Database error: " . $e->getMessage());
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT . '/includes/head.php'; ?>
    <body>
        <?php include ROOT . '/includes/header.php'; ?>
        <main class="container py-5">
            <?php if (!$success): ?>
                <div class="alert alert-danger" role="alert">
                    <h4>Sign In Failed</h4>
                    <p><?php echo htmlspecialchars($errorMsg); ?></p>
                    <a href="/signin.php" class="btn btn-dark">Try again</a>
                </div>
            <?php endif; ?>
        </main>
        <?php include ROOT . '/includes/footer.php'; ?>
    </body>
</html>