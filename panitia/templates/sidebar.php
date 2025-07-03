<?php
// qurban_app/panitia/templates/sidebar.php
// File ini akan di-include untuk sidebar navigasi di halaman panitia
// Disesuaikan untuk NIK dan level dari sesi baru.

$current_page = basename($_SERVER['PHP_SELF']);
$user_display_name = $_SESSION['user_nm_lengkap'] ?? $_SESSION['username'] ?? 'Panitia';
?>
<nav class="navbar-default navbar-static-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav metismenu" id="side-menu">
            <li class="nav-header">
                <div class="dropdown profile-element">
                    <span>
                        <img alt="image" class="img-circle" src="../assets/img/profile_small.jpg" />
                    </span>
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <span class="clear">
                            <span class="block m-t-xs"> <strong class="font-bold"><?php echo htmlspecialchars($user_display_name); ?></strong> </span>
                            <span class="text-muted text-xs block">Panitia <b class="caret"></b></span>
                        </span>
                    </a>
                    <ul class="dropdown-menu animated fadeInRight m-t-xs">
                        <li><a href="../auth/logout.php">Logout</a></li>
                    </ul>
                </div>
                <div class="logo-element">
                    Q+
                </div>
            </li>
            <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php"><i class="fa fa-list"></i> <span class="nav-label">Daftar Distribusi</span></a>
            </li>
            <li class="<?php echo ($current_page == 'verifikasi_qr.php') ? 'active' : ''; ?>">
                <a href="verifikasi_qr.php"><i class="fa fa-qrcode"></i> <span class="nav-label">Verifikasi QR Code</span></a>
            </li>
        </ul>
    </div>
</nav>