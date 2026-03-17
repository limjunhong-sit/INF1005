<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../signin.php");
    exit();
}

$sql = "SELECT 
            p.product_id,
            p.name, 
            p.image_url,
            COALESCE(SUM(oi.quantity), 0) as quantity_sold,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_earned
        FROM products p
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.order_id AND o.status != 'cancelled'
        GROUP BY p.product_id, p.name, p.image_url 
        ORDER BY total_earned DESC, quantity_sold DESC";

$stmt = $pdo->query($sql);

if ($stmt === false) {
    $errorInfo = $pdo->errorInfo();
    $error = "Database error: " . $errorInfo[2];
    $analytics = [];
} else {
    $analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$grandTotalEarned = 0;
$grandTotalSold = 0;
foreach ($analytics as $item) {
    $grandTotalEarned += $item['total_earned'];
    $grandTotalSold += $item['quantity_sold'];
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

<<div class="main-content">
    <div class="topbar">
        <h2>Sales Analytics</h2>
    </div>

    <div class="page-body">
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats-row" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card">
                <div class="stat-label">Total Store Revenue</div>
                <div class="stat-value accent">$<?php echo number_format($grandTotalEarned, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Items Sold</div>
                <div class="stat-value"><?php echo number_format($grandTotalSold); ?></div>
            </div>
        </div>

        <div class="table-wrap mt-4">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align: center;">Quantity Sold</th>
                        <th style="text-align: right;">Total Earned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($analytics) > 0): ?>
                        <?php foreach ($analytics as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <?php 
                                            
                                            $img = $item['image_url'];
                                            if (strpos($img, 'http') !== 0 && strpos($img, '../') !== 0) {
                                                $img = '../' . $img; 
                                            }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($img); ?>" width="40" height="40" class="rounded me-3" style="object-fit: cover;">
                                        <span class="product-name fw-bold"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                
                                <td style="text-align: center;">
                                    <?php if ($item['quantity_sold'] > 0): ?>
                                        <span class="badge bg-dark px-3 py-2 rounded-pill fs-6"><?php echo $item['quantity_sold']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td style="text-align: right; font-size: 1.1rem;" class="price">
                                    $<?php echo number_format($item['total_earned'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">No analytics data available yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
