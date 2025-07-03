<?php
// qurban_app/functions/data.php

/**
 * Kumpulan Fungsi Pembantu Umum (Helper Functions)
 * Untuk sanitasi input, format data, dll.
 */

// Fungsi untuk membersihkan input dari user
// Mencegah XSS (Cross-Site Scripting) dan membersihkan whitespace.
function sanitizeInput($data) {
    $data = trim($data); // Menghapus spasi di awal dan akhir string
    $data = stripslashes($data); // Menghapus backslashes
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Mengubah karakter khusus menjadi entitas HTML
    return $data;
}

// Fungsi untuk format angka menjadi format mata uang Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Anda bisa menambahkan fungsi-fungsi umum lain di sini sesuai kebutuhan, misalnya:
// function getJenisKelaminText($kode_jk) {
//     return ($kode_jk == 'L') ? 'Laki-laki' : 'Perempuan';
// }

?>