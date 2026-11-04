<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'cap_nhat_ticket') {
        $baoTriController->capNhatTrangThaiTicket();
    } elseif ($action === 'store_ticket') {
        $baoTriController->storeTicket();
    } elseif ($action === 'xoa_ticket') {
        $baoTriController->xoaTicket();
    }
}

if (!isset($tickets, $thong_ke)) {
    $data     = $baoTriController->getIndexData();
    $tickets  = $data['tickets'];
    $thong_ke = $data['thong_ke'];
}

if (!isset($thiet_bi_list)) {
    $thiet_bi_list = $baoTriController->getThietBiList();
}

// Nhóm thiết bị theo sân bay để hiển thị trong modal
$thietBiTheoSanBay = [];
$sanBayThietBi    = []; // tên hiển thị của sân bay
$sanBayTen        = []; // tên ngắn
$sanBayCuaThietBi = []; // id thiết bị => id sân bay

foreach ($thiet_bi_list as $tb) {
    $idSanBay = (int) ($tb['id_san_bay'] ?? 0);
    $thietBiTheoSanBay[$idSanBay][] = $tb;
    $sanBayCuaThietBi[(int) $tb['id']] = $idSanBay;

    if (!isset($sanBayThietBi[$idSanBay])) {
        $tenNgan  = trim((string) ($tb['ten_ngan'] ?? ''));
        $tenDayDu = trim((string) ($tb['ten_san_bay'] ?? ''));

        $sanBayTen[$idSanBay] = $tenNgan !== '' ? $tenNgan : ($tenDayDu !== '' ? $tenDayDu : 'Khác');
        $sanBayThietBi[$idSanBay] = $sanBayTen[$idSanBay];
    }
}

// Thiết bị được chọn sẵn khi mở modal tạo phiếu từ trang chi tiết thiết bị.
$selectedThietBiId = (int) ($_GET['id_thiet_bi'] ?? 0);

// Bộ lọc sân bay cho form tạo phiếu (thuần PHP/HTML, không dùng JavaScript).
$coLocSanBay   = array_key_exists('loc_san_bay', $_GET);
$sanBayDangLoc = $coLocSanBay ? (int) $_GET['loc_san_bay'] : 0;

// Mở từ trang chi tiết thiết bị thì tự chọn sân bay của thiết bị đó.
if (!$coLocSanBay && $selectedThietBiId > 0 && isset($sanBayCuaThietBi[$selectedThietBiId])) {
    $sanBayDangLoc = $sanBayCuaThietBi[$selectedThietBiId];
}

if ($sanBayDangLoc > 0 && !isset($thietBiTheoSanBay[$sanBayDangLoc])) {
    $sanBayDangLoc = 0;
}

// Thiết bị được chọn sẵn phải thuộc sân bay đang lọc, nếu không thì bỏ chọn.
if ($selectedThietBiId > 0) {
    if (!isset($sanBayCuaThietBi[$selectedThietBiId])
        || ($sanBayDangLoc > 0 && $sanBayCuaThietBi[$selectedThietBiId] !== $sanBayDangLoc)) {
        $selectedThietBiId = 0;
    }
}

// Danh sách thiết bị hiển thị trong select: chỉ còn thiết bị của sân bay đang lọc.
$thietBiHienThi = $sanBayDangLoc > 0
    ? [$sanBayDangLoc => $thietBiTheoSanBay[$sanBayDangLoc]]
    : $thietBiTheoSanBay;

$nhanSanBayDangLoc = $sanBayDangLoc > 0 ? $sanBayThietBi[$sanBayDangLoc] : 'Tất cả sân bay';
$soThietBiDangLoc  = $sanBayDangLoc > 0 ? count($thietBiTheoSanBay[$sanBayDangLoc]) : count($thiet_bi_list);

$pageTitle  = 'Bảo trì & khắc phục';
$pageHeader = 'Bảo trì & khắc phục';
$navActive    = 'bao_tri';
$navSubActive = 'tickets';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Bảo trì', 'url' => null],
];

$statusLabels = [
    'Moi'        => 'Mới',
    'Dang_Xu_Ly' => 'Đang xử lý',
    'Hoan_Thanh' => 'Hoàn thành',
];

$statusBadgeClasses = [
    'Moi'        => 'text-bg-info',
    'Dang_Xu_Ly' => 'text-bg-primary',
    'Hoan_Thanh' => 'text-bg-success',
];

$priorityLabels = [
    'Thap'       => 'Thấp',
    'Trung_Binh' => 'Trung bình',
    'Khan_Cap'   => 'Khẩn cấp',
];

$priorityBadgeClasses = [
    'Thap'       => 'text-bg-success',
    'Trung_Binh' => 'text-bg-warning',
    'Khan_Cap'   => 'text-bg-danger',
];

$priorityIcons = [
    'Thap'       => 'bi-check-circle',
    'Trung_Binh' => 'bi-exclamation-circle',
    'Khan_Cap'   => 'bi-exclamation-octagon',
];

$loaiThietBiLabels = [
    'Den_Tin_Hieu'       => 'Đèn tín hiệu',
    'Thiet_Bi_Dan_Duong' => 'Thiết bị dẫn đường',
    'Xe_Thang'           => 'Xe thang',
    'Xe_Keo'             => 'Xe kéo',
    'Khac'               => 'Khác',
];

$trangThaiThietBiLabels = [
    'Tot'     => 'Tốt',
    'Bao_Tri' => 'Đang bảo trì',
    'Hong'    => 'Hỏng',
];

// Tự mở modal khi được yêu cầu (?open=create) hoặc vừa có lỗi nhập liệu
$modalOpen  = isset($_GET['open']) && $_GET['open'] === 'create';
$modalError = null;
if ($modalOpen && !empty($_SESSION['flash_error'])) {
    $modalError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}



// Giá trị mặc định phòng khi view được nạp trực tiếp
$thong_ke = array_merge([
    'dang_mo'          => 0,
    'khan_cap'         => 0,
    'hoan_thanh_thang' => 0,
    'thiet_bi_bao_tri' => 0,
], $thong_ke ?? []);

$totalTickets = count($tickets);

/* ------------------------------------------------------------
 * Lọc danh sách phiếu phía máy chủ (thay cho bộ lọc JavaScript cũ).
 * Tham số GET: q, san_bay, trang_thai, muc_do, thiet_bi.
 * ------------------------------------------------------------ */
$ticketQ        = trim((string) ($_GET['q'] ?? ''));
$ticketStatus   = trim((string) ($_GET['trang_thai'] ?? 'dang_mo'));
$ticketPriority = trim((string) ($_GET['muc_do'] ?? ''));
$ticketThietBi  = (int) ($_GET['thiet_bi'] ?? 0);
$ticketSanBay   = (int) ($_GET['san_bay'] ?? 0);

// Danh sách sân bay cho bộ lọc (chỉ những sân bay đang có thiết bị).
$sanBayLocOptions = [];
foreach ($thiet_bi_list as $tb) {
    $idSb = (int) ($tb['id_san_bay'] ?? 0);
    if ($idSb <= 0 || isset($sanBayLocOptions[$idSb])) {
        continue;
    }

    $tenDayDu = trim((string) ($tb['ten_san_bay'] ?? ''));

    $sanBayLocOptions[$idSb] = $tenDayDu !== '' ? $tenDayDu : 'SB-' . $idSb;
}
asort($sanBayLocOptions, SORT_NATURAL | SORT_FLAG_CASE);

// Sân bay lọc không hợp lệ thì bỏ qua.
if ($ticketSanBay > 0 && !isset($sanBayLocOptions[$ticketSanBay])) {
    $ticketSanBay = 0;
}

// Thiết bị đang lọc phải thuộc sân bay đang lọc, nếu không thì bỏ lọc thiết bị.
if ($ticketThietBi > 0 && $ticketSanBay > 0) {
    $sanBayCuaThietBiDangLoc = (int) ($sanBayCuaThietBi[$ticketThietBi] ?? 0);
    if ($sanBayCuaThietBiDangLoc > 0 && $sanBayCuaThietBiDangLoc !== $ticketSanBay) {
        $ticketThietBi = 0;
    }
}

$ticketThietBiName = '';
if ($ticketThietBi > 0) {
    foreach ($thiet_bi_list as $tb) {
        if ((int) $tb['id'] === $ticketThietBi) {
            $ticketThietBiName = (string) $tb['ten_thiet_bi'];
            break;
        }
    }
}

$totalTicketsGoc = $totalTickets;

$statusCounts = array_fill_keys(array_keys($statusLabels), 0);
foreach ($tickets as $ticketItem) {
    $statusKey = (string) ($ticketItem['trang_thai_xu_ly'] ?? '');
    if (array_key_exists($statusKey, $statusCounts)) {
        $statusCounts[$statusKey]++;
    }
}

$tickets = array_values(array_filter($tickets, static function (array $ticket) use ($ticketQ, $ticketStatus, $ticketPriority, $ticketThietBi, $ticketSanBay): bool {
    if ($ticketSanBay > 0 && (int) ($ticket['id_san_bay'] ?? 0) !== $ticketSanBay) {
        return false;
    }
    if ($ticketThietBi > 0 && (int) $ticket['id_thiet_bi'] !== $ticketThietBi) {
        return false;
    }
    if ($ticketQ !== '') {
        $maPhieu = '#BT-' . sprintf('%03d', (int) $ticket['id']);
        $haystack = $maPhieu . ' ' . $ticket['ma_iata'] . ' ' . $ticket['ten_thiet_bi'] . ' ' . $ticket['mo_ta_su_co'];
        if (mb_stripos($haystack, $ticketQ, 0, 'UTF-8') === false) {
            return false;
        }
    }
    if ($ticketStatus === 'dang_mo' && (string) $ticket['trang_thai_xu_ly'] === 'Hoan_Thanh') {
        return false;
    }
    if ($ticketStatus !== '' && $ticketStatus !== 'dang_mo' && (string) $ticket['trang_thai_xu_ly'] !== $ticketStatus) {
        return false;
    }
    if ($ticketPriority !== '' && (string) $ticket['muc_do'] !== $ticketPriority) {
        return false;
    }
    return true;
}));

$ticketDeleteId = (int) ($_GET['xac_nhan_xoa'] ?? 0);
$ticketCanXoa = null;
if ($ticketDeleteId > 0) {
    foreach ($tickets as $ticketItem) {
        if ((int) $ticketItem['id'] === $ticketDeleteId) {
            $ticketCanXoa = $ticketItem;
            break;
        }
    }
}

$ticketChiTietId = (int) ($_GET['chi_tiet'] ?? 0);
$ticketChiTiet = null;
if ($ticketChiTietId > 0) {
    foreach ($tickets as $ticketItem) {
        if ((int) $ticketItem['id'] === $ticketChiTietId) {
            $ticketChiTiet = $ticketItem;
            break;
        }
    }
}

$soPhieuHienThi = count($tickets);

// Phân trang danh sách phiếu: 4 phiếu mỗi trang.
$soPhieuMoiTrang  = 4;
$tongTrang        = max(1, (int) ceil($soPhieuHienThi / $soPhieuMoiTrang));
$trangHienTai     = max(1, (int) ($_GET['trang'] ?? 1));
$trangHienTai     = min($trangHienTai, $tongTrang);
$viTriBatDau      = ($trangHienTai - 1) * $soPhieuMoiTrang;
$ticketsTrenTrang = array_slice($tickets, $viTriBatDau, $soPhieuMoiTrang);
$tuPhieu          = $soPhieuHienThi > 0 ? $viTriBatDau + 1 : 0;
$denPhieu         = min($viTriBatDau + $soPhieuMoiTrang, $soPhieuHienThi);

$urlDongModalQuery = array_filter([
    'q'        => $ticketQ,
    'san_bay'  => $ticketSanBay > 0 ? $ticketSanBay : '',
    'muc_do'   => $ticketPriority,
    'thiet_bi' => $ticketThietBi > 0 ? $ticketThietBi : '',
    'trang'    => $trangHienTai > 1 ? $trangHienTai : '',
], static fn($v) => $v !== '');
$urlDongModalQuery['trang_thai'] = $ticketStatus;
$urlDongModal = baoTriUrl('index.php', $urlDongModalQuery);

$ticketQuickUrl = static function (string $status) use ($ticketQ, $ticketSanBay, $ticketPriority, $ticketThietBi): string {
    return baoTriUrl('index.php', array_filter([
        'q'          => $ticketQ,
        'san_bay'    => $ticketSanBay > 0 ? $ticketSanBay : '',
        'trang_thai' => $status,
        'muc_do'     => $ticketPriority,
        'thiet_bi'   => $ticketThietBi > 0 ? $ticketThietBi : '',
    ], static fn($v) => $v !== ''));
};

// Link lọc sân bay trong modal tạo phiếu: giữ nguyên bộ lọc danh sách và trạng thái mở modal.
$ticketFormLocUrl = static function (int $sanBayId) use ($ticketQ, $ticketSanBay, $ticketStatus, $ticketPriority, $ticketThietBi, $trangHienTai): string {
    $query = array_filter([
        'q'           => $ticketQ,
        'san_bay'     => $ticketSanBay > 0 ? $ticketSanBay : '',
        'muc_do'      => $ticketPriority,
        'thiet_bi'    => $ticketThietBi > 0 ? $ticketThietBi : '',
        'loc_san_bay' => $sanBayId > 0 ? $sanBayId : '',
        'trang'       => $trangHienTai > 1 ? $trangHienTai : '',
    ], static fn($v) => $v !== '');

    // Giữ cả trạng thái rỗng (Tất cả trạng thái), không để rơi về mặc định "đang mở".
    $query['trang_thai'] = $ticketStatus;
    $query['open']       = 'create';

    return baoTriUrl('index.php', $query);
};

// Link phân trang: giữ nguyên bộ lọc hiện tại.
$ticketPageUrl = static function (int $page) use ($ticketQ, $ticketSanBay, $ticketStatus, $ticketPriority, $ticketThietBi): string {
    $query = array_filter([
        'q'        => $ticketQ,
        'san_bay'  => $ticketSanBay > 0 ? $ticketSanBay : '',
        'muc_do'   => $ticketPriority,
        'thiet_bi' => $ticketThietBi > 0 ? $ticketThietBi : '',
        'trang'    => $page > 1 ? $page : '',
    ], static fn($v) => $v !== '');
    $query['trang_thai'] = $ticketStatus;

    return baoTriUrl('index.php', $query);
};

ob_start();
?>
<?php require __DIR__ . '/_flash.php'; ?>

<div class="maint-page maint-page--ticket">
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= (int) $thong_ke['dang_mo'] ?></h3>
                <p>Phiếu đang mở</p>
            </div>
            <i class="small-box-icon bi bi-clipboard-check"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3><?= (int) $thong_ke['khan_cap'] ?></h3>
                <p>Sự cố khẩn cấp</p>
            </div>
            <i class="small-box-icon bi bi-exclamation-octagon"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= (int) $thong_ke['hoan_thanh_thang'] ?></h3>
                <p>Hoàn thành trong tháng</p>
            </div>
            <i class="small-box-icon bi bi-check2-circle"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= (int) $thong_ke['thiet_bi_bao_tri'] ?></h3>
                <p>Thiết bị đang bảo trì</p>
            </div>
            <i class="small-box-icon bi bi-tools"></i>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách phiếu bảo trì</h3>
        <div class="card-tools d-flex flex-wrap align-items-center gap-1">
            <span class="badge text-bg-secondary" title="Tổng số phiếu"><?= $totalTicketsGoc ?> phiếu</span>
            <a href="#taoPhieuModal" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Tạo phiếu bảo trì
            </a>
        </div>
    </div>

    <?php if ($ticketThietBi > 0): ?>
        <div class="card-body pb-0">
            <div class="alert alert-info d-flex flex-wrap align-items-center gap-2 mb-0 py-2">
                <i class="bi bi-funnel-fill"></i>
                <div class="small">
                    Đang xem phiếu của thiết bị
                    <strong><?= htmlspecialchars($ticketThietBiName !== '' ? $ticketThietBiName : 'TB-' . sprintf('%03d', $ticketThietBi), ENT_QUOTES, 'UTF-8') ?></strong>.
                </div>
                <a href="<?= htmlspecialchars(baoTriUrl('chi_tiet_thiet_bi.php', ['id' => $ticketThietBi]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary ms-auto">
                    <i class="bi bi-eye me-1"></i>Xem thiết bị
                </a>
                <a href="<?= htmlspecialchars(baoTriUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Bỏ lọc
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($totalTicketsGoc > 0): ?>
        <div class="card-body pb-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="small text-secondary fw-semibold text-uppercase">Lọc nhanh</span>
                <a href="<?= htmlspecialchars($ticketQuickUrl('dang_mo'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $ticketStatus === 'dang_mo' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Đang mở <span class="badge text-bg-secondary ms-1"><?= $statusCounts['Moi'] + $statusCounts['Dang_Xu_Ly'] ?></span>
                </a>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <a href="<?= htmlspecialchars($ticketQuickUrl($key), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm <?= $ticketStatus === $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= $label ?> <span class="badge text-bg-secondary ms-1"><?= $statusCounts[$key] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="<?= htmlspecialchars(baoTriUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 mb-3">
                <?php if ($ticketThietBi > 0): ?>
                    <input type="hidden" name="thiet_bi" value="<?= (int) $ticketThietBi ?>">
                <?php endif; ?>
                <div class="col-12 col-lg-4">
                    <label for="ticketSearch" class="visually-hidden">Tìm kiếm phiếu</label>
                    <input type="search" id="ticketSearch" name="q" value="<?= htmlspecialchars($ticketQ, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Mã phiếu, thiết bị hoặc mô tả...">
                </div>
                <div class="col-6 col-lg-3">
                    <label for="ticketAirportFilter" class="visually-hidden">Sân bay</label>
                    <select id="ticketAirportFilter" name="san_bay" class="form-select">
                        <option value=""<?= $ticketSanBay === 0 ? ' selected' : '' ?>>Tất cả sân bay</option>
                        <?php foreach ($sanBayLocOptions as $idSb => $nhanSb): ?>
                            <option value="<?= (int) $idSb ?>"<?= $ticketSanBay === (int) $idSb ? ' selected' : '' ?>><?= htmlspecialchars($nhanSb, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="ticketStatusFilter" class="visually-hidden">Trạng thái</label>
                    <select id="ticketStatusFilter" name="trang_thai" class="form-select">
                        <option value="dang_mo"<?= $ticketStatus === 'dang_mo' ? ' selected' : '' ?>>Phiếu đang mở</option>
                        <option value="">Tất cả trạng thái</option>
                        <?php foreach ($statusLabels as $key => $label): ?>
                            <option value="<?= $key ?>"<?= $ticketStatus === $key ? ' selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="ticketPriorityFilter" class="visually-hidden">Mức độ</label>
                    <select id="ticketPriorityFilter" name="muc_do" class="form-select">
                        <option value="">Tất cả mức độ</option>
                        <?php foreach ($priorityLabels as $key => $label): ?>
                            <option value="<?= $key ?>"<?= $ticketPriority === $key ? ' selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-1 d-grid">
                    <button type="submit" class="btn btn-outline-primary" title="Tìm kiếm" aria-label="Tìm kiếm">
                        <i class="bi bi-search"></i><span class="d-lg-none ms-1">Tìm</span>
                    </button>
                </div>
            </form>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-secondary mb-2">
                <span>
                    Hiển thị <strong><?= $tuPhieu ?>–<?= $denPhieu ?></strong> / <strong><?= $soPhieuHienThi ?></strong> phiếu
                    <?php if ($ticketSanBay > 0 && isset($sanBayLocOptions[$ticketSanBay])): ?>
                        · <span class="badge text-bg-light border"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($sanBayLocOptions[$ticketSanBay], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
                <a href="<?= htmlspecialchars(baoTriUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-body maint-ticket-list" id="ticketTable">
        <?php if (!empty($ticketsTrenTrang)): ?>
            <?php foreach ($ticketsTrenTrang as $ticket): ?>
                <?php
                $maPhieu = '#BT-' . sprintf('%03d', (int) $ticket['id']);
                $ngayTaoTimestamp = strtotime($ticket['ngay_tao']);
                $ngayTao = $ngayTaoTimestamp !== false ? date('d/m/Y H:i', $ngayTaoTimestamp) : 'Thời gian không hợp lệ';
                $mucDo = $ticket['muc_do'];
                $trangThai = $ticket['trang_thai_xu_ly'];
                $priorityLabel = $priorityLabels[$mucDo] ?? htmlspecialchars($mucDo, ENT_QUOTES, 'UTF-8');
                $priorityClass = $priorityBadgeClasses[$mucDo] ?? 'text-bg-secondary';
                $priorityIcon = $priorityIcons[$mucDo] ?? 'bi-circle';
                $statusLabel = $statusLabels[$trangThai] ?? htmlspecialchars($trangThai, ENT_QUOTES, 'UTF-8');
                $statusClass = $statusBadgeClasses[$trangThai] ?? 'text-bg-secondary';
                $thoiGianCho = $ngayTaoTimestamp !== false ? time() - $ngayTaoTimestamp : 0;
                $hienThiThoiGianCho = $trangThai === 'Moi' && $thoiGianCho > 86400;
                if ($hienThiThoiGianCho) {
                    $soNgayCho = (int) floor($thoiGianCho / 86400);
                    $soGioCho = (int) floor(($thoiGianCho % 86400) / 3600);
                    $statusLabel = 'Chờ ' . $soNgayCho . ' ngày' . ($soGioCho > 0 ? ' ' . $soGioCho . ' giờ' : '');
                }
                $chiTietQuery = array_filter([
                    'q'        => $ticketQ,
                    'san_bay'  => $ticketSanBay > 0 ? $ticketSanBay : '',
                    'muc_do'   => $ticketPriority,
                    'thiet_bi' => $ticketThietBi > 0 ? $ticketThietBi : '',
                    'trang'    => $trangHienTai > 1 ? $trangHienTai : '',
                    'chi_tiet' => (int) $ticket['id'],
                ], static fn($v) => $v !== '');
                $chiTietQuery['trang_thai'] = $ticketStatus;
                $chiTietUrl = baoTriUrl('index.php', $chiTietQuery);
                ?>
                <article class="maint-ticket-card"
                         data-ticket-search="<?= htmlspecialchars($maPhieu . ' ' . $ticket['ten_thiet_bi'] . ' ' . $ticket['mo_ta_su_co'], ENT_QUOTES, 'UTF-8') ?>"
                         data-ticket-status="<?= htmlspecialchars($trangThai, ENT_QUOTES, 'UTF-8') ?>"
                         data-ticket-priority="<?= htmlspecialchars($mucDo, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="maint-ticket-card__header">
                        <div>
                            <span class="maint-ticket-card__code"><?= $maPhieu ?></span>
                            <div class="maint-ticket-card__date">
                                <i class="bi bi-clock" aria-hidden="true"></i><?= $ngayTao ?>
                            </div>
                        </div>
                        <div class="maint-ticket-card__badges">
                            <span class="badge <?= $priorityClass ?>">
                                <i class="bi <?= $priorityIcon ?> me-1" aria-hidden="true"></i><?= $priorityLabel ?>
                            </span>
                            <?php if ($hienThiThoiGianCho): ?>
                                <span class="badge text-bg-warning" title="Phiếu vẫn đang ở trạng thái mới">
                                    <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="maint-ticket-card__content">
                        <div class="maint-ticket-card__field">
                            <span class="maint-ticket-card__label">Sân bay</span>
                            <div class="maint-ticket-card__value">
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                <?= htmlspecialchars(trim((string) ($ticket['ten_san_bay'] ?? '')) ?: 'Chưa xác định', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <div class="maint-ticket-card__field maint-ticket-card__field--incident">
                            <span class="maint-ticket-card__label">Thiết bị &amp; sự cố</span>
                            <a href="<?= htmlspecialchars(baoTriUrl('chi_tiet_thiet_bi.php', ['id' => (int) $ticket['id_thiet_bi']]), ENT_QUOTES, 'UTF-8') ?>"
                               class="maint-ticket-card__equipment">
                                <i class="bi bi-tools" aria-hidden="true"></i>
                                <?= htmlspecialchars((string) $ticket['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <p class="maint-ticket-card__description"><?= htmlspecialchars((string) $ticket['mo_ta_su_co'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    <div class="maint-ticket-card__footer">
                        <a href="<?= htmlspecialchars($chiTietUrl, ENT_QUOTES, 'UTF-8') ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i>Xem chi tiết
                        </a>
                        <?php if ($trangThai !== 'Hoan_Thanh'): ?>
                            <form action="<?= baoTriUrl('index.php') ?>" method="POST" class="maint-ticket-card__status-form">
                                <label class="visually-hidden" for="ticketStatus<?= (int) $ticket['id'] ?>">Cập nhật trạng thái <?= $maPhieu ?></label>
                                <input type="hidden" name="action" value="cap_nhat_ticket">
                                <input type="hidden" name="id_ticket" value="<?= (int) $ticket['id'] ?>">
                                <select id="ticketStatus<?= (int) $ticket['id'] ?>" name="trang_thai_xu_ly" class="form-select form-select-sm">
                                    <option value="Moi" <?= $trangThai === 'Moi' ? 'selected' : '' ?>>Mới</option>
                                    <option value="Dang_Xu_Ly" <?= $trangThai === 'Dang_Xu_Ly' ? 'selected' : '' ?>>Đang xử lý</option>
                                    <option value="Hoan_Thanh">Hoàn thành</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-success" title="Lưu trạng thái phiếu <?= $maPhieu ?>" aria-label="Lưu trạng thái phiếu <?= $maPhieu ?>">
                                    <i class="bi bi-check2" aria-hidden="true"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="maint-ticket-card__completed">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Đã hoàn thành
                            </span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php elseif ($totalTicketsGoc > 0): ?>
            <div class="text-center py-5">
                <div class="fw-semibold">Không tìm thấy phiếu phù hợp với bộ lọc.</div>
                <div class="small text-secondary mb-3">Hãy xóa bớt điều kiện lọc để xem toàn bộ danh sách.</div>
                <a href="<?= htmlspecialchars(baoTriUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="maint-empty-icon"><i class="bi bi-clipboard-x"></i></div>
                <div class="fw-semibold">Chưa có phiếu bảo trì nào</div>
                <div class="small text-secondary mb-3">Tạo phiếu đầu tiên để ghi nhận sự cố và theo dõi xử lý.</div>
                <a href="#taoPhieuModal" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tạo phiếu bảo trì
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($tongTrang > 1): ?>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="small text-secondary">Trang <strong><?= $trangHienTai ?></strong> / <?= $tongTrang ?></div>
            <?= baoTriPagination($trangHienTai, $tongTrang, $ticketPageUrl) ?>
        </div>
    <?php endif; ?>
</div>
</div>

<?php if ($ticketCanXoa !== null): ?>
<?php
$ticketCanXoaMa = '#BT-' . sprintf('%03d', (int) $ticketCanXoa['id']);
$urlHuyXoaQuery = array_filter([
    'q'        => $ticketQ,
    'san_bay'  => $ticketSanBay > 0 ? $ticketSanBay : '',
    'muc_do'   => $ticketPriority,
    'thiet_bi' => $ticketThietBi > 0 ? $ticketThietBi : '',
    'trang'    => $trangHienTai > 1 ? $trangHienTai : '',
    'chi_tiet' => (int) $ticketCanXoa['id'],
], static fn($v) => $v !== '');
$urlHuyXoaQuery['trang_thai'] = $ticketStatus;
$urlHuyXoa = baoTriUrl('index.php', $urlHuyXoaQuery);
?>
<div class="modal fade is-open" id="xoaTicketModal" tabindex="-1" aria-labelledby="xoaTicketModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger mb-3" style="width: 58px; height: 58px;">
                    <i class="bi bi-trash3 fs-4"></i>
                </span>
                <h5 class="mb-2" id="xoaTicketModalLabel">Xóa phiếu bảo trì?</h5>
                <p class="text-secondary small mb-0">
                    Bạn có chắc muốn xóa <strong><?= $ticketCanXoaMa ?></strong> của thiết bị
                    <strong><?= htmlspecialchars((string) $ticketCanXoa['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?></strong>?
                    Hành động này không thể hoàn tác.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <a href="<?= htmlspecialchars($urlHuyXoa, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border px-3">Hủy</a>
                <form method="post" action="<?= htmlspecialchars(baoTriUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                    <input type="hidden" name="action" value="xoa_ticket">
                    <input type="hidden" name="id_ticket" value="<?= (int) $ticketCanXoa['id'] ?>">
                    <input type="hidden" name="giu_q" value="<?= htmlspecialchars($ticketQ, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="giu_san_bay" value="<?= $ticketSanBay ?>">
                    <input type="hidden" name="giu_muc_do" value="<?= htmlspecialchars($ticketPriority, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="giu_thiet_bi" value="<?= $ticketThietBi ?>">
                    <input type="hidden" name="giu_trang_thai" value="<?= htmlspecialchars($ticketStatus, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="giu_trang" value="<?= $trangHienTai ?>">
                    <button type="submit" class="btn btn-danger px-3">
                        <i class="bi bi-trash3 me-1"></i>Xóa phiếu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($ticketChiTiet !== null): ?>
<?php
$chiTietMaPhieu = '#BT-' . sprintf('%03d', (int) $ticketChiTiet['id']);
$chiTietAnhThuc = trim((string) ($ticketChiTiet['hinh_anh_minh_hoa'] ?? ''));
$chiTietAnhUrl = $chiTietAnhThuc !== '' ? baoTriImageUrl($chiTietAnhThuc) : '';
$chiTietMucDo = (string) $ticketChiTiet['muc_do'];
$chiTietTrangThai = (string) $ticketChiTiet['trang_thai_xu_ly'];
$chiTietNgayHoanThanh = trim((string) ($ticketChiTiet['ngay_hoan_thanh'] ?? ''));
$chiTietThoiGianHoanThanh = $chiTietNgayHoanThanh !== '' ? strtotime($chiTietNgayHoanThanh) : false;
?>
<div class="modal fade maint-modal maint-modal--ticket maint-ticket-detail is-open" id="chiTietPhieuModal" tabindex="-1" aria-labelledby="chiTietPhieuModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-icon"><i class="bi bi-clipboard-pulse"></i></span>
                    <div>
                        <div class="small text-secondary text-uppercase fw-semibold">Hồ sơ sự cố</div>
                        <h5 class="modal-title mb-0 fs-5" id="chiTietPhieuModalLabel">Chi tiết phiếu bảo trì</h5>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="acv-close" aria-label="Đóng">&times;</a>
            </div>
            <div class="modal-body">
                <div class="maint-ticket-detail__heading">
                    <div class="maint-ticket-detail__identity">
                        <span class="maint-ticket-detail__code"><?= $chiTietMaPhieu ?></span>
                        <h3><?= htmlspecialchars((string) $ticketChiTiet['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="small text-secondary">
                            <i class="bi bi-clock me-1" aria-hidden="true"></i><?= date('d/m/Y H:i', strtotime($ticketChiTiet['ngay_tao'])) ?>
                        </div>
                    </div>
                    <div class="maint-ticket-detail__badges">
                        <span class="badge <?= $priorityBadgeClasses[$chiTietMucDo] ?? 'text-bg-secondary' ?>">
                            <i class="bi <?= $priorityIcons[$chiTietMucDo] ?? 'bi-circle' ?> me-1" aria-hidden="true"></i>
                            <?= $priorityLabels[$chiTietMucDo] ?? htmlspecialchars($chiTietMucDo, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="badge <?= $statusBadgeClasses[$chiTietTrangThai] ?? 'text-bg-secondary' ?>">
                            <?= $statusLabels[$chiTietTrangThai] ?? htmlspecialchars($chiTietTrangThai, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <section class="maint-ticket-detail__section maint-ticket-detail__section--description">
                    <h6><i class="bi bi-card-text me-2" aria-hidden="true"></i>Mô tả sự cố</h6>
                    <p class="maint-ticket-detail__description"><?= nl2br(htmlspecialchars((string) $ticketChiTiet['mo_ta_su_co'], ENT_QUOTES, 'UTF-8')) ?></p>
                </section>

                <section class="maint-ticket-detail__section maint-ticket-detail__section--info">
                    <h6><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Thông tin phiếu</h6>
                    <div class="maint-ticket-detail__info">
                        <div>
                            <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            <span>Sân bay</span>
                            <strong><?= htmlspecialchars(trim((string) ($ticketChiTiet['ten_san_bay'] ?? '')) ?: 'Chưa xác định', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div>
                            <i class="bi bi-tools" aria-hidden="true"></i>
                            <span>Thiết bị</span>
                            <strong><?= htmlspecialchars((string) $ticketChiTiet['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <?php if ($chiTietTrangThai === 'Hoan_Thanh'): ?>
                            <div>
                                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                                <span>Hoàn thành lúc</span>
                                <strong><?= $chiTietThoiGianHoanThanh !== false ? date('d/m/Y H:i', $chiTietThoiGianHoanThanh) : 'Chưa ghi nhận thời gian' ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="maint-ticket-detail__section maint-ticket-detail__section--image">
                    <h6><i class="bi bi-image me-2" aria-hidden="true"></i>Ảnh sự cố</h6>
                    <?php if ($chiTietAnhUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($chiTietAnhUrl, ENT_QUOTES, 'UTF-8') ?>"
                             alt="Ảnh sự cố của phiếu <?= $chiTietMaPhieu ?>"
                             class="maint-ticket-detail__image" loading="lazy">
                    <?php else: ?>
                        <div class="maint-ticket-detail__no-image">
                            <i class="bi bi-image me-2" aria-hidden="true"></i>Phiếu này chưa có ảnh sự cố được tải lên.
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            <div class="modal-footer">
                <?php if ($chiTietTrangThai !== 'Hoan_Thanh'): ?>
                    <?php
                    $xoaChiTietQuery = array_filter([
                        'q'            => $ticketQ,
                        'san_bay'      => $ticketSanBay > 0 ? $ticketSanBay : '',
                        'muc_do'       => $ticketPriority,
                        'thiet_bi'     => $ticketThietBi > 0 ? $ticketThietBi : '',
                        'trang'        => $trangHienTai > 1 ? $trangHienTai : '',
                        'xac_nhan_xoa' => (int) $ticketChiTiet['id'],
                    ], static fn($v) => $v !== '');
                    $xoaChiTietQuery['trang_thai'] = $ticketStatus;
                    $xoaChiTietUrl = baoTriUrl('index.php', $xoaChiTietQuery);
                    ?>
                    <a href="<?= htmlspecialchars($xoaChiTietUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-danger me-auto">
                        <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Xóa phiếu
                    </a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($urlDongModal, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary px-3">
                    <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Đóng
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!--begin::Modal tạo phiếu bảo trì-->
<div class="modal fade maint-modal maint-modal--ticket<?= $modalOpen ? ' is-open' : '' ?>" id="taoPhieuModal" tabindex="-1" aria-labelledby="taoPhieuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <form action="<?= htmlspecialchars(baoTriUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="store_ticket">
                <input type="hidden" name="loc_san_bay" value="<?= (int) $sanBayDangLoc ?>">
                <div class="modal-header ps-3 pe-5 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="modal-icon">
                            <i class="bi bi-tools"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-0 fs-5" id="taoPhieuModalLabel">Tạo phiếu bảo trì</h5>
                            <div class="small text-secondary">Ghi nhận sự cố và mức độ ưu tiên xử lý</div>
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

                    <div class="maint-form-section"><i class="bi bi-clipboard-pulse"></i> Thông tin sự cố</div>

                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="mb-2">
                                <div class="form-label fw-semibold mb-1">
                                    <i class="bi bi-airplane-engines me-1"></i>Sân bay
                                </div>
                                <details class="maint-airport-picker">
                                    <summary class="maint-airport-summary">
                                        <span class="maint-airport-summary-icon"><i class="bi bi-geo-alt-fill"></i></span>
                                        <span class="maint-airport-summary-text">
                                            <span class="maint-airport-summary-value"><?= htmlspecialchars($nhanSanBayDangLoc, ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                        <span class="badge text-bg-light maint-airport-summary-count"><?= $soThietBiDangLoc ?> thiết bị</span>
                                        <i class="bi bi-chevron-down maint-airport-summary-caret"></i>
                                    </summary>
                                    <div class="maint-airport-menu">
                                        <a href="<?= htmlspecialchars($ticketFormLocUrl(0), ENT_QUOTES, 'UTF-8') ?>"
                                           class="maint-airport-option<?= $sanBayDangLoc === 0 ? ' is-active' : '' ?>">
                                            <span class="maint-airport-option-code"><i class="bi bi-grid-3x3-gap-fill"></i></span>
                                            <span class="maint-airport-option-name">Tất cả sân bay</span>
                                            <span class="maint-airport-option-count"><?= count($thiet_bi_list) ?></span>
                                            <i class="bi bi-check2 maint-airport-option-check"></i>
                                        </a>
                                        <?php foreach ($thietBiTheoSanBay as $idSanBay => $dsThietBi): ?>
                                            <a href="<?= htmlspecialchars($ticketFormLocUrl((int) $idSanBay), ENT_QUOTES, 'UTF-8') ?>"
                                               class="maint-airport-option<?= $sanBayDangLoc === (int) $idSanBay ? ' is-active' : '' ?>"
                                               title="<?= htmlspecialchars((string) ($dsThietBi[0]['ten_san_bay'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="maint-airport-option-code"><i class="bi bi-geo-alt"></i></span>
                                                <span class="maint-airport-option-name"><?= htmlspecialchars($sanBayTen[$idSanBay] ?? 'Khác', ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="maint-airport-option-count"><?= count($dsThietBi) ?></span>
                                                <i class="bi bi-check2 maint-airport-option-check"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                                <label for="modal_id_thiet_bi" class="form-label fw-semibold mt-2">
                                    Thiết bị gặp sự cố <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-body-tertiary"><i class="bi bi-hdd-stack"></i></span>
                                    <select name="id_thiet_bi" id="modal_id_thiet_bi" class="form-select form-select-sm" required>
                                        <option value="">-- Chọn thiết bị --</option>
                                        <?php foreach ($thietBiHienThi as $idSanBay => $dsThietBi): ?>
                                            <optgroup label="<?= htmlspecialchars($sanBayThietBi[$idSanBay], ENT_QUOTES, 'UTF-8') ?>">
                                                <?php foreach ($dsThietBi as $tb): ?>
                                                    <?php
                                                    $loaiThietBi = $loaiThietBiLabels[$tb['loai_thiet_bi']] ?? str_replace('_', ' ', $tb['loai_thiet_bi']);
                                                    $trangThaiThietBi = $trangThaiThietBiLabels[$tb['trang_thai']] ?? $tb['trang_thai'];
                                                    $nhanThietBi = $tb['ten_thiet_bi'] . ' — ' . $loaiThietBi . ' (' . $trangThaiThietBi . ')';
                                                    ?>
                                                    <option value="<?= (int) $tb['id'] ?>"<?= $selectedThietBiId === (int) $tb['id'] ? ' selected' : '' ?>><?= htmlspecialchars($nhanThietBi, ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if (empty($thietBiTheoSanBay)): ?>
                                    <div class="form-text text-danger mt-1">
                                        Chưa có thiết bị.
                                        <a href="<?= htmlspecialchars(baoTriUrl('thiet_bi.php') . '#themThietBiModal', ENT_QUOTES, 'UTF-8') ?>">Thêm thiết bị</a> trước khi tạo phiếu.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-2">
                                <label for="modal_mo_ta_su_co" class="form-label fw-semibold mb-0">
                                    Mô tả sự cố <span class="text-danger">*</span>
                                </label>
                                <textarea name="mo_ta_su_co" id="modal_mo_ta_su_co" class="form-control form-control-sm mt-1" rows="3"
                                          maxlength="1000"
                                          required></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold">Mức độ nghiêm trọng <span class="text-danger">*</span></label>
                                <div class="btn-group w-100 severity-group maint-severity" role="group" aria-label="Mức độ nghiêm trọng">
                                    <input type="radio" class="btn-check" name="muc_do" id="mucDoThap" value="Thap" checked>
                                    <label class="btn btn-outline-success flex-fill" for="mucDoThap" title="Không ảnh hưởng ngay đến khai thác">
                                        <i class="bi bi-check-circle me-1"></i>Thấp
                                    </label>

                                    <input type="radio" class="btn-check" name="muc_do" id="mucDoTrungBinh" value="Trung_Binh">
                                    <label class="btn btn-outline-warning flex-fill" for="mucDoTrungBinh" title="Cần xử lý trong thời gian sớm nhất">
                                        <i class="bi bi-exclamation-circle me-1"></i>Trung bình
                                    </label>

                                    <input type="radio" class="btn-check" name="muc_do" id="mucDoKhanCap" value="Khan_Cap">
                                    <label class="btn btn-outline-danger flex-fill" for="mucDoKhanCap" title="Ảnh hưởng trực tiếp đến an toàn khai thác">
                                        <i class="bi bi-exclamation-octagon me-1"></i>Khẩn cấp
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="maint-form-section"><i class="bi bi-image"></i> Minh chứng</div>
                            <div class="maint-image-upload">
                                <div class="maint-image-upload-head">
                                    <i class="bi bi-image"></i>
                                    <span class="fw-semibold">Chọn ảnh</span>
                                </div>
                                <label for="modal_anh_tai_len" class="visually-hidden">Chọn ảnh minh họa</label>
                                <input type="file" name="anh_tai_len" id="modal_anh_tai_len" class="form-control form-control-sm"
                                       accept="image/png, image/jpeg, image/jpg, image/gif, image/webp">
                            </div>
                            <div class="maint-image-preview d-none" id="modal_anh_preview_wrap">
                                <img id="modal_anh_preview" alt="Ảnh minh chứng đã chọn">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 d-flex flex-wrap justify-content-between gap-2 px-3 py-2">
                    <div class="d-flex gap-2 ms-auto">
                        <button type="submit" class="btn btn-sm btn-danger px-3">
                            <i class="bi bi-send me-1"></i> Gửi yêu cầu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal tạo phiếu bảo trì-->

<script>
(function () {
    var input = document.getElementById('modal_anh_tai_len');
    var wrap  = document.getElementById('modal_anh_preview_wrap');
    var img   = document.getElementById('modal_anh_preview');
    if (!input || !wrap || !img) {
        return;
    }

    var objectUrl = null;

    function xoaAnhXemTruoc() {
        if (objectUrl !== null) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        img.removeAttribute('src');
        wrap.classList.add('d-none');
    }

    input.addEventListener('change', function () {
        xoaAnhXemTruoc();

        var file = input.files && input.files[0];
        if (!file || (file.type !== '' && file.type.indexOf('image/') !== 0)) {
            return;
        }

        objectUrl = URL.createObjectURL(file);
        img.src = objectUrl;
        wrap.classList.remove('d-none');
    });

    window.addEventListener('pagehide', xoaAnhXemTruoc);
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
