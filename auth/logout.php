<?php
// qurban_app/auth/logout.php
// File untuk proses logout pengguna

session_start();        // Memulai session
session_unset();        // Menghapus semua variabel session
session_destroy();      // Menghancurkan session

// Redirect ke halaman login setelah logout
header('Location: login.php');
exit();
?>