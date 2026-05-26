<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Laporan.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/status.php';

require_role('masyarakat');

$user = current_user();

$laporanModel = new Laporan($pdo);
$laporanList = $laporanModel->findByUser($user['id_user']);

require_once __DIR__ . '/layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Monitoring Progress</p>
                <h2 class="fw-bold mb-2">Riwayat Laporan Saya</h2>
                <p class="text-muted mb-0">
                    Pantau status laporan parkir liar yang sudah kamu kirim.
                </p>
            </div>
            <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary">
                Buat Laporan Baru
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <?php if (count($laporanList) === 0): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    <h5 class="fw-bold">Belum Ada Laporan</h5>
                    <p class="text-muted">
                        Kamu belum pernah mengirim laporan parkir liar.
                    </p>
                    <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary">
                        Buat Laporan Pertama
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($laporanList as $laporan): ?>
                    <div class="col-lg-6">
                        <div class="card report-card shadow-sm h-100">
                            <div class="report-image">
                                <img 
                                    src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" 
                                    alt="Foto laporan"
                                >
                            </div>

                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="fw-bold mb-0">
                                        <?= e($laporan['judul']); ?>
                                    </h5>

                                    <span class="badge rounded-pill bg-light text-dark border">
                                        <?= e(ucfirst($laporan['status'])); ?>
                                    </span>
                                </div>

                                <p class="text-muted small mb-3">
                                    Dikirim pada <?= date('d M Y H:i', strtotime($laporan['created_at'])); ?>
                                </p>

                                <p class="text-muted">
                                    <?= e(mb_strimwidth($laporan['deskripsi'], 0, 120, '...')); ?>
                                </p>

                                <div class="mb-3">
                                    <?= render_status_stepper($laporan['status']); ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <a 
                                        href="<?= url('detail-laporan.php?id=' . $laporan['id_laporan']); ?>" 
                                        class="btn btn-outline-primary w-100"
                                    >
                                        Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>