<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/ProfileController.php';

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        ProfileController::updateProfile();
    }

    if ($action === 'update_password') {
        ProfileController::updatePassword();
    }

    set_flash('error', 'Aksi tidak valid.');
    header('Location: ' . url('profil.php'));
    exit;
}

$userModel = new User($pdo);
$userData = $userModel->findById($user['id_user']);

require_once __DIR__ . '/layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Pengaturan Akun</p>
                <h2 class="fw-bold mb-2">Profil Saya</h2>
                <p class="text-muted mb-0">
                    Kelola informasi akun dan keamanan password.
                </p>
            </div>

            <a 
                href="<?= $user['role'] === 'admin' ? url('admin/dashboard.php') : url('dashboard.php'); ?>" 
                class="btn btn-outline-primary"
            >
                Kembali ke Dashboard
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm profile-card">
                    <div class="card-body p-4 text-center">
                        <div class="profile-avatar mx-auto mb-3">
                            <?= strtoupper(substr($userData['nama'], 0, 1)); ?>
                        </div>

                        <h4 class="fw-bold mb-1"><?= e($userData['nama']); ?></h4>
                        <p class="text-muted mb-3"><?= e($userData['email']); ?></p>

                        <span class="profile-role-badge">
                            <?= e(ucfirst($userData['role'])); ?>
                        </span>

                        <hr class="my-4">

                        <div class="profile-info-list">
                            <div>
                                <span>Role</span>
                                <strong><?= e(ucfirst($userData['role'])); ?></strong>
                            </div>

                            <div>
                                <span>Tanggal Daftar</span>
                                <strong><?= date('d M Y', strtotime($userData['created_at'])); ?></strong>
                            </div>

                            <div>
                                <span>Status Akun</span>
                                <strong>Aktif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1">Informasi Profil</h5>
                        <p class="text-muted mb-4">
                            Perbarui nama dan email akun kamu.
                        </p>

                        <form method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input 
                                    type="text" 
                                    name="nama" 
                                    class="form-control form-control-lg"
                                    value="<?= e($userData['nama']); ?>"
                                    required
                                >
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Email</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    class="form-control form-control-lg"
                                    value="<?= e($userData['email']); ?>"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Simpan Perubahan Profil
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1">Keamanan Password</h5>
                        <p class="text-muted mb-4">
                            Gunakan password yang kuat dan tidak mudah ditebak.
                        </p>

                        <form method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="update_password">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password Lama</label>
                                <input 
                                    type="password" 
                                    name="password_lama" 
                                    class="form-control form-control-lg"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password Baru</label>
                                <input 
                                    type="password" 
                                    name="password_baru" 
                                    class="form-control form-control-lg"
                                    minlength="6"
                                    required
                                >
                                <small class="text-muted">
                                    Minimal 6 karakter.
                                </small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                <input 
                                    type="password" 
                                    name="password_konfirmasi" 
                                    class="form-control form-control-lg"
                                    minlength="6"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Ganti Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>