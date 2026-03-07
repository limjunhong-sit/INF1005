<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
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
                            <li><a class="dropdown-item" href="#">Shirts</a></li>
                            <li><a class="dropdown-item" href="#">Hoodies</a></li>
                            <li><a class="dropdown-item" href="#">Jackets</a></li>
                            <li><a class="dropdown-item" href="#">Pants</a></li>
                            <li><a class="dropdown-item" href="#">Accessories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="womenDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Women</a>
                        <ul class="dropdown-menu" aria-labelledby="womenDropdown">
                            <li><a class="dropdown-item" href="#">Tops</a></li>
                            <li><a class="dropdown-item" href="WomenDresses.php">Dresses</a></li>
                            <li><a class="dropdown-item" href="#">Hoodies</a></li>
                            <li><a class="dropdown-item" href="#">Jackets</a></li>
                            <li><a class="dropdown-item" href="#">Skirts</a></li>
                            <li><a class="dropdown-item" href="#">Accessories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#">Sale</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <button class="btn nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#searchSection">🔍</button>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#">Cart</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Sign In</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="collapse bg-light border-top" id="searchSection">
        <div class="container py-3">
            <form class="d-flex justify-content-center">
                <input class="form-control me-2 w-50" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </form>
        </div>
    </div>
</header>
