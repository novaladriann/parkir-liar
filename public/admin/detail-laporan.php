<?php

require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Laporan.php';
require_once __DIR__ . '/../../app/helpers/url.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/status.php';
require_once __DIR__ . '/../../app/helpers/csrf.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../app/controllers/AdminController.php';
    AdminController::updateStatusLaporan();
}

$idLaporan = $_GET['id'] ?? null;

if (!$idLaporan || !ctype_digit($idLaporan)) {
    set_flash('error', 'Laporan tidak valid.');
    header('Location: ' . url('admin/laporan.php'));
    exit;
}

$laporanModel = new Laporan($pdo);
$laporan = $laporanModel->findByIdWithUser($idLaporan);
$statusLogs = $laporanModel->getStatusLogs($idLaporan);

if (!$laporan) {
    set_flash('error', 'Laporan tidak ditemukan.');
    header('Location: ' . url('admin/laporan.php'));
    exit;
}

require_once __DIR__ . '/../layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Detail Laporan Admin</p>
                <h2 class="fw-bold mb-2"><?= e($laporan['judul']); ?></h2>
                <p class="text-muted mb-0">
                    Dikirim oleh <?= e($laporan['nama']); ?> pada
                    <?= date('d M Y H:i', strtotime($laporan['created_at'])); ?>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= url('admin/cetak-detail-laporan.php?id=' . $laporan['id_laporan']); ?>" target="_blank"
                    class="btn btn-primary">
                    Cetak Laporan
                </a>

                <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-outline-primary">
                    Kembali
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="report-detail-image">
                        <img src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" alt="Foto laporan"
                            role="button" data-bs-toggle="modal" data-bs-target="#fotoPreviewModal">
                    </div>

                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Deskripsi Laporan</h5>
                        <p class="text-muted">
                            <?= nl2br(e($laporan['deskripsi'])); ?>
                        </p>

                        <?php if ($laporan['alamat']): ?>
                            <div class="alert alert-light border mb-0">
                                <strong>Alamat / Patokan:</strong><br>
                                <?= nl2br(e($laporan['alamat'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Lokasi Pelanggaran</h5>

                        <div id="adminDetailMap" class="map-detail"></div>

                        <a href="https://www.google.com/maps?q=<?= e($laporan['latitude']); ?>,<?= e($laporan['longitude']); ?>"
                            target="_blank" class="btn btn-primary w-100 mt-3">
                            Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Progress Saat Ini</h5>
                        <div class="card shadow-sm mb-4">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">Riwayat Status</h5>

                                <?php if (count($statusLogs) === 0): ?>
                                    <div class="alert alert-info mb-0">
                                        Belum ada riwayat perubahan status.
                                    </div>
                                <?php else: ?>
                                    <div class="status-timeline">
                                        <?php foreach ($statusLogs as $log): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-dot status-<?= e($log['status_sesudah']); ?>"></div>

                                                <div class="timeline-content">
                                                    <div class="d-flex justify-content-between gap-2">
                                                        <strong>
                                                            <?= e(ucfirst($log['status_sesudah'])); ?>
                                                        </strong>

                                                        <small class="text-muted">
                                                            <?= date('d M Y H:i', strtotime($log['created_at'])); ?>
                                                        </small>
                                                    </div>

                                                    <?php if ($log['status_sebelum']): ?>
                                                        <p class="text-muted mb-1 small">
                                                            Dari <?= e(ucfirst($log['status_sebelum'])); ?>
                                                            ke <?= e(ucfirst($log['status_sesudah'])); ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="text-muted mb-1 small">
                                                            Status awal laporan.
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if ($log['catatan']): ?>
                                                        <p class="mb-1">
                                                            <?= nl2br(e($log['catatan'])); ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <small class="text-muted">
                                                        Oleh: <?= e($log['nama'] ?: 'Sistem'); ?>
                                                        <?= $log['role'] ? '(' . e($log['role']) . ')' : ''; ?>
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
                        <h5 class="fw-bold mb-3">Update Status Laporan</h5>

                        <form method="POST" action="">
                            <?= csrf_field(); ?>

                            <input type="hidden" name="id_laporan" value="<?= e($laporan['id_laporan']); ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="menunggu" <?= $laporan['status'] === 'menunggu' ? 'selected' : ''; ?>>
                                        Menunggu
                                    </option>
                                    <option value="diverifikasi" <?= $laporan['status'] === 'diverifikasi' ? 'selected' : ''; ?>>
                                        Diverifikasi
                                    </option>
                                    <option value="diproses" <?= $laporan['status'] === 'diproses' ? 'selected' : ''; ?>>
                                        Diproses
                                    </option>
                                    <option value="selesai" <?= $laporan['status'] === 'selesai' ? 'selected' : ''; ?>>
                                        Selesai
                                    </option>
                                    <option value="ditolak" <?= $laporan['status'] === 'ditolak' ? 'selected' : ''; ?>>
                                        Ditolak
                                    </option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Catatan Admin</label>
                                <textarea name="catatan_admin" class="form-control" rows="5"
                                    placeholder="Contoh: Laporan valid dan akan diteruskan ke petugas lapangan."><?= e($laporan['catatan_admin'] ?? ''); ?></textarea>
                                <small class="text-muted">
                                    Wajib diisi jika status laporan ditolak.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Data Pelapor</h5>

                        <div class="info-list">
                            <div>
                                <span>Nama</span>
                                <strong><?= e($laporan['nama']); ?></strong>
                            </div>

                            <div>
                                <span>Email</span>
                                <strong><?= e($laporan['email']); ?></strong>
                            </div>

                            <div>
                                <span>Latitude</span>
                                <strong><?= e($laporan['latitude']); ?></strong>
                            </div>

                            <div>
                                <span>Longitude</span>
                                <strong><?= e($laporan['longitude']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="fotoPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content preview-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Preview Foto Laporan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <img src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" alt="Preview foto laporan"
                    class="preview-image">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lat = <?= json_encode((float) $laporan['latitude']); ?>;
        const lng = <?= json_encode((float) $laporan['longitude']); ?>;

        const map = L.map('adminDetailMap').setView([lat, lng], 17);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng])
            .addTo(map)
            .bindPopup('Titik lokasi pelanggaran')
            .openPopup();
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>