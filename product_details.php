<?php
require_once __DIR__ . '/config/paths.php';
require_once ROOT . '/config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$product_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}

// Optional per-product image credits (no database changes required).
$imageCredits = require ROOT . '/config/image_credits.php';
$productNameKey = (string)($product['name'] ?? '');
$productCredit = $imageCredits[$product_id]
    ?? ($productNameKey !== '' ? ($imageCredits[$productNameKey] ?? null) : null);
$hasProductCredit = is_array($productCredit)
    && !empty($productCredit['author'])
    && !empty($productCredit['author_url'])
    && !empty($productCredit['website'])
    && !empty($productCredit['website_url']);

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

// =========================
// Reviews (summary + list)
// =========================
$reviewSummary = ['avg_rating' => 0.0, 'review_count' => 0];
$reviews = [];
$reviewImagesByReviewId = [];
$canReview = false;

try {
    // Summary
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(AVG(rating), 0) AS avg_rating,
            COUNT(*) AS review_count
        FROM product_reviews
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id]);
    $reviewSummary = $stmt->fetch(PDO::FETCH_ASSOC) ?: $reviewSummary;
    $reviewSummary['avg_rating'] = (float)($reviewSummary['avg_rating'] ?? 0);
    $reviewSummary['review_count'] = (int)($reviewSummary['review_count'] ?? 0);

    // Reviews
    $stmt = $pdo->prepare("
        SELECT
            pr.review_id,
            pr.user_id,
            pr.rating,
            pr.body,
            pr.is_anonymous,
            pr.created_at,
            u.first_name,
            u.last_name
        FROM product_reviews pr
        JOIN users u ON pr.user_id = u.user_id
        WHERE pr.product_id = ?
        ORDER BY pr.created_at DESC, pr.review_id DESC
        LIMIT 50
    ");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Images (grouped)
    if (!empty($reviews)) {
        $reviewIds = array_values(array_filter(array_map(function($r) { return (int)$r['review_id']; }, $reviews)));
        if (!empty($reviewIds)) {
            $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
            $stmt = $pdo->prepare("
                SELECT review_id, image_url
                FROM product_review_images
                WHERE review_id IN ($placeholders)
                ORDER BY image_id ASC
            ");
            $stmt->execute($reviewIds);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $rid = (int)$row['review_id'];
                if (!isset($reviewImagesByReviewId[$rid])) $reviewImagesByReviewId[$rid] = [];
                $reviewImagesByReviewId[$rid][] = (string)$row['image_url'];
            }
        }
    }

    // Eligibility (verified purchase + not admin)
    if ($userId && !$isAdmin) {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.user_id = ?
              AND o.status = 'paid'
              AND oi.product_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $product_id]);
        $hasPurchased = (bool)$stmt->fetchColumn();

        // One review per user per product (UI-level; DB unique constraint is recommended)
        $stmt = $pdo->prepare("
            SELECT 1
            FROM product_reviews
            WHERE product_id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$product_id, $userId]);
        $alreadyReviewed = (bool)$stmt->fetchColumn();

        $canReview = $hasPurchased && !$alreadyReviewed;
    }
} catch (PDOException $e) {
    error_log("Reviews load error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
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
                <?php if ($hasProductCredit): ?>
                    <p class="mt-2 mb-0 text-muted" style="font-size: 0.78rem; letter-spacing: 0.01em;">
                        Image by
                        <a href="<?php echo htmlspecialchars((string)$productCredit['author_url']); ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                            <?php echo htmlspecialchars((string)$productCredit['author']); ?>
                        </a>
                        via
                        <a href="<?php echo htmlspecialchars((string)$productCredit['website_url']); ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                            <?php echo htmlspecialchars((string)$productCredit['website']); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div> 

            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php" class="text-dark">Home</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                    </ol>
                </nav>

                <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
                <h2 class="h3 text-muted mb-4">$<?php echo number_format($product['price'], 2); ?></h2>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <?php
                        $avg = (float)($reviewSummary['avg_rating'] ?? 0);
                        $count = (int)($reviewSummary['review_count'] ?? 0);
                        $rounded = (int)round($avg);
                    ?>
                    <span class="small text-muted">Rating</span>
                    <span class="small fw-semibold"><?php echo htmlspecialchars(number_format($avg, 1)); ?></span>
                    <span class="small rating-stars" role="img" aria-label="Average rating <?php echo number_format($avg, 1); ?> out of 5">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $rounded): ?>
                                <span class="star star-filled">&#9733;</span>
                            <?php else: ?>
                                <span class="star star-empty">&#9733;</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </span>
                    <a class="small text-decoration-none review-count-link" href="#reviews">
                        (<?php echo $count; ?>)
                    </a>
                </div>
                
                <hr>
                
                <p class="lead mb-4">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </p>

                <p class="text-muted small">
                    Availability: <span class="stock-label"><?php echo $totalStock; ?> in stock</span>
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
                    $maxVariantStock = 0;
                    foreach ($variants as $v) {
                        $maxVariantStock = max($maxVariantStock, (int)($v['stock_quantity'] ?? 0));
                    }
                    ?>
                    <form action="/cart/add_to_cart.php" method="POST" id="addToCartForm">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <input type="hidden" name="variant_id" id="variantIdInput" value="<?php echo $defaultVariant['variant_id']; ?>">
                        <input type="hidden" name="colour" id="colourInput" value="<?php echo htmlspecialchars((string)($defaultVariant['colour'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

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

                        <?php include ROOT . '/includes/color.php'; ?>

                        <div class="mb-3">
                            <div class="small text-muted">
                                Stock: <span id="selectedVariantStock" class="fw-semibold">—</span>
                            </div>
                        </div>

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
                        var colourInput = document.getElementById('colourInput');
                        var variantIdInput = document.getElementById('variantIdInput');
                        var addToCartBtn = document.getElementById('addToCartBtn');
                        var selectedVariantStock = document.getElementById('selectedVariantStock');

                        function getSelectedSize() { return sizeSelect ? sizeSelect.value : (variants[0] && variants[0].size) ? variants[0].size : ''; }
                        function getSelectedColour() {
                            if (colourInput && colourInput.value) return colourInput.value;
                            var activeSwatch = document.querySelector('.swatches .swatch.active');
                            if (activeSwatch && activeSwatch.dataset && activeSwatch.dataset.name) return activeSwatch.dataset.name;
                            return (variants[0] && variants[0].colour) ? variants[0].colour : '';
                        }

                        function updateVariantId() {
                            var key = getSelectedSize() + '|' + getSelectedColour();
                            var v = variantMap[key];
                            if (v) {
                                variantIdInput.value = v.variant_id;
                                if (addToCartBtn) addToCartBtn.disabled = parseInt(v.stock_quantity) <= 0;
                                if (selectedVariantStock) selectedVariantStock.textContent = String(parseInt(v.stock_quantity) || 0);
                            } else {
                                if (addToCartBtn) addToCartBtn.disabled = true;
                                if (selectedVariantStock) selectedVariantStock.textContent = '—';
                            }
                        }

                        if (sizeSelect) sizeSelect.addEventListener('change', updateVariantId);
                        document.addEventListener('click', function(e) {
                            var swatch = e.target && e.target.classList && e.target.classList.contains('swatch') ? e.target : null;
                            if (!swatch) return;
                            var name = swatch.dataset ? swatch.dataset.name : '';
                            if (colourInput && name) colourInput.value = name;
                            updateVariantId();
                        });
                        updateVariantId();
                    })();
                    </script>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($variants)): ?>
                    <details class="mt-4 variant-stock-details">
                        <summary class="fw-semibold variant-stock-summary">View stock for all variants</summary>
                        <div class="variant-stock-card mt-2">
                            <div class="table-responsive">
                            <table class="table table-sm align-middle variant-stock-table mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Size</th>
                                        <th scope="col">Colour</th>
                                        <th scope="col">Availability</th>
                                        <th scope="col" class="text-end">Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($variants as $v): ?>
                                        <?php
                                            $vSize = (string)($v['size'] ?? '');
                                            $vColour = (string)($v['colour'] ?? '');
                                            $vStock = (int)($v['stock_quantity'] ?? 0);
                                            $ratio = $maxVariantStock > 0 ? max(0, min(1, $vStock / $maxVariantStock)) : 0;
                                            $pct = (int)round($ratio * 100);
                                            $status = 'In stock';
                                            $statusClass = 'ok';
                                            if ($vStock <= 0) { $status = 'Out of stock'; $statusClass = 'oos'; }
                                            else if ($vStock <= 3) { $status = 'Low stock'; $statusClass = 'low'; }
                                        ?>
                                        <tr class="<?php echo $vStock > 0 ? '' : 'is-oos'; ?>">
                                            <td><span class="variant-pill"><?php echo htmlspecialchars($vSize !== '' ? $vSize : '—'); ?></span></td>
                                            <td><?php echo htmlspecialchars($vColour !== '' ? $vColour : '—'); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="variant-badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($status); ?></span>
                                                    <div class="variant-stock-bar" aria-hidden="true">
                                                        <div class="variant-stock-bar__fill <?php echo htmlspecialchars($statusClass); ?>" style="width: <?php echo $pct; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="variant-stock-num"><?php echo $vStock; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <section class="mt-5 pt-4 border-top" id="reviews">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-1">Reviews</h2>
                    <p class="text-muted small mb-0">
                        <?php echo htmlspecialchars(number_format((float)$reviewSummary['avg_rating'], 1)); ?> out of 5
                        &middot;
                        <?php echo (int)$reviewSummary['review_count']; ?> review(s)
                    </p>
                </div>
            </div>

            <?php if (!empty($_GET['review_ok'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars((string)$_GET['review_ok']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($_GET['review_err'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars((string)$_GET['review_err']); ?>
                </div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="alert alert-secondary" role="alert">
                    Reviews are visible, but admins cannot submit reviews.
                </div>
            <?php endif; ?>

            <?php if (!$isAdmin): ?>
                <?php if (!$userId): ?>
                    <?php $redirect = '/product_details.php?id=' . (int)$product_id . '#reviews'; ?>
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">Leave a review</div>
                                <div class="text-muted small">Sign in to review (verified purchases only).</div>
                            </div>
                            <a class="btn btn-dark" href="/signin.php?redirect=<?php echo urlencode($redirect); ?>">Sign in</a>
                        </div>
                    </div>
                <?php elseif ($canReview): ?>
                    <?php require_once ROOT . '/includes/csrf.php'; ?>
                    <form class="border rounded p-3 bg-light" action="/reviews/submit_review.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="product_id" value="<?php echo (int)$product_id; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Your rating</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="rating<?php echo $i; ?>">
                                            <?php echo $i; ?> ★
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reviewBody" class="form-label fw-semibold">Your review</label>
                            <textarea class="form-control" id="reviewBody" name="body" rows="4" maxlength="2000" required placeholder="Share your experience (at least 10 characters)"></textarea>
                        </div>

                        <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="anonymousCheck" name="is_anonymous" value="1">
                                <label class="form-check-label" for="anonymousCheck">Post as anonymous</label>
                            </div>
                            <div class="small text-muted">Up to 3 images (JPG/PNG/WEBP, 2MB each)</div>
                        </div>

                        <div class="mb-3">
                            <label for="reviewImages" class="form-label fw-semibold">Photos (optional)</label>
                            <input class="form-control" type="file" id="reviewImages" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        </div>

                        <button type="submit" class="btn btn-dark">Submit review</button>
                    </form>
                <?php else: ?>
                    <div class="border rounded p-3 bg-light">
                        <div class="fw-semibold mb-1">Leave a review</div>
                        <div class="text-muted small">
                            Only verified purchases can review, and each customer can review a product once.
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-4">
                <?php if (empty($reviews)): ?>
                    <p class="text-muted mb-0">No reviews yet. Be the first to review this product.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($reviews as $r): ?>
                            <?php
                                $rid = (int)$r['review_id'];
                                $name = 'Anonymous';
                                if (!(int)$r['is_anonymous']) {
                                    $fn = trim((string)($r['first_name'] ?? ''));
                                    $ln = trim((string)($r['last_name'] ?? ''));
                                    $name = trim($fn . ' ' . $ln) ?: 'Customer';
                                }
                                $rRating = (int)($r['rating'] ?? 0);
                                $created = (string)($r['created_at'] ?? '');
                                $imgs = $reviewImagesByReviewId[$rid] ?? [];
                            ?>
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($name); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($created); ?></div>
                                    </div>
                                    <span class="small rating-stars" role="img" aria-label="Rating <?php echo $rRating; ?> out of 5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $rRating): ?>
                                                <span class="star star-filled">&#9733;</span>
                                            <?php else: ?>
                                                <span class="star star-empty">&#9733;</span>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <?php echo nl2br(htmlspecialchars((string)$r['body'])); ?>
                                </div>
                                <?php if (!empty($imgs)): ?>
                                    <div class="mt-3 d-flex flex-wrap gap-2">
                                        <?php foreach ($imgs as $url): ?>
                                            <?php
                                                $url = (string)$url;
                                                $publicPath = '/' . ltrim($url, '/');
                                                $cacheBuster = '';
                                                $diskPath = ROOT . '/' . ltrim($url, '/');
                                                if (is_file($diskPath)) {
                                                    $cacheBuster = '?v=' . (string)filemtime($diskPath);
                                                }
                                            ?>
                                            <a href="<?php echo htmlspecialchars($publicPath); ?>" target="_blank" rel="noopener noreferrer">
                                                <img
                                                    src="<?php echo htmlspecialchars($publicPath . $cacheBuster); ?>"
                                                    alt="Review image"
                                                    style="width: 84px; height: 84px; object-fit: cover; border-radius: 6px; border: 1px solid rgba(0,0,0,0.1);">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>
</body>
</html>