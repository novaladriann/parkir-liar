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
$idLaporan = $_GET['id'] ?? null;

if (!$idLaporan || !ctype_digit($idLaporan)) {
    set_flash('error', 'Laporan tidak valid.');
    header('Location: ' . url('riwayat-laporan.php'));
    exit;
}

$laporanModel = new Laporan($pdo);
$laporan = $laporanModel->findByIdAndUser($idLaporan, $user['id_user']);

if (!$laporan) {
    set_flash('error', 'Laporan tidak ditemukan.');
    header('Location: ' . url('riwayat-laporan.php'));
    exit;
}

require_once __DIR__ . '/layouts/header.php';

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Detail Laporan</p>
                <h2 class="fw-bold mb-2"><?= e($laporan['judul']); ?></h2>
                <p class="text-muted mb-0">
                    Dikirim pada <?= date('d M Y H:i', strtotime($laporan['created_at'])); ?>
                </p>
            </div>
            <a href="<?= url('riwayat-laporan.php'); ?>" class="btn btn-outline-primary">
                Kembali
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="report-detail-image">
                        <img 
                            src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" 
                            alt="Foto laporan"
                        >
                    </div>

                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Deskripsi Laporan</h5>
                        <p class="text-muted mb-0">
                            <?= nl2br(e($laporan['deskripsi'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Progress Laporan</h5>

                        <?= render_status_stepper($laporan['status']); ?>

                        <?php if ($laporan['catatan_admin']): ?>
                            <div class="alert alert-info mt-4 mb-0">
                                <strong>Catatan Admin:</strong><br>
                                <?= nl2br(e($laporan['catatan_admin'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Informasi Lokasi</h5>

                        <div class="info-list">
                            <div>
                                <span>Status</span>
                                <strong><?= e(ucfirst($laporan['status'])); ?></strong>
                            </div>

                            <div>
                                <span>Latitude</span>
                                <strong><?= e($laporan['latitude']); ?></strong>
                            </div>

                            <div>
                                <span>Longitude</span>
                                <strong><?= e($laporan['longitude']); ?></strong>
                            </div>

                            <div>
                                <span>Alamat / Patokan</span>
                                <strong><?= e($laporan['alamat'] ?: '-'); ?></strong>
                            </div>
                        </div>

                        <a 
                            href="https://www.google.com/maps?q=<?= e($laporan['latitude']); ?>,<?= e($laporan['longitude']); ?>" 
                            target="_blank"
                            class="btn btn-primary w-100 mt-4"
                        >
                            Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>