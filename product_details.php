<?php
require_once __DIR__ . '/config/paths.php';
require_once ROOT . '/config/db_connect.php';

$product_id = isset($_GET['id']) ? $_GET['id'] : header("Location: index.php");

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}
?>

<?php include ROOT . '/includes/head.php'; ?>
<body>
    <?php include ROOT . '/includes/header.php'; ?>

    <main class="container my-5">
        <div class="row pt-lg-5">
            <div class="col-md-6 mb-4">
                <div class="img-wrap rounded shadow-sm overflow-hidden">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         class="img-fluid" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="colour-overlay" id="productOverlay"></div>
                </div>
            </div> 

            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-dark">Home</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                    </ol>
                </nav>

                <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
                <h3 class="text-muted mb-4">$<?php echo number_format($product['price'], 2); ?></h3>
                
                <hr>
                
                <p class="lead mb-4">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </p>

                <p class="text-muted small">
                    Availability: <span class="text-success"><?php echo $product['stock_quantity']; ?> in stock</span>
                </p>

                <?php include ROOT . '/includes/color.php'; ?>

                <button class="btn btn-dark btn-lg px-5 w-100 w-md-auto">Add to Cart</button>
            </div>
        </div>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>
</body>
</html>