<?php
require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ROOT . '/config/admin_timeout.php';

try {
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $menItems = $pdo->query("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.department = 'Men'")->fetchColumn();
    $womenItems = $pdo->query("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.department = 'Women'")->fetchColumn();

    $threshold = 5;
    try {
        $settingStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'low_stock_threshold'");
        if ($settingStmt && ($t = $settingStmt->fetchColumn()) !== false) {
            $threshold = (int)$t;
        }
    } catch (PDOException $e) {
        $threshold = 5;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM (
            SELECT p.product_id, COALESCE(SUM(pv.stock_quantity), 0) AS total_stock
            FROM products p
            LEFT JOIN product_variants pv ON p.product_id = pv.product_id
            GROUP BY p.product_id
            HAVING total_stock < ?
        ) AS low_stock_products
    ");
    $stmt->execute([$threshold]);
    $lowStock = $stmt->fetchColumn();

    $sql = "SELECT p.product_id, p.name, p.price, p.description, p.image_url, p.category_id,
            c.name AS cat_name, c.department,
            COALESCE(SUM(pv.stock_quantity), 0) AS total_stock
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN product_variants pv ON p.product_id = pv.product_id
            GROUP BY p.product_id, p.name, p.price, p.description, p.image_url, p.category_id, c.name, c.department
            ORDER BY p.product_id DESC";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();

    $catStmt = $pdo->query("SELECT department, name FROM categories ORDER BY department, name");
    $categoriesByDept = [];
    while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
        $dept = $row['department'];
        if (!isset($categoriesByDept[$dept])) {
            $categoriesByDept[$dept] = [];
        }
        $categoriesByDept[$dept][] = $row['name'];
    }

    $womenOrder = ['Tops', 'Dresses', 'Jackets', 'Skirts', 'Accessories'];
    if (!empty($categoriesByDept['Women'])) {
        $categoriesByDept['Women'] = array_values(
            array_intersect($womenOrder, $categoriesByDept['Women'])
        );
    }
} catch (PDOException $e) {
    error_log("Admin index error: " . $e->getMessage());
    $_SESSION['admin_error'] = "Database error. Please ensure all tables (products, categories, product_variants) exist.";
    $totalProducts = $menItems = $womenItems = $lowStock = 0;
    $products = [];
    $categoriesByDept = ['Men' => [], 'Women' => []];
    $threshold = 5;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniClothes — Admin Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<script>
    window.categoryMap = <?php echo json_encode($categoriesByDept); ?>;
</script>

<?php include ROOT . '/includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h2>Products</h2>
        <button class="btn-add" onclick="openAddModal()">+ Add Product</button>
    </div>

    <div class="page-body">
        <?php if (!empty($_SESSION['admin_error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['admin_error']);
                    unset($_SESSION['admin_error']);
                ?>
            </div>
        <?php endif; ?>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Products</div>
                <div class="stat-value accent"><?php echo $totalProducts; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Men's Items</div>
                <div class="stat-value"><?php echo $menItems; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Women's Items</div>
                <div class="stat-value"><?php echo $womenItems; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Low Stock</div>
                <div class="stat-value warn"><?php echo $lowStock; ?></div>
            </div>
        </div>

        <div class="toolbar">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍  Search products...">
            
            <select id="deptFilter" class="filter-select">
                <option value="">All Departments</option>
                <option value="Men">Men</option>
                <option value="Women">Women</option>
            </select>
            
            <select id="catFilter" class="filter-select">
                <option value="">All Categories</option>
                <option value="T-Shirts">T-Shirts</option>
                <option value="Hoodies">Hoodies</option>
                <option value="Dresses">Dresses</option>
                <option value="Tops">Tops</option>
            </select>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Department</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <?php 
                                    $img = $p['image_url'];
                                    if (strpos($img, 'http') !== 0 && strpos($img, '../') !== 0) {
                                        $img = '../' . $img; 
                                    }?>
                                <img src="<?php echo htmlspecialchars($img); ?>" width="30" height="30" class="rounded me-2" style="object-fit: cover;">
                                <span class="product-name"><?php echo htmlspecialchars($p['name']); ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge-dept badge-<?php echo strtolower($p['department']); ?>">
                                <?php echo $p['department']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($p['cat_name']); ?></td>
                        <td class="price">$<?php echo number_format($p['price'], 2); ?></td>
                        <td class="<?php echo ($p['total_stock'] ?? 0) < $threshold ? 'stock-low' : 'stock-ok'; ?>">
                            <?php echo (int)($p['total_stock'] ?? 0); ?> in stock
                        </td>
                        <td>
                            <button class="btn-edit" onclick="openEditModal(
                                <?php echo $p['product_id']; ?>, 
                                '<?php echo addslashes($p['name']); ?>', 
                                <?php echo $p['price']; ?>, 
                                '<?php echo $p['department']; ?>', 
                                '<?php echo $p['cat_name']; ?>', 
                                <?php echo (int)($p['total_stock'] ?? 0); ?>, 
                                '<?php echo addslashes($p['description']); ?>',
                                '<?php echo $p['image_url']; ?>'
                            )">Edit</button>
                                <button class="btn-delete" onclick="openDeleteModal(<?php echo $p['product_id']; ?>, '<?php echo addslashes($p['name']); ?>')">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="productModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Add Product</h3>
        </div>
        
        <form action="process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="product_id" id="productId">

            <div class="modal-body">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="productName" placeholder="e.g. Charcoal Zip Hoodie" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="dept" id="productDept" required>
                            <option value="">Select department</option>
                            <option value="Men">Men</option>
                            <option value="Women">Women</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="productCategory" required disabled>
                            <option value="">Select department first</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" name="price" id="productPrice" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group" id="productStockGroup">
                        <label>Stock Quantity <span class="text-muted small">(used if no variants below)</span></label>
                        <input type="number" name="stock" id="productStock" placeholder="0" min="0" value="0" required>
                    </div>
                </div>

                <div id="variantsSection" class="form-group">
                    <hr class="my-4">
                    <label class="fw-bold mb-2">Size / Colour Variants</label>
                    <p class="variants-help-text small mb-3">Manage different sizes and colours for this product. Each variant has its own stock.</p>
                    <div class="variants-add-box mb-3 p-3 border rounded">
                        <label class="small fw-semibold mb-2 d-block">Add New Variant</label>
                        <div class="d-flex flex-wrap gap-2 align-items-end">
                            <div>
                                <label class="variants-field-label small d-block">Size</label>
                                <select id="newVariantSize" class="form-select form-select-sm" style="width: 120px;">
                                    <option value="">— Select —</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="2XL">2XL</option>
                                    <option value="One Size">One Size</option>
                                    <option value="_custom">Other (type below)</option>
                                </select>
                                <input type="text" id="newVariantSizeCustom" class="form-control form-control-sm mt-1" placeholder="Custom size" style="width: 120px; display: none;">
                            </div>
                            <div>
                                <label class="variants-field-label small d-block">Colour</label>
                                <select id="newVariantColour" class="form-select form-select-sm" style="width: 120px;">
                                    <option value="">— Select —</option>
                                    <option value="Black">Black</option>
                                    <option value="White">White</option>
                                    <option value="Navy">Navy</option>
                                    <option value="Grey">Grey</option>
                                    <option value="Charcoal">Charcoal</option>
                                    <option value="Red">Red</option>
                                    <option value="Blue">Blue</option>
                                    <option value="Green">Green</option>
                                    <option value="Burgundy">Burgundy</option>
                                    <option value="Camel">Camel</option>
                                    <option value="Brown">Brown</option>
                                    <option value="Pink">Pink</option>
                                    <option value="Beige">Beige</option>
                                    <option value="_custom">Other (type below)</option>
                                </select>
                                <input type="text" id="newVariantColourCustom" class="form-control form-control-sm mt-1" placeholder="Custom colour" style="width: 120px; display: none;">
                            </div>
                            <div>
                                <label class="variants-field-label small d-block">Stock</label>
                                <input type="number" id="newVariantStock" class="form-control form-control-sm" min="0" value="0" style="width: 80px;">
                            </div>
                            <button type="button" class="btn btn-sm btn-dark" id="addVariantBtn">Add Variant</button>
                        </div>
                    </div>
                    <div class="table-responsive variants-table-wrap">
                        <table class="table table-sm table-bordered variants-table">
                            <thead><tr><th>Size</th><th>Colour</th><th>Stock</th><th>Actions</th></tr></thead>
                            <tbody id="variantsTableBody"></tbody>
                            <tbody id="variantsToAddBody"></tbody>
                        </table>
                    </div>
                </div>
                
                <div class="form-group position-relative" id="imageFormGroup">
                    <label>Image</label>
                    <input type="file" name="image" id="productImage" accept="image/*" class="image-file-input-hidden">
                    <div id="imageAddState">
                        <button type="button" class="btn btn-sm btn-outline-dark" id="addImageBtn">Choose file</button>
                        <span class="small text-muted ms-2" id="addFileName">No file chosen</span>
                    </div>
                    <div id="imageEditState" style="display: none;">
                        <div class="image-current-wrap mb-3">
                            <p class="small text-muted mb-1">Current image</p>
                            <img id="productImagePreview" src="" alt="Current product" class="img-fluid rounded image-preview-thumb image-preview-clickable" role="button" tabindex="0">
                        </div>
                        <div class="image-replace-wrap">
                            <label class="small fw-semibold d-block mb-1">Replace image</label>
                            <button type="button" class="btn btn-sm btn-outline-dark" id="replaceImageBtn">Choose file</button>
                            <span class="small text-muted ms-2" id="replaceFileName">No file chosen</span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="desc" id="productDesc" placeholder="Short product description..." required></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('productModal')">Cancel</button>
                <button type="submit" class="btn-save" id="modalSaveBtn">Save Product</button>
            </div>
        </form>
    </div>
</div>

<div class="image-expand-overlay" id="imageExpandOverlay" role="button" tabindex="-1" aria-label="Close expanded image">
    <img id="imageExpandImg" src="" alt="Expanded product image">
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="delete-modal-box">
        <div class="delete-icon">🗑️</div>
        <h3>Delete Product</h3>
        <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?<br>This action cannot be undone.</p>
        
        <form action="process.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="deleteProductId">
            
            <div class="delete-actions mt-4">
                <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="../js/admin.js"></script>
</body>
</html>