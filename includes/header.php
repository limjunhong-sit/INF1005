<?php
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block"); 
if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<header class="sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light">
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
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <button id="themeToggle" class="btn nav-link" title="Toggle Dark Mode">🌓</button>
                    </li>
                    <li class="nav-item">
                        <button class="btn nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#searchSection">🔍</button>
                    </li>
                    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/cart/cart.php">Cart</a>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link fw-bold text-primary" href="/admin/">Dashboard</a>
                            </li>
                        <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="/auth/logout.php">Sign Out</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="/signin.php">Sign In</a></li>
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
