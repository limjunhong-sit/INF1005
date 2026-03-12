<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>UniClothes</h1>
        <p>Admin Page</p>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-link-item">
            <span class="icon">🏠</span> Homepage
        </a>
        <a href="admin.php" class="nav-link-item <?php echo ($currentPage === 'admin.php') ? 'active' : ''; ?>">
            <span class="icon">👕</span> Products
        </a>
        <a href="orders.php" class="nav-link-item <?php echo ($currentPage === 'orders.php') ? 'active' : ''; ?>">
            <span class="icon">📦</span> Orders
        </a>
        <a href="analytics.php" class="nav-link-item <?php echo ($currentPage === 'analytics.php') ? 'active' : ''; ?>">
            <span class="icon">📊</span> Analytics
        </a>
        <a href="settings.php" class="nav-link-item <?php echo ($currentPage === 'settings.php') ? 'active' : ''; ?>">
            <span class="icon">⚙️</span> Settings
        </a>
        <a href="logout.php" class="nav-link-item">
            <span class="icon">🚪</span> Logout
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