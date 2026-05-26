<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';
require_once __DIR__ . '/../app/helpers/auth.php';

require_login();

$user = current_user();

if ($user['role'] === 'admin') {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$statusCounts = [
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

$stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM laporan WHERE id_user = :id_user GROUP BY status");
$stmt->execute([
    ':id_user' => $user['id_user']
]);

foreach ($stmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = $row['total'];
}

$totalLaporan = array_sum($statusCounts);

require_once __DIR__ . '/layouts/header.php';

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
                <p class="text-primary fw-semibold mb-1">Dashboard Masyarakat</p>
                <h2 class="fw-bold mb-2">Halo, <?= e($user['nama']); ?></h2>
                <p class="text-muted mb-0">
                    Pantau laporan parkir liar yang sudah kamu kirim dan buat laporan baru dengan bukti foto serta
                    lokasi GPS.
                </p>
            </div>

            <div class="mt-3 mt-md-0">
                <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary btn-lg">
                    Buat Laporan
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total Laporan</p>
                        <h3 class="fw-bold mb-0"><?= $totalLaporan; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Sedang Diproses</p>
                        <h3 class="fw-bold mb-0"><?= $statusCounts['diproses']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Selesai</p>
                        <h3 class="fw-bold mb-0"><?= $statusCounts['selesai']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Progress Laporan</h5>

                <div class="row g-3">
                    <div class="col-md">
                        <div class="mini-status">
                            <span>Menunggu</span>
                            <strong><?= $statusCounts['menunggu']; ?></strong>
                        </div>
                    </div>

                    <div class="col-md">
                        <div class="mini-status">
                            <span>Diverifikasi</span>
                            <strong><?= $statusCounts['diverifikasi']; ?></strong>
                        </div>
                    </div>

                    <div class="col-md">
                        <div class="mini-status">
                            <span>Diproses</span>
                            <strong><?= $statusCounts['diproses']; ?></strong>
                        </div>
                    </div>

                    <div class="col-md">
                        <div class="mini-status">
                            <span>Selesai</span>
                            <strong><?= $statusCounts['selesai']; ?></strong>
                        </div>
                    </div>

                    <div class="col-md">
                        <div class="mini-status danger">
                            <span>Ditolak</span>
                            <strong><?= $statusCounts['ditolak']; ?></strong>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap mt-4">
                    <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary">
                        Buat Laporan
                    </a>

                    <a href="<?= url('riwayat-laporan.php'); ?>" class="btn btn-outline-primary">
                        Lihat Riwayat
                    </a>

                    <a href="<?= url('peta-laporan.php'); ?>" class="btn btn-outline-primary">
                        Peta Saya
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>