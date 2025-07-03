<?php
// qurban_app/admin/laporan_distribusi.php
// Halaman untuk Administrator melihat laporan distribusi daging
// Disesuaikan dengan skema database baru (NIK_penerima, NIK_panitia_konfirm)
// Menampilkan QR Code yang dihasilkan oleh Endroid\QrCode.

session_start();

// Definisikan ROOT_PATH proyek Anda
define('ROOT_PATH', dirname(__DIR__)); // Ini akan menghasilkan path ke qurban_app/

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';
$distribusi_list = []; // Untuk menyimpan data distribusi

// Logika untuk menghapus distribusi (jika ada permintaan DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_distribusi_to_delete = $_GET['id'];

    try {
        $conn->beginTransaction(); // Mulai transaksi

        // Hapus file QR Code terkait terlebih dahulu
        $stmt_get_qr_filename = $conn->prepare("SELECT kd_qr FROM distribusi_daging WHERE id_distribusi = :id_distribusi");
        $stmt_get_qr_filename->bindParam(':id_distribusi', $id_distribusi_to_delete);
        $stmt_get_qr_filename->execute();
        $qr_filename = $stmt_get_qr_filename->fetchColumn();

        // Hapus data dari tabel distribusi_daging (FOREIGN KEY akan menangani)
        $stmt_delete_distribusi = $conn->prepare("DELETE FROM distribusi_daging WHERE id_distribusi = :id_distribusi");
        $stmt_delete_distribusi->bindParam(':id_distribusi', $id_distribusi_to_delete);
        
        if ($stmt_delete_distribusi->execute()) {
            // Setelah data dihapus dari DB, baru hapus filenya
            if ($qr_filename && file_exists(ROOT_PATH . '/qrcodes/' . $qr_filename)) {
                unlink(ROOT_PATH . '/qrcodes/' . $qr_filename); // Hapus file dari folder qrcodes/
                $success_message = 'Data distribusi dan QR Code terkait berhasil dihapus.';
            } else {
                $success_message = 'Data distribusi berhasil dihapus, namun file QR Code tidak ditemukan atau tidak dapat dihapus.';
            }
            $conn->commit();
        } else {
            $conn->rollBack();
            $error_message = 'Gagal menghapus data distribusi.';
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error_message = 'Terjadi kesalahan database saat menghapus distribusi: ' . $e->getMessage();
        error_log("Delete distribusi_daging error: " . $e->getMessage());
    } catch (Exception $e) { // Tangani error saat menghapus file
        $conn->rollBack();
        $error_message = 'Gagal menghapus file QR Code: ' . $e->getMessage();
        error_log("Delete QR file error: " . $e->getMessage());
    }
}

// Logika untuk mengubah status pengambilan (dari "belum_diambil" ke "sudah_diambil" atau sebaliknya)
if (isset($_GET['action']) && $_GET['action'] == 'change_status' && isset($_GET['id'])) {
    $id_distribusi_to_update = $_GET['id'];
    $nik_panitia_konfirmator = $_SESSION['user_nik'] ?? null; // NIK admin yang sedang login

    try {
        if (!$nik_panitia_konfirmator) {
             $error_message = 'NIK Panitia Konfirmator tidak ditemukan di sesi.';
        } else {
            $stmt_get_status = $conn->prepare("SELECT status_pengambilan FROM distribusi_daging WHERE id_distribusi = :id_distribusi");
            $stmt_get_status->bindParam(':id_distribusi', $id_distribusi_to_update);
            $stmt_get_status->execute();
            $current_status = $stmt_get_status->fetchColumn();

            $new_status = ($current_status == 'belum_diambil') ? 'sudah_diambil' : 'belum_diambil'; // Toggle status

            $stmt_update_status = $conn->prepare("UPDATE distribusi_daging SET status_pengambilan = :new_status, NIK_panitia_konfirm = :nik_panitia_konfirm WHERE id_distribusi = :id_distribusi");
            $stmt_update_status->bindParam(':new_status', $new_status);
            $stmt_update_status->bindParam(':nik_panitia_konfirm', $nik_panitia_konfirmator);
            $stmt_update_status->bindParam(':id_distribusi', $id_distribusi_to_update);

            if ($stmt_update_status->execute()) {
                $success_message = 'Status pengambilan distribusi berhasil diperbarui.';
            } else {
                $error_message = 'Gagal memperbarui status pengambilan distribusi.';
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Terjadi kesalahan database saat memperbarui status: ' . $e->getMessage();
        error_log("Update distribusi status error: " . $e->getMessage());
    }
}


// Ambil semua data distribusi dari database, join dengan users (untuk nama penerima dan nama panitia konfirmator)
try {
    $stmt_distribusi = $conn->prepare("
        SELECT 
            dd.id_distribusi,
            u_penerima.nm_lengkap AS nama_penerima, -- Nama warga penerima
            u_penerima.NIK AS NIK_penerima_val, -- NIK warga penerima
            dd.jumlah_daging_sapi,
            dd.jumlah_daging_kambing,
            dd.tgl_distribusi,
            dd.kd_qr,
            dd.status_pengambilan,
            u_konfirm.nm_lengkap AS nama_panitia_konfirm -- Nama panitia yang mengkonfirmasi
        FROM 
            distribusi_daging dd
        JOIN 
            users u_penerima ON dd.NIK_penerima = u_penerima.NIK
        LEFT JOIN -- Gunakan LEFT JOIN karena NIK_panitia_konfirm bisa NULL
            users u_konfirm ON dd.NIK_panitia_konfirm = u_konfirm.NIK
        ORDER BY 
            dd.tgl_distribusi DESC
    ");
    $stmt_distribusi->execute();
    $distribusi_list = $stmt_distribusi->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data distribusi: " . $e->getMessage();
    error_log("Fetch laporan_distribusi error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Laporan Distribusi Daging</title>
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
                                <h5>Laporan Distribusi Daging Qurban</h5>
                                <div class="ibox-tools">
                                    <a href="distribusi_daging.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Catat Distribusi Baru
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
                                                <th>Warga Penerima</th>
                                                <th>NIK Penerima</th>
                                                <th>Daging Sapi (Kg)</th>
                                                <th>Daging Kambing (Kg)</th>
                                                <th>Tgl. Distribusi</th>
                                                <th>QR Code</th>
                                                <th>Status Pengambilan</th>
                                                <th>Konfirmator</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php foreach ($distribusi_list as $distribusi): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($distribusi['nama_penerima']); ?></td>
                                                    <td><?php echo htmlspecialchars($distribusi['NIK_penerima_val']); ?></td>
                                                    <td><?php echo number_format($distribusi['jumlah_daging_sapi'], 2, ',', '.'); ?></td>
                                                    <td><?php echo number_format($distribusi['jumlah_daging_kambing'], 2, ',', '.'); ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($distribusi['tgl_distribusi']))); ?></td>
                                                    <td class="text-center">
                                                        <?php if (!empty($distribusi['kd_qr'])): ?>
                                                            <a href="../qrcodes/<?php echo htmlspecialchars($distribusi['kd_qr']); ?>" target="_blank" title="Lihat QR Code">
                                                                <img src="../qrcodes/<?php echo htmlspecialchars($distribusi['kd_qr']); ?>" alt="QR Code" style="width: 50px; height: 50px; display: block; margin: 0 auto;">
                                                            </a>
                                                            <a href="../qrcodes/<?php echo htmlspecialchars($distribusi['kd_qr']); ?>" download class="btn btn-xs btn-default m-t-xs">
                                                                <i class="fa fa-download"></i> Unduh
                                                            </a>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $status_label = ($distribusi['status_pengambilan'] == 'sudah_diambil') ? 'label-primary' : 'label-warning';
                                                            echo '<span class="label ' . $status_label . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $distribusi['status_pengambilan']))) . '</span>';
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($distribusi['nama_panitia_konfirm'] ?? '-'); ?></td>
                                                    <td class="text-center">
                                                        <a href="?action=change_status&id=<?php echo $distribusi['id_distribusi']; ?>" 
                                                           class="btn btn-info btn-xs" title="Ubah Status Pengambilan"
                                                           onclick="return confirm('Apakah Anda yakin ingin mengubah status pengambilan daging ini?');">
                                                            <i class="fa fa-refresh"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?php echo $distribusi['id_distribusi']; ?>" 
                                                           class="btn btn-danger btn-xs" title="Hapus Distribusi"
                                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data distribusi ini? Ini juga akan menghapus QR Code terkait.');">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($distribusi_list)): ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">Belum ada data distribusi daging.</td>
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
                    {extend: 'excel', title: 'LaporanDistribusiDaging'},
                    {extend: 'pdf', title: 'LaporanDistribusiDaging'},
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