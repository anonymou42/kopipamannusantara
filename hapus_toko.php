<?php
session_start();
require_once 'koneksi.php';

// Cek Login
if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

// PROTEKSI: Cek apakah user adalah admin
if ($_SESSION['role'] !== 'admin') {
    // Jika sales mencoba akses, tendang balik
    header('Location: dashboard_admin.php?error=Hanya Admin yang diizinkan menghapus data!');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // 1. Ambil nama file foto untuk dihapus dari folder storage
        $stmt_foto = $pdo->prepare("SELECT foto_toko FROM toko WHERE id = ?");
        $stmt_foto->execute([$id]);
        $data = $stmt_foto->fetch();
        
        if ($data && $data['foto_toko']) {
            // Jika path foto tersimpan lengkap, hapus filenya
            if (file_exists($data['foto_toko'])) {
                unlink($data['foto_toko']);
            }
        }

        // 2. Hapus data dari database
        $stmt = $pdo->prepare("DELETE FROM toko WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: dashboard_admin.php?success=Toko telah berhasil dihapus');
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header('Location: dashboard_admin.php');
}
exit;