<?php
// qurban_app/admin/user_detail.php
// Halaman untuk Administrator melihat detail lengkap satu pengguna
// Disesuaikan untuk NIK sebagai PK, level tunggal.

session_start();

define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$user_data = null;
$error_message = '';

// Pastikan ada NIK user yang dikirim melalui URL
if (isset($_GET['nik'])) {
    $user_nik = $_GET['nik'];

    try {
        // Ambil data user dari tabel 'users' (sekarang menampung semua detail)
        $stmt_user = $conn->prepare("
            SELECT 
                NIK, 
                username, 
                nm_lengkap, 
                email, 
                telp, 
                alamat, 
                jk, 
                RT, 
                RW, 
                level 
            FROM 
                users 
            WHERE 
                NIK = :nik
        ");
        $stmt_user->bindParam(':nik', $user_nik);
        $stmt_user->execute();
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            $error_message = "Pengguna tidak ditemukan.";
        }

    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database: " . $e->getMessage();
        error_log("User detail error in user_detail.php: " . $e->getMessage());
    }
} else {
    $error_message = "NIK Pengguna tidak diberikan.";
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Detail Pengguna</title>
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
                                <h5>Detail Pengguna</h5>
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
                                    <div class="alert alert-danger">
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php else: ?>
                                    <?php if ($user_data): ?>
                                        <div class="form-horizontal">
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">NIK:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($user_data['NIK']); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Username:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($user_data['username']); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Nama Lengkap:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($user_data['nm_lengkap']); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Level:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars(ucfirst($user_data['level'])); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Email:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($user_data['email'] ?? '-'); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">No. Telepon:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($user_data['telp'] ?? '-'); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Alamat:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo nl2br(htmlspecialchars($user_data['alamat'] ?? '-')); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">Jenis Kelamin:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($user_data['jk'] == 'L' ? 'Laki-laki' : ($user_data['jk'] == 'P' ? 'Perempuan' : '-')); ?></p></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-3 control-label">RT/RW:</label>
                                                <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($user_data['RT'] ?? '-') . '/' . htmlspecialchars($user_data['RW'] ?? '-'); ?></p></div>
                                            </div>

                                            <div class="hr-line-dashed"></div>
                                            <div class="form-group">
                                                <div class="col-sm-9 col-sm-offset-3">
                                                    <a href="manage_users.php" class="btn btn-white">Kembali ke Daftar Pengguna</a>
                                                    <?php if ($user_data['NIK'] != 'ADMINNIK123'): // Admin utama tidak bisa diedit/dihapus dari sini ?>
                                                        <a href="edit_user.php?nik=<?php echo htmlspecialchars($user_data['NIK']); ?>" class="btn btn-warning">Edit Pengguna</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'templates/footer.php'; ?>
        </div>
    </div>

    <?php include 'templates/scripts.php'; ?>

</body>
</html>