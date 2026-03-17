<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity >= 1) {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE item_id = ?");
        $stmt->execute([$quantity, $itemId]);
    }
}

header("Location: cart.php");
exit;
?>