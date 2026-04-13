<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php?error=Akses tidak diizinkan');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validasi input
if (empty($username) || empty($password) || empty($confirm_password)) {
    header('Location: register.php?error=Semua field wajib diisi');
    exit;
}

if ($password !== $confirm_password) {
    header('Location: register.php?error=Password konfirmasi tidak cocok');
    exit;
}

if (strlen($username) < 3 || strlen($username) > 50) {
    header('Location: register.php?error=Username harus 3-50 karakter');
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    header('Location: register.php?error=Username hanya boleh huruf, angka, dan underscore');
    exit;
}

if (strlen($password) < 8) {
    header('Location: register.php?error=Password minimal 8 karakter');
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=Format email tidak valid');
    exit;
}

try {
    // Cek apakah username sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        header('Location: register.php?error=Username sudah terdaftar');
        exit;
    }

    // Cek apakah email sudah ada (jika diisi)
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: register.php?error=Email sudah terdaftar');
            exit;
        }
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user baru
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'null')");
    $success = $stmt->execute([$username, $email ?: null, $hashed_password]);

    if ($success) {
        header('Location: index.php?success=Registrasi berhasil! Silakan login');
        exit;
    } else {
        header('Location: register.php?error=Gagal membuat akun');
        exit;
    }

} catch (PDOException $e) {
    error_log("Register error: " . $e->getMessage());
    header('Location: register.php?error=Terjadi kesalahan server');
    exit;
}
?>