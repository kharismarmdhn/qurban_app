<?php
// qurban_app/admin/edit_user.php
// Halaman untuk Administrator mengedit data pengguna
// Disesuaikan: NIK sebagai PK, level tunggal, semua data KTP wajib.

session_start();

define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['admin']); 

$user_nik_from_get = $_GET['nik'] ?? null; // NIK user yang akan diedit, diambil dari URL
$user_data_from_db = null; // Akan menampung data user dari DB
$success_message = '';
$error_message = '';

// Daftar level yang tersedia
$available_levels = [
    'warga' => 'Warga',
    'panitia' => 'Panitia',
    'pengqurban' => 'Pengqurban',
    'admin' => 'Administrator' 
];

// --- FASE 1: AMBIL DATA USER SAAT PERTAMA KALI HALAMAN DIBUKA (GET Request) ---
if ($user_nik_from_get) {
    try {
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
        $stmt_user->bindParam(':nik', $user_nik_from_get);
        $stmt_user->execute();
        $user_data_from_db = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user_data_from_db) {
            $error_message = "Pengguna tidak ditemukan.";
            $user_nik_from_get = null; // Set null agar form tidak ditampilkan
        } elseif ($user_data_from_db['NIK'] == 'ADMINNIK123') { // Admin utama
            // Admin utama tidak bisa diubah NIK/username/level oleh admin lain
            if ($_SESSION['user_nik'] != 'ADMINNIK123') { // Jika yang login bukan admin utama itu sendiri
                $error_message = "Anda tidak diizinkan mengedit akun Administrator utama ini.";
                $user_nik_from_get = null;
            }
        }

    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan database saat mengambil data pengguna: " . $e->getMessage();
        error_log("Edit user fetch error in edit_user.php: " . $e->getMessage());
    }
} else {
    $error_message = "NIK Pengguna tidak diberikan.";
}

// --- FASE 2: INISIALISASI VARIABEL FORM (Untuk mengisi input HTML) ---
// Defaultkan dengan data dari database (saat GET) atau dari POST (saat ada error validasi)
if ($user_data_from_db) { // Jika $user_data_from_db berhasil diambil dari DB
    $nik_form = $user_data_from_db['NIK'];
    $username_form = $user_data_from_db['username'];
    $nm_lengkap_form = $user_data_from_db['nm_lengkap'];
    $email_form = $user_data_from_db['email'];
    $telp_form = $user_data_from_db['telp'];
    $alamat_form = $user_data_from_db['alamat'];
    $jk_form = $user_data_from_db['jk'];
    $rt_form = $user_data_from_db['RT'];
    $rw_form = $user_data_from_db['RW'];
    $selected_level_form = $user_data_from_db['level'];
} 
// Jika ada POST data (misal setelah submit dengan error), override dengan POST data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik_form = trim($_POST['NIK'] ?? '');
    $username_form = trim($_POST['username'] ?? '');
    $nm_lengkap_form = trim($_POST['nm_lengkap'] ?? '');
    $email_form = trim($_POST['email'] ?? '');
    $telp_form = trim($_POST['telp'] ?? '');
    $alamat_form = trim($_POST['alamat'] ?? '');
    $jk_form = $_POST['jk'] ?? '';
    $rt_form = trim($_POST['RT'] ?? '');
    $rw_form = trim($_POST['RW'] ?? '');
    $selected_level_form = trim($_POST['level'] ?? '');
} else if (!$user_data_from_db) { // Default jika tidak ada data user dari DB dan bukan POST
    $nik_form = ''; $username_form = ''; $nm_lengkap_form = ''; $email_form = '';
    $telp_form = ''; $alamat_form = ''; $jk_form = ''; $rt_form = ''; $rw_form = '';
    $selected_level_form = '';
}


// --- FASE 3: PROSES UPDATE FORM JIKA DISUBMIT (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_data_from_db) {
    $old_nik = $user_nik_from_get; // NIK lama yang ada di URL (identifikasi record)
    $new_nik = trim($_POST['NIK'] ?? ''); // NIK baru dari form
    $new_username = trim($_POST['username'] ?? '');
    $new_password_input = $_POST['password'] ?? ''; 
    $new_level = trim($_POST['level'] ?? ''); 
    $new_nm_lengkap = trim($_POST['nm_lengkap'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_telp = trim($_POST['telp'] ?? '');
    $new_alamat = trim($_POST['alamat'] ?? '');
    $new_jk = $_POST['jk'] ?? '';
    $new_rt = trim($_POST['RT'] ?? '');
    $new_rw = trim($_POST['RW'] ?? '');
    
    // Validasi semua field personal wajib diisi
    if (empty($new_nik) || empty($new_username) || empty($new_nm_lengkap) || empty($new_level) || 
        empty($new_telp) || empty($new_alamat) || empty($new_jk) || empty($new_rt) || empty($new_rw)) {
        $error_message = 'Semua field (termasuk data KTP) wajib diisi.';
    } elseif ($new_nik == 'ADMINNIK123' && $old_nik != 'ADMINNIK123') {
        $error_message = 'NIK ADMINNIK123 hanya untuk user administrator utama dan tidak bisa digunakan.';
    } elseif ($new_nik == 'ADMINNIK123' && $new_level != 'admin') {
        $error_message = 'Level "admin" harus dipilih jika NIK adalah ADMINNIK123.';
    } elseif (!empty($new_telp) && !is_numeric($new_telp)) {
        $error_message = 'Nomor telepon harus berupa angka.';
    } else {
        try {
            $conn->beginTransaction(); 

            // 1. Cek apakah NIK baru sudah ada di user lain (jika NIK diubah)
            if ($new_nik !== $old_nik) {
                $stmt_check_nik = $conn->prepare("SELECT COUNT(*) FROM users WHERE NIK = :nik");
                $stmt_check_nik->bindParam(':nik', $new_nik);
                $stmt_check_nik->execute();
                if ($stmt_check_nik->fetchColumn() > 0) {
                    $error_message = 'NIK baru sudah digunakan oleh user lain. Silakan gunakan NIK lain.';
                    $conn->rollBack();
                }
            }
            
            // 2. Cek apakah username baru sudah ada di user lain (jika username diubah)
            if ($new_username !== $user_data_from_db['username'] && empty($error_message)) {
                $stmt_check_username = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmt_check_username->bindParam(':username', $new_username);
                $stmt_check_username->execute();
                if ($stmt_check_username->fetchColumn() > 0) {
                    $error_message = 'Username baru sudah digunakan oleh user lain. Silakan pilih username lain.';
                    $conn->rollBack();
                }
            }

            // Validasi khusus untuk ADMINNIK123 (admin utama)
            if ($old_nik == 'ADMINNIK123' && empty($error_message)) {
                // Admin utama tidak boleh mengubah NIK-nya
                if ($new_nik !== 'ADMINNIK123') {
                    $error_message = 'NIK Administrator utama tidak dapat diubah.';
                    $conn->rollBack();
                }
                // Admin utama tidak boleh mengubah levelnya menjadi selain 'admin'
                if ($new_level != 'admin') {
                    $error_message = 'Peran "Administrator" tidak dapat diubah dari akun Administrator utama.';
                    $conn->rollBack();
                }
            }

            if (empty($error_message)) {
                // 3. Update data di tabel 'users'
                $sql_update_user = "UPDATE users SET NIK = :new_nik, username = :new_username, nm_lengkap = :nm_lengkap, email = :new_email, telp = :new_telp, alamat = :new_alamat, jk = :new_jk, RT = :new_rt, RW = :new_rw, level = :new_level";
                if (!empty($new_password_input)) {
                    $sql_update_user .= ", password = :hashed_password"; 
                }
                $sql_update_user .= " WHERE NIK = :old_nik";
                
                $stmt_update_user = $conn->prepare($sql_update_user);
                $stmt_update_user->bindParam(':new_nik', $new_nik);
                $stmt_update_user->bindParam(':new_username', $new_username);
                $stmt_update_user->bindParam(':nm_lengkap', $new_nm_lengkap);
                $stmt_update_user->bindParam(':new_email', $new_email); 
                $stmt_update_user->bindParam(':new_telp', $new_telp);   
                $stmt_update_user->bindParam(':new_alamat', $new_alamat); 
                $stmt_update_user->bindParam(':new_jk', $new_jk);       
                $stmt_update_user->bindParam(':new_rt', $new_rt);       
                $stmt_update_user->bindParam(':new_rw', $new_rw);       
                $stmt_update_user->bindParam(':new_level', $new_level); 
                if (!empty($new_password_input)) {
                    $hashed_password = password_hash($new_password_input, PASSWORD_DEFAULT);
                    $stmt_update_user->bindParam(':hashed_password', $hashed_password);
                }
                $stmt_update_user->bindParam(':old_nik', $old_nik); 
                
                if ($stmt_update_user->execute()) {
                    $conn->commit();
                    $success_message = 'Data pengguna berhasil diperbarui!';
                    
                    // Jika NIK atau username berubah, sesi user yang sedang login perlu diperbarui
                    if ($old_nik == $_SESSION['user_nik']) { // Jika yang diedit adalah user yang sedang login
                         $_SESSION['user_nik'] = $new_nik;
                         $_SESSION['username'] = $new_username;
                         $_SESSION['user_level'] = $new_level;
                         $_SESSION['user_nm_lengkap'] = $new_nm_lengkap;
                    }

                    header("Location: manage_users.php?status=edit_success");
                    exit();
                } else {
                    $conn->rollBack();
                    $error_message = 'Gagal memperbarui data pengguna.';
                }
            }

        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Update user error in edit_user.php: " . $e->getMessage());
        }
    }
}
// Jika ada status sukses dari redirect sebelumnya (dari halaman ini sendiri setelah POST)
if (isset($_GET['status']) && $_GET['status'] == 'edit_success') {
    $success_message = 'Data pengguna berhasil diperbarui!';
}

?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Edit Pengguna</title>
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
                                <h5>Edit Pengguna</h5>
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

                                <?php if ($user_data_from_db): ?>
                                    <form method="POST" action="edit_user.php?nik=<?php echo htmlspecialchars($user_nik_from_get); ?>" class="form-horizontal">
                                        <div class="form-group"><label class="col-sm-2 control-label">NIK</label>
                                            <div class="col-sm-10">
                                                <input type="text" name="NIK" class="form-control" required 
                                                       value="<?php echo htmlspecialchars($nik_form); ?>"
                                                       <?php echo ($user_data_from_db['NIK'] == 'ADMINNIK123') ? 'readonly' : ''; ?>
                                                >
                                            </div>
                                        </div>
                                        <div class="form-group"><label class="col-sm-2 control-label">Username</label>
                                            <div class="col-sm-10">
                                                <input type="text" name="username" class="form-control" required 
                                                       value="<?php echo htmlspecialchars($username_form); ?>"
                                                       <?php echo ($user_data_from_db['NIK'] == 'ADMINNIK123') ? 'readonly' : ''; ?>
                                                >
                                            </div>
                                        </div>
                                        <div class="form-group"><label class="col-sm-2 control-label">Ubah Password (kosongkan jika tidak diubah)</label>
                                            <div class="col-sm-10"><input type="password" name="password" class="form-control"></div>
                                        </div>
                                        <div class="form-group"><label class="col-sm-2 control-label">Nama Lengkap</label>
                                            <div class="col-sm-10"><input type="text" name="nm_lengkap" class="form-control" required value="<?php echo htmlspecialchars($nm_lengkap_form); ?>"></div>
                                        </div>
                                        <div class="form-group"><label class="col-sm-2 control-label">Email</label>
                                            <div class="col-sm-10"><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email_form); ?>"></div>
                                        </div>
                                        <div class="form-group"><label class="col-sm-2 control-label">No. Telepon</label>
                                            <div class="col-sm-10"><input type="text" name="telp" class="form-control" required value="<?php echo htmlspecialchars($telp_form); ?>"></div>
                                        </div>

                                        <div class="form-group"><label class="col-sm-2 control-label">Alamat</label>
                                            <div class="col-sm-10"><textarea name="alamat" class="form-control" required><?php echo htmlspecialchars($alamat_form); ?></textarea></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">Jenis Kelamin</label>
                                            <div class="col-sm-10">
                                                <select name="jk" class="form-control m-b" required>
                                                    <option value="">-- Pilih --</option>
                                                    <option value="L" <?php echo ($jk_form == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                                    <option value="P" <?php echo ($jk_form == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label">RT</label>
                                            <div class="col-sm-4"><input type="text" name="RT" class="form-control" required value="<?php echo htmlspecialchars($rt_form); ?>"></div>
                                            <label class="col-sm-2 control-label">RW</label>
                                            <div class="col-sm-4"><input type="text" name="RW" class="form-control" required value="<?php echo htmlspecialchars($rw_form); ?>"></div>
                                        </div>

                                        <div class="form-group"><label class="col-sm-2 control-label">Level User</label>
                                            <div class="col-sm-10">
                                                <select name="level" id="level_select" class="form-control m-b" required
                                                        <?php echo ($user_data_from_db['NIK'] == 'ADMINNIK123') ? 'disabled' : ''; // Level admin utama tidak bisa diubah ?>
                                                >
                                                    <option value="">-- Pilih Level --</option>
                                                    <?php foreach ($available_levels as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" 
                                                            <?php echo ($value == $selected_level_form) ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php if ($user_data_from_db['NIK'] == 'ADMINNIK123'): ?>
                                                    <input type="hidden" name="level" value="admin"> <small class="text-danger">Level Administrator utama tidak dapat diubah.</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        

                                        <div class="hr-line-dashed"></div>
                                        <div class="form-group">
                                            <div class="col-sm-4 col-sm-offset-2">
                                                <a href="manage_users.php" class="btn btn-white">Batal</a>
                                                <button class="btn btn-primary" type="submit">Update Data</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <p class="text-danger">Tidak ada data pengguna untuk diedit.</p>
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