<?php

require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/url.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_role('admin');

$totalLaporan = $pdo->query("SELECT COUNT(*) AS total FROM laporan")->fetch()['total'];
$totalUser = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'masyarakat'")->fetch()['total'];

$statusCounts = [
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

$stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM laporan GROUP BY status");

foreach ($stmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = $row['total'];
}

require_once __DIR__ . '/../layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

?>

<section class="py-5">
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-hero mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Dashboard Admin</p>
                <h2 class="fw-bold mb-2">Monitoring Pelaporan Parkir Liar</h2>
                <p class="text-muted mb-0">
                    Kelola laporan masyarakat, pantau titik pelanggaran, dan lihat statistik pelaporan.
                </p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Laporan</p>
                        <h3 class="fw-bold mb-0"><?= $totalLaporan; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Masyarakat</p>
                        <h3 class="fw-bold mb-0"><?= $totalUser; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Diproses</p>
                        <h3 class="fw-bold mb-0"><?= $statusCounts['diproses']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Selesai</p>
                        <h3 class="fw-bold mb-0"><?= $statusCounts['selesai']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card menu-card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold">Kelola Laporan</h5>
                        <p class="text-muted">Verifikasi dan ubah status laporan masyarakat.</p>
                        <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-outline-primary">Buka</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card menu-card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold">Peta Pelanggaran</h5>
                        <p class="text-muted">Lihat titik lokasi parkir liar dari laporan valid.</p>
                        <a href="#" class="btn btn-outline-primary">Buka</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card menu-card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold">Grafik Pelaporan</h5>
                        <p class="text-muted">Pantau statistik laporan berdasarkan status dan waktu.</p>
                        <a href="#" class="btn btn-outline-primary">Buka</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4 mb-0">
            Halaman kelola laporan, peta, dan grafik akan dibuat pada tahap setelah fitur laporan masyarakat.
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>