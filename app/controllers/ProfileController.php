<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/url.php';
require_once __DIR__ . '/../helpers/csrf.php';

class ProfileController
{
    public static function updateProfile()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('profil.php'));
            exit;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Token keamanan tidak valid.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        $user = current_user();

        if (!$user) {
            set_flash('error', 'Silakan login terlebih dahulu.');
            header('Location: ' . url('login.php'));
            exit;
        }

        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($nama === '' || $email === '') {
            set_flash('error', 'Nama dan email wajib diisi.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        if (strlen($nama) < 3) {
            set_flash('error', 'Nama minimal 3 karakter.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Format email tidak valid.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        $userModel = new User($pdo);

        if ($userModel->findByEmailExceptId($email, $user['id_user'])) {
            set_flash('error', 'Email sudah digunakan oleh akun lain.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        $userModel->updateProfile($user['id_user'], $nama, $email);

        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['email'] = $email;

        set_flash('success', 'Profil berhasil diperbarui.');
        header('Location: ' . url('profil.php'));
        exit;
    }

    public static function updatePassword()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('profil.php'));
            exit;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Token keamanan tidak valid.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        $user = current_user();

        if (!$user) {
            set_flash('error', 'Silakan login terlebih dahulu.');
            header('Location: ' . url('login.php'));
            exit;
        }

        $passwordLama = $_POST['password_lama'] ?? '';
        $passwordBaru = $_POST['password_baru'] ?? '';
        $passwordKonfirmasi = $_POST['password_konfirmasi'] ?? '';

        if ($passwordLama === '' || $passwordBaru === '' || $passwordKonfirmasi === '') {
            set_flash('error', 'Semua field password wajib diisi.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        if (strlen($passwordBaru) < 6) {
            set_flash('error', 'Password baru minimal 6 karakter.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        if ($passwordBaru !== $passwordKonfirmasi) {
            set_flash('error', 'Konfirmasi password baru tidak sesuai.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        $userModel = new User($pdo);
        $userData = $userModel->findById($user['id_user']);

        if (!$userData || !password_verify($passwordLama, $userData['password'])) {
            set_flash('error', 'Password lama salah.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        if (password_verify($passwordBaru, $userData['password'])) {
            set_flash('error', 'Password baru tidak boleh sama dengan password lama.');
            header('Location: ' . url('profil.php'));
            exit;
        }

        $userModel->updatePassword($user['id_user'], $passwordBaru);

        set_flash('success', 'Password berhasil diperbarui.');
        header('Location: ' . url('profil.php'));
        exit;
    }
}