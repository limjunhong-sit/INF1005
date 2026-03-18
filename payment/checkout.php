<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
require_once ROOT . '/config/stripe_config.php';
require_once ROOT . '/includes/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /signin.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT
        ci.item_id,
        ci.product_id,
        ci.quantity,
        p.name,
        p.description,
        p.price,
        p.image_url
    FROM cart c
    JOIN cart_items ci ON c.cart_id = ci.cart_id
    JOIN products p ON ci.product_id = p.product_id
    WHERE c.user_id = ?
    ORDER BY ci.added_at DESC
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    header("Location: /cart/cart.php");
    exit;
}

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT . '/includes/head.php'; ?>
<link rel="stylesheet" href="/css/cart.css">
<link rel="stylesheet" href="/css/payment.css">
<body>
    <div class="cart-page">
        <div class="cart-container checkout-container">

            <!-- Order Summary -->
            <div class="cart-left">
                <h1>Order Summary</h1>

                <?php foreach ($items as $item): ?>
                    <div class="cart-card checkout-card">
                        <div class="cart-image">
                            <img src="/<?= htmlspecialchars($item['image_url']) ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>
                        <div class="cart-details">
                            <h2><?= htmlspecialchars($item['name']) ?></h2>
                            <p class="price">$<?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?></p>
                        </div>
                        <div class="cart-subtotal">
                            $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="summary-card checkout-total">
                    <div class="summary-total-row">
                        <span>Total</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                </div>

                <a href="/cart/cart.php" class="shop-btn back-to-cart">Back to Cart</a>
            </div>

            <!-- Payment Form -->
            <div class="cart-right">
                <div class="summary-card">
                    <h2>Payment Details</h2>

                    <div class="payment-field">
                        <label class="payment-label">Shipping Address</label>
                        <textarea id="shipping-address" class="payment-textarea" placeholder="Enter your shipping address" required></textarea>
                    </div>

                    <div class="payment-field">
                        <label class="payment-label">Cardholder Name</label>
                        <input type="text" id="cardholder-name" class="payment-input" placeholder="Name on card" required>
                    </div>

                    <div class="payment-field">
                        <label class="payment-label">Card Information</label>
                        <div id="card-element" class="payment-card-element"></div>
                    </div>

                    <div id="card-errors" class="payment-error" role="alert"></div>

                    <input type="hidden" id="csrf_token" value="<?= generate_csrf_token() ?>">

                    <button id="pay-btn" class="checkout-btn">
                        Pay $<?= number_format($total, 2) ?>
                    </button>

                    <p class="summary-note">&#128274; Payments are securely processed by Stripe</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            hidePostalCode: true,
            style: {
                base: {
                    fontSize: '14px',
                    color: '#1c1b19',
                    fontFamily: 'Jost, sans-serif',
                    '::placeholder': { color: '#9e9b95' }
                },
                invalid: { color: '#b44' }
            }
        });
        cardElement.mount('#card-element');

        cardElement.on('change', function(event) {
            document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
        });

        document.getElementById('pay-btn').addEventListener('click', async function() {
            const cardholderName = document.getElementById('cardholder-name').value.trim();
            const shippingAddress = document.getElementById('shipping-address').value.trim();

            if (!shippingAddress) {
                document.getElementById('card-errors').textContent = 'Please enter your shipping address.';
                return;
            }
            if (!cardholderName) {
                document.getElementById('card-errors').textContent = 'Please enter the cardholder name.';
                return;
            }

            this.disabled = true;
            this.classList.add('processing');
            this.textContent = 'Processing...';

            try {
                const response = await fetch('process_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        amount: <?= intval($total * 100) ?>,
                        shipping_address: shippingAddress,
                        csrf_token: document.getElementById('csrf_token').value
                    })
                });
                const data = await response.json();

                if (data.error) {
                    document.getElementById('card-errors').textContent = data.error;
                    this.disabled = false;
                    this.classList.remove('processing');
                    this.textContent = 'Pay $<?= number_format($total, 2) ?>';
                    return;
                }

                const result = await stripe.confirmCardPayment(data.clientSecret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: { name: cardholderName }
                    }
                });

                if (result.error) {
                    document.getElementById('card-errors').textContent = result.error.message;
                    this.disabled = false;
                    this.classList.remove('processing');
                    this.textContent = 'Pay $<?= number_format($total, 2) ?>';
                } else {
                    window.location.href = 'payment_success.php?payment_id=' + result.paymentIntent.id;
                }
            } catch (err) {
                document.getElementById('card-errors').textContent = 'An error occurred. Please try again.';
                this.disabled = false;
                this.classList.remove('processing');
                this.textContent = 'Pay $<?= number_format($total, 2) ?>';
            }
        });
    </script>
</body>
</html>