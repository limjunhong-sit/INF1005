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

<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>UniClothes</h1>
        <p>Admin Page</p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Management</div>
        <a href="#" class="nav-link-item active">
            <span class="icon">👕</span> Products
        </a>
        <a href="#" class="nav-link-item">
            <span class="icon">📦</span> Orders
        </a>
        <a href="#" class="nav-link-item">
            <span class="icon">👥</span> Customers
        </a>
        <a href="#" class="nav-link-item">
            <span class="icon">🏷️</span> Categories
        </a>
        <div class="nav-section-label" style="margin-top:16px">Reports</div>
        <a href="#" class="nav-link-item">
            <span class="icon">📊</span> Analytics
        </a>
        <a href="#" class="nav-link-item">
            <span class="icon">⚙️</span> Settings
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-badge">
            <div class="admin-avatar">AD</div>
            <div class="admin-info">
                <strong>Admin</strong>
                <span>Administrator</span>
            </div>
        </div>
    </div>
</aside>

<div class="main-content">
    <div class="topbar">
        <h2>Products</h2>
        <button class="btn-add" onclick="openAddModal()">+ Add Product</button>
    </div>

    <div class="page-body">

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Products</div>
                <div class="stat-value accent">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Men's Items</div>
                <div class="stat-value">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Women's Items</div>
                <div class="stat-value">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Low Stock</div>
                <div class="stat-value warn">0</div>
            </div>
        </div>

        <div class="toolbar">
            <input type="text" class="search-input" placeholder="🔍  Search products...">
            <select class="filter-select">
                <option value="">All Departments</option>
                <option value="men">Men</option>
                <option value="women">Women</option>
            </select>
            <select class="filter-select">
                <option value="">All Categories</option>
                <option value="tshirts">T-Shirts</option>
                <option value="hoodies">Hoodies</option>
                <option value="dresses">Dresses</option>
                <option value="tops">Tops</option>
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
                    <tr>
                        <td>
                            <div class="product-cell">
                                <div class="product-img-placeholder">👕</div>
                                <span class="product-name">Charcoal Zip Hoodie</span>
                            </div>
                        </td>
                        <td><span class="badge-dept badge-men">Men</span></td>
                        <td>Hoodies</td>
                        <td class="price">$54.99</td>
                        <td class="stock-ok">25 in stock</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-edit" onclick="openEditModal('Charcoal Zip Hoodie','54.99','Men','Hoodies',25,'Zip-up hoodie with front pockets. Versatile layering piece.')">Edit</button>
                                <button class="btn-delete" onclick="openDeleteModal('Charcoal Zip Hoodie')">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <div class="product-img-placeholder">👕</div>
                                <span class="product-name">Classic White Tee</span>
                            </div>
                        </td>
                        <td><span class="badge-dept badge-men">Men</span></td>
                        <td>T-Shirts</td>
                        <td class="price">$24.99</td>
                        <td class="stock-ok">0 in stock</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-edit" onclick="openEditModal('Classic White Tee','24.99','Men','T-Shirts',0,'Everyday essential. Soft cotton, relaxed fit.')">Edit</button>
                                <button class="btn-delete" onclick="openDeleteModal('Classic White Tee')">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <div class="product-img-placeholder">👗</div>
                                <span class="product-name">Floral Summer Dress</span>
                            </div>
                        </td>
                        <td><span class="badge-dept badge-women">Women</span></td>
                        <td>Dresses</td>
                        <td class="price">$49.99</td>
                        <td class="stock-low">0 in stock</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-edit" onclick="openEditModal('Floral Summer Dress','49.99','Women','Dresses',0,'Lightweight floral print wrap dress, perfect for summer.')">Edit</button>
                                <button class="btn-delete" onclick="openDeleteModal('Floral Summer Dress')">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <div class="product-img-placeholder">👚</div>
                                <span class="product-name">Crop Top Coral</span>
                            </div>
                        </td>
                        <td><span class="badge-dept badge-women">Women</span></td>
                        <td>Tops</td>
                        <td class="price">$29.99</td>
                        <td class="stock-ok">0 in stock</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-edit" onclick="openEditModal('Crop Top Coral','29.99','Women','Tops',0,'Trendy coral crop top. Perfect for layering.')">Edit</button>
                                <button class="btn-delete" onclick="openDeleteModal('Crop Top Coral')">Delete</button>
                            </div>
                        </td>
                    </tr>
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
        <div class="modal-body">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" id="productName" placeholder="e.g. Charcoal Zip Hoodie">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Department</label>
                    <select id="productDept">
                        <option value="">Select department</option>
                        <option value="Men">Men</option>
                        <option value="Women">Women</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="productCategory">
                        <option value="">Select category</option>
                        <option>T-Shirts</option>
                        <option>Hoodies</option>
                        <option>Dresses</option>
                        <option>Tops</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" id="productPrice" placeholder="0.00" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" id="productStock" placeholder="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" id="productImage" placeholder="https://example.com/image.jpg">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="productDesc" placeholder="Short product description..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('productModal')">Cancel</button>
            <button class="btn-save" id="modalSaveBtn">Save Product</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="delete-modal-box">
        <div class="delete-icon">🗑️</div>
        <h3>Delete Product</h3>
        <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?<br>This action cannot be undone.</p>
        <div class="delete-actions">
            <button class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn-confirm-delete">Yes, Delete</button>
        </div>
    </div>
</div>

<script src="js/admin.js"></script>
</body>
</html>