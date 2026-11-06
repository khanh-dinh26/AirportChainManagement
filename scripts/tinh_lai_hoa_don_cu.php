<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/KeToanController.php';

$kt = new KeToanController($conn);
$dry = in_array('--dry-run', $argv ?? [], true);

$rs = mysqli_query($conn, "SELECT id, tong_tien FROM hoa_don
                           WHERE trang_thai_thanh_toan = 'Chua_Thanh_Toan' AND don_gia_do IS NULL");
$ok = $loi = 0;
while ($row = mysqli_fetch_assoc($rs)) {
    if ($dry) {
        echo "[dry-run] HĐ #{$row['id']} (cũ: {$row['tong_tien']})\n";
        continue;
    }
    if ($kt->TinhLaiHoaDon((int) $row['id'], 'Tính lại theo công thức chuẩn hóa 05/11/2026')) {
        $ok++;
    } else {
        $loi++;
        echo "Lỗi HĐ #{$row['id']}\n";
    }
}
echo "Hoàn tất: $ok thành công, $loi lỗi\n";
