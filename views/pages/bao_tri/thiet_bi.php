<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'store_thiet_bi') {
    $baoTriController->storeThietBi();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_thiet_bi') {
    $baoTriController->updateThietBi();
}

/* Xóa chỉ chạy khi form xác nhận được gửi (POST), không xóa bằng link GET */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'xoa_thiet_bi') {
    $baoTriController->xoaThietBi();
}

if (!isset($thiet_bi)) {
    $thiet_bi = $baoTriController->getDanhSachThietBi();
}

if (!isset($san_bay_list)) {
    $san_bay_list = $baoTriController->getSanBayList();
}

// Tự mở lại modal khi vừa có lỗi nhập liệu
$modalError = null;
if (isset($_GET['open']) && $_GET['open'] === 'create' && !empty($_SESSION['flash_error'])) {
    $modalError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Thiết bị đang mở modal sửa (?sua=<id>)
$suaId          = (int) ($_GET['sua'] ?? 0);
$thietBiDangSua = null;
$modalEditError = null;
if ($suaId > 0) {
    $thietBiDangSua = $baoTriController->getThietBiById($suaId);
    if ($thietBiDangSua === null) {
        $_SESSION['flash_error'] = 'Không tìm thấy thiết bị cần sửa.';
        header('Location: ' . baoTriUrl('thiet_bi.php'));
        exit();
    }
    if (isset($_GET['open']) && $_GET['open'] === 'edit' && !empty($_SESSION['flash_error'])) {
        $modalEditError = (string) $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
    }
}

$pageTitle  = 'Quản lý Thiết bị Sân bay';
$pageHeader = 'Danh mục Trang thiết bị';
$navActive    = 'bao_tri';
$navSubActive = 'equipment';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Bảo trì', 'url' => baoTriUrl('index.php')],
    ['label' => 'Trang thiết bị', 'url' => null],
];

$statusLabels = [
    'Tot'     => 'Tốt',
    'Bao_Tri' => 'Đang bảo trì',
    'Hong'    => 'Hỏng',
];

$statusBadgeClasses = [
    'Tot'     => 'text-bg-success',
    'Bao_Tri' => 'text-bg-warning',
    'Hong'    => 'text-bg-danger',
];

$loaiThietBiLabels = [
    'Den_Tin_Hieu'       => 'Đèn tín hiệu',
    'Thiet_Bi_Dan_Duong' => 'Thiết bị dẫn đường',
    'Xe_Thang'           => 'Xe thang',
    'Xe_Keo'             => 'Xe kéo',
    'Khac'               => 'Khác',
];

$loaiThietBiIcons = [
    'Den_Tin_Hieu'       => 'bi-lightbulb',
    'Thiet_Bi_Dan_Duong' => 'bi-broadcast-pin',
    'Xe_Thang'           => 'bi-truck',
    'Xe_Keo'             => 'bi-truck',
    'Khac'               => 'bi-box-seam',
];

$loaiAvatarClasses = [
    'Den_Tin_Hieu'       => 'maint-avatar--warning',
    'Thiet_Bi_Dan_Duong' => 'maint-avatar--info',
    'Xe_Thang'           => 'maint-avatar--primary',
    'Xe_Keo'             => 'maint-avatar--success',
    'Khac'               => 'maint-avatar--secondary',
];

$tongThietBi     = count($thiet_bi);
$soThietBiTot    = 0;
$soThietBiBaoTri = 0;
$soThietBiHong   = 0;

foreach ($thiet_bi as $tb) {
    if ($tb['trang_thai'] === 'Tot') {
        $soThietBiTot++;
    } elseif ($tb['trang_thai'] === 'Bao_Tri') {
        $soThietBiBaoTri++;
    } elseif ($tb['trang_thai'] === 'Hong') {
        $soThietBiHong++;
    }
}

$statusCounts = [
    'Tot'     => $soThietBiTot,
    'Bao_Tri' => $soThietBiBaoTri,
    'Hong'    => $soThietBiHong,
];

$dsSanBay = [];
$dsLoai   = [];
foreach ($thiet_bi as $tb) {
    $dsSanBay[$tb['ten_san_bay']] = true;
    $dsLoai[$tb['loai_thiet_bi']] = true;
}
$dsSanBay = array_keys($dsSanBay);
sort($dsSanBay);

$dsLoai = array_keys($dsLoai);
usort($dsLoai, function ($a, $b) use ($loaiThietBiLabels) {
    return strcmp($loaiThietBiLabels[$a] ?? $a, $loaiThietBiLabels[$b] ?? $b);
});

/* ------------------------------------------------------------
 * Lọc danh sách phía máy chủ (thay cho bộ lọc JavaScript cũ).
 * Tham số GET: q, san_bay, loai, trang_thai, co_phieu_mo.
 * ------------------------------------------------------------ */
$filterQ         = trim((string) ($_GET['q'] ?? ''));
$filterSanBay    = trim((string) ($_GET['san_bay'] ?? ''));
$filterLoai      = trim((string) ($_GET['loai'] ?? ''));
$filterTrangThai = trim((string) ($_GET['trang_thai'] ?? ''));
$filterCoPhieuMo = trim((string) ($_GET['co_phieu_mo'] ?? ''));

$tongThietBiGoc = $tongThietBi;
$soThietBiCoPhieuMo = 0;
foreach ($thiet_bi as $tb) {
    if ((int) ($tb['so_phieu_mo'] ?? 0) > 0) {
        $soThietBiCoPhieuMo++;
    }
}

$thiet_bi = array_values(array_filter($thiet_bi, static function (array $tb) use ($filterQ, $filterSanBay, $filterLoai, $filterTrangThai, $filterCoPhieuMo, $loaiThietBiLabels): bool {
    if ($filterQ !== '') {
        $maThietBi = 'TB-' . sprintf('%03d', (int) $tb['id']);
        $loaiLabel = $loaiThietBiLabels[$tb['loai_thiet_bi']] ?? str_replace('_', ' ', (string) $tb['loai_thiet_bi']);
        $haystack  = $tb['ten_thiet_bi'] . ' ' . $maThietBi . ' ' . $tb['ten_san_bay'] . ' ' . $loaiLabel . ' ' . $tb['loai_thiet_bi'];
        if (mb_stripos($haystack, $filterQ, 0, 'UTF-8') === false) {
            return false;
        }
    }
    if ($filterSanBay !== '' && (string) $tb['ten_san_bay'] !== $filterSanBay) {
        return false;
    }
    if ($filterLoai !== '' && (string) $tb['loai_thiet_bi'] !== $filterLoai) {
        return false;
    }
    if ($filterTrangThai !== '' && (string) $tb['trang_thai'] !== $filterTrangThai) {
        return false;
    }
    if ($filterCoPhieuMo === '1' && (int) ($tb['so_phieu_mo'] ?? 0) <= 0) {
        return false;
    }
    return true;
}));

$soLuongHienThi = count($thiet_bi);

// Phân trang danh sách thiết bị: 5 thiết bị mỗi trang.
$soThietBiMoiTrang = 5;
$tongTrang         = max(1, (int) ceil($soLuongHienThi / $soThietBiMoiTrang));
$trangHienTai      = max(1, (int) ($_GET['trang'] ?? 1));
$trangHienTai      = min($trangHienTai, $tongTrang);
$viTriBatDau       = ($trangHienTai - 1) * $soThietBiMoiTrang;
$thietBiTrenTrang  = array_slice($thiet_bi, $viTriBatDau, $soThietBiMoiTrang);
$tuThietBi         = $soLuongHienThi > 0 ? $viTriBatDau + 1 : 0;
$denThietBi        = min($viTriBatDau + $soThietBiMoiTrang, $soLuongHienThi);

$urlDongModal   = baoTriUrl('thiet_bi.php', array_filter([
    'q'           => $filterQ,
    'san_bay'     => $filterSanBay,
    'loai'        => $filterLoai,
    'trang_thai'  => $filterTrangThai,
    'co_phieu_mo' => $filterCoPhieuMo,
    'trang'       => $trangHienTai > 1 ? $trangHienTai : '',
], static fn($v) => $v !== ''));

$equipQuickUrl = static function (string $status) use ($filterQ, $filterSanBay, $filterLoai, $filterCoPhieuMo): string {
    return baoTriUrl('thiet_bi.php', array_filter([
        'q'           => $filterQ,
        'san_bay'     => $filterSanBay,
        'loai'        => $filterLoai,
        'trang_thai'  => $status,
        'co_phieu_mo' => $filterCoPhieuMo,
    ], static fn($v) => $v !== ''));
};

$equipOpenTicketUrl = static function (string $value) use ($filterQ, $filterSanBay, $filterLoai, $filterTrangThai): string {
    return baoTriUrl('thiet_bi.php', array_filter([
        'q'           => $filterQ,
        'san_bay'     => $filterSanBay,
        'loai'        => $filterLoai,
        'trang_thai'  => $filterTrangThai,
        'co_phieu_mo' => $value,
    ], static fn($v) => $v !== ''));
};

// Link phân trang: giữ nguyên bộ lọc hiện tại.
$equipPageUrl = static function (int $page) use ($filterQ, $filterSanBay, $filterLoai, $filterTrangThai, $filterCoPhieuMo): string {
    return baoTriUrl('thiet_bi.php', array_filter([
        'q'           => $filterQ,
        'san_bay'     => $filterSanBay,
        'loai'        => $filterLoai,
        'trang_thai'  => $filterTrangThai,
        'co_phieu_mo' => $filterCoPhieuMo,
        'trang'       => $page > 1 ? $page : '',
    ], static fn($v) => $v !== ''));
};

/* ------------------------------------------------------------
 * Xác nhận xóa (thuần PHP + CSS, không dùng confirm() của JS):
 * link Xóa trỏ tới ?xac_nhan_xoa=<id>; trang hiện hộp thoại,
 * nút "Xóa" trong hộp thoại mới gửi form POST thật.
 * ------------------------------------------------------------ */
$idCanXoa      = (int) ($_GET['xac_nhan_xoa'] ?? 0);
$thietBiCanXoa = null;
if ($idCanXoa > 0) {
    foreach ($baoTriController->getDanhSachThietBi() as $tbItem) {
        if ((int) $tbItem['id'] === $idCanXoa) {
            $thietBiCanXoa = $tbItem;
            break;
        }
    }
}



ob_start();
?>
<?php require __DIR__ . '/_flash.php'; ?>
<div class="maint-page maint-page--equipment">
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= $tongThietBi ?></h3>
                <p>Tổng thiết bị</p>
            </div>
            <i class="small-box-icon bi bi-hdd-stack"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= $soThietBiTot ?></h3>
                <p>Đang hoạt động</p>
            </div>
            <i class="small-box-icon bi bi-check2-circle"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= $soThietBiBaoTri ?></h3>
                <p>Đang bảo trì</p>
            </div>
            <i class="small-box-icon bi bi-tools"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3><?= $soThietBiHong ?></h3>
                <p>Đang hỏng</p>
            </div>
            <i class="small-box-icon bi bi-exclamation-triangle"></i>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Danh sách thiết bị
                    <span class="badge text-bg-secondary ms-1" title="Tổng số thiết bị"><?= $tongThietBiGoc ?></span>
                </h3>
                <div class="card-tools d-flex flex-wrap align-items-center gap-1">
                    <a href="#themThietBiModal" class="btn btn-sm btn-success">
                        <i class="bi bi-plus-lg me-1"></i>Thêm thiết bị
                    </a>
                </div>
            </div>

            <?php if ($tongThietBiGoc > 0): ?>
                <div class="card-body pb-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="small text-secondary fw-semibold text-uppercase">Lọc nhanh</span>
                        <a href="<?= htmlspecialchars($equipQuickUrl(''), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $filterTrangThai === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            Tất cả <span class="badge text-bg-secondary ms-1"><?= $tongThietBiGoc ?></span>
                        </a>
                        <?php foreach ($statusLabels as $key => $label): ?>
                            <a href="<?= htmlspecialchars($equipQuickUrl($key), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $filterTrangThai === $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                <?= $label ?> <span class="badge text-bg-secondary ms-1"><?= $statusCounts[$key] ?></span>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= htmlspecialchars($equipOpenTicketUrl($filterCoPhieuMo === '1' ? '' : '1'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $filterCoPhieuMo === '1' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <i class="bi bi-funnel me-1"></i>Có phiếu mở <span class="badge text-bg-secondary ms-1"><?= $soThietBiCoPhieuMo ?></span>
                        </a>
                    </div>

                    <form method="get" action="<?= htmlspecialchars(baoTriUrl('thiet_bi.php'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 mb-3">
                        <div class="col-12 col-lg-3">
                            <label for="filterSearch" class="visually-hidden">Tìm kiếm thiết bị</label>
                            <input type="search" id="filterSearch" name="q" value="<?= htmlspecialchars($filterQ, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Tên, mã TB, sân bay, loại...">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="filterSanBay" class="visually-hidden">Sân bay</label>
                            <select id="filterSanBay" name="san_bay" class="form-select">
                                <option value="">Tất cả sân bay</option>
                                <?php foreach ($dsSanBay as $tenSanBay): ?>
                                    <option value="<?= htmlspecialchars($tenSanBay, ENT_QUOTES, 'UTF-8') ?>"<?= $filterSanBay === $tenSanBay ? ' selected' : '' ?>><?= htmlspecialchars($tenSanBay, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="filterLoai" class="visually-hidden">Phân loại</label>
                            <select id="filterLoai" name="loai" class="form-select">
                                <option value="">Tất cả loại</option>
                                <?php foreach ($dsLoai as $loai): ?>
                                    <option value="<?= htmlspecialchars($loai, ENT_QUOTES, 'UTF-8') ?>"<?= $filterLoai === $loai ? ' selected' : '' ?>><?= $loaiThietBiLabels[$loai] ?? htmlspecialchars(str_replace('_', ' ', $loai), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="filterTrangThai" class="visually-hidden">Trạng thái</label>
                            <select id="filterTrangThai" name="trang_thai" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <?php foreach ($statusLabels as $key => $label): ?>
                                    <option value="<?= $key ?>"<?= $filterTrangThai === $key ? ' selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="filterCoPhieuMo" class="visually-hidden">Tình trạng phiếu</label>
                            <select id="filterCoPhieuMo" name="co_phieu_mo" class="form-select">
                                <option value="">Tất cả thiết bị</option>
                                <option value="1"<?= $filterCoPhieuMo === '1' ? ' selected' : '' ?>>Đang có phiếu mở</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-1 d-grid">
                            <button type="submit" class="btn btn-outline-primary" title="Lọc thiết bị" aria-label="Lọc thiết bị">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-secondary mb-2">
                        <span>Hiển thị <strong><?= $tuThietBi ?>–<?= $denThietBi ?></strong> / <strong><?= $soLuongHienThi ?></strong> thiết bị</span>
                        <a href="<?= htmlspecialchars(baoTriUrl('thiet_bi.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 maint-equipment-table" id="thietBiTable">
                    <thead>
                        <tr>
                            <th>Thiết bị</th>
                            <th>Sân bay</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($thietBiTrenTrang)): ?>
                            <?php foreach ($thietBiTrenTrang as $item): ?>
                                <?php
                                $maThietBi  = 'TB-' . sprintf('%03d', (int) $item['id']);
                                $soPhieu    = (int) $item['so_phieu'];
                                $loaiIcon = $loaiThietBiIcons[$item['loai_thiet_bi']] ?? 'bi-hdd-stack';
                                $loaiAvatarClass = $loaiAvatarClasses[$item['loai_thiet_bi']] ?? 'maint-avatar--secondary';
                                $statusLabel = $statusLabels[$item['trang_thai']] ?? htmlspecialchars($item['trang_thai'], ENT_QUOTES, 'UTF-8');
                                $statusClass = $statusBadgeClasses[$item['trang_thai']] ?? 'text-bg-secondary';
                                $chiTietUrl = baoTriUrl('chi_tiet_thiet_bi.php', ['id' => (int) $item['id']]);
                                $suaUrl = baoTriUrl('thiet_bi.php', array_filter([
                                    'q'           => $filterQ,
                                    'san_bay'     => $filterSanBay,
                                    'loai'        => $filterLoai,
                                    'trang_thai'  => $filterTrangThai,
                                    'co_phieu_mo' => $filterCoPhieuMo,
                                    'trang'       => $trangHienTai > 1 ? $trangHienTai : '',
                                    'sua'         => (int) $item['id'],
                                ], static fn($v) => $v !== ''));
                                ?>
                                <tr data-search="<?= htmlspecialchars($item['ten_thiet_bi'] . ' ' . $maThietBi, ENT_QUOTES, 'UTF-8') ?>"
                                    data-san-bay="<?= htmlspecialchars($item['ten_san_bay'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-loai="<?= htmlspecialchars($item['loai_thiet_bi'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-trang-thai="<?= htmlspecialchars($item['trang_thai'], ENT_QUOTES, 'UTF-8') ?>">
                                    <td>
                                        <div class="maint-equipment-row__identity">
                                            <span class="maint-avatar maint-equipment-row__icon <?= $loaiAvatarClass ?>">
                                                <i class="bi <?= $loaiIcon ?>"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <a href="<?= htmlspecialchars($chiTietUrl, ENT_QUOTES, 'UTF-8') ?>" class="maint-equipment-row__name" title="Xem chi tiết và lịch sử bảo trì">
                                                    <?= htmlspecialchars($item['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                                <div class="small text-secondary maint-code"><?= $maThietBi ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="bi bi-geo-alt text-secondary me-1"></i><?= htmlspecialchars($item['ten_san_bay'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <span class="badge maint-equipment-row__status <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            <a href="<?= htmlspecialchars($chiTietUrl, ENT_QUOTES, 'UTF-8') ?>"
                                               class="btn btn-sm btn-outline-primary" title="Xem chi tiết và lịch sử bảo trì">
                                                <i class="bi bi-eye"></i><span class="d-none d-xl-inline ms-1">Xem</span>
                                            </a>
                                            <a href="<?= htmlspecialchars($suaUrl, ENT_QUOTES, 'UTF-8') ?>"
                                               class="btn btn-sm btn-outline-success" title="Sửa thiết bị">
                                                <i class="bi bi-pencil-square"></i><span class="d-none d-xl-inline ms-1">Sửa</span>
                                            </a>
                                            <?php if ($soPhieu > 0): ?>
                                                <span class="btn btn-sm btn-outline-secondary disabled" title="Không thể xóa vì thiết bị đã có phiếu bảo trì">
                                                    <i class="bi bi-lock"></i><span class="d-none d-xl-inline ms-1">Đã có phiếu</span>
                                                </span>
                                            <?php else: ?>
                                                <?php
                                                $xoaUrl = baoTriUrl('thiet_bi.php', array_filter([
                                                    'q'            => $filterQ,
                                                    'san_bay'      => $filterSanBay,
                                                    'loai'         => $filterLoai,
                                                    'trang_thai'   => $filterTrangThai,
                                                    'co_phieu_mo'  => $filterCoPhieuMo,
                                                    'trang'        => $trangHienTai > 1 ? $trangHienTai : '',
                                                    'xac_nhan_xoa' => (int) $item['id'],
                                                ], static fn($v) => $v !== ''));
                                                ?>
                                                <a href="<?= htmlspecialchars($xoaUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                   class="btn btn-sm btn-outline-danger" title="Xóa thiết bị">
                                                    <i class="bi bi-trash3"></i><span class="d-none d-xl-inline ms-1">Xóa</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <?php if ($tongThietBiGoc > 0): ?>
                                        <div class="fw-semibold">Không tìm thấy thiết bị phù hợp với bộ lọc.</div>
                                        <div class="small text-secondary mb-3">Hãy xóa bớt điều kiện lọc để xem toàn bộ danh mục.</div>
                                        <a href="<?= htmlspecialchars(baoTriUrl('thiet_bi.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
                                    <?php else: ?>
                                        <div class="maint-empty-icon"><i class="bi bi-inbox"></i></div>
                                        <div class="fw-semibold">Chưa có thiết bị nào</div>
                                        <div class="small text-secondary mb-3">Thêm thiết bị đầu tiên để bắt đầu quản lý.</div>
                                        <a href="#themThietBiModal" class="btn btn-sm btn-success">
                                            <i class="bi bi-plus-lg me-1"></i>Thêm thiết bị
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($tongTrang > 1): ?>
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-secondary">Trang <strong><?= $trangHienTai ?></strong> / <?= $tongTrang ?></div>
                    <?= baoTriPagination($trangHienTai, $tongTrang, $equipPageUrl) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!--begin::Modal thêm thiết bị-->
<div class="modal fade maint-modal maint-modal--equipment<?= ($modalError !== null && $thietBiDangSua === null) ? ' is-open' : '' ?>" id="themThietBiModal" tabindex="-1" aria-labelledby="themThietBiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('thiet_bi.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="action" value="store_thiet_bi">
                <div class="modal-header ps-3 pe-5 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="modal-icon">
                            <i class="bi bi-plus-lg"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0 fs-5" id="themThietBiModalLabel">Thêm thiết bị mới</h5>
                            <div class="small text-secondary">Khai báo trang thiết bị mặt đất</div>
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

                    <div class="maint-form-section"><i class="bi bi-hdd-stack"></i> Thông tin thiết bị</div>

                    <div class="mb-2">
                        <label for="them_ten_thiet_bi" class="form-label fw-semibold">
                            Tên thiết bị <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-hdd-stack"></i></span>
                            <input type="text" name="ten_thiet_bi" id="them_ten_thiet_bi" class="form-control"
                                   maxlength="100" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="them_loai_thiet_bi" class="form-label fw-semibold">
                            Phân loại <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-tags"></i></span>
                            <select name="loai_thiet_bi" id="them_loai_thiet_bi" class="form-select form-select-sm" required>
                                <option value="">-- Chọn phân loại --</option>
                                <?php foreach ($loaiThietBiLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="them_id_san_bay" class="form-label fw-semibold">
                            Sân bay quản lý <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt"></i></span>
                            <select name="id_san_bay" id="them_id_san_bay" class="form-select form-select-sm" required>
                                <option value="">-- Chọn sân bay --</option>
                                <?php foreach ($san_bay_list as $sb): ?>
                                    <option value="<?= (int) $sb['id'] ?>"><?= htmlspecialchars($sb['ten_san_bay'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (empty($san_bay_list)): ?>
                            <div class="form-text text-danger">Chưa có dữ liệu sân bay.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between gap-2 px-3 py-2">
                    <div class="d-flex gap-2 ms-auto">
                        <button type="submit" class="btn btn-sm btn-success px-3" <?= empty($san_bay_list) ? 'disabled' : '' ?>>
                            <i class="bi bi-check2 me-1"></i> Lưu thiết bị
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal thêm thiết bị-->

<?php if ($thietBiDangSua !== null): ?>
<?php
$suaStatusLabel = $statusLabels[$thietBiDangSua['trang_thai']] ?? htmlspecialchars((string) $thietBiDangSua['trang_thai'], ENT_QUOTES, 'UTF-8');
$suaStatusClass = $statusBadgeClasses[$thietBiDangSua['trang_thai']] ?? 'text-bg-secondary';
$suaLoaiLabel   = $loaiThietBiLabels[$thietBiDangSua['loai_thiet_bi']] ?? htmlspecialchars(str_replace('_', ' ', (string) $thietBiDangSua['loai_thiet_bi']), ENT_QUOTES, 'UTF-8');
?>
<!--begin::Modal sửa thiết bị-->
<div class="modal fade maint-modal maint-modal--equipment is-open" id="suaThietBiModal" tabindex="-1" aria-labelledby="suaThietBiModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('thiet_bi.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="action" value="update_thiet_bi">
                <input type="hidden" name="id" value="<?= (int) $thietBiDangSua['id'] ?>">
                <div class="modal-header ps-3 pe-5 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="modal-icon">
                            <i class="bi bi-pencil-square"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0 fs-5" id="suaThietBiModalLabel">Sửa thiết bị</h5>
                            <div class="small text-secondary">TB-<?= sprintf('%03d', (int) $thietBiDangSua['id']) ?> · <?= $suaLoaiLabel ?></div>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="acv-close" aria-label="Đóng">&times;</a>
                </div>

                <div class="modal-body px-3 py-3">
                    <?php if ($modalEditError !== null): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle-fill mt-1"></i>
                            <div class="small"><?= htmlspecialchars($modalEditError, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="maint-form-section"><i class="bi bi-hdd-stack"></i> Thông tin thiết bị</div>

                    <div class="mb-2">
                        <label for="sua_ten_thiet_bi" class="form-label fw-semibold">
                            Tên thiết bị <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-hdd-stack"></i></span>
                            <input type="text" name="ten_thiet_bi" id="sua_ten_thiet_bi" class="form-control"
                                   maxlength="100" value="<?= htmlspecialchars($thietBiDangSua['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="sua_loai_thiet_bi" class="form-label fw-semibold">
                            Phân loại <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-tags"></i></span>
                            <select name="loai_thiet_bi" id="sua_loai_thiet_bi" class="form-select form-select-sm" required>
                                <option value="">-- Chọn phân loại --</option>
                                <?php foreach ($loaiThietBiLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= $thietBiDangSua['loai_thiet_bi'] === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sua_id_san_bay" class="form-label fw-semibold">
                            Sân bay quản lý <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body-tertiary"><i class="bi bi-geo-alt"></i></span>
                            <select name="id_san_bay" id="sua_id_san_bay" class="form-select form-select-sm" required>
                                <option value="">-- Chọn sân bay --</option>
                                <?php foreach ($san_bay_list as $sb): ?>
                                    <option value="<?= (int) $sb['id'] ?>"<?= (int) $thietBiDangSua['id_san_bay'] === (int) $sb['id'] ? ' selected' : '' ?>><?= htmlspecialchars($sb['ten_san_bay'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="maint-form-section"><i class="bi bi-activity"></i> Trạng thái vận hành</div>
                    <div class="d-flex align-items-center justify-content-between gap-3 rounded-3 border bg-body-tertiary px-3 py-2">
                        <div>
                            <div class="small text-secondary">Trạng thái hiện tại</div>
                            <span class="badge <?= $suaStatusClass ?>"><?= $suaStatusLabel ?></span>
                        </div>
                        <div class="small text-secondary text-end">
                            <i class="bi bi-arrow-repeat me-1"></i>Tự động theo phiếu
                        </div>
                    </div>
                    <div class="alert alert-light border small mt-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Trạng thái được cập nhật tự động khi tạo, xử lý hoặc hoàn thành phiếu bảo trì.
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between gap-2 px-3 py-2">
                    <div class="small text-secondary d-none d-sm-block">
                        <i class="bi bi-shield-check me-1"></i> Không sửa tay trạng thái thiết bị.
                    </div>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="submit" class="btn btn-sm btn-success px-3">
                            <i class="bi bi-check2 me-1"></i> Lưu thay đổi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal sửa thiết bị-->
<?php endif; ?>

<?php if ($thietBiCanXoa !== null): ?>
<!-- Modal xác nhận xóa (thuần PHP + CSS, không dùng JS) -->
<div class="modal fade is-open" id="xoaThietBiModal" tabindex="-1" aria-labelledby="xoaThietBiModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger mb-3" style="width: 58px; height: 58px;">
                    <i class="bi bi-trash3 fs-4"></i>
                </span>
                <h5 class="mb-2" id="xoaThietBiModalLabel">Xóa thiết bị?</h5>
                <p class="text-secondary small mb-0">
                    Bạn có chắc muốn xóa <strong><?= htmlspecialchars($thietBiCanXoa['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?></strong>?
                    Hành động này không thể hoàn tác.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border px-3">Hủy</a>
                <form method="post" action="<?= htmlspecialchars(baoTriUrl('thiet_bi.php', ['id' => $idCanXoa]), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                    <input type="hidden" name="action" value="xoa_thiet_bi" />
                    <button type="submit" class="btn btn-danger px-3">
                        <i class="bi bi-trash3 me-1"></i>Xóa thiết bị
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
