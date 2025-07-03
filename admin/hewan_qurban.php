<?php
// qurban_app/admin/hewan_qurban.php
// Halaman untuk Administrator mengelola data Hewan Qurban
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
$hewan_qurban_list = []; // Untuk menyimpan data hewan qurban

// Logika untuk menghapus hewan qurban (jika ada permintaan DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_hewan_to_delete = $_GET['id'];

    try {
        // Cek apakah hewan qurban ini sudah terkait dengan iuran pengqurban
        // Jika sudah ada iuran, sebaiknya tidak boleh dihapus atau hapus iuran dulu
        $stmt_check_iuran = $conn->prepare("SELECT COUNT(*) FROM iuran_pengqurban WHERE id_hewanqurban = :id_hewan");
        $stmt_check_iuran->bindParam(':id_hewan', $id_hewan_to_delete);
        $stmt_check_iuran->execute();
        
        if ($stmt_check_iuran->fetchColumn() > 0) {
            $error_message = 'Tidak dapat menghapus hewan qurban ini karena sudah terdaftar di iuran pengqurban. Harap hapus data iuran terkait terlebih dahulu.';
        } else {
            // Lanjutkan proses penghapusan
            $stmt_delete_hewan = $conn->prepare("DELETE FROM hewan_qurban WHERE id_hewan = :id_hewan");
            $stmt_delete_hewan->bindParam(':id_hewan', $id_hewan_to_delete);
            
            if ($stmt_delete_hewan->execute()) {
                $success_message = 'Data hewan qurban berhasil dihapus.';
            } else {
                $error_message = 'Gagal menghapus data hewan qurban.';
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Terjadi kesalahan database saat menghapus: ' . $e->getMessage();
        error_log("Delete hewan_qurban error: " . $e->getMessage());
    }
}

// Ambil semua data hewan qurban dari database
try {
    $stmt_hewan = $conn->prepare("SELECT * FROM hewan_qurban ORDER BY tgl_beli DESC, jenis_hewan ASC");
    $stmt_hewan->execute();
    $hewan_qurban_list = $stmt_hewan->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data hewan qurban: " . $e->getMessage();
    error_log("Fetch hewan_qurban error: " . $e->getMessage());
}

// Menampilkan pesan sukses dari redirect (misal dari halaman tambah/edit)
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'add_success') {
        $success_message = 'Hewan qurban berhasil ditambahkan!';
    } elseif ($_GET['status'] == 'edit_success') {
        $success_message = 'Data hewan qurban berhasil diperbarui!';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Manajemen Hewan Qurban</title>
    <link href="../assets/css/plugins/dataTables/datatables.min.css" rel="stylesheet">
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
                                <h5>Daftar Hewan Qurban</h5>
                                <div class="ibox-tools">
                                    <a href="add_hewan_qurban.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Tambah Hewan Qurban
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

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover dataTables-example" >
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Jenis Hewan</th>
                                                <th>Jumlah Bagian</th>
                                                <th>Harga</th>
                                                <th>Berat Daging (Kg)</th>
                                                <th>Tanggal Beli</th>
                                                <th>Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php foreach ($hewan_qurban_list as $hewan): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($hewan['jenis_hewan'])); ?></td>
                                                    <td><?php echo htmlspecialchars($hewan['jumlah_bagian']); ?></td>
                                                    <td>Rp <?php echo number_format($hewan['harga'], 0, ',', '.'); ?></td>
                                                    <td><?php echo number_format($hewan['berat_daging'], 0, ',', '.'); ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($hewan['tgl_beli']))); ?></td>
                                                    <td><?php echo htmlspecialchars($hewan['status']); ?></td>
                                                    <td class="text-center">
                                                        <a href="edit_hewan_qurban.php?id=<?php echo $hewan['id_hewan']; ?>" class="btn btn-warning btn-xs" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?php echo $hewan['id_hewan']; ?>" 
                                                           class="btn btn-danger btn-xs" title="Hapus"
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data hewan qurban ini? Jika hewan ini sudah terkait dengan iuran pengqurban, penghapusan akan gagal.');">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($hewan_qurban_list)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">Belum ada data hewan qurban.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
    <script src="../assets/js/plugins/dataTables/datatables.min.js"></script>

    <script>
        $(document).ready(function(){
            $('.dataTables-example').DataTable({
                pageLength: 10,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp',
                buttons: [
                    { extend: 'copy'},
                    {extend: 'csv'},
                    {extend: 'excel', title: 'DaftarHewanQurban'},
                    {extend: 'pdf', title: 'DaftarHewanQurban'},
                    {extend: 'print',
                     customize: function (win){
                            $(win.document.body).addClass('white-bg');
                            $(win.document.body).css('font-size', '10px');

                            $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css('font-size', 'inherit');
                    }
                    }
                ]
            });
        });
    </script>

</body>
</html>