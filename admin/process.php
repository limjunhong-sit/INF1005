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
    // Changed $_POST['id'] to $_POST['product_id']
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$_POST['product_id']]);
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

    // 2. Validate Numbers (Ensure they are actually numbers)
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);

    // 3. Final Verification: If validation failed, redirect back with error instead of showing a blank page
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
    if ($stock === false || $stock < 0) {
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
        // 3. Decide: Are we Updating or Inserting?
        if (!empty($id)) {
            // UPDATE EXISTING PRODUCT (Because an ID was sent from the Edit Modal)
            $sql = "UPDATE products 
                    SET name=?, price=?, stock_quantity=?, description=?, image_url=?, category_id=? 
                    WHERE product_id=?";
            $pdo->prepare($sql)->execute([$name, $price, $stock, $desc, $img, $catId, $id]);
        } else {
            // INSERT NEW PRODUCT (Because no ID was sent from the Add Modal)
            $sql = "INSERT INTO products (name, price, stock_quantity, description, image_url, category_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$name, $price, $stock, $desc, $img, $catId]);
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