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

function is_valid_date_input_print($date)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
}

$statusFilter = $_GET['status'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSelesai = $_GET['tanggal_selesai'] ?? '';

if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = '';
}

if ($tanggalMulai !== '' && !is_valid_date_input_print($tanggalMulai)) {
    $tanggalMulai = '';
}

if ($tanggalSelesai !== '' && !is_valid_date_input_print($tanggalSelesai)) {
    $tanggalSelesai = '';
}

if ($tanggalMulai !== '' && $tanggalSelesai !== '' && $tanggalMulai > $tanggalSelesai) {
    $temp = $tanggalMulai;
    $tanggalMulai = $tanggalSelesai;
    $tanggalSelesai = $temp;
}

$filters = [
    'status' => $statusFilter,
    'keyword' => $keyword,
    'tanggal_mulai' => $tanggalMulai,
    'tanggal_selesai' => $tanggalSelesai
];

$laporanModel = new Laporan($pdo);

$totalData = $laporanModel->countAdminReports($filters);
$laporanList = $laporanModel->searchAdminReports($filters, 5000, 0);

$statusText = $statusFilter !== '' ? ucfirst($statusFilter) : 'Semua Status';
$periodeText = '-';

if ($tanggalMulai !== '' && $tanggalSelesai !== '') {
    $periodeText = date('d M Y', strtotime($tanggalMulai)) . ' - ' . date('d M Y', strtotime($tanggalSelesai));
} elseif ($tanggalMulai !== '') {
    $periodeText = 'Mulai ' . date('d M Y', strtotime($tanggalMulai));
} elseif ($tanggalSelesai !== '') {
    $periodeText = 'Sampai ' . date('d M Y', strtotime($tanggalSelesai));
}

?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Laporan Parkir Liar</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 28px;
            font-size: 12px;
        }

        .print-header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
        }

        .print-header h2 {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .print-header p {
            margin: 0;
            color: #4b5563;
        }

        .meta {
            margin-bottom: 18px;
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 6px;
        }

        .meta strong {
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            color: #111827;
            font-weight: bold;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        .status {
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }

        .signature {
            width: 240px;
            text-align: center;
        }

        .signature-space {
            height: 70px;
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

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }

            @page {
                size: landscape;
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

        <a href="<?= url('admin/laporan.php?' . http_build_query($_GET)); ?>" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    <div class="print-header">
        <h2>Laporan Sistem Pelaporan Parkir Liar</h2>
        <p>Rekapitulasi laporan masyarakat berdasarkan filter admin</p>
    </div>

    <div class="meta">
        <strong>Status</strong>
        <span>: <?= e($statusText); ?></span>

        <strong>Kata Kunci</strong>
        <span>: <?= e($keyword !== '' ? $keyword : '-'); ?></span>

        <strong>Periode</strong>
        <span>: <?= e($periodeText); ?></span>

        <strong>Total Data</strong>
        <span>: <?= e($totalData); ?> laporan</span>

        <strong>Tanggal Cetak</strong>
        <span>: <?= date('d M Y H:i'); ?></span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th>Judul</th>
                <th>Pelapor</th>
                <th>Status</th>
                <th>Lokasi</th>
                <th>Tanggal</th>
                <th>Catatan Admin</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($laporanList) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">
                        Tidak ada data laporan.
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($laporanList as $laporan): ?>
                    <tr>
                        <td><?= $no++; ?></td>

                        <td>
                            <strong><?= e($laporan['judul']); ?></strong><br>
                            <?= e($laporan['deskripsi']); ?>
                        </td>

                        <td>
                            <?= e($laporan['nama']); ?><br>
                            <?= e($laporan['email']); ?>
                        </td>

                        <td class="status">
                            <?= e(ucfirst($laporan['status'])); ?>
                        </td>

                        <td>
                            <?= e($laporan['alamat'] ?: '-'); ?><br>
                            Lat: <?= e($laporan['latitude']); ?><br>
                            Lng: <?= e($laporan['longitude']); ?>
                        </td>

                        <td>
                            <?= date('d M Y H:i', strtotime($laporan['created_at'])); ?>
                        </td>

                        <td>
                            <?= e($laporan['catatan_admin'] ?: '-'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Petugas/Admin</p>
            <div class="signature-space"></div>
            <p>________________________</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            // Auto print boleh diaktifkan kalau mau:
            // window.print();
        });
    </script>

</body>
</html>