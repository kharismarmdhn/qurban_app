<?php
// qurban_app/pengqurban/templates/top_navbar.php
// File ini akan di-include untuk top navbar di halaman pengqurban
// Disesuaikan untuk NIK dan level dari sesi baru.


$user_display_name = $_SESSION['user_nm_lengkap'] ?? $_SESSION['username'] ?? 'Pengqurban';
?>
<div class="row border-bottom">
    <nav class="navbar navbar-static-top white-bg" role="navigation" style="margin-bottom: 0">
        <div class="navbar-header">
            <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
            <form role="search" class="navbar-form-custom" action="#">
                <div class="form-group">
                    <input type="text" placeholder="Cari..." class="form-control" name="top-search" id="top-search">
                </div>
            </form>
        </div>
        <ul class="nav navbar-top-links navbar-right">
            <li>
                <span class="m-r-sm text-muted welcome-message">Selamat datang, Pengqurban <?php echo htmlspecialchars($user_display_name); ?>!</span>
            </li>
            <li class="dropdown">
                <a class="dropdown-toggle count-info" data-toggle="dropdown" href="#">
                    <i class="fa fa-envelope"></i>  <span class="label label-warning">0</span>
                </a>
                <ul class="dropdown-menu dropdown-messages">
                    <li>
                        <div class="text-center link-block">
                            <a href="#">
                                <i class="fa fa-envelope"></i> <strong>Baca Semua Pesan</strong>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <li>
                <a href="../auth/logout.php">
                    <i class="fa fa-sign-out"></i> Log out
                </a>
            </li>
        </ul>
    </nav>
</div>