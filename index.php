<?php
// qurban_app/index.php
// Halaman utama publik, akan langsung mengarahkan ke halaman login atau dashboard yang sesuai

// Memulai session PHP
session_start();

// Sertakan file koneksi database (opsional di sini, tapi baik jika ada logging)
include 'config/db.php';
// Sertakan file untuk pengecekan otentikasi (fungsi isLoggedIn dan hasRole)
include 'config/auth_check.php';

// Cek apakah user sudah login
if (isLoggedIn()) {
    // Jika sudah login, redirect ke dashboard yang sesuai level (prioritas)
    if (hasRole('admin')) {
        header('Location: admin/index.php');
    } elseif (hasRole('panitia')) {
        header('Location: panitia/index.php');
    } elseif (hasRole('pengqurban')) {
        header('Location: pengqurban/index.php');
    } elseif (hasRole('warga')) {
        header('Location: warga/index.php');
    } else {
        // Jika tidak ada peran yang valid, arahkan ke login
        header('Location: auth/login.php');
    }
} else {
    // Jika belum login, arahkan ke halaman login
    header('Location: auth/login.php');
}
exit(); // Penting untuk menghentikan eksekusi script setelah redirect
?>