<?php
// qurban_app/admin/register_user.php
// Halaman untuk Administrator mendaftarkan user baru (Warga, Panitia, Pengqurban)
// Disesuaikan: Semua field personal (NIK, Nama, JK, Alamat, RT/RW, Telp, Email) wajib diisi untuk semua user.

session_start();

include '../config/db.php';
include '../config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$success_message = '';
$error_message = '';

// Inisialisasi variabel untuk mengisi kembali form jika ada error
$nik = '';
$username = '';
$password = ''; // Password tidak pernah ditampilkan kembali
$nm_lengkap = '';
$email = '';
$telp = '';
$alamat = '';
$jk = '';
$rt = '';
$rw = '';
$selected_levels = []; // Akan menyimpan array level yang dipilih

// Daftar level yang tersedia (karena tidak ada lagi tabel 'level')
$available_levels = [
    'warga' => 'Warga',
    'panitia' => 'Panitia',
    'pengqurban' => 'Pengqurban',
    // 'admin' => 'Administrator' // Admin tidak bisa mendaftarkan admin lain dari sini
];

// Proses form registrasi jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik = trim($_POST['NIK'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $posted_levels = $_POST['level'] ?? []; // Ini akan menjadi array dari checkbox/select multiple
    $nm_lengkap = trim($_POST['nm_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telp = trim($_POST['telp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $jk = $_POST['jk'] ?? '';
    $rt = trim($_POST['RT'] ?? '');
    $rw = trim($_POST['RW'] ?? '');

    // Konversi array level terpilih menjadi string terpisah koma
    sort($posted_levels);
    $level_string = implode(',', $posted_levels);
    $selected_levels = $posted_levels; // Simpan untuk mengisi kembali form

    // Validasi semua field personal wajib diisi
    if (empty($nik) || empty($username) || empty($password) || empty($nm_lengkap) || empty($level_string) || 
        empty($telp) || empty($alamat) || empty($jk) || empty($rt) || empty($rw)) {
        $error_message = 'Semua field (termasuk data KTP) wajib diisi.';
    } elseif (!is_numeric($telp)) {
        $error_message = 'Nomor telepon harus berupa angka.';
    } else {
        try {
            $conn->beginTransaction(); // Mulai transaksi database

            // 1. Cek apakah NIK sudah ada
            $stmt_check_nik = $conn->prepare("SELECT COUNT(*) FROM users WHERE NIK = :nik");
            $stmt_check_nik->bindParam(':nik', $nik);
            $stmt_check_nik->execute();
            if ($stmt_check_nik->fetchColumn() > 0) {
                $error_message = 'NIK sudah terdaftar. Silakan gunakan NIK lain atau edit user yang sudah ada.';
                $conn->rollBack();
            } else {
                // 2. Cek apakah username sudah ada
                $stmt_check_username = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmt_check_username->bindParam(':username', $username);
                $stmt_check_username->execute();
                if ($stmt_check_username->fetchColumn() > 0) {
                    $error_message = 'Username sudah digunakan. Silakan pilih username lain.';
                    $conn->rollBack();
                } else {
                    // 3. Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // 4. Masukkan data ke tabel 'users' dengan semua detail profil dan level
                    $stmt_user = $conn->prepare("INSERT INTO users (NIK, username, password, nm_lengkap, email, telp, alamat, jk, RT, RW, level) 
                                                VALUES (:nik, :username, :hashed_password, :nm_lengkap, :email, :telp, :alamat, :jk, :rt, :rw, :level)");
                    $stmt_user->bindParam(':nik', $nik);
                    $stmt_user->bindParam(':username', $username);
                    $stmt_user->bindParam(':hashed_password', $hashed_password);
                    $stmt_user->bindParam(':nm_lengkap', $nm_lengkap);
                    $stmt_user->bindParam(':email', $email);
                    $stmt_user->bindParam(':telp', $telp);
                    $stmt_user->bindParam(':alamat', $alamat);
                    $stmt_user->bindParam(':jk', $jk);
                    $stmt_user->bindParam(':rt', $rt);
                    $stmt_user->bindParam(':rw', $rw);
                    $stmt_user->bindParam(':level', $level_string);

                    if ($stmt_user->execute()) {
                        $conn->commit(); // Komit transaksi jika semua berhasil
                        $success_message = 'User berhasil didaftarkan!';
                        
                        // Reset form setelah sukses
                        $nik = ''; $username = ''; $password = ''; $nm_lengkap = ''; $email = '';
                        $telp = ''; $alamat = ''; $jk = ''; $rt = ''; $rw = ''; $selected_levels = [];
                    } else {
                        $conn->rollBack();
                        $error_message = 'Gagal menambahkan user.';
                    }
                }
            }

        } catch (PDOException $e) {
            $conn->rollBack(); // Rollback transaksi jika ada error
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Registrasi user error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Registrasi User</title>
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
                                <h5>Registrasi User Baru</h5>
                            </div>
                            <div class="ibox-content">
                                <?php if (!empty($success_message)): ?>
                                    <div class="alert alert-success">
                                        <?php echo $success_message; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger">
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="register_user.php" class="form-horizontal">
                                    <div class="form-group"><label class="col-sm-2 control-label">NIK</label>
                                        <div class="col-sm-10"><input type="text" name="NIK" class="form-control" required value="<?php echo htmlspecialchars($nik); ?>"></div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Username</label>
                                        <div class="col-sm-10"><input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($username); ?>"></div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Password</label>
                                        <div class="col-sm-10"><input type="password" name="password" class="form-control" required></div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Nama Lengkap</label>
                                        <div class="col-sm-10"><input type="text" name="nm_lengkap" class="form-control" required value="<?php echo htmlspecialchars($nm_lengkap); ?>"></div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">Email</label>
                                        <div class="col-sm-10"><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>"></div>
                                    </div>
                                    <div class="form-group"><label class="col-sm-2 control-label">No. Telepon</label>
                                        <div class="col-sm-10"><input type="text" name="telp" class="form-control" required value="<?php echo htmlspecialchars($telp); ?>"></div>
                                    </div>

                                    <div class="form-group"><label class="col-sm-2 control-label">Alamat</label>
                                        <div class="col-sm-10"><textarea name="alamat" class="form-control" required><?php echo htmlspecialchars($alamat); ?></textarea></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">Jenis Kelamin</label>
                                        <div class="col-sm-10">
                                            <select name="jk" class="form-control m-b" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="L" <?php echo ($jk == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                                <option value="P" <?php echo ($jk == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">RT</label>
                                        <div class="col-sm-4"><input type="text" name="RT" class="form-control" required value="<?php echo htmlspecialchars($rt); ?>"></div>
                                        <label class="col-sm-2 control-label">RW</label>
                                        <div class="col-sm-4"><input type="text" name="RW" class="form-control" required value="<?php echo htmlspecialchars($rw); ?>"></div>
                                    </div>

                                    <div class="form-group"><label class="col-sm-2 control-label">Level User</label>
                                        <div class="col-sm-10">
                                            <select name="level[]" id="level_select" class="form-control m-b" multiple required>
                                                <?php foreach ($available_levels as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" 
                                                        <?php echo in_array($value, $selected_levels) ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="help-block m-b-none">Tekan Ctrl/Cmd untuk memilih lebih dari satu level.</span>
                                        </div>
                                    </div>
                                    

                                    <div class="hr-line-dashed"></div>
                                    <div class="form-group">
                                        <div class="col-sm-4 col-sm-offset-2">
                                            <button class="btn btn-primary" type="submit">Daftarkan User</button>
                                            <a href="manage_users.php" class="btn btn-white">Batal</a>
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

    <script>
        $(document).ready(function() {
            // Karena semua field personal sekarang wajib diisi, tidak perlu lagi fungsi toggleRoleFields
            // atau mengatur 'required' secara dinamis untuk field-field tersebut.
            // Hanya biarkan validasi 'required' pada HTML.
            // Jika ada logika khusus untuk dropdown level yang mempengaruhi field lain (selain yang dihapus),
            // maka logika tersebut tetap di sini.
            // Untuk saat ini, fungsi toggleRoleFields dihapus.
        });
    </script>
</body>
</html>