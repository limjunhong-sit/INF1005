<?php require_once __DIR__ . '/config/paths.php'; ?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT . '/includes/head.php'; ?>
    <body>
        <?php include ROOT . '/includes/header.php'; ?>
        <main>
            <section class="container py-5">
                <h2 class="text-center mb-4" style="font-family: 'Bebas Neue', serif;">Create Account</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form action="auth/process_register.php" method="POST" class="border rounded p-4 bg-light">
                            <div class="mb-3">
                                <label for="fname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="fname" name="fname" required>
                            </div>
                            <div class="mb-3">
                                <label for="lname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lname" name="lname" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="pwd" class="form-label">Password</label>
                                <input type="password" class="form-control" id="pwd" name="pwd" minlength="8" required>
                            </div>
                            <div class="mb-3">
                                <label for="pwd_confirm" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="pwd_confirm" name="pwd_confirm" minlength="8" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Register</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
        <?php include ROOT . '/includes/footer.php'; ?>
    </body>
</html>
