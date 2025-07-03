<?php
// qurban_app/admin/add_hewan_qurban.php
// Halaman untuk Administrator menambahkan Hewan Qurban baru
// FINAL: Disesuaikan untuk skema database baru (menggunakan checkUserAccess)

session_start();

// Definisikan ROOT_PATH proyek Anda
define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';

// Inisialisasi variabel untuk mengisi kembali form jika ada error
$jenis_hewan = '';
$jumlah_bagian = '';
$harga = '';
$berat_daging = '';
$tgl_beli = date('Y-m-d'); // Default tanggal hari ini
$status = 'Tersedia'; // Default status

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis_hewan = trim($_POST['jenis_hewan'] ?? '');
    $jumlah_bagian = trim($_POST['jumlah_bagian'] ?? '');
    $harga = str_replace(['.', ','], '', trim($_POST['harga'] ?? '')); // Hapus format ribuan
    $berat_daging = str_replace(',', '.', trim($_POST['berat_daging'] ?? '')); // Ganti koma jadi titik untuk desimal
    $tgl_beli = trim($_POST['tgl_beli'] ?? '');
    $status = trim($_POST['status'] ?? 'Tersedia');

    // Validasi input
    if (empty($jenis_hewan) || empty($jumlah_bagian) || empty($harga) || empty($berat_daging) || empty($tgl_beli)) {
        $error_message = 'Semua field wajib diisi kecuali status (default Tersedia).';
    } elseif (!is_numeric($jumlah_bagian) || $jumlah_bagian <= 0) {
        $error_message = 'Jumlah bagian harus angka positif.';
    } elseif (!is_numeric($harga) || $harga < 0) {
        $error_message = 'Harga harus angka positif.';
    } elseif (!is_numeric($berat_daging) || $berat_daging <= 0) {
        $error_message = 'Berat daging harus angka positif.';
    } else {
        try {
            // Masukkan data ke tabel hewan_qurban
            $stmt = $conn->prepare("INSERT INTO hewan_qurban (jenis_hewan, jumlah_bagian, harga, berat_daging, tgl_beli, status) VALUES (:jenis_hewan, :jumlah_bagian, :harga, :berat_daging, :tgl_beli, :status)");
            
            $stmt->bindParam(':jenis_hewan', $jenis_hewan);
            $stmt->bindParam(':jumlah_bagian', $jumlah_bagian, PDO::PARAM_INT);
            $stmt->bindParam(':harga', $harga);
            $stmt->bindParam(':berat_daging', $berat_daging);
            $stmt->bindParam(':tgl_beli', $tgl_beli);
            $stmt->bindParam(':status', $status);

            if ($stmt->execute()) {
                // Redirect ke halaman daftar hewan qurban dengan pesan sukses
                header('Location: hewan_qurban.php?status=add_success');
                exit();
            } else {
                $error_message = 'Gagal menambahkan hewan qurban.';
            }

        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Add hewan_qurban error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Tambah Hewan Qurban</title>
    <link href="../assets/css/plugins/datapicker/datepicker3.css" rel="stylesheet">
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
                                <h5>Tambah Hewan Qurban Baru</h5>
                            </div>
                            <div class="ibox-content">
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger alert-dismissable">
                                        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="add_hewan_qurban.php" class="form-horizontal">
                                    <div class="form-group"><label class="col-sm-2 control-label">Jenis Hewan</label>
                                        <div class="col-sm-10">
                                            <select name="jenis_hewan" class="form-control m-b" required>
                                                <option value="">-- Pilih Jenis Hewan --</option>
                                                <option value="sapi" <?php echo ($jenis_hewan == 'sapi') ? 'selected' : ''; ?>>Sapi</option>
                                                <option value="kambing" <?php echo ($jenis_hewan == 'kambing') ? 'selected' : ''; ?>>Kambing</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Jumlah Bagian</label>
                                        <div class="col-sm-10">
                                            <input type="number" name="jumlah_bagian" class="form-control" min="1" required 
                                                   value="<?php echo htmlspecialchars($jumlah_bagian); ?>">
                                            <span class="help-block m-b-none">Misal: 7 untuk sapi, 1 untuk kambing.</span>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Harga (Rp)</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="harga" class="form-control input-mask-currency" required 
                                                   value="<?php echo htmlspecialchars($harga); ?>">
                                            <span class="help-block m-b-none">Masukkan angka saja, contoh: 2700000.</span>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Berat Daging (Kg)</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="berat_daging" class="form-control" required 
                                                   value="<?php echo htmlspecialchars($berat_daging); ?>">
                                            <span class="help-block m-b-none">Perkiraan berat daging bersih dalam Kilogram, gunakan titik untuk desimal.</span>
                                        </div>
                                    </div>
                                    <div class="form-group" id="data_1"><label class="col-sm-2 control-label">Tanggal Beli</label>
                                        <div class="col-sm-10">
                                            <div class="input-group date">
                                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text" name="tgl_beli" class="form-control" value="<?php echo htmlspecialchars($tgl_beli); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Status</label>
                                        <div class="col-sm-10">
                                            <select name="status" class="form-control m-b" required>
                                                <option value="Tersedia" <?php echo ($status == 'Tersedia') ? 'selected' : ''; ?>>Tersedia</option>
                                                <option value="Terjual" <?php echo ($status == 'Terjual') ? 'selected' : ''; ?>>Terjual</option>
                                                <option value="Terdistribusi" <?php echo ($status == 'Terdistribusi') ? 'selected' : ''; ?>>Terdistribusi</option>
                                                <option value="Lainnya" <?php echo ($status == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="hr-line-dashed"></div>
                                    <div class="form-group">
                                        <div class="col-sm-4 col-sm-offset-2">
                                            <a href="hewan_qurban.php" class="btn btn-white">Batal</a>
                                            <button class="btn btn-primary" type="submit">Simpan Data</button>
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
    <script src="../assets/js/plugins/jasny/jasny-bootstrap.min.js"></script> 
    <link href="../assets/css/plugins/jasny/jasny-bootstrap.min.css" rel="stylesheet">

    <script>
        $(document).ready(function(){
            // Inisialisasi Datepicker
            $('#data_1 .input-group.date').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
                format: "yyyy-mm-dd" // Format tanggal sesuai dengan MySQL DATE
            });

            // Inisialisasi Masking untuk Harga (opsional)
            $('.input-mask-currency').jasnymask({
                mask: '999.999.999.999', // Contoh mask untuk angka besar, sesuaikan kebutuhan
                reverse: true, // Mulai masking dari kanan
                placeholder: ''
            });
        });
    </script>

</body>
</html>