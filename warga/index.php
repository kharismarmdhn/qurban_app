<?php
// qurban_app/warga/index.php
// Dashboard utama untuk Warga - Menampilkan QR Code Pribadi dan Status Pengambilan Daging.
// Final: Tampilan QR "kupon" dan AJAX polling untuk update otomatis setelah panitia scan.

session_start();

// Definisikan ROOT_PATH proyek Anda
define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya warga ('warga') yang bisa mengakses halaman ini
checkUserAccess(['warga']); 

$warga_nik_login = $_SESSION['user_nik'] ?? null;
$nama_warga_display = $_SESSION['user_nm_lengkap'] ?? $_SESSION['username'] ?? 'Warga';

$distribusi_data_utama = null; // Data alokasi daging yang paling relevan untuk ditampilkan QR
$error_message = '';
$success_message = ''; 

if ($warga_nik_login) {
    try {
        // Ambil data alokasi daging yang BELUM DIAMBIL untuk warga ini (prioritas pertama)
        // Jika tidak ada yang belum diambil, ambil yang statusnya sudah diambil untuk ditampilkan sebagai riwayat
        $stmt_distribusi = $conn->prepare("
            SELECT 
                id_distribusi,
                NIK_penerima,
                jumlah_daging_sapi,
                jumlah_daging_kambing,
                tgl_distribusi,
                kd_qr,
                status_pengambilan
            FROM 
                distribusi_daging
            WHERE 
                NIK_penerima = :nik_warga
            ORDER BY
                FIELD(status_pengambilan, 'belum_diambil', 'sudah_diambil') ASC, -- Prioritaskan 'belum_diambil'
                id_distribusi DESC -- Ambil yang terbaru jika status sama
            LIMIT 1 
        ");
        $stmt_distribusi->bindParam(':nik_warga', $warga_nik_login);
        $stmt_distribusi->execute();
        $distribusi_data_utama = $stmt_distribusi->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching warga dashboard data: " . $e->getMessage());
        $error_message = "Terjadi kesalahan saat mengambil data alokasi Anda. Silakan coba lagi nanti.";
    }
} else {
    $error_message = "Data warga Anda tidak ditemukan. Harap hubungi admin.";
}

// Data untuk JavaScript AJAX Polling
$js_distribusi_id = $distribusi_data_utama['id_distribusi'] ?? 'null';
$js_warga_nik = $distribusi_data_utama['NIK_penerima'] ?? 'null';
$js_initial_status = $distribusi_data_utama['status_pengambilan'] ?? 'null';

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Pengambilan Daging</title>
    <style>
        .qr-section-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px; 
            margin: 0 auto; 
            text-align: center;
        }
        .qr-code-img {
            width: 100%;
            max-width: 400px; 
            height: auto;
            border: 1px solid #eee;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        .status-belum-diambil { background-color: #f8ac59; color: #fff; } 
        .status-sudah_diambil { background-color: #1ab394; color: #fff; } 
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'templates/sidebar.php'; ?>
        <div id="page-wrapper" class="gray-bg">
            <?php include 'templates/top_navbar.php'; ?>

            <div class="wrapper wrapper-content animated fadeInRight">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <h5>QR Code Pengambilan Daging Qurban Anda</h5>
                                <div class="ibox-tools">
                                    <a class="collapse-link">
                                        <i class="fa fa-chevron-up"></i>
                                    </a>
                                    <a class="close-link">
                                        <i class="fa fa-times"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="ibox-content">
                                <?php if (!empty($success_message)): ?>
                                    <div class="alert alert-success alert-dismissable">
                                        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                        <?php echo $success_message; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger alert-dismissable">
                                        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$distribusi_data_utama): // Jika belum ada alokasi sama sekali ?>
                                    <div class="alert alert-info">Belum ada alokasi daging qurban untuk Anda. Mohon tunggu informasi selanjutnya.</div>
                                    <a href="../auth/logout.php" class="btn btn-primary m-t-md">Logout</a>
                                <?php else: // Jika ada alokasi (baik belum atau sudah diambil) ?>
                                    <div class="qr-section-container">
                                        <h2>QR Code Pengambilan Daging Anda</h2>
                                        <p class="m-t-md">Tunjukkan QR Code ini kepada panitia.</p>
                                        
                                        <div id="qr_display_area">
                                            <?php if ($distribusi_data_utama['status_pengambilan'] == 'belum_diambil'): ?>
                                                <img id="qr_code_image" src="<?php echo '../qrcodes/' . htmlspecialchars($distribusi_data_utama['kd_qr']); ?>" alt="QR Code Pengambilan Daging" class="qr-code-img">
                                                <p class="status-badge status-belum-diambil" id="status_text_display">Status: Belum Diambil</p>
                                                <p class="text-muted m-t-sm">Kode ini akan hilang setelah daging diambil.</p>
                                            <?php else: // status_pengambilan == 'sudah_diambil' ?>
                                                <img src="../assets/img/check_mark.png" alt="Daging Sudah Diambil" class="qr-code-img" style="max-width: 200px; display: block; margin: 0 auto 20px; "> 
                                                <p class="status-badge status-sudah-diambil" id="status_text_display">Status: Sudah Diambil</p>
                                                <p class="text-muted m-t-sm">Terima kasih, daging qurban Anda sudah berhasil diambil.</p>
                                                <a href="../auth/logout.php" class="btn btn-primary m-t-md">Logout</a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <hr>
                                        <p>Alokasi Daging Anda:</p>
                                        <p>Sapi: **<?php echo number_format($distribusi_data_utama['jumlah_daging_sapi'], 2, ',', '.'); ?> Kg**</p>
                                        <p>Kambing: **<?php echo number_format($distribusi_data_utama['jumlah_daging_kambing'], 2, ',', '.'); ?> Kg**</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'templates/footer.php'; ?>
        </div>
    </div>

    <?php include 'templates/scripts.php'; ?>

    <script>
        $(document).ready(function() {
            // Data untuk AJAX Polling (diperlukan untuk warga)
            var distribusiId = <?php echo $js_distribusi_id; ?>;
            var wargaNik = '<?php echo $js_warga_nik; ?>';
            var currentStatus = '<?php echo $js_initial_status; ?>';
            var pollingInterval;

            // Hanya lakukan polling jika ada alokasi dan statusnya 'belum_diambil'
            if (distribusiId !== 'null' && currentStatus === 'belum_diambil') {
                pollingInterval = setInterval(function() {
                    checkQrStatus();
                }, 3000); // Cek setiap 3 detik (lebih responsif)
            }

            function checkQrStatus() {
                $.ajax({
                    url: '../api/check_distribusi_status.php', // API endpoint untuk cek status
                    method: 'GET',
                    data: { 
                        id_distribusi: distribusiId, 
                        nik: wargaNik 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success' && response.data.status_pengambilan === 'sudah_diambil') {
                            // Hentikan polling
                            clearInterval(pollingInterval);
                            // Ubah tampilan QR menjadi pesan sudah diambil (HTML diganti)
                            $('#qr_display_area').html( 
                                '<img src="../assets/img/check_mark.png" alt="Daging Sudah Diambil" class="qr-code-img" style="max-width: 200px; display: block; margin: 0 auto 20px; ">' + 
                                '<p class="status-badge status-sudah-diambil" id="status_text_display">Status: Sudah Diambil</p>' +
                                '<p class="text-muted m-t-sm">Terima kasih, daging qurban Anda sudah berhasil diambil.</p>' +
                                '<a href="../auth/logout.php" class="btn btn-primary m-t-md">Logout</a>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error checking QR status: ", error);
                    }
                });
            }
        });
    </script>
</body>
</html>