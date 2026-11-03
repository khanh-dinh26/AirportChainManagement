<?php
require_once __DIR__ . '/_bootstrap.php';

/* Xóa chỉ chạy khi form xác nhận được gửi (POST), không xóa bằng link GET */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'xoa_tau_bay') {
    $baoTriController->xoaTauBay();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'them_tau_bay') {
    $baoTriController->themTauBay();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cap_nhat_tau_bay') {
    $baoTriController->capNhatTauBay();
}

if (!isset($tau_bay)) {
    $tau_bay = $baoTriController->getDanhSachTauBay();
}

if (!isset($san_bay_list)) {
    $san_bay_list = $baoTriController->getSanBayList();
}

if (!isset($hang_list)) {
    $hang_list = $baoTriController->getHangHangKhongList();
}

$hangLabels = [];
foreach ($hang_list as $hang) {
    $tenHang  = trim((string) ($hang['ten_hang'] ?? ''));
    $hangLabels[(int) $hang['id']] = $tenHang;
}

// Chỉ hiển thị tên sân bay; mã IATA vẫn có thể dùng trong tìm kiếm.
$sanBayLabels = [];
foreach ($san_bay_list as $sb) {
    $tenDayDu = trim((string) ($sb['ten_san_bay'] ?? ''));
    $sanBayLabels[(int) $sb['id']] = $tenDayDu;
}

// Tự mở lại modal khi vừa có lỗi nhập liệu
$modalError = null;
if (isset($_GET['open']) && $_GET['open'] === 'create' && !empty($_SESSION['flash_error'])) {
    $modalError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
$editModalError = null;
if (isset($_GET['sua']) && !empty($_SESSION['flash_error'])) {
    $editModalError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

$pageTitle  = 'Quản lý Loại tàu bay';
$pageHeader = 'Danh mục Loại tàu bay (MTOW)';
$navActive    = 'bao_tri';
$navSubActive = 'aircraft_types';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Bảo trì', 'url' => baoTriUrl('index.php')],
    ['label' => 'Loại tàu bay', 'url' => null],
];

$tongLoaiTauBay = count($tau_bay);
$sucChuaLonNhat = 0;
$tongMtow       = 0.0;
$mtowLonNhat    = 0.0;

foreach ($tau_bay as $tb) {
    $sucChua = (int) $tb['suc_chua_hanh_khach'];
    $mtow    = (float) $tb['tai_trong_cat_canh_mtow'];

    if ($sucChua > $sucChuaLonNhat) {
        $sucChuaLonNhat = $sucChua;
    }
    if ($mtow > $mtowLonNhat) {
        $mtowLonNhat = $mtow;
    }

    $tongMtow += $mtow;
}

$mtowTrungBinh = $tongLoaiTauBay > 0 ? $tongMtow / $tongLoaiTauBay : 0.0;

/* ------------------------------------------------------------
 * Lọc danh sách phía máy chủ: ?q=...&san_bay=<id>.
 * ------------------------------------------------------------ */
$tuKhoa            = trim((string) ($_GET['q'] ?? ''));
$locSanBay         = (int) ($_GET['san_bay'] ?? 0);
$tongLoaiTauBayGoc = $tongLoaiTauBay;

if ($locSanBay > 0 && !isset($sanBayLabels[$locSanBay])) {
    $locSanBay = 0;
}

if ($tuKhoa !== '' || $locSanBay > 0) {
    $tau_bay = array_values(array_filter($tau_bay, static function (array $tb) use ($tuKhoa, $locSanBay): bool {
        if ($locSanBay > 0 && (int) ($tb['id_san_bay'] ?? 0) !== $locSanBay) {
            return false;
        }

        if ($tuKhoa === '') {
            return true;
        }

        $maDayDu  = 'LTB-' . sprintf('%03d', (int) $tb['id']);
        $haystack = $tb['ma_loai'] . ' ' . ($tb['hang'] ?? '') . ' ' . ($tb['ma_hang'] ?? '') . ' ' . $maDayDu . ' ' . ($tb['ma_iata'] ?? '') . ' ' . ($tb['ten_san_bay'] ?? '');

        return mb_stripos($haystack, $tuKhoa, 0, 'UTF-8') !== false;
    }));
}

$soLuongHienThi = count($tau_bay);

$filterQuery = array_filter([
    'q'       => $tuKhoa,
    'san_bay' => $locSanBay > 0 ? $locSanBay : '',
], static fn($v) => $v !== '');

$urlDongModal = baoTriUrl('tau_bay.php', $filterQuery);

/* ------------------------------------------------------------
 * Xác nhận xóa (thuần PHP + CSS, không dùng confirm() của JS):
 * link Xóa trỏ tới ?xac_nhan_xoa=<id>; trang hiện hộp thoại,
 * nút "Xóa" trong hộp thoại mới gửi form POST thật.
 * ------------------------------------------------------------ */
$idCanXoa     = (int) ($_GET['xac_nhan_xoa'] ?? 0);
$tauBayCanXoa = null;
if ($idCanXoa > 0) {
    foreach ($baoTriController->getDanhSachTauBay() as $tbItem) {
        if ((int) $tbItem['id'] === $idCanXoa) {
            $tauBayCanXoa = $tbItem;
            break;
        }
    }
}

$idCanSua     = (int) ($_GET['sua'] ?? 0);
$tauBayCanSua = null;
if ($idCanSua > 0) {
    foreach ($baoTriController->getDanhSachTauBay() as $tbItem) {
        if ((int) $tbItem['id'] === $idCanSua) {
            $tauBayCanSua = $tbItem;
            break;
        }
    }
}

ob_start();
?>
<?php require __DIR__ . '/_flash.php'; ?>
<div class="maint-page maint-page--aircraft">
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= $tongLoaiTauBay ?></h3>
                <p>Tổng loại tàu bay</p>
            </div>
            <i class="small-box-icon bi bi-airplane"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= number_format($sucChuaLonNhat) ?></h3>
                <p>Sức chứa lớn nhất (khách)</p>
            </div>
            <i class="small-box-icon bi bi-people"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= number_format($mtowTrungBinh, 2) ?></h3>
                <p>MTOW trung bình (tấn)</p>
            </div>
            <i class="small-box-icon bi bi-speedometer2"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= number_format($mtowLonNhat, 2) ?></h3>
                <p>MTOW lớn nhất (tấn)</p>
            </div>
            <i class="small-box-icon bi bi-arrow-up-circle"></i>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Danh sách loại tàu bay
            <span class="badge text-bg-secondary ms-1" title="Tổng số loại tàu bay"><?= $tongLoaiTauBayGoc ?></span>
        </h3>
        <div class="card-tools d-flex flex-wrap align-items-center gap-1">
            <a href="#themTauBayModal" class="btn btn-sm btn-success">
                <i class="bi bi-plus-lg me-1"></i>Thêm loại tàu bay
            </a>
        </div>
    </div>

    <?php if ($tongLoaiTauBayGoc > 0): ?>
        <div class="card-body pb-0">
            <form method="get" action="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 mb-3">
                <div class="col-12 col-lg-4">
                    <label for="aircraftSearch" class="visually-hidden">Tìm kiếm loại tàu bay</label>
                    <input type="search" id="aircraftSearch" name="q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Tên dòng máy bay, hãng hoặc mã LTB...">
                </div>
                <div class="col-12 col-lg-4">
                    <label for="aircraftAirportFilter" class="visually-hidden">Sân bay</label>
                    <select id="aircraftAirportFilter" name="san_bay" class="form-select">
                        <option value=""<?= $locSanBay === 0 ? ' selected' : '' ?>>Tất cả sân bay</option>
                        <?php foreach ($sanBayLabels as $idSb => $nhanSb): ?>
                            <option value="<?= (int) $idSb ?>"<?= $locSanBay === (int) $idSb ? ' selected' : '' ?>><?= htmlspecialchars($nhanSb, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i>Tìm kiếm
                    </button>
                </div>
                <div class="col-6 col-lg-2 d-grid">
                    <a href="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Xóa lọc</a>
                </div>
            </form>
            <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary mb-2">
                <span>
                    Hiển thị <strong><?= $soLuongHienThi ?></strong> / <strong><?= $tongLoaiTauBayGoc ?></strong> loại tàu bay
                    <?php if ($locSanBay > 0 && isset($sanBayLabels[$locSanBay])): ?>
                        · <span class="badge text-bg-light border"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($sanBayLabels[$locSanBay], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="aircraftTable">
            <thead>
                <tr>
                    <th>Hình ảnh</th>
                    <th>Loại tàu bay</th>
                    <th>Hãng hàng không</th>
                    <th>Sân bay</th>
                    <th>MTOW</th>
                    <th>Sức chứa</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tau_bay)): ?>
                    <?php foreach ($tau_bay as $tb): ?>
                        <?php
                        $maLoaiTauBay = 'LTB-' . sprintf('%03d', (int) $tb['id']);
                        $mtow         = (float) $tb['tai_trong_cat_canh_mtow'];
                        $sucChua      = (int) $tb['suc_chua_hanh_khach'];
                        $anhUrl       = baoTriAircraftImageUrl($tb['hinh_anh'] ?? null);
                        $tenSanBay    = trim((string) ($tb['ten_san_bay'] ?? ''));
                        ?>
                        <tr>
                            <td style="width: 112px;">
                                <?php if ($anhUrl !== ''): ?>
                                    <a href="#anh-tau-bay-<?= (int) $tb['id'] ?>" class="d-inline-block" title="Xem ảnh <?= htmlspecialchars($tb['ma_loai'], ENT_QUOTES, 'UTF-8') ?>">
                                        <img src="<?= htmlspecialchars($anhUrl, ENT_QUOTES, 'UTF-8') ?>"
                                             alt="Ảnh loại tàu bay <?= htmlspecialchars($tb['ma_loai'], ENT_QUOTES, 'UTF-8') ?>"
                                             class="maint-aircraft-thumb maint-thumb" loading="lazy">
                                    </a>
                                <?php else: ?>
                                    <span class="maint-aircraft-thumb maint-aircraft-thumb--empty d-inline-flex align-items-center justify-content-center text-secondary">
                                        <i class="bi bi-image"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($tb['ma_loai'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="small text-secondary"><?= $maLoaiTauBay ?></div>
                            </td>
                            <td>
                                <?php
                                $hangTauBay = trim((string) ($tb['hang'] ?? ''));
                                ?>
                                <?php if ($hangTauBay !== ''): ?>
                                    <span class="badge text-bg-info"><?= htmlspecialchars($hangTauBay, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="text-secondary">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 180px; white-space: normal;">
                                <?php if ($tenSanBay !== ''): ?>
                                    <i class="bi bi-geo-alt text-secondary me-1" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($tenSanBay, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="text-secondary">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge text-bg-primary">
                                    <i class="bi bi-speedometer2 me-1"></i><?= number_format($mtow, 2) ?> tấn
                                </span>
                            </td>
                            <td>
                                <span class="badge text-bg-success">
                                    <i class="bi bi-people me-1"></i><?= number_format($sucChua) ?> khách
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php
                                $xoaUrl = baoTriUrl('tau_bay.php', array_filter([
                                    'q'            => $tuKhoa,
                                    'san_bay'      => $locSanBay > 0 ? $locSanBay : '',
                                    'xac_nhan_xoa' => (int) $tb['id'],
                                ], static fn($v) => $v !== ''));
                                $suaUrl = baoTriUrl('tau_bay.php', array_filter([
                                    'q'       => $tuKhoa,
                                    'san_bay' => $locSanBay > 0 ? $locSanBay : '',
                                    'sua'     => (int) $tb['id'],
                                ], static fn($v) => $v !== ''));
                                ?>
                                <a href="<?= htmlspecialchars($suaUrl, ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square me-1"></i>Sửa
                                </a>
                                <a href="<?= htmlspecialchars($xoaUrl, ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash3 me-1"></i>Xóa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php elseif ($tuKhoa !== '' || $locSanBay > 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-secondary">
                            Không tìm thấy loại tàu bay phù hợp với bộ lọc hiện tại.
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="maint-empty-icon"><i class="bi bi-airplane"></i></div>
                            <div class="fw-semibold">Chưa có loại tàu bay nào</div>
                            <div class="small text-secondary mb-3">Thêm loại tàu bay đầu tiên để bắt đầu quản lý.</div>
                            <a href="#themTauBayModal" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Thêm loại tàu bay</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php
// Lightbox cho ảnh loại tàu bay (mở bằng :target, đóng bằng nút X).
$lightboxImages = [];
foreach ($tau_bay as $tbAnh) {
    $urlAnh = baoTriAircraftImageUrl($tbAnh['hinh_anh'] ?? null);
    if ($urlAnh === '') {
        continue;
    }
    $lightboxImages[] = [
        'id'  => 'anh-tau-bay-' . (int) $tbAnh['id'],
        'url' => $urlAnh,
        'alt' => 'Ảnh loại tàu bay ' . $tbAnh['ma_loai'],
    ];
}
require __DIR__ . '/_lightbox.php';
?>

<!--begin::Modal thêm loại tàu bay-->
<div class="modal fade maint-modal maint-modal--aircraft<?= $modalError !== null ? ' is-open' : '' ?>" id="themTauBayModal" tabindex="-1" aria-labelledby="themTauBayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="them_tau_bay">
                <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="giu_san_bay" value="<?= (int) $locSanBay ?>">
                <div class="modal-header ps-3 pe-5 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="modal-icon">
                            <i class="bi bi-airplane"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0 fs-5" id="themTauBayModalLabel">Thêm loại tàu bay</h5>
                            <div class="small text-secondary">Khai báo dòng máy bay, hãng khai thác và thông số</div>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="acv-close" aria-label="Đóng">&times;</a>
                </div>

                <div class="modal-body px-3 py-3">
                    <?php if ($modalError !== null): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle-fill mt-1"></i>
                            <div class="small"><?= htmlspecialchars($modalError, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="maint-form-section"><i class="bi bi-airplane"></i> Thông số khai thác</div>

                    <div class="mb-2">
                        <label for="id_san_bay" class="form-label fw-semibold">
                            Sân bay khai thác <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt"></i></span>
                            <select name="id_san_bay" id="id_san_bay" class="form-select" required>
                                <option value="">Chọn sân bay</option>
                                <?php foreach ($sanBayLabels as $idSb => $nhanSb): ?>
                                    <option value="<?= (int) $idSb ?>"<?= $locSanBay === (int) $idSb ? ' selected' : '' ?>><?= htmlspecialchars($nhanSb, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="ma_loai" class="form-label fw-semibold">
                            Mã loại / tên dòng máy bay <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-airplane"></i></span>
                            <input type="text" name="ma_loai" id="ma_loai" class="form-control"
                                   maxlength="50" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="id_hang_hang_khong" class="form-label fw-semibold">
                            Hãng hàng không khai thác <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-building"></i></span>
                            <select name="id_hang_hang_khong" id="id_hang_hang_khong" class="form-select" required>
                                <option value="">Chọn hãng hàng không</option>
                                <?php foreach ($hangLabels as $idHang => $nhanHang): ?>
                                    <option value="<?= (int) $idHang ?>"><?= htmlspecialchars($nhanHang, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="tai_trong_cat_canh_mtow" class="form-label fw-semibold">
                                MTOW (tấn) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-speedometer2"></i></span>
                                <input type="number" step="0.01" min="0.01" name="tai_trong_cat_canh_mtow"
                                       id="tai_trong_cat_canh_mtow" class="form-control" placeholder="Tấn" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="suc_chua_hanh_khach" class="form-label fw-semibold">
                                Sức chứa (khách) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-people"></i></span>
                                <input type="number" min="1" name="suc_chua_hanh_khach"
                                       id="suc_chua_hanh_khach" class="form-control" placeholder="Số khách" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label for="anh_tai_len" class="form-label fw-semibold">Hình ảnh loại tàu bay</label>
                        <input type="file" name="anh_tai_len" id="anh_tai_len" class="form-control form-control-sm"
                               accept="image/png, image/jpeg, image/jpg, image/gif, image/webp">
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between gap-2 px-3 py-2">
                    <div class="d-flex gap-2 ms-auto">
                        <button type="submit" class="btn btn-sm btn-success px-3">
                            <i class="bi bi-check2 me-1"></i> Lưu loại tàu bay
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal thêm loại tàu bay-->

<?php if ($tauBayCanSua !== null): ?>
<?php $anhTauBayCanSua = baoTriAircraftImageUrl($tauBayCanSua['hinh_anh'] ?? null); ?>
<!--begin::Modal sửa loại tàu bay-->
<div class="modal fade maint-modal maint-modal--aircraft is-open" id="suaTauBayModal" tabindex="-1" aria-labelledby="suaTauBayModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="cap_nhat_tau_bay">
                <input type="hidden" name="id" value="<?= (int) $tauBayCanSua['id'] ?>">
                <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="giu_san_bay" value="<?= (int) $locSanBay ?>">
                <div class="modal-header ps-3 pe-5 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="modal-icon"><i class="bi bi-pencil-square"></i></span>
                        <div>
                            <h5 class="modal-title mb-0 fs-5" id="suaTauBayModalLabel">Sửa loại tàu bay</h5>
                            <div class="small text-secondary">Cập nhật thông tin dòng máy bay và thông số khai thác</div>
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

                    <div class="maint-form-section"><i class="bi bi-airplane"></i> Thông số khai thác</div>

                    <div class="mb-2">
                        <label for="sua_id_san_bay" class="form-label fw-semibold">
                            Sân bay khai thác <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt"></i></span>
                            <select name="id_san_bay" id="sua_id_san_bay" class="form-select" required>
                                <option value="">Chọn sân bay</option>
                                <?php foreach ($sanBayLabels as $idSb => $nhanSb): ?>
                                    <option value="<?= (int) $idSb ?>"<?= (int) $tauBayCanSua['id_san_bay'] === (int) $idSb ? ' selected' : '' ?>><?= htmlspecialchars($nhanSb, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="sua_ma_loai" class="form-label fw-semibold">
                            Mã loại / tên dòng máy bay <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-airplane"></i></span>
                            <input type="text" name="ma_loai" id="sua_ma_loai" class="form-control"
                                   maxlength="50" value="<?= htmlspecialchars((string) $tauBayCanSua['ma_loai'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="sua_id_hang_hang_khong" class="form-label fw-semibold">
                            Hãng hàng không khai thác <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-building"></i></span>
                            <select name="id_hang_hang_khong" id="sua_id_hang_hang_khong" class="form-select" required>
                                <option value="">Chọn hãng hàng không</option>
                                <?php foreach ($hangLabels as $idHang => $nhanHang): ?>
                                    <option value="<?= (int) $idHang ?>"<?= (int) $tauBayCanSua['id_hang_hang_khong'] === (int) $idHang ? ' selected' : '' ?>><?= htmlspecialchars($nhanHang, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="sua_tai_trong_cat_canh_mtow" class="form-label fw-semibold">
                                MTOW (tấn) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-speedometer2"></i></span>
                                <input type="number" step="0.01" min="0.01" name="tai_trong_cat_canh_mtow"
                                       id="sua_tai_trong_cat_canh_mtow" class="form-control"
                                       value="<?= htmlspecialchars(number_format((float) $tauBayCanSua['tai_trong_cat_canh_mtow'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="sua_suc_chua_hanh_khach" class="form-label fw-semibold">
                                Sức chứa (khách) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-people"></i></span>
                                <input type="number" min="1" name="suc_chua_hanh_khach"
                                       id="sua_suc_chua_hanh_khach" class="form-control"
                                       value="<?= (int) $tauBayCanSua['suc_chua_hanh_khach'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label for="sua_anh_tai_len" class="form-label fw-semibold">Hình ảnh loại tàu bay</label>
                        <?php if ($anhTauBayCanSua !== ''): ?>
                            <div class="mb-2">
                                <img src="<?= htmlspecialchars($anhTauBayCanSua, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Ảnh hiện tại của <?= htmlspecialchars((string) $tauBayCanSua['ma_loai'], ENT_QUOTES, 'UTF-8') ?>"
                                     class="maint-aircraft-thumb" loading="lazy">
                                <span class="small text-secondary ms-2">Ảnh hiện tại</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="anh_tai_len" id="sua_anh_tai_len" class="form-control form-control-sm"
                               accept="image/png, image/jpeg, image/jpg, image/gif, image/webp">
                        <div class="form-text">Để trống nếu muốn giữ ảnh hiện tại.</div>
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-end gap-2 px-3 py-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-check2 me-1"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal sửa loại tàu bay-->
<?php endif; ?>

<?php if ($tauBayCanXoa !== null): ?>
<!-- Modal xác nhận xóa (thuần PHP + CSS, không dùng JS) -->
<div class="modal fade is-open" id="xoaTauBayModal" tabindex="-1" aria-labelledby="xoaTauBayModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger mb-3" style="width: 58px; height: 58px;">
                    <i class="bi bi-trash3 fs-4"></i>
                </span>
                <h5 class="mb-2" id="xoaTauBayModalLabel">Xóa loại tàu bay?</h5>
                <p class="text-secondary small mb-0">
                    Bạn có chắc muốn xóa <strong><?= htmlspecialchars($tauBayCanXoa['ma_loai'], ENT_QUOTES, 'UTF-8') ?></strong>?
                    Hành động này không thể hoàn tác.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border px-3">Hủy</a>
                <form method="post" action="<?= htmlspecialchars(baoTriUrl('tau_bay.php', ['id' => $idCanXoa]), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                    <input type="hidden" name="action" value="xoa_tau_bay" />
                    <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="giu_san_bay" value="<?= (int) $locSanBay ?>" />
                    <button type="submit" class="btn btn-danger px-3">
                        <i class="bi bi-trash3 me-1"></i>Xóa loại tàu bay
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
