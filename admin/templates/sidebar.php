<?php
// qurban_app/admin/templates/sidebar.php
// File ini akan di-include untuk sidebar navigasi di halaman admin

// Mendapatkan nama halaman saat ini untuk menandai menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar-default navbar-static-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav metismenu" id="side-menu">
            <li class="nav-header">
                <div class="dropdown profile-element">
                    <span>
                        <img src="../assets/img/logo_qurban.png" alt="Logo Sistem Qurban" class="img-responsive" style="max-height: 100px; margin: 0 auto; display: block;">
                    </span>
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <span class="clear">
                            <span class="block m-t-xs"> <strong class="font-bold"><?php echo $_SESSION['username']; ?></strong> </span>
                            <span class="text-muted text-xs block">Administrator <b class="caret"></b></span>
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
                <a href="index.php"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard</span></a>
            </li>
            <li class="<?php echo (in_array($current_page, ['register_user.php', 'manage_users.php', 'manage_warga.php', 'manage_panitia.php', 'manage_pengqurban.php'])) ? 'active' : ''; ?>">
                <a href="#"><i class="fa fa-users"></i> <span class="nav-label">Manajemen User</span> <span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li><a href="register_user.php">Registrasi User</a></li>
                    <li><a href="manage_users.php">Daftar Semua User</a></li>
                    <li><a href="manage_warga.php">Daftar Warga</a></li>
                    <li><a href="manage_panitia.php">Daftar Panitia</a></li>
                    <li><a href="manage_pengqurban.php">Daftar Pengqurban</a></li>
                </ul>
            </li>
            <li class="<?php echo ($current_page == 'hewan_qurban.php') ? 'active' : ''; ?>">
                <a href="hewan_qurban.php"><i class="fa fa-cut"></i> <span class="nav-label">Hewan Qurban</span></a>
            </li>
            <li class="<?php echo (in_array($current_page, ['keuangan.php', 'input_pemasukan.php', 'input_pengeluaran.php', 'iuran_pengqurban.php'])) ? 'active' : ''; ?>">
                <a href="#"><i class="fa fa-money"></i> <span class="nav-label">Keuangan</span> <span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li><a href="keuangan.php">Ringkasan Keuangan</a></li>
                    <li><a href="input_pemasukan.php">Input Pemasukan</a></li>
                    <li><a href="input_pengeluaran.php">Input Pengeluaran</a></li>
                    <li><a href="iuran_pengqurban.php">Iuran Pengqurban</a></li>
                </ul>
            </li>
            <li class="<?php echo (in_array($current_page, ['distribusi_daging.php', 'laporan_distribusi.php'])) ? 'active' : ''; ?>">
                <a href="#"><i class="fa fa-gift"></i> <span class="nav-label">Distribusi Daging</span> <span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li><a href="distribusi_daging.php">Input Distribusi</a></li>
                    <li><a href="laporan_distribusi.php">Laporan Distribusi</a></li>
                </ul>
            </li>
            </ul>
    </div>
</nav>