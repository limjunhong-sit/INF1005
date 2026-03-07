<!DOCTYPE html>
<html lang="en">
    <?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main>

            <div class="text-center py-3">
                <h2 style="font-family: 'Italiana', serif; font-size: 2rem;">Welcome to UniClothes</h2>
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
        <?php include 'footer.php'; ?>

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
    </body>
</html>