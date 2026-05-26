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
$laporanList = $laporanModel->findForMap($statusFilter);

$statusCounts = [
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

$countStmt = $pdo->query("SELECT status, COUNT(*) AS total FROM laporan GROUP BY status");

foreach ($countStmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

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
        'nama' => $laporan['nama'],
        'tanggal' => date('d M Y H:i', strtotime($laporan['created_at'])),
        'detail_url' => url('admin/detail-laporan.php?id=' . $laporan['id_laporan'])
    ];
}

require_once __DIR__ . '/../layouts/header.php';

function map_status_label($status)
{
    return ucfirst($status);
}

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Peta Pelanggaran</p>
                <h2 class="fw-bold mb-2">Sebaran Laporan Parkir Liar</h2>
                <p class="text-muted mb-0">
                    Pantau titik lokasi laporan parkir liar berdasarkan data GPS dari masyarakat.
                </p>
            </div>

            <a href="<?= url('admin/dashboard.php'); ?>" class="btn btn-outline-primary">
                Dashboard
            </a>
        </div>

        <div class="row g-3 mb-4">
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

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Filter Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>

                            <?php foreach ($allowedStatus as $status): ?>
                                <option value="<?= e($status); ?>" <?= $statusFilter === $status ? 'selected' : ''; ?>>
                                    <?= e(map_status_label($status)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <button type="submit" class="btn btn-primary">
                            Terapkan Filter
                        </button>

                        <a href="<?= url('admin/peta.php'); ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>

                        <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-outline-primary">
                            Kelola Laporan
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="map-legend mb-3">
                    <div><span class="legend-dot marker-menunggu"></span> Menunggu</div>
                    <div><span class="legend-dot marker-diverifikasi"></span> Diverifikasi</div>
                    <div><span class="legend-dot marker-diproses"></span> Diproses</div>
                    <div><span class="legend-dot marker-selesai"></span> Selesai</div>
                    <div><span class="legend-dot marker-ditolak"></span> Ditolak</div>
                </div>

                <div id="violationMap" class="map-admin"></div>

                <?php if (count($mapData) === 0): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        Belum ada titik laporan untuk status yang dipilih.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reports = <?= json_encode($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const defaultLat = -6.2088;
    const defaultLng = 106.8456;

    const map = L.map('violationMap').setView([defaultLat, defaultLng], 11);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    function getMarkerIcon(status) {
        return L.divIcon({
            className: '',
            html: `<div class="custom-map-marker marker-${status}"></div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            popupAnchor: [0, -12]
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
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
                        <strong>Pelapor:</strong> ${escapeHtml(report.nama)}<br>
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
            alt: 'Marker laporan ' + report.judul,
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>