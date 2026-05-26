<?php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';

$userModel = new User($pdo);

$nama = 'Administrator';
$email = 'admin@parkirliar.test';
$password = 'admin123';

$existingAdmin = $userModel->findByEmail($email);

if ($existingAdmin) {
    echo "Akun admin sudah ada.<br>";
    echo "Email: " . $email;
    exit;
}

$userModel->create($nama, $email, $password, 'admin');

echo "Akun admin berhasil dibuat.<br>";
echo "Email: " . $email . "<br>";
echo "Password: " . $password . "<br>";
echo "<strong>Hapus file create-admin.php setelah akun berhasil dibuat.</strong>";