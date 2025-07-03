<?php
// qurban_app/pengqurban/status_iuran.php
// Halaman untuk Pengqurban melihat status iuran mereka
// Disesuaikan untuk skema database baru (NIK_pengqurban sebagai FK)

session_start();

include '../config/db.php';
include '../config/auth_check.php';

// Pastikan hanya pengqurban ('pengqurban') yang bisa mengakses halaman ini
checkUserAccess(['pengqurban']); 

$error_message = '';
$iuran_saya_list = []; // Untuk menyimpan data iuran pengqurban ini

// Ambil NIK warga dari user yang sedang login
$pengqurban_nik_login = $_SESSION['user_nik'] ?? null;

// Ambil semua data iuran pengqurban yang sedang login
if ($pengqurban_nik_login) {
    try {
        $stmt_iuran = $conn->prepare("
            SELECT 
                ip.id_iuran,
                hq.jenis_hewan,
                hq.jumlah_bagian,
                ip.jumlah_iuran,
                ip.tgl_bayar,
                ip.status_bayar
            FROM 
                iuran_pengqurban ip
            JOIN 
                hewan_qurban hq ON ip.id_hewanqurban = hq.id_hewan
            WHERE
                ip.NIK_pengqurban = :nik_pengqurban
            ORDER BY 
                ip.tgl_bayar DESC
        ");
        $stmt_iuran->bindParam(':nik_pengqurban', $pengqurban_nik_login);
        $stmt_iuran->execute();
        $iuran_saya_list = $stmt_iuran->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_message = "Gagal mengambil data iuran Anda: " . $e->getMessage();
        error_log("Fetch pengqurban iuran error: " . $e->getMessage());
    }
} else {
    $error_message = "Data pengqurban Anda tidak ditemukan. Harap hubungi admin.";
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Status Iuran Saya</title>
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
                                <h5>Status Iuran Qurban Saya</h5>
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
                                                <th>Hewan Qurban</th>
                                                <th>Jumlah Iuran (Rp)</th>
                                                <th>Tanggal Bayar</th>
                                                <th>Status Bayar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php if (!empty($iuran_saya_list)): ?>
                                                <?php foreach ($iuran_saya_list as $iuran): ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
                                                        <td><?php echo htmlspecialchars(ucfirst($iuran['jenis_hewan']) . ' (' . $iuran['jumlah_bagian'] . ' Bagian)'); ?></td>
                                                        <td>Rp <?php echo number_format($iuran['jumlah_iuran'], 0, ',', '.'); ?></td>
                                                        <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($iuran['tgl_bayar']))); ?></td>
                                                        <td>
                                                            <?php 
                                                                $status_label = ($iuran['status_bayar'] == 'lunas') ? 'label-primary' : 'label-warning';
                                                                echo '<span class="label ' . $status_label . '">' . htmlspecialchars(ucfirst($iuran['status_bayar'])) . '</span>';
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Belum ada data iuran untuk Anda.</td>
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
                    {extend: 'excel', title: 'StatusIuranSaya'},
                    {extend: 'pdf', title: 'StatusIuranSaya'},
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