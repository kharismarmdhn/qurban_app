<?php
// qurban_app/admin/distribusi_daging.php
// Halaman untuk Administrator mengelola input distribusi daging
// Disesuaikan: QR Code sekarang mengkodekan NIK|ID_DISTRIBUSI sebagai string.

session_start();

// Definisikan ROOT_PATH proyek Anda
define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Include library QR Code chillerlan/php-qrcode
require_once ROOT_PATH . '/vendor/autoload.php';

use chillerlan\QRCode\{QRCode, QROptions};

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';

// Inisialisasi variabel untuk mengisi kembali form jika ada error
$nik_penerima = '';
$jumlah_daging_sapi = '';
$jumlah_daging_kambing = '';
$tgl_distribusi = date('Y-m-d H:i:s'); // Default tanggal dan waktu saat ini
$status_pengambilan = 'belum_diambil'; // Default status

$warga_penerima_list = []; // Untuk dropdown warga penerima

try {
    // Ambil daftar warga untuk dropdown (user dengan level 'warga')
    $stmt_warga = $conn->prepare("SELECT NIK, nm_lengkap FROM users WHERE level = 'warga' ORDER BY nm_lengkap ASC");
    $stmt_warga->execute();
    $warga_penerima_list = $stmt_warga->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data warga penerima: " . $e->getMessage();
    error_log("Distribusi daging dropdown fetch error: " . $e->getMessage());
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik_penerima = trim($_POST['nik_penerima'] ?? '');
    $jumlah_daging_sapi = str_replace(',', '.', trim($_POST['jumlah_daging_sapi'] ?? '0'));
    $jumlah_daging_kambing = str_replace(',', '.', trim($_POST['jumlah_daging_kambing'] ?? '0'));
    $tgl_distribusi = trim($_POST['tgl_distribusi'] ?? date('Y-m-d H:i:s'));
    $status_pengambilan = trim($_POST['status_pengambilan'] ?? 'belum_diambil');
    
    // NIK panitia yang mengkonfirmasi. Saat ini adalah NIK admin yang sedang login.
    $nik_panitia_konfirm = null; 
    if ($status_pengambilan == 'sudah_diambil') { 
        $nik_panitia_konfirm = $_SESSION['user_nik']; 
    }

    // Validasi input
    if (empty($nik_penerima) || empty($tgl_distribusi)) {
        $error_message = 'Warga Penerima dan Tanggal & Waktu Distribusi wajib diisi.';
    } elseif (!is_numeric($jumlah_daging_sapi) || $jumlah_daging_sapi < 0) {
        $error_message = 'Jumlah daging sapi harus angka positif atau nol.';
    } elseif (!is_numeric($jumlah_daging_kambing) || $jumlah_daging_kambing < 0) {
        $error_message = 'Jumlah daging kambing harus angka positif atau nol.';
    } elseif ($jumlah_daging_sapi == 0 && $jumlah_daging_kambing == 0) {
        $error_message = 'Setidaknya salah satu jenis daging harus dialokasikan.';
    } else {
        try {
            $conn->beginTransaction(); // Mulai transaksi

            // Simpan data distribusi ke database terlebih dahulu
            $stmt = $conn->prepare("INSERT INTO distribusi_daging (NIK_penerima, jumlah_daging_sapi, jumlah_daging_kambing, tgl_distribusi, kd_qr, status_pengambilan, NIK_panitia_konfirm) VALUES (:nik_penerima, :jumlah_daging_sapi, :jumlah_daging_kambing, :tgl_distribusi, :kd_qr, :status_pengambilan, :nik_panitia_konfirm)");
            
            $stmt->bindParam(':nik_penerima', $nik_penerima);
            $stmt->bindParam(':jumlah_daging_sapi', $jumlah_daging_sapi);
            $stmt->bindParam(':jumlah_daging_kambing', $jumlah_daging_kambing);
            $stmt->bindParam(':tgl_distribusi', $tgl_distribusi);
            $stmt->bindValue(':kd_qr', null, PDO::PARAM_STR); // Awalnya NULL, akan diupdate setelah QR digenerate
            $stmt->bindParam(':status_pengambilan', $status_pengambilan);
            $stmt->bindParam(':nik_panitia_konfirm', $nik_panitia_konfirm, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $last_inserted_id = $conn->lastInsertId(); // Dapatkan ID distribusi yang baru
                
                // --- Generasi QR Code menggunakan chillerlan/php-qrcode ---
                // QR Code akan mengkodekan string NIK_penerima|id_distribusi
                $qr_final_content = $nik_penerima . '|' . $last_inserted_id; 

                $qr_filename = 'qr_distribusi_' . $last_inserted_id . '.png';
                $qr_filepath = ROOT_PATH . '/qrcodes/' . $qr_filename; // Path absolut ke folder qrcodes

                try {
                    $options = new QROptions([
                        'outputType'  => QRCode::OUTPUT_IMAGE_PNG,
                        'eccLevel'    => QRCode::ECC_H, // ECC_H untuk High error correction
                        'scale'       => 10,  // Ukuran modul QR code (pixel per modul)
                        'quality'     => 90,  // Kualitas gambar PNG (0-100)
                        'margin'      => 1,   // Margin di sekitar QR code
                        'pngCompressionFlag' => PNG_NO_FILTER, // Opsi kompresi PNG (optional)
                    ]);
                    
                    (new QRCode($options))->render($qr_final_content, $qr_filepath);

                } catch (Exception $e) {
                    $error_message_qr = 'Gagal menyimpan file QR Code: ' . $e->getMessage();
                    error_log("QR Code save error (chillerlan/php-qrcode): " . $e->getMessage());
                    $conn->rollBack();
                    $error_message = 'Gagal mencatat distribusi karena masalah QR Code: ' . $e->getMessage();
                    return; 
                }
                // --- Akhir Generasi QR Code ---

                // Update kolom kd_qr di database dengan nama file QR Code
                $stmt_update_qr = $conn->prepare("UPDATE distribusi_daging SET kd_qr = :qr_filename WHERE id_distribusi = :id_distribusi");
                $stmt_update_qr->bindParam(':qr_filename', $qr_filename);
                $stmt_update_qr->bindParam(':id_distribusi', $last_inserted_id, PDO::PARAM_INT);
                $stmt_update_qr->execute();

                $conn->commit(); 
                $success_message = 'Alokasi daging berhasil dicatat dan QR Code generated.';
                
                // Reset form setelah sukses
                $nik_penerima = '';
                $jumlah_daging_sapi = '';
                $jumlah_daging_kambing = '';
                $tgl_distribusi = date('Y-m-d H:i:s');
                $status_pengambilan = 'belum_diambil';

            } else {
                $conn->rollBack();
                $error_message = 'Gagal mencatat alokasi daging.';
            }

        } catch (PDOException $e) {
            $conn->rollBack(); 
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Add distribusi_daging database error: " . $e->getMessage());
        } catch (Exception $e) { 
            $conn->rollBack();
            $error_message = 'Terjadi kesalahan umum saat membuat QR Code: ' . $e->getMessage();
            error_log("QR Code generation general error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Input Distribusi Daging</title>
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
                                <h5>Input Distribusi Daging ke Warga</h5>
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

                                <form method="POST" action="distribusi_daging.php" class="form-horizontal">
                                    <div class="form-group"><label class="col-sm-2 control-label">Warga Penerima</label>
                                        <div class="col-sm-10">
                                            <select name="nik_penerima" class="form-control m-b" required>
                                                <option value="">-- Pilih Warga --</option>
                                                <?php foreach ($warga_penerima_list as $warga): ?>
                                                    <option value="<?php echo htmlspecialchars($warga['NIK']); ?>" 
                                                        <?php echo ($nik_penerima == $warga['NIK']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($warga['nm_lengkap'] . ' (NIK: ' . $warga['NIK'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="help-block m-b-none">Pilih warga yang akan menerima daging qurban.</span>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Jumlah Daging Sapi (Kg)</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="jumlah_daging_sapi" class="form-control" 
                                                   value="<?php echo htmlspecialchars($jumlah_daging_sapi); ?>">
                                            <span class="help-block m-b-none">Masukkan jumlah daging sapi dalam Kilogram. Gunakan titik untuk desimal.</span>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Jumlah Daging Kambing (Kg)</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="jumlah_daging_kambing" class="form-control" 
                                                   value="<?php echo htmlspecialchars($jumlah_daging_kambing); ?>">
                                            <span class="help-block m-b-none">Masukkan jumlah daging kambing dalam Kilogram. Gunakan titik untuk desimal.</span>
                                        </div>
                                    </div>
                                    <div class="form-group" id="data_time"><label class="col-sm-2 control-label">Tanggal & Waktu Distribusi</label>
                                        <div class="col-sm-10">
                                            <div class="input-group date">
                                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text" name="tgl_distribusi" class="form-control" value="<?php echo htmlspecialchars($tgl_distribusi); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Status Pengambilan</label>
                                        <div class="col-sm-10">
                                            <select name="status_pengambilan" class="form-control m-b" required>
                                                <option value="belum_diambil" <?php echo ($status_pengambilan == 'belum_diambil') ? 'selected' : ''; ?>>Belum Diambil</option>
                                                <option value="sudah_diambil" <?php echo ($status_pengambilan == 'sudah_diambil') ? 'selected' : ''; ?>>Sudah Diambil</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="hr-line-dashed"></div>
                                    <div class="form-group">
                                        <div class="col-sm-4 col-sm-offset-2">
                                            <a href="laporan_distribusi.php" class="btn btn-white">Lihat Laporan Distribusi</a>
                                            <button class="btn btn-primary" type="submit">Catat Distribusi & Generate QR</button>
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

    <script>
        $(document).ready(function(){
            // Inisialisasi Datepicker
            $('#data_time .input-group.date').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
                format: "yyyy-mm-dd", // Hanya tanggal, waktu akan di handle PHP default
                endDate: "today"
            }).on('changeDate', function(e) {
                // Di sini Anda bisa menambahkan logika untuk memperbarui bagian waktu jika ada timepicker terpisah
            });
        });
    </script>

</body>
</html>