<?php
session_set_cookie_params(0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ .'/cart_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /register.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$items = getCartItems($pdo, $userId);
$total = getCartTotal($items);
?>

<style>
    .story-grid {
        display: grid !important;
        grid-template-columns: 2.5fr 1fr !important; 
        gap: 60px !important;
        align-items: start !important; 
        overflow: visible !important;
    }

    body, main, .section, .container {
        overflow: visible !important;
    }

    /* Right Column Container */
    .cart-right {
        display: block !important; 
        height: 100%;
        position: relative;
    }

    /* Summary Box */
    .cart-right .contact-card {
        position: -webkit-sticky !important;
        position: sticky !important;
        top: 120px !important; 
        z-index: 1000;
        max-width: 380px;
        width: 100%;
        margin-left: auto; 
        height: fit-content !important;
    }
    
    /* Product display*/
    .cart-left .contact-card {
        width: 100% !important;
        padding: 40px !important;
        margin-bottom: 30px !important;
    }
</style>

<!DOCTYPE html>
<html lang="en">
    <?php include ROOT . '/includes/head.php'; ?>
<body class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark-theme' : ''; ?>">
    
    <?php include ROOT . '/includes/header.php'; ?>

    <main>
        <div class="section">
            <div class="container">
                
                <div class="story-grid">
                    
                    <div class="cart-left">
                        <h1 class="story-heading mb-5">Your Cart</h1>
                        
                        <?php if (empty($items)): ?>
                            <div class="contact-card text-center py-5 fade-in-el visible">
                                <p class="contact-body mb-4">Your cart is empty.</p>
                                <a href="/index.php" class="btn btn-dark px-4 py-2">Continue Shopping</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <div class="contact-card mb-4 d-flex gap-4 align-items-center fade-in-el visible">
                                    <div class="cart-image">
                                        <img src="/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                                    </div>

                                    <div class="flex-grow-1">
                                        <h2 class="contact-title mb-1" style="font-size: 1.4rem;"><?php echo htmlspecialchars($item['product_name']); ?></h2>
                                        <p class="contact-body mb-2" style="font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($item['description']); ?>
                                        </p>
                                        <?php
                                        $variantParts = array_filter([$item['size'] ?? null, $item['colour'] ?? null]);
                                        if (!empty($variantParts)): ?>
                                            <p class="text-muted small mb-1"><?php echo htmlspecialchars(implode(' / ', $variantParts)); ?></p>
                                        <?php endif; ?>
                                        <p class="stat-number" style="font-size: 1.3rem;">$<?php echo number_format($item['price'], 2); ?></p>
                                        
                                        <div class="d-flex align-items-center gap-3 mt-3">
                                            <form action="update_cart.php" method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo (int)($item['variant_stock'] ?? 999); ?>"
                                                       class="form-control form-control-sm" style="width: 60px; background: var(--warm-white); color: var(--charcoal); border: 1px solid var(--border);">
                                                <button type="submit" class="btn btn-sm btn-outline-dark">Update</button>
                                            </form>

                                            <form action="remove_cart_item.php" method="POST">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0 small text-decoration-none">Remove</button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <p class="stat-label">Subtotal</p>
                                        <p class="fw-bold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="cart-right" style="position: relative; overflow: visible;"> 
                       <div class="sticky-wrapper" style="height: 100%; min-height: 500px; overflow: visible !important;">
                            <div class="contact-card" style="
                                position: -webkit-sticky; 
                                position: sticky; 
                                top: 120px; 
                                z-index: 10; 
                                max-width: 360px;">
                                <h2 class="contact-title mb-4" style="font-size: 1.5rem;">Order Summary</h2>
                                
                                <div class="d-flex justify-content-between mb-4">
                                    <span class="contact-body">Total</span>
                                    <span class="stat-number" style="font-size: 1.8rem;">$<?php echo number_format($total, 2); ?></span>
                                </div>
                                
                                <div class="border-top border-secondary pt-4">
                                    <a href="/payment/checkout.php" class="btn btn-dark w-100 py-3 mb-3 fw-bold">Proceed to Checkout</a>
                                    <a href="/index.php" class="btn btn-outline-dark w-100 py-2">Continue Shopping</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>
</body>
</html>