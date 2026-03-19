<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

try {
    $stmt = $pdo->query("
        SELECT 
            o.order_id, o.total_amount, o.status, o.created_at,
            u.first_name, u.last_name, u.email,
            oi.quantity, p.price as item_price,
            p.name as product_name, p.image_url
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        ORDER BY o.created_at DESC
    ");
    
    $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($rawResults as $row) {
        $oid = $row['order_id'];

        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_id' => $row['order_id'],
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'customer_name' => $row['first_name'] . ' ' . $row['last_name'],
                'customer_email' => $row['email'],
                'items' => []
            ];
        }
        
        $orders[$oid]['items'][] = [
            'name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['item_price'],
            'image_url' => $row['image_url']
        ];
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniClothes — Orders Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body> 
    <?php require_once ROOT . '/includes/admin_sidebar.php'; ?>
    <style>
        .admin-main-content {
            margin-left: 260px; 
            padding: 40px;
            background-color: var(--background);
            min-height: 100vh;
        }

        .accordion-button:not(.collapsed) {
            background-color: var(--warm-white);
            color: var(--charcoal);
            box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
        }
        .order-badge {
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 5px 10px;
        }
        .badge-paid { background-color: #28a745; color: white; }
        .badge-pending { background-color: #ffc107; color: black; }
        .badge-shipped { background-color: var(--accent); color: white; }
        
        body.dark-theme .accordion-item {
            background-color: rgba(255,255,255,0.03);
            border-color: rgba(255,255,255,0.1);
            color: var(--cream);
        }
        body.dark-theme .accordion-button {
            background-color: transparent;
            color: var(--cream);
        }
        body.dark-theme .accordion-button:not(.collapsed) {
            background-color: rgba(255,255,255,0.05);
        }

        @media (max-width: 992px) {
            .admin-main-content { margin-left: 0; padding: 20px; }
        }
        
        body.dark-theme .admin-main-content .text-muted {
            color: rgba(255, 255, 255, 0.6) !important;
        }
    </style>

    <main class="admin-main-content">
        <div class="container-fluid max-w-1200 mx-auto">
            
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h1 class="story-heading m-0" style="font-size: 2.5rem;">Order <span>Management</span></h1>
            </div>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5 contact-card shadow-sm">
                    <p class="text-muted mb-0">No orders have been placed yet.</p>
                </div>
            <?php else: ?>
                <div class="accordion shadow-sm" id="ordersAccordion">
                    
                    <?php foreach ($orders as $order): ?>
                        <div class="accordion-item border-secondary">
                            <h2 class="accordion-header" id="heading<?= $order['order_id'] ?>">
                                <button class="accordion-button collapsed d-flex flex-wrap gap-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $order['order_id'] ?>" aria-expanded="false" aria-controls="collapse<?= $order['order_id'] ?>">
                                    
                                    <span class="fw-bold" style="min-width: 100px;">
                                        Order #<?= htmlspecialchars($order['order_id']) ?>
                                    </span>
                                    
                                    <span style="min-width: 180px;">
                                        &#128100; <?= htmlspecialchars(ucwords($order['customer_name'])) ?>
                                    </span>
                                    
                                    <span class="text-muted" style="min-width: 150px;">
                                        &#128197; <?= date('M j, Y - g:i A', strtotime($order['created_at'])) ?>
                                    </span>
                                    
                                    <span class="fw-bold ms-auto me-3" style="color: var(--accent);">
                                        $<?= number_format($order['total_amount'], 2) ?>
                                    </span>

                                    <?php 
                                        $badgeClass = 'badge-pending';
                                        if (strtolower($order['status']) === 'paid') $badgeClass = 'badge-paid';
                                        if (strtolower($order['status']) === 'shipped') $badgeClass = 'badge-shipped';
                                    ?>
                                    <span class="badge rounded-pill order-badge <?= $badgeClass ?> text-uppercase">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                    
                                </button>
                            </h2>
                            
                            <div id="collapse<?= $order['order_id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $order['order_id'] ?>" data-bs-parent="#ordersAccordion">
                                <div class="accordion-body border-top border-secondary">
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <p class="mb-1 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Customer Email</p>
                                            <p class="fw-bold m-0"><?= htmlspecialchars($order['customer_email']) ?></p>
                                        </div>
                                    </div>

                                    <h6 class="text-uppercase text-muted mb-3" style="letter-spacing: 1px; font-size: 0.85rem;">Items Ordered</h6>
                                    
                                    <ul class="list-group list-group-flush border-top border-secondary">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <li class="list-group-item d-flex align-items-center bg-transparent px-0 py-3 border-secondary">
                                                <img src="/<?= htmlspecialchars($item['image_url']) ?>" alt="Product" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; margin-right: 15px;">
                                                <div class="flex-grow-1">
                                                    <span class="fw-bold d-block" style="font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; letter-spacing: 1px;">
                                                        <?= htmlspecialchars(strtoupper($item['name'])) ?>
                                                    </span>
                                                    <span class="text-muted small">Qty: <?= $item['quantity'] ?></span>
                                                </div>
                                                <span class="fw-bold stat-number" style="font-size: 1.2rem;">
                                                    $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?> 
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

