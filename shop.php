<?php 
require_once __DIR__ . '/config/paths.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once ROOT . '/config/db_connect.php'; 

$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$cat = isset($_GET['cat']) ? $_GET['cat'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

$sql = "SELECT p.product_id, p.name, p.price, p.image_url 
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE 1=1";

$params = [];

if (!empty($dept)) {
    $sql .= " AND c.department = ?";
    $params[] = $dept;
}

if (!empty($cat)) {
    $sql .= " AND c.name = ?";
    $params[] = $cat;
}

if ($sort == '1') {
    $sql .= " ORDER BY p.price ASC"; 
} elseif ($sort == '2') {
    $sql .= " ORDER BY p.price DESC"; 
} else {
    $sql .= " ORDER BY p.product_id DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "All Products";
if ($dept && $cat) {
    $pageTitle = "$dept's $cat";
} elseif ($dept) {
    $pageTitle = "$dept's Collection";
}
?>

<?php include ROOT . '/includes/head.php'; ?>
    <body>
        <?php include ROOT . '/includes/header.php'; ?>
        
        <main class="container my-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                
                <h2 style="font-family: 'Bebas Neue', cursive; font-size: 2.5rem; letter-spacing: 1px;">
                    <?php echo htmlspecialchars($pageTitle); ?>
                </h2>
                
                <form method="GET" action="">
                    <?php if ($dept): ?><input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>"><?php endif; ?>
                    <?php if ($cat): ?><input type="hidden" name="cat" value="<?php echo htmlspecialchars($cat); ?>"><?php endif; ?>
                    
                    <select name="sort" class="form-select w-auto" aria-label="Sort products" onchange="this.form.submit()">
                        <option value=""  <?php echo $sort == '' ? 'selected' : ''; ?>>Sort by: Featured</option>
                        <option value="1" <?php echo $sort == '1' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="2" <?php echo $sort == '2' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </form>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $item): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0">
                                <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="...">
                                </a>
                                <div class="card-body text-center d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title fs-6" style="font-family: 'Jost', sans-serif;"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="card-text fw-bold mb-3">$<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="btn btn-outline-dark w-100 rounded-0 mt-auto">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted fs-5">No products found in this category right now.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include ROOT . '/includes/footer.php'; ?>
    </body>
</html>