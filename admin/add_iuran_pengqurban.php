<?php
// qurban_app/admin/add_iuran_pengqurban.php
// Halaman untuk Administrator menambahkan Iuran Pengqurban baru
// Disesuaikan dengan skema database baru (NIK_pengqurban sebagai FK ke users.NIK)

session_start();

define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';

// Inisialisasi variabel untuk mengisi kembali form jika ada error
$nik_pengqurban = ''; 
$id_hewanqurban = '';
$jumlah_iuran = '';
$tgl_bayar = date('Y-m-d'); // Default tanggal hari ini
$status_bayar = 'belum_lunas'; // Default status

$pengqurban_list = []; // Untuk dropdown pengqurban
$hewan_qurban_list_dropdown = []; // Untuk dropdown hewan qurban

try {
    // Ambil daftar pengqurban untuk dropdown (user dengan level 'pengqurban')
    $stmt_pengqurban = $conn->prepare("SELECT NIK, nm_lengkap FROM users WHERE level = 'pengqurban' ORDER BY nm_lengkap ASC");
    $stmt_pengqurban->execute();
    $pengqurban_list = $stmt_pengqurban->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar hewan qurban untuk dropdown (hanya yang berstatus 'Tersedia' atau 'Terjual')
    $stmt_hewan = $conn->prepare("SELECT id_hewan, jenis_hewan, jumlah_bagian, harga FROM hewan_qurban WHERE status = 'Tersedia' OR status = 'Terjual' ORDER BY jenis_hewan ASC, jumlah_bagian ASC");
    $stmt_hewan->execute();
    $hewan_qurban_list_dropdown = $stmt_hewan->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data dropdown: " . $e->getMessage();
    error_log("Add iuran dropdown fetch error: " . $e->getMessage());
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik_pengqurban = trim($_POST['nik_pengqurban'] ?? ''); 
    $id_hewanqurban = trim($_POST['id_hewanqurban'] ?? '');
    $jumlah_iuran = str_replace(['.', ','], '', trim($_POST['jumlah_iuran'] ?? '')); // Hapus format ribuan
    $tgl_bayar = trim($_POST['tgl_bayar'] ?? date('Y-m-d'));
    $status_bayar = trim($_POST['status_bayar'] ?? 'belum_lunas');

    // Validasi input
    if (empty($nik_pengqurban) || empty($id_hewanqurban) || empty($jumlah_iuran) || empty($tgl_bayar) || empty($status_bayar)) {
        $error_message = 'Semua field wajib diisi.';
    } elseif (!is_numeric($jumlah_iuran) || $jumlah_iuran <= 0) {
        $error_message = 'Jumlah iuran harus angka positif.';
    } else {
        try {
            $conn->beginTransaction(); 

            // Masukkan data ke tabel iuran_pengqurban
            $stmt = $conn->prepare("INSERT INTO iuran_pengqurban (NIK_pengqurban, id_hewanqurban, jumlah_iuran, tgl_bayar, status_bayar) VALUES (:nik_pengqurban, :id_hewanqurban, :jumlah_iuran, :tgl_bayar, :status_bayar)");
            
            $stmt->bindParam(':nik_pengqurban', $nik_pengqurban); 
            $stmt->bindParam(':id_hewanqurban', $id_hewanqurban, PDO::PARAM_INT);
            $stmt->bindParam(':jumlah_iuran', $jumlah_iuran);
            $stmt->bindParam(':tgl_bayar', $tgl_bayar);
            $stmt->bindParam(':status_bayar', $status_bayar);

            if ($stmt->execute()) {
                if ($status_bayar == 'lunas') {
                    $kategori_pemasukan = 'Iuran Pengqurban';
                    $stmt_nama_pengqurban = $conn->prepare("SELECT nm_lengkap FROM users WHERE NIK = :nik");
                    $stmt_nama_pengqurban->bindParam(':nik', $nik_pengqurban);
                    $stmt_nama_pengqurban->execute();
                    $nama_pengqurban = $stmt_nama_pengqurban->fetchColumn() ?? 'Nama Tidak Diketahui';

                    $deskripsi_pemasukan = 'Pembayaran iuran dari ' . $nama_pengqurban . ' (NIK: ' . $nik_pengqurban . ').';
                    $tipe_transaksi_pemasukan = 'Pemasukan'; 

                    $stmt_transaksi = $conn->prepare("INSERT INTO transaksi_keuangan (tipe_transaksi, kategori, deskripsi, jumlah, tgl_transaksi) VALUES (:tipe_transaksi, :kategori, :deskripsi, :jumlah, :tgl_transaksi)");
                    $stmt_transaksi->bindParam(':tipe_transaksi', $tipe_transaksi_pemasukan);
                    $stmt_transaksi->bindParam(':kategori', $kategori_pemasukan);
                    $stmt_transaksi->bindParam(':deskripsi', $deskripsi_pemasukan);
                    $stmt_transaksi->bindParam(':jumlah', $jumlah_iuran);
                    $stmt_transaksi->bindParam(':tgl_transaksi', date('Y-m-d H:i:s'));
                    $stmt_transaksi->execute();
                }

                $conn->commit();
                header('Location: iuran_pengqurban.php?status=add_iuran_success');
                exit();
            } else {
                $conn->rollBack();
                $error_message = 'Gagal menambahkan iuran pengqurban.';
            }

        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Add iuran_pengqurban error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Tambah Iuran Pengqurban</title>
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
                                <h5>Tambah Iuran Pengqurban Baru</h5>
                            </div>
                            <div class="ibox-content">
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger alert-dismissable">
                                        <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="add_iuran_pengqurban.php" class="form-horizontal">
                                    <div class="form-group"><label class="col-sm-2 control-label">Pengqurban</label>
                                        <div class="col-sm-10">
                                            <select name="nik_pengqurban" class="form-control m-b" required>
                                                <option value="">-- Pilih Pengqurban --</option>
                                                <?php foreach ($pengqurban_list as $pengqurban): ?>
                                                    <option value="<?php echo htmlspecialchars($pengqurban['NIK']); ?>" 
                                                        <?php echo ($nik_pengqurban == $pengqurban['NIK']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($pengqurban['nm_lengkap'] . ' (NIK: ' . $pengqurban['NIK'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Hewan Qurban</label>
                                        <div class="col-sm-10">
                                            <select name="id_hewanqurban" class="form-control m-b" required>
                                                <option value="">-- Pilih Hewan Qurban --</option>
                                                <?php foreach ($hewan_qurban_list_dropdown as $hewan): ?>
                                                    <option value="<?php echo $hewan['id_hewan']; ?>" 
                                                        data-harga="<?php echo htmlspecialchars($hewan['harga'] / $hewan['jumlah_bagian']); ?>"
                                                        <?php echo ($id_hewanqurban == $hewan['id_hewan']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars(ucfirst($hewan['jenis_hewan']) . ' (' . $hewan['jumlah_bagian'] . ' Bagian, Harga Total Rp ' . number_format($hewan['harga'], 0, ',', '.') . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="help-block m-b-none">Harga per bagian akan ditampilkan otomatis di bawah.</span>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Harga per Bagian (Estimasi)</label>
                                        <div class="col-sm-10">
                                            <p class="form-control-static" id="harga_per_bagian">Rp 0</p>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Jumlah Iuran (Rp)</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="jumlah_iuran" class="form-control input-mask-currency" required 
                                                   value="<?php echo htmlspecialchars($jumlah_iuran); ?>">
                                            <span class="help-block m-b-none">Jumlah uang yang dibayarkan pengqurban.</span>
                                        </div>
                                    </div>
                                    <div class="form-group" id="data_1"><label class="col-sm-2 control-label">Tanggal Bayar</label>
                                        <div class="col-sm-10">
                                            <div class="input-group date">
                                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text" name="tgl_bayar" class="form-control" value="<?php echo htmlspecialchars($tgl_bayar); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Status Bayar</label>
                                        <div class="col-sm-10">
                                            <select name="status_bayar" class="form-control m-b" required>
                                                <option value="belum_lunas" <?php echo ($status_bayar == 'belum_lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                                                <option value="lunas" <?php echo ($status_bayar == 'lunas') ? 'selected' : ''; ?>>Lunas</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="hr-line-dashed"></div>
                                    <div class="form-group">
                                        <div class="col-sm-4 col-sm-offset-2">
                                            <a href="iuran_pengqurban.php" class="btn btn-white">Batal</a>
                                            <button class="btn btn-primary" type="submit">Catat Iuran</button>
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

            // Inisialisasi Masking untuk Jumlah Iuran
            $('.input-mask-currency').jasnymask({
                mask: '999.999.999.999', // Contoh mask untuk angka besar
                reverse: true, // Mulai masking dari kanan
                placeholder: ''
            });

            // Fungsi untuk memperbarui harga per bagian
            function updateHargaPerBagian() {
                var selectedOption = $('select[name="id_hewanqurban"] option:selected');
                var hargaPerBagianRaw = selectedOption.data('harga'); // Ambil nilai mentah dari data-harga

                // Tambahkan debugging ke konsol browser
                console.log("Nilai data-harga mentah:", hargaPerBagianRaw);
                
                // Pastikan nilai adalah angka yang valid sebelum diformat
                var hargaPerBagianParsed = parseFloat(String(hargaPerBagianRaw).replace(',', '.')); 
                
                console.log("Nilai data-harga setelah parse:", hargaPerBagianParsed);

                if (!isNaN(hargaPerBagianParsed) && hargaPerBagianParsed > 0) { 
                    $('#harga_per_bagian').text('Rp ' + hargaPerBagianParsed.toLocaleString('id-ID'));
                } else {
                    $('#harga_per_bagian').text('Rp 0');
                }
            }

            // Panggil fungsi saat halaman dimuat
            updateHargaPerBagian();

            // Panggil fungsi saat dropdown hewan qurban berubah
            $('select[name="id_hewanqurban"]').change(function() {
                updateHargaPerBagian();
            });
        });
    </script>

</body>
</html>