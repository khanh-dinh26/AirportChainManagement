<?php
/**
 * ============================================================
 * TRANG: LỊCH SỬ HÓA ĐƠN & THỐNG KÊ SẢN LƯỢNG HÀNH KHÁCH
 * views/pages/ke_toan/lich_su.php
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

// Xử lý xác nhận thu tiền nếu thao tác từ trang lịch sử
if (isset($_GET['thanh_toan_id'])) {
    $idXacNhan = (int) $_GET['thanh_toan_id'];
    $keToan->XacNhanThanhToan($idXacNhan);
    $_SESSION['flash_success'] = "Đã xác nhận thu tiền hóa đơn #{$idXacNhan} thành công!";
    $filterParam = isset($_GET['loc_hd']) ? '&loc_hd=' . urlencode($_GET['loc_hd']) : '';
    $pParam = isset($_GET['p']) ? '&p=' . (int) $_GET['p'] : '';
    header('Location: ' . BASE_URL . '/index.php?page=ke_toan&view=lich_su&tab=hoa_don' . $filterParam . $pParam);
    exit();
}

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Tab đang chọn: 'hoa_don' hoặc 'khach'
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'khach' ? 'khach' : 'hoa_don';

// --- DỮ LIỆU TAB 1: LỊCH SỬ HÓA ĐƠN & PHÂN TRANG ---
$filterLichSu = isset($_GET['loc_hd']) ? (string) $_GET['loc_hd'] : null;
$p = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 10;

$tongHoaDon = $keToan->DemLichSuHoaDon($filterLichSu);
$tongPages = max(1, (int) ceil($tongHoaDon / $perPage));
if ($p > $tongPages) {
    $p = $tongPages;
}
$offset = ($p - 1) * $perPage;

$danh_sach_lich_su = $keToan->LichSuHoaDon($filterLichSu, $perPage, $offset);
$tongTien = $keToan->TongTienLichSuHoaDon($filterLichSu);

// Helper tạo URL phân trang
$queryBase = [
    'page' => 'ke_toan',
    'view' => 'lich_su',
    'tab' => 'hoa_don',
];
if (!empty($filterLichSu)) {
    $queryBase['loc_hd'] = $filterLichSu;
}
$urlPage = static function ($pageNum) use ($queryBase) {
    $params = array_merge($queryBase, ['p' => $pageNum]);
    return BASE_URL . '/index.php?' . http_build_query($params);
};

// --- DỮ LIỆU TAB 2: THỐNG KÊ SẢN LƯỢNG HÀNH KHÁCH ---
$danh_sach_san_bay = $keToan->DanhSachSanBay();
$san_bay_chon = isset($_GET['san_bay']) ? (int) $_GET['san_bay'] : 0;
$nam_hien_tai = (int) date('Y');
$tu_nam = isset($_GET['tu_nam']) ? (int) $_GET['tu_nam'] : $nam_hien_tai;
$den_nam = isset($_GET['den_nam']) ? (int) $_GET['den_nam'] : $nam_hien_tai;
$tong_khach_san_bay = 0;
$chi_tiet_thang = [];
$chi_tiet_chuyen_bay = [];

if ($san_bay_chon > 0) {
    $activeTab = 'khach'; // Tự động active tab thống kê khách khi có filter sân bay
    $tong_khach_san_bay = $keToan->ThongKeHanhKhachSanBay($san_bay_chon, $tu_nam, $den_nam);
    $chi_tiet_thang = $keToan->ChiTietKhachTheoThang($san_bay_chon, $tu_nam, $den_nam);
    $chi_tiet_chuyen_bay = $keToan->ChiTietKhachTheoChuyenBay($san_bay_chon, $tu_nam, $den_nam);
}

$pageTitle = 'Lịch sử hóa đơn & Thống kê sản lượng';
$pageHeader = 'Lịch sử hóa đơn & Thống kê sản lượng';
$navActive = 'ke_toan';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Kế toán & doanh thu', 'url' => BASE_URL . '/index.php?page=ke_toan'],
    ['label' => 'Lịch sử & Thống kê', 'url' => null],
];

ob_start();
?>

<?php if ($flashSuccess !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <!-- NÚT CHUYỂN TRANG / CHUYỂN TAB -->
    <ul class="nav nav-pills" id="keToanTabs">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&tab=hoa_don" 
               class="nav-link <?= $activeTab === 'hoa_don' ? 'active shadow-sm' : '' ?>">
                <i class="bi bi-clock-history me-1"></i>Lịch sử hóa đơn đã lập
                <span class="badge <?= $activeTab === 'hoa_don' ? 'bg-light text-dark' : 'bg-secondary' ?> ms-1"><?= $tongHoaDon ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&tab=khach" 
               class="nav-link <?= $activeTab === 'khach' ? 'active shadow-sm' : '' ?>">
                <i class="bi bi-people me-1"></i>Thống kê sản lượng hành khách
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=thong_ke" 
               class="nav-link">
                <i class="bi bi-bar-chart-line-fill me-1"></i>Thống kê doanh thu (Biểu đồ)
            </a>
        </li>
    </ul>

    <a href="<?= BASE_URL ?>/index.php?page=ke_toan" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Quay lại Kế toán chính
    </a>
</div>

<!-- ==================== NỘI DUNG THEO TAB ==================== -->
<?php if ($activeTab === 'hoa_don'): ?>
    <!-- TAB 1: LỊCH SỬ HÓA ĐƠN -->
    <div class="card card-primary card-outline shadow-sm">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <h3 class="card-title mb-0"><i class="bi bi-receipt me-2"></i>Danh sách tất cả hóa đơn đã lập</h3>
                <span class="badge text-bg-secondary ms-1">Tổng <?= $tongHoaDon ?> hóa đơn</span>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&tab=hoa_don" class="btn <?= empty($filterLichSu) ? 'btn-primary' : 'btn-outline-primary' ?>">Tất cả</a>
                    <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&tab=hoa_don&loc_hd=Chua_Thanh_Toan" class="btn <?= $filterLichSu === 'Chua_Thanh_Toan' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">Chờ thu</a>
                    <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&tab=hoa_don&loc_hd=Da_Thanh_Toan" class="btn <?= $filterLichSu === 'Da_Thanh_Toan' ? 'btn-success' : 'btn-outline-success' ?>">Đã thu</a>
                </div>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã HĐ</th>
                        <th>Chuyến bay</th>
                        <th>Hãng hàng không</th>
                        <th>Chặng bay</th>
                        <th>Thời gian đỗ</th>
                        <th>Tổng tiền</th>
                        <th>Ngày lập</th>
                        <th>Trạng thái</th>
                        <th class="text-end" style="width: 110px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($danh_sach_lich_su)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Không có hóa đơn nào phù hợp với bộ lọc.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($danh_sach_lich_su as $hd): ?>
                    <tr>
                        <td class="fw-bold text-danger">#<?= (int) $hd['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($hd['so_hieu']) ?></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($hd['ten_hang']) ?></span></td>
                        <td><small><?= htmlspecialchars($hd['ten_san_bay_di']) ?> → <?= htmlspecialchars($hd['ten_san_bay_den']) ?></small></td>
                        <td>
                            <?= $hd['thoi_gian_do_gio'] !== null ? htmlspecialchars($hd['thoi_gian_do_gio']) . ' giờ' : '—' ?>
                            <?php if (!empty($hd['tam_tinh'])): ?><span class="badge bg-warning text-dark ms-1">Tạm tính</span><?php endif; ?>
                        </td>
                        <td class="fw-bold text-primary"><?= number_format($hd['tong_tien']) ?> VNĐ</td>
                        <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($hd['ngay_xuat'])) ?></small></td>
                        <td>
                            <?php if ($hd['trang_thai_thanh_toan'] === 'Da_Thanh_Toan'): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Đã thanh toán</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Chờ thu</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end" style="white-space: nowrap;">
                            <div class="d-inline-flex flex-column gap-1 align-items-end">
                                <a href="<?= BASE_URL ?>/views/pages/ke_toan/chi_tiet_hoa_don.php?id=<?= (int) $hd['id'] ?>" 
                                   class="btn btn-sm btn-outline-info d-inline-flex align-items-center justify-content-center" 
                                   style="width: 88px;">
                                    <i class="bi bi-eye me-1"></i>Xem
                                </a>
                                <?php if ($hd['trang_thai_thanh_toan'] !== 'Da_Thanh_Toan'): ?>
                                    <a href="<?= BASE_URL ?>/index.php?page=ke_toan&view=lich_su&thanh_toan_id=<?= (int) $hd['id'] ?><?= $filterLichSu ? '&loc_hd=' . urlencode($filterLichSu) : '' ?>&p=<?= $p ?>" 
                                       class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center" 
                                       style="width: 88px;">
                                        <i class="bi bi-check-lg me-1"></i>Thu tiền
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($danh_sach_lich_su)): ?>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end">TỔNG GIÁ TRỊ (TOÀN BỘ):</th>
                        <th class="text-primary fs-6"><?= number_format($tongTien) ?> VNĐ</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- PHÂN TRANG -->
        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2 bg-light">
            <div class="text-muted small">
                Hiển thị <strong><?= $tongHoaDon > 0 ? $offset + 1 : 0 ?></strong> đến <strong><?= min($offset + $perPage, $tongHoaDon) ?></strong> trong tổng số <strong><?= $tongHoaDon ?></strong> hóa đơn
            </div>
            <?php if ($tongPages > 1): ?>
            <nav aria-label="Phân trang hóa đơn">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $p <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $urlPage(1) ?>" title="Trang đầu">&laquo;&laquo;</a>
                    </li>
                    <li class="page-item <?= $p <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $urlPage($p - 1) ?>" title="Trang trước">&laquo;</a>
                    </li>
                    <?php for ($i = max(1, $p - 2); $i <= min($tongPages, $p + 2); $i++): ?>
                        <li class="page-item <?= $i === $p ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $urlPage($i) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $p >= $tongPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $urlPage($p + 1) ?>" title="Trang sau">&raquo;</a>
                    </li>
                    <li class="page-item <?= $p >= $tongPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $urlPage($tongPages) ?>" title="Trang cuối">&raquo;&raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- TAB 2: THỐNG KÊ SẢN LƯỢNG HÀNH KHÁCH -->
    <div class="card card-secondary card-outline shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="bi bi-people me-2"></i>Thống kê sản lượng hành khách theo Sân bay</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <input type="hidden" name="page" value="ke_toan">
                <input type="hidden" name="view" value="lich_su">
                <input type="hidden" name="tab" value="khach">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Chọn sân bay:</label>
                    <select name="san_bay" class="form-select" required>
                        <option value="">-- Chọn sân bay --</option>
                        <?php foreach ($danh_sach_san_bay as $sb): ?>
                            <option value="<?= $sb['id'] ?>" <?= ($san_bay_chon == $sb['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sb['ten_san_bay']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Từ năm:</label>
                    <input type="number" name="tu_nam" class="form-control" min="2000" max="2050" value="<?= $tu_nam ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Đến năm:</label>
                    <input type="number" name="den_nam" class="form-control" min="2000" max="2050" value="<?= $den_nam ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-1"></i>Lọc dữ liệu
                    </button>
                </div>
            </form>

            <!-- KẾT QUẢ HIỂN THỊ -->
            <?php if ($san_bay_chon > 0): ?>
                <div class="alert alert-info text-center mb-4">
                    <h4 class="mb-0">
                        Tổng sản lượng hành khách (Đi + Đến) từ <?= $tu_nam ?> đến <?= $den_nam ?>: 
                        <strong class="fs-3 text-primary"><?= number_format($tong_khach_san_bay) ?></strong> lượt khách
                    </h4>
                </div>

                <!-- BẢNG 1: CHI TIẾT THEO THÁNG -->
                <?php if (!empty($chi_tiet_thang)): ?>
                    <h6 class="fw-bold mb-3"><i class="bi bi-calendar-month text-primary me-2"></i>Chi tiết theo tháng</h6>
                    <table class="table table-bordered table-striped text-center mb-4">
                        <thead class="table-dark">
                            <tr><th>Năm</th><th>Tháng</th><th>Số lượng khách</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chi_tiet_thang as $ct): ?>
                            <tr>
                                <td><?= $ct['nam'] ?></td>
                                <td>Tháng <?= $ct['thang'] ?></td>
                                <td class="fw-bold text-primary"><?= number_format($ct['tong_khach']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Không có dữ liệu chuyến bay nào trong khoảng thời gian này.</div>
                <?php endif; ?>

                <!-- BẢNG 2: CHI TIẾT THEO CHẶNG BAY -->
                <?php if (!empty($chi_tiet_chuyen_bay)): ?>
                    <hr class="my-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-airplane-engines text-primary me-2"></i>Chi tiết sản lượng theo từng chặng bay</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Chặng bay</th>
                                    <th class="text-center">Số hiệu</th>
                                    <th class="text-end">Số lượng khách</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chi_tiet_chuyen_bay as $ct_cb): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge text-bg-primary fs-6"><?= htmlspecialchars($ct_cb['ten_di']) ?></span>
                                        <i class="bi bi-arrow-right mx-2"></i>
                                        <span class="badge text-bg-danger fs-6"><?= htmlspecialchars($ct_cb['ten_den']) ?></span>
                                    </td>
                                    <td class="text-center fw-bold"><?= htmlspecialchars($ct_cb['so_hieu']) ?></td>
                                    <td class="text-end fw-bold text-success"><?= number_format($ct_cb['tong_khach']) ?> khách</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                    Vui lòng chọn sân bay và khoảng năm bên trên để xem thống kê sản lượng hành khách.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
