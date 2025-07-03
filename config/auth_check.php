<?php
// qurban_app/config/auth_check.php
// File ini berisi fungsi-fungsi untuk pengecekan otentikasi (login) dan otorisasi (level akses)
// Disesuaikan untuk NIK sebagai primary key user dan kolom 'level' (string terpisah koma)

// Fungsi untuk memeriksa apakah user sudah login
function isLoggedIn() {
    // Memulai session jika belum dimulai
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // Mengembalikan true jika 'user_nik' ada di session, menandakan user sudah login
    return isset($_SESSION['user_nik']);
}

// Fungsi untuk memeriksa apakah user memiliki peran tertentu
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false; // Tidak punya peran jika belum login
    }

    $user_levels = explode(',', $_SESSION['user_level']); // Pecah string level menjadi array
    return in_array($required_role, $user_levels); // Cek apakah peran yang dibutuhkan ada di array level user
}

// Fungsi untuk memeriksa level akses user untuk halaman tertentu
function checkUserAccess($allowed_roles = []) {
    // Memastikan session sudah dimulai
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Jika user belum login, redirect ke halaman login
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }

    // Jika user adalah 'admin', dia punya akses ke mana saja (override)
    if (hasRole('admin')) {
        return true;
    }

    // Jika bukan admin, cek apakah user memiliki setidaknya satu peran yang diizinkan
    $has_access = false;
    foreach ($allowed_roles as $role) {
        if (hasRole($role)) {
            $has_access = true;
            break;
        }
    }

    if (!$has_access) {
        // Jika tidak punya akses, redirect ke dashboard yang sesuai atau halaman unauthorized
        // Prioritas redirect: admin > panitia > pengqurban > warga
        if (hasRole('admin')) {
            header('Location: ../admin/index.php');
        } elseif (hasRole('panitia')) {
            header('Location: ../panitia/index.php');
        } elseif (hasRole('pengqurban')) {
            header('Location: ../pengqurban/index.php');
        } elseif (hasRole('warga')) {
            header('Location: ../warga/index.php');
        } else {
            // Jika tidak ada peran yang sesuai, atau peran tidak dikenal
            header('Location: ../auth/login.php');
        }
        exit();
    }
}
?>