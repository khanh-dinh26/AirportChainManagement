<?php
require_once __DIR__ . '/_bootstrap.php';

/* Xóa chỉ chạy khi form xác nhận được gửi (POST) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'xoa_hang_hang_khong') {
    $baoTriController->xoaHangHangKhong();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'them_hang_hang_khong') {
    $baoTriController->themHangHangKhong();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cap_nhat_hang_hang_khong') {
    $baoTriController->capNhatHangHangKhong();
}

$hang_list = $baoTriController->getHangHangKhongList();
$tongHangGoc = count($hang_list);

// Tự mở lại modal khi vừa có lỗi nhập liệu
$modalHangError = null;
if (isset($_GET['open']) && $_GET['open'] === 'create_hang' && !empty($_SESSION['flash_error'])) {
    $modalHangError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

$editModalError = null;
if (isset($_GET['sua']) && !empty($_SESSION['flash_error'])) {
    $editModalError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

$pageTitle    = 'Quản lý Hãng hàng không';
$pageHeader   = 'Danh mục Hãng hàng không';
$navActive    = 'ha_tang';
$navSubActive = 'airlines';
$breadcrumb   = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Quản lý hạ tầng', 'url' => BASE_URL . '/index.php?page=ha_tang'],
    ['label' => 'Hãng hàng không', 'url' => null],
];

/* ------------------------------------------------------------
 * Lọc danh sách theo từ khóa: ?q=...
 * ------------------------------------------------------------ */
$tuKhoa = trim((string) ($_GET['q'] ?? ''));

if ($tuKhoa !== '') {
    $hang_list = array_values(array_filter($hang_list, static function (array $h) use ($tuKhoa): bool {
        $haystack = ($h['ma_iata'] ?? '') . ' ' . ($h['ten_hang'] ?? '') . ' ' . ($h['quoc_gia'] ?? '');
        return mb_stripos($haystack, $tuKhoa, 0, 'UTF-8') !== false;
    }));
}

$tongKetQua = count($hang_list);
$soHangMoiTrang = 10;
$tongTrang = max(1, (int) ceil($tongKetQua / $soHangMoiTrang));
$trangHienTai = max(1, (int) ($_GET['trang'] ?? 1));
$trangHienTai = min($trangHienTai, $tongTrang);
$viTriBatDau = ($trangHienTai - 1) * $soHangMoiTrang;
$hangTrenTrang = array_slice($hang_list, $viTriBatDau, $soHangMoiTrang);

$filterQuery = array_filter([
    'q'     => $tuKhoa,
    'trang' => $trangHienTai > 1 ? $trangHienTai : '',
], static fn($v) => $v !== '');

$urlDongModal = baoTriUrl('hang_hang_khong.php', $filterQuery);

/* ------------------------------------------------------------
 * Xác nhận xóa & Sửa:
 * ------------------------------------------------------------ */
$idCanXoa = (int) ($_GET['xac_nhan_xoa'] ?? 0);
$hangCanXoa = null;
if ($idCanXoa > 0) {
    foreach ($baoTriController->getHangHangKhongList() as $hItem) {
        if ((int) $hItem['id'] === $idCanXoa) {
            $hangCanXoa = $hItem;
            break;
        }
    }
}

$idCanSua = (int) ($_GET['sua'] ?? 0);
$hangCanSua = null;
if ($idCanSua > 0) {
    foreach ($baoTriController->getHangHangKhongList() as $hItem) {
        if ((int) $hItem['id'] === $idCanSua) {
            $hangCanSua = $hItem;
            break;
        }
    }
}

ob_start();
?>
<?php require __DIR__ . '/_flash.php'; ?>
<div class="maint-page maint-page--aircraft">
<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="card-title mb-0 fs-5 fw-bold text-primary">
                <i class="bi bi-buildings me-2"></i>Danh sách hãng hàng không
                <span class="badge text-bg-primary ms-1" title="Tổng số hãng hàng không"><?= $tongHangGoc ?></span>
            </h3>
            <div class="card-tools d-flex flex-wrap align-items-center gap-2">
                <a href="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-airplane me-1"></i>Xem loại tàu bay
                </a>
                <a href="#themHangModal" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Thêm hãng hàng không
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form method="get" action="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 mb-3">
            <div class="col-12 col-md-6 col-lg-4">
                <label for="airlineSearch" class="visually-hidden">Tìm kiếm hãng hàng không</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" id="airlineSearch" name="q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Tìm theo tên hãng, mã IATA, quốc gia...">
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Tìm kiếm
                </button>
            </div>
            <?php if ($tuKhoa !== ''): ?>
            <div class="col-6 col-md-3 col-lg-2 d-grid">
                <a href="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Xóa tìm kiếm
                </a>
            </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0 border">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">STT</th>
                        <th style="width: 120px;">Mã IATA</th>
                        <th>Tên hãng hàng không</th>
                        <th style="width: 200px;">Quốc gia</th>
                        <th class="text-center" style="width: 160px;">Số loại tàu bay</th>
                        <th class="text-end pe-3" style="width: 220px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hangTrenTrang)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                <?= $tuKhoa !== '' ? 'Không tìm thấy hãng hàng không nào phù hợp với từ khóa.' : 'Chưa có hãng hàng không nào trong hệ thống.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hangTrenTrang as $idx => $hangItem): ?>
                            <?php 
                            $idHang = (int) $hangItem['id'];
                            $soTb = (int) ($hangItem['so_tau_bay'] ?? 0); 
                            $maIata = htmlspecialchars((string) ($hangItem['ma_iata'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $tenHang = htmlspecialchars((string) ($hangItem['ten_hang'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $quocGia = htmlspecialchars((string) ($hangItem['quoc_gia'] ?? 'Viet Nam'), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td class="text-center text-secondary font-monospace"><?= $viTriBatDau + $idx + 1 ?></td>
                                <td>
                                    <span class="badge text-bg-primary fs-6 px-3 py-1 font-monospace fw-bold shadow-sm">
                                        <?= $maIata ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= $tenHang ?></div>
                                </td>
                                <td>
                                    <span class="text-secondary">
                                        <i class="bi bi-globe-americas me-1 text-primary"></i><?= $quocGia ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $soTb > 0 ? 'text-bg-success' : 'text-bg-secondary' ?> px-2 py-1">
                                        <?= $soTb ?> loại tàu bay
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= htmlspecialchars(baoTriUrl('tau_bay.php', ['q' => $hangItem['ma_iata']]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" title="Xem các loại máy bay thuộc hãng này">
                                            <i class="bi bi-airplane me-1"></i>Máy bay
                                        </a>
                                        <a href="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php', $filterQuery + ['sua' => $idHang]), ENT_QUOTES, 'UTF-8') ?>#suaHangModal" class="btn btn-outline-secondary" title="Sửa thông tin hãng">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($soTb === 0): ?>
                                            <a href="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php', $filterQuery + ['xac_nhan_xoa' => $idHang]), ENT_QUOTES, 'UTF-8') ?>#xoaHangModal" class="btn btn-outline-danger" title="Xóa hãng hàng không">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled title="Không thể xóa hãng đang có loại tàu bay">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($tongTrang > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="small text-secondary">
                Hiển thị <?= $viTriBatDau + 1 ?>–<?= min($viTriBatDau + $soHangMoiTrang, $tongKetQua) ?> / <?= $tongKetQua ?> hãng
            </div>
            <nav aria-label="Phân trang hãng hàng không">
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $tongTrang; $p++): ?>
                        <li class="page-item <?= $p === $trangHienTai ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php', array_filter(['q' => $tuKhoa, 'trang' => $p > 1 ? $p : ''])), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!--begin::Modal thêm hãng hàng không-->
<div class="modal fade maint-modal maint-modal--aircraft<?= $modalHangError !== null ? ' is-open' : '' ?>" id="themHangModal" tabindex="-1" aria-labelledby="themHangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="action" value="them_hang_hang_khong">
                <input type="hidden" name="return_page" value="hang_hang_khong.php">
                <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="giu_trang" value="<?= $trangHienTai ?>">
                <div class="modal-header ps-3 pe-5 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="modal-icon text-primary">
                            <i class="bi bi-building-add"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0 fs-5 text-primary fw-bold" id="themHangModalLabel">Thêm hãng hàng không</h5>
                            <div class="small text-secondary">Khai báo hãng hàng không mới vào hệ thống</div>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="acv-close" aria-label="Đóng">&times;</a>
                </div>

                <div class="modal-body px-3 py-3">
                    <?php if ($modalHangError !== null): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle-fill mt-1"></i>
                            <div class="small"><?= htmlspecialchars($modalHangError, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="them_ma_iata_hang" class="form-label fw-semibold">
                            Mã IATA (2 ký tự) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-tag-fill"></i></span>
                            <input type="text" name="ma_iata" id="them_ma_iata_hang" class="form-control text-uppercase"
                                   placeholder="Ví dụ: VU, BL, EK, SQ..." maxlength="2" pattern="[A-Za-z0-9]{2}" required
                                   oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="form-text text-secondary small">Mã định danh 2 ký tự chuẩn quốc tế IATA (ví dụ: VN, VJ, QH, VU...).</div>
                    </div>

                    <div class="mb-3">
                        <label for="them_ten_hang_hang_khong" class="form-label fw-semibold">
                            Tên hãng hàng không <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-building"></i></span>
                            <input type="text" name="ten_hang" id="them_ten_hang_hang_khong" class="form-control"
                                   placeholder="Ví dụ: Vietravel Airlines, Pacific Airlines..." maxlength="100" required>
                        </div>
                        <div class="form-text text-secondary small">Tên thương mại đầy đủ của hãng hàng không.</div>
                    </div>

                    <div class="mb-2">
                        <label for="them_quoc_gia_hang" class="form-label fw-semibold">
                            Quốc gia
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-globe-americas"></i></span>
                            <input type="text" name="quoc_gia" id="them_quoc_gia_hang" class="form-control"
                                   placeholder="Ví dụ: Viet Nam, Singapore, UAE..." value="Viet Nam" maxlength="50">
                        </div>
                        <div class="form-text text-secondary small">Quốc gia đặt trụ sở chính (mặc định: Viet Nam).</div>
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between gap-2 px-3 py-2 bg-light">
                    <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary px-3">
                        Hủy
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-check2 me-1"></i> Lưu hãng hàng không
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal thêm hãng hàng không-->

<?php if ($hangCanSua !== null): ?>
<!--begin::Modal sửa hãng hàng không-->
<div class="modal fade maint-modal maint-modal--aircraft is-open" id="suaHangModal" tabindex="-1" aria-labelledby="suaHangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="action" value="cap_nhat_hang_hang_khong">
                <input type="hidden" name="id" value="<?= (int) $hangCanSua['id'] ?>">
                <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="giu_trang" value="<?= $trangHienTai ?>">
                <div class="modal-header ps-3 pe-5 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="modal-icon text-primary">
                            <i class="bi bi-pencil-square"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0 fs-5 text-primary fw-bold" id="suaHangModalLabel">Sửa hãng hàng không</h5>
                            <div class="small text-secondary">Cập nhật thông tin hãng <?= htmlspecialchars($hangCanSua['ten_hang'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="acv-close" aria-label="Đóng">&times;</a>
                </div>

                <div class="modal-body px-3 py-3">
                    <?php if ($editModalError !== null): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle-fill mt-1"></i>
                            <div class="small"><?= htmlspecialchars($editModalError, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="sua_ma_iata_hang" class="form-label fw-semibold">
                            Mã IATA (2 ký tự) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-tag-fill"></i></span>
                            <input type="text" name="ma_iata" id="sua_ma_iata_hang" class="form-control text-uppercase"
                                   value="<?= htmlspecialchars($hangCanSua['ma_iata'], ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="2" pattern="[A-Za-z0-9]{2}" required
                                   oninput="this.value = this.value.toUpperCase()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sua_ten_hang_hang_khong" class="form-label fw-semibold">
                            Tên hãng hàng không <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-building"></i></span>
                            <input type="text" name="ten_hang" id="sua_ten_hang_hang_khong" class="form-control"
                                   value="<?= htmlspecialchars($hangCanSua['ten_hang'], ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="100" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="sua_quoc_gia_hang" class="form-label fw-semibold">
                            Quốc gia
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-globe-americas"></i></span>
                            <input type="text" name="quoc_gia" id="sua_quoc_gia_hang" class="form-control"
                                   value="<?= htmlspecialchars($hangCanSua['quoc_gia'] ?? 'Viet Nam', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="50">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between gap-2 px-3 py-2 bg-light">
                    <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary px-3">
                        Hủy
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-check2 me-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal sửa hãng hàng không-->
<?php endif; ?>

<?php if ($hangCanXoa !== null): ?>
<!--begin::Modal xác nhận xóa-->
<div class="modal fade maint-modal is-open" id="xoaHangModal" tabindex="-1" aria-labelledby="xoaHangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg text-center p-3">
            <div class="modal-body pt-3 pb-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger mb-3" style="width: 58px; height: 58px;">
                    <i class="bi bi-trash3 fs-4"></i>
                </span>
                <h5 class="mb-2 fw-bold" id="xoaHangModalLabel">Xóa hãng hàng không?</h5>
                <p class="text-secondary small mb-0">
                    Bạn có chắc muốn xóa hãng <strong><?= htmlspecialchars($hangCanXoa['ten_hang'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($hangCanXoa['ma_iata'], ENT_QUOTES, 'UTF-8') ?>)?
                    Hành động này không thể hoàn tác.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-2 pb-2">
                <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border px-3">Hủy</a>
                <form method="post" action="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                    <input type="hidden" name="action" value="xoa_hang_hang_khong" />
                    <input type="hidden" name="id" value="<?= (int) $hangCanXoa['id'] ?>" />
                    <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="giu_trang" value="<?= $trangHienTai ?>" />
                    <button type="submit" class="btn btn-danger px-3">
                        <i class="bi bi-trash3 me-1"></i>Xóa hãng
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal xác nhận xóa-->
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
