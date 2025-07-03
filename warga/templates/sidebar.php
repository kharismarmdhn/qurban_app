<?php
// qurban_app/warga/templates/sidebar.php
// File ini akan di-include untuk sidebar navigasi di halaman warga
// Mengembalikan sidebar dengan menu minimal agar bisa logout.

$current_page = basename($_SERVER['PHP_SELF']);
$user_display_name = $_SESSION['user_nm_lengkap'] ?? $_SESSION['username'] ?? 'Warga';
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
                            <span class="text-muted text-xs block">Warga RT 001 <b class="caret"></b></span>
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
                <a href="index.php"><i class="fa fa-qrcode"></i> <span class="nav-label">QR Code Pengambilan</span></a>
            </li>
            <li class="<?php echo ($current_page == 'pengambilan_daging.php') ? 'active' : ''; ?>">
                <a href="pengambilan_daging.php"><i class="fa fa-list"></i> <span class="nav-label">Riwayat Pengambilan</span></a>
            </li>
        </ul>
    </div>
</nav>