<?php
// 1. Start session BEFORE any output
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. Fixed Security Check: Added isset() to prevent errors
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    exit('Unauthorized access.'); 
}

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

// Simple helper to set a flash message for the admin dashboard
function admin_flash_and_redirect(string $type, string $message): void {
    $_SESSION['admin_flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
    header("Location: index.php");
    exit();
}

$action = $_POST['action'] ?? '';

// ==========================================
// DELETE PRODUCT
// ==========================================
if ($action === 'delete') {
    $productId = $_POST['product_id'] ?? '';

    if (empty($productId)) {
        admin_flash_and_redirect('error', 'Missing product identifier. Please try again.');
    }

    // Confirm that the product exists before attempting deletion
    $checkStmt = $pdo->prepare("SELECT name FROM products WHERE product_id = ?");
    $checkStmt->execute([$productId]);
    $productName = $checkStmt->fetchColumn();

    if (!$productName) {
        admin_flash_and_redirect('error', 'Product not found or already deleted.');
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$productId]);

    admin_flash_and_redirect('success', 'Product "' . $productName . '" deleted successfully.');
}

// ==========================================
// ADD OR EDIT PRODUCT
// ==========================================
if ($action === 'save') {
    $id = $_POST['product_id'] ?? '';
    $isUpdate = !empty($id);

    // 1. Sanitize Strings (Remove accidental spaces and block HTML/Scripts)
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars(trim($_POST['desc'] ?? ''), ENT_QUOTES, 'UTF-8');
    $dept = htmlspecialchars(trim($_POST['dept'] ?? ''), ENT_QUOTES, 'UTF-8');
    $cat  = htmlspecialchars(trim($_POST['category'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Handle image upload (use existing image if no new file)
    $existingImage = trim($_POST['existing_image'] ?? '');
    $img = $existingImage;

    if (!empty($_FILES['image_file']['name'])) {
        // Resolve filesystem path and public URL path to match existing products
        $uploadDirFs  = ROOT . '/image/products/';
        $uploadDirUrl = 'image/products/';

        // Ensure upload directory exists
        if (!is_dir($uploadDirFs)) {
            if (!mkdir($uploadDirFs, 0755, true)) {
                admin_flash_and_redirect('error', 'Image folder is not writable. Please contact the site administrator.');
            }
        }

        $fileName   = basename($_FILES['image_file']['name']);
        $safeName   = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $fileName);
        $newName    = time() . '_' . $safeName;
        $targetFs   = $uploadDirFs . $newName;
        $targetUrl  = $uploadDirUrl . $newName;

        // Basic image check (only if function exists)
        if (function_exists('mime_content_type')) {
            $fileType = mime_content_type($_FILES['image_file']['tmp_name']);
            if ($fileType === false || strpos($fileType, 'image/') !== 0) {
                admin_flash_and_redirect('error', 'Uploaded file must be an image.');
            }
        }

        if (!is_uploaded_file($_FILES['image_file']['tmp_name']) ||
            !move_uploaded_file($_FILES['image_file']['tmp_name'], $targetFs)) {
            admin_flash_and_redirect('error', 'Failed to upload image. Please try again or use a smaller file.');
        }

        $img = $targetUrl;
    }

    // 2. Validate Numbers (Ensure they are actually numbers)
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);

    // 3. Final Verification: Stop the script if validation failed
    if (empty($name) || empty($desc)) {
        admin_flash_and_redirect('error', 'Name and description cannot be empty.');
    }
    if ($price === false || $price < 0) {
        admin_flash_and_redirect('error', 'Price must be a valid positive number.');
    }
    if ($stock === false || $stock < 0) {
        admin_flash_and_redirect('error', 'Stock must be a valid whole number (0 or higher).');
    }

    // 1. Execute the search for the Category ID
    $cStmt = $pdo->prepare("SELECT category_id FROM categories WHERE department = ? AND name = ?");
    $cStmt->execute([$dept, $cat]);
    $catId = $cStmt->fetchColumn();

    // 2. Safety check: If someone messed with the HTML dropdowns, stop the script
    if (!$catId) {
        admin_flash_and_redirect('error', 'Invalid Department and Category combination. Please check your selection.');
    }

    try {
        // 3. Decide: Are we Updating or Inserting?
        if ($isUpdate) {
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
        admin_flash_and_redirect('error', 'Database Error: ' . $e->getMessage());
    }
    
    $message = $isUpdate ? 'Product updated successfully.' : 'Product created successfully.';
    admin_flash_and_redirect('success', $message);
}
?>