<?php

//1. Kết nối cơ sở dữ liệu
require_once __DIR__ . '/../../../config/database.php'; 
require_once __DIR__ . '/../../../controllers/KeToanController.php';
$keToan = new KeToanController($conn);

//2. Xử lý các hành động được gửi lên (PHP thuần: flash + redirect, không JS)
$keToanUrl = 'index.php?page=ke_toan';

// Khởi tạo CSRF Token cho form sửa đơn giá
if (empty($_SESSION['ke_toan_csrf'])) {
    $_SESSION['ke_toan_csrf'] = bin2hex(random_bytes(32));
}

// Xử lý cập nhật đơn giá (Sửa)
if (isset($_POST['btn_sua_gia'])) {
    $id    = (int) ($_POST['id'] ?? 0);
    $gia   = (float) ($_POST['don_gia'] ?? 0);
    $donVi = trim((string) ($_POST['don_vi_tinh'] ?? ''));
    $token = (string) ($_POST['csrf'] ?? '');

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
    } else {
        $_SESSION['flash_error'] = 'Không cập nhật được đơn giá. Vui lòng thử lại.';
    }
    header('Location: ' . $keToanUrl);
    exit();
}

// Xử lý phát hành hóa đơn tự động
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

// Xử lý xác nhận thanh toán
if (isset($_GET['thanh_toan_id'])) {
    $keToan->XacNhanThanhToan((int) $_GET['thanh_toan_id']);
    $_SESSION['flash_success'] = 'Đã xác nhận thanh toán thành công!';
    header('Location: ' . $keToanUrl);
    exit();
}

// Lấy flash message (nếu có) rồi xóa để không hiện lại khi tải lại trang
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// 3. Lấy dữ liệu để hiển thị trên trang
$danh_sach_gia = $keToan->DanhSachDonGia();
$danh_sach_chuyen_bay = $keToan->ChuyenBayHoanThanh();
$data_thang = $keToan->DoanhThuTheoThang();
$data_hang = $keToan->DoanhThuTheoHang();
$stats = $keToan->ThongKeNhanh();
$danh_sach_hoa_don_cho = $keToan->DanhSachHoaDonCho();
$danh_sach_san_bay = $keToan->DanhSachSanBay();
$san_bay_chon = isset($_GET['san_bay']) ? (int)$_GET['san_bay'] : 0;
$tong_khach_san_bay = 0;
if ($san_bay_chon > 0) {
    $tong_khach_san_bay = $keToan->ThongKeHanhKhachSanBay($san_bay_chon);
}

// Lấy thông tin khoản phí đang sửa (nếu có)
$giaDangSua = null;
if (isset($_GET['sua'])) {
    $giaDangSua = $keToan->LayDonGia((int) $_GET['sua']);
}

// Nhãn tiếng Việt cho 3 khoản phí
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
    <!--Doanh thu-->
    <div class="col">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= number_format($stats['doanh_thu_thang'] / 1000000, 1) ?> Tr</h3>
                <p>Doanh thu tháng</p>
            </div>
            <i class="small-box-icon bi bi-graph-up-arrow"></i>
        </div>
    </div>
    
    <!--Hóa đơn đã thu-->
    <div class="col">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= $stats['hd_da_thu'] ?></h3>
                <p>Hóa đơn đã thu</p>
            </div>
            <i class="small-box-icon bi bi-receipt"></i>
        </div>
    </div>
    
    <!--Hóa đơn chờ thu-->
    <div class="col">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= $stats['hd_chua_thu'] ?></h3>
                <p>Hóa đơn chờ thu</p>
            </div>
            <i class="small-box-icon bi bi-hourglass-split"></i>
        </div>
    </div>
    
    <!--Công nợ quá hạn-->
    <div class="col">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3><?= number_format($stats['no_qua_han'] / 1000000, 1) ?> Tr</h3>
                <p>Công nợ quá hạn</p>
            </div>
            <i class="small-box-icon bi bi-exclamation-triangle"></i>
        </div>
    </div>

    <!--Số chuyến bay trong ngày-->
    <div class="col">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= $stats['chuyen_bay_trong_ngay'] ?></h3>
                <p>Chuyến bay trong ngày</p>
            </div>
            <i class="small-box-icon bi bi-airplane"></i>
        </div>
    </div>
</div>

<div class="row">
    <!--PHẦN 2: QUẢN LÝ BIỂU PHÍ-->
    <div class="col-md-5">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Biểu phí dịch vụ</h3>
            </div>
            <div class="card-body">
                <?php if ($giaDangSua): ?>
                    <form method="post" action="index.php?page=ke_toan">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['ke_toan_csrf'], ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= (int) $giaDangSua['id'] ?>" />
                        <div class="mb-2">
                            <label class="form-label small mb-1 fw-bold">Khoản phí</label>
                            <input type="text" class="form-control form-control-sm" disabled
                                   value="<?= htmlspecialchars($nhanPhi[$giaDangSua['loai_dich_vu']] ?? $giaDangSua['loai_dich_vu'], ENT_QUOTES, 'UTF-8') ?>" />
                            <div class="form-text">Mã kỹ thuật: <?= htmlspecialchars($giaDangSua['loai_dich_vu'], ENT_QUOTES, 'UTF-8') ?> (không đổi)</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1 fw-bold">Đơn giá (VNĐ)</label>
                            <input type="number" min="0.01" step="0.01" name="don_gia"
                                   class="form-control form-control-sm" required
                                   value="<?= htmlspecialchars((string) $giaDangSua['don_gia'], ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1 fw-bold">Đơn vị tính</label>
                            <input type="text" maxlength="50" name="don_vi_tinh"
                                   class="form-control form-control-sm" required
                                   value="<?= htmlspecialchars($giaDangSua['don_vi_tinh'], ENT_QUOTES, 'UTF-8') ?>" />
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" name="btn_sua_gia" class="btn btn-sm btn-primary flex-fill">Lưu thay đổi</button>
                            <a href="index.php?page=ke_toan" class="btn btn-sm btn-light border">Hủy</a>
                        </div>
                    </form>
                <?php elseif (isset($_GET['sua'])): ?>
                    <div class="alert alert-warning mb-3">Không tìm thấy khoản phí cần sửa.</div>
                <?php else: ?>
                    <p class="text-muted small mb-3">Bấm <strong>Sửa</strong> ở bảng bên dưới để cập nhật đơn giá.</p>
                <?php endif; ?>
                <hr>
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tên phí</th>
                            <th>Đơn giá</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danh_sach_gia as $gia): ?>
                        <tr>
                            <td><?= htmlspecialchars($nhanPhi[$gia['loai_dich_vu']] ?? $gia['loai_dich_vu'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= number_format($gia['don_gia']) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($gia['don_vi_tinh'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td class="text-end">
                                <a href="index.php?page=ke_toan&sua=<?= (int) $gia['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">Sửa</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!--PHẦN 3: DANH SÁCH CHUYẾN BAY-->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Chuyến bay đã hoàn thành (Chờ lập hóa đơn)</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap align-middle">
                    <thead>
                        <tr>
                            <th>Mã chuyến bay</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($danh_sach_chuyen_bay)): ?>
                            <tr><td colspan="3" class="text-center">Không có chuyến bay nào cần lập hóa đơn.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach ($danh_sach_chuyen_bay as $cb): ?>
                        <tr>
                            <td>
                                <strong>Số hiệu: <?= htmlspecialchars($cb['so_hieu']) ?></strong> 
                                <br><small class="text-muted">ID: #<?= htmlspecialchars($cb['id']) ?></small>
                            </td>
                            <td><span class="badge text-bg-success"><?= htmlspecialchars($cb['trang_thai_tong']) ?></span></td>
                            <td class="text-end">
                                <a href="index.php?page=ke_toan&tao_hoa_don=<?= $cb['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                   Tính và lập hóa đơn
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--Hóa đơn chờ thanh toán-->
<div class="row mt-4">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-cash-coin me-2"></i>Hóa đơn chờ thanh toán</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Mã HĐ</th>
                            <th>Chuyến bay</th>
                            <th>Tổng tiền</th>
                            <th>Hạn chót</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
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
                                <!-- Nút Xem chi tiết -->
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalChiTiet"
                                        data-id="<?= $hd['id'] ?>">
                                    <i class="bi bi-eye"></i> Xem
                                </button>
                                <a href="index.php?page=ke_toan&thanh_toan_id=<?= $hd['id'] ?>" 
                                   class="btn btn-sm btn-outline-success">
                                   Xác nhận thu tiền
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--PHẦN 4: BÁO CÁO TÀI CHÍNH-->
<div class="row mt-4">
    <!--BẢNG 1: DOANH THU THEO THÁNG-->
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-graph-up me-2"></i>Doanh thu theo tháng</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tháng</th>
                            <th class="text-end">Tổng Doanh Thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($data_thang)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Chưa có dữ liệu doanh thu</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach ($data_thang as $dt): ?>
                        <tr>
                            <td>Tháng <?= $dt['thang'] ?></td>
                            <td class="text-end fw-bold text-success">
                                <?= number_format($dt['tong_doanh_thu']) ?> VNĐ
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!--BẢNG 2: DOANH THU THEO HÃNG HÀNG KHÔNG-->
    <div class="col-md-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-airplane me-2"></i>Doanh thu theo Hãng Hàng Không</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Hãng Hàng Không</th>
                            <th class="text-end">Doanh Thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($data_hang)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Chưa có dữ liệu đối tác</td></tr>
                        <?php endif; ?>

                        <?php foreach ($data_hang as $hh): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($hh['ten_hang']) ?></strong></td>
                            <td class="text-end fw-bold text-primary">
                                <?= number_format($hh['doanh_thu']) ?> VNĐ
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--PHẦN 5: THỐNG KÊ HÀNH KHÁCH THEO SÂN BAY-->
<div class="row mt-3 mb-5">
    <div class="col-12">
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-people me-2"></i>Thống kê sản lượng hành khách theo Sân bay</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <input type="hidden" name="page" value="ke_toan">
                    <div class="col-auto">
                        <label class="col-form-label fw-bold">Chọn sân bay:</label>
                    </div>
                    <div class="col-md-4">
                        <select name="san_bay" class="form-select" required>
                            <option value="">-- Chọn sân bay để thống kê --</option>
                            <?php foreach ($danh_sach_san_bay as $sb): ?>
                                <option value="<?= $sb['id'] ?>" <?= ($san_bay_chon == $sb['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sb['ten_san_bay']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Thống kê</button>
                    </div>
                </form>

                <?php if ($san_bay_chon > 0): ?>
                    <div class="alert alert-info mt-4 text-center">
                        <h4 class="mb-0">Tổng sản lượng hành khách (Đi + Đến): <strong>
                            <?= number_format($tong_khach_san_bay) ?></strong> lượt khách</h4>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!--Model chi tiết hóa đơn-->
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
                        <tr>
                            <th style="width: 30%;">Mã hóa đơn:</th>
                            <td class="fw-bold text-danger" id="ct-id">#</td>
                        </tr>
                        <tr>
                            <th>Chuyến bay:</th>
                            <td class="fw-bold" id="ct-chuyen-bay"></td>
                        </tr>
                        <tr>
                            <th>Hãng hàng không:</th>
                            <td id="ct-hang-bay"></td>
                        </tr>
                        <tr>
                            <th>Ngày lập:</th>
                            <td id="ct-ngay-lap"></td>
                        </tr>
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
                                <td>Phí cất/hạ cánh</td>
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
                        document.getElementById('ct-chuyen-bay').innerText = d.so_hieu;
                        document.getElementById('ct-hang-bay').innerText = d.ten_hang;
                        document.getElementById('ct-ngay-lap').innerText = new Date(d.ngay_xuat).toLocaleString('vi-VN');
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
?>