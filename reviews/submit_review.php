<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

require_once __DIR__ . '/../config/paths.php';
require_once ROOT . '/config/db_connect.php';
require_once ROOT . '/includes/csrf.php';

function redirect_back($productId, $params = []) {
    $base = '/product_details.php?id=' . urlencode((string)$productId) . '#reviews';
    if (!empty($params)) {
        $base = '/product_details.php?id=' . urlencode((string)$productId) . '&' . http_build_query($params) . '#reviews';
    }
    header('Location: ' . $base);
    exit;
}

function safe_query_message($msg) {
    // Keep querystring messages short and non-sensitive
    $msg = trim((string)$msg);
    if ($msg === '') return '';
    if (mb_strlen($msg) > 140) $msg = mb_substr($msg, 0, 140);
    return $msg;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Invalid request.');
}

if (!isset($_SESSION['user_id'])) {
    $pid = (int)($_POST['product_id'] ?? 0);
    header('Location: /signin.php?redirect=' . urlencode('/product_details.php?id=' . $pid . '#reviews'));
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    redirect_back((int)($_POST['product_id'] ?? 0), ['review_err' => 'Admins cannot submit reviews.']);
}

$userId = (int)$_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$body = trim((string)($_POST['body'] ?? ''));
$isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;

if ($productId <= 0) {
    header('Location: /index.php');
    exit;
}

if ($rating < 1 || $rating > 5) {
    redirect_back($productId, ['review_err' => safe_query_message('Please select a rating (1–5).')]);
}

if ($body === '' || mb_strlen($body) < 10) {
    redirect_back($productId, ['review_err' => safe_query_message('Review text must be at least 10 characters.')]);
}

if (mb_strlen($body) > 2000) {
    redirect_back($productId, ['review_err' => safe_query_message('Review text is too long (max 2000 characters).')]);
}

// Verified purchase check: paid order containing product
try {
    // Ensure product exists
    $stmt = $pdo->prepare("SELECT 1 FROM products WHERE product_id = ? LIMIT 1");
    $stmt->execute([$productId]);
    if (!$stmt->fetchColumn()) {
        redirect_back($productId, ['review_err' => safe_query_message('Product not found.')]);
    }

    $stmt = $pdo->prepare("
        SELECT 1
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = ?
          AND o.status = 'paid'
          AND oi.product_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $productId]);
    if (!$stmt->fetchColumn()) {
        redirect_back($productId, ['review_err' => safe_query_message('Only verified purchases can leave a review.')]);
    }

    // One review per user per product (enforced here; DB unique is recommended)
    $stmt = $pdo->prepare("SELECT 1 FROM product_reviews WHERE product_id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$productId, $userId]);
    if ($stmt->fetchColumn()) {
        redirect_back($productId, ['review_err' => safe_query_message('You have already reviewed this product.')]);
    }
} catch (PDOException $e) {
    error_log('Review eligibility check error: ' . $e->getMessage());
    redirect_back($productId, ['review_err' => safe_query_message('An unexpected error occurred. Please try again later.')]);
}

// Upload settings
$maxImages = 3;
$maxBytesPerImage = 2 * 1024 * 1024; // 2MB
$allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

$uploadDir = ROOT . '/uploads/reviews/';
$relativeUploadDir = 'uploads/reviews/';

if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        error_log('Review upload mkdir failed for: ' . $uploadDir);
        redirect_back($productId, ['review_err' => safe_query_message('Server cannot create uploads folder. Please contact support.')]);
    }
}

if (!is_writable($uploadDir)) {
    error_log('Review upload dir not writable: ' . $uploadDir);
    redirect_back($productId, ['review_err' => safe_query_message('Uploads folder is not writable. Please fix folder permissions.')]);
}

function normalize_files_array($files) {
    $normalized = [];
    if (!isset($files['name']) || !is_array($files['name'])) return $normalized;
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $normalized[] = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $normalized;
}

$images = [];
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
    $images = normalize_files_array($_FILES['images']);
    // Remove empty slots
    $images = array_values(array_filter($images, function($f) {
        return !empty($f['name']) && (int)$f['error'] !== UPLOAD_ERR_NO_FILE;
    }));
}

if (count($images) > $maxImages) {
    redirect_back($productId, ['review_err' => safe_query_message("You can upload up to {$maxImages} images.")]);
}

// Insert review + images in a transaction
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO product_reviews (product_id, user_id, rating, body, is_anonymous, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$productId, $userId, $rating, $body, $isAnonymous]);
    $reviewId = (int)$pdo->lastInsertId();

    if (!empty($images)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $stmtImg = $pdo->prepare("INSERT INTO product_review_images (review_id, image_url, created_at) VALUES (?, ?, NOW())");
        $savedImages = 0;
        $firstError = '';

        foreach ($images as $img) {
            $err = (int)($img['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err !== UPLOAD_ERR_OK) {
                if ($firstError === '') {
                    // Map common upload errors to user-friendly text
                    switch ($err) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $firstError = 'One of your images is too large.';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $firstError = 'Image upload was interrupted. Please try again.';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $firstError = 'Server missing a temp folder for uploads.';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $firstError = 'Server cannot write uploaded files.';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $firstError = 'Upload blocked by a server extension.';
                            break;
                        default:
                            $firstError = 'Image upload failed. Please try again.';
                            break;
                    }
                }
                continue;
            }
            if ((int)$img['size'] <= 0 || (int)$img['size'] > $maxBytesPerImage) {
                if ($firstError === '') $firstError = 'One of your images is too large (max 2MB each).';
                continue;
            }
            if (!is_uploaded_file($img['tmp_name'])) {
                if ($firstError === '') $firstError = 'Image upload failed. Please try again.';
                continue;
            }

            $originalName = basename((string)$img['name']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                if ($firstError === '') $firstError = 'Only JPG, PNG, or WEBP images are allowed.';
                continue;
            }

            $mime = $finfo->file($img['tmp_name']);
            if (!in_array($mime, $allowedMimes, true)) {
                if ($firstError === '') $firstError = 'Only JPG, PNG, or WEBP images are allowed.';
                continue;
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $filename = $safeBase . '_' . $reviewId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . $filename;

            if (!move_uploaded_file($img['tmp_name'], $targetPath)) {
                $lastErr = error_get_last();
                error_log('Review image move failed. tmp=' . ($img['tmp_name'] ?? '') . ' target=' . $targetPath . ' err=' . json_encode($lastErr));
                if ($firstError === '') $firstError = 'Server could not save your image. Please try again.';
                continue;
            }

            $stmtImg->execute([$reviewId, $relativeUploadDir . $filename]);
            $savedImages++;
        }

        // If user selected images but none saved, surface a helpful error
        if ($savedImages === 0) {
            // Keep the review itself, but tell user why images didn't attach
            if ($firstError === '') $firstError = 'Could not attach images. Please try again.';
            $pdo->commit();
            redirect_back($productId, ['review_ok' => safe_query_message('Review submitted.'), 'review_err' => safe_query_message($firstError)]);
        }
    }

    $pdo->commit();
    redirect_back($productId, ['review_ok' => safe_query_message('Review submitted. Thank you!')]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Submit review error: ' . $e->getMessage());
    redirect_back($productId, ['review_err' => safe_query_message('Failed to submit review. Please try again.')]);
}

