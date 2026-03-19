<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

$action = $_POST['action'] ?? '';

function jsonError($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function jsonSuccess() {
    echo json_encode(['success' => true]);
    exit;
}

// Add variant
if ($action === 'add_variant') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $size = trim(htmlspecialchars($_POST['size'] ?? '', ENT_QUOTES, 'UTF-8'));
    $colour = trim(htmlspecialchars($_POST['colour'] ?? '', ENT_QUOTES, 'UTF-8'));
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);

    if (!$productId) jsonError('Invalid product');
    if ($stock === false || $stock < 0) jsonError('Stock must be 0 or greater');

    $size = $size === '' ? null : $size;
    $colour = $colour === '' ? null : $colour;

    try {
        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, colour, stock_quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$productId, $size, $colour, max(0, $stock)]);
        jsonSuccess();
    } catch (PDOException $e) {
        jsonError('Failed to add variant');
    }
}

// Update variant
if ($action === 'update_variant') {
    $variantId = (int)($_POST['variant_id'] ?? 0);
    $size = trim(htmlspecialchars($_POST['size'] ?? '', ENT_QUOTES, 'UTF-8'));
    $colour = trim(htmlspecialchars($_POST['colour'] ?? '', ENT_QUOTES, 'UTF-8'));
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);

    if (!$variantId) jsonError('Invalid variant');
    if ($stock === false || $stock < 0) jsonError('Stock must be 0 or greater');

    $size = $size === '' ? null : $size;
    $colour = $colour === '' ? null : $colour;

    try {
        $stmt = $pdo->prepare("UPDATE product_variants SET size = ?, colour = ?, stock_quantity = ? WHERE variant_id = ?");
        $stmt->execute([$size, $colour, max(0, $stock), $variantId]);
        jsonSuccess();
    } catch (PDOException $e) {
        jsonError('Failed to update variant');
    }
}

// Delete variant
if ($action === 'delete_variant') {
    $variantId = (int)($_POST['variant_id'] ?? 0);
    if (!$variantId) jsonError('Invalid variant');

    try {
        $stmt = $pdo->prepare("SELECT product_id FROM product_variants WHERE variant_id = ?");
        $stmt->execute([$variantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonError('Variant not found');

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
        $stmt->execute([$row['product_id']]);
        $count = (int)$stmt->fetchColumn();

        if ($count <= 1) {
            jsonError('Product must have at least one variant. Add another before deleting.');
        }

        $stmt = $pdo->prepare("DELETE FROM product_variants WHERE variant_id = ?");
        $stmt->execute([$variantId]);
        jsonSuccess();
    } catch (PDOException $e) {
        jsonError('Failed to delete variant');
    }
}

jsonError('Invalid action');
