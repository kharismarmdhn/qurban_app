<?php
// qurban_app/functions/auth.php

/**
 * Kumpulan Fungsi Autentikasi Pengguna
 * Meliputi hashing password, verifikasi, cek status login, dan otorisasi akses.
 */

// Fungsi untuk meng-hash password menggunakan algoritma BCRYPT yang direkomendasikan
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Fungsi untuk memverifikasi password yang dimasukkan user dengan hash di database
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

// Fungsi untuk mengecek apakah user sudah login
function isUserLoggedIn() {
    // Mengecek apakah variabel sesi 'id_user' sudah diset
    return isset($_SESSION['id_user']);
}

// Fungsi untuk mendapatkan level ID user yang sedang login dari sesi
function getUserLevel() {
    return $_SESSION['level_id'] ?? null; // Mengembalikan null jika level_id tidak diset
}

// Fungsi untuk mengarahkan (redirect) user ke dashboard yang sesuai berdasarkan levelnya
function redirectToDashboard($level_id) {
    switch ($level_id) {
        case 1: // Administrator
            header("Location: admin/dashboard.php");
            break;
        case 2: // Panitia
            header("Location: panitia/dashboard.php");
            break;
        case 3: // Pengqurban
            header("Location: pengqurban/dashboard.php");
            break;
        case 4: // Warga
            header("Location: warga/dashboard.php");
            break;
        default: // Jika level tidak dikenali, arahkan kembali ke halaman login
            header("Location: index.php");
            break;
    }
    exit(); // Penting untuk menghentikan eksekusi script setelah redirect
}

// Fungsi untuk otorisasi akses halaman berdasarkan level pengguna
function authorizePage($allowed_levels) {
    // Jika user belum login, arahkan ke halaman login
    if (!isUserLoggedIn()) {
        header("Location: ../index.php"); // Path relatif ke halaman login utama
        exit();
    }

    // Jika level user yang login tidak ada dalam daftar level yang diizinkan
    if (!in_array(getUserLevel(), $allowed_levels)) {
        // Arahkan user ke dashboard mereka sendiri (akses ditolak)
        redirectToDashboard(getUserLevel());
        exit();
    }
}
?>