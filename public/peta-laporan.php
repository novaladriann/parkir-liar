<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Laporan.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';
require_once __DIR__ . '/../app/helpers/auth.php';

require_role('masyarakat');

$user = current_user();

$allowedStatus = [
    'menunggu',
    'diverifikasi',
    'diproses',
    'selesai',
    'ditolak'
];

$statusFilter = $_GET['status'] ?? '';

if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = '';
}

$laporanModel = new Laporan($pdo);

$statusCounts = [
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

foreach ($laporanModel->countByStatusForUser($user['id_user']) as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

$totalLaporan = array_sum($statusCounts);

$laporanList = $laporanModel->findForUserMap(
    $user['id_user'],
    $statusFilter !== '' ? $statusFilter : null
);

$mapData = [];

foreach ($laporanList as $laporan) {
    $mapData[] = [
        'id_laporan' => (int) $laporan['id_laporan'],
        'judul' => $laporan['judul'],
        'deskripsi' => mb_strimwidth($laporan['deskripsi'], 0, 100, '...'),
        'foto' => url('assets/uploads/laporan/' . $laporan['foto']),
        'latitude' => (float) $laporan['latitude'],
        'longitude' => (float) $laporan['longitude'],
        'alamat' => $laporan['alamat'] ?: '-',
        'status' => $laporan['status'],
        'tanggal' => date('d M Y H:i', strtotime($laporan['created_at'])),
        'detail_url' => url('detail-laporan.php?id=' . $laporan['id_laporan'])
    ];
}

require_once __DIR__ . '/layouts/header.php';

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Peta Laporan Saya</p>
                <h2 class="fw-bold mb-2">Sebaran Titik Laporan</h2>
                <p class="text-muted mb-0">
                    Lihat semua titik lokasi laporan parkir liar yang sudah kamu kirim.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary">
                    Buat Laporan
                </a>

                <a href="<?= url('riwayat-laporan.php'); ?>" class="btn btn-outline-primary">
                    Riwayat
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md">
                <a 
                    href="<?= url('peta-laporan.php'); ?>" 
                    class="map-status-card <?= $statusFilter === '' ? 'active' : ''; ?>"
                >
                    <span>Semua</span>
                    <strong><?= $totalLaporan; ?></strong>
                </a>
            </div>

            <div class="col-md">
                <a 
                    href="<?= url('peta-laporan.php?status=menunggu'); ?>" 
                    class="map-status-card <?= $statusFilter === 'menunggu' ? 'active' : ''; ?>"
                >
                    <span>Menunggu</span>
                    <strong><?= $statusCounts['menunggu']; ?></strong>
                </a>
            </div>

            <div class="col-md">
                <a 
                    href="<?= url('peta-laporan.php?status=diverifikasi'); ?>" 
                    class="map-status-card <?= $statusFilter === 'diverifikasi' ? 'active' : ''; ?>"
                >
                    <span>Diverifikasi</span>
                    <strong><?= $statusCounts['diverifikasi']; ?></strong>
                </a>
            </div>

            <div class="col-md">
                <a 
                    href="<?= url('peta-laporan.php?status=diproses'); ?>" 
                    class="map-status-card <?= $statusFilter === 'diproses' ? 'active' : ''; ?>"
                >
                    <span>Diproses</span>
                    <strong><?= $statusCounts['diproses']; ?></strong>
                </a>
            </div>

            <div class="col-md">
                <a 
                    href="<?= url('peta-laporan.php?status=selesai'); ?>" 
                    class="map-status-card <?= $statusFilter === 'selesai' ? 'active' : ''; ?>"
                >
                    <span>Selesai</span>
                    <strong><?= $statusCounts['selesai']; ?></strong>
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                    <div class="map-legend">
                        <div><span class="legend-dot marker-menunggu"></span> Menunggu</div>
                        <div><span class="legend-dot marker-diverifikasi"></span> Diverifikasi</div>
                        <div><span class="legend-dot marker-diproses"></span> Diproses</div>
                        <div><span class="legend-dot marker-selesai"></span> Selesai</div>
                        <div><span class="legend-dot marker-ditolak"></span> Ditolak</div>
                    </div>

                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                        <?= count($laporanList); ?> titik tampil
                    </span>
                </div>

                <div id="userReportMap" class="map-user-report"></div>

                <?php if (count($laporanList) === 0): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        Belum ada titik laporan untuk filter yang dipilih.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($laporanList) > 0): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Daftar Titik Laporan</h5>
                            <p class="text-muted small mb-0">
                                Ringkasan laporan yang sedang tampil di peta.
                            </p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <?php foreach (array_slice($laporanList, 0, 6) as $laporan): ?>
                            <div class="col-md-6">
                                <a 
                                    href="<?= url('detail-laporan.php?id=' . $laporan['id_laporan']); ?>" 
                                    class="map-report-list-item"
                                >
                                    <img 
                                        src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" 
                                        alt="Foto laporan"
                                    >

                                    <div>
                                        <strong><?= e($laporan['judul']); ?></strong>
                                        <span><?= e(ucfirst($laporan['status'])); ?> • <?= date('d M Y', strtotime($laporan['created_at'])); ?></span>
                                        <small><?= e($laporan['alamat'] ?: 'Tanpa alamat tambahan'); ?></small>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($laporanList) > 6): ?>
                        <div class="text-center mt-4">
                            <a href="<?= url('riwayat-laporan.php'); ?>" class="btn btn-outline-primary">
                                Lihat Semua Riwayat
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php

$extraScripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const reports = ' . json_encode($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';

    const defaultLat = -6.2088;
    const defaultLng = 106.8456;

    const map = L.map("userReportMap").setView([defaultLat, defaultLng], 11);

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

    reports.forEach(function(report) {
        if (!report.latitude || !report.longitude) {
            return;
        }

        const latLng = [report.latitude, report.longitude];
        bounds.push(latLng);

        const popupContent = `
            <div class="map-popup">
                <img src="${escapeHtml(report.foto)}" alt="Foto laporan">

                <div class="map-popup-body">
                    <span class="status-badge status-${escapeHtml(report.status)}">
                        ${escapeHtml(report.status.charAt(0).toUpperCase() + report.status.slice(1))}
                    </span>

                    <h6>${escapeHtml(report.judul)}</h6>

                    <p class="text-muted small mb-2">
                        ${escapeHtml(report.deskripsi)}
                    </p>

                    <div class="small mb-2">
                        <strong>Tanggal:</strong> ${escapeHtml(report.tanggal)}<br>
                        <strong>Alamat:</strong> ${escapeHtml(report.alamat)}
                    </div>

                    <a href="${escapeHtml(report.detail_url)}" class="btn btn-primary btn-sm w-100">
                        Lihat Detail
                    </a>
                </div>
            </div>
        `;

        L.marker(latLng, {
            icon: getMarkerIcon(report.status),
            title: report.judul
        })
        .addTo(map)
        .bindPopup(popupContent, {
            maxWidth: 300
        });
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, {
            padding: [40, 40]
        });
    }
});
</script>
';

require_once __DIR__ . '/layouts/footer.php';

?>