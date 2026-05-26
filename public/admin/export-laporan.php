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

function is_valid_date_input_export($date)
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

if ($tanggalMulai !== '' && !is_valid_date_input_export($tanggalMulai)) {
    $tanggalMulai = '';
}

if ($tanggalSelesai !== '' && !is_valid_date_input_export($tanggalSelesai)) {
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

// Batas aman agar export tidak terlalu berat
$laporanList = $laporanModel->searchAdminReports($filters, 5000, 0);

$filename = 'export_laporan_parkir_liar_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM agar Excel membaca UTF-8 dengan benar
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, [
    'No',
    'Judul Laporan',
    'Deskripsi',
    'Pelapor',
    'Email Pelapor',
    'Status',
    'Alamat / Patokan',
    'Latitude',
    'Longitude',
    'Catatan Admin',
    'Tanggal Dibuat',
    'Terakhir Diupdate'
], ';');

$no = 1;

foreach ($laporanList as $laporan) {
    fputcsv($output, [
        $no++,
        $laporan['judul'],
        $laporan['deskripsi'],
        $laporan['nama'],
        $laporan['email'],
        ucfirst($laporan['status']),
        $laporan['alamat'] ?: '-',
        $laporan['latitude'],
        $laporan['longitude'],
        $laporan['catatan_admin'] ?: '-',
        $laporan['created_at'],
        $laporan['updated_at']
    ], ';');
}

fclose($output);
exit;