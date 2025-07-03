<?php
// qurban_app/admin/edit_hewan_qurban.php
// Halaman untuk Administrator mengedit data Hewan Qurban
// FINAL: Disesuaikan untuk skema database baru (menggunakan checkUserAccess)

session_start();

define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$id_hewan = $_GET['id'] ?? null;
$hewan_data = null;
$success_message = '';
$error_message = '';

// Ambil data hewan qurban yang akan diedit
if ($id_hewan) {
    try {
        $stmt_hewan = $conn->prepare("SELECT * FROM hewan_qurban WHERE id_hewan = :id_hewan");
        $stmt_hewan->bindParam(':id_hewan', $id_hewan);
        $stmt_hewan->execute();
        $hewan_data = $stmt_hewan->fetch(PDO::FETCH_ASSOC);

        if (!$hewan_data) {
            $error_message = "Data hewan qurban tidak ditemukan.";
            $id_hewan = null; // Set null agar form tidak ditampilkan
        }

    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database saat mengambil data hewan qurban: " . $e->getMessage();
        error_log("Edit hewan_qurban fetch error: " . $e->getMessage());
    }
} else {
    $error_message = "ID Hewan Qurban tidak diberikan.";
}

// Inisialisasi variabel form dengan data dari database atau POST (jika ada error validasi)
if ($hewan_data && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $jenis_hewan = $hewan_data['jenis_hewan'];
    $jumlah_bagian = $hewan_data['jumlah_bagian'];
    $harga = $hewan_data['harga'];
    $berat_daging = $hewan_data['berat_daging'];
    $tgl_beli = $hewan_data['tgl_beli'];
    $status = $hewan_data['status'];
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis_hewan = trim($_POST['jenis_hewan'] ?? '');
    $jumlah_bagian = trim($_POST['jumlah_bagian'] ?? '');
    $harga = str_replace(['.', ','], '', trim($_POST['harga'] ?? ''));
    $berat_daging = str_replace(',', '.', trim($_POST['berat_daging'] ?? ''));
    $tgl_beli = trim($_POST['tgl_beli'] ?? '');
    $status = trim($_POST['status'] ?? 'Tersedia');
} else {
    $jenis_hewan = ''; $jumlah_bagian = ''; $harga = ''; $berat_daging = '';
    $tgl_beli = date('Y-m-d'); $status = 'Tersedia';
}


// Proses update form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $hewan_data) {
    $jenis_hewan = trim($_POST['jenis_hewan'] ?? '');
    $jumlah_bagian = trim($_POST['jumlah_bagian'] ?? '');
    $harga = str_replace(['.', ','], '', trim($_POST['harga'] ?? ''));
    $berat_daging = str_replace(',', '.', trim($_POST['berat_daging'] ?? ''));
    $tgl_beli = trim($_POST['tgl_beli'] ?? '');
    $status = trim($_POST['status'] ?? 'Tersedia');

    // Validasi input
    if (empty($jenis_hewan) || empty($jumlah_bagian) || empty($harga) || empty($berat_daging) || empty($tgl_beli)) {
        $error_message = 'Semua field wajib diisi kecuali status.';
    } elseif (!is_numeric($jumlah_bagian) || $jumlah_bagian <= 0) {
        $error_message = 'Jumlah bagian harus angka positif.';
    } elseif (!is_numeric($harga) || $harga < 0) {
        $error_message = 'Harga harus angka positif.';
    } elseif (!is_numeric($berat_daging) || $berat_daging <= 0) {
        $error_message = 'Berat daging harus angka positif.';
    } else {
        try {
            // Update data di tabel hewan_qurban
            $stmt = $conn->prepare("UPDATE hewan_qurban SET 
                                    jenis_hewan = :jenis_hewan, 
                                    jumlah_bagian = :jumlah_bagian, 
                                    harga = :harga, 
                                    berat_daging = :berat_daging, 
                                    tgl_beli = :tgl_beli, 
                                    status = :status 
                                    WHERE id_hewan = :id_hewan");
            
            $stmt->bindParam(':jenis_hewan', $jenis_hewan);
            $stmt->bindParam(':jumlah_bagian', $jumlah_bagian, PDO::PARAM_INT);
            $stmt->bindParam(':harga', $harga);
            $stmt->bindParam(':berat_daging', $berat_daging);
            $stmt->bindParam(':tgl_beli', $tgl_beli);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id_hewan', $id_hewan);

            if ($stmt->execute()) {
                header('Location: hewan_qurban.php?status=edit_success');
                exit();
            } else {
                $error_message = 'Gagal memperbarui data hewan qurban.';
            }

        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Update hewan_qurban error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Edit Hewan Qurban</title>
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
                                <h5>Edit Hewan Qurban</h5>
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

                                <?php if ($hewan_data): ?>
                                <form method="POST" action="edit_hewan_qurban.php?id=<?php echo htmlspecialchars($id_hewan); ?>" class="form-horizontal">
                                    <div class="form-group"><label class="col-sm-2 control-label">Jenis Hewan</label>
                                        <div class="col-sm-10">
                                            <select name="jenis_hewan" class="form-control m-b" required>
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
                                                <option value="Terjual" <?php echo ($status == 'Terjual') ? 'selected' : ''; ?>>Terdistribusi</option>
                                                <option value="Terdistribusi" <?php echo ($status == 'Terdistribusi') ? 'selected' : ''; ?>>Terdistribusi</option>
                                                <option value="Lainnya" <?php echo ($status == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="hr-line-dashed"></div>
                                    <div class="form-group">
                                        <div class="col-sm-4 col-sm-offset-2">
                                            <a href="hewan_qurban.php" class="btn btn-white">Batal</a>
                                            <button class="btn btn-primary" type="submit">Update Data</button>
                                        </div>
                                    </div>
                                </form>
                                <?php else: ?>
                                    <p class="text-danger">Tidak ada data hewan qurban untuk diedit.</p>
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

            // Inisialisasi Masking untuk Harga
            $('.input-mask-currency').jasnymask({
                mask: '999.999.999.999', // Contoh mask untuk angka besar
                reverse: true,
                placeholder: ''
            });
        });
    </script>

</body>
</html>