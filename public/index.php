<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';

$user = current_user();
$role = $user['role'] ?? 'guest';

$statusCounts = [
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

$totalLaporan = 0;
$totalUser = 0;
$completionRate = 0;

if ($role === 'admin') {
    $totalLaporan = (int) $pdo->query("SELECT COUNT(*) AS total FROM laporan")->fetch()['total'];
    $totalUser = (int) $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'masyarakat'")->fetch()['total'];

    $stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM laporan GROUP BY status");

    foreach ($stmt->fetchAll() as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }

    $completionRate = $totalLaporan > 0
        ? round(($statusCounts['selesai'] / $totalLaporan) * 100)
        : 0;
}

if ($role === 'masyarakat') {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM laporan WHERE id_user = :id_user GROUP BY status");
    $stmt->execute([
        ':id_user' => $user['id_user']
    ]);

    foreach ($stmt->fetchAll() as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }

    $totalLaporan = array_sum($statusCounts);

    $completionRate = $totalLaporan > 0
        ? round(($statusCounts['selesai'] / $totalLaporan) * 100)
        : 0;
}

require_once __DIR__ . '/layouts/header.php';

?>

<section class="landing-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <?php if ($role === 'admin'): ?>
                    <span class="landing-chip">Admin Monitoring System</span>

                    <h1 class="landing-title">
                        Pusat Monitoring Pelaporan Parkir Liar
                    </h1>

                    <p class="landing-subtitle">
                        Kelola laporan masyarakat, pantau titik pelanggaran melalui peta,
                        analisis grafik pelaporan, dan update status penanganan secara terstruktur.
                    </p>

                    <div class="landing-actions">
                        <a href="<?= url('admin/dashboard.php'); ?>" class="btn btn-primary btn-lg">
                            Buka Dashboard
                        </a>

                        <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-outline-primary btn-lg">
                            Kelola Laporan
                        </a>
                    </div>

                <?php elseif ($role === 'masyarakat'): ?>
                    <span class="landing-chip">Area Pelapor Masyarakat</span>

                    <h1 class="landing-title">
                        Laporkan Parkir Liar dengan Cepat dan Akurat
                    </h1>

                    <p class="landing-subtitle">
                        Buat laporan dengan foto bukti, lokasi GPS otomatis, serta pantau
                        perkembangan laporan melalui stepper progress dan notifikasi.
                    </p>

                    <div class="landing-actions">
                        <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary btn-lg">
                            Buat Laporan
                        </a>

                        <a href="<?= url('riwayat-laporan.php'); ?>" class="btn btn-outline-primary btn-lg">
                            Lihat Riwayat
                        </a>
                    </div>

                <?php else: ?>
                    <span class="landing-chip">Platform Pelaporan Publik</span>

                    <h1 class="landing-title">
                        Sistem Pelaporan Parkir Liar
                    </h1>

                    <p class="landing-subtitle">
                        Laporkan area parkir ilegal secara cepat dengan bukti foto,
                        lokasi GPS otomatis, peta pelanggaran, dan monitoring progress laporan.
                    </p>

                    <div class="landing-actions">
                        <a href="<?= url('register.php'); ?>" class="btn btn-primary btn-lg">
                            Mulai Melapor
                        </a>

                        <a href="<?= url('login.php'); ?>" class="btn btn-outline-primary btn-lg">
                            Login
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                    <div class="landing-stat-grid">
                        <div class="landing-stat-card">
                            <span>Total Laporan</span>
                            <strong><?= $totalLaporan; ?></strong>
                        </div>

                        <div class="landing-stat-card">
                            <span>Menunggu</span>
                            <strong><?= $statusCounts['menunggu']; ?></strong>
                        </div>

                        <div class="landing-stat-card">
                            <span>Selesai</span>
                            <strong><?= $statusCounts['selesai']; ?></strong>
                        </div>
                    </div>
                <?php elseif ($role === 'masyarakat'): ?>
                    <div class="landing-stat-grid">
                        <div class="landing-stat-card">
                            <span>Laporan Saya</span>
                            <strong><?= $totalLaporan; ?></strong>
                        </div>

                        <div class="landing-stat-card">
                            <span>Diproses</span>
                            <strong><?= $statusCounts['diproses']; ?></strong>
                        </div>

                        <div class="landing-stat-card">
                            <span>Selesai</span>
                            <strong><?= $statusCounts['selesai']; ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="landing-trust">
                        <div>
                            <strong>Foto Bukti</strong>
                            <span>Upload dokumentasi laporan</span>
                        </div>

                        <div>
                            <strong>GPS Otomatis</strong>
                            <span>Lokasi lebih akurat</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-6">
                <div class="hero-visual-card">
                    <div class="visual-header">
                        <div>
                            <span class="visual-dot"></span>
                            <strong>Live Monitoring</strong>
                        </div>

                        <span class="visual-status">
                            <?= $role === 'admin' ? 'Admin View' : 'Report View'; ?>
                        </span>
                    </div>

                    <div class="map-mockup">
                        <div class="map-line line-1"></div>
                        <div class="map-line line-2"></div>
                        <div class="map-line line-3"></div>

                        <span class="map-pin pin-1"></span>
                        <span class="map-pin pin-2"></span>
                        <span class="map-pin pin-3"></span>

                        <div class="floating-report floating-report-1">
                            <span class="mini-label">Status</span>
                            <strong><?= $role === 'admin' ? 'Menunggu Verifikasi' : 'Menunggu'; ?></strong>
                            <small>Parkir liar terdeteksi</small>
                        </div>

                        <div class="floating-report floating-report-2">
                            <span class="mini-label">Lokasi</span>
                            <strong>GPS Aktif</strong>
                            <small>Marker peta otomatis</small>
                        </div>
                    </div>

                    <div class="visual-footer">
                        <div>
                            <span>Progress</span>
                            <div class="visual-progress">
                                <div style="width: <?= $role === 'guest' ? 70 : $completionRate; ?>%"></div>
                            </div>
                        </div>

                        <strong><?= $role === 'guest' ? 'Realtime' : $completionRate . '%'; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-section">
    <div class="container">
        <div class="section-heading text-center mb-5">
            <span class="landing-chip">Fitur Sistem</span>
            <h2 class="fw-bold mt-3 mb-2">Fitur Utama Pelaporan</h2>
            <p class="text-muted mb-0">
                Sistem dibuat untuk memudahkan pelaporan, pemantauan, dan pengelolaan data parkir liar.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="feature-icon">01</div>
                    <h5>Foto Kendaraan / Lokasi</h5>
                    <p>
                        Pelapor dapat mengunggah foto sebagai bukti visual agar laporan lebih valid.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="feature-icon">02</div>
                    <h5>GPS Otomatis</h5>
                    <p>
                        Titik lokasi laporan tersimpan otomatis menggunakan latitude dan longitude.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="feature-icon">03</div>
                    <h5>Monitoring Progress</h5>
                    <p>
                        Status laporan ditampilkan dalam bentuk stepper agar proses mudah dipahami.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="feature-icon">04</div>
                    <h5>Peta Pelanggaran</h5>
                    <p>
                        Admin dapat melihat sebaran titik laporan parkir liar dalam peta interaktif.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="feature-icon">05</div>
                    <h5>Grafik Pelaporan</h5>
                    <p>
                        Data laporan divisualisasikan dalam grafik status dan tren laporan per bulan.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="feature-icon">06</div>
                    <h5>Notifikasi Status</h5>
                    <p>
                        Masyarakat mendapat informasi saat status laporan diperbarui oleh admin.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="landing-section pt-0">
    <div class="container">
        <div class="workflow-card">
            <div class="row align-items-center g-4">
                <div class="col-lg-5">
                    <span class="landing-chip">Alur Sistem</span>
                    <h2 class="fw-bold mt-3 mb-3">Alur Pelaporan yang Jelas</h2>
                    <p class="text-muted mb-0">
                        Setiap laporan memiliki proses yang dapat dipantau, mulai dari laporan masuk,
                        verifikasi admin, penanganan, hingga selesai.
                    </p>
                </div>

                <div class="col-lg-7">
                    <div class="workflow-steps">
                        <div class="workflow-step">
                            <span>1</span>
                            <strong>Laporan Dibuat</strong>
                            <small>Masyarakat mengirim foto dan lokasi GPS.</small>
                        </div>

                        <div class="workflow-step">
                            <span>2</span>
                            <strong>Diverifikasi</strong>
                            <small>Admin mengecek validitas laporan.</small>
                        </div>

                        <div class="workflow-step">
                            <span>3</span>
                            <strong>Diproses</strong>
                            <small>Laporan ditindaklanjuti oleh petugas.</small>
                        </div>

                        <div class="workflow-step">
                            <span>4</span>
                            <strong>Selesai</strong>
                            <small>Status akhir diperbarui ke pelapor.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>