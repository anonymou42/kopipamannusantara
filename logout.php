<?php
session_start();

// Hapus semua session data
$_SESSION = array();
session_destroy();

// Redirect ke halaman login
header('Location: index.php?success=Anda telah logout');
exit;
?>