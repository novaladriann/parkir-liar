<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Laporan.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/url.php';
require_once __DIR__ . '/../helpers/csrf.php';

class AdminController
{
    public static function updateStatusLaporan()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('admin/laporan.php'));
            exit;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Token keamanan tidak valid.');
            header('Location: ' . url('admin/laporan.php'));
            exit;
        }

        $idLaporan = $_POST['id_laporan'] ?? '';
        $status = $_POST['status'] ?? '';
        $catatanAdmin = trim($_POST['catatan_admin'] ?? '');

        $allowedStatus = [
            'menunggu',
            'diverifikasi',
            'diproses',
            'selesai',
            'ditolak'
        ];

        if (!ctype_digit($idLaporan)) {
            set_flash('error', 'ID laporan tidak valid.');
            header('Location: ' . url('admin/laporan.php'));
            exit;
        }

        if (!in_array($status, $allowedStatus, true)) {
            set_flash('error', 'Status laporan tidak valid.');
            header('Location: ' . url('admin/detail-laporan.php?id=' . $idLaporan));
            exit;
        }

        if ($status === 'ditolak' && $catatanAdmin === '') {
            set_flash('error', 'Catatan admin wajib diisi jika laporan ditolak.');
            header('Location: ' . url('admin/detail-laporan.php?id=' . $idLaporan));
            exit;
        }

        $laporanModel = new Laporan($pdo);
        $laporan = $laporanModel->findByIdWithUser($idLaporan);

        if (!$laporan) {
            set_flash('error', 'Laporan tidak ditemukan.');
            header('Location: ' . url('admin/laporan.php'));
            exit;
        }

        $statusSebelum = $laporan['status'];
        $catatanSebelumnya = $laporan['catatan_admin'];

        $laporanModel->updateStatus(
            $idLaporan,
            $status,
            $catatanAdmin !== '' ? $catatanAdmin : null
        );

        $user = current_user();

        if ($statusSebelum !== $status || $catatanSebelumnya !== $catatanAdmin) {
            $laporanModel->addStatusLog(
                $idLaporan,
                $statusSebelum,
                $status,
                $catatanAdmin !== '' ? $catatanAdmin : 'Status laporan diperbarui oleh admin.',
                $user['id_user']
            );
        }

        set_flash('success', 'Status laporan berhasil diperbarui.');
        header('Location: ' . url('admin/detail-laporan.php?id=' . $idLaporan));
        exit;
    }
}