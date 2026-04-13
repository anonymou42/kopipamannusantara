<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($username) || empty($password)) {
    header('Location: index.php?error=Username dan password harus diisi');
    exit;
}

if (strlen($username) < 3 || strlen($password) < 6) {
    header('Location: index.php?error=Username minimal 3 karakter dan password minimal 6 karakter');
    exit;
}

try {
    // Cek user di database menggunakan prepared statement
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

            // Ganti redirect login:
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Tambah role
            $_SESSION['logged_in'] = true;
            
            // Redirect berdasarkan role
            if ($user['role'] === 'sales') {
                header('Location: dashboard_sales.php');
            } elseif ($user['role'] === 'admin') {
                header('Location: dashboard_admin.php');
            }
             else {
                header('Location: warning.php');
            }
            exit;
        } else {
        // 3. JIKA USER TIDAK DITEMUKAN ATAU PASSWORD SALAH
        header('Location: index.php?error=Username atau password salah');
        exit;
    }

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    header('Location: index.php?error=Terjadi kesalahan server');
    exit;
}
?>