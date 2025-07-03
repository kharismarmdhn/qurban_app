<?php
// qurban_app/pengqurban/templates/sidebar.php
// File ini akan di-include untuk sidebar navigasi di halaman pengqurban
// Disesuaikan untuk NIK dan level dari sesi baru.


// include '../config/auth_check.php'; // Tidak perlu include di sini, karena sudah di halaman utama.

$current_page = basename($_SERVER['PHP_SELF']);
$user_display_name = $_SESSION['user_nm_lengkap'] ?? $_SESSION['username'] ?? 'Pengqurban';
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
                            <span class="text-muted text-xs block">Pengqurban <b class="caret"></b></span>
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
                <a href="index.php"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard Pengqurban</span></a>
            </li>
            <li class="<?php echo ($current_page == 'status_iuran.php') ? 'active' : ''; ?>">
                <a href="status_iuran.php"><i class="fa fa-money"></i> <span class="nav-label">Status Iuran Saya</span></a>
            </li>
        </ul>
    </div>
</nav>