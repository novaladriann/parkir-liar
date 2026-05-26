<?php
require_once __DIR__ . '/../../app/config/app.php';
require_once __DIR__ . '/../../app/helpers/url.php';
require_once __DIR__ . '/../../app/helpers/session.php';

$user = current_user();
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <link href="<?= url('assets/css/style.css'); ?>" rel="stylesheet">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand text-primary" href="<?= url('index.php'); ?>">
                ParkirLiar
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('index.php'); ?>">Beranda</a>
                    </li>

                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link"
                                href="<?= $user['role'] === 'admin' ? url('admin/dashboard.php') : url('dashboard.php'); ?>">
                                Dashboard
                            </a>
                        </li>

                        <?php if ($user['role'] === 'masyarakat'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= url('buat-laporan.php'); ?>">Buat Laporan</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="<?= url('riwayat-laporan.php'); ?>">Riwayat</a>
                            </li>
                        <?php endif; ?>

                        <?php if ($user['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= url('admin/laporan.php'); ?>">Kelola Laporan</a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <span class="nav-link text-muted">
                                <?= e($user['nama']); ?>
                            </span>
                        </li>

                        <li class="nav-item">
                            <a class="btn btn-outline-danger btn-sm px-3" href="<?= url('logout.php'); ?>">
                                Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('login.php'); ?>">Login</a>
                        </li>

                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm px-3" href="<?= url('register.php'); ?>">
                                Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>