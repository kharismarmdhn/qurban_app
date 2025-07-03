<?php
// qurban_app/panitia/index.php
// Dashboard utama untuk Panitia - Menampilkan daftar distribusi daging
// Disesuaikan untuk skema database baru (NIK sebagai PK, detail profil di tabel users, kolom 'level' string)

session_start();

// Definisikan ROOT_PATH proyek Anda
define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya panitia ('panitia') atau admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['panitia', 'admin']); 

$success_message = '';
$error_message = '';
$distribusi_list = []; // Untuk menyimpan data distribusi

// Logika untuk mengubah status pengambilan (dari "belum_diambil" ke "sudah_diambil" atau sebaliknya)
// Ini sama dengan di admin/laporan_distribusi.php
if (isset($_GET['action']) && $_GET['action'] == 'change_status' && isset($_GET['id'])) {
    $id_distribusi_to_update = $_GET['id'];
    $nik_panitia_konfirmator = $_SESSION['user_nik'] ?? null; // NIK panitia/admin yang sedang login

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
// Fokus pada yang belum diambil, tapi tetap tampilkan semua agar bisa toggle status
try {
    $stmt_distribusi = $conn->prepare("
        SELECT 
            dd.id_distribusi,
            u_penerima.nm_lengkap AS nama_penerima, 
            u_penerima.NIK AS NIK_penerima_val, 
            dd.jumlah_daging_sapi,
            dd.jumlah_daging_kambing,
            dd.tgl_distribusi,
            dd.kd_qr,
            dd.status_pengambilan,
            u_konfirm.nm_lengkap AS nama_panitia_konfirm 
        FROM 
            distribusi_daging dd
        JOIN 
            users u_penerima ON dd.NIK_penerima = u_penerima.NIK
        LEFT JOIN 
            users u_konfirm ON dd.NIK_panitia_konfirm = u_konfirm.NIK
        ORDER BY 
            dd.tgl_distribusi DESC, dd.status_pengambilan ASC -- Belum diambil di atas
    ");
    $stmt_distribusi->execute();
    $distribusi_list = $stmt_distribusi->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data distribusi: " . $e->getMessage();
    error_log("Fetch panitia_distribusi_list error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Dashboard Panitia</title>
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
                                <h5>Daftar Distribusi Daging (Verifikasi Manual)</h5>
                                <div class="ibox-tools">
                                    <a href="verifikasi_qr.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-qrcode"></i> Verifikasi Via QR Code
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
                                                        </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($distribusi_list)): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">Belum ada data distribusi daging.</td>
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
                    {extend: 'excel', title: 'DaftarDistribusiPanitia'},
                    {extend: 'pdf', title: 'DaftarDistribusiPanitia'},
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