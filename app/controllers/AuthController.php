<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/url.php';
require_once __DIR__ . '/../helpers/csrf.php';

class AuthController
{
    public static function register()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('register.php'));
            exit;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Token keamanan tidak valid.');
            header('Location: ' . url('register.php'));
            exit;
        }

        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($nama === '' || $email === '' || $password === '') {
            set_flash('error', 'Semua field wajib diisi.');
            header('Location: ' . url('register.php'));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Format email tidak valid.');
            header('Location: ' . url('register.php'));
            exit;
        }

        if (strlen($password) < 6) {
            set_flash('error', 'Password minimal 6 karakter.');
            header('Location: ' . url('register.php'));
            exit;
        }

        if ($password !== $passwordConfirm) {
            set_flash('error', 'Konfirmasi password tidak sesuai.');
            header('Location: ' . url('register.php'));
            exit;
        }

        $userModel = new User($pdo);

        if ($userModel->findByEmail($email)) {
            set_flash('error', 'Email sudah terdaftar.');
            header('Location: ' . url('register.php'));
            exit;
        }

        $userModel->create($nama, $email, $password, 'masyarakat');

        set_flash('success', 'Registrasi berhasil. Silakan login.');
        header('Location: ' . url('login.php'));
        exit;
    }

    public static function login()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('login.php'));
            exit;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('error', 'Token keamanan tidak valid.');
            header('Location: ' . url('login.php'));
            exit;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            set_flash('error', 'Email dan password wajib diisi.');
            header('Location: ' . url('login.php'));
            exit;
        }

        $userModel = new User($pdo);
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            set_flash('error', 'Email atau password salah.');
            header('Location: ' . url('login.php'));
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id_user' => $user['id_user'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        set_flash('success', 'Login berhasil.');

        header('Location: ' . url('dashboard.php'));
        exit;
    }

    public static function logout()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        session_start();

        set_flash('success', 'Anda berhasil logout.');
        header('Location: ' . url('login.php'));
        exit;
    }
}