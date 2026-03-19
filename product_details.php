<?php
require_once __DIR__ . '/config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
if (!$product_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

// Fetch variants for this product
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY size, colour");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total stock across all variants
$totalStock = 0;
foreach ($variants as $v) {
    $totalStock += (int)$v['stock_quantity'];
}

// Build unique sizes and colours from variants
$sizes = array_values(array_unique(array_filter(array_column($variants, 'size'))));
$colours = array_values(array_unique(array_filter(array_column($variants, 'colour'))));
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
                    Availability: <span class="text-success"><?php echo $totalStock; ?> in stock</span>
                </p>

                <?php if (empty($variants)): ?>
                    <p class="text-warning small mb-3">This product is currently unavailable.</p>
                <?php else: ?>
                    <?php
                    $variantMap = [];
                    foreach ($variants as $v) {
                        $key = (string)($v['size'] ?? '') . '|' . (string)($v['colour'] ?? '');
                        $variantMap[$key] = $v;
                    }
                    $defaultVariant = $variants[0];
                    ?>
                    <form action="/cart/add_to_cart.php" method="POST" id="addToCartForm">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <input type="hidden" name="variant_id" id="variantIdInput" value="<?php echo $defaultVariant['variant_id']; ?>">

                        <?php if (count($sizes) > 1): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Size</label>
                            <select name="size" id="sizeSelect" class="form-select" style="max-width: 120px;">
                                <?php foreach ($sizes as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <?php if (count($colours) > 1): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Colour</label>
                            <select name="colour" id="colourSelect" class="form-select" style="max-width: 150px;">
                                <?php foreach ($colours as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <?php include ROOT . '/includes/color.php'; ?>

                        <?php if ($isAdmin): ?>
                        <button type="button" class="btn btn-secondary btn-lg px-5 w-100 w-md-auto" disabled>
                            You are currently in an admin's account
                        </button>
                        <?php else: ?>
                        <button type="submit" class="btn btn-dark btn-lg px-5 w-100 w-md-auto" id="addToCartBtn">
                            Add to Cart
                        </button>
                        <?php endif; ?>
                    </form>
                    <?php if (!$isAdmin): ?>
                    <script>
                    (function() {
                        var variants = <?php echo json_encode($variants); ?>;
                        var variantMap = <?php echo json_encode($variantMap); ?>;
                        var sizeSelect = document.getElementById('sizeSelect');
                        var colourSelect = document.getElementById('colourSelect');
                        var variantIdInput = document.getElementById('variantIdInput');
                        var addToCartBtn = document.getElementById('addToCartBtn');

                        function getSelectedSize() { return sizeSelect ? sizeSelect.value : (variants[0] && variants[0].size) ? variants[0].size : ''; }
                        function getSelectedColour() { return colourSelect ? colourSelect.value : (variants[0] && variants[0].colour) ? variants[0].colour : ''; }

                        function updateVariantId() {
                            var key = getSelectedSize() + '|' + getSelectedColour();
                            var v = variantMap[key];
                            if (v) {
                                variantIdInput.value = v.variant_id;
                                if (addToCartBtn) addToCartBtn.disabled = parseInt(v.stock_quantity) <= 0;
                            } else {
                                if (addToCartBtn) addToCartBtn.disabled = true;
                            }
                        }

                        if (sizeSelect) sizeSelect.addEventListener('change', updateVariantId);
                        if (colourSelect) colourSelect.addEventListener('change', updateVariantId);
                        updateVariantId();
                    })();
                    </script>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>
</body>
</html>