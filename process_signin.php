<?php 
// 1. Include your working connection and start the session
include 'db_connect.php'; 
session_start();

// Redirect if accessed without POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signin.php');
    exit;
}

$success = true;
$errorMsg = "";


$email = trim($_POST['email'] ?? '');
$pwd = $_POST['pwd'] ?? '';

if (empty($email) || empty($pwd)) {
    $errorMsg = "Email and password are required.";
    $success = false;
} else {
    try {
        // Use your existing $pdo object!
        $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 4. Check if user exists and password matches
        if ($user && password_verify($pwd, $user['password'])) {
            // Success! Store info in Session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: index.php');
            exit;
        } else {
            $errorMsg = "Invalid email or password.";
            $success = false;
        }
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main class="container py-5">
            <?php if (!$success): ?>
                <div class="alert alert-danger">
                    <h4>Sign In Failed</h4>
                    <p><?php echo htmlspecialchars($errorMsg); ?></p>
                    <a href="signin.php" class="btn btn-dark">Try again</a>
                </div>
            <?php endif; ?>
        </main>
        <?php include 'footer.php'; ?>
    </body>
</html>
