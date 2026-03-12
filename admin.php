<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { //admin check
    header("Location: signin.php");
    exit();
}
include 'db_connect.php';

$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$menItems = $pdo->query("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.department = 'Men'")->fetchColumn();
$womenItems = $pdo->query("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.department = 'Women'")->fetchColumn();

// Read the threshold from the session, or default to 5 (temporary until database has settings table)
$threshold = $_SESSION['temp_low_stock_threshold'] ?? 5;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity < ?");
$stmt->execute([$threshold]);
$lowStock = $stmt->fetchColumn();

$sql = "SELECT p.*, c.name as cat_name, c.department 
        FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.product_id DESC";
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniClothes — Admin Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h2>Products</h2>
        <button class="btn-add" onclick="openAddModal()">+ Add Product</button>
    </div>

    <div class="page-body">
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
                                <img src="<?php echo htmlspecialchars($p['image_url']); ?>" width="30" height="30" class="rounded me-2">
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
                        <td class="<?php echo $p['stock_quantity'] < $threshold ? 'stock-low' : 'stock-ok'; ?>">
                            <?php echo $p['stock_quantity']; ?> in stock
                        </td>
                        <td>
                            <button class="btn-edit" onclick="openEditModal(
                                <?php echo $p['product_id']; ?>, 
                                '<?php echo addslashes($p['name']); ?>', 
                                <?php echo $p['price']; ?>, 
                                '<?php echo $p['department']; ?>', 
                                '<?php echo $p['cat_name']; ?>', 
                                <?php echo $p['stock_quantity']; ?>, 
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
            <button class="modal-close" onclick="closeModal('productModal')">✕</button>
        </div>
        
        <form action="admin_process.php" method="POST">
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
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" id="productStock" placeholder="0" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image" id="productImage" placeholder="https://example.com/image.jpg" required>
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

<div class="modal-overlay" id="deleteModal">
    <div class="delete-modal-box">
        <div class="delete-icon">🗑️</div>
        <h3>Delete Product</h3>
        <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?<br>This action cannot be undone.</p>
        
        <form action="admin_process.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="deleteProductId">
            
            <div class="delete-actions mt-4">
                <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="js/admin.js"></script>
</body>
</html>