<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newThreshold = filter_var($_POST['low_stock'] ?? 0, FILTER_VALIDATE_INT);

    if ($newThreshold === false || $newThreshold < 1) {
        $_SESSION['error'] = "The threshold must be a valid number of 1 or higher.";
    } else {
       $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES ('low_stock_threshold', ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newThreshold, $newThreshold]);
        $_SESSION['success'] = "Inventory threshold permanently updated to " . $newThreshold . "!";
    }
    header("Location: settings.php");
    exit();
}

$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'low_stock_threshold'");
$stmt->execute();
$dbThreshold = $stmt->fetchColumn();

$currentThreshold = ($dbThreshold !== false) ? (int)$dbThreshold : 5;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniClothes — Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<?php include ROOT . '/includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h2>Settings</h2>
    </div>

    <div class="page-body">

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <strong>Oops!</strong> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <strong>Success!</strong> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mt-3 settings-card">
            <div class="card-body p-4">
                <h4 class="card-title fw-bold mb-4 settings-card-title">Inventory Rules</h4>

                <form action="settings.php" method="POST">
                    <div class="mb-4">
                        <label for="low_stock" class="form-label fw-bold">Low Stock Threshold</label>
                        <p class="text-muted small mb-3">
                            Products with inventory below this number will be flagged as 
                            <span class="badge bg-danger">Low Stock</span>.
                        </p>
                        
                        <div class="input-group" style="max-width: 220px;">
                            <input
                                type="number"
                                class="form-control"
                                id="low_stock"
                                name="low_stock"
                                value="<?php echo htmlspecialchars($currentThreshold); ?>"
                                min="1"
                                required
                            >
                            <span class="input-group-text">items</span>
                        </div>
                    </div>
                    
                    <hr class="my-4 text-muted">
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-dark px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div> 
    </div>
</div>
</body>
</html>