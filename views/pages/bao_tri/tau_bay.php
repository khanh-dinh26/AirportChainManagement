<?php
require_once __DIR__ . '/_bootstrap.php';

/* Xóa chỉ chạy khi form xác nhận được gửi (POST), không xóa bằng link GET */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'xoa_tau_bay') {
    $baoTriController->xoaTauBay();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'them_tau_bay') {
    $baoTriController->themTauBay();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'them_hang_hang_khong') {
    $baoTriController->themHangHangKhong();
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

// Kiểm tra phạm vi phân quyền sân bay theo tài khoản
$userSanBay = pham_vi_san_bay();
$isQuanLySanBay = ($userSanBay > 0);
$tenSanBayQuanLy = '';
if ($isQuanLySanBay && isset($sanBayLabels[$userSanBay])) {
    $tenSanBayQuanLy = $sanBayLabels[$userSanBay];
}

// Tự mở lại modal khi vừa có lỗi nhập liệu
$modalError = null;
if (isset($_GET['open']) && $_GET['open'] === 'create' && !empty($_SESSION['flash_error'])) {
    $modalError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
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

$pageTitle  = 'Quản lý Loại tàu bay';
$pageHeader = 'Danh mục Loại tàu bay (MTOW)';
$navActive    = 'ha_tang';
$navSubActive = 'aircraft_types';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Quản lý hạ tầng', 'url' => BASE_URL . '/index.php?page=ha_tang'],
    ['label' => 'Loại tàu bay', 'url' => null],
];

$tongLoaiTauBay = count($tau_bay);

/* ------------------------------------------------------------
 * Lọc danh sách phía máy chủ: ?q=...&san_bay=<id>.
 * ------------------------------------------------------------ */
$tuKhoa            = trim((string) ($_GET['q'] ?? ''));
$locSanBay         = $isQuanLySanBay ? (int) $userSanBay : (int) ($_GET['san_bay'] ?? 0);
$tongLoaiTauBayGoc = $tongLoaiTauBay;

if (!$isQuanLySanBay && $locSanBay > 0 && !isset($sanBayLabels[$locSanBay])) {
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

$tongKetQua = count($tau_bay);
$soTauBayMoiTrang = 6;
$tongTrang = max(1, (int) ceil($tongKetQua / $soTauBayMoiTrang));
$trangHienTai = max(1, (int) ($_GET['trang'] ?? 1));
$trangHienTai = min($trangHienTai, $tongTrang);
$viTriBatDau = ($trangHienTai - 1) * $soTauBayMoiTrang;
$tauBayTrenTrang = array_slice($tau_bay, $viTriBatDau, $soTauBayMoiTrang);
$tuTauBay = $tongKetQua > 0 ? $viTriBatDau + 1 : 0;
$denTauBay = min($viTriBatDau + $soTauBayMoiTrang, $tongKetQua);

$filterQuery = array_filter([
    'q'       => $tuKhoa,
    'san_bay' => $locSanBay > 0 ? $locSanBay : '',
    'trang'   => $trangHienTai > 1 ? $trangHienTai : '',
], static fn($v) => $v !== '');

$urlDongModal = baoTriUrl('tau_bay.php', $filterQuery);
$tauBayPageUrl = static function (int $page) use ($tuKhoa, $locSanBay): string {
    return baoTriUrl('tau_bay.php', array_filter([
        'q'       => $tuKhoa,
        'san_bay' => $locSanBay > 0 ? $locSanBay : '',
        'trang'   => $page > 1 ? $page : '',
    ], static fn($v) => $v !== ''));
};

/* ------------------------------------------------------------
 * Xác nhận xóa (thuần PHP + CSS, không dùng confirm() của JS):
 * link Xóa trỏ tới ?xac_nhan_xoa=<id>; trang hiện hộp thoại,
 * nút "Xóa" trong hộp thoại mới gửi form POST thật.
 * ------------------------------------------------------------ */
$idCanXoa     = (int) ($_GET['xac_nhan_xoa'] ?? 0);
$tauBayCanXoa = null;
if ($idCanXoa > 0) {
    foreach ($tau_bay as $tbItem) {
        if ((int) $tbItem['id'] === $idCanXoa) {
            $tauBayCanXoa = $tbItem;
            break;
        }
    }
}

$idCanSua     = (int) ($_GET['sua'] ?? 0);
$tauBayCanSua = null;
if ($idCanSua > 0) {
    foreach ($tau_bay as $tbItem) {
        if ((int) $tbItem['id'] === $idCanSua) {
            $tauBayCanSua = $tbItem;
            break;
        }
    }
}

$idCanXemChiTiet = (int) ($_GET['chi_tiet'] ?? 0);
$tauBayChiTiet = null;
if ($idCanXemChiTiet > 0) {
    foreach ($tau_bay as $tbItem) {
        if ((int) $tbItem['id'] === $idCanXemChiTiet) {
            $tauBayChiTiet = $tbItem;
            break;
        }
    }
}

ob_start();
?>
<?php require __DIR__ . '/_flash.php'; ?>
<div class="maint-page maint-page--aircraft">
<div class="card">
    <div class="card-header">
        <h3 class="card-title d-flex align-items-center flex-wrap gap-2">
            <span>Danh sách loại tàu bay</span>
            <span class="badge text-bg-secondary" title="Tổng số loại tàu bay"><?= $tongLoaiTauBayGoc ?></span>
            <?php if ($isQuanLySanBay && $tenSanBayQuanLy !== ''): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7 fw-semibold">
                    <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($tenSanBayQuanLy, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>
        </h3>
        <div class="card-tools d-flex flex-wrap align-items-center gap-2">
            <a href="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" title="Quản lý danh sách hãng hàng không">
                <i class="bi bi-buildings me-1"></i>Quản lý hãng hàng không (<?= count($hang_list) ?>)
            </a>
            <a href="#themTauBayModal" class="btn btn-sm btn-success">
                <i class="bi bi-plus-lg me-1"></i>Thêm loại tàu bay
            </a>
        </div>
    </div>

    <?php if ($tongLoaiTauBayGoc > 0): ?>
        <div class="card-body pb-0">
            <form method="get" action="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 mb-3">
                <div class="<?= $isQuanLySanBay ? 'col-12 col-lg-8' : 'col-12 col-lg-4' ?>">
                    <label for="aircraftSearch" class="visually-hidden">Tìm kiếm loại tàu bay</label>
                    <input type="search" id="aircraftSearch" name="q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Tên dòng máy bay, hãng hoặc mã LTB...">
                </div>
                <?php if ($isQuanLySanBay): ?>
                    <input type="hidden" name="san_bay" value="<?= (int) $userSanBay ?>">
                <?php else: ?>
                    <div class="col-12 col-lg-4">
                        <label for="aircraftAirportFilter" class="visually-hidden">Sân bay</label>
                        <select id="aircraftAirportFilter" name="san_bay" class="form-select">
                            <option value=""<?= $locSanBay === 0 ? ' selected' : '' ?>>Tất cả sân bay</option>
                            <?php foreach ($sanBayLabels as $idSb => $nhanSb): ?>
                                <option value="<?= (int) $idSb ?>"<?= $locSanBay === (int) $idSb ? ' selected' : '' ?>><?= htmlspecialchars($nhanSb, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
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
                    <?php if ($tongKetQua > 0): ?>
                        Hiển thị <strong><?= $tuTauBay ?>–<?= $denTauBay ?></strong> / <strong><?= $tongKetQua ?></strong> loại tàu bay
                    <?php else: ?>
                        Hiển thị <strong>0</strong> loại tàu bay
                    <?php endif; ?>
                    <?php if ($isQuanLySanBay && $tenSanBayQuanLy !== ''): ?>
                        · <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($tenSanBayQuanLy, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php elseif ($locSanBay > 0 && isset($sanBayLabels[$locSanBay])): ?>
                        · <span class="badge text-bg-light border"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($sanBayLabels[$locSanBay], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-body">
        <?php if (!empty($tauBayTrenTrang)): ?>
            <div class="maint-aircraft-grid" id="aircraftList">
                <?php foreach ($tauBayTrenTrang as $tb): ?>
                    <?php
                    $maLoaiTauBay = 'LTB-' . sprintf('%03d', (int) $tb['id']);
                    $anhUrl       = baoTriAircraftImageUrl($tb['hinh_anh'] ?? null);
                    $tenSanBay    = trim((string) ($tb['ten_san_bay'] ?? ''));
                    $hangTauBay   = trim((string) ($tb['hang'] ?? ''));
                    $xoaUrl = baoTriUrl('tau_bay.php', array_filter([
                        'q'            => $tuKhoa,
                        'san_bay'      => $locSanBay > 0 ? $locSanBay : '',
                        'trang'        => $trangHienTai > 1 ? $trangHienTai : '',
                        'xac_nhan_xoa' => (int) $tb['id'],
                    ], static fn($v) => $v !== ''));
                    $suaUrl = baoTriUrl('tau_bay.php', array_filter([
                        'q'       => $tuKhoa,
                        'san_bay' => $locSanBay > 0 ? $locSanBay : '',
                        'trang'   => $trangHienTai > 1 ? $trangHienTai : '',
                        'sua'     => (int) $tb['id'],
                    ], static fn($v) => $v !== ''));
                    $chiTietUrl = baoTriUrl('tau_bay.php', array_filter([
                        'q'        => $tuKhoa,
                        'san_bay'  => $locSanBay > 0 ? $locSanBay : '',
                        'trang'    => $trangHienTai > 1 ? $trangHienTai : '',
                        'chi_tiet' => (int) $tb['id'],
                    ], static fn($v) => $v !== ''));
                    ?>
                    <article class="maint-aircraft-card">
                        <div class="maint-aircraft-card__image">
                            <?php if ($anhUrl !== ''): ?>
                                <a href="#anh-tau-bay-<?= (int) $tb['id'] ?>" title="Phóng to ảnh <?= htmlspecialchars($tb['ma_loai'], ENT_QUOTES, 'UTF-8') ?>">
                                    <img src="<?= htmlspecialchars($anhUrl, ENT_QUOTES, 'UTF-8') ?>"
                                         alt="Ảnh loại tàu bay <?= htmlspecialchars($tb['ma_loai'], ENT_QUOTES, 'UTF-8') ?>"
                                         loading="lazy">
                                </a>
                            <?php else: ?>
                                <span class="maint-aircraft-card__image-empty" aria-label="Chưa có ảnh">
                                    <i class="bi bi-airplane" aria-hidden="true"></i>
                                </span>
                            <?php endif; ?>
                            <span class="maint-aircraft-card__code"><?= $maLoaiTauBay ?></span>
                        </div>
                        <div class="maint-aircraft-card__body">
                            <h4><?= htmlspecialchars((string) $tb['ma_loai'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <div class="maint-aircraft-card__meta">
                                <div>
                                    <i class="bi bi-building" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($hangTauBay !== '' ? $hangTauBay : 'Chưa xác định hãng', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div>
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($tenSanBay !== '' ? $tenSanBay : 'Chưa xác định sân bay', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <div class="maint-aircraft-card__actions">
                                <a href="<?= htmlspecialchars($chiTietUrl, ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye me-1"></i>Xem chi tiết
                                </a>
                                <div class="d-flex gap-1">
                                    <a href="<?= htmlspecialchars($suaUrl, ENT_QUOTES, 'UTF-8') ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Sửa loại tàu bay">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= htmlspecialchars($xoaUrl, ENT_QUOTES, 'UTF-8') ?>"
                                       class="btn btn-sm btn-outline-danger" title="Xóa loại tàu bay">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php elseif ($tuKhoa !== '' || $locSanBay > 0): ?>
            <div class="text-center py-4 text-secondary">
                Không tìm thấy loại tàu bay phù hợp với bộ lọc hiện tại.
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="maint-empty-icon"><i class="bi bi-airplane"></i></div>
                <div class="fw-semibold">Chưa có loại tàu bay nào</div>
                <div class="small text-secondary mb-3">Thêm loại tàu bay đầu tiên để bắt đầu quản lý.</div>
                <a href="#danhSachHangModal" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-buildings me-1"></i>Danh sách hãng (<?= count($hang_list) ?>)
            </a>
            <a href="#themHangModal" class="btn btn-sm btn-primary">
                <i class="bi bi-building-add me-1"></i>Thêm hãng hàng không
            </a>
            <a href="#themTauBayModal" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Thêm loại tàu bay</a>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($tongTrang > 1): ?>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="small text-secondary">Trang <strong><?= $trangHienTai ?></strong> / <?= $tongTrang ?></div>
            <?= baoTriPagination($trangHienTai, $tongTrang, $tauBayPageUrl) ?>
        </div>
    <?php endif; ?>
</div>
</div>

<?php
// Lightbox cho ảnh loại tàu bay (mở bằng :target, đóng bằng nút X).
$lightboxImages = [];
foreach ($tauBayTrenTrang as $tbAnh) {
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

<?php if ($tauBayChiTiet !== null): ?>
<?php $anhTauBayChiTiet = baoTriAircraftImageUrl($tauBayChiTiet['hinh_anh'] ?? null); ?>
<div class="modal fade maint-modal maint-modal--aircraft maint-aircraft-detail is-open" id="chiTietTauBayModal" tabindex="-1" aria-labelledby="chiTietTauBayModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-icon"><i class="bi bi-airplane"></i></span>
                    <div>
                        <div class="small text-secondary text-uppercase fw-semibold">Hồ sơ khai thác</div>
                        <h5 class="modal-title mb-0 fs-5" id="chiTietTauBayModalLabel">Chi tiết loại tàu bay</h5>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="acv-close" aria-label="Đóng">&times;</a>
            </div>
            <div class="modal-body">
                <section class="maint-aircraft-detail__hero">
                    <div class="maint-aircraft-detail__image">
                        <?php if ($anhTauBayChiTiet !== ''): ?>
                            <img src="<?= htmlspecialchars($anhTauBayChiTiet, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Ảnh loại tàu bay <?= htmlspecialchars((string) $tauBayChiTiet['ma_loai'], ENT_QUOTES, 'UTF-8') ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <i class="bi bi-airplane" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div class="maint-aircraft-detail__identity">
                        <span class="maint-aircraft-detail__code">LTB-<?= sprintf('%03d', (int) $tauBayChiTiet['id']) ?></span>
                        <h3><?= htmlspecialchars((string) $tauBayChiTiet['ma_loai'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="maint-aircraft-detail__airline">
                            <i class="bi bi-building" aria-hidden="true"></i>
                            <?= htmlspecialchars(trim((string) ($tauBayChiTiet['hang'] ?? '')) ?: 'Chưa xác định hãng', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </section>
                <section class="maint-aircraft-detail__stats" aria-label="Thông số khai thác">
                    <div class="maint-aircraft-detail__stat">
                        <span class="maint-aircraft-detail__stat-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                        <div>
                            <div class="maint-aircraft-detail__label">MTOW</div>
                            <div class="maint-aircraft-detail__value"><?= number_format((float) $tauBayChiTiet['tai_trong_cat_canh_mtow'], 2) ?><small> tấn</small></div>
                        </div>
                    </div>
                    <div class="maint-aircraft-detail__stat">
                        <span class="maint-aircraft-detail__stat-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <div>
                            <div class="maint-aircraft-detail__label">Sức chứa</div>
                            <div class="maint-aircraft-detail__value"><?= number_format((int) $tauBayChiTiet['suc_chua_hanh_khach']) ?><small> khách</small></div>
                        </div>
                    </div>
                </section>
                <section class="maint-aircraft-detail__location">
                    <span class="maint-aircraft-detail__location-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                    <div>
                        <div class="maint-aircraft-detail__label">Sân bay khai thác</div>
                        <div class="maint-aircraft-detail__location-name">
                            <?= htmlspecialchars(trim((string) ($tauBayChiTiet['ten_san_bay'] ?? '')) ?: 'Chưa xác định', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </section>
                <section class="maint-aircraft-detail__description">
                    <div class="maint-aircraft-detail__description-title">
                        <i class="bi bi-card-text" aria-hidden="true"></i><span>Mô tả</span>
                    </div>
                    <p>
                        Loại tàu bay <strong><?= htmlspecialchars((string) $tauBayChiTiet['ma_loai'], ENT_QUOTES, 'UTF-8') ?></strong>
                        do <strong><?= htmlspecialchars(trim((string) ($tauBayChiTiet['hang'] ?? '')) ?: 'hãng hàng không chưa xác định', ENT_QUOTES, 'UTF-8') ?></strong>
                        khai thác tại <strong><?= htmlspecialchars(trim((string) ($tauBayChiTiet['ten_san_bay'] ?? '')) ?: 'sân bay chưa xác định', ENT_QUOTES, 'UTF-8') ?></strong>.
                        Tàu bay có trọng tải cất cánh tối đa <?= number_format((float) $tauBayChiTiet['tai_trong_cat_canh_mtow'], 2) ?> tấn
                        và sức chứa <?= number_format((int) $tauBayChiTiet['suc_chua_hanh_khach']) ?> hành khách.
                    </p>
                </section>
            </div>
            <div class="modal-footer">
                <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary px-3">
                    <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Đóng
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!--begin::Modal thêm hãng hàng không-->
<div class="modal fade maint-modal maint-modal--aircraft<?= $modalHangError !== null ? ' is-open' : '' ?>" id="themHangModal" tabindex="-1" aria-labelledby="themHangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="action" value="them_hang_hang_khong">
                <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="giu_san_bay" value="<?= (int) $locSanBay ?>">
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

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between gap-2 px-3 py-2">
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

<!--begin::Modal thêm loại tàu bay-->
<div class="modal fade maint-modal maint-modal--aircraft<?= $modalError !== null ? ' is-open' : '' ?>" id="themTauBayModal" tabindex="-1" aria-labelledby="themTauBayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('tau_bay.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="them_tau_bay">
                <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="giu_san_bay" value="<?= (int) $locSanBay ?>">
                <input type="hidden" name="giu_trang" value="<?= $trangHienTai ?>">
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
                        <?php if ($isQuanLySanBay): ?>
                            <input type="hidden" name="id_san_bay" value="<?= (int) $userSanBay ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                                <input type="text" class="form-control bg-light fw-medium text-dark" value="<?= htmlspecialchars($tenSanBayQuanLy ?: 'Sân bay đang quản lý', ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
                            </div>
                            <div class="form-text small text-secondary">Tàu bay được tự động phân bổ vào sân bay bạn đang quản lý.</div>
                        <?php else: ?>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt"></i></span>
                                <select name="id_san_bay" id="id_san_bay" class="form-select" required>
                                    <option value="">Chọn sân bay</option>
                                    <?php foreach ($sanBayLabels as $idSb => $nhanSb): ?>
                                        <option value="<?= (int) $idSb ?>"<?= $locSanBay === (int) $idSb ? ' selected' : '' ?>><?= htmlspecialchars($nhanSb, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
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
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="id_hang_hang_khong" class="form-label fw-semibold mb-0">
                                Hãng hàng không khai thác <span class="text-danger">*</span>
                            </label>
                            <a href="<?= htmlspecialchars(baoTriUrl('hang_hang_khong.php'), ENT_QUOTES, 'UTF-8') ?>#themHangModal" class="small text-primary text-decoration-none" title="Chuyển sang trang thêm hãng hàng không mới">
                                <i class="bi bi-plus-circle me-1"></i>Thêm hãng mới
                            </a>
                        </div>
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
                        <label for="anh_tai_len" class="form-label fw-semibold">Hình ảnh loại tàu bay <span class="text-danger">*</span></label>
                        <input type="file" name="anh_tai_len" id="anh_tai_len" class="form-control form-control-sm"
                               accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" required>
                        <div class="form-text small text-danger"><i class="bi bi-exclamation-circle me-1"></i>Bắt buộc tải lên hình ảnh tàu bay.</div>
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
                <input type="hidden" name="giu_trang" value="<?= $trangHienTai ?>">
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
                        <?php if ($isQuanLySanBay): ?>
                            <input type="hidden" name="id_san_bay" value="<?= (int) $userSanBay ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                                <input type="text" class="form-control bg-light fw-medium text-dark" value="<?= htmlspecialchars($tenSanBayQuanLy ?: 'Sân bay đang quản lý', ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
                            </div>
                        <?php else: ?>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt"></i></span>
                                <select name="id_san_bay" id="sua_id_san_bay" class="form-select" required>
                                    <option value="">Chọn sân bay</option>
                                    <?php foreach ($sanBayLabels as $idSb => $nhanSb): ?>
                                        <option value="<?= (int) $idSb ?>"<?= (int) $tauBayCanSua['id_san_bay'] === (int) $idSb ? ' selected' : '' ?>><?= htmlspecialchars($nhanSb, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
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
                    <input type="hidden" name="id" value="<?= (int) $idCanXoa ?>" />
                    <input type="hidden" name="giu_q" value="<?= htmlspecialchars($tuKhoa, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="giu_san_bay" value="<?= (int) $locSanBay ?>" />
                    <input type="hidden" name="giu_trang" value="<?= $trangHienTai ?>" />
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
