<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php'; 

function getActiveCartId(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        return (int)$cart['cart_id'];
    }

    $stmt = $pdo->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
    $stmt->execute([$userId]);

    return (int)$pdo->lastInsertId();
}

function addToCart(PDO $pdo, int $userId, int $variantId, int $quantity = 1): void {
    $cartId = getActiveCartId($pdo, $userId);

    // Get product_id from variant
    $stmt = $pdo->prepare("SELECT product_id FROM product_variants WHERE variant_id = ?");
    $stmt->execute([$variantId]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$variant) {
        return;
    }
    $productId = (int)$variant['product_id'];

    $stmt = $pdo->prepare("SELECT item_id, quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? LIMIT 1");
    $stmt->execute([$cartId, $variantId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE item_id = ?");
        $stmt->execute([$quantity, $item['item_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, added_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$cartId, $productId, $variantId, $quantity]);
    }
}

function getCartItems(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT
            ci.item_id,
            ci.product_id,
            ci.variant_id,
            ci.quantity,
            p.name,
            p.name AS product_name,
            p.description,
            p.price,
            p.image_url,
            pv.size,
            pv.colour,
            pv.stock_quantity AS variant_stock
        FROM cart c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        JOIN product_variants pv ON ci.variant_id = pv.variant_id
        JOIN products p ON ci.product_id = p.product_id
        WHERE c.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCartTotal(array $items): float {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function savePurchaseHistory(PDO $pdo, int $userId, array $items, float $total): int {
    // Save the order
    $stmt = $pdo->prepare("INSERT INTO purchase_history (user_id, total_amount, purchased_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $total]);
    $orderId = (int)$pdo->lastInsertId();

    // Save each item
    foreach ($items as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO purchase_history_items (order_id, product_id, product_name, quantity, price)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['name'],
            $item['quantity'],
            $item['price']
        ]);
    }

    return $orderId;
}

function clearCart(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cart['cart_id']]);
    }
}

?>