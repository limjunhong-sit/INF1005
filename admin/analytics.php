<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

$currentSort = $_GET['sort'] ?? 'earned_desc';
$orderBy = 'total_earned DESC, quantity_sold DESC'; 

switch ($currentSort) {
    case 'earned_asc':
        $orderBy = 'total_earned ASC, quantity_sold ASC';
        break;
    case 'qty_desc':
        $orderBy = 'quantity_sold DESC, total_earned DESC';
        break;
    case 'qty_asc':
        $orderBy = 'quantity_sold ASC, total_earned ASC';
        break;
    case 'earned_desc':
    default:
        $orderBy = 'total_earned DESC, quantity_sold DESC';
        break;
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
        ORDER BY $orderBy";

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
    <style>
        body.dark-theme .bg-dark {
            background-color: #f4f4f5 !important;
            color: #18181b !important;
        }
        body.dark-theme .text-muted {
            color: #a1a1aa !important;
        }
    </style>
</head>
<body>
<?php include ROOT . '/includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h2 class="mt-0">Sales Analytics</h2>
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

        <div class="toolbar d-flex justify-content-between align-items-center mt-4 mb-2">
            <h3 class="m-0" style="font-family: 'Bebas Neue', serif; font-size: 1.3rem; letter-spacing: 1px;">Product Performance</h3>
            
            <form action="analytics.php" method="GET" class="d-flex align-items-center gap-2">
                <label for="sort" class="text-muted small fw-bold mb-0" style="letter-spacing: 1px;">SORT BY:</label>
                <select name="sort" id="sort" class="filter-select py-1" onchange="this.form.submit()">
                    <option value="earned_desc" <?php echo $currentSort === 'earned_desc' ? 'selected' : ''; ?>>Total Earned (Desc)</option>
                    <option value="earned_asc" <?php echo $currentSort === 'earned_asc' ? 'selected' : ''; ?>>Total Earned (Asc)</option>
                    <option value="qty_desc" <?php echo $currentSort === 'qty_desc' ? 'selected' : ''; ?>>Qty Sold (Desc)</option>
                    <option value="qty_asc" <?php echo $currentSort === 'qty_asc' ? 'selected' : ''; ?>>Qty Sold (Asc)</option>
                </select>
            </form>
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
                                        <span class="badge-bg dark px-3 py-2 rounded-pill fs-6"><?php echo $item['quantity_sold']; ?></span>
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
