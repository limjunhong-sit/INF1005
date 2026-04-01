<?php
session_start();
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/includes/csrf.php';
$redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT . '/includes/head.php'; ?>
    <body>
        <?php include ROOT . '/includes/header.php'; ?>
        <main>
            <section class="container py-5">
                <h1 class="text-center mb-4" style="font-family: 'Bebas Neue', serif;">Sign In</h1>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'signed_out'): ?>
                            <div class="alert alert-warning alert-dismissible fade show shadow-sm mb-4" role="alert">
                                <strong>Session Expired!</strong> You have been signed out because your account was accessed from another device.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <form action="/auth/process_signin.php" method="POST" class="border rounded p-4 bg-light shadow-sm">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <?php if ($redirect): ?><input type="hidden" name="redirect" value="<?= $redirect ?>"><?php endif; ?>
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