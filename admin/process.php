<?php
// 1. Start session BEFORE any output
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. Fixed Security Check: Added isset() to prevent errors
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    exit('Unauthorized access.'); 
}

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

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
    
    // Sanitize the URL to remove illegal characters
    $img  = filter_var(trim($_POST['image'] ?? ''), FILTER_SANITIZE_URL);

    // 2. Validate Numbers (Ensure they are actually numbers)
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);

    // 3. Final Verification: Stop the script if validation failed
    if (empty($name) || empty($desc) || empty($img)) {
        die("Error: Name, description, and image URL cannot be empty.");
    }
    if ($price === false || $price < 0) {
        die("Error: Price must be a valid positive number.");
    }
    if ($stock === false || $stock < 0) {
        die("Error: Stock must be a valid whole number (0 or higher).");
    }

    // 1. Execute the search for the Category ID
    $cStmt = $pdo->prepare("SELECT category_id FROM categories WHERE department = ? AND name = ?");
    $cStmt->execute([$dept, $cat]);
    $catId = $cStmt->fetchColumn();

    // 2. Safety check: If someone messed with the HTML dropdowns, stop the script
    if (!$catId) {
        die("Error: Invalid Department and Category combination. Please check your spelling.");
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
        die("Database Error: " . $e->getMessage());
    }
}

// Redirect back to dashboard
header("Location: index.php");
exit();
?>