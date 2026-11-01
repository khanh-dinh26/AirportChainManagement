<?php
require_once __DIR__ . '/../../../config/database.php'; 
require_once __DIR__ . '/../../../controllers/KeToanController.php';
$keToan = new KeToanController($conn);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = $keToan->LayChiTietHoaDon($id);
if ($data) {
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
} else {
    echo json_encode(['status' => 'error']);
}
?>