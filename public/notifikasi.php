<?php

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Notification.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/session.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/csrf.php';

require_role('masyarakat');

$user = current_user();

$notificationModel = new Notification($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Token keamanan tidak valid.');
        header('Location: ' . url('notifikasi.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_all_read') {
        $notificationModel->markAllAsRead($user['id_user']);
        set_flash('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    if ($action === 'mark_read') {
        $idNotifikasi = $_POST['id_notifikasi'] ?? '';

        if (ctype_digit($idNotifikasi)) {
            $notificationModel->markAsRead($idNotifikasi, $user['id_user']);
            set_flash('success', 'Notifikasi ditandai sudah dibaca.');
        }
    }

    header('Location: ' . url('notifikasi.php'));
    exit;
}

$notifications = $notificationModel->findByUser($user['id_user']);
$unreadCount = $notificationModel->countUnreadByUser($user['id_user']);

require_once __DIR__ . '/layouts/header.php';

$success = get_flash('success');
$error = get_flash('error');

?>

<section class="py-5">
    <div class="container">
        <div class="page-header mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Notifikasi</p>
                <h2 class="fw-bold mb-2">Pembaruan Laporan</h2>
                <p class="text-muted mb-0">
                    Lihat informasi terbaru terkait perubahan status laporan parkir liar.
                </p>
            </div>

            <form method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="mark_all_read">

                <button 
                    type="submit" 
                    class="btn btn-primary"
                    <?= $unreadCount === 0 ? 'disabled' : ''; ?>
                >
                    Tandai Semua Dibaca
                </button>
            </form>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Daftar Notifikasi</h5>

                    <span class="badge bg-primary rounded-pill">
                        <?= $unreadCount; ?> belum dibaca
                    </span>
                </div>

                <?php if (count($notifications) === 0): ?>
                    <div class="empty-state">
                        <strong>Belum ada notifikasi</strong>
                        <p class="text-muted mb-0">
                            Pembaruan laporan akan muncul di sini.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="notification-list">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item <?= $notif['is_read'] ? '' : 'unread'; ?>">
                                <div class="notification-icon">
                                    <?= $notif['is_read'] ? '✓' : '!' ?>
                                </div>

                                <div class="notification-content">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <h6 class="fw-bold mb-1">
                                                <?= e($notif['judul']); ?>
                                            </h6>

                                            <p class="text-muted mb-2">
                                                <?= e($notif['pesan']); ?>
                                            </p>

                                            <small class="text-muted">
                                                <?= date('d M Y H:i', strtotime($notif['created_at'])); ?>
                                            </small>
                                        </div>

                                        <div class="notification-actions">
                                            <?php if ($notif['id_laporan']): ?>
                                                <a 
                                                    href="<?= url('detail-laporan.php?id=' . $notif['id_laporan']); ?>" 
                                                    class="btn btn-outline-primary btn-sm"
                                                >
                                                    Detail
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!$notif['is_read']): ?>
                                                <form method="POST" class="mt-2">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <input 
                                                        type="hidden" 
                                                        name="id_notifikasi" 
                                                        value="<?= e($notif['id_notifikasi']); ?>"
                                                    >

                                                    <button type="submit" class="btn btn-light btn-sm w-100">
                                                        Dibaca
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>