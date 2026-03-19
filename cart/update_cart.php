<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity >= 1) {
        $stmt = $pdo->prepare("
            SELECT ci.item_id, pv.stock_quantity
            FROM cart_items ci
            JOIN product_variants pv ON ci.variant_id = pv.variant_id
            WHERE ci.item_id = ?
        ");
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $maxQty = (int)$row['stock_quantity'];
            $quantity = min($quantity, $maxQty > 0 ? $maxQty : $quantity);
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE item_id = ?");
            $stmt->execute([$quantity, $itemId]);
        }
    }
}

header("Location: cart.php");
exit;
?>