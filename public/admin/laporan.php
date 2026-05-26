<?php

require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Laporan.php';
require_once __DIR__ . '/../../app/helpers/url.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_role('admin');

$allowedStatus = [
    'menunggu',
    'diverifikasi',
    'diproses',
    'selesai',
    'ditolak'
];

$statusFilter = $_GET['status'] ?? null;

if ($statusFilter && !in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = null;
}

$laporanModel = new Laporan($pdo);
$laporanList = $laporanModel->findAllWithUser($statusFilter);

require_once __DIR__ . '/../layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

function badge_status($status)
{
    return 'status-badge status-' . $status;
}

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Admin Panel</p>
                <h2 class="fw-bold mb-2">Kelola Laporan Parkir Liar</h2>
                <p class="text-muted mb-0">
                    Verifikasi laporan masyarakat dan pantau status penanganannya.
                </p>
            </div>
            <a href="<?= url('admin/dashboard.php'); ?>" class="btn btn-outline-primary">
                Dashboard
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Filter Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <?php foreach ($allowedStatus as $status): ?>
                                <option value="<?= e($status); ?>" <?= $statusFilter === $status ? 'selected' : ''; ?>>
                                    <?= e(ucfirst($status)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            Terapkan Filter
                        </button>
                        <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if (count($laporanList) === 0): ?>
                    <div class="text-center p-5">
                        <h5 class="fw-bold">Belum Ada Laporan</h5>
                        <p class="text-muted mb-0">Data laporan belum tersedia.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Pelapor</th>
                                    <th>Laporan</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($laporanList as $laporan): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($laporan['nama']); ?></strong><br>
                                            <small class="text-muted"><?= e($laporan['email']); ?></small>
                                        </td>

                                        <td>
                                            <strong><?= e($laporan['judul']); ?></strong><br>
                                            <small class="text-muted">
                                                <?= e(mb_strimwidth($laporan['deskripsi'], 0, 70, '...')); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <small class="text-muted">
                                                <?= e($laporan['latitude']); ?>,<br>
                                                <?= e($laporan['longitude']); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <span class="<?= badge_status($laporan['status']); ?>">
                                                <?= e(ucfirst($laporan['status'])); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= date('d M Y', strtotime($laporan['created_at'])); ?><br>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($laporan['created_at'])); ?>
                                            </small>
                                        </td>

                                        <td class="text-end">
                                            <a 
                                                href="<?= url('admin/detail-laporan.php?id=' . $laporan['id_laporan']); ?>" 
                                                class="btn btn-outline-primary btn-sm"
                                            >
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>