<?php 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$success = true;
$errorMsg = "";

$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$pwd = $_POST['pwd'] ?? '';
$pwd_confirm = $_POST['pwd_confirm'] ?? '';

$password_regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
$name_regex = "/^[a-zA-Z\s'\-]{2,50}$/";

if (empty($fname) || empty($lname) || empty($email) || empty($pwd) || empty($pwd_confirm)) {
    $errorMsg = "All fields are required.";
    $success = false;
} elseif (!preg_match($name_regex, $fname) || !preg_match($name_regex, $lname)) {
    $errorMsg = "Names may only contain letters, spaces, hyphens, or apostrophes.";
    $success = false;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMsg = "Please enter a valid email address.";
    $success = false;
} elseif (!preg_match($password_regex, $pwd)) {
    $errorMsg = "Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.";
    $success = false;
} elseif ($pwd !== $pwd_confirm) {
    $errorMsg = "Passwords do not match.";
    $success = false;
} else {
    $pwd_hashed = password_hash($pwd, PASSWORD_DEFAULT);

    include 'db_connect.php'; 

    try {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, created_at, role) VALUES (?, ?, ?, ?, NOW(), 'customer')");
        
        if ($stmt->execute([$fname, $lname, $email, $pwd_hashed])) {
            $success = true;
        } else {
            $errorMsg = "Registration failed.";
            $success = false;
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $errorMsg = "This email is already registered.";
        } else {
            $errorMsg = "Database error: " . $e->getMessage();
        }
        $success = false;
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
                <?php if ($success): ?>
                    <h4>Registration successful!</h4>
                    <p>Welcome, <?php echo htmlspecialchars($fname); ?>!</p>
                <?php else: ?>
                    <h4>Error:</h4>
                    <p><?php echo htmlspecialchars($errorMsg); ?></p>
                    <p><a href="register.php" class="btn btn-dark">Try again</a></p>
                <?php endif; ?>
            </section>
        </main>
        <?php include __DIR__ . '/footer.php'; ?>
    </body>
</html>