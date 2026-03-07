<?php 
/*
* Process member registration form and save to database.
*/

// Redirect if accessed without POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$success = true;
$errorMsg = "";

// Get form data
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$pwd = $_POST['pwd'] ?? '';
$pwd_confirm = $_POST['pwd_confirm'] ?? '';

// Validate required fields
if (empty($fname) || empty($lname) || empty($email) || empty($pwd) || empty($pwd_confirm)) {
    $errorMsg = "All fields are required.";
    $success = false;
}
// Validate password match
elseif ($pwd !== $pwd_confirm) {
    $errorMsg = "Passwords do not match.";
    $success = false;
}
else {
    $pwd_hashed = password_hash($pwd, PASSWORD_DEFAULT);
}

/*
* Helper function to write the member data to the database.
*/
function saveMemberToDB()
{
    global $fname, $lname, $email, $pwd_hashed, $errorMsg, $success;

    // Create database connection
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
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, created_at, role) VALUES (?, ?, ?, ?, NOW(), 'customer')");

            if ($stmt) {
                $stmt->bind_param("ssss", $fname, $lname, $email, $pwd_hashed);
                if (!$stmt->execute()) {
                    $errorMsg = "Registration failed: " . $stmt->error;
                    $success = false;
                }
                $stmt->close();
            } else {
                $errorMsg = "Database error: " . $conn->error;
                $success = false;
            }
            $conn->close();
        }
    }
}

if ($success) {
    saveMemberToDB();
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