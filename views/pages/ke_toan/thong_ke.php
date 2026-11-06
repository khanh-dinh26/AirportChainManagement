<?php


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

// 1. BỘ LỌC
$namHienTai    = (int) date('Y');
$namChon       = isset($_GET['nam']) ? (int) $_GET['nam'] : $namHienTai;
$hangChon      = isset($_GET['hang']) ? (int) $_GET['hang'] : 0;
$sanBayChon    = isset($_GET['san_bay']) ? (int) $_GET['san_bay'] : 0;
$trangThaiChon = isset($_GET['trang_thai']) && in_array($_GET['trang_thai'], ['Da_Thanh_Toan', 'Chua_Thanh_Toan', 'Tat_Ca'], true)
                 ? $_GET['trang_thai']
                 : 'Da_Thanh_Toan';

// Danh sách dữ liệu bộ lọc
$danhSachNam    = $keToan->LayDanhSachNamHoaDon();
if (!in_array($namChon, $danhSachNam, true)) {
    $namChon = $danhSachNam[0] ?? $namHienTai;
}
$danhSachHang   = $keToan->LayDanhSachHangHangKhong();
$danhSachSanBay = $keToan->DanhSachSanBay();

// 2. LẤY DỮ LIỆU THỐNG KÊ
$kpi          = $keToan->ThongKeTongQuanDoanhThu($namChon, $hangChon, $sanBayChon);
$data12Thang  = $keToan->ThongKeDoanhThu12Thang($namChon, $hangChon, $sanBayChon, $trangThaiChon);
$dataTheoHang = $keToan->ThongKeDoanhThuTheoHangChiTiet($namChon, $sanBayChon, $trangThaiChon);

// Helper tạo URL mở trang xem chi tiết theo tháng
$taoUrlChiTietThang = static function (int $thang) use ($namChon, $hangChon, $sanBayChon, $trangThaiChon): string {
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

// Tính giá trị lớn nhất trong 12 tháng để vẽ cột CSS
$maxDoanhThuThang = 0.0;
$tongDoanhThuNam  = 0.0;
foreach ($data12Thang as $t) {
    if ($t['tong_doanh_thu'] > $maxDoanhThuThang) {
        $maxDoanhThuThang = $t['tong_doanh_thu'];
    }
    $tongDoanhThuNam += $t['tong_doanh_thu'];
}
if ($maxDoanhThuThang <= 0) {
    $maxDoanhThuThang = 1.0;
}

// Tính giá trị lớn nhất của các hãng để vẽ thanh ngang
$maxDoanhThuHang = 0.0;
foreach ($dataTheoHang as $h) {
    if ($h['doanh_thu'] > $maxDoanhThuHang) {
        $maxDoanhThuHang = $h['doanh_thu'];
    }
}
if ($maxDoanhThuHang <= 0) {
    $maxDoanhThuHang = 1.0;
}

$pageTitle  = 'Thống kê doanh thu - Kế toán';
$pageHeader = 'Thống kê doanh thu & Báo cáo tài chính';
$navActive  = 'ke_toan';
$navSubActive = 'thong_ke';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Kế toán & doanh thu', 'url' => BASE_URL . '/index.php?page=ke_toan'],
    ['label' => 'Thống kê doanh thu', 'url' => null],
];

ob_start();
?>
<style>
  /* Styling thẻ KPI */
  .stat-card-modern {
    border-radius: 0.85rem;
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background: #ffffff;
  }
  .stat-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.06);
  }
  .stat-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
  }

  /* Khung vẽ biểu đồ cột thuần CSS */
  .css-chart-wrapper {
    position: relative;
    height: 320px;
    margin-top: 1.5rem;
    margin-bottom: 2.2rem;
    padding-left: 65px;
    padding-bottom: 30px;
  }
  .css-chart-grid {
    position: absolute;
    top: 0;
    left: 65px;
    right: 0;
    bottom: 30px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    pointer-events: none;
  }
  .css-grid-line {
    position: relative;
    border-bottom: 1px dashed #e2e8f0;
    width: 100%;
    height: 0;
  }
  .css-grid-val {
    position: absolute;
    left: -65px;
    top: -9px;
    font-size: 0.72rem;
    color: #64748b;
    width: 58px;
    text-align: right;
    font-weight: 600;
  }
  .css-bar-chart-container {
    height: 100%;
    display: flex;
    align-items: flex-end;
    gap: 12px;
    position: relative;
    z-index: 2;
    border-bottom: 2px solid #94a3b8;
  }
  .css-bar-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    justify-content: flex-end;
    position: relative;
    text-decoration: none !important;
    cursor: pointer;
    transition: transform 0.18s ease;
  }
  .css-bar-col:hover {
    transform: translateY(-4px);
  }
  .css-bar-track {
    width: 100%;
    max-width: 48px;
    height: 100%;
    display: flex;
    align-items: flex-end;
    background: #f8fafc;
    border-radius: 8px 8px 0 0;
    position: relative;
    border: 1px solid #f1f5f9;
    border-bottom: none;
    transition: all 0.2s ease;
  }
  .css-bar-fill {
    width: 100%;
    border-radius: 6px 6px 0 0;
    position: relative;
    transition: all 0.25s ease;
  }
  .css-bar-fill.has-value {
    background: linear-gradient(180deg, #38bdf8 0%, #0284c7 100%);
    box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
  }
  .css-bar-col:hover .css-bar-fill.has-value {
    background: linear-gradient(180deg, #60a5fa 0%, #1d4ed8 100%);
    box-shadow: 0 6px 16px rgba(29, 78, 216, 0.4);
    transform: scaleX(1.05);
  }
  .css-bar-fill.is-zero {
    height: 0% !important;
    min-height: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
  }

  /* Nhãn hiển thị số tiền trên đỉnh cột */
  .css-bar-tooltip-val {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    z-index: 10;
  }
  .css-val-badge {
    background: #0f172a;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
    display: inline-block;
    transition: background 0.15s ease;
  }
  .css-bar-col:hover .css-val-badge {
    background: #0284c7;
    box-shadow: 0 3px 10px rgba(2, 132, 199, 0.45);
  }
  .css-val-badge::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    border-width: 4px 4px 0 4px;
    border-style: solid;
    border-color: #0f172a transparent transparent transparent;
  }
  .css-bar-col:hover .css-val-badge::after {
    border-color: #0284c7 transparent transparent transparent;
  }
  .css-bar-label {
    position: absolute;
    bottom: -28px;
    font-size: 0.8rem;
    color: #334155;
    font-weight: 700;
  }
  .css-bar-col:hover .css-bar-label {
    color: #0284c7;
  }

  /* Thanh tiến trình hãng hàng không */
  .airline-bar-item {
    padding: 0.75rem;
    border-radius: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    margin-bottom: 0.75rem;
    transition: background 0.15s ease;
  }
  .airline-bar-item:hover {
    background: #f1f5f9;
  }
</style>

<!-- ==================== HEADER TABS ==================== -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan" class="nav-link">
                <i class="bi bi-wallet2 me-1"></i>Thu phí &amp; Hóa đơn chờ
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&tab=hoa_don" class="nav-link">
                <i class="bi bi-clock-history me-1"></i>Lịch sử hóa đơn
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&tab=khach" class="nav-link">
                <i class="bi bi-people me-1"></i>Sản lượng khách
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=thong_ke" class="nav-link active fw-bold">
                <i class="bi bi-bar-chart-line me-1"></i>Thống kê doanh thu
            </a>
        </li>
    </ul>
</div>

<!-- ==================== THANH BỘ LỌC ==================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form action="<?= BASE_URL ?>/index.php" method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="ke_toan">
            <input type="hidden" name="view" value="thong_ke">

            <!-- Lọc theo Năm -->
            <div class="col-md-2 col-sm-6">
                <label for="filter_nam" class="form-label small fw-semibold text-secondary mb-1">
                    <i class="bi bi-calendar3 me-1"></i>Năm tài chính
                </label>
                <select name="nam" id="filter_nam" class="form-select form-select-sm">
                    <?php foreach ($danhSachNam as $n): ?>
                        <option value="<?= $n ?>" <?= $namChon === $n ? 'selected' : '' ?>>Năm <?= $n ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Lọc theo Hãng hàng không -->
            <div class="col-md-3 col-sm-6">
                <label for="filter_hang" class="form-label small fw-semibold text-secondary mb-1">
                    <i class="bi bi-building me-1"></i>Hãng hàng không
                </label>
                <select name="hang" id="filter_hang" class="form-select form-select-sm">
                    <option value="0">-- Tất cả hãng hàng không --</option>
                    <?php foreach ($danhSachHang as $h): ?>
                        <option value="<?= $h['id'] ?>" <?= $hangChon === (int)$h['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($h['ten_hang']) ?> (<?= htmlspecialchars($h['ma_hang'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Lọc theo Sân bay -->
            <div class="col-md-3 col-sm-6">
                <label for="filter_san_bay" class="form-label small fw-semibold text-secondary mb-1">
                    <i class="bi bi-geo-alt me-1"></i>Sân bay phát sinh
                </label>
                <select name="san_bay" id="filter_san_bay" class="form-select form-select-sm">
                    <option value="0">-- Toàn mạng lưới sân bay --</option>
                    <?php foreach ($danhSachSanBay as $sb): ?>
                        <option value="<?= $sb['id'] ?>" <?= $sanBayChon === (int)$sb['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sb['ten_san_bay']) ?> (<?= htmlspecialchars($sb['ma_iata']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Lọc theo Trạng thái -->
            <div class="col-md-2 col-sm-6">
                <label for="filter_trang_thai" class="form-label small fw-semibold text-secondary mb-1">
                    <i class="bi bi-funnel me-1"></i>Trạng thái
                </label>
                <select name="trang_thai" id="filter_trang_thai" class="form-select form-select-sm">
                    <option value="Da_Thanh_Toan" <?= $trangThaiChon === 'Da_Thanh_Toan' ? 'selected' : '' ?>>Đã thanh toán (Thực thu)</option>
                    <option value="Chua_Thanh_Toan" <?= $trangThaiChon === 'Chua_Thanh_Toan' ? 'selected' : '' ?>>Chờ thanh toán (Công nợ)</option>
                    <option value="Tat_Ca" <?= $trangThaiChon === 'Tat_Ca' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                </select>
            </div>

            <!-- Nút bấm -->
            <div class="col-md-2 col-sm-12 d-flex align-items-end gap-2 pt-md-4">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-filter me-1"></i>Lọc
                </button>
                <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=thong_ke" class="btn btn-sm btn-light border" title="Đặt lại bộ lọc">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ==================== CÁC CHỈ SỐ KPI TỔNG QUAN ==================== -->
<div class="row g-3 mb-4">
    <!-- Doanh thu thực thu -->
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card-modern p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Doanh thu thực thu</span>
                <div class="stat-icon-wrapper bg-success-subtle text-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <h3 class="fs-4 fw-bold text-success mb-1"><?= number_format($kpi['thuc_thu']) ?> <small class="fs-6 text-muted">VNĐ</small></h3>
            <div class="small text-muted">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                <?= $kpi['so_hd_da_thu'] ?> hóa đơn đã thanh toán
            </div>
        </div>
    </div>

    <!-- Doanh thu chờ thanh toán -->
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card-modern p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Công nợ chờ thu</span>
                <div class="stat-icon-wrapper bg-warning-subtle text-warning-emphasis">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <h3 class="fs-4 fw-bold text-warning-emphasis mb-1"><?= number_format($kpi['cho_thu']) ?> <small class="fs-6 text-muted">VNĐ</small></h3>
            <div class="small text-muted">
                <i class="bi bi-exclamation-circle text-warning me-1"></i>
                <?= $kpi['so_hd_cho_thu'] ?> hóa đơn đang chờ thu
            </div>
        </div>
    </div>

    <!-- Tổng giá trị phát sinh -->
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card-modern p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Tổng giá trị phát sinh</span>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
            <h3 class="fs-4 fw-bold text-primary mb-1"><?= number_format($kpi['tong_gia_tri']) ?> <small class="fs-6 text-muted">VNĐ</small></h3>
            <div class="small text-muted">
                <i class="bi bi-layers-fill text-primary me-1"></i>
                Tổng <?= $kpi['tong_so_hd'] ?> hóa đơn trong năm <?= $namChon ?>
            </div>
        </div>
    </div>

    <!-- Doanh thu TB / Chuyến bay -->
    <div class="col-xl-3 col-sm-6">
        <div class="card stat-card-modern p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Doanh thu TB / Chuyến</span>
                <div class="stat-icon-wrapper bg-info-subtle text-info-emphasis">
                    <i class="bi bi-airplane-engines"></i>
                </div>
            </div>
            <h3 class="fs-4 fw-bold text-info-emphasis mb-1"><?= number_format($kpi['tb_chuyen_bay']) ?> <small class="fs-6 text-muted">VNĐ</small></h3>
            <div class="small text-muted">
                <i class="bi bi-calculator me-1"></i>
                Tính trên các chuyến đã thu tiền
            </div>
        </div>
    </div>
</div>

<!-- ==================== BIỂU ĐỒ CỘT 1: DOANH THU 12 THÁNG (CSS NATIVE) ==================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>Biểu đồ cột: Doanh thu 12 tháng năm <?= $namChon ?>
            </h5>
            <div class="small text-muted">
                Biểu đồ hiển thị theo tỷ lệ doanh thu &bull; Trạng thái: <strong><?= $trangThaiChon === 'Da_Thanh_Toan' ? 'Đã thanh toán (Thực thu)' : ($trangThaiChon === 'Chua_Thanh_Toan' ? 'Chờ thanh toán' : 'Tất cả') ?></strong>
                &bull; <span class="text-primary fw-semibold"><i class="bi bi-box-arrow-up-right me-1"></i>Bấm vào cột tháng để mở trang xem chi tiết</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                Tổng doanh thu: <strong><?= number_format($tongDoanhThuNam) ?> VNĐ</strong>
            </span>
            <?php
                $urlXuatExcelNam = BASE_URL . '/index.php?' . http_build_query([
                    'page'       => 'ke_toan',
                    'view'       => 'xuat_excel',
                    'kieu'       => 'nam',
                    'nam'        => $namChon,
                    'hang'       => $hangChon,
                    'san_bay'    => $sanBayChon,
                    'trang_thai' => $trangThaiChon,
                ]);
            ?>
            <a href="<?= $urlXuatExcelNam ?>" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-file-earmark-excel"></i>
                <span>Xuất file Excel</span>
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="css-chart-wrapper">
            <!-- VẠCH LƯỚI TỌA ĐỘ TRỤC Y -->
            <div class="css-chart-grid">
                <?php
                    $maxTrieu = round($maxDoanhThuThang / 1000000, 1);
                ?>
                <div class="css-grid-line"><span class="css-grid-val"><?= $maxTrieu ?> Tr</span></div>
                <div class="css-grid-line"><span class="css-grid-val"><?= round($maxTrieu * 0.75, 1) ?> Tr</span></div>
                <div class="css-grid-line"><span class="css-grid-val"><?= round($maxTrieu * 0.50, 1) ?> Tr</span></div>
                <div class="css-grid-line"><span class="css-grid-val"><?= round($maxTrieu * 0.25, 1) ?> Tr</span></div>
                <div class="css-grid-line"><span class="css-grid-val">0 Tr</span></div>
            </div>

            <!-- CÁC CỘT DOANH THU 12 THÁNG (BẤM VÀO ĐỂ NHẢY SANG TRANG CHI TIẾT) -->
            <div class="css-bar-chart-container">
                <?php foreach ($data12Thang as $t): ?>
                    <?php
                        $hasVal = $t['tong_doanh_thu'] > 0;
                        $heightPercent = ($maxDoanhThuThang > 0 && $hasVal) ? max(5, round(($t['tong_doanh_thu'] / $maxDoanhThuThang) * 100)) : 0;
                        $trieu = round($t['tong_doanh_thu'] / 1000000, 2);
                        $formattedBadge = $trieu >= 1000 ? round($trieu / 1000, 2) . ' Tỷ' : number_format($trieu, 1, ',', '.') . ' Tr';
                    ?>
                    <a href="<?= $taoUrlChiTietThang($t['thang']) ?>" 
                       class="css-bar-col" 
                       title="Bấm để mở trang chi tiết hóa đơn Tháng <?= $t['thang'] ?> (<?= number_format($t['tong_doanh_thu']) ?> VNĐ - <?= $t['so_hoa_don'] ?> hóa đơn)">
                        <div class="css-bar-track">
                            <div class="css-bar-fill <?= $hasVal ? 'has-value' : 'is-zero' ?>" style="height: <?= $heightPercent ?>%;">
                                <?php if ($hasVal): ?>
                                    <div class="css-bar-tooltip-val">
                                        <span class="css-val-badge"><?= $formattedBadge ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="css-bar-label">Tháng <?= $t['thang'] ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ==================== HÀNG 2: 2 BIỂU ĐỒ PHỤ (THEO HÃNG & CƠ CẤU PHÍ) ==================== -->
<div class="row g-4 mb-4">
    <!-- BIỂU ĐỒ 2: DOANH THU THEO HÃNG HÀNG KHÔNG (CSS BARS) -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="card-title fw-bold text-dark mb-0">
                    <i class="bi bi-building text-info me-2"></i>Doanh thu theo Hãng hàng không (Năm <?= $namChon ?>)
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($dataTheoHang)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Không có dữ liệu hóa đơn nào của các hãng trong năm <?= $namChon ?>.
                    </div>
                <?php else: ?>
                    <?php foreach ($dataTheoHang as $h): ?>
                        <?php
                            $barPercent = $maxDoanhThuHang > 0 ? round(($h['doanh_thu'] / $maxDoanhThuHang) * 100, 1) : 0;
                            $pctTotal = $tongDoanhThuNam > 0 ? round(($h['doanh_thu'] / $tongDoanhThuNam) * 100, 1) : 0;
                        ?>
                        <div class="airline-bar-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <strong class="text-dark"><?= htmlspecialchars($h['ten_hang']) ?></strong>
                                    <span class="badge text-bg-light border ms-1"><?= htmlspecialchars($h['ma_iata'] ?? $h['ma_hang'] ?? '') ?></span>
                                    <small class="text-muted ms-2">(<?= $h['so_hoa_don'] ?> chuyến)</small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-primary"><?= number_format($h['doanh_thu']) ?> VNĐ</strong>
                                    <small class="text-muted ms-1">(<?= $pctTotal ?>%)</small>
                                </div>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 6px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?= max(4, $barPercent) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ 3: CƠ CẤU 3 NGUỒN PHÍ DỊCH VỤ -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="card-title fw-bold text-dark mb-0">
                    <i class="bi bi-pie-chart text-warning me-2"></i>Cơ cấu theo Loại phí dịch vụ
                </h6>
            </div>
            <div class="card-body">
                <?php
                    $tongPhi = $kpi['phi_cat_ha_canh'] + $kpi['phi_do_tau_bay'] + $kpi['phi_phuc_vu_hk'];
                    $pctLanding = $tongPhi > 0 ? round(($kpi['phi_cat_ha_canh'] / $tongPhi) * 100, 1) : 0;
                    $pctParking = $tongPhi > 0 ? round(($kpi['phi_do_tau_bay'] / $tongPhi) * 100, 1) : 0;
                    $pctPsc     = $tongPhi > 0 ? round(($kpi['phi_phuc_vu_hk'] / $tongPhi) * 100, 1) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span><i class="bi bi-airplane-engines text-primary me-1"></i>Phí cất / hạ cánh</span>
                        <span class="text-primary"><?= number_format($kpi['phi_cat_ha_canh']) ?> VNĐ (<?= $pctLanding ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pctLanding ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span><i class="bi bi-p-square text-warning me-1"></i>Phí đỗ tàu bay</span>
                        <span class="text-warning-emphasis"><?= number_format($kpi['phi_do_tau_bay']) ?> VNĐ (<?= $pctParking ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $pctParking ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span><i class="bi bi-people text-success me-1"></i>Phí phục vụ khách (PSC)</span>
                        <span class="text-success"><?= number_format($kpi['phi_phuc_vu_hk']) ?> VNĐ (<?= $pctPsc ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pctPsc ?>%"></div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Tổng cơ cấu phí:</span>
                        <span class="fw-bold fs-5 text-dark"><?= number_format($tongPhi) ?> VNĐ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== BẢNG SỐ LIỆU CHI TIẾT 12 THÁNG ==================== -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div>
            <h6 class="card-title fw-bold text-dark mb-0">
                <i class="bi bi-table text-primary me-2"></i>Bảng tổng hợp doanh thu theo từng tháng (Năm <?= $namChon ?>)
            </h6>
            <div class="small text-muted mt-1">Bấm nút "Xem chi tiết" để chuyển sang trang danh sách hóa đơn của tháng tương ứng</div>
        </div>
        <span class="badge text-bg-light border text-secondary">
            <i class="bi bi-calendar-check me-1"></i>12 Tháng
        </span>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle mb-0 text-center">
            <thead class="table-light text-secondary text-uppercase small">
                <tr>
                    <th style="min-width: 125px;">Tháng</th>
                    <th style="min-width: 120px;">Số hóa đơn</th>
                    <th class="text-end" style="min-width: 175px;">Phí cất / hạ cánh</th>
                    <th class="text-end" style="min-width: 175px;">Phí đỗ tàu bay</th>
                    <th class="text-end" style="min-width: 185px;">Phí phục vụ khách</th>
                    <th class="text-end text-primary" style="min-width: 200px;">Tổng doanh thu</th>
                    <th style="min-width: 100px;">Tỷ trọng</th>
                    <th style="min-width: 130px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data12Thang as $t): ?>
                    <?php
                        $tyTrong = $tongDoanhThuNam > 0 ? round(($t['tong_doanh_thu'] / $tongDoanhThuNam) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td class="fw-bold">
                            <a href="<?= $taoUrlChiTietThang($t['thang']) ?>" class="text-decoration-none text-dark">
                                Tháng <?= $t['thang'] ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($t['so_hoa_don'] > 0): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= $t['so_hoa_don'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-secondary"><?= number_format($t['phi_cat_ha_canh']) ?> VNĐ</td>
                        <td class="text-end text-secondary"><?= number_format($t['phi_do_tau_bay']) ?> VNĐ</td>
                        <td class="text-end text-secondary"><?= number_format($t['phi_phuc_vu_hk']) ?> VNĐ</td>
                        <td class="text-end fw-bold text-primary fs-6">
                            <?= number_format($t['tong_doanh_thu']) ?> VNĐ
                        </td>
                        <td>
                            <?php if ($tyTrong > 0): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $tyTrong ?>%</span>
                            <?php else: ?>
                                <span class="text-muted">0%</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $taoUrlChiTietThang($t['thang']) ?>" 
                               class="btn btn-sm btn-outline-primary py-0 px-2" 
                               style="font-size: 0.8rem;">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Chi tiết
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td>TỔNG CỘNG</td>
                    <td><?= $kpi['tong_so_hd'] ?> HĐ</td>
                    <td class="text-end"><?= number_format($kpi['phi_cat_ha_canh']) ?> VNĐ</td>
                    <td class="text-end"><?= number_format($kpi['phi_do_tau_bay']) ?> VNĐ</td>
                    <td class="text-end"><?= number_format($kpi['phi_phuc_vu_hk']) ?> VNĐ</td>
                    <td class="text-end text-primary fs-5"><?= number_format($tongDoanhThuNam) ?> VNĐ</td>
                    <td>100%</td>
                    <td>–</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';