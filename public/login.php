<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    AuthController::login();
}

require_once __DIR__ . '/layouts/header.php';

$error = get_flash('error');
$success = get_flash('success');

?>

<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-80">
            <div class="col-lg-5 col-md-7">
                <div class="card auth-card shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="auth-icon bg-primary text-white mx-auto mb-3">
                                ↗
                            </div>
                            <h3 class="fw-bold mb-1">Login</h3>
                            <p class="text-muted mb-0">Masuk untuk mengakses dashboard pelaporan.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= e($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= e($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?= csrf_field(); ?>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control form-control-lg" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Login
                            </button>
                        </form>

                        <p class="text-center text-muted mt-4 mb-0">
                            Belum punya akun?
                            <a href="<?= url('register.php'); ?>" class="text-decoration-none fw-semibold">
                                Daftar sekarang
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>