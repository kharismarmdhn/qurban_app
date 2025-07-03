<?php
// qurban_app/pengqurban/index.php
// Dashboard utama untuk Pengqurban
// Disesuaikan untuk skema database baru (NIK sebagai PK, detail profil di tabel users, kolom 'level' string)

session_start();

include '../config/db.php';
include '../config/auth_check.php';

// Pastikan hanya pengqurban ('pengqurban') yang bisa mengakses halaman ini
// checkUserLevel(3); // BARIS INI MENYEBABKAN ERROR
checkUserAccess(['pengqurban']); // UBAH KE FUNGSI YANG BENAR

// Ambil data pengqurban yang sedang login (sekarang langsung dari tabel users)
$pengqurban_nik_login = $_SESSION['user_nik'] ?? null;
$nama_pengqurban_display = $_SESSION['user_nm_lengkap'] ?? $_SESSION['username'] ?? 'Pengqurban';

$total_iuran_lunas = 0;
$total_iuran_belum_lunas = 0;
$jumlah_hewan_diqurbankan = 0;

if ($pengqurban_nik_login) {
    try {
        // Total iuran lunas
        $stmt_lunas = $conn->prepare("SELECT SUM(jumlah_iuran) AS total FROM iuran_pengqurban WHERE NIK_pengqurban = :nik_pengqurban AND status_bayar = 'lunas'");
        $stmt_lunas->bindParam(':nik_pengqurban', $pengqurban_nik_login);
        $stmt_lunas->execute();
        $total_iuran_lunas = $stmt_lunas->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Total iuran belum lunas
        $stmt_belum_lunas = $conn->prepare("SELECT SUM(jumlah_iuran) AS total FROM iuran_pengqurban WHERE NIK_pengqurban = :nik_pengqurban AND status_bayar = 'belum_lunas'");
        $stmt_belum_lunas->bindParam(':nik_pengqurban', $pengqurban_nik_login);
        $stmt_belum_lunas->execute();
        $total_iuran_belum_lunas = $stmt_belum_lunas->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Jumlah hewan yang diiur oleh pengqurban ini
        $stmt_hewan_iur = $conn->prepare("SELECT COUNT(DISTINCT id_hewanqurban) AS total FROM iuran_pengqurban WHERE NIK_pengqurban = :nik_pengqurban");
        $stmt_hewan_iur->bindParam(':nik_pengqurban', $pengqurban_nik_login);
        $stmt_hewan_iur->execute();
        $jumlah_hewan_diqurbankan = $stmt_hewan_iur->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    } catch (PDOException $e) {
        error_log("Error fetching pengqurban dashboard data: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Dashboard Pengqurban</title>
</head>
<body>
    <div id="wrapper">
        <?php include 'templates/sidebar.php'; ?>
        <div id="page-wrapper" class="gray-bg">
            <?php include 'templates/top_navbar.php'; ?>

            <div class="wrapper wrapper-content animated fadeInRight">
                <div class="row">
                    <div class="col-lg-12">
                        <h2>Halo, Pengqurban <?php echo htmlspecialchars($nama_pengqurban_display); ?>! Selamat datang di Dashboard Pengqurban.</h2>
                        <p>Anda dapat melihat status iuran qurban Anda di sini.</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-primary pull-right">Total</span>
                                <h5>Iuran Lunas</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">Rp <?php echo number_format($total_iuran_lunas, 0, ',', '.'); ?></h1>
                                <small>Jumlah iuran yang sudah lunas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-warning pull-right">Total</span>
                                <h5>Iuran Belum Lunas</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">Rp <?php echo number_format($total_iuran_belum_lunas, 0, ',', '.'); ?></h1>
                                <small>Jumlah iuran yang belum lunas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-info pull-right">Total</span>
                                <h5>Hewan Diqurbankan</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins"><?php echo number_format($jumlah_hewan_diqurbankan, 0, ',', '.'); ?></h1>
                                <small>Jenis hewan yang diiur</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row m-t-lg">
                    <div class="col-lg-12 text-center">
                        <a href="status_iuran.php" class="btn btn-success btn-lg">
                            <i class="fa fa-list"></i> Lihat Detail Status Iuran
                        </a>
                    </div>
                </div>

            </div>

            <?php include 'templates/footer.php'; ?>
        </div>
    </div>

    <?php include 'templates/scripts.php'; ?>

</body>
</html>