<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/url.php';

function require_login()
{
    if (!is_logged_in()) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        header('Location: ' . url('login.php'));
        exit;
    }
}

function require_guest()
{
    if (is_logged_in()) {
        header('Location: ' . url('dashboard.php'));
        exit;
    }
}

function require_role($role)
{
    require_login();

    $user = current_user();

    if (!$user || $user['role'] !== $role) {
        set_flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        header('Location: ' . url('dashboard.php'));
        exit;
    }
}

function is_admin()
{
    return is_logged_in() && current_user()['role'] === 'admin';
}

function is_masyarakat()
{
    return is_logged_in() && current_user()['role'] === 'masyarakat';
}