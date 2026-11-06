<?php
/**
 * ============================================================
 * ĐIỂM VÀO (ENTRY POINT) CỦA HỆ THỐNG
 * File: index.php (nằm ở gốc dự án)
 * ------------------------------------------------------------
 * CÁCH TRUY CẬP:
 *   http://localhost/AirportChainManagement/            -> Trang tổng quan
 *   http://localhost/AirportChainManagement/index.php?page=ha_tang
 *   http://localhost/AirportChainManagement/index.php?page=chuyen_bay
 *   http://localhost/AirportChainManagement/index.php?page=bao_tri
 *   http://localhost/AirportChainManagement/index.php?page=ke_toan
 *   http://localhost/AirportChainManagement/index.php?page=login
 *   http://localhost/AirportChainManagement/index.php?page=register
 *   http://localhost/AirportChainManagement/index.php?page=forgot
 *
 * LƯU Ý QUAN TRỌNG: KHÔNG mở file .php bằng cách nháy đúp chuột
 * (địa chỉ file:///... sẽ chỉ hiện mã nguồn). Luôn mở qua
 * http://localhost/... để Apache chạy PHP.
 * ============================================================
 */

/* ---------- Nạp cấu hình dùng chung ---------- */
require_once __DIR__ . '/config/config.php';

/* ---------- Bảng điều hướng: ?page=key  ->  file view ---------- */
$routes = [
    // Thành viên 1 - Quản lý nhân sự & Xác thực
    'nhan_su'  => 'views/pages/nhan_su/index.php',
    'login'    => 'views/pages/auth/login.php',
    'forgot'   => 'views/pages/auth/forgot-password.php',
    'ho_so'    => 'views/pages/auth/ho_so.php',
    // Thành viên 2 - Hạ tầng
    'ha_tang'  => 'views/pages/ha_tang/index.php',
    // Thành viên 3 - Chuyến bay
    'chuyen_bay' => 'views/pages/chuyen_bay/index.php',
    // Thành viên 4 - Bảo trì
    'bao_tri'  => 'views/pages/bao_tri/index.php',
    // Thành viên 5 - Kế toán
    'ke_toan'  => 'views/pages/ke_toan/index.php',
];

$page = isset($_GET['page']) ? strtolower(trim((string) $_GET['page'])) : '';

/* ---------- Chặn đăng nhập (Middleware auth) ---------- */
$publicPages = ['login', 'forgot'];
if (!in_array($page, $publicPages, true)) {
    yeu_cau_dang_nhap();
}

/* Kiểm tra quyền truy cập theo vai trò (Role-based access control) */
if ($page !== '' && !in_array($page, $publicPages, true)) {
    if ($page === 'nhan_su' && !co_quyen_nhan_su()) {
        $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Quản lý nhân sự.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    if ($page === 'ke_toan' && !co_quyen_ke_toan()) {
        $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Kế toán & Doanh thu.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    if ($page === 'bao_tri' && !co_quyen_bao_tri()) {
        $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Kỹ thuật & Bảo trì.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/* Nếu có ?page=... và tồn tại trong bảng điều hướng thì nạp trang đó.
   Bản thân các file trong views/pages/ đã tự gọi layout nên chỉ cần require. */
if ($page !== '' && isset($routes[$page])) {
    $viewFile = __DIR__ . '/' . $routes[$page];
    if (is_file($viewFile)) {
        require $viewFile;
        exit;
    }
}

/* ---------- Mặc định: trang tổng quan ---------- */
$pageTitle  = 'Tổng quan điều hành chuỗi cảng hàng không';
$pageHeader = 'Trung tâm điều hành chuỗi cảng hàng không';
$navActive  = '';
$breadcrumb = [
    ['label' => 'Trang chủ', 'url' => null],
    ['label' => 'Tổng quan', 'url' => null],
];

// Nạp kết nối CSDL để lấy chỉ số điều hành thực tế
require_once __DIR__ . '/config/database.php';

$userHoTen    = $_SESSION['ho_ten'] ?? 'Quản trị viên';
$nhanVaiTro   = [
    'Admin_Tong_Cong_Ty'   => 'Quản trị tổng công ty',
    'Quan_Ly_San_Bay'      => 'Quản lý sân bay',
    'Nhan_Vien_Dieu_Hanh'  => 'Nhân viên điều hành',
    'Nhan_Vien_Bao_Tri'    => 'Nhân viên bảo trì',
    'Ke_Toan'              => 'Kế toán',
];
$vaiTroRaw     = $_SESSION['vai_tro'] ?? '';
$vaiTroHienThi = $nhanVaiTro[$vaiTroRaw] ?? ($vaiTroRaw ?: 'Quản trị viên');
if (!empty($_SESSION['san_bay_iata'])) {
    $vaiTroHienThi .= ' (' . $_SESSION['san_bay_iata'] . ')';
} elseif (la_admin()) {
    $vaiTroHienThi .= ' (Toàn quốc)';
}

$userSanBay   = pham_vi_san_bay();
$isQuanLySb   = ($userSanBay > 0);
$tenSbHienTai = '';

if ($isQuanLySb) {
    $stmtSb = $conn->prepare("SELECT ma_iata, ten_san_bay FROM san_bay WHERE id = ?");
    if ($stmtSb) {
        $stmtSb->bind_param('i', $userSanBay);
        $stmtSb->execute();
        $resSb = $stmtSb->get_result();
        if ($rowSb = $resSb->fetch_assoc()) {
            $tenSbHienTai = $rowSb['ten_san_bay'] . ' (' . $rowSb['ma_iata'] . ')';
        }
        $stmtSb->close();
    }
}

// 1. Thống kê mạng lưới sân bay
$soSanBay = 0;
$danhSachSanBay = [];
$resAllSb = $conn->query("SELECT id, ma_iata, ten_san_bay, ten_ngan FROM san_bay ORDER BY id ASC");
if ($resAllSb) {
    while ($rowSb = $resAllSb->fetch_assoc()) {
        $danhSachSanBay[] = $rowSb;
    }
    $soSanBay = count($danhSachSanBay);
}

// 2. Chuyến bay (kiểm tra an toàn)
$soChuyenBayHomNay = 0;
$soChuyenBayDaHa = 0;
try {
    $sqlCb = "SELECT COUNT(*) AS total, SUM(trang_thai_tong = 'Ha_Canh') AS da_ha FROM chuyen_bay_ngay WHERE DATE(gio_du_kien) = CURRENT_DATE()";
    if ($isQuanLySb) {
        $sqlCb .= " AND (id_san_bay_di = " . (int)$userSanBay . " OR id_san_bay_den = " . (int)$userSanBay . ")";
    }
    $resCb = $conn->query($sqlCb);
    if ($resCb && ($rowCb = $resCb->fetch_assoc())) {
        $soChuyenBayHomNay = (int) $rowCb['total'];
        $soChuyenBayDaHa = (int) ($rowCb['da_ha'] ?? 0);
    }
    // Nếu hôm nay chưa có chuyến bay nào thì lấy tổng số chuyến bay trong hệ thống
    if ($soChuyenBayHomNay === 0) {
        $sqlCbAll = "SELECT COUNT(*) AS total, SUM(trang_thai_tong = 'Ha_Canh') AS da_ha FROM chuyen_bay_ngay WHERE 1=1";
        if ($isQuanLySb) {
            $sqlCbAll .= " AND (id_san_bay_di = " . (int)$userSanBay . " OR id_san_bay_den = " . (int)$userSanBay . ")";
        }
        $resCbAll = $conn->query($sqlCbAll);
        if ($resCbAll && ($rowCbAll = $resCbAll->fetch_assoc())) {
            $soChuyenBayHomNay = (int) $rowCbAll['total'];
            $soChuyenBayDaHa = (int) ($rowCbAll['da_ha'] ?? 0);
        }
    }
} catch (Throwable $e) {
    $soChuyenBayHomNay = 0;
    $soChuyenBayDaHa = 0;
}

// 3. Thiết bị & Bảo trì
$tongThietBi = 0;
$thietBiBaoTri = 0;
$phieuBaoTriMo = 0;
try {
    $sqlTb = "SELECT COUNT(*) AS total, SUM(trang_thai = 'Bao_Tri') AS dang_bt FROM thiet_bi_san_bay WHERE 1=1";
    if ($isQuanLySb) {
        $sqlTb .= " AND id_san_bay = " . (int)$userSanBay;
    }
    $resTb = $conn->query($sqlTb);
    if ($resTb && ($rowTb = $resTb->fetch_assoc())) {
        $tongThietBi = (int) $rowTb['total'];
        $thietBiBaoTri = (int) ($rowTb['dang_bt'] ?? 0);
    }

    $sqlPhieu = "SELECT COUNT(*) AS total FROM yeu_cau_bao_tri y JOIN thiet_bi_san_bay t ON y.id_thiet_bi = t.id WHERE y.trang_thai_xu_ly <> 'Hoan_Thanh'";
    if ($isQuanLySb) {
        $sqlPhieu .= " AND t.id_san_bay = " . (int)$userSanBay;
    }
    $resPhieu = $conn->query($sqlPhieu);
    if ($resPhieu && ($rowPhieu = $resPhieu->fetch_assoc())) {
        $phieuBaoTriMo = (int) $rowPhieu['total'];
    }
} catch (Throwable $e) {
    $tongThietBi = 0;
    $thietBiBaoTri = 0;
    $phieuBaoTriMo = 0;
}

// 4. Doanh thu tháng hiện hành
$doanhThuThang = 0.0;
$hienThiDoanhThu = (co_quyen_ke_toan() || la_admin());
if ($hienThiDoanhThu) {
    try {
        $sqlDt = "SELECT SUM(hd.tong_tien) AS total FROM hoa_don hd JOIN chuyen_bay_ngay cb ON hd.id_chuyen_bay = cb.id WHERE MONTH(hd.ngay_xuat) = MONTH(CURRENT_DATE()) AND YEAR(hd.ngay_xuat) = YEAR(CURRENT_DATE()) AND hd.trang_thai_thanh_toan = 'Da_Thanh_Toan'";
        if ($isQuanLySb) {
            $sqlDt .= " AND (cb.id_san_bay_di = " . (int)$userSanBay . " OR cb.id_san_bay_den = " . (int)$userSanBay . ")";
        }
        $resDt = $conn->query($sqlDt);
        if ($resDt && ($rowDt = $resDt->fetch_assoc()) && $rowDt['total'] !== null) {
            $doanhThuThang = (float) $rowDt['total'];
        } else {
            // Lấy tổng thực thu lũy kế nếu tháng này chưa phát sinh
            $sqlDtAll = "SELECT SUM(tong_tien) AS total FROM hoa_don WHERE trang_thai_thanh_toan = 'Da_Thanh_Toan'";
            $resDtAll = $conn->query($sqlDtAll);
            if ($resDtAll && ($rowDtAll = $resDtAll->fetch_assoc())) {
                $doanhThuThang = (float) ($rowDtAll['total'] ?? 0);
            }
        }
    } catch (Throwable $e) {
        $doanhThuThang = 0.0;
    }
}

// 5. Hãng hàng không & Loại tàu bay
$soHang = 0;
try {
    $resHang = $conn->query("SELECT COUNT(*) AS total FROM hang_hang_khong");
    if ($resHang && ($rowHang = $resHang->fetch_assoc())) {
        $soHang = (int) $rowHang['total'];
    }
} catch (Throwable $e) {
    $soHang = 0;
}

$soLoaiTauBay = 0;
try {
    $sqlLtb = "SELECT COUNT(*) AS total FROM loai_tau_bay WHERE 1=1";
    if ($isQuanLySb) {
        $sqlLtb .= " AND id_san_bay = " . (int)$userSanBay;
    }
    $resLtb = $conn->query($sqlLtb);
    if ($resLtb && ($rowLtb = $resLtb->fetch_assoc())) {
        $soLoaiTauBay = (int) $rowLtb['total'];
    }
} catch (Throwable $e) {
    $soLoaiTauBay = 0;
}

/* Danh sách phân hệ chức năng hàng không */
$rawModules = [
    [
        'key'        => 'chuyen_bay',
        'name'       => 'Điều hành & Lịch bay',
        'desc'       => 'Giám sát điều phối cất hạ cánh, lịch bay theo ngày/tuần, thông tin chuyến bay và hãng khai thác.',
        'icon'       => 'bi-airplane-engines',
        'badge'      => 'Vận hành bay',
        'quickLinks' => [
            ['label' => 'Bảng điều hành bay', 'url' => 'index.php?page=chuyen_bay'],
            ['label' => 'Chuyến bay ngày', 'url' => 'views/pages/chuyen_bay/chuyen_bay_ngay.php'],
            ['label' => 'Lịch bay tuần', 'url' => 'views/pages/chuyen_bay/lich_bay_tuan.php'],
        ],
    ],
    [
        'key'        => 'ha_tang',
        'name'       => 'Hạ tầng & Cảng hàng không',
        'desc'       => 'Quản lý tài nguyên nhà ga hành khách, hệ thống đường cất hạ cánh, sân đỗ tàu bay và băng chuyền.',
        'icon'       => 'bi-buildings',
        'badge'      => 'Cảng & Mặt đất',
        'quickLinks' => [
            ['label' => 'Mạng lưới sân bay', 'url' => 'index.php?page=ha_tang'],
            ['label' => 'Nhà ga hành khách', 'url' => 'views/pages/ha_tang/nha_ga.php'],
            ['label' => 'Đường băng & Sân đỗ', 'url' => 'views/pages/ha_tang/duong_bang.php'],
        ],
    ],
    [
        'key'        => 'bao_tri',
        'name'       => 'Kỹ thuật & Bảo trì Thiết bị',
        'desc'       => 'Phiếu yêu cầu bảo trì, giám sát tình trạng vận hành thiết bị cảng hàng không và danh mục loại tàu bay (MTOW).',
        'icon'       => 'bi-tools',
        'badge'      => 'Kỹ thuật & An toàn',
        'quickLinks' => [
            ['label' => 'Phiếu bảo trì', 'url' => 'index.php?page=bao_tri'],
            ['label' => 'Thiết bị sân bay', 'url' => 'views/pages/bao_tri/thiet_bi.php'],
            ['label' => 'Danh mục loại tàu bay', 'url' => 'views/pages/bao_tri/tau_bay.php'],
        ],
    ],
    [
        'key'        => 'ke_toan',
        'name'       => 'Tài chính & Doanh thu',
        'desc'       => 'Hóa đơn dịch vụ hàng không, phí cất hạ cánh, đỗ tàu bay, thống kê doanh thu 12 tháng và xuất báo cáo.',
        'icon'       => 'bi-cash-coin',
        'badge'      => 'Tài chính & Thu phí',
        'quickLinks' => [
            ['label' => 'Hóa đơn dịch vụ', 'url' => 'index.php?page=ke_toan'],
            ['label' => 'Chi tiết doanh thu tháng', 'url' => 'views/pages/ke_toan/chi_tiet_thang.php'],
            ['label' => 'Thống kê năm', 'url' => 'views/pages/ke_toan/thong_ke.php'],
        ],
    ],
    [
        'key'        => 'nhan_su',
        'name'       => 'Nhân lực & Phân quyền',
        'desc'       => 'Danh bạ cán bộ nhân viên chuỗi cảng, phân quyền vai trò quản lý theo từng sân bay và tài khoản.',
        'icon'       => 'bi-people-fill',
        'badge'      => 'Tổ chức nhân sự',
        'quickLinks' => [
            ['label' => 'Danh bạ nhân viên', 'url' => 'index.php?page=nhan_su'],
            ['label' => 'Hồ sơ cá nhân', 'url' => 'index.php?page=ho_so'],
        ],
    ],
];

$modules = [];
foreach ($rawModules as $m) {
    if ($m['key'] === 'nhan_su' && !co_quyen_nhan_su()) continue;
    if ($m['key'] === 'ke_toan' && !co_quyen_ke_toan()) continue;
    if ($m['key'] === 'bao_tri' && !co_quyen_bao_tri()) continue;
    if ($m['key'] === 'chuyen_bay' && (la_bao_tri() || la_ke_toan())) continue;
    $modules[] = $m;
}

ob_start();
?>
<!--begin::Aviation Hero Banner (Trắng & Xanh da trời)-->
<div class="sky-hero">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <div class="sky-hero__badge">
        <i class="bi bi-airplane-fill"></i>
        <span>HỆ THỐNG ĐIỀU HÀNH CHUỖI CẢNG HÀNG KHÔNG (AeroChain)</span>
      </div>
      <h1 class="sky-hero__title">
        Chào mừng, <?= htmlspecialchars($userHoTen, ENT_QUOTES, 'UTF-8') ?>!
      </h1>
      <p class="sky-hero__lead">
        Trung tâm giám sát và điều hành tập trung mạng lưới cảng hàng không. Theo dõi hoạt động khai thác chuyến bay, hệ thống hạ tầng đường băng - nhà ga, kỹ thuật bảo trì và quản trị doanh thu thời gian thực.
      </p>
      <div class="sky-hero__meta">
        <div class="sky-hero__meta-item">
          <i class="bi bi-shield-check"></i>
          <span>Vai trò: <strong><?= htmlspecialchars($vaiTroHienThi, ENT_QUOTES, 'UTF-8') ?></strong></span>
        </div>
        <div class="sky-hero__meta-item">
          <i class="bi bi-geo-alt-fill"></i>
          <span>Phạm vi: <strong><?= $isQuanLySb && $tenSbHienTai !== '' ? htmlspecialchars($tenSbHienTai, ENT_QUOTES, 'UTF-8') : 'Toàn mạng lưới cảng hàng không' ?></strong></span>
        </div>
        <div class="sky-hero__meta-item">
          <i class="bi bi-calendar3"></i>
          <span><?= date('d/m/Y') ?></span>
        </div>
        <div class="sky-hero__status-pill">
          <span class="sky-hero__status-dot"></span>
          <span>Hệ thống trực tuyến</span>
        </div>
      </div>
    </div>
    <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
      <div class="d-inline-flex flex-column gap-2 text-start bg-white p-3 rounded-3 border border-info-subtle shadow-sm">
        <div class="small text-secondary fw-semibold text-uppercase">
          <i class="bi bi-broadcast text-primary me-1"></i>Kênh điều hành trực tiếp
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="index.php?page=chuyen_bay" class="btn btn-sm btn-primary px-3">
            <i class="bi bi-airplane-engines me-1"></i>Lịch bay hôm nay
          </a>
          <a href="index.php?page=bao_tri" class="btn btn-sm btn-outline-primary px-3">
            <i class="bi bi-tools me-1"></i>Bảo trì
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<!--end::Aviation Hero Banner-->

<!--begin::Hàng chỉ số KPI then chốt-->
<div class="row g-3 mb-4">
  <!-- Thẻ 1: Mạng lưới cảng hàng không -->
  <div class="col-sm-6 col-xl-3">
    <div class="sky-metric-card">
      <div class="sky-metric-card__header">
        <span class="sky-metric-card__title"><?= $isQuanLySb ? 'Sân bay phụ trách' : 'Mạng lưới sân bay' ?></span>
        <span class="sky-metric-card__icon-box"><i class="bi bi-buildings"></i></span>
      </div>
      <div class="sky-metric-card__value"><?= $soSanBay ?></div>
      <p class="sky-metric-card__subtext">
        <i class="bi bi-check-circle-fill text-success"></i>
        <span><?= $isQuanLySb ? ($tenSbHienTai ?: 'Cảng đang quản lý') : 'Toàn bộ cảng đang vận hành' ?></span>
      </p>
    </div>
  </div>

  <!-- Thẻ 2: Chuyến bay hôm nay -->
  <div class="col-sm-6 col-xl-3">
    <div class="sky-metric-card">
      <div class="sky-metric-card__header">
        <span class="sky-metric-card__title">Chuyến bay hôm nay</span>
        <span class="sky-metric-card__icon-box"><i class="bi bi-airplane-engines"></i></span>
      </div>
      <div class="sky-metric-card__value"><?= number_format($soChuyenBayHomNay) ?></div>
      <p class="sky-metric-card__subtext">
        <i class="bi bi-arrow-down-circle text-primary"></i>
        <span>Đã hoàn thành: <strong><?= number_format($soChuyenBayDaHa) ?></strong> chuyến</span>
      </p>
    </div>
  </div>

  <!-- Thẻ 3: Thiết bị & An toàn bảo trì -->
  <div class="col-sm-6 col-xl-3">
    <div class="sky-metric-card">
      <div class="sky-metric-card__header">
        <span class="sky-metric-card__title">Thiết bị & Bảo trì</span>
        <span class="sky-metric-card__icon-box"><i class="bi bi-tools"></i></span>
      </div>
      <div class="sky-metric-card__value"><?= number_format($tongThietBi) ?></div>
      <p class="sky-metric-card__subtext">
        <?php if ($phieuBaoTriMo > 0): ?>
          <i class="bi bi-exclamation-triangle-fill text-warning"></i>
          <span>Phiếu đang mở: <strong class="text-danger"><?= $phieuBaoTriMo ?></strong></span>
        <?php else: ?>
          <i class="bi bi-shield-check text-success"></i>
          <span>100% thiết bị vận hành tốt</span>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <!-- Thẻ 4: Doanh thu tháng hoặc Tàu bay liên kết -->
  <div class="col-sm-6 col-xl-3">
    <div class="sky-metric-card">
      <div class="sky-metric-card__header">
        <span class="sky-metric-card__title"><?= $hienThiDoanhThu ? 'Doanh thu tháng ' . date('n') : 'Dòng tàu bay & Hãng' ?></span>
        <span class="sky-metric-card__icon-box">
          <i class="bi <?= $hienThiDoanhThu ? 'bi-cash-coin' : 'bi-airplane' ?>"></i>
        </span>
      </div>
      <?php if ($hienThiDoanhThu): ?>
        <div class="sky-metric-card__value" title="<?= number_format($doanhThuThang, 0, ',', '.') ?> VNĐ">
          <?php
          if ($doanhThuThang >= 1000000000) {
              echo number_format($doanhThuThang / 1000000000, 2) . ' <small style="font-size: 0.95rem;">tỷ</small>';
          } elseif ($doanhThuThang >= 1000000) {
              echo number_format($doanhThuThang / 1000000, 1) . ' <small style="font-size: 0.95rem;">tr</small>';
          } else {
              echo number_format($doanhThuThang) . ' <small style="font-size: 0.85rem;">đ</small>';
          }
          ?>
        </div>
        <p class="sky-metric-card__subtext">
          <i class="bi bi-check2-circle text-primary"></i>
          <span>Đã thực thu trong tháng <?= date('n/Y') ?></span>
        </p>
      <?php else: ?>
        <div class="sky-metric-card__value"><?= $soLoaiTauBay ?></div>
        <p class="sky-metric-card__subtext">
          <i class="bi bi-buildings text-primary"></i>
          <span>Liên kết <strong><?= $soHang ?></strong> hãng hàng không</span>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<!--end::Hàng chỉ số KPI then chốt-->

<!--begin::Khu vực phân hệ chức năng-->
<div class="sky-section-header">
  <h2>Trung tâm nghiệp vụ điều hành</h2>
  <p>Truy cập trực tiếp vào các phân hệ chức năng của chuỗi cảng hàng không theo thẩm quyền phân công.</p>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($modules as $m): ?>
    <div class="col-lg-4 col-md-6">
      <div class="sky-portal-card">
        <div>
          <div class="sky-portal-card__badge-row">
            <div class="sky-portal-card__icon">
              <i class="bi <?= $m['icon'] ?>"></i>
            </div>
            <span class="sky-portal-card__badge"><?= htmlspecialchars($m['badge'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <h3 class="sky-portal-card__title"><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          <p class="sky-portal-card__desc"><?= htmlspecialchars($m['desc'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div>
          <?php if (!empty($m['quickLinks'])): ?>
            <div class="sky-portal-card__quick-links">
              <?php foreach ($m['quickLinks'] as $ql): ?>
                <a href="<?= htmlspecialchars($ql['url'], ENT_QUOTES, 'UTF-8') ?>" class="sky-portal-card__tag">
                  <i class="bi bi-chevron-right small text-primary"></i><?= htmlspecialchars($ql['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <a href="index.php?page=<?= $m['key'] ?>" class="sky-portal-card__btn">
            <span>Mở phân hệ</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<!--end::Khu vực phân hệ chức năng-->

<!--begin::Khối thông tin bổ trợ: Mạng lưới cảng & Tiêu chuẩn vận hành-->
<div class="row g-3">
  <!-- Cột 1: Mạng lưới sân bay -->
  <div class="col-lg-7">
    <div class="sky-panel">
      <h3 class="sky-panel__title">
        <i class="bi bi-geo-alt-fill"></i>
        <span>Mạng lưới Cảng hàng không trực thuộc AeroChain (<?= count($danhSachSanBay) ?> cảng)</span>
      </h3>
      <p class="text-secondary small mb-3">
        Danh sách các cảng hàng không quốc tế và nội địa trong hệ thống quản lý điều hành tập trung:
      </p>
      <div class="sky-airports-chip-list">
        <?php foreach ($danhSachSanBay as $sbItem): ?>
          <div class="sky-airport-chip">
            <strong><?= htmlspecialchars($sbItem['ma_iata'], ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars($sbItem['ten_san_bay'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3 pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2 text-secondary small">
        <span><i class="bi bi-info-circle me-1 text-primary"></i>Kết nối dữ liệu thời gian thực toàn quốc</span>
        <a href="index.php?page=ha_tang" class="text-primary text-decoration-none fw-semibold">
          Quản lý chi tiết hạ tầng <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Cột 2: Tiêu chuẩn an toàn & Hướng dẫn-->
  <div class="col-lg-5">
    <div class="sky-panel">
      <h3 class="sky-panel__title">
        <i class="bi bi-shield-check"></i>
        <span>Tiêu chuẩn vận hành & Điều phối</span>
      </h3>
      <ul class="sky-guide-list">
        <li class="sky-guide-item">
          <span class="sky-guide-item__num">1</span>
          <div>
            <strong>Phân quyền khu vực nghiêm ngặt:</strong> Dữ liệu tàu bay, hạ tầng và chuyến bay được kiểm soát chặt theo phạm vi sân bay được phân công.
          </div>
        </li>
        <li class="sky-guide-item">
          <span class="sky-guide-item__num">2</span>
          <div>
            <strong>Giám sát bảo trì chủ động:</strong> Ưu tiên xử lý các phiếu bảo dưỡng thiết bị ở mức độ khẩn cấp nhằm đảm bảo an toàn bay 100%.
          </div>
        </li>
        <li class="sky-guide-item">
          <span class="sky-guide-item__num">3</span>
          <div>
            <strong>Đối soát tài chính định kỳ:</strong> Tra cứu bảng kê chi tiết hóa đơn theo tháng và xuất báo cáo chuẩn Excel (.xls) phục vụ kiểm toán.
          </div>
        </li>
      </ul>
      <div class="mt-3 pt-3 border-top text-center">
        <span class="small text-secondary">
          Hệ thống Quản lý Chuỗi Cảng Hàng không AeroChain · Bản quyền 2026
        </span>
      </div>
    </div>
  </div>
</div>
<!--end::Khối thông tin bổ trợ-->
<?php
$content = ob_get_clean();
require __DIR__ . '/views/layouts/main.php';