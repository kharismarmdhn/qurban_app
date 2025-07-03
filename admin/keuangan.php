<?php
// qurban_app/admin/keuangan.php
// Halaman untuk Administrator melihat ringkasan dan daftar transaksi keuangan
// Disesuaikan dengan skema database baru (otorisasi saja)

session_start();

include '../config/db.php';
include '../config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';
$transaksi_list = []; // Untuk menyimpan data transaksi

// Logika untuk menghapus transaksi (jika ada permintaan DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_transaksi_to_delete = $_GET['id'];

    try {
        $stmt_delete_transaksi = $conn->prepare("DELETE FROM transaksi_keuangan WHERE id_transaksi = :id_transaksi");
        $stmt_delete_transaksi->bindParam(':id_transaksi', $id_transaksi_to_delete);
        
        if ($stmt_delete_transaksi->execute()) {
            $success_message = 'Transaksi berhasil dihapus.';
        } else {
            $error_message = 'Gagal menghapus transaksi.';
        }
    } catch (PDOException $e) {
        $error_message = 'Terjadi kesalahan database saat menghapus transaksi: ' . $e->getMessage();
        error_log("Delete transaksi error: " . $e->getMessage());
    }
}

// Ambil data ringkasan keuangan
$total_pemasukan = 0;
$total_pengeluaran = 0;
$saldo_kas = 0;

try {
    // Total Pemasukan
    $stmt_pemasukan_total = $conn->prepare("SELECT SUM(jumlah) AS total FROM transaksi_keuangan WHERE tipe_transaksi = 'Pemasukan'");
    $stmt_pemasukan_total->execute();
    $result_pemasukan_total = $stmt_pemasukan_total->fetch(PDO::FETCH_ASSOC);
    $total_pemasukan = $result_pemasukan_total['total'] ?? 0;

    // Total Pengeluaran
    $stmt_pengeluaran_total = $conn->prepare("SELECT SUM(jumlah) AS total FROM transaksi_keuangan WHERE tipe_transaksi = 'Pengeluaran'");
    $stmt_pengeluaran_total->execute();
    $result_pengeluaran_total = $stmt_pengeluaran_total->fetch(PDO::FETCH_ASSOC);
    $total_pengeluaran = $result_pengeluaran_total['total'] ?? 0;

    $saldo_kas = $total_pemasukan - $total_pengeluaran;

} catch (PDOException $e) {
    $error_message = "Gagal mengambil ringkasan keuangan: " . $e->getMessage();
    error_log("Fetch keuangan summary error: " . $e->getMessage());
}


// Ambil semua data transaksi dari database
try {
    $stmt_transaksi = $conn->prepare("SELECT * FROM transaksi_keuangan ORDER BY tgl_transaksi DESC");
    $stmt_transaksi->execute();
    $transaksi_list = $stmt_transaksi->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data transaksi: " . $e->getMessage();
    error_log("Fetch transaksi_keuangan error: " . $e->getMessage());
}

// Menampilkan pesan sukses dari redirect (misal dari halaman input pemasukan/pengeluaran)
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'pemasukan_success') {
        $success_message = 'Pemasukan berhasil dicatat!';
    } elseif ($_GET['status'] == 'pengeluaran_success') {
        $success_message = 'Pengeluaran berhasil dicatat!';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Ringkasan Keuangan</title>
    <link href="../assets/css/plugins/dataTables/datatables.min.css" rel="stylesheet">
</head>
<body>
    <div id="wrapper">
        <?php include 'templates/sidebar.php'; ?>
        <div id="page-wrapper" class="gray-bg">
            <?php include 'templates/top_navbar.php'; ?>

            <div class="wrapper wrapper-content animated fadeInRight">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <span class="label label-success pull-right">Total</span>
                                <h5>Total Pemasukan</h5>
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
                                <h5>Total Pengeluaran</h5>
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
                                <h5>Saldo Kas Saat Ini</h5>
                            </div>
                            <div class="ibox-content">
                                <h1 class="no-margins">Rp <?php echo number_format($saldo_kas, 0, ',', '.'); ?></h1>
                                <small>Pemasukan - Pengeluaran</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox float-e-margins">
                            <div class="ibox-title">
                                <h5>Daftar Transaksi Keuangan</h5>
                                <div class="ibox-tools">
                                    <a href="input_pemasukan.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Tambah Pemasukan
                                    </a>
                                    <a href="input_pengeluaran.php" class="btn btn-warning btn-xs">
                                        <i class="fa fa-minus"></i> Tambah Pengeluaran
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
                                                <th>Tanggal Transaksi</th>
                                                <th>Tipe Transaksi</th>
                                                <th>Kategori</th>
                                                <th>Deskripsi</th>
                                                <th>Jumlah (Rp)</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php foreach ($transaksi_list as $transaksi): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($transaksi['tgl_transaksi']))); ?></td>
                                                    <td>
                                                        <?php 
                                                            if ($transaksi['tipe_transaksi'] == 'Pemasukan') {
                                                                echo '<span class="label label-success">Pemasukan</span>';
                                                            } elseif ($transaksi['tipe_transaksi'] == 'Pengeluaran') {
                                                                echo '<span class="label label-danger">Pengeluaran</span>';
                                                            } else {
                                                                echo htmlspecialchars($transaksi['tipe_transaksi']); // Fallback jika ada tipe lain yang tidak dikenal
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaksi['kategori']); ?></td>
                                                    <td><?php echo htmlspecialchars($transaksi['deskripsi']); ?></td>
                                                    <td>Rp <?php echo number_format($transaksi['jumlah'], 0, ',', '.'); ?></td>
                                                    <td class="text-center">
                                                        <a href="?action=delete&id=<?php echo $transaksi['id_transaksi']; ?>" 
                                                           class="btn btn-danger btn-xs" title="Hapus"
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($transaksi_list)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Belum ada data transaksi keuangan.</td>
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
                    {extend: 'excel', title: 'DaftarTransaksiKeuangan'},
                    {extend: 'pdf', title: 'DaftarTransaksiKeuangan'},
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