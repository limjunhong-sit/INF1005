<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
require_once ROOT . '/config/stripe_config.php';
require_once ROOT . '/includes/csrf.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Please sign in to continue.']);
    exit;
}

// Admins cannot purchase
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo json_encode(['error' => 'Admins cannot place orders.']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verify_csrf_token($input['csrf_token'] ?? '')) {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$amount = intval($input['amount'] ?? 0);
$shippingAddress = htmlspecialchars(trim($input['shipping_address'] ?? ''), ENT_QUOTES, 'UTF-8');

// Validate
if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid payment amount.']);
    exit;
}
if (empty($shippingAddress)) {
    echo json_encode(['error' => 'Shipping address is required.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Get cart items to create order (include variant_id)
    $stmt = $pdo->prepare("
        SELECT ci.product_id, ci.variant_id, ci.quantity, p.price
        FROM cart c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        JOIN product_variants pv ON ci.variant_id = pv.variant_id
        JOIN products p ON ci.product_id = p.product_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        echo json_encode(['error' => 'Your cart is empty.']);
        exit;
    }

    // Calculate total from database
    $dbTotal = 0;
    foreach ($cartItems as $item) {
        $dbTotal += $item['price'] * $item['quantity'];
    }
    $dbAmountCents = intval($dbTotal * 100);

    // Build a stable hash of the current cart (prevents duplicate orders on retry/double-click)
    $cartHashParts = [];
    foreach ($cartItems as $item) {
        $cartHashParts[] = [
            'product_id' => (int)$item['product_id'],
            'variant_id' => (int)$item['variant_id'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['price'],
        ];
    }
    usort($cartHashParts, function ($a, $b) {
        return [$a['product_id'], $a['variant_id']] <=> [$b['product_id'], $b['variant_id']];
    });
    $cartHash = hash('sha256', json_encode($cartHashParts));

    // If we already created an order for this cart in this session, reuse it
    $existingOrderId = (int)($_SESSION['pending_order_id'] ?? 0);
    $existingCartHash = (string)($_SESSION['pending_cart_hash'] ?? '');
    if ($existingOrderId > 0 && hash_equals($existingCartHash, $cartHash)) {
        $stmt = $pdo->prepare("
            SELECT p.stripe_payment_id
            FROM orders o
            JOIN payments p ON o.order_id = p.order_id
            WHERE o.order_id = ? AND o.user_id = ? AND o.status = 'pending' AND p.status = 'pending'
            ORDER BY p.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$existingOrderId, $userId]);
        $existingPayment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingPayment && !empty($existingPayment['stripe_payment_id'])) {
            // Keep shipping address up to date for the already-created order
            $stmt = $pdo->prepare("
                UPDATE orders
                SET shipping_address = ?, total_amount = ?
                WHERE order_id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$shippingAddress, $dbTotal, $existingOrderId, $userId]);

            $intent = \Stripe\PaymentIntent::retrieve($existingPayment['stripe_payment_id']);
            if (!empty($intent->client_secret)) {
                echo json_encode([
                    'clientSecret' => $intent->client_secret,
                    'orderId' => $existingOrderId
                ]);
                exit;
            }
        }

        // If something is inconsistent (missing pending payment), drop session linkage and proceed to create a new one
        unset($_SESSION['pending_order_id'], $_SESSION['pending_cart_hash']);
    }

    // Create Stripe PaymentIntent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $dbAmountCents,
        'currency' => 'sgd',
        'payment_method_types' => ['card'],
        'metadata' => [
            'user_id' => $userId
        ]
    ]);

    // Begin database transaction
    $pdo->beginTransaction();

    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, created_at) VALUES (?, ?, 'pending', ?, NOW())");
    $stmt->execute([$userId, $dbTotal, $shippingAddress]);
    $orderId = (int)$pdo->lastInsertId();

    // Create order items (include variant_id)
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    foreach ($cartItems as $item) {
        $stmt->execute([$orderId, $item['product_id'], $item['variant_id'], $item['quantity'], $item['price']]);
    }

    // Create payment record
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, user_id, stripe_payment_id, amount, currency, status, card_last4, created_at) VALUES (?, ?, ?, ?, 'SGD', 'pending', '0000', NOW())");
    $stmt->execute([$orderId, $userId, $paymentIntent->id, $dbTotal]);

    $pdo->commit();

    $_SESSION['pending_order_id'] = $orderId;
    $_SESSION['pending_cart_hash'] = $cartHash;

    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret,
        'orderId' => $orderId
    ]);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Payment error: " . $e->getMessage());
    echo json_encode(['error' => 'Payment processing failed. Please try again.']);
}