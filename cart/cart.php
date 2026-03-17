<?php
session_start();
require_once __DIR__ .'/cart_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /register.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$items = getCartItems($pdo, $userId);
$total = getCartTotal($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart</title>
    <link rel="stylesheet" href="/css/cart.css">
</head>
<body>
    <div class="cart-page">
        <div class="cart-container">
            <div class="cart-left">
                <h1>Your Cart</h1>

                <?php if (empty($items)): ?>
                    <div class="empty-cart">
                        <p>Your cart is empty.</p>
                        <a href="/index.php" class="shop-btn">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="cart-card">
                            <div class="cart-image">
                                <img src="/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>

                            <div class="cart-details">
                                <h2><?php echo htmlspecialchars($item['product_name']); ?></h2>
                                <p class="description">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </p>
                                <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                                
                                <form action="update_cart.php" method="POST" class="quantity-form">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1">
                                    <button type="submit">Update</button>
                                </form>

                                <form action="remove_cart_item.php" method="POST">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <button type="submit" class="remove-btn">Remove</button>
                                </form>
                            </div>

                            <div class="cart-subtotal">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cart-right">
                <div class="summary-card">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
                <a href="/index.php" class="shop-btn" style="display:block; text-align:center; margin-top:14px;">
                    Continue Shopping
                </a> 
            </div>
        </div>
    </div>
</body>
</html>