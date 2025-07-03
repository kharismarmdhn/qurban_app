<?php
// qurban_app/panitia/templates/top_navbar.php
// File ini akan di-include untuk top navbar di halaman panitia
// Disesuaikan untuk NIK dan level dari sesi baru.

$user_display_name = $_SESSION['user_nm_lengkap'] ?? $_SESSION['username'] ?? 'Panitia';
?>
<div class="row border-bottom">
    <nav class="navbar navbar-static-top white-bg" role="navigation" style="margin-bottom: 0">
        <div class="navbar-header">
            <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
        </div>
        <ul class="nav navbar-top-links navbar-right">
            <li>
                <span class="m-r-sm text-muted welcome-message">Selamat datang, Panitia <?php echo htmlspecialchars($user_display_name); ?>!</span>
            </li>
            <li>
                <a href="../auth/logout.php">
                    <i class="fa fa-sign-out"></i> Log out
                </a>
            </li>
        </ul>
    </nav>
</div>