<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signin.php");
    exit();
}

require_once ROOT . '/includes/csrf.php';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid request. Please refresh the page and try again.';
    } else {
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pwd = $_POST['pwd'] ?? '';
        $pwd_confirm = $_POST['pwd_confirm'] ?? '';

        $password_regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
        $name_regex = "/^[a-zA-Z\s'\-]{2,50}$/";

        if (empty($fname) || empty($lname) || empty($email) || empty($pwd) || empty($pwd_confirm)) {
            $errorMsg = "All fields are required.";
        } elseif (!preg_match($name_regex, $fname) || !preg_match($name_regex, $lname)) {
            $errorMsg = "Names may only contain letters, spaces, hyphens, or apostrophes.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = "Please enter a valid email address.";
        } elseif (!preg_match($password_regex, $pwd)) {
            $errorMsg = "Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.";
        } elseif ($pwd !== $pwd_confirm) {
            $errorMsg = "Passwords do not match.";
        } else {
            try {
                // Check if a user with this email already exists
                $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $errorMsg = "This email is already registered.";
                } else {
                    // Create a brand new admin user
                    $pwd_hashed = password_hash($pwd, PASSWORD_DEFAULT);
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO users (first_name, last_name, email, password, created_at, role)
                         VALUES (?, ?, ?, ?, NOW(), 'admin')"
                    );

                    if ($insertStmt->execute([$fname, $lname, $email, $pwd_hashed])) {
                        $successMsg = "New admin account created successfully.";
                    } else {
                        $errorMsg = "Failed to create admin account. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errorMsg = "This email is already registered.";
                } else {
                    $errorMsg = "An unexpected error occurred. Please try again later.";
                    error_log("Admin creation error: " . $e->getMessage());
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniClothes — Manage Admins</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        /* Same Inventory Rules style as Settings page */
        body.dark-theme .settings-card {
            background-color: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #ffffff;
        }
        body.dark-theme .settings-card .form-control {
            background-color: rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
        body.dark-theme .settings-card .input-group-text {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: rgba(255, 255, 255, 0.8) !important;
        }
        body.dark-theme .settings-card .text-muted,
        body.dark-theme .settings-card .form-text,
        body.dark-theme .settings-card .admin-form-desc {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        body.dark-theme .settings-card .form-label {
            color: #ffffff !important;
        }
        body.dark-theme .settings-card .btn-dark,
        body.dark-theme .settings-card .btn-admin-save {
            background-color: #ffffff;
            color: #000000;
            border-color: #ffffff;
        }
        body.dark-theme .settings-card .btn-dark:hover,
        body.dark-theme .settings-card .btn-admin-save:hover {
            background-color: #dddddd;
        }
        body.dark-theme .settings-card hr.text-muted {
            border-color: rgba(255, 255, 255, 0.2) !important;
        }
    </style>
</head>
<body>

<?php include ROOT . '/includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h2>Manage Admins</h2>
    </div>

    <div class="page-body">
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm admin-alert" role="alert">
                <strong>Oops!</strong> <?php echo htmlspecialchars($errorMsg); ?>
                <button type="button" class="btn-close btn-close-theme" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm admin-alert" role="alert">
                <strong>Success!</strong> <?php echo htmlspecialchars($successMsg); ?>
                <button type="button" class="btn-close btn-close-theme" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mt-3 settings-card admin-form-card">
            <div class="card-body p-4">
                <h4 class="card-title fw-bold mb-4 settings-card-title">Create Admin</h4>
                <p class="admin-form-desc small mb-4">
                    Use this form to create a new admin account. The email must not already be registered.
                </p>

                <form action="manage_admins.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fname" class="form-label fw-bold">First Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="fname"
                                name="fname"
                                required
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lname" class="form-label fw-bold">Last Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="lname"
                                name="lname"
                                required
                            >
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            required
                        >
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pwd" class="form-label fw-bold">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="pwd"
                                name="pwd"
                                minlength="8"
                                required
                            >
                            <div class="form-text">
                                Must include uppercase, lowercase, number, and special character.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pwd_confirm" class="form-label fw-bold">Confirm Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="pwd_confirm"
                                name="pwd_confirm"
                                minlength="8"
                                required
                            >
                        </div>
                    </div>

                    <hr class="my-4 text-muted">

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-admin-save px-4">
                            Save Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

