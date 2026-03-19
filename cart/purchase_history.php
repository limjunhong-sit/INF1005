<?php
session_set_cookie_params(0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /signin.php");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /admin/dashboard.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        o.order_id,
        o.total_amount,
        o.status,
        o.shipping_address,
        o.created_at,
        p.stripe_payment_id
    FROM orders o
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as &$order) {
    $stmt = $pdo->prepare("
        SELECT
            oi.quantity,
            oi.unit_price,
            pr.name,
            pr.image_url
        FROM order_items oi
        JOIN products pr ON oi.product_id = pr.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['order_id']]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT . '/includes/head.php'; ?>
<body class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark-theme' : ''; ?>">

<?php include ROOT . '/includes/header.php'; ?>

<link rel="stylesheet" href="/css/cart.css">
<link rel="stylesheet" href="/css/payment.css">
<link rel="stylesheet" href="/css/purchase_history.css">

<main>
    <div class="history-wrapper">
        <h1 class="history-heading">Purchase History</h1>
        <p class="history-subtext">All your past orders in one place.</p>
        <a href="javascript:history.back()" class="btn btn-outline-dark px-4 py-2 mb-4">← Back</a>

        <?php if (empty($orders)): ?>
            <div class="empty-state fade-in-el visible">
                <div class="empty-icon">🛍️</div>
                <h2 class="empty-title">No orders yet</h2>
                <p class="empty-sub">Looks like you haven't made any purchases yet.</p>
                <a href="/index.php" class="btn btn-dark px-4 py-2">Start Shopping</a>
            </div>

        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card fade-in-el visible">

                    <div class="order-header">
                        <div class="order-meta">
                            <div class="order-meta-item">
                                <span class="order-meta-label">Order</span>
                                <span class="order-meta-value">#<?= $order['order_id'] ?></span>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Date</span>
                                <span class="order-meta-value"><?= date('d M Y', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Items</span>
                                <span class="order-meta-value"><?= count($order['items']) ?></span>
                            </div>
                        </div>

                        <span class="order-status <?= $order['status'] === 'paid' ? 'status-paid' : 'status-pending' ?>">
                            <span class="status-dot"></span>
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>

                    <div class="order-items-list">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item-row">
                                <img
                                    src="/<?= htmlspecialchars($item['image_url']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    class="order-item-img">
                                <div class="order-item-info">
                                    <p class="order-item-name"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="order-item-qty">Qty: <?= $item['quantity'] ?></p>
                                </div>
                                <span class="order-item-price">
                                    $<?= number_format($item['unit_price'] * $item['quantity'], 2) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <p class="order-shipping">
                            📦 <span>Ships to:</span> <?= htmlspecialchars($order['shipping_address']) ?>
                        </p>
                        <p class="order-total">Total: $<?= number_format($order['total_amount'], 2) ?></p>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include ROOT . '/includes/footer.php'; ?>
</body>
</html>