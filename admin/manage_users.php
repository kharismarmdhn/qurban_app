<?php
// qurban_app/admin/manage_users.php
// Halaman untuk Administrator melihat daftar semua pengguna yang terdaftar
// Disesuaikan untuk NIK sebagai PK, level tunggal.

session_start();

define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';
$users = []; // Untuk menyimpan data pengguna

// Logika untuk menghapus user (jika ada permintaan DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['nik'])) {
    $nik_to_delete = $_GET['nik'];

    try {
        $conn->beginTransaction(); // Mulai transaksi

        // Dapatkan data user yang akan dihapus
        $stmt_get_user = $conn->prepare("SELECT username, NIK, level FROM users WHERE NIK = :nik");
        $stmt_get_user->bindParam(':nik', $nik_to_delete);
        $stmt_get_user->execute();
        $user_data_to_delete = $stmt_get_user->fetch(PDO::FETCH_ASSOC);

        if ($user_data_to_delete) {
            // Cek apakah user yang dihapus adalah admin utama (NIK='ADMINNIK123')
            // Atau user yang sedang login
            if ($nik_to_delete == 'ADMINNIK123' || $nik_to_delete == $_SESSION['user_nik']) {
                $error_message = 'Tidak diizinkan menghapus akun Administrator utama atau akun yang sedang login.';
                $conn->rollBack();
            } else {
                // Saat menghapus user, NIK mereka mungkin masih ada di tabel lain
                // Karena NIK tidak lagi PK, integritas FK harus dijaga di level aplikasi.
                // Kita akan set NIK di tabel lain (distribusi_daging, iuran_pengqurban) menjadi NULL
                // atau di-restrict jika ada data. Untuk ini, kita SET NULL agar tidak error.

                // Set NIK_penerima di distribusi_daging menjadi NULL jika user ini penerima
                $stmt_update_distribusi_penerima = $conn->prepare("UPDATE distribusi_daging SET NIK_penerima = NULL WHERE NIK_penerima = :nik_user");
                $stmt_update_distribusi_penerima->bindParam(':nik_user', $nik_to_delete);
                $stmt_update_distribusi_penerima->execute();

                // Set NIK_panitia_konfirm di distribusi_daging menjadi NULL jika user ini panitia konfirmator
                $stmt_update_distribusi_konfirm = $conn->prepare("UPDATE distribusi_daging SET NIK_panitia_konfirm = NULL WHERE NIK_panitia_konfirm = :nik_user");
                $stmt_update_distribusi_konfirm->bindParam(':nik_user', $nik_to_delete);
                $stmt_update_distribusi_konfirm->execute();
                
                // Set NIK_pengqurban di iuran_pengqurban menjadi NULL jika user ini pengqurban
                $stmt_update_iuran = $conn->prepare("UPDATE iuran_pengqurban SET NIK_pengqurban = NULL WHERE NIK_pengqurban = :nik_user");
                $stmt_update_iuran->bindParam(':nik_user', $nik_to_delete);
                $stmt_update_iuran->execute();


                // Hapus data dari tabel `users`
                $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE NIK = :nik");
                $stmt_delete_user->bindParam(':nik', $nik_to_delete);
                
                if ($stmt_delete_user->execute() && $stmt_delete_user->rowCount() > 0) {
                    $conn->commit();
                    $success_message = 'Pengguna ' . htmlspecialchars($user_data_to_delete['username']) . ' berhasil dihapus.';
                } else {
                    $conn->rollBack();
                    $error_message = 'Gagal menghapus pengguna atau pengguna tidak ditemukan.';
                }
            }
        } else {
            $conn->rollBack();
            $error_message = 'Pengguna tidak ditemukan.';
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error_message = 'Terjadi kesalahan database saat menghapus: ' . $e->getMessage();
        error_log("Delete user error in manage_users.php: " . $e->getMessage());
    }
}


// Ambil semua data pengguna dari database (tabel `users` sekarang menampung semua)
try {
    $stmt_users = $conn->prepare("
        SELECT 
            NIK, 
            username, 
            nm_lengkap,
            level
        FROM 
            users 
        ORDER BY 
            nm_lengkap ASC
    ");
    $stmt_users->execute();
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Gagal mengambil data pengguna: " . $e->getMessage();
    error_log("Fetch users error in manage_users.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Daftar Pengguna</title>
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
                                <h5>Daftar Semua Pengguna</h5>
                                <div class="ibox-tools">
                                    <a href="register_user.php" class="btn btn-primary btn-xs">
                                        <i class="fa fa-plus"></i> Tambah User Baru
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
                                                <th>NIK</th>
                                                <th>Username</th>
                                                <th>Nama Lengkap</th>
                                                <th>Level</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($user['NIK']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['nm_lengkap']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($user['level'])); ?></td>
                                                    <td class="text-center">
                                                        <a href="user_detail.php?nik=<?php echo htmlspecialchars($user['NIK']); ?>" class="btn btn-info btn-xs" title="Detail">
                                                            <i class="fa fa-info-circle"></i>
                                                        </a>
                                                        <a href="edit_user.php?nik=<?php echo htmlspecialchars($user['NIK']); ?>" class="btn btn-warning btn-xs" title="Edit">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>
                                                        <?php if ($user['NIK'] == 'ADMINNIK123' || $user['NIK'] == $_SESSION['user_nik']): // Mencegah hapus admin utama atau user yang sedang login ?>
                                                            <button class="btn btn-danger btn-xs" title="Tidak dapat dihapus" disabled>
                                                                <i class="fa fa-ban"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <a href="?action=delete&nik=<?php echo htmlspecialchars($user['NIK']); ?>" 
                                                               class="btn btn-danger btn-xs" title="Hapus"
                                                               onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Data terkait di distribusi dan iuran akan di-NULL-kan atau di-reset.');">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($users)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">Tidak ada data pengguna.</td>
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
                    {extend: 'excel', title: 'DaftarPengguna'},
                    {extend: 'pdf', title: 'DaftarPengguna'},
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