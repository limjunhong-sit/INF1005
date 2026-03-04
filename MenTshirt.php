<?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main class="container my-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Men's T-Shirts</h2>
                <select class="form-select w-auto" aria-label="Sort products">
                    <option selected>Sort by: Featured</option>
                    <option value="1">Price: Low to High</option>
                    <option value="2">Price: High to Low</option>
                    <option value="3">Newest Arrivals</option>
                </select>
            </div>
        </main>
        <?php include 'footer.php'; ?>
    </body>
</html>