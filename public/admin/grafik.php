<?php

require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Laporan.php';
require_once __DIR__ . '/../../app/helpers/url.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_role('admin');

$laporanModel = new Laporan($pdo);

$statusCounts = [
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

foreach ($laporanModel->countByStatus() as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

$monthlyRows = $laporanModel->countByMonth();

$monthlyLabels = [];
$monthlyTotals = [];

foreach ($monthlyRows as $row) {
    $monthlyLabels[] = date('M Y', strtotime($row['bulan'] . '-01'));
    $monthlyTotals[] = (int) $row['total'];
}

$totalLaporan = array_sum($statusCounts);
$totalSelesai = $statusCounts['selesai'];
$totalDiproses = $statusCounts['diproses'];
$totalBelumSelesai = $statusCounts['menunggu'] + $statusCounts['diverifikasi'] + $statusCounts['diproses'];

$latestReports = $laporanModel->latestReports(6);

require_once __DIR__ . '/../layouts/header.php';

function grafik_badge_status($status)
{
    return 'status-badge status-' . $status;
}

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Grafik Pelaporan</p>
                <h2 class="fw-bold mb-2">Statistik Laporan Parkir Liar</h2>
                <p class="text-muted mb-0">
                    Analisis jumlah laporan berdasarkan status dan waktu pelaporan.
                </p>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= url('admin/dashboard.php'); ?>" class="btn btn-outline-primary">
                    Dashboard
                </a>

                <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-primary">
                    Kelola Laporan
                </a>
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
                        <p class="text-muted mb-1">Belum Selesai</p>
                        <h3 class="fw-bold mb-0"><?= $totalBelumSelesai; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Diproses</p>
                        <h3 class="fw-bold mb-0"><?= $totalDiproses; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Selesai</p>
                        <h3 class="fw-bold mb-0"><?= $totalSelesai; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Status Laporan</h5>
                                <p class="text-muted mb-0 small">
                                    Distribusi laporan berdasarkan status.
                                </p>
                            </div>
                        </div>

                        <div class="chart-box">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Laporan Per Bulan</h5>
                                <p class="text-muted mb-0 small">
                                    Tren jumlah laporan berdasarkan bulan.
                                </p>
                            </div>
                        </div>

                        <div class="chart-box chart-box-wide">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Laporan Terbaru</h5>
                        <p class="text-muted mb-0 small">
                            Daftar laporan terbaru dari masyarakat.
                        </p>
                    </div>

                    <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-outline-primary btn-sm">
                        Lihat Semua
                    </a>
                </div>

                <?php if (count($latestReports) === 0): ?>
                    <div class="alert alert-info mb-0">
                        Belum ada laporan yang masuk.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Pelapor</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($latestReports as $laporan): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($laporan['judul']); ?></strong><br>
                                            <small class="text-muted">
                                                <?= e(mb_strimwidth($laporan['deskripsi'], 0, 80, '...')); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= e($laporan['nama']); ?><br>
                                            <small class="text-muted"><?= e($laporan['email']); ?></small>
                                        </td>

                                        <td>
                                            <span class="<?= grafik_badge_status($laporan['status']); ?>">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusLabels = ['Menunggu', 'Diverifikasi', 'Diproses', 'Selesai', 'Ditolak'];
    const statusData = <?= json_encode(array_values($statusCounts)); ?>;

    const monthlyLabels = <?= json_encode($monthlyLabels); ?>;
    const monthlyData = <?= json_encode($monthlyTotals); ?>;

    const statusCtx = document.getElementById('statusChart');
    const monthlyCtx = document.getElementById('monthlyChart');

    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: [
                    '#f59e0b',
                    '#2563eb',
                    '#7c3aed',
                    '#16a34a',
                    '#dc2626'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Jumlah Laporan',
                data: monthlyData,
                backgroundColor: '#2563eb',
                borderRadius: 10,
                maxBarThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>