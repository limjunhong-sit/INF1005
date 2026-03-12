<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="image/ecommerce_logo.png" alt="" width="40" height="30" class="rounded-circle">UniClothes
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="index.php">Home</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="menDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Men</a>
                        <ul class="dropdown-menu" aria-labelledby="menDropdown">
                            <li><a class="dropdown-item" href="MenTshirt.php">T-Shirts</a></li>
                            <li><a class="dropdown-item" href="MenHoodies.php">Hoodies</a></li>
                            <!--<li><a class="dropdown-item" href="#">Shirts</a></li>
                            <li><a class="dropdown-item" href="#">Jackets</a></li>
                            <li><a class="dropdown-item" href="#">Pants</a></li>
                            <li><a class="dropdown-item" href="#">Accessories</a></li>-->
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="womenDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Women</a>
                        <ul class="dropdown-menu" aria-labelledby="womenDropdown">
                            <li><a class="dropdown-item" href="WomenTop.php">Tops</a></li>
                            <li><a class="dropdown-item" href="WomenDresses.php">Dresses</a></li>
                            <!--<li><a class="dropdown-item" href="#">Hoodies</a></li>
                            <li><a class="dropdown-item" href="#">Jackets</a></li>
                            <li><a class="dropdown-item" href="#">Skirts</a></li>
                            <li><a class="dropdown-item" href="#">Accessories</a></li>-->
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <button class="btn nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#searchSection">🔍</button>
                    </li>
                    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">Cart</a>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link fw-bold text-primary" href="admin.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">Sign Out</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="signin.php">Sign In</a></li>
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
</header>
