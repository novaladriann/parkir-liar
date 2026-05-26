<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

require_role('masyarakat');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/LaporanController.php';
    LaporanController::store();
}

require_once __DIR__ . '/layouts/header.php';

$error = get_flash('error');
$success = get_flash('success');

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Form Pelaporan</p>
                <h2 class="fw-bold mb-2">Buat Laporan Parkir Liar</h2>
                <p class="text-muted mb-0">
                    Unggah bukti foto dan pastikan lokasi GPS berhasil terdeteksi sebelum mengirim laporan.
                </p>
            </div>
            <a href="<?= url('riwayat-laporan.php'); ?>" class="btn btn-outline-primary">
                Riwayat Laporan
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data" action="">
                            <?= csrf_field(); ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Judul Laporan</label>
                                <input type="text" name="judul" class="form-control form-control-lg"
                                    placeholder="Contoh: Parkir liar di depan minimarket" maxlength="150" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi Kejadian</label>
                                <textarea name="deskripsi" class="form-control" rows="5"
                                    placeholder="Jelaskan kondisi parkir liar, waktu kejadian, dan dampaknya."
                                    required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Alamat / Patokan Lokasi</label>
                                <textarea name="alamat" class="form-control" rows="3"
                                    placeholder="Contoh: Dekat gerbang pasar, depan toko, samping halte, dan sebagainya."></textarea>
                                <small class="text-muted">
                                    Alamat ini opsional, tetapi membantu admin memahami lokasi.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Foto Kendaraan / Lokasi</label>
                                <input type="file" name="foto" id="foto" class="form-control"
                                    accept="image/jpeg,image/png,image/webp" required>
                                <small class="text-muted">
                                    Format: JPG, PNG, WEBP. Maksimal 2MB.
                                </small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Lokasi GPS</label>
                                <div id="reportMap" class="map-preview mb-3"></div>
                                <div class="gps-box mb-3">
                                    <div>
                                        <strong id="gpsStatus">Mendeteksi lokasi...</strong>
                                        <p class="text-muted mb-0" id="gpsInfo">
                                            Izinkan akses lokasi pada browser.
                                        </p>
                                    </div>

                                    <button type="button" class="btn btn-outline-primary" onclick="getLocation()">
                                        Ambil Ulang GPS
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" name="latitude" id="latitude" class="form-control"
                                            placeholder="Latitude" readonly required>
                                    </div>

                                    <div class="col-md-6">
                                        <input type="text" name="longitude" id="longitude" class="form-control"
                                            placeholder="Longitude" readonly required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Kirim Laporan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Panduan Laporan</h5>

                        <div class="guide-item">
                            <span>1</span>
                            <p>Pastikan foto menunjukkan kendaraan atau area parkir liar dengan jelas.</p>
                        </div>

                        <div class="guide-item">
                            <span>2</span>
                            <p>Aktifkan izin lokasi agar titik GPS dapat tersimpan otomatis.</p>
                        </div>

                        <div class="guide-item">
                            <span>3</span>
                            <p>Gunakan deskripsi singkat, jelas, dan sesuai kondisi di lapangan.</p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    Laporan palsu atau tidak relevan dapat ditolak oleh admin.
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    let reportMap;
    let reportMarker;

    function initMap() {
        const defaultLat = -6.2088;
        const defaultLng = 106.8456;

        reportMap = L.map('reportMap').setView([defaultLat, defaultLng], 12);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(reportMap);

        reportMarker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(reportMap);

        reportMarker.bindPopup('Titik laporan akan muncul di sini.').openPopup();

        reportMarker.on('dragend', function (event) {
            const position = event.target.getLatLng();

            document.getElementById('latitude').value = position.lat.toFixed(8);
            document.getElementById('longitude').value = position.lng.toFixed(8);

            document.getElementById('gpsStatus').textContent = 'Lokasi dipilih manual dari peta';
            document.getElementById('gpsInfo').textContent = 'Marker digeser secara manual oleh pelapor.';
        });
    }

    function updateMap(lat, lng, popupText = 'Lokasi laporan terdeteksi.') {
        if (!reportMap || !reportMarker) {
            return;
        }

        reportMap.setView([lat, lng], 17);
        reportMarker.setLatLng([lat, lng]);
        reportMarker.bindPopup(popupText).openPopup();
    }

    function getLocation() {
        const gpsStatus = document.getElementById('gpsStatus');
        const gpsInfo = document.getElementById('gpsInfo');
        const latitude = document.getElementById('latitude');
        const longitude = document.getElementById('longitude');

        if (!navigator.geolocation) {
            gpsStatus.textContent = 'GPS tidak didukung';
            gpsInfo.textContent = 'Browser kamu tidak mendukung fitur geolocation.';
            return;
        }

        gpsStatus.textContent = 'Mendeteksi lokasi...';
        gpsInfo.textContent = 'Mohon izinkan akses lokasi pada browser.';

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                latitude.value = lat.toFixed(8);
                longitude.value = lng.toFixed(8);

                gpsStatus.textContent = 'Lokasi berhasil terdeteksi';
                gpsInfo.textContent = 'Latitude dan longitude sudah terisi otomatis. Marker juga sudah diperbarui di peta.';

                updateMap(lat, lng);
            },
            function (error) {
                latitude.value = '';
                longitude.value = '';

                gpsStatus.textContent = 'Gagal mendeteksi lokasi';

                if (error.code === error.PERMISSION_DENIED) {
                    gpsInfo.textContent = 'Akses lokasi ditolak. Aktifkan izin lokasi pada browser atau geser marker di peta secara manual.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    gpsInfo.textContent = 'Informasi lokasi tidak tersedia. Kamu bisa geser marker di peta secara manual.';
                } else if (error.code === error.TIMEOUT) {
                    gpsInfo.textContent = 'Waktu permintaan lokasi habis. Coba ambil ulang GPS atau geser marker manual.';
                } else {
                    gpsInfo.textContent = 'Terjadi kesalahan saat mengambil lokasi. Kamu bisa geser marker manual.';
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        getLocation();
    });
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>