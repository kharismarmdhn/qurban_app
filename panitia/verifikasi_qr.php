<?php
// qurban_app/panitia/verifikasi_qr.php
// Halaman untuk Panitia memverifikasi QR Code dengan INPUT TEKS MANUAL.
// Final: Panitia menginput NIK|ID_DISTRIBUSI, sistem update database dan tampilkan detail.

session_start();

// Definisikan ROOT_PATH proyek Anda
define('ROOT_PATH', dirname(__DIR__)); 

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya panitia ('panitia') atau admin ('admin') yang bisa mengakses halaman ini
checkUserAccess(['panitia', 'admin']); 

$success_message = '';
$error_message = '';
$distribusi_data = null; // Data distribusi yang ditemukan dari input QR

// Inisialisasi variabel untuk input QR
$input_qr_code = '';

// Logika untuk proses verifikasi dan konfirmasi pengambilan daging setelah input manual
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_qr_code = trim($_POST['qr_code_input'] ?? ''); // Data dari input teks manual
    
    if (empty($input_qr_code)) {
        $error_message = 'Kode QR tidak boleh kosong.';
    } else {
        try {
            // Kode QR warga diharapkan formatnya: NIK_penerima|id_distribusi
            $qr_parts = explode('|', $input_qr_code);
            $nik_penerima_from_qr = $qr_parts[0] ?? '';
            $id_distribusi_from_qr = $qr_parts[1] ?? '';

            if (empty($nik_penerima_from_qr) || empty($id_distribusi_from_qr) || !is_numeric($id_distribusi_from_qr)) {
                $error_message = 'Format Kode QR tidak valid. Harap masukkan format NIK_WARGA|ID_DISTRIBUSI (contoh: 1234567890123456|1).';
            } else {
                // Cari data distribusi berdasarkan NIK_penerima dan ID distribusi
                $stmt_distribusi = $conn->prepare("
                    SELECT 
                        dd.id_distribusi,
                        dd.NIK_penerima,
                        dd.jumlah_daging_sapi,
                        dd.jumlah_daging_kambing,
                        dd.tgl_distribusi,
                        dd.status_pengambilan,
                        u_penerima.nm_lengkap AS nama_warga,
                        u_penerima.alamat AS alamat_warga,
                        u_penerima.telp AS telp_warga
                    FROM 
                        distribusi_daging dd
                    JOIN 
                        users u_penerima ON dd.NIK_penerima = u_penerima.NIK
                    WHERE 
                        dd.NIK_penerima = :nik_penerima AND dd.id_distribusi = :id_distribusi
                ");
                $stmt_distribusi->bindParam(':nik_penerima', $nik_penerima_from_qr);
                $stmt_distribusi->bindParam(':id_distribusi', $id_distribusi_from_qr, PDO::PARAM_INT);
                $stmt_distribusi->execute();
                $distribusi_data = $stmt_distribusi->fetch(PDO::FETCH_ASSOC);

                if ($distribusi_data) {
                    if ($distribusi_data['status_pengambilan'] == 'sudah_diambil') {
                        $error_message = 'Daging untuk warga ini sudah diambil sebelumnya!';
                        // Tampilkan data meskipun sudah diambil agar panitia tahu statusnya
                    } else {
                        // Data ditemukan dan belum diambil, LANGSUNG KONFIRMASI PENGAMBILAN
                        $nik_panitia_login = $_SESSION['user_nik'] ?? null; 

                        if (!$nik_panitia_login) {
                             $error_message = 'NIK Panitia Konfirmator tidak ditemukan di sesi. Harap login ulang.';
                        } else {
                            $conn->beginTransaction();
                            $stmt_update = $conn->prepare("UPDATE distribusi_daging SET status_pengambilan = 'sudah_diambil', NIK_panitia_konfirm = :nik_panitia_konfirm WHERE id_distribusi = :id_distribusi AND NIK_penerima = :nik_penerima");
                            $stmt_update->bindParam(':nik_panitia_konfirm', $nik_panitia_login); 
                            $stmt_update->bindParam(':id_distribusi', $distribusi_data['id_distribusi'], PDO::PARAM_INT);
                            $stmt_update->bindParam(':nik_penerima', $distribusi_data['NIK_penerima']);
                            
                            if ($stmt_update->execute()) {
                                $conn->commit();
                                $success_message = 'Pengambilan daging berhasil dikonfirmasi secara otomatis!';
                                $distribusi_data['status_pengambilan'] = 'sudah_diambil'; // Update data yang ditampilkan
                                $input_qr_code = ''; // Reset input setelah sukses, siap untuk input berikutnya
                            } else {
                                $conn->rollBack();
                                $error_message = 'Gagal mengkonfirmasi pengambilan daging secara otomatis.';
                            }
                        }
                    }
                } else {
                    $error_message = 'QR Code tidak ditemukan atau tidak sesuai dengan data distribusi.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan database saat mencari/mengupdate data: ' . $e->getMessage();
            error_log("Panitia Manual Confirm error: " . $e->getMessage());
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan umum: ' . $e->getMessage();
            error_log("Panitia Manual Confirm general error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'templates/head.php'; ?>
    <title>Qurban App | Verifikasi Pengambilan</title>
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
                                <h5>Verifikasi Pengambilan Daging (Input Manual)</h5>
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

                                <form method="POST" action="verifikasi_qr.php" class="form-horizontal">
                                    <div class="form-group"><label class="col-sm-2 control-label">Input Data QR Code</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="qr_code_input" class="form-control" placeholder="Masukkan NIK_WARGA|ID_DISTRIBUSI" required 
                                                   value="<?php echo htmlspecialchars($input_qr_code); ?>" autofocus>
                                            <span class="help-block m-b-none">Contoh: 1234567890123456|1 (Ambil dari hasil scan HP atau minta warga menyebutkannya).</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-4 col-sm-offset-2">
                                            <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i> Proses Konfirmasi</button>
                                            <a href="verifikasi_qr.php" class="btn btn-white">Reset Form</a>
                                        </div>
                                    </div>
                                </form>

                                <?php if ($distribusi_data): // Tampilkan detail jika data ditemukan (dari proses scan sebelumnya) ?>
                                    <hr>
                                    <h3 class="m-t-lg">Detail Pengambilan Daging</h3>
                                    <div class="form-horizontal">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Nama Warga:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($distribusi_data['nama_warga']); ?></p></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">NIK Warga:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($distribusi_data['NIK_penerima']); ?></p></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Alamat Warga:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo nl2br(htmlspecialchars($distribusi_data['alamat_warga'])); ?></p></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Telepon Warga:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($distribusi_data['telp_warga']); ?></p></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">ID Distribusi:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars($distribusi_data['id_distribusi']); ?></p></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Daging Sapi:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo number_format($distribusi_data['jumlah_daging_sapi'], 2, ',', '.') . ' Kg'; ?></p></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Daging Kambing:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo number_format($distribusi_data['jumlah_daging_kambing'], 2, ',', '.') . ' Kg'; ?></p></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Tanggal Distribusi:</label>
                                            <div class="col-sm-9"><p class="form-control-static"><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($distribusi_data['tgl_distribusi']))); ?></p></div>
                                            </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Status Pengambilan:</label>
                                            <div class="col-sm-9">
                                                <p class="form-control-static">
                                                    <?php 
                                                        $status_text = ucfirst(str_replace('_', ' ', $distribusi_data['status_pengambilan']));
                                                        $status_class = ($distribusi_data['status_pengambilan'] == 'sudah_diambil') ? 'text-primary' : 'text-warning';
                                                        echo '<span class="' . $status_class . ' font-bold">' . htmlspecialchars($status_text) . '</span>';
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
    ```