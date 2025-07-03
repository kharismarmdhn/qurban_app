<?php
// qurban_app/api/check_distribusi_status.php
// API endpoint untuk mengecek status pengambilan daging
// Digunakan oleh halaman warga untuk polling status QR Code

session_start();

// Definisikan ROOT_PATH
define('ROOT_PATH', dirname(__DIR__)); // Ini akan menghasilkan path ke qurban_app/

include ROOT_PATH . '/config/db.php';
include ROOT_PATH . '/config/auth_check.php';

// Pastikan hanya warga yang sudah login yang bisa mengakses API ini
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$id_distribusi = $_GET['id_distribusi'] ?? null;
$nik_warga = $_GET['nik'] ?? null;

// Pastikan ID distribusi dan NIK warga sesuai dengan yang login
if ($nik_warga !== $_SESSION['user_nik']) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'NIK mismatch.']);
    exit();
}

$response_data = ['status' => 'error', 'message' => 'Invalid request.'];

if ($id_distribusi && $nik_warga) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                status_pengambilan
            FROM 
                distribusi_daging
            WHERE 
                id_distribusi = :id_distribusi AND NIK_penerima = :nik_warga
        ");
        $stmt->bindParam(':id_distribusi', $id_distribusi, PDO::PARAM_INT);
        $stmt->bindParam(':nik_warga', $nik_warga);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $response_data = [
                'status' => 'success',
                'data' => [
                    'status_pengambilan' => $result['status_pengambilan']
                ]
            ];
        } else {
            $response_data = ['status' => 'error', 'message' => 'Distribusi not found.'];
        }

    } catch (PDOException $e) {
        error_log("API check_distribusi_status error: " . $e->getMessage());
        $response_data = ['status' => 'error', 'message' => 'Database error.'];
    }
}

header('Content-Type: application/json');
echo json_encode($response_data);
exit();
?>