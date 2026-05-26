<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Laporan.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/url.php';
require_once __DIR__ . '/../helpers/csrf.php';

class LaporanController
{
    public static function store()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Token keamanan tidak valid.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $user = current_user();

        if (!$user || $user['role'] !== 'masyarakat') {
            set_flash('error', 'Anda tidak memiliki akses membuat laporan.');
            header('Location: ' . url('login.php'));
            exit;
        }

        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');

        if ($judul === '' || $deskripsi === '' || $latitude === '' || $longitude === '') {
            set_flash('error', 'Judul, deskripsi, dan lokasi GPS wajib diisi.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        if (strlen($judul) > 150) {
            set_flash('error', 'Judul laporan maksimal 150 karakter.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        if (strlen($deskripsi) < 10) {
            set_flash('error', 'Deskripsi minimal 10 karakter agar laporan lebih jelas.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            set_flash('error', 'Format koordinat GPS tidak valid.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            set_flash('error', 'Koordinat GPS berada di luar batas yang valid.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Foto bukti wajib diunggah.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $foto = $_FILES['foto'];

        $maxSize = 2 * 1024 * 1024;

        if ($foto['size'] > $maxSize) {
            set_flash('error', 'Ukuran foto maksimal 2MB.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($foto['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!array_key_exists($mimeType, $allowedTypes)) {
            set_flash('error', 'Format foto harus JPG, PNG, atau WEBP.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $imageInfo = getimagesize($foto['tmp_name']);

        if ($imageInfo === false) {
            set_flash('error', 'File yang diunggah bukan gambar valid.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $imageWidth = $imageInfo[0];
        $imageHeight = $imageInfo[1];

        if ($imageWidth < 300 || $imageHeight < 300) {
            set_flash('error', 'Resolusi foto terlalu kecil. Gunakan foto minimal 300x300 piksel.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        if ($imageWidth > 6000 || $imageHeight > 6000) {
            set_flash('error', 'Resolusi foto terlalu besar. Gunakan foto dengan ukuran lebih wajar.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $extension = $allowedTypes[$mimeType];
        $fileName = 'laporan_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

        $uploadDir = __DIR__ . '/../../public/assets/uploads/laporan/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($foto['tmp_name'], $targetPath)) {
            set_flash('error', 'Gagal mengunggah foto. Coba lagi.');
            header('Location: ' . url('buat-laporan.php'));
            exit;
        }

        $laporanModel = new Laporan($pdo);

        $idLaporan = $laporanModel->create([
            'id_user' => $user['id_user'],
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'foto' => $fileName,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'alamat' => $alamat !== '' ? $alamat : null,
            'status' => 'menunggu'
        ]);

        $laporanModel->addStatusLog(
            $idLaporan,
            null,
            'menunggu',
            'Laporan dikirim oleh masyarakat dan menunggu verifikasi admin.',
            $user['id_user']
        );

        set_flash('success', 'Laporan berhasil dikirim dan sedang menunggu verifikasi.');
        header('Location: ' . url('riwayat-laporan.php'));
        exit;
    }
}