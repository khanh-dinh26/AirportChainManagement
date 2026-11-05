<?php

//1. Kết nối cơ sở dữ liệu
require_once __DIR__ . '/../../../config/database.php'; 
require_once __DIR__ . '/../../../controllers/KeToanController.php';
$keToan = new KeToanController($conn);
$keToan->TuDongTaoHoaDon();

//2. Xử lý các hành động
$keToanUrl = 'index.php?page=ke_toan';

if (empty($_SESSION['ke_toan_csrf'])) {
    $_SESSION['ke_toan_csrf'] = bin2hex(random_bytes(32));
}

if (isset($_POST['btn_sua_gia'])) {
    $id    = (int) ($_POST['id'] ?? 0);
    $gia   = (float) ($_POST['don_gia'] ?? 0);
    $donVi = trim((string) ($_POST['don_vi_tinh'] ?? ''));
    $token = (string) ($_POST['csrf'] ?? '');
    $daLuu = false;

    if (!hash_equals($_SESSION['ke_toan_csrf'], $token)) {
        $_SESSION['flash_error'] = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } elseif ($id <= 0 || !$keToan->LayDonGia($id)) {
        $_SESSION['flash_error'] = 'Không tìm thấy khoản phí cần sửa.';
    } elseif ($gia <= 0) {
        $_SESSION['flash_error'] = 'Đơn giá phải lớn hơn 0.';
    } elseif ($donVi === '' || mb_strlen($donVi) > 50) {
        $_SESSION['flash_error'] = 'Đơn vị tính không được để trống (tối đa 50 ký tự).';
    } elseif ($keToan->CapNhatDonGia($id, $gia, $donVi)) {
        $_SESSION['flash_success'] = 'Đã cập nhật đơn giá thành công!';
        $daLuu = true;
    } else {
        $_SESSION['flash_error'] = 'Không cập nhật được đơn giá. Vui lòng thử lại.';
    }

    // Sau khi tải lại: mở lại khung biểu phí; nếu lỗi thì mở đúng dòng vừa sửa.
    $_SESSION['ke_toan_mo_bieu_phi'] = true;
    $chuyenHuong = $keToanUrl;
    if (!$daLuu && $id > 0) {
        $chuyenHuong .= '#sua-gia-' . $id;
    }
    header('Location: ' . $chuyenHuong);
    exit();
}

if (isset($_GET['tao_hoa_don']) && !empty($_GET['tao_hoa_don'])) {
    $ma_cb = (int) $_GET['tao_hoa_don'];
    if ($keToan->TinhVaTaoHoaDon($ma_cb)) {
        $_SESSION['flash_success'] = 'Đã tính toán và tạo hóa đơn thành công!';
    } else {
        $_SESSION['flash_error'] = 'Lỗi khi tạo hóa đơn! Vui lòng kiểm tra lại dữ liệu chuyến bay.';
    }
    header('Location: ' . $keToanUrl);
    exit();
}

if (isset($_GET['thanh_toan_id'])) {
    $keToan->XacNhanThanhToan((int) $_GET['thanh_toan_id']);
    $_SESSION['flash_success'] = 'Đã xác nhận thanh toán thành công!';
    header('Location: ' . $keToanUrl);
    exit();
}

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Mở sẵn khung "Biểu phí dịch vụ" sau khi lưu (tránh phải bấm lại nút nổi).
$moBieuPhi = !empty($_SESSION['ke_toan_mo_bieu_phi']);
unset($_SESSION['ke_toan_mo_bieu_phi']);

// 3. Lấy dữ liệu
$danh_sach_gia = $keToan->DanhSachDonGia();
$danh_sach_chuyen_bay = $keToan->ChuyenBayHoanThanh();
$data_thang = $keToan->DoanhThuTheoThang();
$data_hang = $keToan->DoanhThuTheoHang();
$stats = $keToan->ThongKeNhanh();
$danh_sach_hoa_don_cho = $keToan->DanhSachHoaDonCho();
$danh_sach_san_bay = $keToan->DanhSachSanBay();
$san_bay_chon = isset($_GET['san_bay']) ? (int)$_GET['san_bay'] : 0;
$nam_hien_tai = date('Y');
$tu_nam = isset($_GET['tu_nam']) ? (int)$_GET['tu_nam'] : $nam_hien_tai;
$den_nam = isset($_GET['den_nam']) ? (int)$_GET['den_nam'] : $nam_hien_tai;
$tong_khach_san_bay = 0;
$chi_tiet_thang = [];
$chi_tiet_chuyen_bay = [];
if ($san_bay_chon > 0) {
    $tong_khach_san_bay = $keToan->ThongKeHanhKhachSanBay($san_bay_chon, $tu_nam, $den_nam);
    $chi_tiet_thang = $keToan->ChiTietKhachTheoThang($san_bay_chon, $tu_nam, $den_nam);
    $chi_tiet_chuyen_bay = $keToan->ChiTietKhachTheoChuyenBay($san_bay_chon, $tu_nam, $den_nam);
}

$nhanPhi = [
    'Phi_Cat_Ha_Canh'        => 'Phí cất hạ cánh',
    'Phi_Do_Tau_Bay'         => 'Phí đỗ tàu bay',
    'Phi_Phuc_Vu_Hanh_Khach' => 'Phí phục vụ hành khách',
];

// 4. Cấu hình trang
$pageTitle  = 'Kế toán & doanh thu';
$pageHeader = 'Kế toán & doanh thu';
$navActive  = 'ke_toan';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => '/AirportChainManagement/index.php'],
    ['label' => 'Kế toán', 'url' => null],
];
ob_start();
?>

<?php if ($flashSuccess !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<!--PHẦN 1: THỐNG KÊ NHANH-->
<div class="row">
    <div class="col">
        <div class="small-box text-bg-primary">
            <div class="inner"><h3><?= number_format($stats['doanh_thu_thang'] / 1000000, 1) ?> Tr</h3><p>Doanh thu tháng</p></div>
            <i class="small-box-icon bi bi-graph-up-arrow"></i>
        </div>
    </div>
    <div class="col">
        <div class="small-box text-bg-success">
            <div class="inner"><h3><?= $stats['hd_da_thu'] ?></h3><p>Hóa đơn đã thu</p></div>
            <i class="small-box-icon bi bi-receipt"></i>
        </div>
    </div>
    <div class="col">
        <div class="small-box text-bg-warning">
            <div class="inner"><h3><?= $stats['hd_chua_thu'] ?></h3><p>Hóa đơn chờ thu</p></div>
            <i class="small-box-icon bi bi-hourglass-split"></i>
        </div>
    </div>
    <div class="col">
        <div class="small-box text-bg-danger">
            <div class="inner"><h3><?= number_format($stats['no_qua_han'] / 1000000, 1) ?> Tr</h3><p>Công nợ quá hạn</p></div>
            <i class="small-box-icon bi bi-exclamation-triangle"></i>
        </div>
    </div>
    <div class="col">
        <div class="small-box text-bg-info">
            <div class="inner"><h3><?= $stats['chuyen_bay_trong_ngay'] ?></h3><p>Chuyến bay trong ngày</p></div>
            <i class="small-box-icon bi bi-airplane"></i>
        </div>
    </div>
</div>

<!--PHẦN 2:HÓA ĐƠN CHỜ THANH TOÁN-->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-cash-coin me-2"></i>Hóa đơn chờ thanh toán</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Mã HĐ</th><th>Chuyến bay</th><th>Tổng tiền</th><th>Hạn chót</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($danh_sach_hoa_don_cho)): ?>
                            <tr><td colspan="5" class="text-center">Không có hóa đơn nào chờ thanh toán.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($danh_sach_hoa_don_cho as $hd): ?>
                        <tr>
                            <td>#<?= $hd['id'] ?></td>
                            <td><?= htmlspecialchars($hd['so_hieu']) ?></td>
                            <td class="text-danger fw-bold"><?= number_format($hd['tong_tien']) ?> VNĐ</td>
                            <td><?= date('d/m/Y', strtotime($hd['han_thanh_toan'])) ?></td>
                            <td class="text-end">
                                <a href="views/pages/ke_toan/chi_tiet_hoa_don.php?id=<?= (int) $hd['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> Xem</a>
                                <a href="index.php?page=ke_toan&thanh_toan_id=<?= $hd['id'] ?>" class="btn btn-sm btn-outline-success">Xác nhận thu tiền</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--PHẦN 3: THỐNG KÊ HÀNH KHÁCH THEO SÂN BAY-->
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-secondary card-outline">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-people me-2"></i>Thống kê sản lượng hành khách theo Sân bay</h3></div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end mb-4">
                    <input type="hidden" name="page" value="ke_toan">
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
                    <div class="col-md-4"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Lọc dữ liệu</button></div>
                </form>

                <!-- KẾT QUẢ HIỂN THỊ -->
                <?php if ($san_bay_chon > 0): ?>
                    <div class="alert alert-info text-center mb-4">
                        <h4 class="mb-0">Tổng sản lượng hành khách (Đi + Đến) từ <?= $tu_nam ?> đến <?= $den_nam ?>: <strong class="fs-3"><?= number_format($tong_khach_san_bay) ?></strong> lượt khách</h4>
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
                        <div class="alert alert-warning">Không có dữ liệu chuyến bay nào trong khoảng thời gian này.</div>
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
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!--PHẦN 4: CHUYẾN BAY ĐÃ HOÀN THÀNH-->
<div class="row mt-3 mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Chuyến bay đã hoàn thành (Chờ lập hóa đơn)</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap align-middle">
                    <thead>
                        <tr><th>Chuyến bay</th><th>Hãng / MTOW</th><th>Số khách</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($danh_sach_chuyen_bay)): ?>
                            <tr><td colspan="5" class="text-center">Không có chuyến bay nào cần lập hóa đơn.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($danh_sach_chuyen_bay as $cb): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cb['so_hieu']) ?></strong><br><small class="text-muted">ID: #<?= htmlspecialchars($cb['id']) ?></small></td>
                            <td><span class="badge text-bg-primary"><?= htmlspecialchars($cb['ten_hang']) ?></span><br><small class="text-muted">MTOW: <?= htmlspecialchars($cb['tai_trong_cat_canh_mtow']) ?> Tấn</small></td>
                            <td><span class="fw-bold"><?= htmlspecialchars($cb['luong_hanh_khach']) ?></span> khách</td>
                            <td>
                                <span class="badge text-bg-success">
                                    <?php 
                                        $trang_thai = $cb['trang_thai_tong'];
                                        // So sánh chính xác với dữ liệu trong Database
                                        if ($trang_thai == 'Hạ_Cánh' || $trang_thai == 'Ha_Canh' || $trang_thai == 'Hạ cánh') {
                                            echo 'Hạ Cánh';
                                        } elseif ($trang_thai == 'Hoan_Thanh') {
                                            echo 'Hoàn Thành';
                                        } elseif ($trang_thai == 'Delay') {
                                            echo 'Trễ Chuyến';
                                        } else {
                                            echo htmlspecialchars($trang_thai);
                                        }
                                    ?>
                                </span>
                            </td>
                            <td class="text-end"><a href="index.php?page=ke_toan&tao_hoa_don=<?= $cb['id'] ?>" class="btn btn-sm btn-outline-primary">Tính và lập hóa đơn</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="floating-price-btn">
    <details<?= $moBieuPhi ? ' open' : '' ?>>
        <summary class="btn btn-primary shadow" title="Cập nhật biểu phí"><i class="bi bi-tags-fill fs-4"></i></summary>
        <div class="card card-primary card-outline floating-price-content shadow-lg" id="bieu-phi">
            <div class="card-header"><h3 class="card-title">Biểu phí dịch vụ</h3></div>
            <div class="card-body">
                <p class="text-muted small mb-3">Bấm <strong>Sửa</strong> để chỉnh ngay tại dòng, không cần tải lại trang.</p>
                <hr>
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead><tr><th>Tên phí</th><th>Đơn giá</th><th class="text-end">Thao tác</th></tr></thead>
                    <tbody>
                        <?php foreach ($danh_sach_gia as $gia): ?>
                        <?php $tenPhi = $nhanPhi[$gia['loai_dich_vu']] ?? $gia['loai_dich_vu']; ?>
                        <tr class="gia-dong">
                            <td><?= htmlspecialchars($tenPhi, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format($gia['don_gia']) ?><br><small class="text-muted"><?= htmlspecialchars($gia['don_vi_tinh'], ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td class="text-end"><a href="#sua-gia-<?= (int) $gia['id'] ?>" class="btn btn-sm btn-outline-primary">Sửa</a></td>
                        </tr>
                        <tr class="gia-form" id="sua-gia-<?= (int) $gia['id'] ?>">
                            <td colspan="3">
                                <form method="post" action="index.php?page=ke_toan">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['ke_toan_csrf'], ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int) $gia['id'] ?>" />
                                    <div class="fw-bold small mb-2"><?= htmlspecialchars($tenPhi, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-1 fw-bold" for="don-gia-<?= (int) $gia['id'] ?>">Đơn giá (VNĐ)</label>
                                        <input type="number" min="0.01" step="0.01" id="don-gia-<?= (int) $gia['id'] ?>" name="don_gia" class="form-control form-control-sm" required value="<?= htmlspecialchars((string) $gia['don_gia'], ENT_QUOTES, 'UTF-8') ?>" />
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small mb-1 fw-bold" for="don-vi-<?= (int) $gia['id'] ?>">Đơn vị tính</label>
                                        <input type="text" maxlength="50" id="don-vi-<?= (int) $gia['id'] ?>" name="don_vi_tinh" class="form-control form-control-sm" required value="<?= htmlspecialchars($gia['don_vi_tinh'], ENT_QUOTES, 'UTF-8') ?>" />
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="submit" name="btn_sua_gia" class="btn btn-sm btn-primary flex-fill">Lưu thay đổi</button>
                                        <a href="#bieu-phi" class="btn btn-sm btn-light border">Hủy</a>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </details>
</div>

<!--MODAL CHI TIẾT HÓA ĐƠN-->
<div class="modal fade" id="modalChiTiet" tabindex="-1" aria-labelledby="modalChiTietLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalChiTietLabel"><i class="bi bi-receipt"></i> Chi tiết hóa đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="loading-spinner" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Đang tải dữ liệu...</p>
                </div>
                
                <div id="chi-tiet-content" style="display: none;">
                    <table class="table table-sm table-borderless mb-4">
                        <tr><th style="width: 30%;">Mã hóa đơn:</th><td class="fw-bold text-danger" id="ct-id">#</td></tr>
                        <tr><th>Hãng hàng không:</th><td id="ct-hang-bay"></td></tr>
                        <tr><th>Chuyến bay:</th><td class="fw-bold" id="ct-chuyen-bay"></td></tr>
                        <tr><th>Lộ trình:</th><td class="fw-bold" id="ct-lo-trinh"></td></tr>
                        <tr><th>Ngày lập:</th><td id="ct-ngay-lap"></td></tr>
                    </table>
                    
                    <h6 class="fw-bold border-bottom pb-2">Chi tiết các khoản phí</h6>
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Khoản phí</th>
                                <th class="text-center">Cách tính</th>
                                <th class="text-end">Thành tiền (VNĐ)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <tr>
                                    <td>
                                        Phí cất/hạ cánh
                                        <br><small class="text-muted">
                                            Đi: <span id="ct-ten-sb-di"></span> <br>
                                            Đến: <span class="text-muted" id="ct-ten-sb-den"></span>
                                        </small>
                                    </td>
                                    <td class="text-center" id="ct-mtow"></td>
                                    <td class="text-end" id="ct-phi-cat-ha-canh"></td>
                                </tr>
                            <tr>
                                <td>Phí đỗ tàu bay</td>
                                <td class="text-center">Theo thời gian đỗ</td>
                                <td class="text-end" id="ct-phi-do"></td>
                            </tr>
                            <tr>
                                <td>Phí phục vụ hành khách</td>
                                <td class="text-center" id="ct-so-khach"></td>
                                <td class="text-end" id="ct-phi-psc"></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-warning">
                                <th colspan="2" class="text-end fs-5">TỔNG CỘNG:</th>
                                <th class="text-end fs-5 text-danger" id="ct-tong-tien"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
.floating-price-btn { position: fixed; bottom: 30px; right: 30px; z-index: 1050; }
.floating-price-btn summary { list-style: none; cursor: pointer; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.floating-price-btn summary::-webkit-details-marker { display: none; }
.floating-price-content { position: absolute; bottom: 70px; right: 0; width: 350px; background: white; }

/* Sửa biểu phí tại chỗ bằng CSS :target — KHÔNG dùng JavaScript, KHÔNG tải lại trang. */
tr.gia-form { display: none; }
tr.gia-form:target { display: table-row; }
tr.gia-dong:has(+ tr.gia-form:target) { display: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalChiTiet = document.getElementById('modalChiTiet');
    if (modalChiTiet) {
        modalChiTiet.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var idHoaDon = button.getAttribute('data-id');
            document.getElementById('loading-spinner').style.display = 'block';
            document.getElementById('chi-tiet-content').style.display = 'none';
            fetch('views/pages/ke_toan/api_chi_tiet_hoa_don.php?id=' + idHoaDon)
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        var d = res.data;
                        document.getElementById('ct-id').innerText = '#' + d.id;
                        document.getElementById('ct-hang-bay').innerText = d.ten_hang;
                        document.getElementById('ct-chuyen-bay').innerText = d.so_hieu;
                        document.getElementById('ct-lo-trinh').innerText = d.ten_san_bay_di + ' ➔ ' + d.ten_san_bay_den;
                        document.getElementById('ct-ngay-lap').innerText = new Date(d.ngay_xuat).toLocaleString('vi-VN');
                        document.getElementById('ct-ten-sb-di').innerText = d.ten_san_bay_di;
                        document.getElementById('ct-ten-sb-den').innerText = d.ten_san_bay_den;
                        document.getElementById('ct-mtow').innerText = d.tai_trong_cat_canh_mtow + ' Tấn';
                        document.getElementById('ct-phi-cat-ha-canh').innerText = Number(d.phi_cat_ha_canh).toLocaleString('vi-VN');
                        document.getElementById('ct-phi-do').innerText = Number(d.phi_do_tau_bay).toLocaleString('vi-VN');
                        document.getElementById('ct-so-khach').innerText = d.luong_hanh_khach + ' khách';
                        document.getElementById('ct-phi-psc').innerText = Number(d.phi_phuc_vu_hk).toLocaleString('vi-VN');
                        document.getElementById('ct-tong-tien').innerText = Number(d.tong_tien).toLocaleString('vi-VN') + ' VNĐ';
                        document.getElementById('loading-spinner').style.display = 'none';
                        document.getElementById('chi-tiet-content').style.display = 'block';
                    }
                })
                .catch(error => {
                    alert("Có lỗi xảy ra khi tải dữ liệu!");
                    console.error(error);
                });
        });
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof bootstrap !== 'undefined') {
        console.log("Bootstrap đã sẵn sàng!");
    } else {
        var script = document.createElement('script');
        script.src = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js";
        document.head.appendChild(script);
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
?>