<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
require_once __DIR__ . '/cart_functions.php';

if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['HTTP_REFERER'] ?? '/index.php');
    header("Location: /signin.php?redirect=" . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_SESSION['user_id'];
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($variantId <= 0 && $productId > 0) {
        // Fallback: use first variant when only product_id is sent (e.g. legacy link)
        $stmt = $pdo->prepare("SELECT variant_id FROM product_variants WHERE product_id = ? ORDER BY variant_id LIMIT 1");
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $variantId = $row ? (int)$row['variant_id'] : 0;
    }

    if ($variantId > 0) {
        $stmt = $pdo->prepare("SELECT variant_id, product_id FROM product_variants WHERE variant_id = ?");
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($variant) {
            if ($productId && (int)$variant['product_id'] !== $productId) {
                header("Location: /cart/cart.php?error=invalid_variant");
                exit;
            }
            addToCart($pdo, $userId, $variantId, 1);
        }
    }
}

header("Location: /cart/cart.php");
exit;
?>