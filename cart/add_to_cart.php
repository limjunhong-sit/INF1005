<?php
session_start();
require_once __DIR__ . '/cart_functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /register.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $productId = (int)$_POST['product_id'];

    addToCart($pdo, $userId, $productId, 1);
}

header("Location: /cart/cart.php");
exit;
?>