<?php
// qurban_app/admin/index.php
// Ini adalah halaman Dashboard utama untuk Administrator
// Disesuaikan untuk skema database baru (NIK sebagai PK, level tunggal)

session_start();

define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

// --- Logika untuk mengambil data ringkasan dashboard dari database ---
$total_pengqurban = 0;
$total_warga = 0;
$total_panitia = 0; 
$total_hewan_sapi = 0;
$total_hewan_kambing = 0;
$total_pemasukan = 0;
$total_pengeluaran = 0;
$saldo_kas = 0;
$total_daging_sapi_distribusi = 0;
$total_daging_kambing_distribusi = 0;

try {
    // Total Pengqurban (user dengan level 'pengqurban')
    $stmt_pengqurban = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE level = 'pengqurban'");
    $stmt_pengqurban->execute();
    $total_pengqurban = $stmt_pengqurban->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total Warga (user dengan level 'warga')
    $stmt_warga = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE level = 'warga'");
    $stmt_warga->execute();
    $total_warga = $stmt_warga->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total Panitia (user dengan level 'panitia')
    $stmt_panitia = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE level = 'panitia'");
    $stmt_panitia->execute();
    $total_panitia = $stmt_panitia->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total Hewan Sapi
    $stmt_sapi = $conn->prepare("SELECT COUNT(*) AS total FROM hewan_qurban WHERE jenis_hewan = 'sapi'");
    $stmt_sapi->execute();
    $total_hewan_sapi = $stmt_sapi->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total Hewan Kambing
    $stmt_kambing = $conn->prepare("SELECT COUNT(*) AS total FROM hewan_qurban WHERE jenis_hewan = 'kambing'");
    $stmt_kambing->execute();
    $total_hewan_kambing = $stmt_kambing->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total Pemasukan
    $stmt_pemasukan_total = $conn->prepare("SELECT SUM(jumlah) AS total FROM transaksi_keuangan WHERE tipe_transaksi = 'Pemasukan'");
    $stmt_pemasukan_total->execute();
    $total_pemasukan = $stmt_pemasukan_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total Pengeluaran
    $stmt_pengeluaran_total = $conn->prepare("SELECT SUM(jumlah) AS total FROM transaksi_keuangan WHERE tipe_transaksi = 'Pengeluaran'");
    $stmt_pengeluaran_total->execute();
    $total_pengeluaran = $stmt_pengeluaran_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Saldo Kas
    $saldo_kas = $total_pemasukan - $total_pengeluaran;

    // Total daging sapi terdistribusi
    $stmt_daging_sapi_distribusi = $conn->prepare("SELECT SUM(jumlah_daging_sapi) AS total FROM distribusi_daging WHERE status_pengambilan = 'sudah_diambil'");
    $stmt_daging_sapi_distribusi->execute();
    $total_daging_sapi_distribusi = $stmt_daging_sapi_distribusi->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total daging kambing terdistribusi
    $stmt_daging_kambing_distribusi = $conn->prepare("SELECT SUM(jumlah_daging_kambing) AS total FROM distribusi_daging WHERE status_pengambilan = 'sudah_diambil'");
    $stmt_daging_kambing_distribusi->execute();
    $total_daging_kambing_distribusi = $stmt_daging_kambing_distribusi->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


} catch (PDOException $e) {
    error_log("Database Error in admin/index.php (summary): " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Dashboard Admin</title>
</head>
<body>
    <div id="wrapper">
        <?php include 'templates/sidebar.php'; ?>
        <div id="page-wrapper" class="gray-bg">
            <?php include 'templates/top_navbar.php'; ?>

            <div class="wrapper wrapper-content">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-success pull-right">Total</span>
                                <h5>Pengqurban</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins"><?php echo number_format($total_pengqurban, 0, ',', '.'); ?></h1>
                                <small>Orang Pengqurban</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-info pull-right">Total</span>
                                <h5>Warga Terdaftar</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins"><?php echo number_format($total_warga, 0, ',', '.'); ?></h1>
                                <small>Warga RT 001 Terdaftar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-warning pull-right">Total</span>
                                <h5>Panitia Terdaftar</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins"><?php echo number_format($total_panitia, 0, ',', '.'); ?></h1>
                                <small>Orang Panitia</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-danger pull-right">Total</span>
                                <h5>Hewan Qurban</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins"><?php echo number_format($total_hewan_sapi, 0, ',', '.') . ' Sapi, ' . number_format($total_hewan_kambing, 0, ',', '.') . ' Kambing'; ?></h1>
                                <small>Ekor Sapi dan Kambing</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-success pull-right">Total</span>
                                <h5>Pemasukan</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></h1>
                                <small>Keseluruhan pemasukan</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-danger pull-right">Total</span>
                                <h5>Pengeluaran</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></h1>
                                <small>Keseluruhan pengeluaran</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-primary pull-right">Saldo</span>
                                <h5>Saldo Kas</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">Rp <?php echo number_format($saldo_kas, 0, ',', '.'); ?></h1>
                                <small>Sisa dana</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <h5>Daging Terdistribusi (Sudah Diambil)</h5>
                            </div>
                            <div class="ibox-content">
                                <div class="row">
                                    <div class="col-sm-6 text-center">
                                        <h1 class="no-margins text-navy"><?php echo number_format($total_daging_sapi_distribusi, 2, ',', '.'); ?> Kg</h1>
                                        <small>Daging Sapi Terdistribusi</small>
                                    </div>
                                    <div class="col-sm-6 text-center">
                                        <h1 class="no-margins text-info"><?php echo number_format($total_daging_kambing_distribusi, 2, ',', '.'); ?> Kg</h1>
                                        <small>Daging Kambing Terdistribusi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <?php include 'templates/footer.php'; ?>
        </div>
    </div>

    <?php include 'templates/scripts.php'; ?>
</body>
</html>