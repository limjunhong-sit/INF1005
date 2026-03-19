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

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: /admin/dashboard.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get cart items (with variant info for display)
$stmt = $pdo->prepare("
    SELECT
        ci.item_id,
        ci.product_id,
        ci.variant_id,
        ci.quantity,
        p.name,
        p.description,
        p.price,
        p.image_url,
        pv.size,
        pv.colour
    FROM cart c
    JOIN cart_items ci ON c.cart_id = ci.cart_id
    JOIN product_variants pv ON ci.variant_id = pv.variant_id
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
<body>
    <?php include ROOT . '/includes/header.php'; ?>
    <style>
        body, main, .section, .container { overflow: visible !important; }
        
        .story-grid {
            display: grid !important;
            grid-template-columns: 1.5fr 1fr !important; 
            gap: 60px !important;
            align-items: start !important;
            overflow: visible !important;
        }

        .checkout-right {
            display: block !important;
            height: 100%;
            position: relative;
        }

        .checkout-right .contact-card {
            position: -webkit-sticky !important;
            position: sticky !important;
            top: 120px !important;
            z-index: 1000;
            width: 100%;
            height: fit-content !important;
        }

        .checkout-left .mv-card {
            background-color: #ffffff !important;
            border: 1px solid var(--border);
            box-shadow: 0 2px 5px rgba(0,0,0,0.02); 
        }

        
        body.dark-theme .checkout-left .mv-card {
            background-color: rgba(255, 255, 255, 0.03) !important; 
            border-color: rgba(255, 255, 255, 0.1);
        }

        .checkout-right .form-control::placeholder {
            color: #999999 !important; 
            opacity: 1; 
        }

        body.dark-theme .checkout-right .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5) !important; 
            opacity: 1;
        }

        .stripe-wrapper {
            background: #ffffff;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 6px;
        }

        @media (max-width: 992px) {
            .story-grid { grid-template-columns: 1fr !important; }
        }
    </style>


    <main>
        <div class="section">
            <div class="container">
                <div class="story-grid">
                    
                    <div class="checkout-left">
                        <h1 class="story-heading mb-5">Order Summary</h1>

                        <?php foreach ($items as $item): ?>
                            <div class="mv-card mb-4 d-flex gap-4 align-items-center p-3">
                                <div class="cart-image">
                                    <img src="/<?= htmlspecialchars($item['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                </div>
                                
                                <div class="flex-grow-1">
                                    <h2 class="contact-title mb-1" style="font-size: 1.2rem;"><?= htmlspecialchars($item['name']) ?></h2>
                                    <?php
                                    $variantParts = array_filter([$item['size'] ?? null, $item['colour'] ?? null]);
                                    if (!empty($variantParts)): ?>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars(implode(' / ', $variantParts)) ?></p>
                                    <?php endif; ?>
                                    <p class="contact-body mb-0" style="font-size: 0.9rem;">
                                        $<?= number_format($item['price'], 2) ?> &times; <?= $item['quantity'] ?>
                                    </p>
                                </div>
                                
                                <div class="text-end">
                                    <p class="stat-number m-0" style="font-size: 1.2rem;">
                                        $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-4 pt-4 border-top border-secondary d-flex justify-content-between align-items-center">
                            <a href="/cart/cart.php" class="btn btn-outline-dark">&larr; Back to Cart</a>
                            <div class="text-end">
                                <span class="contact-body me-3">Subtotal</span>
                                <span class="stat-number" style="font-size: 1.8rem;">$<?= number_format($total, 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-right">
                        <div class="contact-card shadow-sm">
                            <h2 class="contact-title mb-4 border-bottom border-secondary pb-3">Payment Details</h2>

                            <div class="mb-4">
                                <label class="contact-body mb-2 fw-bold" style="font-size: 0.9rem;">Shipping Address</label>
                                <textarea id="shipping-address" class="form-control" rows="3" placeholder="Enter your full shipping address" required style="background: var(--warm-white); color: var(--charcoal); border-color: var(--border);"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="contact-body mb-2 fw-bold" style="font-size: 0.9rem;">Name on Card</label>
                                <input type="text" id="cardholder-name" class="form-control" placeholder="John Doe" required style="background: var(--warm-white); color: var(--charcoal); border-color: var(--border);">
                            </div>

                            <div class="mb-4">
                                <label class="contact-body mb-2 fw-bold" style="font-size: 0.9rem;">Card Information</label>
                                <div class="stripe-wrapper">
                                    <div id="card-element"></div>
                                </div>
                            </div>

                            <div id="card-errors" class="text-danger mb-3 small fw-bold" role="alert"></div>

                            <input type="hidden" id="csrf_token" value="<?= generate_csrf_token() ?>">

                            <button id="pay-btn" class="btn btn-dark w-100 py-3 mb-3 fw-bold" style="letter-spacing: 2px; text-transform: uppercase;">
                                Pay $<?= number_format($total, 2) ?>
                            </button>

                            <p class="text-center text-muted mb-0" style="font-size: 0.75rem; letter-spacing: 1px;">
                                &#128274; SECURELY PROCESSED BY STRIPE
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
        const elements = stripe.elements();
        
        const cardElement = elements.create('card', {
            hidePostalCode: true,
            style: {
                base: {
                    fontSize: '16px',
                    color: '#333333',
                    fontFamily: 'Jost, sans-serif',
                    '::placeholder': { color: '#aab7c4' }
                },
                invalid: { color: '#dc3545' }
            }
        });
        cardElement.mount('#card-element');

        cardElement.on('change', function(event) {
            document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
        });

        document.getElementById('pay-btn').addEventListener('click', async function() {
            const cardholderName = document.getElementById('cardholder-name').value.trim();
            const shippingAddress = document.getElementById('shipping-address').value.trim();
            const errorDiv = document.getElementById('card-errors');
            const btn = this;

            if (!shippingAddress) {
                errorDiv.textContent = 'Please enter your shipping address.';
                return;
            }
            if (!cardholderName) {
                errorDiv.textContent = 'Please enter the name on the card.';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';

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
                    errorDiv.textContent = data.error;
                    btn.disabled = false;
                    btn.textContent = 'Pay $<?= number_format($total, 2) ?>';
                    return;
                }

                const result = await stripe.confirmCardPayment(data.clientSecret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: { name: cardholderName }
                    }
                });

                if (result.error) {
                    errorDiv.textContent = result.error.message;
                    btn.disabled = false;
                    btn.textContent = 'Pay $<?= number_format($total, 2) ?>';
                } else {
                    window.location.href = 'payment_success.php?payment_id=' + result.paymentIntent.id;
                }
            } catch (err) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Pay $<?= number_format($total, 2) ?>';
            }
        });
    </script>
</body>
</html>