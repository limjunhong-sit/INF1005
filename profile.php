<?php
session_start();
require_once __DIR__ . '/config/paths.php';
require_once ROOT . '/config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /signin.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$user = null;

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, role, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Profile error: " . $e->getMessage());
}

if (!$user) {
    session_destroy();
    header("Location: /signin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT . '/includes/head.php'; ?>
<body>
    <?php include ROOT . '/includes/header.php'; ?>

    <main class="section" style="min-height: 80vh; display: flex; align-items: center; padding-top: 80px;">
        <div class="container d-flex justify-content-center">
            
            <div class="contact-card shadow-lg p-5" style="max-width: 450px; width: 100%;">
                
                <div class="text-center mb-4">
                    <?php 
                        $fName = trim($user['first_name'] ?? '');
                        $lName = trim($user['last_name'] ?? '');
                        $avatarTarget = !empty($fName) ? $fName : (!empty($lName) ? $lName : 'U');
                    ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($avatarTarget) ?>&background=9B744A&color=fff&size=100&rounded=true&bold=true&length=1" 
                         alt="Profile" 
                         class="rounded-circle shadow"
                         style="border: 4px solid var(--cream);">
                </div>

                <div class="text-center mb-4">
                    <h1 class="story-heading" style="font-size: 2.2rem; line-height: 1.2; margin-bottom: 8px;">
                        <?= htmlspecialchars(ucwords($user['first_name'] . ' ' . $user['last_name'])) ?>
                    </h1>
                    <p class="contact-body text-uppercase" style="letter-spacing: 2px; font-size: 0.8rem; margin-top: 0; color: var(--charcoal);">
                        <?= htmlspecialchars($user['role'] === 'admin' ? 'Administrator' : 'Member') ?>
                    </p>
                </div>

                <div class="border-top border-secondary pt-4 mb-4">
                    <div class="mb-3">
                        <label class="contact-body d-block text-muted mb-1" style="font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase;">Email Address</label>
                        <p class="stat-number m-0" style="font-size: 1.2rem;"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="contact-body d-block text-muted mb-1" style="font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase;">Member Since</label>
                        <p class="stat-number m-0" style="font-size: 1.2rem;">
                            <?= date('F j, Y', strtotime($user['created_at'] ?? 'now')) ?>
                        </p>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <?php if ($user['role'] !== 'admin'): ?>
                        <a href="/cart/purchase_history.php" class="btn btn-dark py-2">View Order History</a>
                    <?php endif; ?>
                    <a href="/auth/logout.php" class="btn btn-signout py-2" style="color: #a31621; border-color: #a31621;">Sign Out</a>
                </div>

            </div>
        </div>
    </main>

    <?php include ROOT . '/includes/footer.php'; ?>
</body>
</html>