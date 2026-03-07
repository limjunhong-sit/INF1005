<?php 
/*
* Process sign in form and authenticate user against database.
*/

// Redirect if accessed without POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signin.php');
    exit;
}

$success = true;
$errorMsg = "";

// Get form data
$email = trim($_POST['email'] ?? '');
$pwd = $_POST['pwd'] ?? '';

// Validate required fields
if (empty($email) || empty($pwd)) {
    $errorMsg = "Email and password are required.";
    $success = false;
} else {
    // Verify credentials against database
    $config = @parse_ini_file('/var/www/private/db-config.ini');
    $db = $config['database'] ?? $config;

    if (!$config) {
        $errorMsg = "Failed to read database config file.";
        $success = false;
    } else {
        $conn = new mysqli(
            $db['servername'] ?? 'localhost',
            $db['username'] ?? '',
            $db['password'] ?? '',
            $db['dbname'] ?? ''
        );

        if ($conn->connect_error) {
            $errorMsg = "Connection failed: " . $conn->connect_error;
            $success = false;
        } else {
            $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role FROM users WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($pwd, $user['password'])) {
                        session_start();
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $stmt->close();
                        $conn->close();
                        header('Location: index.php');
                        exit;
                    }
                }
                
                $stmt->close();
            }
            $conn->close();
            $errorMsg = "Invalid email or password.";
            $success = false;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
    <?php include __DIR__ . '/head.php'; ?>
    <body>
        <?php include __DIR__ . '/header.php'; ?>
        <main>
            <section class="container py-5">
                <?php if (!$success): ?>
                    <h4>Sign In Failed</h4>
                    <p><?php echo htmlspecialchars($errorMsg); ?></p>
                    <p><a href="signin.php" class="btn btn-dark">Try again</a></p>
                <?php endif; ?>
            </section>
        </main>
        <?php include __DIR__ . '/footer.php'; ?>
    </body>
</html>
