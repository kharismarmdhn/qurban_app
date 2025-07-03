<?php
// qurban_app/includes/sidebar.php
// Hanya berisi navigasi sidebar kiri

// Variabel $user_level, $user_username, $user_level_name diambil dari includes/header.php
// Variabel $active_menu diambil dari halaman yang meng-include sidebar ini.
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
                            <span class="block m-t-xs">
                                <strong class="font-bold"><?php echo htmlspecialchars($user_username); ?></strong>
                            </span>
                            <span class="text-muted text-xs block">
                                <?php echo htmlspecialchars($user_level_name); ?> <b class="caret"></b>
                            </span>
                        </span>
                    </a>
                    <ul class="dropdown-menu animated fadeInRight m-t-xs">
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </div>
                <div class="logo-element">
                    Q+
                </div>
            </li>

            <?php if ($user_level == 1): // Menu untuk Administrator ?>
            <li class="<?php echo ($active_menu == 'dashboard_admin') ? 'active' : ''; ?>">
                <a href="../admin/dashboard.php"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard Admin</span></a>
            </li>
            <li class="<?php echo ($active_menu == 'pengguna') ? 'active' : ''; ?>">
                <a href="#"><i class="fa fa-users"></i> <span class="nav-label">Manajemen Pengguna</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level collapse <?php echo ($active_menu == 'pengguna') ? 'in' : ''; ?>">
                    <li><a href="../admin/pengguna/registrasi.php">Registrasi User Baru</a></li>
                    <li><a href="../admin/pengguna/daftar.php">Daftar User</a></li>
                </ul>
            </li>
            <li class="<?php echo ($active_menu == 'data_master') ? 'active' : ''; ?>">
                 <a href="#"><i class="fa fa-database"></i> <span class="nav-label">Data Master</span><span class="fa arrow"></span></a>
                 <ul class="nav nav-second-level collapse <?php echo ($active_menu == 'data_master') ? 'in' : ''; ?>">
                    <li><a href="../admin/data/warga.php">Data Warga</a></li>
                    <li><a href="../admin/data/panitia.php">Data Panitia</a></li>
                    <li><a href="../admin/data/pengqurban.php">Data Pengqurban</a></li>
                 </ul>
            </li>
            <li class="<?php echo ($active_menu == 'keuangan') ? 'active' : ''; ?>">
                <a href="#"><i class="fa fa-money"></i> <span class="nav-label">Keuangan</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level collapse <?php echo ($active_menu == 'keuangan') ? 'in' : ''; ?>">
                    <li><a href="../admin/keuangan/ringkasan.php">Ringkasan Keuangan</a></li>
                    <li><a href="../admin/keuangan/hewan_qurban.php">Data Hewan Qurban</a></li>
                    <li><a href="../admin/keuangan/iuran_pengqurban.php">Iuran Pengqurban</a></li>
                    <li><a href="../admin/keuangan/transaksi_lain.php">Transaksi Lain</a></li>
                </ul>
            </li>
            <li class="<?php echo ($active_menu == 'distribusi') ? 'active' : ''; ?>">
                <a href="#"><i class="fa fa-cube"></i> <span class="nav-label">Distribusi Daging</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level collapse <?php echo ($active_menu == 'distribusi') ? 'in' : ''; ?>">
                    <li><a href="../admin/distribusi/pengaturan.php">Pengaturan Distribusi</a></li>
                    <li><a href="../admin/distribusi/daftar.php">Daftar Distribusi</a></li>
                    <li><a href="../admin/distribusi/cetak_qr.php">Cetak QR Code</a></li>
                </ul>
            </li>
            <?php elseif ($user_level == 2): // Menu untuk Panitia ?>
            <li class="<?php echo ($active_menu == 'dashboard_panitia') ? 'active' : ''; ?>">
                <a href="../panitia/dashboard.php"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard Panitia</span></a>
            </li>
            <li class="<?php echo ($active_menu == 'cek_distribusi') ? 'active' : ''; ?>">
                <a href="../panitia/cek_distribusi.php"><i class="fa fa-qrcode"></i> <span class="nav-label">Cek Distribusi QR</span></a>
            </li>
            <?php elseif ($user_level == 3): // Menu untuk Pengqurban ?>
            <li class="<?php echo ($active_menu == 'dashboard_pengqurban') ? 'active' : ''; ?>">
                <a href="../pengqurban/dashboard.php"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard Pengqurban</span></a>
            </li>
            <?php elseif ($user_level == 4): // Menu untuk Warga ?>
            <li class="<?php echo ($active_menu == 'dashboard_warga') ? 'active' : ''; ?>">
                <a href="../warga/dashboard.php"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard Warga</span></a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>