<!DOCTYPE html>
<html lang="en">
    <?php include 'head.php'; ?>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <section class="container py-5">
                <h2 class="text-center mb-4" style="font-family: 'Bebas Neue', serif;">Sign In</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form action="process_signin.php" method="POST" class="border rounded p-4 bg-light">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="pwd" class="form-label">Password</label>
                                <input type="password" class="form-control" id="pwd" name="pwd" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Sign In</button>
                            <p class="mt-3 mb-0 text-center text-muted small">Don't have an account? <a href="register.php">Register</a></p>
                        </form>
                    </div>
                </div>
            </section>
        </main>
        <?php include 'footer.php'; ?>
    </body>
</html>
