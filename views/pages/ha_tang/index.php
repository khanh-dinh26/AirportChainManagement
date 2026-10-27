<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/HaTangController.php';

$controller = new HaTangController($conn);
$data = $controller->handle();

$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$url = static function (array $params = []) {
    return BASE_URL . '/index.php?' . http_build_query(array_merge(['page' => 'ha_tang'], $params));
};

/**
 * Ảnh dùng trong trang: trả về URL đầy đủ.
 * Nếu là đường dẫn nội bộ thì kiểm tra tệp có tồn tại thật hay không
 * (thay cho thuộc tính onerror của JavaScript trước đây).
 */
$projectRoot = dirname(__DIR__, 3);

$imageExists = static function (string $path) use ($projectRoot): bool {
    if ($path === '' || preg_match('~^https?://~i', $path)) {
        return true; // Ảnh ngoài hệ thống: không kiểm tra được, giữ nguyên
    }

    return is_file($projectRoot . '/' . ltrim($path, '/'));
};

$imageUrl = static function ($path, $fallback = '') use ($imageExists) {
    $candidates = [
        trim((string) $path),
        trim((string) $fallback),
        'assets/img/san_bay/SGN.jpg', // ảnh dự phòng cuối cùng
    ];

    $path = '';
    foreach ($candidates as $candidate) {
        if ($candidate !== '' && $imageExists($candidate)) {
            $path = $candidate;
            break;
        }
    }
    if ($path === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    return BASE_URL . '/' . ltrim($path, '/');
};

$airportImage = static function ($airport) use ($imageUrl) {
    $maIata = (string) ($airport['ma_iata'] ?? '');

    return $imageUrl(
        $airport['hinh_anh'] ?? '',
        'assets/img/san_bay/' . $maIata . '.jpg'
    );
};

$statusLabels = [
    'Hoat_Dong' => 'Hoạt động',
    'Bao_Tri' => 'Bảo trì',
    'Dong_Cua_Khan_Cap' => 'Đóng cửa khẩn cấp',
    'Len_Lich' => 'Lên lịch',
    'Dang_Phuc_Vu' => 'Đang phục vụ',
    'Cat_Canh' => 'Cất cánh',
    'Ha_Canh' => 'Hạ cánh',
    'Hoan_Thanh' => 'Hoàn thành',
    'Huy' => 'Hủy',
    'Tri_Hoan' => 'Trì hoãn',
];
$statusClasses = [
    'Hoat_Dong' => 'success',
    'Bao_Tri' => 'warning',
    'Dong_Cua_Khan_Cap' => 'danger',
    'Len_Lich' => 'secondary',
    'Dang_Phuc_Vu' => 'warning',
    'Cat_Canh' => 'info',
    'Ha_Canh' => 'primary',
    'Hoan_Thanh' => 'success',
    'Huy' => 'danger',
    'Tri_Hoan' => 'warning',
];
$statusBadge = static function ($status) use ($statusLabels, $statusClasses, $e) {
    $status = (string) $status;
    if ($status === '') {
        return '<span class="badge text-bg-secondary">—</span>';
    }
    $label = $statusLabels[$status] ?? $status;
    $class = $statusClasses[$status] ?? 'secondary';

    return '<span class="badge text-bg-' . $class . '">' . $e($label) . '</span>';
};

$terminalTypeLabels = [
    'Quoc_Noi' => 'Quốc nội',
    'Quoc_Te' => 'Quốc tế',
    'Hon_Hop' => 'Hỗn hợp',
];
$terminalTypeClasses = [
    'Quoc_Noi' => 'success',
    'Quoc_Te' => 'primary',
    'Hon_Hop' => 'info',
];
$terminalTypeBadge = static function ($type) use ($terminalTypeLabels, $terminalTypeClasses, $e) {
    $type = (string) $type;
    $label = $terminalTypeLabels[$type] ?? $type;
    $class = $terminalTypeClasses[$type] ?? 'secondary';

    return '<span class="badge text-bg-' . $class . '">' . $e($label) . '</span>';
};

$capLabels = ['Quoc_Te' => 'CHKQT', 'Quoc_Noi' => 'CHK'];
$capClasses = ['Quoc_Te' => 'primary', 'Quoc_Noi' => 'success'];

$formatDateTime = static function ($value) {
    if (empty($value)) {
        return '—';
    }
    $time = strtotime((string) $value);

    return $time ? date('d/m/Y H:i', $time) : (string) $value;
};
$formatTime = static function ($value) {
    if (empty($value)) {
        return '—';
    }
    $time = strtotime((string) $value);

    return $time ? date('H:i', $time) : (string) $value;
};
$formatDate = static function ($value) {
    if (empty($value)) {
        return '—';
    }
    $time = strtotime((string) $value);

    return $time ? date('d/m/Y', $time) : (string) $value;
};

$selectedAirport = $data['selectedAirport'];
$selectedTerminal = $data['selectedTerminal'];
$terminals = $data['terminals'];
$gates = $data['gates'];
$checkins = $data['checkins'];
$belts = $data['belts'];
$flights = $data['flights'];
$filters = $data['filters'];
$airlines = $data['airlines'];
$quanTri = $data['quanTri'];
$management = $data['management'];
$khu = $data['khu'];
$tab = $data['tab'];
$flash = $data['flash'];

$totalAirports = count($data['airports']);
$totalTerminals = array_sum(array_column($data['airports'], 'so_nha_ga'));
$totalGates = array_sum(array_column($data['airports'], 'so_cua_ra'));
$totalCheckins = array_sum(array_column($data['airports'], 'so_quay_checkin'));
$totalBelts = array_sum(array_column($data['airports'], 'so_bang_chuyen'));

$viewParams = [];
if ($selectedAirport) {
    $viewParams['san_bay'] = $selectedAirport['ma_iata'];
}
if ($selectedTerminal) {
    $viewParams['nha_ga'] = (int) $selectedTerminal['id'];
    $viewParams['khu'] = 'nha_ga';
} else {
    $viewParams['khu'] = 'chuyen_bay';
}
$viewUrl = $url($viewParams);

$airports = $data['airports'];

$pageTitle = 'Quản lý hạ tầng';
$pageHeader = 'Quản lý hạ tầng';
$navActive = 'ha_tang';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Hạ tầng', 'url' => null],
];
if ($selectedAirport) {
    $breadcrumb[] = ['label' => $selectedAirport['ten_ngan'], 'url' => null];
}

ob_start();
?>
<style>
  /* Tắt cuộn mượt để khi mở trang kèm mốc #ht-admin-tabs (hoặc #ht-tabs)
     trình duyệt dừng ngay tại thanh tab, không cuộn từ đầu trang xuống
     (AdminLTE bật scroll-behavior: smooth mặc định) */
  :root {
    scroll-behavior: auto;
  }
  .ht-page {
    --ht-ink: #10233f;
    --ht-muted: #5c6b82;
    --ht-line: rgba(15, 35, 63, .11);
    --ht-intl: #2563eb;
    --ht-dom: #0f9d76;
    --ht-shadow: 0 14px 34px rgba(8, 25, 50, .1);
  }
  [data-bs-theme="dark"] .ht-page {
    --ht-ink: #e7eefc;
    --ht-muted: #a9b7cc;
    --ht-line: rgba(255, 255, 255, .13);
    --ht-shadow: 0 14px 34px rgba(0, 0, 0, .35);
  }
  .ht-page .ht-hero {
    position: relative;
    overflow: hidden;
    padding: 1.75rem;
    border-radius: 24px;
    color: #fff;
    background:
      radial-gradient(circle at 12% 15%, rgba(59, 130, 246, .45), transparent 32%),
      radial-gradient(circle at 88% 85%, rgba(16, 185, 129, .25), transparent 34%),
      linear-gradient(135deg, #0b1f3a 0%, #071426 70%);
    box-shadow: 0 22px 50px rgba(8, 25, 50, .24);
  }
  .ht-page .ht-hero::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
      linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
    background-size: 34px 34px;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, .55), transparent 75%);
  }
  .ht-page .ht-hero > * {
    position: relative;
    z-index: 1;
  }
  .ht-page .ht-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    opacity: .82;
  }
  .ht-page .ht-pulse {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 0 0 rgba(52, 211, 153, .55);
    animation: ht-pulse 2.2s infinite;
  }
  @keyframes ht-pulse {
    0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, .55); }
    70% { box-shadow: 0 0 0 9px rgba(52, 211, 153, 0); }
    100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
  }
  .ht-page .ht-hero__title {
    margin: .55rem 0 .35rem;
    font-size: clamp(1.55rem, 2.6vw, 2.35rem);
    font-weight: 800;
    letter-spacing: -.02em;
  }
  .ht-page .ht-hero__sub {
    max-width: 700px;
    margin: 0;
    color: rgba(255, 255, 255, .74);
    font-size: .95rem;
  }
  .ht-page .ht-hero__stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .65rem;
    margin-top: 1.4rem;
  }
  .ht-page .ht-hero__stat {
    padding: .75rem .9rem;
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 14px;
    background: rgba(255, 255, 255, .075);
    backdrop-filter: blur(8px);
  }
  .ht-page .ht-hero__stat strong {
    display: block;
    font-size: 1.3rem;
    line-height: 1;
  }
  .ht-page .ht-hero__stat span {
    display: block;
    margin-top: .3rem;
    font-size: .7rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .68);
  }
  .ht-page .ht-section-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin: 1.4rem 0 .9rem;
  }
  .ht-page .ht-section-head h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--ht-ink);
  }
  .ht-page .ht-section-head p {
    margin: .2rem 0 0;
    color: var(--ht-muted);
    font-size: .82rem;
  }
  .ht-page .ht-legend {
    display: flex;
    align-items: center;
    gap: .9rem;
    flex-wrap: wrap;
    color: var(--ht-muted);
    font-size: .72rem;
  }
  .ht-page .ht-legend span {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
  }
  .ht-page .ht-legend i {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
  }
  .ht-page .ht-legend .is-intl { background: var(--ht-intl); }
  .ht-page .ht-legend .is-dom { background: var(--ht-dom); }
  .ht-page .ht-airport-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    justify-content: start;
    gap: 12px;
    margin-bottom: 1rem;
  }
  .ht-page .ht-airport {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border: 1px solid var(--ht-line);
    border-radius: 16px;
    color: var(--ht-ink);
    text-decoration: none;
    background: var(--bs-body-bg);
    box-shadow: 0 8px 18px rgba(8, 25, 50, .08);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  }
  .ht-page .ht-airport:hover {
    color: var(--ht-ink);
    border-color: rgba(37, 99, 235, .4);
    box-shadow: 0 14px 26px rgba(8, 25, 50, .16);
    transform: translateY(-3px);
  }
  .ht-page .ht-airport:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
  }
  .ht-page .ht-airport.is-selected {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .2), 0 14px 26px rgba(8, 25, 50, .16);
  }
  .ht-page .ht-airport__media {
    position: relative;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    background: #0f172a;
  }
  .ht-page .ht-airport__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .45s ease;
  }
  .ht-page .ht-airport:hover .ht-airport__media img {
    transform: scale(1.05);
  }
  .ht-page .ht-airport__tag {
    position: absolute;
    top: 7px;
    left: 7px;
    padding: .2rem .38rem;
    border: 1px solid rgba(255, 255, 255, .3);
    border-radius: 999px;
    color: #fff;
    font-size: .52rem;
    font-weight: 800;
    letter-spacing: .05em;
    backdrop-filter: blur(8px);
  }
  .ht-page .ht-airport__tag.is-intl { background: rgba(37, 99, 235, .78); }
  .ht-page .ht-airport__tag.is-dom { background: rgba(15, 157, 118, .78); }
  .ht-page .ht-airport__code {
    position: absolute;
    bottom: 7px;
    left: 7px;
    padding: .16rem .4rem;
    border-radius: 7px;
    color: #fff;
    background: rgba(7, 20, 38, .62);
    backdrop-filter: blur(8px);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .05em;
  }
  .ht-page .ht-airport__check {
    position: absolute;
    top: 7px;
    right: 7px;
    display: none;
    align-items: center;
    justify-content: center;
    width: 21px;
    height: 21px;
    border-radius: 50%;
    color: #1d4ed8;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
  }
  .ht-page .ht-airport.is-selected .ht-airport__check { display: inline-flex; }
  .ht-page .ht-airport__body {
    flex: 0 0 auto;
    min-width: 0;
    padding: .45rem .55rem .5rem;
    border-top: 1px solid var(--ht-line);
  }
  .ht-page .ht-airport__name {
    display: block;
    overflow: hidden;
    color: var(--ht-ink);
    font-size: .82rem;
    font-weight: 800;
    line-height: 1.25;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .ht-page .ht-airport__city {
    display: block;
    overflow: hidden;
    margin-top: .1rem;
    color: var(--ht-muted);
    font-size: .64rem;
    line-height: 1.25;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .ht-page .ht-airport-detail {
    overflow: hidden;
    border: 0;
    border-radius: 22px;
    box-shadow: var(--ht-shadow);
  }
  .ht-page .ht-airport-detail__image {
    position: relative;
    min-height: 210px;
    height: 100%;
    background: #0f172a;
  }
  .ht-page .ht-airport-detail__image img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .ht-page .ht-airport-detail__image::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 40%, rgba(4, 12, 24, .7));
  }
  .ht-page .ht-airport-detail__code {
    position: absolute;
    z-index: 1;
    bottom: 14px;
    left: 16px;
    padding: .35rem .55rem;
    border: 1px solid rgba(255, 255, 255, .25);
    border-radius: 9px;
    color: #fff;
    background: rgba(7, 20, 38, .55);
    backdrop-filter: blur(8px);
    font-size: 1.2rem;
    font-weight: 900;
    letter-spacing: .08em;
  }
  .ht-page .ht-mini-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(66px, 1fr));
    gap: .5rem;
    min-width: min(100%, 360px);
  }
  .ht-page .ht-mini-stat {
    padding: .7rem .5rem;
    border: 1px solid var(--ht-line);
    border-radius: 13px;
    background: var(--bs-tertiary-bg);
    text-align: center;
  }
  .ht-page .ht-mini-stat strong {
    display: block;
    color: var(--ht-ink);
    font-size: 1.1rem;
    line-height: 1;
  }
  .ht-page .ht-mini-stat span {
    display: block;
    margin-top: .3rem;
    color: var(--ht-muted);
    font-size: .64rem;
    text-transform: uppercase;
    letter-spacing: .04em;
  }
  .ht-page .ht-tabs {
    gap: .35rem;
    border-bottom: 1px solid var(--ht-line);
    scroll-margin-top: 4.75rem;
  }
  .ht-page .ht-admin-tabs {
    scroll-margin-top: 4.75rem;
  }
  .ht-page .ht-tabs .nav-link {
    border: 0;
    border-radius: 12px 12px 0 0;
    color: var(--ht-muted);
    font-weight: 700;
    padding: .8rem 1.05rem;
    cursor: pointer;
    user-select: none;
    transition: none;
  }
  /* Tab Chuyến bay / Nhà ga dùng radio + label (thuần CSS, không tải lại
     trang) nên vị trí cuộn được giữ nguyên khi chuyển tab. */
  .ht-page .ht-khu-radio {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
  }
  .ht-page #ht-khu-chuyen-bay:checked ~ .ht-tabs label[for="ht-khu-chuyen-bay"],
  .ht-page #ht-khu-nha-ga:checked ~ .ht-tabs label[for="ht-khu-nha-ga"] {
    color: var(--ht-ink);
    background: var(--bs-body-bg);
    box-shadow: inset 0 -3px 0 var(--bs-primary);
  }
  .ht-page #ht-khu-chuyen-bay:focus-visible ~ .ht-tabs label[for="ht-khu-chuyen-bay"],
  .ht-page #ht-khu-nha-ga:focus-visible ~ .ht-tabs label[for="ht-khu-nha-ga"] {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
  }
  .ht-page .ht-tab-panel {
    display: none;
  }
  .ht-page #ht-khu-chuyen-bay:checked ~ .ht-tab-content #ht-panel-chuyen-bay,
  .ht-page #ht-khu-nha-ga:checked ~ .ht-tab-content #ht-panel-nha-ga {
    display: block;
  }
  .ht-page .ht-tab-content {
    padding-top: 1rem;
  }
  .ht-page .ht-panel {
    overflow: hidden;
    border: 0;
    border-radius: 18px;
    box-shadow: var(--ht-shadow);
  }
  .ht-page .ht-panel .card-header {
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid var(--ht-line);
    background: transparent;
  }
  .ht-page .ht-panel .card-header h3 {
    margin: 0;
    color: var(--ht-ink);
    font-size: 1rem;
    font-weight: 800;
  }
  .ht-page .ht-panel .card-header p {
    margin: .2rem 0 0;
    color: var(--ht-muted);
    font-size: .78rem;
  }
  .ht-page .ht-filter {
    padding: .9rem;
    border: 1px solid var(--ht-line);
    border-radius: 14px;
    background: var(--bs-tertiary-bg);
  }
  .ht-page .ht-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .42rem .7rem;
    border: 1px solid var(--ht-line);
    border-radius: 999px;
    color: var(--ht-muted);
    background: var(--bs-body-bg);
    font-size: .78rem;
    font-weight: 700;
    text-decoration: none;
  }
  .ht-page .ht-chip span {
    color: inherit;
    opacity: .66;
  }
  .ht-page .ht-chip:hover,
  .ht-page .ht-chip.is-active {
    border-color: var(--bs-primary);
    color: #fff;
    background: var(--bs-primary);
  }
  .ht-page .ht-flight-route {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .82rem;
    font-weight: 700;
    color: var(--ht-ink);
  }
  .ht-page .ht-flight-route i {
    color: var(--ht-muted);
    font-size: .7rem;
  }
  .ht-page .ht-flight-sub {
    display: block;
    margin-top: .15rem;
    color: var(--ht-muted);
    font-size: .68rem;
  }
  .ht-page .ht-terminal-strip {
    display: flex;
    gap: .65rem;
    overflow-x: auto;
    padding-bottom: .2rem;
  }
  .ht-page .ht-terminal-card {
    flex: 0 0 auto;
    min-width: 215px;
    padding: .9rem;
    border: 1px solid var(--ht-line);
    border-radius: 15px;
    color: inherit;
    background: var(--bs-body-bg);
    text-decoration: none;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
  }
  .ht-page .ht-terminal-card:hover {
    color: inherit;
    transform: translateY(-2px);
    border-color: rgba(37, 99, 235, .45);
    box-shadow: 0 10px 22px rgba(8, 25, 50, .08);
  }
  .ht-page .ht-terminal-card.is-active {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
  }
  .ht-page .ht-terminal-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
  }
  .ht-page .ht-terminal-card__code {
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--bs-primary);
  }
  .ht-page .ht-terminal-card__meta {
    display: flex;
    gap: .7rem;
    flex-wrap: wrap;
    margin-top: .55rem;
    color: var(--ht-muted);
    font-size: .68rem;
  }
  .ht-page .ht-diagram {
    position: relative;
    overflow: hidden;
    padding: 1.1rem;
    border: 1px solid var(--ht-line);
    border-radius: 18px;
    background:
      radial-gradient(circle at 5% 0%, rgba(37, 99, 235, .09), transparent 35%),
      var(--bs-tertiary-bg);
  }
  .ht-page .ht-diagram__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .75rem;
  }
  .ht-page .ht-diagram__zone {
    padding: .9rem;
    border: 1px solid var(--ht-line);
    border-radius: 14px;
    background: var(--bs-body-bg);
  }
  .ht-page .ht-diagram__zone h4 {
    display: flex;
    align-items: center;
    gap: .35rem;
    margin: 0 0 .65rem;
    color: var(--ht-muted);
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }
  .ht-page .ht-gates,
  .ht-page .ht-counters,
  .ht-page .ht-belts {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
  }
  .ht-page .ht-gate {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    min-width: 47px;
    padding: .38rem .42rem;
    border: 1px solid rgba(37, 99, 235, .2);
    border-radius: 9px;
    color: #1d4ed8;
    background: rgba(37, 99, 235, .09);
    font-size: .8rem;
    font-weight: 800;
  }
  .ht-page .ht-gate small,
  .ht-page .ht-counter small,
  .ht-page .ht-belt small {
    margin-top: .1rem;
    font-size: .54rem;
    font-weight: 500;
    opacity: .72;
    white-space: nowrap;
  }
  .ht-page .ht-gate.is-maintenance {
    border-color: rgba(217, 119, 6, .25);
    color: #b45309;
    background: rgba(245, 158, 11, .12);
  }
  .ht-page .ht-counter {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .38rem .48rem;
    border: 1px solid rgba(15, 157, 118, .2);
    border-radius: 8px;
    color: #0f766e;
    background: rgba(15, 157, 118, .09);
    font-size: .74rem;
    font-weight: 700;
  }
  .ht-page .ht-counter.is-maintenance,
  .ht-page .ht-belt.is-maintenance {
    border-color: rgba(217, 119, 6, .25);
    color: #b45309;
    background: rgba(245, 158, 11, .12);
  }
  .ht-page .ht-belt {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .38rem .48rem;
    border: 1px solid rgba(124, 58, 237, .2);
    border-radius: 8px;
    color: #6d28d9;
    background: rgba(124, 58, 237, .09);
    font-size: .74rem;
    font-weight: 700;
  }
  [data-bs-theme="dark"] .ht-page .ht-gate {
    border-color: rgba(147, 197, 253, .25);
    color: #93c5fd;
    background: rgba(37, 99, 235, .18);
  }
  [data-bs-theme="dark"] .ht-page .ht-counter {
    border-color: rgba(110, 231, 183, .22);
    color: #6ee7b7;
    background: rgba(15, 157, 118, .18);
  }
  [data-bs-theme="dark"] .ht-page .ht-belt {
    border-color: rgba(196, 181, 253, .22);
    color: #c4b5fd;
    background: rgba(124, 58, 237, .18);
  }
  [data-bs-theme="dark"] .ht-page .ht-gate.is-maintenance,
  [data-bs-theme="dark"] .ht-page .ht-counter.is-maintenance,
  [data-bs-theme="dark"] .ht-page .ht-belt.is-maintenance {
    border-color: rgba(252, 211, 77, .25);
    color: #fcd34d;
    background: rgba(245, 158, 11, .18);
  }
  .ht-page .ht-diagram__legend {
    display: flex;
    flex-wrap: wrap;
    gap: .9rem;
    margin-top: .8rem;
    color: var(--ht-muted);
    font-size: .68rem;
  }
  .ht-page .ht-diagram__legend span {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
  }
  .ht-page .ht-diagram__legend i {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 3px;
  }
  .ht-page .ht-diagram-gallery-block {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ht-line);
  }
  .ht-page .ht-diagram-gallery {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .5rem;
  }
  .ht-page .ht-diagram-thumb {
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--ht-line);
    border-radius: 10px;
    color: var(--ht-ink);
    text-decoration: none;
    background: var(--bs-body-bg);
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  }
  .ht-page .ht-diagram-thumb img {
    width: 100%;
    height: 78px;
    padding: .25rem;
    object-fit: contain;
    background: var(--bs-tertiary-bg);
  }
  .ht-page .ht-diagram-thumb__caption {
    display: -webkit-box;
    min-height: 2.15em;
    padding: .3rem .4rem;
    overflow: hidden;
    border-top: 1px solid var(--ht-line);
    font-size: .63rem;
    font-weight: 700;
    line-height: 1.2;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }
  .ht-page .ht-diagram-thumb__zoom {
    position: absolute;
    top: .25rem;
    right: .25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 7px;
    color: #fff;
    background: rgba(15, 23, 42, .72);
    font-size: .65rem;
    opacity: .7;
    pointer-events: none;
    transition: opacity .18s ease, background .18s ease;
  }
  .ht-page .ht-diagram-thumb:hover {
    border-color: rgba(37, 99, 235, .45);
    box-shadow: 0 8px 18px rgba(8, 25, 50, .1);
    transform: translateY(-1px);
  }
  .ht-page .ht-diagram-thumb:hover .ht-diagram-thumb__zoom,
  .ht-page .ht-diagram-thumb:focus-visible .ht-diagram-thumb__zoom {
    opacity: 1;
    background: var(--bs-primary);
  }
  .ht-page .ht-diagram-thumb:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
  }
  .ht-page .ht-diagram-source {
    margin-top: .55rem;
    color: var(--ht-muted);
    font-size: .7rem;
  }
  .ht-page .ht-diagram-source a {
    color: var(--bs-primary);
    text-decoration: none;
  }
  .ht-page .ht-diagram-source a:hover {
    text-decoration: underline;
  }
  .ht-page .ht-lightbox {
    --ht-lightbox-stage-height: min(72vh, 760px);
  }
  .ht-page .ht-lightbox .modal-content {
    overflow: hidden;
    border-color: var(--ht-line);
  }
  .ht-page .ht-lightbox .modal-header {
    padding: .8rem 1rem;
  }
  .ht-page .ht-lightbox .modal-body {
    padding: 0;
  }
  .ht-page .ht-lightbox-heading {
    min-width: 0;
  }
  .ht-page .ht-lightbox-heading .modal-title {
    font-size: 1rem;
    line-height: 1.3;
  }
  .ht-page .ht-lightbox-viewer {
    position: relative;
    background: #0b1220;
  }
  .ht-page .ht-lightbox-stage {
    height: var(--ht-lightbox-stage-height);
    overflow: auto;
    overscroll-behavior: contain;
    background: #0b1220;
  }
  .ht-page .ht-lightbox-canvas {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 100%;
    min-height: 100%;
    padding: 1rem;
    box-sizing: border-box;
  }
  .ht-page .ht-lightbox-canvas.is-zoomed {
    align-items: flex-start;
    justify-content: flex-start;
  }
  .ht-page .ht-lightbox-canvas img {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: calc(var(--ht-lightbox-stage-height) - 2rem);
    border-radius: 10px;
    background: #fff;
    user-select: none;
  }
  .ht-page .ht-lightbox-canvas img.is-zoomed {
    max-width: none;
    max-height: none;
    border-radius: 0;
  }
  .ht-page .ht-lightbox-nav {
    position: absolute;
    top: 50%;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, .32);
    border-radius: 50%;
    color: #fff;
    background: rgba(15, 23, 42, .72);
    font-size: 1.15rem;
    transform: translateY(-50%);
    transition: background .18s ease, transform .18s ease;
  }
  .ht-page .ht-lightbox-nav:hover {
    background: var(--bs-primary);
    transform: translateY(-50%) scale(1.04);
  }
  .ht-page .ht-lightbox-nav:focus-visible {
    outline: 2px solid #fff;
    outline-offset: 2px;
  }
  .ht-page .ht-lightbox-nav[hidden] {
    display: none;
  }
  .ht-page .ht-lightbox-nav--prev {
    left: .65rem;
  }
  .ht-page .ht-lightbox-nav--next {
    right: .65rem;
  }
  .ht-page .ht-lightbox-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .5rem .8rem;
    padding: .7rem 1rem;
    border-top: 1px solid var(--ht-line);
    background: var(--bs-body-bg);
  }
  .ht-page .ht-lightbox-zoom {
    display: inline-flex;
    align-items: center;
    padding: .2rem;
    border: 1px solid var(--ht-line);
    border-radius: 999px;
    background: var(--bs-tertiary-bg);
  }
  .ht-page .ht-lightbox-zoom button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 30px;
    padding: 0 .45rem;
    border: 0;
    border-radius: 999px;
    color: var(--ht-ink);
    background: transparent;
    font-size: .78rem;
    font-weight: 700;
  }
  .ht-page .ht-lightbox-zoom button:hover:not(:disabled) {
    background: var(--bs-body-bg);
  }
  .ht-page .ht-lightbox-zoom button:disabled {
    opacity: .35;
    cursor: not-allowed;
  }
  .ht-page .ht-lightbox-zoom [data-ht-zoom-label] {
    min-width: 48px;
    font-variant-numeric: tabular-nums;
  }
  .ht-page .ht-lightbox-source {
    flex: 1 1 250px;
    color: var(--ht-muted);
    font-size: .7rem;
    text-align: right;
  }
  .ht-page .ht-lightbox-source a {
    color: var(--bs-primary);
    text-decoration: none;
  }
  .ht-page .ht-lightbox-source a:hover {
    text-decoration: underline;
  }
  .ht-page .ht-empty {
    padding: 2.6rem 1rem;
    border: 1px dashed var(--ht-line);
    border-radius: 18px;
    color: var(--ht-muted);
    text-align: center;
    background: var(--bs-body-bg);
  }
  .ht-page .ht-empty i {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    color: var(--bs-primary);
    background: rgba(13, 110, 253, .1);
    font-size: 1.2rem;
  }
  .ht-page .ht-empty h3 {
    margin: .8rem 0 .3rem;
    color: var(--ht-ink);
    font-size: 1rem;
    font-weight: 800;
  }
  .ht-page .ht-empty p {
    max-width: 560px;
    margin: 0 auto;
    font-size: .82rem;
  }
  .ht-page .ht-table {
    margin-bottom: 0;
    font-size: .8rem;
  }
  .ht-page .ht-table thead th {
    padding: .65rem .75rem;
    color: var(--ht-muted);
    font-size: .64rem;
    font-weight: 800;
    letter-spacing: .07em;
    text-transform: uppercase;
    white-space: nowrap;
    border-bottom-color: var(--ht-line);
  }
  .ht-page .ht-table tbody td {
    padding: .7rem .75rem;
    vertical-align: middle;
    border-bottom-color: var(--ht-line);
  }
  .ht-page .ht-table tbody tr:last-child td { border-bottom: 0; }
  .ht-page .ht-actions {
    display: flex;
    justify-content: flex-end;
    gap: .3rem;
    white-space: nowrap;
  }
  .ht-page .ht-table tbody tr.ht-row-link {
    cursor: default;
  }
  .ht-page .ht-table tbody tr.ht-row-link:hover > td {
    background: rgba(0, 20, 137, .04);
  }
  .ht-page .ht-terminal-link {
    color: var(--ht-ink);
    text-decoration: none;
  }
  .ht-page .ht-terminal-link:hover,
  .ht-page .ht-terminal-link:focus-visible {
    color: var(--bs-primary);
    text-decoration: underline;
    text-underline-offset: 2px;
  }
  .ht-page .ht-resource-count {
    color: inherit;
    text-decoration: underline dotted rgba(37, 99, 235, .55);
    text-underline-offset: 2px;
  }
  .ht-page .ht-resource-count:hover,
  .ht-page .ht-resource-count:focus-visible {
    text-decoration-style: solid;
    text-decoration-color: var(--bs-primary);
  }
  .ht-page .ht-resource-count:hover strong,
  .ht-page .ht-resource-count:focus-visible strong {
    color: var(--bs-primary);
  }
  .ht-page .ht-form-card {
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(37, 99, 235, .22);
    border-left: 4px solid var(--bs-primary);
    border-radius: 14px;
    background: rgba(37, 99, 235, .045);
  }
  .ht-page .ht-form-card h4 {
    margin: 0 0 .75rem;
    color: var(--ht-ink);
    font-size: .92rem;
    font-weight: 800;
  }
  .ht-page .ht-form-card .form-label {
    margin-bottom: .25rem;
    color: var(--ht-muted);
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
  }
  .ht-page .ht-upload-preview {
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 0;
    padding: .4rem;
    border: 1px solid var(--ht-line);
    border-radius: 10px;
    background: var(--bs-body-bg);
  }
  .ht-page .ht-upload-preview img {
    flex: 0 0 auto;
    width: 96px;
    height: 64px;
    border-radius: 7px;
    object-fit: contain;
    background: var(--bs-tertiary-bg);
  }
  .ht-page .ht-upload-preview span {
    min-width: 0;
    overflow: hidden;
    color: var(--ht-muted);
    font-size: .7rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .ht-page .ht-admin-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-bottom: 1rem;
  }
  .ht-page .ht-admin-tabs a {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .5rem .75rem;
    border: 1px solid var(--ht-line);
    border-radius: 10px;
    color: var(--ht-muted);
    background: var(--bs-body-bg);
    font-size: .78rem;
    font-weight: 700;
    text-decoration: none;
  }
  .ht-page .ht-admin-tabs a:hover,
  .ht-page .ht-admin-tabs a.is-active {
    border-color: var(--bs-primary);
    color: #fff;
    background: var(--bs-primary);
  }
  .ht-page .ht-soft-label {
    display: block;
    color: var(--ht-muted);
    font-size: .68rem;
    line-height: 1.4;
  }
  .ht-page .ht-soft-label strong {
    color: var(--ht-ink);
    font-weight: 700;
  }
  @media (max-width: 1199.98px) {
    .ht-page .ht-airport-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
  }
  @media (max-width: 991.98px) {
    .ht-page .ht-airport-detail__image { min-height: 190px; }
    .ht-page .ht-diagram__grid { grid-template-columns: 1fr; }
    .ht-page .ht-mini-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .ht-page .ht-diagram-gallery { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .ht-page .ht-lightbox { --ht-lightbox-stage-height: calc(100vh - 190px); }
  }
  @media (max-width: 767.98px) {
    .ht-page .ht-hero { padding: 1.25rem; border-radius: 18px; }
    .ht-page .ht-hero__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ht-page .ht-airport-grid { grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); gap: 10px; }
    .ht-page .ht-airport__name { font-size: .78rem; }
    .ht-page .ht-section-head { align-items: flex-start; flex-direction: column; }
    .ht-page .ht-mini-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ht-page .ht-diagram-gallery { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ht-page .ht-diagram-thumb img { height: 70px; }
    .ht-page .ht-lightbox .modal-header { padding: .7rem .8rem; }
    .ht-page .ht-lightbox-toolbar { padding: .6rem .7rem; }
    .ht-page .ht-lightbox-source { flex-basis: 100%; text-align: left; }
    .ht-page .ht-lightbox-nav { width: 38px; height: 38px; font-size: 1rem; }
  }
  @media (max-width: 575.98px) {
    .ht-page .ht-airport-grid { grid-template-columns: repeat(auto-fill, minmax(126px, 1fr)); }
  }

  /* Typography: tăng cỡ chữ toàn bộ nội dung trang Hạ tầng */
  .ht-page {
    font-size: 1.05rem;
  }
  .ht-page .ht-eyebrow { font-size: .82rem; }
  .ht-page .ht-hero__title { font-size: clamp(1.75rem, 2.8vw, 2.55rem); }
  .ht-page .ht-hero__sub { font-size: 1.06rem; }
  .ht-page .ht-hero__stat strong { font-size: 1.5rem; }
  .ht-page .ht-hero__stat span { font-size: .82rem; }
  .ht-page .ht-section-head h3 { font-size: 1.22rem; }
  .ht-page .ht-section-head p { font-size: .96rem; }
  .ht-page .ht-legend { font-size: .85rem; }
  .ht-page .ht-airport__tag { font-size: .66rem; }
  .ht-page .ht-airport__code { font-size: .85rem; }
  .ht-page .ht-airport__name { font-size: .95rem; }
  .ht-page .ht-airport__city { font-size: .78rem; }
  .ht-page .ht-airport-detail__code { font-size: 1.35rem; }
  .ht-page .ht-mini-stat strong { font-size: 1.3rem; }
  .ht-page .ht-mini-stat span { font-size: .78rem; }
  .ht-page .ht-tabs .nav-link { font-size: 1.02rem; }
  .ht-page .ht-panel .card-header h3 { font-size: 1.2rem; }
  .ht-page .ht-panel .card-header p { font-size: .92rem; }
  .ht-page .ht-chip { font-size: .92rem; }
  .ht-page .ht-flight-route { font-size: .95rem; }
  .ht-page .ht-flight-route i { font-size: .82rem; }
  .ht-page .ht-flight-sub { font-size: .82rem; }
  .ht-page .ht-terminal-card__code { font-size: .82rem; }
  .ht-page .ht-terminal-card__meta { font-size: .8rem; }
  .ht-page .ht-diagram__zone h4 { font-size: .8rem; }
  .ht-page .ht-gate { font-size: .92rem; }
  .ht-page .ht-gate small,
  .ht-page .ht-counter small,
  .ht-page .ht-belt small { font-size: .68rem; }
  .ht-page .ht-counter,
  .ht-page .ht-belt { font-size: .86rem; }
  .ht-page .ht-diagram__legend { font-size: .82rem; }
  .ht-page .ht-diagram-thumb__caption { font-size: .78rem; }
  .ht-page .ht-diagram-thumb__zoom { font-size: .74rem; }
  .ht-page .ht-diagram-source { font-size: .84rem; }
  .ht-page .ht-lightbox-heading .modal-title { font-size: 1.15rem; }
  .ht-page .ht-lightbox-zoom button { font-size: .9rem; }
  .ht-page .ht-lightbox-source { font-size: .84rem; }
  .ht-page .ht-empty h3 { font-size: 1.2rem; }
  .ht-page .ht-empty p { font-size: .96rem; }
  .ht-page .ht-table { font-size: .94rem; }
  .ht-page .ht-table thead th { font-size: .78rem; }
  .ht-page .ht-form-card h4 { font-size: 1.08rem; }
  .ht-page .ht-form-card .form-label { font-size: .8rem; }
  .ht-page .ht-upload-preview span { font-size: .84rem; }
  .ht-page .ht-admin-tabs a { font-size: .92rem; }
  .ht-page .ht-soft-label { font-size: .8rem; }
  .ht-page .form-control,
  .ht-page .form-select { font-size: 1.02rem; }
  .ht-page .btn { font-size: 1rem; }
  .ht-page .btn-sm { font-size: .9rem; }
  .ht-page .badge { font-size: .82rem; }
</style>

<div class="ht-page">
  <?php if ($flash): ?>
    <div class="alert alert-<?= $e($flash['type']) ?>" role="alert">
      <i class="bi bi-info-circle me-1"></i><?= $e($flash['message']) ?>
    </div>
  <?php endif; ?>

  <div class="ht-hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        <div class="ht-eyebrow"><span class="ht-pulse"></span> Hạ tầng cảng hàng không</div>
        <h2 class="ht-hero__title">Mạng lưới <?= $totalAirports ?> sân bay</h2>
        <p class="ht-hero__sub">
          Chọn một sân bay trên bản đồ để xem chuyến bay, nhà ga và sơ đồ khai thác.
          <?= $quanTri ? 'Bạn đang ở chế độ quản trị danh mục hạ tầng.' : 'Dữ liệu được đồng bộ từ danh mục sân bay, nhà ga và tài nguyên khai thác.' ?>
        </p>
      </div>
    </div>

    <div class="ht-hero__stats">
      <div class="ht-hero__stat"><strong><?= $totalAirports ?></strong><span>Sân bay</span></div>
      <div class="ht-hero__stat"><strong><?= $totalTerminals ?></strong><span>Nhà ga</span></div>
      <div class="ht-hero__stat"><strong><?= $totalGates ?></strong><span>Cửa ra máy bay</span></div>
      <div class="ht-hero__stat"><strong><?= $totalCheckins ?></strong><span>Quầy check-in</span></div>
    </div>
  </div>

  <div class="ht-section-head">
    <div>
      <h3>Bản đồ chuỗi sân bay</h3>
      <p>Nhấn vào một ô sân bay để mở khu vực khai thác bên dưới.</p>
    </div>
    <div class="ht-legend">
      <span><i class="is-intl"></i> Quốc tế · CHKQT</span>
      <span><i class="is-dom"></i> Quốc nội · CHK</span>
    </div>
  </div>

  <div class="ht-airport-grid">
    <?php foreach ($airports as $airport): ?>
      <?php
      $isSelected = $selectedAirport && (int) $selectedAirport['id'] === (int) $airport['id'];
      $isInternational = $airport['phan_cap'] === 'Quoc_Te';
      $airportLinkParams = ['san_bay' => $airport['ma_iata'], 'khu' => 'chuyen_bay'];
      if ($quanTri) {
          unset($airportLinkParams['khu']);
          $airportLinkParams['quan_tri'] = '1';
          $airportLinkParams['tab'] = $tab;
      }
      ?>
      <a
        href="<?= $e($url($airportLinkParams)) ?>"
        class="ht-airport <?= $isSelected ? 'is-selected' : '' ?>"
        title="<?= $e($airport['ten_san_bay'] . ' · ' . $airport['vi_tri']) ?>"
        aria-label="Chọn sân bay <?= $e($airport['ten_san_bay']) ?>"
        <?= $isSelected ? 'aria-current="true"' : '' ?>
      >
        <span class="ht-airport__media">
          <img
            src="<?= $e($airportImage($airport)) ?>"
            alt="<?= $e($airport['ten_san_bay']) ?>"
            loading="lazy"
          />
          <span class="ht-airport__tag <?= $isInternational ? 'is-intl' : 'is-dom' ?>">
            <?= $e($capLabels[$airport['phan_cap']] ?? $airport['phan_cap']) ?>
          </span>
          <span class="ht-airport__code"><?= $e($airport['ma_iata']) ?></span>
          <span class="ht-airport__check"><i class="bi bi-check2"></i></span>
        </span>
        <span class="ht-airport__body">
          <span class="ht-airport__name"><?= $e($airport['ten_ngan']) ?></span>
          <span class="ht-airport__city"><?= $e($airport['ten_san_bay']) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($selectedAirport): ?>
    <div class="card ht-airport-detail mb-3">
      <div class="row g-0">
        <div class="col-md-3">
          <div class="ht-airport-detail__image">
            <img
              src="<?= $e($airportImage($selectedAirport)) ?>"
              alt="<?= $e($selectedAirport['ten_san_bay']) ?>"
            />
            <span class="ht-airport-detail__code"><?= $e($selectedAirport['ma_iata']) ?></span>
          </div>
        </div>
        <div class="col-md-9">
          <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
              <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                  <h3 class="h4 mb-0 fw-bold"><?= $e($selectedAirport['ten_san_bay']) ?></h3>
                  <?= $statusBadge($selectedAirport['trang_thai']) ?>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                  <span class="ht-soft-label">
                    <i class="bi bi-geo-alt me-1"></i><?= $e($selectedAirport['vi_tri']) ?>
                  </span>
                  <span class="badge text-bg-<?= $selectedAirport['phan_cap'] === 'Quoc_Te' ? 'primary' : 'success' ?>">
                    <?= $e($selectedAirport['phan_cap'] === 'Quoc_Te' ? 'Cảng hàng không quốc tế' : 'Cảng hàng không quốc nội') ?>
                  </span>
                </div>
                <?php if (!empty($selectedAirport['mo_ta'])): ?>
                  <p class="text-secondary mb-0 small"><?= $e($selectedAirport['mo_ta']) ?></p>
                <?php endif; ?>
              </div>
              <div class="ht-mini-stats">
                <div class="ht-mini-stat"><strong><?= (int) $selectedAirport['so_nha_ga'] ?></strong><span>Nhà ga</span></div>
                <div class="ht-mini-stat"><strong><?= (int) $selectedAirport['so_cua_ra'] ?></strong><span>Cửa ra</span></div>
                <div class="ht-mini-stat"><strong><?= (int) $selectedAirport['so_quay_checkin'] ?></strong><span>Check-in</span></div>
                <div class="ht-mini-stat"><strong><?= (int) $selectedAirport['so_bang_chuyen'] ?></strong><span>Băng chuyền</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($quanTri): ?>
    <div class="ht-section-head mt-4">
      <div>
        <h3>Quản trị hạ tầng</h3>
        <p>Thêm, sửa hoặc xóa sân bay, nhà ga và tài nguyên khai thác.</p>
      </div>
      <a href="<?= $e($viewUrl) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Quay lại
      </a>
    </div>
    <?php require __DIR__ . '/_quan_tri.php'; ?>
  <?php elseif ($selectedAirport): ?>
    <input
      type="radio"
      name="ht-khu"
      id="ht-khu-chuyen-bay"
      class="ht-khu-radio"
      value="chuyen_bay"
      <?= $khu === 'chuyen_bay' ? 'checked' : '' ?>
    />
    <input
      type="radio"
      name="ht-khu"
      id="ht-khu-nha-ga"
      class="ht-khu-radio"
      value="nha_ga"
      <?= $khu === 'nha_ga' ? 'checked' : '' ?>
    />
    <ul class="nav nav-tabs ht-tabs mt-4" id="ht-tabs">
      <li class="nav-item">
        <label class="nav-link" for="ht-khu-chuyen-bay">
          <i class="bi bi-airplane-engines me-1"></i> Chuyến bay
        </label>
      </li>
      <li class="nav-item">
        <label class="nav-link" for="ht-khu-nha-ga">
          <i class="bi bi-diagram-3 me-1"></i> Nhà ga &amp; sơ đồ
        </label>
      </li>
    </ul>
    <div class="ht-tab-content">
      <div class="ht-tab-panel" id="ht-panel-chuyen-bay">
        <?php require __DIR__ . '/_flights.php'; ?>
      </div>
      <div class="ht-tab-panel" id="ht-panel-nha-ga">
        <?php require __DIR__ . '/_terminal.php'; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="ht-empty mt-4">
      <i class="bi bi-hand-index-thumb"></i>
      <h3>Chọn một sân bay để bắt đầu</h3>
      <p>Nhấn vào một ô sân bay phía trên để xem chuyến bay, danh sách nhà ga và sơ đồ khai thác.</p>
    </div>
  <?php endif; ?>
</div>

<?php
/* ------------------------------------------------------------
 * Hộp thoại xác nhận xóa (thuần PHP + CSS, không dùng JS)
 * Dữ liệu được HaTangController lưu vào session ở lần gửi đầu.
 * ------------------------------------------------------------ */
$xacNhanXoa = $_SESSION['ha_tang_xac_nhan_xoa'] ?? null;
unset($_SESSION['ha_tang_xac_nhan_xoa']);

if (is_array($xacNhanXoa) && !empty($xacNhanXoa['action'])):
    $xoaAction = (string) $xacNhanXoa['action'];
    $xoaNoiDung = [
        'xoa_san_bay' => [
            'tieu_de' => 'Xóa sân bay này?',
            'mo_ta'   => 'Toàn bộ nhà ga và tài nguyên trực thuộc (cửa ra, quầy check-in, băng chuyền) cũng sẽ bị xóa. Không thể xóa nếu sân bay đang có chuyến bay tham chiếu. Thao tác này không thể hoàn tác.',
        ],
        'xoa_nha_ga' => [
            'tieu_de' => 'Xóa nhà ga này?',
            'mo_ta'   => 'Các cửa ra, quầy check-in và băng chuyền trực thuộc cũng sẽ bị xóa. Thao tác này không thể hoàn tác.',
        ],
        'xoa_cua_ra' => [
            'tieu_de' => 'Xóa cửa ra máy bay này?',
            'mo_ta'   => 'Các chuyến bay đang dùng cửa ra này sẽ được bỏ liên kết. Thao tác này không thể hoàn tác.',
        ],
        'xoa_quay' => [
            'tieu_de' => 'Xóa quầy check-in này?',
            'mo_ta'   => 'Các chuyến bay đang dùng quầy này sẽ được bỏ liên kết. Thao tác này không thể hoàn tác.',
        ],
        'xoa_bang_chuyen' => [
            'tieu_de' => 'Xóa băng chuyền hành lý này?',
            'mo_ta'   => 'Các chuyến bay đang dùng băng chuyền này sẽ được bỏ liên kết. Thao tác này không thể hoàn tác.',
        ],
    ];
    $xoaThongTin = $xoaNoiDung[$xoaAction] ?? [
        'tieu_de' => 'Xóa bản ghi này?',
        'mo_ta'   => 'Các chuyến bay đang tham chiếu sẽ được bỏ liên kết. Thao tác này không thể hoàn tác.',
    ];
    $xoaHuyUrl = (string) ($_SERVER['REQUEST_URI'] ?? $url(['quan_tri' => '1', 'tab' => 'san_bay']));
?>
<div
  class="modal fade is-open"
  id="xacNhanXoaModal"
  tabindex="-1"
  aria-labelledby="xacNhanXoaLabel"
  aria-hidden="false"
>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger mb-3" style="width: 58px; height: 58px;">
          <i class="bi bi-trash3 fs-4"></i>
        </span>
        <h5 class="mb-2" id="xacNhanXoaLabel"><?= $e($xoaThongTin['tieu_de']) ?></h5>
        <p class="text-secondary small mb-0"><?= $e($xoaThongTin['mo_ta']) ?></p>
      </div>
      <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
        <a href="<?= $e($xoaHuyUrl) ?>" class="btn btn-light border px-3">Hủy</a>
        <form method="post" action="<?= $e(BASE_URL . '/index.php?page=ha_tang') ?>" class="d-inline">
          <?php foreach ($xacNhanXoa as $tenTruong => $giaTri): ?>
            <?php if (in_array($tenTruong, ['csrf', 'xac_nhan'], true) || is_array($giaTri)) { continue; } ?>
            <input type="hidden" name="<?= $e($tenTruong) ?>" value="<?= $e($giaTri) ?>" />
          <?php endforeach; ?>
          <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>" />
          <input type="hidden" name="xac_nhan" value="1" />
          <button type="submit" class="btn btn-danger px-3">
            <i class="bi bi-trash3 me-1"></i>Xóa
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
