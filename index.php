<?php
require_once __DIR__ . '/config/paths.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT . '/includes/head.php'; ?>
    <body>
        <?php include ROOT . '/includes/header.php'; ?>
        <main>
            <div class="text-center py-4 bg-light border-bottom">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <h2 style="font-family: 'Bebas Neue', cursive; font-size: 2.5rem; letter-spacing: 2px;">
                        WELCOME BACK, <?php echo strtoupper(htmlspecialchars($_SESSION['first_name'])); ?>!
                    </h2>
                    <p style="font-family: 'Italiana', serif; font-size: 1.1rem;" class="text-muted">
                        Ready for some fresh campus style?
                    </p>
                <?php else: ?>
                    <h2 style="font-family: 'Italiana', serif; font-size: 2rem;">Welcome to UniClothes</h2>
                <?php endif; ?>
            </div>

            <section id="home" style='margin: 0;'>     
                <h2 class="visually-hidden">Home</h2>   
                <img src="image/3_models.png" alt="UniClothes models" class="fade-in" style="width: 100%; display: block;">
            </section>

            <div class="text-center py-4">
                <p style="font-family: 'Bebas Neue', serif; font-size: 1.2rem; color: #555;">
                    Style your campus life with UniClothes — modern fashion made for students.</p>
            </div>

            <section class="container text-center my-5">
                <h2 style="font-family: 'Bebas Neue', cursive; font-size: 2.5rem; margin-bottom: 1rem;">Shop by Category</h2>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <a href="MenTshirt.php" class="btn btn-dark w-100 py-4 fs-5">Men's Collection</a>
                    </div>
                    <div class="col-md-6">
                        <a href="WomenDresses.php" class="btn btn-outline-dark w-100 py-4 fs-5">Women's Collection</a>
                    </div>
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

        <script>
            const toggle = document.getElementById('darkToggle');
            const body = document.body;

            if (localStorage.getItem('darkMode') === 'true') {
                body.classList.add('dark-mode');
                toggle.textContent = '☀️';
            }

            toggle.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');
                toggle.textContent = isDark ? '☀️' : '🌙';
                localStorage.setItem('darkMode', isDark);
            });
        </script>

    </body>
</html>