<?php
session_start();
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/includes/csrf.php';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT . '/includes/head.php'; ?>
    <body>
        <?php include ROOT . '/includes/header.php'; ?>
        <main>
            <section class="container py-5">
                <h2 class="text-center mb-4" style="font-family: 'Bebas Neue', serif;">Sign In</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form action="auth/process_signin.php" method="POST" class="border rounded p-4 bg-light shadow-sm">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" autofocus required>
                            </div>
                            <div class="mb-3">
                                <label for="pwd" class="form-label">Password</label>
                                <input type="password" class="form-control" id="pwd" name="pwd" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 py-2">Sign In</button>

                            <div class="mt-3 text-center">
                                <p class="mb-0 text-muted small">Don't have an account? 
                                    <a href="register.php" class="text-dark fw-bold text-decoration-none">Register</a>
                                </p>
                            </div>
                        </form>
                    </div>    
                </div>
            </section>
        </main>
        <?php include ROOT . '/includes/footer.php'; ?>
    </body>
</html>