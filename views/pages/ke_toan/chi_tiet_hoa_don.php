<?php
/**
 * ============================================================
 * TRANG: CHI TIẾT HÓA ĐƠN — views/pages/ke_toan/chi_tiet_hoa_don.php
 * Render hoàn toàn phía server (không dùng JavaScript, không fetch).
 * Mở từ nút "Xem" ở danh sách hóa đơn chờ thanh toán.
 * ============================================================
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/KeToanController.php';

$keToan = new KeToanController($conn);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$hoaDon = $id > 0 ? $keToan->LayChiTietHoaDon($id) : null;

$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$pageTitle = 'Chi tiết hóa đơn';
$pageHeader = 'Chi tiết hóa đơn';
$navActive = 'ke_toan';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Kế toán & doanh thu', 'url' => BASE_URL . '/index.php?page=ke_toan'],
    ['label' => $hoaDon ? 'Hóa đơn #' . (int) $hoaDon['id'] : 'Không tìm thấy', 'url' => null],
];

ob_start();
?>
<div class="card card-info card-outline">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="card-title mb-0">
            <i class="bi bi-receipt me-1" aria-hidden="true"></i>
            <?= $hoaDon ? 'Hóa đơn #' . (int) $hoaDon['id'] : 'Chi tiết hóa đơn' ?>
        </h3>
        <a href="<?= BASE_URL ?>/index.php?page=ke_toan" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại
        </a>
    </div>
    <div class="card-body">
        <?php if (!$hoaDon): ?>
            <div class="alert alert-warning mb-0" role="alert">
                Không tìm thấy hóa đơn cần xem hoặc mã hóa đơn không hợp lệ.
            </div>
        <?php else: ?>
            <?php
            $ngayXuat = !empty($hoaDon['ngay_xuat']) ? strtotime((string) $hoaDon['ngay_xuat']) : false;
            $hanThanhToan = (!empty($hoaDon['han_thanh_toan']) && $hoaDon['han_thanh_toan'] !== '0000-00-00') ? strtotime((string) $hoaDon['han_thanh_toan']) : false;

            // Lấy đơn giá từ DB để hiển thị công thức đối chiếu
            $danhSachGia = $keToan->DanhSachDonGia();
            $donGiaMap = [];
            foreach ($danhSachGia as $g) { 
                $donGiaMap[$g['loai_dich_vu']] = $g; 
            }
            $dgCatHa  = $donGiaMap['Phi_Cat_Ha_Canh'] ?? ['don_gia' => 1500000, 'don_vi_tinh' => 'Lượt'];
            $dgDo     = $donGiaMap['Phi_Do_Tau_Bay']   ?? ['don_gia' => 50000,   'don_vi_tinh' => 'Giờ/Tấn MTOW'];
            $dgPhucVu = $donGiaMap['Phi_Phuc_Vu_Hanh_Khach'] ?? ['don_gia' => 100000, 'don_vi_tinh' => 'Hành khách'];

            $mtow       = (float) ($hoaDon['tai_trong_cat_canh_mtow'] ?? 0);
            $soKhach    = (int) ($hoaDon['luong_hanh_khach'] ?? 0);
            // Kiểm tra cột thoi_gian_do_gio nếu CSDL đã mở rộng, ngược lại lấy mặc định 1.0 giờ
            $coCotDo    = isset($hoaDon['thoi_gian_do_gio']) && $hoaDon['thoi_gian_do_gio'] !== null && $hoaDon['thoi_gian_do_gio'] !== '';
            $thoiGianDo = $coCotDo ? (float) $hoaDon['thoi_gian_do_gio'] : 1.5;
            ?>
            <table class="table table-sm table-borderless mb-4">
                <tr><th style="width: 25%;">Mã hóa đơn:</th><td class="fw-bold text-danger">#<?= (int) $hoaDon['id'] ?></td></tr>
                <tr><th>Hãng hàng không:</th><td><?= $e($hoaDon['ten_hang']) ?></td></tr>
                <tr><th>Chuyến bay:</th><td class="fw-bold"><?= $e($hoaDon['so_hieu']) ?></td></tr>
                <tr><th>Lộ trình:</th><td class="fw-bold"><?= $e($hoaDon['ten_san_bay_di']) ?> ➔ <?= $e($hoaDon['ten_san_bay_den']) ?></td></tr>
                <tr><th>Trọng tải tàu bay (MTOW):</th><td><?= $e($mtow) ?> tấn</td></tr>
                <tr><th>Thời gian đỗ:</th><td><?= $e($thoiGianDo) ?> giờ <?= !$coCotDo ? '<span class="badge bg-secondary">Mặc định</span>' : '' ?></td></tr>
                <tr><th>Ngày lập:</th><td><?= $ngayXuat ? $e(date('d/m/Y H:i', $ngayXuat)) : '—' ?></td></tr>
                <tr><th>Hạn thanh toán:</th><td><?= $hanThanhToan ? $e(date('d/m/Y', $hanThanhToan)) : '—' ?></td></tr>
            </table>

            <h6 class="fw-bold border-bottom pb-2">Chi tiết các khoản phí</h6>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Khoản phí</th>
                            <th class="text-center" style="width: 45%;">Công thức tính</th>
                            <th class="text-end" style="width: 25%;">Thành tiền (VNĐ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Phí cất/hạ cánh</strong>
                                <br><small class="text-muted">Chuyến: <?= $e($hoaDon['ten_san_bay_di']) ?> → <?= $e($hoaDon['ten_san_bay_den']) ?></small>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold"><?= number_format((float) $dgCatHa['don_gia'], 0, ',', '.') ?> VNĐ / <?= $e($dgCatHa['don_vi_tinh']) ?></div>
                                <div class="small text-secondary">Tính theo lượt khai thác chuyến bay</div>
                            </td>
                            <td class="text-end fw-bold"><?= number_format((float) $hoaDon['phi_cat_ha_canh'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Phí đỗ tàu bay</strong>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold"><?= number_format((float) $dgDo['don_gia'], 0, ',', '.') ?> VNĐ / <?= $e($dgDo['don_vi_tinh']) ?></div>
                                <div class="small text-secondary">
                                    <?= number_format((float) $dgDo['don_gia'], 0, ',', '.') ?> × <?= $e($mtow) ?> tấn × <?= $e($thoiGianDo) ?> giờ
                                </div>
                                <div class="small text-success">
                                    = <?= number_format((float) $dgDo['don_gia'] * $mtow * $thoiGianDo, 0, ',', '.') ?> VNĐ
                                </div>
                            </td>
                            <td class="text-end fw-bold"><?= number_format((float) $hoaDon['phi_do_tau_bay'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Phí phục vụ hành khách</strong>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold"><?= number_format((float) $dgPhucVu['don_gia'], 0, ',', '.') ?> VNĐ / <?= $e($dgPhucVu['don_vi_tinh']) ?></div>
                                <div class="small text-secondary"><?= number_format((float) $dgPhucVu['don_gia'], 0, ',', '.') ?> × <?= $e($soKhach) ?> khách</div>
                                <div class="small text-success">
                                    = <?= number_format((float) $dgPhucVu['don_gia'] * $soKhach, 0, ',', '.') ?> VNĐ
                                </div>
                            </td>
                            <td class="text-end fw-bold"><?= number_format((float) $hoaDon['phi_phuc_vu_hk'], 0, ',', '.') ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-warning">
                            <th colspan="2" class="text-end fs-5">TỔNG CỘNG:</th>
                            <th class="text-end fs-5 text-danger"><?= number_format((float) $hoaDon['tong_tien'], 0, ',', '.') ?> VNĐ</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="alert alert-light border mt-3 small">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Giải thích quy tắc tính cước:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Phí cất/hạ cánh:</strong> Cước cố định tính theo lượt khai thác chuyến bay tại cảng.</li>
                    <li><strong>Phí đỗ tàu bay:</strong> Tính theo công thức: <code>Đơn giá × MTOW (tấn) × Thời gian đỗ (giờ)</code>. <?= !$coCotDo ? '(Hiện đang áp dụng quy ước mặc định 1.5 giờ tự động tính từ lịch bay)' : '' ?></li>
                    <li><strong>Phí phục vụ hành khách:</strong> Tính theo số lượng hành khách thực tế lên/xuống tàu bay.</li>
                    <li>Đơn giá được áp dụng trực tiếp từ bảng định mức biểu phí dịch vụ của Tổng công ty.</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';