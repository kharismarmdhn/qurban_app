<?php
// qurban_app/warga/pengambilan_daging.php
// Halaman untuk Warga melihat detail pengambilan daging
// Mengembalikan struktur template INSPINIA lengkap (sidebar, top_navbar, footer).

session_start();

// Definisikan ROOT_PATH proyek Anda
define('ROOT_PATH', dirname(__DIR__)); // Ini akan menghasilkan path ke qurban_app/

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya warga ('warga') yang bisa mengakses halaman ini
checkUserAccess(['warga']); 

$error_message = '';
$distribusi_daging_warga_list = []; // Untuk menyimpan daftar alokasi daging

// Ambil NIK warga dari user yang sedang login
$warga_nik_login = $_SESSION['user_nik'] ?? null;

// Ambil semua data distribusi daging untuk warga yang sedang login
if ($warga_nik_login) {
    try {
        $stmt_distribusi = $conn->prepare("
            SELECT 
                id_distribusi,
                jumlah_daging_sapi,
                jumlah_daging_kambing,
                tgl_distribusi,
                kd_qr,
                status_pengambilan
            FROM 
                distribusi_daging
            WHERE 
                NIK_penerima = :nik_warga
            ORDER BY 
                tgl_distribusi DESC
        ");
        $stmt_distribusi->bindParam(':nik_warga', $warga_nik_login);
        $stmt_distribusi->execute();
        $distribusi_daging_warga_list = $stmt_distribusi->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_message = "Gagal mengambil data alokasi daging Anda: " . $e->getMessage();
        error_log("Fetch warga_pengambilan_daging error: " . $e->getMessage());
    }
} else {
    $error_message = "Data warga Anda tidak ditemukan. Harap hubungi admin.";
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Riwayat Pengambilan Daging</title>
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
                                <h5>Riwayat Alokasi Pengambilan Daging Qurban Anda</h5>
                                <div class="ibox-tools">
                                    <a class="collapse-link">
                                        <i class="fa fa-chevron-up"></i>
                                    </a>
                                    <a class="close-link">
                                        <i class="fa fa-times"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="ibox-content">
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
                                                <th>Daging Sapi (Kg)</th>
                                                <th>Daging Kambing (Kg)</th>
                                                <th>Tgl. Distribusi</th>
                                                <th>QR Code</th>
                                                <th>Status Pengambilan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php if (!empty($distribusi_daging_warga_list)): ?>
                                                <?php foreach ($distribusi_daging_warga_list as $distribusi): ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
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
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">Belum ada alokasi daging qurban untuk Anda.</td>
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
                    {extend: 'excel', title: 'AlokasiPengambilanDaging'},
                    {extend: 'pdf', title: 'AlokasiPengambilanDaging'},
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