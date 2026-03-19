<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

$productId = (int)($_GET['product_id'] ?? 0);
if (!$productId) {
    echo json_encode(['error' => 'Invalid product']);
    exit;
}

$stmt = $pdo->prepare("SELECT variant_id, product_id, size, colour, stock_quantity FROM product_variants WHERE product_id = ? ORDER BY size, colour");
$stmt->execute([$productId]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['variants' => $variants]);
