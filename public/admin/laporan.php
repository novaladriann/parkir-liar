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

function is_valid_date_input($date)
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

if ($tanggalMulai !== '' && !is_valid_date_input($tanggalMulai)) {
    $tanggalMulai = '';
}

if ($tanggalSelesai !== '' && !is_valid_date_input($tanggalSelesai)) {
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

$perPage = 3;
$totalData = $laporanModel->countAdminReports($filters);
$totalPages = max(1, (int) ceil($totalData / $perPage));

$page = isset($_GET['page']) && ctype_digit($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$laporanList = $laporanModel->searchAdminReports($filters, $perPage, $offset);

require_once __DIR__ . '/../layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

function badge_status($status)
{
    return 'status-badge status-' . $status;
}

function build_admin_laporan_page_url($pageNumber)
{
    $params = $_GET;
    $params['page'] = $pageNumber;

    return url('admin/laporan.php?' . http_build_query($params));
}

function build_reset_filter_url()
{
    return url('admin/laporan.php');
}

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Admin Panel</p>
                <h2 class="fw-bold mb-2">Kelola Laporan Parkir Liar</h2>
                <p class="text-muted mb-0">
                    Cari, filter, verifikasi, dan pantau laporan masyarakat secara terstruktur.
                </p>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= url('admin/dashboard.php'); ?>" class="btn btn-outline-primary">
                    Dashboard
                </a>

                <a href="<?= url('admin/peta.php'); ?>" class="btn btn-primary">
                    Peta
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Pencarian</label>
                        <input 
                            type="text" 
                            name="keyword" 
                            class="form-control"
                            value="<?= e($keyword); ?>"
                            placeholder="Cari judul, pelapor, email, alamat..."
                        >
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>

                            <?php foreach ($allowedStatus as $status): ?>
                                <option value="<?= e($status); ?>" <?= $statusFilter === $status ? 'selected' : ''; ?>>
                                    <?= e(ucfirst($status)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Tanggal Mulai</label>
                        <input 
                            type="date" 
                            name="tanggal_mulai" 
                            class="form-control"
                            value="<?= e($tanggalMulai); ?>"
                        >
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Tanggal Selesai</label>
                        <input 
                            type="date" 
                            name="tanggal_selesai" 
                            class="form-control"
                            value="<?= e($tanggalSelesai); ?>"
                        >
                    </div>

                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Filter
                        </button>

                        <a href="<?= build_reset_filter_url(); ?>" class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted mb-0">
                Menampilkan 
                <strong><?= count($laporanList); ?></strong> 
                dari 
                <strong><?= $totalData; ?></strong> 
                laporan
            </p>

            <p class="text-muted mb-0">
                Halaman <strong><?= $page; ?></strong> dari <strong><?= $totalPages; ?></strong>
            </p>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if (count($laporanList) === 0): ?>
                    <div class="text-center p-5">
                        <h5 class="fw-bold">Data Tidak Ditemukan</h5>
                        <p class="text-muted mb-3">
                            Tidak ada laporan yang cocok dengan filter yang dipilih.
                        </p>

                        <a href="<?= url('admin/laporan.php'); ?>" class="btn btn-primary">
                            Tampilkan Semua Laporan
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Foto</th>
                                    <th>Pelapor</th>
                                    <th>Laporan</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($laporanList as $laporan): ?>
                                    <tr>
                                        <td>
                                            <img 
                                                src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>" 
                                                alt="Foto laporan"
                                                class="table-thumb"
                                            >
                                        </td>

                                        <td>
                                            <strong><?= e($laporan['nama']); ?></strong><br>
                                            <small class="text-muted"><?= e($laporan['email']); ?></small>
                                        </td>

                                        <td>
                                            <strong><?= e($laporan['judul']); ?></strong><br>
                                            <small class="text-muted">
                                                <?= e(mb_strimwidth($laporan['deskripsi'], 0, 80, '...')); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <small class="text-muted">
                                                <?= e($laporan['alamat'] ?: 'Tanpa alamat tambahan'); ?><br>
                                                <?= e($laporan['latitude']); ?>, <?= e($laporan['longitude']); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <span class="<?= badge_status($laporan['status']); ?>">
                                                <?= e(ucfirst($laporan['status'])); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= date('d M Y', strtotime($laporan['created_at'])); ?><br>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($laporan['created_at'])); ?>
                                            </small>
                                        </td>

                                        <td class="text-end">
                                            <a 
                                                href="<?= url('admin/detail-laporan.php?id=' . $laporan['id_laporan']); ?>" 
                                                class="btn btn-outline-primary btn-sm"
                                            >
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?= build_admin_laporan_page_url($page - 1); ?>">
                                        Sebelumnya
                                    </a>
                                </li>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                ?>

                                <?php if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= build_admin_laporan_page_url(1); ?>">1</a>
                                    </li>

                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?= build_admin_laporan_page_url($i); ?>">
                                            <?= $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>

                                    <li class="page-item">
                                        <a class="page-link" href="<?= build_admin_laporan_page_url($totalPages); ?>">
                                            <?= $totalPages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?= build_admin_laporan_page_url($page + 1); ?>">
                                        Berikutnya
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>