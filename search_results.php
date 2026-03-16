<?php
require_once __DIR__ . '/config/paths.php';
require_once ROOT . '/config/db_connect.php';

$user_search = isset($_GET['query']) ? htmlspecialchars(trim($_GET['query']), ENT_QUOTES, 'UTF-8') : '';
$products = [];

if ($user_search !== '') {
    $term = "%" . strtolower($user_search) . "%";
    
    $sql = "SELECT * FROM products WHERE lower(name) LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$term]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include ROOT . '/includes/head.php'; ?>
<body>
    <?php include ROOT . '/includes/header.php'; ?>

    <main class="container my-5">
        <div class="mb-5">
            <h2 class="fw-bold">Search Results</h2>
            <p class="text-muted">Showing results for: <strong>"<?php echo htmlspecialchars($user_search); ?>"</strong></p>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $item): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0">
                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>" style="object-fit: cover; height: 300px; background-color: #f8f9fa;">
                            </a>
                            
                            <div class="card-body text-center">
                                <h5 class="card-title fs-6 text-dark"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="card-text fw-bold">$<?php echo number_format($item['price'], 2); ?></p>
                                
                                <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="btn btn-outline-dark w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No products found</h4>
                    <p class="text-muted">Try searching for something else, like "Tee" or "Hoodie".</p>
                    <a href="index.php" class="btn btn-dark mt-3">Continue Shopping</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>
</body>
</html>