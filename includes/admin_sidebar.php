<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>UniClothes</h1>
        <p>Admin Page</p>
    </div>
    <nav class="sidebar-nav">
        <a href="../index.php" class="nav-link-item">
            <span class="icon">🏠</span> Customer's View
        </a>
        <a href="index.php" class="nav-link-item <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
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
        <a href="manage_admins.php" class="nav-link-item <?php echo ($currentPage === 'manage_admins.php') ? 'active' : ''; ?>">
            <span class="icon">🧑‍💻</span> Admins
        </a>
        <a href="#" id="adminThemeToggle" class="nav-link-item" style="margin-top: 15px;">
            <span class="icon">🌓</span> Dark Mode
        </a>
        <a href="../auth/logout.php" class="nav-link-item">
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeBtn = document.getElementById('adminThemeToggle');
        const currentTheme = localStorage.getItem('theme');

        if (currentTheme === 'dark') {
            document.body.classList.add('dark-theme');
        }

        themeBtn.addEventListener('click', (e) => {
            e.preventDefault(); 
            document.body.classList.toggle('dark-theme');
            if (document.body.classList.contains('dark-theme')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    });
</script>
