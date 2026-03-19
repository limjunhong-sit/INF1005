<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

$action = $_POST['action'] ?? '';

// ==========================================
// DELETE PRODUCT
// ==========================================
if ($action === 'delete') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId) {
        $stmt = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $stmt->execute([$productId]);
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$productId]);
    }
}

// ==========================================
// ADD OR EDIT PRODUCT
// ==========================================
if ($action === 'save') {
    $id = $_POST['product_id'] ?? '';
    
    // 1. Sanitize Strings (Remove accidental spaces and block HTML/Scripts)
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars(trim($_POST['desc'] ?? ''), ENT_QUOTES, 'UTF-8');
    $dept = htmlspecialchars(trim($_POST['dept'] ?? ''), ENT_QUOTES, 'UTF-8');
    $cat  = htmlspecialchars(trim($_POST['category'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Handle image upload (or existing image on edit)
    $img = '';
    $uploadDir = ROOT . '/uploads/';
    $relativeUploadDir = 'uploads/'; // stored in DB, used on frontend

    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!empty($_FILES['image']['name'])) {
        $originalName = basename($_FILES['image']['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $filename = $safeBase . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            die("Error: Failed to upload image.");
        }

        $img = $relativeUploadDir . $filename;
    } else {
        // When editing, keep existing image if no new file uploaded
        if (!empty($id)) {
            $stmtImg = $pdo->prepare("SELECT image_url FROM products WHERE product_id = ?");
            $stmtImg->execute([$id]);
            $img = $stmtImg->fetchColumn() ?: '';
        }
    }

    // 2. Validate Numbers
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);
    if (empty($id)) {
        $stock = ($stock === false || $stock < 0) ? 0 : $stock;
    }

    // 3. Final Verification
    if (empty($name) || empty($desc) || empty($img)) {
        $_SESSION['admin_error'] = "Name, description, and image cannot be empty.";
        header("Location: index.php");
        exit();
    }
    if ($price === false || $price < 0) {
        $_SESSION['admin_error'] = "Price must be a valid positive number.";
        header("Location: index.php");
        exit();
    }
    if (empty($id) && ($stock === false || $stock < 0)) {
        $_SESSION['admin_error'] = "Stock must be a valid whole number (0 or higher).";
        header("Location: index.php");
        exit();
    }

    // 1. Execute the search for the Category ID
    $cStmt = $pdo->prepare("SELECT category_id FROM categories WHERE department = ? AND name = ?");
    $cStmt->execute([$dept, $cat]);
    $catId = $cStmt->fetchColumn();

    // 2. Safety check: If someone messed with the HTML dropdowns, redirect with error
    if (!$catId) {
        $_SESSION['admin_error'] = "Invalid Department and Category combination. Please check your selection.";
        header("Location: index.php");
        exit();
    }

    try {
        if (!empty($id)) {
            // UPDATE EXISTING PRODUCT (variants managed separately in variants UI)
            $sql = "UPDATE products 
                    SET name=?, price=?, description=?, image_url=?, category_id=? 
                    WHERE product_id=?";
            $pdo->prepare($sql)->execute([$name, $price, $desc, $img, $catId, $id]);
            // Ensure at least one variant exists
            $stmt = $pdo->prepare("SELECT variant_id FROM product_variants WHERE product_id = ? LIMIT 1");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, colour, stock_quantity) VALUES (?, 'One Size', NULL, 0)");
                $stmt->execute([$id]);
            }
        } else {
            // INSERT NEW PRODUCT
            $sql = "INSERT INTO products (name, price, description, image_url, category_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$name, $price, $desc, $img, $catId]);
            $productId = (int)$pdo->lastInsertId();

            $sizes = $_POST['variant_size'] ?? [];
            $colours = $_POST['variant_colour'] ?? [];
            $stocks = $_POST['variant_stock'] ?? [];
            if (is_string($sizes)) $sizes = [$sizes];
            if (is_string($colours)) $colours = [$colours];
            if (is_string($stocks)) $stocks = [$stocks];

            if (!empty($sizes) && !empty($stocks)) {
                $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, colour, stock_quantity) VALUES (?, ?, ?, ?)");
                foreach ($sizes as $i => $size) {
                    $sizeVal = trim(htmlspecialchars($size ?? '', ENT_QUOTES, 'UTF-8')) ?: null;
                    $colourVal = isset($colours[$i]) ? (trim(htmlspecialchars($colours[$i], ENT_QUOTES, 'UTF-8')) ?: null) : null;
                    $stockVal = isset($stocks[$i]) ? max(0, (int)$stocks[$i]) : 0;
                    $stmt->execute([$productId, $sizeVal ?: 'One Size', $colourVal, $stockVal]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, size, colour, stock_quantity) VALUES (?, 'One Size', NULL, ?)");
                $stmt->execute([$productId, max(0, (int)$stock)]);
            }
        }
    } catch (PDOException $e) {
        // Catch any database crashes (like if a string is too long for a column)
        $_SESSION['admin_error'] = "Database Error: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Redirect back to dashboard
header("Location: index.php");
exit();
?>