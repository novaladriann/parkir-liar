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
$statusLogs = $laporanModel->getStatusLogs($idLaporan);

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
                        <img src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" alt="Foto laporan">
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
                        <div class="card shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">Riwayat Progress</h5>

                                <?php if (count($statusLogs) === 0): ?>
                                    <div class="alert alert-info mb-0">
                                        Belum ada riwayat progress.
                                    </div>
                                <?php else: ?>
                                    <div class="status-timeline">
                                        <?php foreach ($statusLogs as $log): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-dot status-<?= e($log['status_sesudah']); ?>"></div>

                                                <div class="timeline-content">
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <strong><?= e(ucfirst($log['status_sesudah'])); ?></strong>

                                                        <small class="text-muted">
                                                            <?= date('d M Y H:i', strtotime($log['created_at'])); ?>
                                                        </small>
                                                    </div>

                                                    <?php if ($log['catatan']): ?>
                                                        <p class="mb-1">
                                                            <?= nl2br(e($log['catatan'])); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <small class="text-muted">
                                                        Diperbarui oleh <?= e($log['nama'] ?: 'Sistem'); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

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
                        <div id="detailMap" class="map-detail mt-4"></div>
                        <a href="https://www.google.com/maps?q=<?= e($laporan['latitude']); ?>,<?= e($laporan['longitude']); ?>"
                            target="_blank" class="btn btn-primary w-100 mt-4">
                            Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lat = <?= json_encode((float) $laporan['latitude']); ?>;
        const lng = <?= json_encode((float) $laporan['longitude']); ?>;

        const map = L.map('detailMap').setView([lat, lng], 17);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng])
            .addTo(map)
            .bindPopup('Lokasi laporan parkir liar')
            .openPopup();
    });
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>