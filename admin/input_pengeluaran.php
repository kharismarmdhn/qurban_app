<?php
// qurban_app/admin/input_pengeluaran.php
// Halaman untuk Administrator mencatat Pengeluaran baru
// Disesuaikan dengan skema database baru (otorisasi saja)

session_start();

include '../config/db.php';
include '../config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';

// Inisialisasi variabel untuk mengisi kembali form jika ada error
$kategori = '';
$deskripsi = '';
$jumlah = '';
$tgl_transaksi = date('Y-m-d H:i:s'); // Default tanggal dan waktu saat ini

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kategori = trim($_POST['kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $jumlah = str_replace(['.', ','], '', trim($_POST['jumlah'] ?? '')); // Hapus format ribuan
    $tgl_transaksi = trim($_POST['tgl_transaksi'] ?? date('Y-m-d H:i:s')); // Ambil dari input atau default

    // Tipe transaksi untuk pengeluaran
    $tipe_transaksi = 'Pengeluaran';

    // Validasi input
    if (empty($kategori) || empty($deskripsi) || empty($jumlah) || empty($tgl_transaksi)) {
        $error_message = 'Semua field wajib diisi.';
    } elseif (!is_numeric($jumlah) || $jumlah <= 0) {
        $error_message = 'Jumlah harus angka positif.';
    } else {
        try {
            // Masukkan data ke tabel transaksi_keuangan
            $stmt = $conn->prepare("INSERT INTO transaksi_keuangan (tipe_transaksi, kategori, deskripsi, jumlah, tgl_transaksi) VALUES (:tipe_transaksi, :kategori, :deskripsi, :jumlah, :tgl_transaksi)");
            
            $stmt->bindParam(':tipe_transaksi', $tipe_transaksi);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':jumlah', $jumlah);
            $stmt->bindParam(':tgl_transaksi', $tgl_transaksi);

            if ($stmt->execute()) {
                // Redirect ke halaman ringkasan keuangan dengan pesan sukses
                header('Location: keuangan.php?status=pengeluaran_success');
                exit();
            } else {
                $error_message = 'Gagal mencatat pengeluaran.';
            }

        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Add pengeluaran error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Input Pengeluaran</title>
    <link href="../assets/css/plugins/datapicker/datepicker3.css" rel="stylesheet">
    <link href="../assets/css/plugins/clockpicker/clockpicker.css" rel="stylesheet">
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
                                <h5>Input Pengeluaran Baru</h5>
                            </div>
                            <div class="ibox-content">
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger alert-dismissable">
                                        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="input_pengeluaran.php" class="form-horizontal">
                                    <div class="form-group"><label class="col-sm-2 control-label">Kategori Pengeluaran</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="kategori" class="form-control" required 
                                                   value="<?php echo htmlspecialchars($kategori); ?>">
                                            <span class="help-block m-b-none">Contoh: Pembelian Hewan Qurban, Biaya Operasional, Perlengkapan Panitia.</span>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Deskripsi</label>
                                        <div class="col-sm-10">
                                            <textarea name="deskripsi" class="form-control" required><?php echo htmlspecialchars($deskripsi); ?></textarea>
                                            <span class="help-block m-b-none">Penjelasan singkat tentang pengeluaran ini.</span>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Jumlah (Rp)</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="jumlah" class="form-control input-mask-currency" required 
                                                   value="<?php echo htmlspecialchars($jumlah); ?>">
                                            <span class="help-block m-b-none">Masukkan angka saja, contoh: 2700000.</span>
                                        </div>
                                    </div>
                                    <div class="form-group" id="data_time"><label class="col-sm-2 control-label">Tanggal & Waktu Transaksi</label>
                                        <div class="col-sm-10">
                                            <div class="input-group date">
                                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text" name="tgl_transaksi" class="form-control" value="<?php echo htmlspecialchars($tgl_transaksi); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="hr-line-dashed"></div>
                                    <div class="form-group">
                                        <div class="col-sm-4 col-sm-offset-2">
                                            <a href="keuangan.php" class="btn btn-white">Batal</a>
                                            <button class="btn btn-primary" type="submit">Catat Pengeluaran</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'templates/footer.php'; ?>
        </div>
    </div>

    <?php include 'templates/scripts.php'; ?>
    <script src="../assets/js/plugins/datapicker/bootstrap-datepicker.js"></script>
    <script src="../assets/js/plugins/clockpicker/clockpicker.js"></script>
    <script src="../assets/js/plugins/jasny/jasny-bootstrap.min.js"></script> 
    <link href="../assets/css/plugins/jasny/jasny-bootstrap.min.css" rel="stylesheet">

    <script>
        $(document).ready(function(){
            // Inisialisasi Datepicker
            $('#data_time .input-group.date').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
                format: "yyyy-mm-dd", // Format tanggal saja
                endDate: "today" // Tidak bisa memilih tanggal di masa depan
            });

            // Inisialisasi Masking untuk Jumlah (Harga)
            $('.input-mask-currency').jasnymask({
                mask: '999.999.999.999', // Contoh mask untuk angka besar
                reverse: true, // Mulai masking dari kanan
                placeholder: ''
            });
        });
    </script>

</body>
</html>