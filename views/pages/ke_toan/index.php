<?php

//1. Kết nối cơ sở dữ liệu & Kiểm tra quyền
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

// Chuyển sang trang xem lịch sử hóa đơn riêng biệt nếu có tham số view=lich_su
if (isset($_GET['view']) && $_GET['view'] === 'lich_su') {
    require __DIR__ . '/lich_su.php';
    exit();
}

// Chuyen sang trang thong ke doanh thu neu co tham so view=thong_ke
if (isset($_GET['view']) && $_GET['view'] === 'thong_ke') {
    require __DIR__ . '/thong_ke.php';
    exit();
}

// Chuyen sang trang chi tiet hoa don theo thang neu co tham so view=chi_tiet_thang
if (isset($_GET['view']) && $_GET['view'] === 'chi_tiet_thang') {
    require __DIR__ . '/chi_tiet_thang.php';
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['tao_hoa_don'])) {
    $ma_cb = (int) $_POST['tao_hoa_don'];
    if ($keToan->TinhVaTaoHoaDon($ma_cb)) {
        $_SESSION['flash_success'] = 'Đã tính toán và tạo hóa đơn thành công!';
    } else {
        $_SESSION['flash_error'] = 'Không lập được hóa đơn: chuyến đã có hóa đơn hoặc thiếu biểu phí/dữ liệu chuyến bay.';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['thanh_toan_id'])) {
    $keToan->XacNhanThanhToan((int) $_POST['thanh_toan_id']);
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

<!--PHẦN 2: HÓA ĐƠN CHỜ THANH TOÁN-->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0"><i class="bi bi-hourglass-split me-2"></i>Hóa đơn chờ thanh toán</h3>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a href="index.php?page=ke_toan&view=thong_ke" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-bar-chart-line-fill me-1"></i>Thống kê doanh thu
                    </a>
                    <a href="index.php?page=ke_toan&view=lich_su&tab=khach" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-people me-1"></i>Thống kê sản lượng khách
                    </a>
                    <a href="index.php?page=ke_toan&view=lich_su&tab=hoa_don" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-clock-history me-1"></i>Lịch sử hóa đơn
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Mã HĐ</th><th>Chuyến bay</th><th>Tổng tiền</th><th>Hạn chót</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($danh_sach_hoa_don_cho)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Không có hóa đơn nào chờ thanh toán.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($danh_sach_hoa_don_cho as $hd): ?>
                        <tr>
                            <td class="fw-bold text-danger">#<?= (int) $hd['id'] ?></td>
                            <td><?= htmlspecialchars($hd['so_hieu']) ?></td>
                            <td class="text-danger fw-bold"><?= number_format($hd['tong_tien']) ?> VNĐ</td>
                            <td><?= date('d/m/Y', strtotime($hd['han_thanh_toan'])) ?></td>
                            <td class="text-end">
                                <a href="views/pages/ke_toan/chi_tiet_hoa_don.php?id=<?= (int) $hd['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> Xem</a>
                                <a href="index.php?page=ke_toan&thanh_toan_id=<?= (int) $hd['id'] ?>" class="btn btn-sm btn-outline-success">Xác nhận thu tiền</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--PHẦN 3: CHUYẾN BAY ĐÃ HOÀN THÀNH-->
<div class="row mt-3 mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="bi bi-airplane me-2"></i>Chuyến bay đã hoàn thành (Chờ lập hóa đơn)</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap align-middle">
                    <thead>
                        <tr><th>Chuyến bay</th><th>Hãng / MTOW</th><th>Số khách</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($danh_sach_chuyen_bay)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Không có chuyến bay nào cần lập hóa đơn.</td></tr>
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
                            <td class="text-end">
                                <a href="views/pages/chuyen_bay/chi_tiet.php?id=<?= (int) $cb['id'] ?>" class="btn btn-sm btn-outline-info me-1"><i class="bi bi-eye"></i> Chi tiết</a>
                                <form method="post" action="index.php?page=ke_toan" class="d-inline">
                                    <input type="hidden" name="tao_hoa_don" value="<?= (int) $cb['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Tính và lập hóa đơn</button>
                                </form>
                            </td>
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
