<?php
// qurban_app/auth/login.php
// Halaman untuk proses login pengguna - Modifikasi dengan layout 2 kolom
// Disesuaikan untuk NIK sebagai primary key user dan kolom 'level' (string terpisah koma)

// Memulai session PHP
session_start();

// Sertakan file koneksi database
include '../config/db.php';
// Sertakan file untuk pengecekan otentikasi dan otorisasi
include '../config/auth_check.php'; // Menggunakan fungsi hasRole() dan checkUserAccess()

// Jika user sudah login, arahkan ke dashboard yang sesuai (berdasarkan peran tertinggi)
if (isLoggedIn()) {
    if (hasRole('admin')) {
        header('Location: ../admin/index.php');
    } elseif (hasRole('panitia')) {
        header('Location: ../panitia/index.php');
    } elseif (hasRole('pengqurban')) {
        header('Location: ../pengqurban/index.php');
    } elseif (hasRole('warga')) {
        header('Location: ../warga/index.php');
    } else {
        // Jika level tidak dikenal, arahkan ke halaman utama atau error
        header('Location: ../index.php');
    }
    exit();
}

$error_message = '';

// Proses login jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if (empty($username_input) || empty($password_input)) {
        $error_message = 'Username dan password tidak boleh kosong.';
    } else {
        try {
            // Ambil data user dari database berdasarkan username
            // Sekarang mengambil NIK, username, password, dan KOLOM LEVEL (string)
            $stmt = $conn->prepare("SELECT NIK, username, password, level, nm_lengkap FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username_input);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi user
            if ($user) {
                // VERIFIKASI PASSWORD DENGAN HASHING
               if ($password_input === $user['password']) {
                    // Login berhasil, simpan data user ke session
                    $_SESSION['user_nik'] = $user['NIK']; // NIK sebagai identifikasi utama
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_level'] = $user['level']; // Level user (string terpisah koma)
                    $_SESSION['user_nm_lengkap'] = $user['nm_lengkap']; // Nama lengkap untuk sapaan

                    // Redirect ke dashboard sesuai peran utama (prioritas: admin > panitia > pengqurban > warga)
                    if (hasRole('admin')) {
                        header('Location: ../admin/index.php');
                    } elseif (hasRole('panitia')) {
                        header('Location: ../panitia/index.php');
                    } elseif (hasRole('pengqurban')) {
                        header('Location: ../pengqurban/index.php');
                    } elseif (hasRole('warga')) {
                        header('Location: ../warga/index.php');
                    } else {
                        // Jika tidak ada peran yang valid, arahkan ke halaman login kembali
                        $error_message = 'Akun Anda tidak memiliki peran yang valid. Harap hubungi admin.';
                        session_destroy(); // Hancurkan sesi yang mungkin sudah dibuat
                    }
                    exit();
                } else {
                    $error_message = 'Password salah.';
                }
            } else {
                $error_message = 'Username tidak ditemukan.';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Qurban | Login</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../assets/css/animate.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="gray-bg">

    <div class="loginColumns animated fadeInDown">
        <div class="row">
            <div class="col-md-6">
                <h2 class="font-bold">Selamat Datang di Sistem Qurban</h2>
                <p>
                    Aplikasi manajemen qurban untuk RT 001 Desa AAAA. Memudahkan pengelolaan hewan qurban, data pengqurban, warga, panitia, serta distribusi daging.
                </p>
                <p>
                    Sistem ini dirancang untuk memastikan proses qurban berjalan transparan dan efisien.
                </p>
                <p>
                    Silakan login menggunakan akun Anda untuk mengakses fitur-fitur yang tersedia.
                </p>
                <p>
                    <small>Dikelola oleh Panitia Qurban RT 001 Desa AAAA.</small>
                </p>
            </div>
            
            <div class="col-md-6">
                <div class="ibox-content">
                    <img src="../assets/img/logo_qurban.png" alt="Logo Sistem Qurban" class="img-responsive" style="max-height: 80px; margin: 0 auto; display: block;">
                    
                    <h3 class="text-center">Login Sistem Qurban</h3>
                    <p class="text-center">Masukkan username dan password Anda.</p>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form class="m-t" role="form" method="POST" action="login.php">
                        <div class="form-group">
                            <input type="text" name="username" class="form-control" placeholder="Username" required="">
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" class="form-control" placeholder="Password" required="">
                        </div>
                        <button type="submit" class="btn btn-primary block full-width m-b">Login</button>
                        <p class="text-muted text-center"><small>Hanya administrator yang dapat membuat akun baru.</small></p>
                    </form>
                    <p class="m-t">
                        <small>Copyright RT 001 Desa AAAA &copy; <?php echo date('Y'); ?></small>
                    </p>
                </div>
            </div>
        </div>
        <hr/>
        <div class="row">
            <div class="col-md-6">
                Sistem Informasi Manajemen Qurban
            </div>
            <div class="col-md-6 text-right">
                <small>&copy; <?php echo date('Y'); ?></small>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-3.1.1.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>