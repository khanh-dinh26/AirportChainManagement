<?php
/**
 * ============================================================
 * TRANG: CHI TIẾT HÓA ĐƠN — views/pages/ke_toan/chi_tiet_hoa_don.php
 * Render hoàn toàn phía server (không dùng JavaScript, không fetch).
 * Mở từ nút "Xem" ở danh sách hóa đơn chờ thanh toán hoặc lịch sử.
 * ============================================================
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/KeToanController.php';

$keToan = new KeToanController($conn);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Xử lý xác nhận thanh toán trực tiếp từ view chi tiết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_xac_nhan_thanh_toan'])) {
    $hdId = (int) $_POST['action_xac_nhan_thanh_toan'];
    if ($hdId > 0 && $keToan->XacNhanThanhToan($hdId)) {
        $_SESSION['flash_success'] = 'Đã xác nhận thanh toán hóa đơn #' . $hdId . ' thành công!';
        header('Location: ' . BASE_URL . '/views/pages/ke_toan/chi_tiet_hoa_don.php?id=' . $hdId);
        exit();
    }
}

$hoaDon = $id > 0 ? $keToan->LayChiTietHoaDon($id) : null;
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

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
<?php if ($flashSuccess !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= $e($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card card-info card-outline">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h3 class="card-title mb-0">
            <i class="bi bi-receipt me-1" aria-hidden="true"></i>
            <?= $hoaDon ? 'Hóa đơn #' . (int) $hoaDon['id'] : 'Chi tiết hóa đơn' ?>
        </h3>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php if ($hoaDon && ($hoaDon['trang_thai_thanh_toan'] ?? '') === 'Chua_Thanh_Toan'): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận hóa đơn #<?= (int) $hoaDon['id'] ?> đã hoàn tất thanh toán?');">
                    <input type="hidden" name="action_xac_nhan_thanh_toan" value="<?= (int) $hoaDon['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-check-circle me-1"></i>Xác nhận thanh toán
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Lịch sử hóa đơn
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại Kế toán
            </a>
        </div>
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

            // Ưu tiên đơn giá đã lưu trong hóa đơn; hóa đơn cũ chưa có thì dùng giá hiện hành và ghi chú
            $coSnapshot = isset($hoaDon['don_gia_do']) && $hoaDon['don_gia_do'] !== null && $hoaDon['don_gia_do'] !== '';

            $dgCatHa = [
                'don_gia'     => $coSnapshot ? $hoaDon['don_gia_cat_ha_canh'] : ($donGiaMap['Phi_Cat_Ha_Canh']['don_gia'] ?? 0),
                'don_vi_tinh' => $donGiaMap['Phi_Cat_Ha_Canh']['don_vi_tinh'] ?? 'Lượt',
            ];
            $dgDo = [
                'don_gia'     => $coSnapshot ? $hoaDon['don_gia_do'] : ($donGiaMap['Phi_Do_Tau_Bay']['don_gia'] ?? 0),
                'don_vi_tinh' => $donGiaMap['Phi_Do_Tau_Bay']['don_vi_tinh'] ?? 'Giờ/Tấn MTOW',
            ];
            $dgPhucVu = [
                'don_gia'     => $coSnapshot ? $hoaDon['don_gia_phuc_vu'] : ($donGiaMap['Phi_Phuc_Vu_Hanh_Khach']['don_gia'] ?? 0),
                'don_vi_tinh' => $donGiaMap['Phi_Phuc_Vu_Hanh_Khach']['don_vi_tinh'] ?? 'Hành khách',
            ];

            $mtow    = (float) ($coSnapshot && isset($hoaDon['mtow_ap_dung']) && $hoaDon['mtow_ap_dung'] !== null ? $hoaDon['mtow_ap_dung'] : ($hoaDon['tai_trong_cat_canh_mtow'] ?? 0));
            $soKhach = (int)   ($coSnapshot && isset($hoaDon['so_khach_ap_dung']) && $hoaDon['so_khach_ap_dung'] !== null ? $hoaDon['so_khach_ap_dung'] : ($hoaDon['luong_hanh_khach'] ?? 0));

            $coCotDo    = isset($hoaDon['thoi_gian_do_gio']) && $hoaDon['thoi_gian_do_gio'] !== null && $hoaDon['thoi_gian_do_gio'] !== '';
            $thoiGianDo = $coCotDo ? (float) $hoaDon['thoi_gian_do_gio'] : null;
            $tamTinh    = !empty($hoaDon['tam_tinh']);
            $trangThaiTT = $hoaDon['trang_thai_thanh_toan'] ?? 'Chua_Thanh_Toan';
            ?>
            <table class="table table-sm table-borderless mb-4">
                <tr><th style="width: 25%;">Mã hóa đơn:</th><td class="fw-bold text-danger">#<?= (int) $hoaDon['id'] ?></td></tr>
                <tr>
                    <th>Trạng thái thanh toán:</th>
                    <td>
                        <?php if ($trangThaiTT === 'Da_Thanh_Toan'): ?>
                            <span class="badge bg-success fs-6"><i class="bi bi-check-circle-fill me-1"></i>Đã thanh toán</span>
                        <?php else: ?>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-warning text-dark fs-6"><i class="bi bi-hourglass-split me-1"></i>Hóa đơn chờ thanh toán</span>
                                <form method="post" class="d-inline" onsubmit="return confirm('Xác nhận hóa đơn #<?= (int) $hoaDon['id'] ?> đã hoàn tất thanh toán?');">
                                    <input type="hidden" name="action_xac_nhan_thanh_toan" value="<?= (int) $hoaDon['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-circle me-1"></i>Xác nhận thanh toán
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr><th>Hãng hàng không:</th><td><?= $e($hoaDon['ten_hang']) ?></td></tr>
                <tr><th>Chuyến bay:</th><td class="fw-bold"><?= $e($hoaDon['so_hieu']) ?></td></tr>
                <tr><th>Lộ trình:</th><td class="fw-bold"><?= $e($hoaDon['ten_san_bay_di']) ?> ➔ <?= $e($hoaDon['ten_san_bay_den']) ?></td></tr>
                <tr><th>Trọng tải tàu bay (MTOW):</th><td><?= $e($mtow) ?> tấn</td></tr>
                <tr><th>Thời gian đỗ:</th><td>
                    <?= $thoiGianDo !== null ? $e($thoiGianDo) . ' giờ' : 'Chưa xác định (hóa đơn lập theo công thức cũ)' ?>
                    <?php if ($tamTinh): ?><span class="badge bg-warning text-dark ms-1">Tạm tính</span><?php endif; ?>
                </td></tr>
                <tr><th>Ngày lập:</th><td><?= $ngayXuat ? $e(date('d/m/Y H:i', $ngayXuat)) : '—' ?></td></tr>
                <tr><th>Hạn thanh toán:</th><td><?= $hanThanhToan ? $e(date('d/m/Y', $hanThanhToan)) : '—' ?></td></tr>
                <?php if (!empty($hoaDon['ghi_chu'])): ?>
                    <tr><th>Ghi chú:</th><td><span class="text-danger"><?= $e($hoaDon['ghi_chu']) ?></span></td></tr>
                <?php endif; ?>
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
                                <?php if ($thoiGianDo !== null): ?>
                                    <div class="small text-secondary">
                                        <?= number_format((float) $dgDo['don_gia'], 0, ',', '.') ?> × <?= $e($mtow) ?> tấn × <?= $e($thoiGianDo) ?> giờ
                                    </div>
                                    <div class="small text-success">
                                        = <?= number_format((float) $dgDo['don_gia'] * $mtow * $thoiGianDo, 0, ',', '.') ?> VNĐ
                                    </div>
                                <?php else: ?>
                                    <div class="small text-muted font-italic">
                                        Không đủ dữ liệu để đối chiếu
                                    </div>
                                <?php endif; ?>
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

            <?php if ($trangThaiTT === 'Chua_Thanh_Toan'): ?>
                <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
                    <form method="post" onsubmit="return confirm('Xác nhận hóa đơn #<?= (int) $hoaDon['id'] ?> đã hoàn tất thanh toán?');">
                        <input type="hidden" name="action_xac_nhan_thanh_toan" value="<?= (int) $hoaDon['id'] ?>">
                        <button type="submit" class="btn btn-success btn-lg shadow-sm">
                            <i class="bi bi-check-circle me-1"></i>Xác nhận thanh toán hóa đơn
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="alert alert-light border mt-3 small">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Giải thích quy tắc tính cước:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Phí cất/hạ cánh:</strong> Cước cố định tính theo lượt khai thác chuyến bay tại cảng.</li>
                    <li><strong>Phí đỗ tàu bay:</strong> Tính theo công thức: <code>Đơn giá × MTOW (tấn) × Thời gian đỗ (giờ)</code>.
                    <?php if ($tamTinh): ?>
                        <span class="text-warning fw-bold">(Hóa đơn tạm tính: <?= !empty($hoaDon['ghi_chu']) ? $e($hoaDon['ghi_chu']) : 'Áp dụng quy ước mặc định 1.5 giờ' ?>)</span>
                    <?php elseif ($thoiGianDo !== null): ?>
                        (Thời gian đỗ thực tế: <?= $e($thoiGianDo) ?> giờ)
                    <?php endif; ?>
                    </li>
                    <li><strong>Phí phục vụ hành khách:</strong> Tính theo số lượng hành khách thực tế lên/xuống tàu bay.</li>
                    <li>Đơn giá được áp dụng trực tiếp từ bảng định mức biểu phí dịch vụ của Tổng công ty.</li>
                    <?php if (!$coSnapshot): ?>
                        <li class="text-secondary fst-italic">Đơn giá hiển thị là đơn giá hiện hành, hóa đơn này lập trước khi lưu đơn giá snapshot.</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
