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

$allowedStatus = [
    'menunggu',
    'diverifikasi',
    'diproses',
    'selesai',
    'ditolak'
];

function is_valid_date_user_report($date)
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

if ($tanggalMulai !== '' && !is_valid_date_user_report($tanggalMulai)) {
    $tanggalMulai = '';
}

if ($tanggalSelesai !== '' && !is_valid_date_user_report($tanggalSelesai)) {
    $tanggalSelesai = '';
}

if ($tanggalMulai !== '' && $tanggalSelesai !== '' && $tanggalMulai > $tanggalSelesai) {
    $temp = $tanggalMulai;
    $tanggalMulai = $tanggalSelesai;
    $tanggalSelesai = $temp;
}

$filters = [
    'id_user' => $user['id_user'],
    'status' => $statusFilter,
    'keyword' => $keyword,
    'tanggal_mulai' => $tanggalMulai,
    'tanggal_selesai' => $tanggalSelesai
];

$laporanModel = new Laporan($pdo);

$statusCounts = [
    'menunggu' => 0,
    'diverifikasi' => 0,
    'diproses' => 0,
    'selesai' => 0,
    'ditolak' => 0
];

foreach ($laporanModel->countByStatusForUser($user['id_user']) as $row) {
    $statusCounts[$row['status']] = (int) $row['total'];
}

$totalSemuaLaporan = array_sum($statusCounts);

$perPage = 6;

$totalData = $laporanModel->countUserReports($filters);
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

$laporanList = $laporanModel->searchUserReports($filters, $perPage, $offset);

function user_report_badge_status($status)
{
    return 'status-badge status-' . $status;
}

function build_user_report_page_url($pageNumber)
{
    $params = $_GET;
    unset($params['ajax']);

    $params['page'] = $pageNumber;

    return url('riwayat-laporan.php?' . http_build_query($params));
}

function render_user_report_content($laporanList, $totalData, $page, $totalPages)
{
    ob_start();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 laporan-summary">
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

    <?php if (count($laporanList) === 0): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center p-5">
                <div class="empty-state mx-auto">
                    <strong>Data laporan tidak ditemukan</strong>
                    <p class="text-muted mb-3">
                        Tidak ada laporan yang cocok dengan filter atau pencarian.
                    </p>

                    <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary">
                        Buat Laporan Baru
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($laporanList as $laporan): ?>
                <div class="col-lg-6">
                    <div class="card report-card modern-report-card shadow-sm h-100">
                        <div class="report-image">
                            <img
                                src="<?= url('assets/uploads/laporan/' . $laporan['foto']); ?>"
                                alt="Foto laporan"
                            >

                            <span class="<?= user_report_badge_status($laporan['status']); ?> report-floating-badge">
                                <?= e(ucfirst($laporan['status'])); ?>
                            </span>
                        </div>

                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <div>
                                    <h5 class="fw-bold mb-1">
                                        <?= e($laporan['judul']); ?>
                                    </h5>

                                    <p class="text-muted small mb-0">
                                        Dikirim pada <?= date('d M Y H:i', strtotime($laporan['created_at'])); ?>
                                    </p>
                                </div>
                            </div>

                            <p class="text-muted mt-3 mb-3">
                                <?= e(mb_strimwidth($laporan['deskripsi'], 0, 130, '...')); ?>
                            </p>

                            <div class="report-location-box mb-3">
                                <span>Lokasi</span>
                                <strong><?= e($laporan['alamat'] ?: 'Tanpa alamat tambahan'); ?></strong>
                                <small>
                                    <?= e($laporan['latitude']); ?>, <?= e($laporan['longitude']); ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <?= render_status_stepper($laporan['status']); ?>
                            </div>

                            <div class="d-flex gap-2">
                                <a
                                    href="<?= url('detail-laporan.php?id=' . $laporan['id_laporan']); ?>"
                                    class="btn btn-primary w-100"
                                >
                                    Lihat Detail
                                </a>

                                <a
                                    href="https://www.google.com/maps?q=<?= e($laporan['latitude']); ?>,<?= e($laporan['longitude']); ?>"
                                    target="_blank"
                                    class="btn btn-outline-primary"
                                >
                                    Maps
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                        <a
                            class="page-link ajax-user-report-page"
                            href="<?= build_user_report_page_url($page - 1); ?>"
                            data-page="<?= $page - 1; ?>"
                        >
                            Sebelumnya
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php if ($startPage > 1): ?>
                        <li class="page-item">
                            <a
                                class="page-link ajax-user-report-page"
                                href="<?= build_user_report_page_url(1); ?>"
                                data-page="1"
                            >
                                1
                            </a>
                        </li>

                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a
                                class="page-link ajax-user-report-page"
                                href="<?= build_user_report_page_url($i); ?>"
                                data-page="<?= $i; ?>"
                            >
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
                            <a
                                class="page-link ajax-user-report-page"
                                href="<?= build_user_report_page_url($totalPages); ?>"
                                data-page="<?= $totalPages; ?>"
                            >
                                <?= $totalPages; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a
                            class="page-link ajax-user-report-page"
                            href="<?= build_user_report_page_url($page + 1); ?>"
                            data-page="<?= $page + 1; ?>"
                        >
                            Berikutnya
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}

$contentHtml = render_user_report_content($laporanList, $totalData, $page, $totalPages);

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    echo $contentHtml;
    exit;
}

require_once __DIR__ . '/layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Monitoring Progress</p>
                <h2 class="fw-bold mb-2">Riwayat Laporan Saya</h2>
                <p class="text-muted mb-0">
                    Cari, filter, dan pantau perkembangan laporan parkir liar yang sudah kamu kirim.
                </p>
            </div>

            <a href="<?= url('buat-laporan.php'); ?>" class="btn btn-primary">
                Buat Laporan Baru
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md">
                <button class="status-filter-card active" data-status="">
                    <span>Semua</span>
                    <strong><?= $totalSemuaLaporan; ?></strong>
                </button>
            </div>

            <div class="col-md">
                <button class="status-filter-card" data-status="menunggu">
                    <span>Menunggu</span>
                    <strong><?= $statusCounts['menunggu']; ?></strong>
                </button>
            </div>

            <div class="col-md">
                <button class="status-filter-card" data-status="diverifikasi">
                    <span>Diverifikasi</span>
                    <strong><?= $statusCounts['diverifikasi']; ?></strong>
                </button>
            </div>

            <div class="col-md">
                <button class="status-filter-card" data-status="diproses">
                    <span>Diproses</span>
                    <strong><?= $statusCounts['diproses']; ?></strong>
                </button>
            </div>

            <div class="col-md">
                <button class="status-filter-card" data-status="selesai">
                    <span>Selesai</span>
                    <strong><?= $statusCounts['selesai']; ?></strong>
                </button>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="GET" id="filterUserReportForm" class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Pencarian</label>
                        <input
                            type="text"
                            name="keyword"
                            id="keywordInput"
                            class="form-control"
                            value="<?= e($keyword); ?>"
                            placeholder="Cari judul, deskripsi, alamat..."
                        >
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="statusInput" class="form-select">
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
                            id="tanggalMulaiInput"
                            class="form-control"
                            value="<?= e($tanggalMulai); ?>"
                        >
                    </div>

                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Tanggal Selesai</label>
                        <input
                            type="date"
                            name="tanggal_selesai"
                            id="tanggalSelesaiInput"
                            class="form-control"
                            value="<?= e($tanggalSelesai); ?>"
                        >
                    </div>

                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            Filter
                        </button>

                        <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary w-100">
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="userReportLoader" class="ajax-loader d-none">
            Memuat riwayat laporan...
        </div>

        <div id="userReportContent" class="ajax-content">
            <?= $contentHtml; ?>
        </div>
    </div>
</section>

<?php

$extraScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filterUserReportForm');
    const content = document.getElementById('userReportContent');
    const loader = document.getElementById('userReportLoader');
    const resetBtn = document.getElementById('resetFilterBtn');
    const keywordInput = document.getElementById('keywordInput');
    const statusInput = document.getElementById('statusInput');
    const statusCards = document.querySelectorAll('.status-filter-card');

    let searchTimer = null;

    function cleanParams(params) {
        for (const [key, value] of [...params.entries()]) {
            if (String(value).trim() === '') {
                params.delete(key);
            }
        }

        return params;
    }

    function buildUrl(page = 1, ajax = false) {
        const params = new URLSearchParams(new FormData(form));

        cleanParams(params);
        params.set('page', page);

        if (ajax) {
            params.set('ajax', '1');
        }

        const query = params.toString();

        return window.location.pathname + (query ? '?' + query : '');
    }

    function updateStatusCards() {
        const currentStatus = statusInput.value;

        statusCards.forEach(function(card) {
            card.classList.toggle('active', card.dataset.status === currentStatus);
        });
    }

    async function loadReports(page = 1, pushState = true) {
        try {
            loader.classList.remove('d-none');
            content.classList.add('is-loading');

            const ajaxUrl = buildUrl(page, true);
            const cleanUrl = buildUrl(page, false);

            const response = await fetch(ajaxUrl, {
                headers: {
                    'X-Requested-With': 'fetch'
                }
            });

            if (!response.ok) {
                throw new Error('Gagal mengambil data laporan.');
            }

            const html = await response.text();

            content.innerHTML = html;

            if (pushState) {
                window.history.pushState({ page: page }, '', cleanUrl);
            }

            updateStatusCards();
        } catch (error) {
            content.innerHTML = `
                <div class="alert alert-danger">
                    Gagal memuat riwayat laporan. Silakan coba lagi.
                </div>
            `;
        } finally {
            loader.classList.add('d-none');
            content.classList.remove('is-loading');
        }
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        loadReports(1);
    });

    keywordInput.addEventListener('input', function() {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(function() {
            loadReports(1);
        }, 500);
    });

    ['statusInput', 'tanggalMulaiInput', 'tanggalSelesaiInput'].forEach(function(id) {
        const input = document.getElementById(id);

        input.addEventListener('change', function() {
            loadReports(1);
        });
    });

    resetBtn.addEventListener('click', function() {
        form.reset();
        loadReports(1);
    });

    statusCards.forEach(function(card) {
        card.addEventListener('click', function() {
            statusInput.value = card.dataset.status;
            loadReports(1);
        });
    });

    document.addEventListener('click', function(event) {
        const link = event.target.closest('.ajax-user-report-page');

        if (!link) {
            return;
        }

        event.preventDefault();

        const page = parseInt(link.dataset.page, 10);

        if (!page || link.closest('.page-item').classList.contains('disabled')) {
            return;
        }

        loadReports(page);
    });

    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);

        form.keyword.value = params.get('keyword') || '';
        form.status.value = params.get('status') || '';
        form.tanggal_mulai.value = params.get('tanggal_mulai') || '';
        form.tanggal_selesai.value = params.get('tanggal_selesai') || '';

        const page = parseInt(params.get('page') || '1', 10);

        updateStatusCards();
        loadReports(page, false);
    });

    updateStatusCards();
});
</script>
HTML;

require_once __DIR__ . '/layouts/footer.php';

?>