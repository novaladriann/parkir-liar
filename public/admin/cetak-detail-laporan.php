<?php

require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Laporan.php';
require_once __DIR__ . '/../../app/helpers/url.php';
require_once __DIR__ . '/../../app/helpers/session.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

require_role('admin');

$idLaporan = $_GET['id'] ?? null;

if (!$idLaporan || !ctype_digit($idLaporan)) {
    die('Laporan tidak valid.');
}

$laporanModel = new Laporan($pdo);
$laporan = $laporanModel->findByIdWithUser($idLaporan);

if (!$laporan) {
    die('Laporan tidak ditemukan.');
}

$statusLogs = $laporanModel->getStatusLogs($idLaporan);

?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Detail Laporan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 28px;
            font-size: 13px;
        }

        .no-print {
            margin-bottom: 20px;
        }

        .btn {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
            display: inline-block;
        }

        .btn-secondary {
            background: #ffffff;
            color: #2563eb;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 22px;
        }

        .header h2 {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .header p {
            margin: 0;
            color: #4b5563;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .box {
            border: 1px solid #d1d5db;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .box h3 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .foto {
            width: 100%;
            max-height: 360px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #d1d5db;
        }

        .info-row {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 8px;
            margin-bottom: 8px;
        }

        .info-row strong {
            color: #111827;
        }

        .status {
            font-weight: bold;
            text-transform: capitalize;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        .signature {
            margin-top: 36px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 240px;
            text-align: center;
        }

        .signature-space {
            height: 70px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }

            @page {
                size: A4;
                margin: 14mm;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn">
        Cetak / Simpan PDF
    </button>

    <a 
        href="<?= url('admin/detail-laporan.php?id=' . $laporan['id_laporan']); ?>" 
        class="btn btn-secondary"
    >
        Kembali
    </a>
</div>

<div class="header">
    <h2>Detail Laporan Parkir Liar</h2>
    <p>Sistem Pelaporan Parkir Liar</p>
</div>

<div class="grid">
    <div>
        <div class="box">
            <h3>Foto Bukti</h3>
            <img 
                src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" 
                alt="Foto laporan"
                class="foto"
            >
        </div>

        <div class="box">
            <h3>Deskripsi Laporan</h3>
            <p><?= nl2br(e($laporan['deskripsi'])); ?></p>
        </div>
    </div>

    <div>
        <div class="box">
            <h3>Informasi Laporan</h3>

            <div class="info-row">
                <strong>Judul</strong>
                <span><?= e($laporan['judul']); ?></span>
            </div>

            <div class="info-row">
                <strong>Status</strong>
                <span class="status"><?= e($laporan['status']); ?></span>
            </div>

            <div class="info-row">
                <strong>Tanggal</strong>
                <span><?= date('d M Y H:i', strtotime($laporan['created_at'])); ?></span>
            </div>

            <div class="info-row">
                <strong>Pelapor</strong>
                <span><?= e($laporan['nama']); ?></span>
            </div>

            <div class="info-row">
                <strong>Email</strong>
                <span><?= e($laporan['email']); ?></span>
            </div>
        </div>

        <div class="box">
            <h3>Lokasi</h3>

            <div class="info-row">
                <strong>Alamat</strong>
                <span><?= e($laporan['alamat'] ?: '-'); ?></span>
            </div>

            <div class="info-row">
                <strong>Latitude</strong>
                <span><?= e($laporan['latitude']); ?></span>
            </div>

            <div class="info-row">
                <strong>Longitude</strong>
                <span><?= e($laporan['longitude']); ?></span>
            </div>

            <div class="info-row">
                <strong>Google Maps</strong>
                <span>
                    https://www.google.com/maps?q=<?= e($laporan['latitude']); ?>,<?= e($laporan['longitude']); ?>
                </span>
            </div>
        </div>

        <div class="box">
            <h3>Catatan Admin</h3>
            <p><?= nl2br(e($laporan['catatan_admin'] ?: '-')); ?></p>
        </div>
    </div>
</div>

<div class="box">
    <h3>Riwayat Status</h3>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Status Sebelum</th>
                <th>Status Sesudah</th>
                <th>Catatan</th>
                <th>Oleh</th>
                <th>Waktu</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($statusLogs) === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">
                        Belum ada riwayat status.
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($statusLogs as $log): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= e($log['status_sebelum'] ?: '-'); ?></td>
                        <td><?= e($log['status_sesudah']); ?></td>
                        <td><?= e($log['catatan'] ?: '-'); ?></td>
                        <td><?= e($log['nama'] ?: 'Sistem'); ?></td>
                        <td><?= date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="signature">
    <div class="signature-box">
        <p>Petugas/Admin</p>
        <div class="signature-space"></div>
        <p>________________________</p>
    </div>
</div>

</body>
</html>