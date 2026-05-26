<?php

require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Laporan.php';
require_once __DIR__ . '/../../app/helpers/url.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_role('admin');

$laporanModel = new Laporan($pdo);

$totalLaporan = (int) $pdo->query("SELECT COUNT(*) AS total FROM laporan")->fetch()['total'];
$totalUser = (int) $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'masyarakat'")->fetch()['total'];

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

$totalBelumSelesai = $statusCounts['menunggu'] + $statusCounts['diverifikasi'] + $statusCounts['diproses'];
$completionRate = $totalLaporan > 0 ? round(($statusCounts['selesai'] / $totalLaporan) * 100) : 0;

$latestReports = $laporanModel->latestReports(5);
$pendingReports = array_slice($laporanModel->findAllWithUser('menunggu'), 0, 5);
$mapReports = array_slice($laporanModel->findForMap(), 0, 30);

$mapData = [];

foreach ($mapReports as $laporan) {
    $mapData[] = [
        'judul' => $laporan['judul'],
        'status' => $laporan['status'],
        'latitude' => (float) $laporan['latitude'],
        'longitude' => (float) $laporan['longitude'],
        'detail_url' => url('admin/detail-laporan.php?id=' . $laporan['id_laporan'])
    ];
}

function dashboard_badge_status($status)
{
    return 'status-badge status-' . $status;
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

        <div class="dashboard-admin-hero mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Dashboard Admin</p>
                <h2 class="fw-bold mb-2">Pusat Monitoring Parkir Liar</h2>
                <p class="text-muted mb-0">
                    Pantau laporan masyarakat, titik pelanggaran, status penanganan, dan statistik pelaporan dalam satu
                    halaman.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap mt-3 mt-lg-0">
                <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-primary">
                    Kelola Laporan
                </a>

                <a href="<?= url('admin/peta.php'); ?>" class="btn btn-outline-primary">
                    Lihat Peta
                </a>

                <a href="<?= url('admin/cetak-laporan.php'); ?>" target="_blank" class="btn btn-outline-primary">
                    Cetak Rekap
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-primary-soft text-primary">01</div>
                        <p class="text-muted mb-1 mt-3">Total Laporan</p>
                        <h3 class="fw-bold mb-0"><?= $totalLaporan; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-warning-soft text-warning">02</div>
                        <p class="text-muted mb-1 mt-3">Menunggu Verifikasi</p>
                        <h3 class="fw-bold mb-0"><?= $statusCounts['menunggu']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-success-soft text-success">03</div>
                        <p class="text-muted mb-1 mt-3">Selesai Ditangani</p>
                        <h3 class="fw-bold mb-0"><?= $statusCounts['selesai']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-info-soft text-info">04</div>
                        <p class="text-muted mb-1 mt-3">Masyarakat Terdaftar</p>
                        <h3 class="fw-bold mb-0"><?= $totalUser; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Peta Ringkas Laporan</h5>
                                <p class="text-muted small mb-0">
                                    Sebaran titik laporan terbaru berdasarkan GPS masyarakat.
                                </p>
                            </div>

                            <a href="<?= url('admin/peta.php'); ?>" class="btn btn-outline-primary btn-sm">
                                Buka Peta Besar
                            </a>
                        </div>

                        <div id="dashboardMiniMap" class="dashboard-mini-map"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1">Ringkasan Status</h5>
                        <p class="text-muted small mb-3">
                            Komposisi laporan berdasarkan status terbaru.
                        </p>

                        <div class="dashboard-chart-box mb-3">
                            <canvas id="dashboardStatusChart"></canvas>
                        </div>

                        <div class="completion-box">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Penyelesaian</span>
                                <strong><?= $completionRate; ?>%</strong>
                            </div>

                            <div class="progress modern-progress">
                                <div class="progress-bar" style="width: <?= $completionRate; ?>%"></div>
                            </div>

                            <small class="text-muted d-block mt-2">
                                <?= $statusCounts['selesai']; ?> dari <?= $totalLaporan; ?> laporan selesai.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <a href="<?= url('admin/laporan.php?status=menunggu'); ?>" class="text-decoration-none">
                    <div class="card action-card shadow-sm h-100">
                        <div class="card-body p-4">
                            <span class="action-label bg-warning-soft text-warning">Prioritas</span>
                            <h5 class="fw-bold mt-3 mb-2">Verifikasi Laporan</h5>
                            <p class="text-muted mb-0">
                                Cek laporan baru yang masih menunggu validasi admin.
                            </p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-4">
                <a href="<?= url('admin/peta.php'); ?>" class="text-decoration-none">
                    <div class="card action-card shadow-sm h-100">
                        <div class="card-body p-4">
                            <span class="action-label bg-primary-soft text-primary">Monitoring</span>
                            <h5 class="fw-bold mt-3 mb-2">Pantau Peta</h5>
                            <p class="text-muted mb-0">
                                Lihat sebaran titik pelanggaran parkir liar secara visual.
                            </p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-4">
                <a href="<?= url('admin/grafik.php'); ?>" class="text-decoration-none">
                    <div class="card action-card shadow-sm h-100">
                        <div class="card-body p-4">
                            <span class="action-label bg-success-soft text-success">Evaluasi</span>
                            <h5 class="fw-bold mt-3 mb-2">Lihat Grafik</h5>
                            <p class="text-muted mb-0">
                                Analisis jumlah laporan berdasarkan status dan bulan.
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Laporan Terbaru</h5>
                                <p class="text-muted small mb-0">
                                    Aktivitas laporan terbaru dari masyarakat.
                                </p>
                            </div>

                            <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-outline-primary btn-sm">
                                Semua
                            </a>
                        </div>

                        <?php if (count($latestReports) === 0): ?>
                            <div class="alert alert-info mb-0">
                                Belum ada laporan masuk.
                            </div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($latestReports as $laporan): ?>
                                    <div class="activity-item">
                                        <div>
                                            <strong><?= e($laporan['judul']); ?></strong>
                                            <p class="text-muted mb-1">
                                                <?= e($laporan['nama']); ?> •
                                                <?= date('d M Y H:i', strtotime($laporan['created_at'])); ?>
                                            </p>
                                            <span class="<?= dashboard_badge_status($laporan['status']); ?>">
                                                <?= e(ucfirst($laporan['status'])); ?>
                                            </span>
                                        </div>

                                        <a href="<?= url('admin/detail-laporan.php?id=' . $laporan['id_laporan']); ?>"
                                            class="btn btn-outline-primary btn-sm">
                                            Detail
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Butuh Verifikasi</h5>
                                <p class="text-muted small mb-0">
                                    Laporan yang masih berstatus menunggu.
                                </p>
                            </div>

                            <span class="badge bg-warning text-dark rounded-pill">
                                <?= count($pendingReports); ?> item
                            </span>
                        </div>

                        <?php if (count($pendingReports) === 0): ?>
                            <div class="empty-state">
                                <strong>Tidak ada antrean</strong>
                                <p class="text-muted mb-0">
                                    Semua laporan baru sudah diverifikasi.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="pending-list">
                                <?php foreach ($pendingReports as $laporan): ?>
                                    <a href="<?= url('admin/detail-laporan.php?id=' . $laporan['id_laporan']); ?>"
                                        class="pending-item">
                                        <div>
                                            <strong><?= e($laporan['judul']); ?></strong>
                                            <span><?= e($laporan['nama']); ?> •
                                                <?= date('d M Y', strtotime($laporan['created_at'])); ?></span>
                                        </div>
                                        <span>›</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php

$extraScripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const statusData = ' . json_encode(array_values($statusCounts)) . ';
    const mapReports = ' . json_encode($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';

    const chartCanvas = document.getElementById("dashboardStatusChart");

    if (chartCanvas) {
        new Chart(chartCanvas, {
            type: "doughnut",
            data: {
                labels: ["Menunggu", "Diverifikasi", "Diproses", "Selesai", "Ditolak"],
                datasets: [{
                    data: statusData,
                    backgroundColor: [
                        "#f59e0b",
                        "#2563eb",
                        "#7c3aed",
                        "#16a34a",
                        "#dc2626"
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    }

    const mapElement = document.getElementById("dashboardMiniMap");

    if (mapElement) {
        const defaultLat = -6.2088;
        const defaultLng = 106.8456;

        const map = L.map("dashboardMiniMap", {
            scrollWheelZoom: false
        }).setView([defaultLat, defaultLng], 11);

        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors"
        }).addTo(map);

        function getMarkerIcon(status) {
            return L.divIcon({
                className: "",
                html: `<div class="custom-map-marker marker-${status}"></div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12],
                popupAnchor: [0, -12]
            });
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll("\"", "&quot;")
                .replaceAll("\'", "&#039;");
        }

        const bounds = [];

        mapReports.forEach(function(report) {
            if (!report.latitude || !report.longitude) {
                return;
            }

            const latLng = [report.latitude, report.longitude];
            bounds.push(latLng);

            L.marker(latLng, {
                icon: getMarkerIcon(report.status),
                title: report.judul
            })
            .addTo(map)
            .bindPopup(`
                <strong>${escapeHtml(report.judul)}</strong><br>
                <small>Status: ${escapeHtml(report.status)}</small><br>
                <a href="${escapeHtml(report.detail_url)}">Lihat detail</a>
            `);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, {
                padding: [30, 30]
            });
        }
    }
});
</script>
';

require_once __DIR__ . '/../layouts/footer.php';

?>