<?php
// qurban_app/admin/iuran_pengqurban.php
// Halaman untuk Administrator mengelola Iuran Pengqurban
// Disesuaikan dengan skema database baru (NIK_pengqurban sebagai FK ke users.NIK)

session_start();

define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';
$iuran_list = []; // Untuk menyimpan data iuran

// Logika untuk menghapus iuran (jika ada permintaan DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_iuran_to_delete = $_GET['id'];

    try {
        $stmt_delete_iuran = $conn->prepare("DELETE FROM iuran_pengqurban WHERE id_iuran = :id_iuran");
        $stmt_delete_iuran->bindParam(':id_iuran', $id_iuran_to_delete);
        
        if ($stmt_delete_iuran->execute()) {
            $success_message = 'Iuran berhasil dihapus.';
        } else {
            $error_message = 'Gagal menghapus iuran.';
        }
    } catch (PDOException $e) {
        $error_message = 'Terjadi kesalahan database saat menghapus iuran: ' . $e->getMessage();
        error_log("Delete iuran_pengqurban error: " . $e->getMessage());
    }
}

// Logika untuk mengubah status pembayaran (misal dari "belum_lunas" ke "lunas")
if (isset($_GET['action']) && $_GET['action'] == 'change_status' && isset($_GET['id'])) {
    $id_iuran_to_update = $_GET['id'];

    try {
        $stmt_get_status = $conn->prepare("SELECT status_bayar, NIK_pengqurban, jumlah_iuran, tgl_bayar FROM iuran_pengqurban WHERE id_iuran = :id_iuran");
        $stmt_get_status->bindParam(':id_iuran', $id_iuran_to_update);
        $stmt_get_status->execute();
        $iuran_data = $stmt_get_status->fetch(PDO::FETCH_ASSOC);

        if ($iuran_data) {
            $current_status = $iuran_data['status_bayar'];
            $nik_pengqurban_iuran = $iuran_data['NIK_pengqurban'];
            $jumlah_iuran_value = $iuran_data['jumlah_iuran'];
            $tgl_bayar_iuran = $iuran_data['tgl_bayar'];

            $new_status = ($current_status == 'belum_lunas') ? 'lunas' : 'belum_lunas'; // Toggle status

            $conn->beginTransaction(); 

            $stmt_update_status = $conn->prepare("UPDATE iuran_pengqurban SET status_bayar = :new_status WHERE id_iuran = :id_iuran");
            $stmt_update_status->bindParam(':new_status', $new_status);
            $stmt_update_status->bindParam(':id_iuran', $id_iuran_to_update);

            if ($stmt_update_status->execute()) {
                if ($new_status == 'lunas') {
                    $stmt_check_transaksi = $conn->prepare("SELECT COUNT(*) FROM transaksi_keuangan WHERE kategori = 'Iuran Pengqurban' AND jumlah = :jumlah AND DATE(tgl_transaksi) = :tgl_bayar_date AND deskripsi LIKE :desc_nik");
                    $stmt_check_transaksi->bindParam(':jumlah', $jumlah_iuran_value);
                    $stmt_check_transaksi->bindParam(':tgl_bayar_date', $tgl_bayar_iuran); 
                    $stmt_check_transaksi->bindValue(':desc_nik', '%NIK: ' . $nik_pengqurban_iuran . '%');
                    $stmt_check_transaksi->execute();

                    if ($stmt_check_transaksi->fetchColumn() == 0) {
                        $kategori_pemasukan = 'Iuran Pengqurban';
                        $stmt_nama_pengqurban = $conn->prepare("SELECT nm_lengkap FROM users WHERE NIK = :nik");
                        $stmt_nama_pengqurban->bindParam(':nik', $nik_pengqurban_iuran);
                        $stmt_nama_pengqurban->execute();
                        $nama_pengqurban = $stmt_nama_pengqurban->fetchColumn() ?? 'Nama Tidak Diketahui';

                        $deskripsi_pemasukan = 'Pembayaran iuran dari ' . $nama_pengqurban . ' (NIK: ' . $nik_pengqurban_iuran . ').';
                        $tipe_transaksi_pemasukan = 'Pemasukan'; 

                        $stmt_transaksi = $conn->prepare("INSERT INTO transaksi_keuangan (tipe_transaksi, kategori, deskripsi, jumlah, tgl_transaksi) VALUES (:tipe_transaksi, :kategori, :deskripsi, :jumlah, :tgl_transaksi)");
                        $stmt_transaksi->bindParam(':tipe_transaksi', $tipe_transaksi_pemasukan);
                        $stmt_transaksi->bindParam(':kategori', $kategori_pemasukan);
                        $stmt_transaksi->bindParam(':deskripsi', $deskripsi_pemasukan);
                        $stmt_transaksi->bindParam(':jumlah', $jumlah_iuran_value);
                        $stmt_transaksi->bindParam(':tgl_transaksi', date('Y-m-d H:i:s'));
                        $stmt_transaksi->execute();
                    }
                }

                $conn->commit();
                $success_message = 'Status pembayaran iuran berhasil diperbarui.';
            } else {
                $conn->rollBack();
                $error_message = 'Gagal memperbarui status pembayaran iuran.';
            }
        } else {
            $error_message = 'Iuran tidak ditemukan.';
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error_message = 'Terjadi kesalahan database saat memperbarui status: ' . $e->getMessage();
        error_log("Update iuran status error: " . $e->getMessage());
    }
}


// Ambil semua data iuran dari database, join dengan users dan hewan_qurban
try {
    $stmt_iuran = $conn->prepare("
        SELECT 
            ip.id_iuran,
            u.nm_lengkap AS nama_pengqurban,
            hq.jenis_hewan,
            hq.jumlah_bagian,
            ip.jumlah_iuran,
            ip.tgl_bayar,
            ip.status_bayar
        FROM 
            iuran_pengqurban ip
        JOIN 
            users u ON ip.NIK_pengqurban = u.NIK
        JOIN 
            hewan_qurban hq ON ip.id_hewanqurban = hq.id_hewan
        WHERE
            u.level LIKE '%pengqurban%' -- Pastikan hanya user berlevel pengqurban yang tampil di daftar ini
        ORDER BY 
            ip.tgl_bayar DESC
    ");
    $stmt_iuran->execute();
    $iuran_list = $stmt_iuran->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data iuran: " . $e->getMessage();
    error_log("Fetch iuran_pengqurban error: " . $e->getMessage());
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'add_iuran_success') {
        $success_message = 'Iuran pengqurban berhasil ditambahkan!';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Iuran Pengqurban</title>
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
                                <h5>Daftar Iuran Pengqurban</h5>
                                <div class="ibox-tools">
                                    <a href="add_iuran_pengqurban.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Tambah Iuran Baru
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
                                                <th>Nama Pengqurban</th>
                                                <th>Hewan Qurban</th>
                                                <th>Jumlah Iuran (Rp)</th>
                                                <th>Tanggal Bayar</th>
                                                <th>Status Bayar</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php foreach ($iuran_list as $iuran): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($iuran['nama_pengqurban']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($iuran['jenis_hewan']) . ' (' . $iuran['jumlah_bagian'] . ' Bagian)'); ?></td>
                                                    <td>Rp <?php echo number_format($iuran['jumlah_iuran'], 0, ',', '.'); ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($iuran['tgl_bayar']))); ?></td>
                                                    <td>
                                                        <?php 
                                                            $status_label = ($iuran['status_bayar'] == 'lunas') ? 'label-primary' : 'label-warning';
                                                            echo '<span class="label ' . $status_label . '">' . htmlspecialchars(ucfirst($iuran['status_bayar'])) . '</span>';
                                                        ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="?action=change_status&id=<?php echo $iuran['id_iuran']; ?>" 
                                                           class="btn btn-info btn-xs" title="Ubah Status Pembayaran"
                                                           onclick="return confirm('Apakah Anda yakin ingin mengubah status pembayaran iuran ini?');">
                                                            <i class="fa fa-refresh"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?php echo $iuran['id_iuran']; ?>" 
                                                           class="btn btn-danger btn-xs" title="Hapus Iuran"
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus iuran ini?');">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($iuran_list)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Belum ada data iuran pengqurban.</td>
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
                    {extend: 'excel', title: 'DaftarIuranPengqurban'},
                    {extend: 'pdf', title: 'DaftarIuranPengqurban'},
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