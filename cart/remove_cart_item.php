<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php'; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int)$_POST['item_id'];

    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE item_id = ?");
    $stmt->execute([$itemId]);
}

header("Location: cart.php");
exit;
?>