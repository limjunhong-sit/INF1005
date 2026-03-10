<?php 
include 'db_connect.php'; 

$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

$sql = "SELECT p.product_id, p.name, p.price, p.image_url 
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE c.department = 'Women' AND c.name = 'Dresses'";

if ($sort == '1') {
    $sql .= " ORDER BY p.price ASC"; 
} elseif ($sort == '2') {
    $sql .= " ORDER BY p.price DESC"; 
} elseif ($sort == '3') {
    $sql .= " ORDER BY p.product_id DESC"; 
}

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        
        <main class="container my-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Men's Hoodies</h2>
                
                <form method="GET" action="">
                    <select name="sort" class="form-select w-auto" aria-label="Sort products" onchange="this.form.submit()">
                        <option value=""  <?php echo $sort == '' ? 'selected' : ''; ?>>Sort by: Featured</option>
                        <option value="1" <?php echo $sort == '1' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="2" <?php echo $sort == '2' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="3" <?php echo $sort == '3' ? 'selected' : ''; ?>>Newest Arrivals</option>
                    </select>
                </form>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $item): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>" style="object-fit: cover; height: 300px; background-color: #f8f9fa;">
                                
                                <div class="card-body text-center">
                                    <h5 class="card-title fs-6"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="card-text fw-bold">$<?php echo number_format($item['price'], 2); ?></p>
                                    
                                    <button class="btn btn-outline-dark w-100">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted">No hoodies found right now. Check back soon!</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
        
        <?php include 'footer.php'; ?>
    </body>
</html>