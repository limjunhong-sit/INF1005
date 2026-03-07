<!DOCTYPE html>
<html lang="en">
    <?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <section class="hero">
                <div class="hero-content fade-in-el visible">
                    <p class="hero-label">Our Story</p>
                    <h1>Dressed for <em>Campus Life</em></h1>
                    <div class="hero-divider"></div>
                    <p>UniClothes is a fashion brand founded in 2024, built for university students and young adults who value modern style, everyday comfort, and genuine affordability.</p>
                </div>
                <div class="hero-stats">
                    <div class="stat">
                        <div class="stat-number">2024</div>
                        <div class="stat-label">Year Founded</div>
                </div>
                    <div class="stat">
                        <div class="stat-number">SG</div>
                        <div class="stat-label">Based in Singapore</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">∞</div>
                        <div class="stat-label">Student Spirit</div>
                        </div>
                </div>
            </section>

            <section class="section">
                <h2 class="visually-hidden">Brand Story</h2>
                <span class="section-tag">Brand Story</span>
                <div class="story-grid fade-in-el">
                    <div class="story-heading">
                        Where fashion meets <span>campus culture</span>
                    </div>
                    <div class="story-body">
                        <p>UniClothes was born from a simple observation: university students deserve 
                        clothing that keeps up with their lives — from lecture halls to weekend hangouts
                        — without breaking the bank.</p>
                        <p>We offer acurated range of casual campus-style apparel including hoodies, 
                        jackets, and t-shirts designed for everyday wear. Every piece reflects the lifestyle 
                        and personality of students, both on and off campus.</p>
                        <p>Based in Singapore, we aim to grow into a trusted campus fashion brand recognised 
                        across universities throughout Southeast Asia and beyond.</p>
                    </div>
                </div>
            </section>


            <section class="mv-section">
                <h2 class="visually-hidden">What Drives Us</h2>
                <span class="section-tag">What Drives Us</span>
                <div class="mv-grid">
                    <div class="mv-card fade-in-el">
                        <div class="mv-number">01</div>
                        <div class="mv-title">Our Mission</div>
                        <p class="mv-body">To provide stylish, comfortable, and affordable clothing that supports the
                        everyday lifestyle of university students. UniClothes aims to combine fashion and practicality 
                        so students can feel confident while studying, socialising, or relaxing. Through simple and modern designs, 
                        we strive to make campus fashion accessible to everyone.</p>
                    </div>
                    <div class="mv-card fade-in-el">
                        <div class="mv-number">02</div>
                        <div class="mv-title">Our Vision</div>
                        <p class="mv-body">To become a recognised campus-inspired fashion brand that connects with students 
                        around the world. UniClothes aims to grow into a trusted brand known for quality, comfort, and modern 
                        student style. In the future, we hope to expand our reach while continuing to represent the spirit and 
                        creativity of university life.</p>
                    </div>
                </div>
                </section>

        </main>
        <?php include 'footer.php'; ?>
        <script>
            const els = document.querySelectorAll('.fade-in-el');
            const observer = new IntersectionObserver(entries => {
                entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
            }, { threshold: 0.15 });
            els.forEach(el => observer.observe(el));
        </script>
    </body>
</html>