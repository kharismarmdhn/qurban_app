<?php
// qurban_app/config/db.php
// File ini berisi konfigurasi dan inisialisasi koneksi ke database

$host = 'localhost';        // Host database Anda (biasanya localhost)
$db_name = 'db_qurban107';  // Nama database yang Anda berikan sebelumnya
$username = 'root';         // Username database Anda (ganti jika bukan 'root')
$password = '';             // Password database Anda (ganti jika ada)

try {
    // Membuat objek PDO (PHP Data Objects) untuk koneksi database
    $conn = new PDO("mysql:host={$host};dbname={$db_name}", $username, $password);

    // Mengatur mode error agar PDO melempar Exception saat ada error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Menampilkan pesan sukses (opsional, hapus setelah testing)
    // echo "Koneksi database berhasil!";

} catch (PDOException $e) {
    // Menangkap error jika koneksi gagal
    // Hentikan eksekusi script dan tampilkan pesan error
    die("Koneksi database gagal: " . $e->getMessage());
}
