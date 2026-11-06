<?php
/**
 * ============================================================
 * TRANG: CHI TIẾT HÓA ĐƠN DOANH THU THEO THÁNG (VIEW RIÊNG BIỆT)
 * views/pages/ke_toan/chi_tiet_thang.php
 * Phân hệ: Kế toán & Doanh thu
 * 100% PHP thuần & CSS (Không dùng JSON / AJAX)
 * ============================================================
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/KeToanController.php';

yeu_cau_dang_nhap();
if (!co_quyen_ke_toan()) {
    $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Kế toán & Doanh thu.';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$keToan = new KeToanController($conn);

// 1. NHẬN BỘ LỌC TỪ URL
$namHienTai    = (int) date('Y');
$namChon       = isset($_GET['nam']) ? (int) $_GET['nam'] : $namHienTai;
$thangChon     = isset($_GET['thang']) ? (int) $_GET['thang'] : (int) date('n');
if ($thangChon < 1 || $thangChon > 12) {
    $thangChon = (int) date('n');
}
$hangChon      = isset($_GET['hang']) ? (int) $_GET['hang'] : 0;
$sanBayChon    = isset($_GET['san_bay']) ? (int) $_GET['san_bay'] : 0;
$trangThaiChon = isset($_GET['trang_thai']) && in_array($_GET['trang_thai'], ['Da_Thanh_Toan', 'Chua_Thanh_Toan', 'Tat_Ca'], true)
                 ? $_GET['trang_thai']
                 : 'Da_Thanh_Toan';

// Dữ liệu cho 12 tháng của năm (để vẽ thanh chuyển nhanh tháng T1..T12 kèm số lượng HĐ)
$data12Thang = $keToan->ThongKeDoanhThu12Thang($namChon, $hangChon, $sanBayChon, $trangThaiChon);

// Danh sách hóa đơn chi tiết của tháng này
$hoaDonThangChon = $keToan->LayHoaDonChiTietTheoThang($namChon, $thangChon, $hangChon, $sanBayChon, $trangThaiChon);

// Tính tổng các loại phí của tháng đang chọn
$tongDoanhThuThang = 0.0;
$tongPhiCatHaCanh  = 0.0;
$tongPhiDo         = 0.0;
$tongPhiKhach      = 0.0;
foreach ($hoaDonThangChon as $hd) {
    $tongDoanhThuThang += (float) $hd['tong_tien'];
    $tongPhiCatHaCanh  += (float) $hd['phi_cat_ha_canh'];
    $tongPhiDo         += (float) $hd['phi_do_tau_bay'];
    $tongPhiKhach      += (float) $hd['phi_phuc_vu_hk'];
}

// URL quay lại trang Thống kê doanh thu (giữ nguyên các bộ lọc)
$urlQuayLaiParams = [
    'page'       => 'ke_toan',
    'view'       => 'thong_ke',
    'nam'        => $namChon,
];
if ($hangChon > 0) {
    $urlQuayLaiParams['hang'] = $hangChon;
}
if ($sanBayChon > 0) {
    $urlQuayLaiParams['san_bay'] = $sanBayChon;
}
if ($trangThaiChon !== 'Da_Thanh_Toan') {
    $urlQuayLaiParams['trang_thai'] = $trangThaiChon;
}
$urlQuayLai = BASE_URL . '/index.php?' . http_build_query($urlQuayLaiParams);

// Helper tạo URL chuyển tháng trong view này
$taoUrlChuyenThang = static function (int $thang) use ($namChon, $hangChon, $sanBayChon, $trangThaiChon): string {
    $p = [
        'page'       => 'ke_toan',
        'view'       => 'chi_tiet_thang',
        'nam'        => $namChon,
        'thang'      => $thang,
    ];
    if ($hangChon > 0) {
        $p['hang'] = $hangChon;
    }
    if ($sanBayChon > 0) {
        $p['san_bay'] = $sanBayChon;
    }
    if ($trangThaiChon !== 'Da_Thanh_Toan') {
        $p['trang_thai'] = $trangThaiChon;
    }
    return BASE_URL . '/index.php?' . http_build_query($p);
};

$pageTitle  = "Chi tiết hóa đơn Tháng {$thangChon}/{$namChon} - Kế toán";
$pageHeader = "Chi tiết doanh thu Tháng {$thangChon}/{$namChon}";
$navActive  = 'ke_toan';
$navSubActive = 'thong_ke';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Kế toán & doanh thu', 'url' => BASE_URL . '/index.php?page=ke_toan'],
    ['label' => 'Thống kê doanh thu', 'url' => $urlQuayLai],
    ['label' => "Chi tiết Tháng {$thangChon}/{$namChon}", 'url' => null],
];

ob_start();
?>

<!-- ==================== HEADER ACTIONS & QUAY LẠI ==================== -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $urlQuayLai ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-arrow-left fs-6"></i>
            <span>Quay lại Thống kê</span>
        </a>
        <div class="vr mx-1 d-none d-sm-block"></div>
        <div>
            <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-calendar-event text-primary"></i>
                Chi tiết Tháng <?= $thangChon ?> / Năm <?= $namChon ?>
            </h4>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark border px-3 py-2">
            <i class="bi bi-funnel me-1 text-primary"></i>
            Trạng thái: <strong><?= $trangThaiChon === 'Da_Thanh_Toan' ? 'Đã thanh toán (Thực thu)' : ($trangThaiChon === 'Chua_Thanh_Toan' ? 'Chờ thanh toán (Công nợ)' : 'Tất cả trạng thái') ?></strong>
        </span>
        <?php
            $urlXuatExcelThang = BASE_URL . '/index.php?' . http_build_query([
                'page'       => 'ke_toan',
                'view'       => 'xuat_excel',
                'kieu'       => 'thang',
                'nam'        => $namChon,
                'thang'      => $thangChon,
                'hang'       => $hangChon,
                'san_bay'    => $sanBayChon,
                'trang_thai' => $trangThaiChon,
            ]);
        ?>
        <a href="<?= $urlXuatExcelThang ?>" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Xuất file Excel</span>
        </a>
    </div>
</div>

<!-- ==================== BỘ CHỌN NHANH THÁNG (PILLS T1 -> T12) ==================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-1">
                <span class="small text-secondary fw-semibold me-2"><i class="bi bi-calendar3 me-1"></i>Chọn nhanh tháng khác:</span>
                <?php foreach ($data12Thang as $t): ?>
                    <?php $isAct = ($t['thang'] === $thangChon); ?>
                    <a href="<?= $taoUrlChuyenThang($t['thang']) ?>" 
                       class="btn btn-sm <?= $isAct ? 'btn-primary shadow-sm' : 'btn-light border' ?> py-1 px-2"
                       style="font-size: 0.8rem; border-radius: 6px;">
                        T.<?= $t['thang'] ?>
                        <?php if ($t['so_hoa_don'] > 0): ?>
                            <span class="badge <?= $isAct ? 'bg-light text-primary' : 'bg-secondary-subtle text-dark' ?> ms-1" style="font-size: 0.65rem;"><?= $t['so_hoa_don'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<!-- ==================== CÁC CHỈ SỐ DOANH THU THÁNG ĐANG CHỌN ==================== -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-primary-subtle border-start border-4 border-primary">
            <div class="small text-primary fw-bold text-uppercase mb-1">Tổng doanh thu Tháng <?= $thangChon ?></div>
            <h4 class="fw-bold text-primary mb-0"><?= number_format($tongDoanhThuThang) ?> <small class="fs-6">VNĐ</small></h4>
            <div class="small text-muted mt-1"><?= count($hoaDonThangChon) ?> hóa đơn phát sinh</div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="small text-secondary fw-semibold mb-1">Phí cất / hạ cánh</div>
            <h5 class="fw-bold text-dark mb-0"><?= number_format($tongPhiCatHaCanh) ?> <small class="fs-6 text-muted">VNĐ</small></h5>
            <div class="small text-muted mt-1">
                <?= $tongDoanhThuThang > 0 ? round(($tongPhiCatHaCanh / $tongDoanhThuThang) * 100, 1) : 0 ?>% tỷ trọng tháng
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="small text-secondary fw-semibold mb-1">Phí đỗ tàu bay</div>
            <h5 class="fw-bold text-dark mb-0"><?= number_format($tongPhiDo) ?> <small class="fs-6 text-muted">VNĐ</small></h5>
            <div class="small text-muted mt-1">
                <?= $tongDoanhThuThang > 0 ? round(($tongPhiDo / $tongDoanhThuThang) * 100, 1) : 0 ?>% tỷ trọng tháng
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="small text-secondary fw-semibold mb-1">Phí phục vụ khách (PSC)</div>
            <h5 class="fw-bold text-dark mb-0"><?= number_format($tongPhiKhach) ?> <small class="fs-6 text-muted">VNĐ</small></h5>
            <div class="small text-muted mt-1">
                <?= $tongDoanhThuThang > 0 ? round(($tongPhiKhach / $tongDoanhThuThang) * 100, 1) : 0 ?>% tỷ trọng tháng
            </div>
        </div>
    </div>
</div>

<!-- ==================== BẢNG DANH SÁCH HÓA ĐƠN CHI TIẾT ==================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="card-title fw-bold text-dark mb-0">
                <i class="bi bi-receipt text-primary me-2"></i>Danh sách Hóa đơn chi tiết (Tháng <?= $thangChon ?>/<?= $namChon ?>)
            </h5>
            <div class="small text-muted">Dữ liệu lấy từ hệ thống kế toán doanh thu sân bay chuỗi</div>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
            Tổng cộng: <strong><?= count($hoaDonThangChon) ?> hóa đơn</strong>
        </span>
    </div>

    <div class="card-body table-responsive p-0">
        <?php if (empty($hoaDonThangChon)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                <h5 class="fw-bold text-secondary">Không có hóa đơn nào</h5>
                <p class="text-muted small mb-0">Không tìm thấy hóa đơn nào phát sinh trong Tháng <?= $thangChon ?>/<?= $namChon ?> với bộ lọc hiện tại.</p>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary text-uppercase small">
                    <tr>
                        <th style="min-width: 85px;" class="text-center">#Mã HĐ</th>
                        <th style="min-width: 180px;">Chuyến bay &amp; Hành trình</th>
                        <th style="min-width: 190px;">Hãng hàng không</th>
                        <th style="min-width: 145px;">Ngày xuất HĐ</th>
                        <th class="text-end" style="min-width: 165px;">Phí cất/hạ cánh</th>
                        <th class="text-end" style="min-width: 155px;">Phí đỗ tàu</th>
                        <th class="text-end" style="min-width: 165px;">Phí khách (PSC)</th>
                        <th class="text-end text-primary" style="min-width: 185px;">Tổng tiền</th>
                        <th class="text-center" style="min-width: 140px;">Trạng thái</th>
                        <th class="text-center" style="min-width: 105px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hoaDonThangChon as $hd): ?>
                        <?php
                            $trangThaiHd = $hd['trang_thai_thanh_toan'] ?? 'Chua_Thanh_Toan';
                            $laDaThanhToan = ($trangThaiHd === 'Da_Thanh_Toan');
                        ?>
                        <tr>
                            <td class="text-center fw-bold text-dark">
                                #<?= $hd['id'] ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($hd['so_hieu'] ?? 'N/A') ?></div>
                                <div class="small text-muted">
                                    <span class="badge text-bg-light border"><?= htmlspecialchars($hd['ma_san_bay_di'] ?? '') ?></span>
                                    <i class="bi bi-arrow-right mx-1 text-secondary"></i>
                                    <span class="badge text-bg-light border"><?= htmlspecialchars($hd['ma_san_bay_den'] ?? '') ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($hd['ten_hang'] ?? 'N/A') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($hd['ma_hang'] ?? '') ?></div>
                            </td>
                            <td>
                                <div><?= !empty($hd['ngay_xuat']) ? date('d/m/Y', strtotime($hd['ngay_xuat'])) : '–' ?></div>
                                <small class="text-muted"><?= !empty($hd['ngay_xuat']) ? date('H:i', strtotime($hd['ngay_xuat'])) : '' ?></small>
                            </td>
                            <td class="text-end text-secondary"><?= number_format((float)$hd['phi_cat_ha_canh']) ?> đ</td>
                            <td class="text-end text-secondary"><?= number_format((float)$hd['phi_do_tau_bay']) ?> đ</td>
                            <td class="text-end text-secondary"><?= number_format((float)$hd['phi_phuc_vu_hk']) ?> đ</td>
                            <td class="text-end fw-bold text-primary fs-6">
                                <?= number_format((float)$hd['tong_tien']) ?> đ
                            </td>
                            <td class="text-center">
                                <?php if ($laDaThanhToan): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i>Đã thu
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-hourglass-split me-1"></i>Chờ thu
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/views/pages/ke_toan/chi_tiet_hoa_don.php?id=<?= $hd['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Xem chi tiết toàn bộ hóa đơn">
                                    <i class="bi bi-file-earmark-text"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="7" class="text-end">TỔNG CỘNG THÁNG <?= $thangChon ?>:</td>
                        <td class="text-end text-primary fs-5"><?= number_format($tongDoanhThuThang) ?> đ</td>
                        <td colspan="2" class="text-center text-muted"><?= count($hoaDonThangChon) ?> hóa đơn</td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>



<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
