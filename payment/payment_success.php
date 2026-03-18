<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /signin.php");
    exit;
}

$paymentId = htmlspecialchars($_GET['payment_id'] ?? '', ENT_QUOTES, 'UTF-8');
$userId = (int)$_SESSION['user_id'];
$orderDetails = null;

if (!empty($paymentId)) {
    try {
        $pdo->beginTransaction();

        // Update payment status to paid
        $stmt = $pdo->prepare("UPDATE payments SET status = 'paid' WHERE stripe_payment_id = ? AND user_id = ?");
        $stmt->execute([$paymentId, $userId]);

        // Get the order_id from payment
        $stmt = $pdo->prepare("SELECT order_id, amount FROM payments WHERE stripe_payment_id = ? AND user_id = ?");
        $stmt->execute([$paymentId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            // Update order status to paid
            $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$payment['order_id'], $userId]);

            $orderDetails = [
                'order_id' => $payment['order_id'],
                'amount' => $payment['amount']
            ];

            // Update product stock
            $stmt = $pdo->prepare("
                SELECT oi.product_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$payment['order_id']]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($orderItems as $item) {
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
                $stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }
        }

        // Clear cart
        $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart['cart_id']]);
        }

        $pdo->commit();
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Payment success error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT . '/includes/head.php'; ?>
<link rel="stylesheet" href="/css/cart.css">
<link rel="stylesheet" href="/css/payment.css">
<body>
    <div class="cart-page">
        <div class="success-container">
            <div class="summary-card success-card">
                <div class="success-icon">
                    <span>&#10003;</span>
                </div>
                <h2 class="success-title">Payment Successful!</h2>
                <p class="success-text">Thank you for your purchase.</p>
                <?php if ($orderDetails): ?>
                    <p class="success-detail">Order #<?= $orderDetails['order_id'] ?></p>
                    <p class="success-detail" style="margin-bottom: 28px;">
                        Amount paid: $<?= number_format($orderDetails['amount'], 2) ?>
                    </p>
                <?php endif; ?>
                <a href="/index.php" class="checkout-btn success-btn">Continue Shopping</a>
            </div>
        </div>
    </div>
    <script>
        // Confetti animation
        function createConfetti() {
            const confettiContainer = document.createElement('div');
            confettiContainer.classList.add('confetti-container');
            document.body.appendChild(confettiContainer);

            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.classList.add('confetti');
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 70%, 60%)`;
                confettiContainer.appendChild(confetti);
            }

            setTimeout(() => {
                document.body.removeChild(confettiContainer);
            }, 5000);
        }

        window.onload = () => {
            createConfetti();
        };
    </script>
</body>
</html>