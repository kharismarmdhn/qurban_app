<?php
// qurban_app/functions/qr_generator.php

/**
 * Fungsi untuk Menghasilkan QR Code
 * Menggunakan Google Charts API untuk kemudahan dan tidak memerlukan library PHP tambahan.
 */

function generateQRCodeUrl($data_to_encode) {
    // URL dasar Google Charts API untuk QR Code
    // Parameter:
    // cht=qr: Chart type adalah QR code
    // chs=200x200: Ukuran gambar QR code (lebar x tinggi dalam piksel)
    // chl=<data>: Data yang akan di-encode ke dalam QR code
    // choe=UTF-8: Encoding karakter (UTF-8)

    return "https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=" . urlencode($data_to_encode) . "&choe=UTF-8";
}

// Jika Anda ingin mengimplementasikan generator QR code di sisi server tanpa API eksternal,
// Anda perlu menginstal library PHP QR Code (misal: "chillerlan/php-qrcode")
// dan kode di sini akan lebih kompleks. Untuk PHP Native sederhana, API eksternal lebih mudah.
?>