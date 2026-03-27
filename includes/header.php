<?php
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT session_id FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $sessionCheck = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sessionCheck && $sessionCheck['session_id'] !== session_id()) {
        session_unset();
        session_destroy();
        header("Location: /signin.php?msg=signed_out");
        exit;
    }
}
?>

<header class="sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand" href="/index.php">
                <img src="/image/ecommerce_logo.png" alt="" width="40" height="30" class="rounded-circle">UniClothes
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="/index.php">Home</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="menDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Men</a>
                        <ul class="dropdown-menu" aria-labelledby="menDropdown">
                            <li><a class="dropdown-item" href="/shop.php?dept=Men">All Men's</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Men&cat=T-Shirts">T-Shirts</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Men&cat=Hoodies">Hoodies</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Men&cat=Jackets">Jackets</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Men&cat=Pants">Pants</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Men&cat=Accessories">Accessories</a></li>

                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="womenDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Women</a>
                        <ul class="dropdown-menu" aria-labelledby="womenDropdown">
                            <li><a class="dropdown-item" href="/shop.php?dept=Women">All Women's</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Women&cat=Tops">Tops</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Women&cat=Dresses">Dresses</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Women&cat=Jackets">Jackets</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Women&cat=Skirts">Skirts</a></li>
                            <li><a class="dropdown-item" href="/shop.php?dept=Women&cat=Accessories">Accessories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/about.php">About Us</a></li>
                </ul>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <div class="admin-view-text mx-auto px-3 text-center"> VIEWING AS CUSTOMER </div>
                <?php endif; ?>

                
                <ul class="navbar-nav ms-auto">
                    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="/cart/cart.php" title="Cart">🛒 Cart</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <button id="themeToggle" class="btn nav-link" title="Toggle Dark Mode">🌓</button>
                    </li>
                    <li class="nav-item">
                        <button class="btn nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#searchSection">🔍</button>
                    </li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown ms-2 me-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none;">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['first_name'] ?? 'U') ?>&background=9B744A&color=fff&rounded=true&bold=true&length=1" 
                                    alt="Profile" 
                                    width="40" height="40" 
                                    class="rounded-circle shadow-sm"
                                    style="border: 2px solid var(--accent);">
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="profileDropdown" style="background-color: var(--warm-white); min-width: 200px;">
                                <li>
                                    <div class="px-3 py-2">
                                        <span class="d-block fw-bold" style="color: var(--charcoal); font-family: 'Bebas Neue', sans-serif; letter-spacing: 1px; font-size: 1.2rem;">
                                            <?= strtoupper(htmlspecialchars($_SESSION['first_name'] ?? 'User')) ?>
                                        </span>
                                        <span class="d-block small text-muted" style="font-size: 0.8rem;">
                                            <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
                                        </span>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider border-secondary"></li>
                                
                                <li><a class="dropdown-item py-2" href="/profile.php">My Profile</a></li>
                                
                                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                                    <li><a class="dropdown-item py-2" href="/cart/cart.php">My Cart</a></li>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold text-primary" href="/admin/dashboard.php">Dashboard</a></li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider border-secondary"></li>
                                <li><a class="dropdown-item py-2 text-danger fw-bold" href="/auth/logout.php">Sign Out</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-2"><a class="nav-link" href="/register.php">Register</a></li>
                        <li class="nav-item ms-2"><a class="nav-link" href="/signin.php">Sign In</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="collapse bg-light border-top" id="searchSection">
        <div class="container py-3">
            <form action="search_results.php" method="GET" class="d-flex justify-content-center">
                <input name="query" class="form-control me-2 w-50" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </form>
        </div>
    </div>
    <script>
        const themeBtn = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-theme');
        }

        themeBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            
            if (document.body.classList.contains('dark-theme')) {
                localStorage.setItem('theme', 'dark'); 
            } else {
                localStorage.setItem('theme', 'light'); 
            }
        });
    </script>
</header>
