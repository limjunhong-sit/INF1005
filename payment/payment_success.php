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

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /admin/dashboard.php");
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

            // Update variant stock
            $stmt = $pdo->prepare("
                SELECT oi.variant_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$payment['order_id']]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($orderItems as $item) {
                $stmt = $pdo->prepare("UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE variant_id = ? AND stock_quantity >= ?");
                $stmt->execute([$item['quantity'], $item['variant_id'], $item['quantity']]);
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
<body>
    <?php include ROOT . '/includes/header.php'; ?>
    <style>
        .confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            opacity: 0;
            animation: fall linear forwards;
        }
        @keyframes fall {
            0% { transform: translateY(-10vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }

        .success-wrapper {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .check-icon {
            font-size: 4rem;
            color: var(--accent); 
            margin-bottom: 20px;
            line-height: 1;
        }
        .order-box {
            background: rgba(0,0,0,0.03);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        body.dark-theme .order-box {
            background: rgba(255,255,255,0.05);
        }
    </style>

    <main>
        <div class="section success-wrapper">
            <div class="container d-flex justify-content-center">
                
                <div class="contact-card text-center p-5 shadow-lg" style="max-width: 500px; width: 100%;">
                    
                    <div class="check-icon">&#10003;</div>
                    
                    <h2 class="story-heading mb-3">Payment Successful</h2>
                    <p class="contact-body mb-4">Thank you for your purchase. Your order has been received.</p>
                    
                    <?php if ($orderDetails): ?>
                        <div class="order-box">
                            <p class="contact-body mb-2 text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">
                                Order #<?= htmlspecialchars($orderDetails['order_id']) ?>
                            </p>
                            <p class="stat-number m-0" style="font-size: 2rem;">
                                Amount Paid: $<?= number_format($orderDetails['amount'], 2) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-3 mt-4 pt-4 border-top border-secondary">
                        <a href="/cart/purchase_history.php" class="btn btn-dark py-3 fw-bold" style="letter-spacing: 1.5px; text-transform: uppercase;">
                            View Purchase History
                        </a>
                        <a href="/index.php" class="btn btn-outline-dark py-2">
                            Continue Shopping
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>

    <script>
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
                if (document.body.contains(confettiContainer)) {
                    document.body.removeChild(confettiContainer);
                }
            }, 5000);
        }

        window.onload = () => {
            createConfetti();
        };
    </script>
</body>
</html>

