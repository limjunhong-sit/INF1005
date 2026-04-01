<?php
require_once __DIR__ . '/config/paths.php';
require_once ROOT . '/config/db_connect.php';

try {
    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price, p.image_url, COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 4
    ");
    $stmt->execute();
    $trendingItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $trendingItems = []; // Safety fallback
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT . '/includes/head.php'; ?>
    <body>
        <?php include ROOT . '/includes/header.php'; ?>
        <main>
            <section id="home" class="position-relative mb-5" style="margin: 0;">     
                <h2 class="visually-hidden">Home</h2>   
                <img src="image/3_models.png" alt="UniClothes models" class="fade-in img-fluid w-100" style="max-height: 70vh; object-fit: cover; object-position: top;">
                
                <div class="text-center py-5 px-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <h2 class="mb-3" style="font-family: 'Bebas Neue', cursive; font-size: 3rem; letter-spacing: 2px;">
                            WELCOME BACK, <?php echo strtoupper(htmlspecialchars($_SESSION['first_name'])); ?>.
                        </h2>
                        <p style="font-family: 'Italiana', serif; font-size: 1.3rem;" class="text-muted mb-0">
                            Ready for some fresh campus style?
                        </p>
                    <?php else: ?>
                        <h2 class="mb-3" style="font-family: 'Italiana', serif; font-size: 3.5rem;">Welcome to UniClothes</h2>
                        <p style="font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; letter-spacing: 1px;" class="mb-0">
                            Style your campus life with modern fashion made for students.
                        </p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="container text-center my-5 py-5 border-top fade-in">
                <h2 style="font-family: 'Bebas Neue', cursive; font-size: 2.5rem; margin-bottom: 2rem; letter-spacing: 1px;">
                    Trending on Campus
                </h2>
                
                <div class="row row-cols-2 row-cols-md-4 g-4">
                    <?php foreach ($trendingItems as $item): ?>
                        <div class="col">
                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none text-dark">
                                <div class="card h-100 border-0 bg-transparent">
                                    <div style="overflow: hidden; border-radius: 8px;">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                            alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                            class="img-fluid w-100 product-zoom" 
                                            style="height: 350px; object-fit: cover;">
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h3 style="font-family: 'Bebas Neue'; font-size: 1.2rem; letter-spacing: 1px; margin-bottom: 5px;">
                                            <?php echo strtoupper(htmlspecialchars($item['name'])); ?>
                                        </h3>
                                        <p style="font-family: 'Bebas Neue'; font-size: 1.2rem; letter-spacing: 1px; margin-bottom: 5px;">
                                            $<?php echo number_format($item['price'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($trendingItems)): ?>
                        <?php for($i=0; $i<4; $i++): ?>
                            <div class="col">
                                <div class="bg-light" style="height: 350px; display: flex; align-items: center; justify-content: center; color: #aaa; border-radius: 8px;">
                                    Coming Soon
                                </div>
                            </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
        <?php include ROOT . '/includes/footer.php'; ?>               
        <script>
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
        </script>
        <script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>
        <df-messenger
        intent="WELCOME"
        chat-title="Uniclothes Assistant"
        agent-id="f97d8394-61ce-4cc9-a629-92e972c50010"
        language-code="en"
        ></df-messenger>
    </body>
</html>