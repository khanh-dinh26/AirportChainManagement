<?php
/**
 * ============================================================
 * POPUP: XEM CHI TIẾT CHUYẾN BAY — views/pages/chuyen_bay/chi_tiet.php
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

$data = (new ChuyenBayController($conn))->handleFlightDetail();
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$dateTime = static function ($value) use ($escape) {
    if ($value === null || $value === '') {
        return '—';
    }
    $parsed = DateTime::createFromFormat('Y-m-d H:i:s', (string) $value);

    return $escape($parsed ? $parsed->format('d/m/Y H:i') : (string) $value);
};

$flight = $data['flight'] ?? null;
$delayHistory = $data['delayHistory'] ?? [];
$statusLabels = [
    'Dang_Mo' => 'Đang mở',
    'Cat_Canh' => 'Cất cánh',
    'Ha_Canh' => 'Hạ cánh',
    'Delay' => 'Trễ chuyến',
    'Huy' => 'Hủy chuyến',
];
$statusClasses = [
    'Dang_Mo' => 'success',
    'Cat_Canh' => 'primary',
    'Ha_Canh' => 'warning',
    'Delay' => 'warning',
    'Huy' => 'danger',
];

// Trễ chuyến chỉ hiển thị cho chuyến sát ngày; chuyến tương lai xa coi là điều chỉnh lịch bay.
$displayStatus = $flight['trang_thai_tong'] ?? '';
$displayLabel = $statusLabels[$displayStatus] ?? $displayStatus;
$displayClass = $statusClasses[$displayStatus] ?? 'secondary';
if ($displayStatus === 'Delay' && $flight && substr((string) $flight['gio_du_kien'], 0, 10) > substr((string) $data['serverNow'], 0, 10)) {
    $displayLabel = 'Điều chỉnh lịch bay';
    $displayClass = 'info';
}


// Tổng thời gian trễ (phút) + số phút đã quá hạn chờ xác nhận.
$totalDelayMinutes = (int) ($data['totalDelayMinutes'] ?? 0);
$overdueMinutes = (int) ($data['overdueMinutes'] ?? 0);
$isAirborne = in_array($displayStatus, ['Cat_Canh', 'Ha_Canh'], true);

// Quy ước thời gian:
//  - gio_du_kien / gio_thuc_te là giờ cất cánh / hạ cánh hiện hành của chuyến (đã gồm delay).
//  - Chưa cất cánh: giờ gốc = giờ hiện hành - tổng delay; giờ dự kiến = giờ hiện hành + thời gian quá hạn.
//  - Đã cất cánh: gio_du_kien là giờ cất cánh thực tế (lúc bấm nút), gio_thuc_te là giờ hạ cánh dự kiến.
$stdText = '—';
$etdText = '—';
$arrivalText = '—';
$delayedArrivalText = '—';
$effectiveLandingTs = null;
if ($flight) {
    $depTs = strtotime((string) $flight['gio_du_kien']);
    $arrTs = $flight['gio_thuc_te'] ? strtotime((string) $flight['gio_thuc_te']) : null;
    if ($isAirborne) {
        $stdTs = $depTs;
        $etdTs = $depTs;
        $staTs = $arrTs;
        $etaTs = $arrTs;
    } else {
        $stdTs = $depTs - ($totalDelayMinutes * 60);
        $etdTs = $depTs + ($overdueMinutes * 60);
        $staTs = $arrTs !== null ? $arrTs - ($totalDelayMinutes * 60) : null;
        $etaTs = $arrTs !== null ? $arrTs + ($overdueMinutes * 60) : null;
    }
    $stdText = date('d/m/Y H:i', $stdTs);
    $etdText = date('d/m/Y H:i', $etdTs);
    $arrivalText = $staTs !== null ? date('d/m/Y H:i', $staTs) : '—';
    $delayedArrivalText = $etaTs !== null ? date('d/m/Y H:i', $etaTs) : '—';
    $effectiveLandingTs = $etaTs;
}

// Đã qua giờ hạ cánh dự kiến thì hiển thị Hạ cánh.
if ($flight && in_array($displayStatus, ['Cat_Canh', 'Delay'], true)
    && $effectiveLandingTs !== null
    && $effectiveLandingTs <= strtotime((string) $data['serverNow'])) {
    $displayStatus = 'Ha_Canh';
    $displayLabel = 'Hạ cánh';
    $displayClass = 'warning';
}

$terminalText = 'Chưa phân công';
if ($flight && (!empty($flight['terminal_code']) || !empty($flight['terminal_name']))) {
    $terminalText = trim(($flight['terminal_code'] ?? '') . ' - ' . ($flight['terminal_name'] ?? ''), ' -');
}

$latestDelay = $delayHistory ? $delayHistory[count($delayHistory) - 1] : null;

$pageTitle = 'Chi tiết chuyến bay';
$pageHeader = $flight ? 'Chi tiết chuyến ' . $flight['so_hieu'] : 'Chi tiết chuyến bay';
$navActive = 'chuyen_bay';

ob_start();
?>
            <?php if ($flight === null): ?>
              <div class="card">
                <div class="card-body">
                  <p class="mb-3">Không tìm thấy chuyến bay cần xem.</p>
                  <button class="btn btn-outline-danger" type="button" data-flight-popup-close>Đóng</button>
                </div>
              </div>
            <?php else: ?>
              <?php if ($displayStatus === 'Delay'): ?>
                <div class="alert alert-warning" role="alert">
                  <strong>Thông tin trễ chuyến</strong><br>
                  Lý do trễ: <?= $escape($latestDelay['ly_do'] ?? 'Chưa có lý do') ?><br>
                  <?php if ($delayHistory): ?>
                    <details class="mt-1">
                      <summary class="text-secondary" style="cursor:pointer;">Lý do trễ trước đó (<?= count($delayHistory) ?>)</summary>
                      <ul class="mb-0 mt-1">
                        <?php foreach ($delayHistory as $entry): ?>
                          <?php if (!empty($entry['ly_do'])): ?>
                            <li>
                              <?= $escape($entry['thoi_gian']) ?> — <?= $escape($entry['ly_do']) ?>
                              <?php if (!empty($entry['so_phut'])): ?>
                                (<?= (int) $entry['so_phut'] ?> phút)
                              <?php endif; ?>
                            </li>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </ul>
                    </details>
                  <?php endif; ?>
                  Thời gian trễ lần này: <?= (int) ($latestDelay['so_phut'] ?? $totalDelayMinutes) ?> phút<br>
                  Tổng thời gian đã delay: <?= (int) ($totalDelayMinutes + $overdueMinutes) ?> phút<?php if ($overdueMinutes > 0): ?>
                    <span class="text-danger">(gồm <?= (int) $overdueMinutes ?> phút quá hạn chờ xác nhận)</span><?php endif; ?><br>
                  Giờ bắt đầu bay: <?= $escape($stdText) ?><br>
                  Giờ dự kiến hạ cánh: <?= $escape($arrivalText) ?><br>
                  Giờ cất cánh sau delay: <?= $escape($etdText) ?><br>
                  Giờ dự kiến hạ cánh sau delay: <?= $escape($delayedArrivalText) ?>
                </div>
              <?php endif; ?>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="card h-100">
                    <div class="card-header"><h3 class="card-title h6 mb-0">Hành trình & tàu bay</h3></div>
                    <div class="card-body">
                      <dl class="row departure-info mb-0">
                        <dt class="col-sm-5">Mã chuyến bay</dt>
                        <dd class="col-sm-7"><?= $escape($flight['so_hieu']) ?></dd>

                        <dt class="col-sm-5">Hãng hàng không</dt>
                        <dd class="col-sm-7"><?= $escape($flight['ten_hang']) ?></dd>

                        <dt class="col-sm-5">Loại tàu bay</dt>
                        <dd class="col-sm-7"><?= $escape($flight['ma_loai']) ?></dd>

                        <dt class="col-sm-5">Sân bay đi (đang ở)</dt>
                        <dd class="col-sm-7">
                          <?= $escape($flight['ma_di']) ?> — <?= $escape($flight['ten_di']) ?>
                        </dd>

                        <dt class="col-sm-5">Nhà ga đi</dt>
                        <dd class="col-sm-7"><?= $escape($terminalText) ?></dd>

                        <dt class="col-sm-5">Sân bay đến</dt>
                        <dd class="col-sm-7">
                          <?= $escape($flight['ma_den']) ?> — <?= $escape($flight['ten_den']) ?>
                        </dd>

                        <dt class="col-sm-5">Giờ cất cánh</dt>
                        <dd class="col-sm-7">
                          <?= $escape($stdText) ?>
                          <?php if ($displayStatus === 'Delay'): ?>
                            <span class="text-secondary small d-block">Sau delay dự kiến: <?= $escape($etdText) ?></span>
                          <?php endif; ?>
                        </dd>

                        <dt class="col-sm-5">Giờ dự kiến hạ cánh</dt>
                        <dd class="col-sm-7"><?= $escape($arrivalText) ?></dd>

                        <dt class="col-sm-5">Giờ dự kiến hạ cánh sau delay</dt>
                        <dd class="col-sm-7"><?= $escape($delayedArrivalText) ?></dd>


                      </dl>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="card h-100">
                    <div class="card-header"><h3 class="card-title h6 mb-0">Hạ tầng & tải</h3></div>
                    <div class="card-body">
                      <dl class="row departure-info mb-0">
                        <dt class="col-sm-5">Cửa ra máy bay</dt>
                        <dd class="col-sm-7"><?= $escape($flight['cua_ra'] ?? 'Chưa phân công') ?></dd>

                        <dt class="col-sm-5">Quầy check-in</dt>
                        <dd class="col-sm-7"><?= $escape($flight['quay_checkin'] ?? 'Chưa phân công') ?></dd>

                        <dt class="col-sm-5">Băng chuyền hành lý</dt>
                        <dd class="col-sm-7"><?= $escape($flight['bang_chuyen'] ?? 'Chưa phân công') ?></dd>

                        <dt class="col-sm-5">Số hành khách</dt>
                        <dd class="col-sm-7">
                          <?= (int) ($flight['luong_hanh_khach'] ?? 0) ?> khách
                          <?php if (!empty($flight['suc_chua_hanh_khach'])): ?>
                            / <?= (int) $flight['suc_chua_hanh_khach'] ?> khách
                          <?php endif; ?>
                        </dd>

                        <dt class="col-sm-5">Hành lý (tấn)</dt>
                        <dd class="col-sm-7">
                          <?= $escape(rtrim(rtrim(number_format((float) ($flight['luong_hang_hoa_tan'] ?? 0), 2, '.', ''), '0'), '.')) ?> tấn
                        </dd>

                        <dt class="col-sm-5">Trạng thái</dt>
                        <dd class="col-sm-7">
                          <span class="badge text-bg-<?= $escape($displayClass) ?>"<?= $displayStatus === 'Ha_Canh' ? ' style="background-color:#33FFFF;color:#000;"' : '' ?>>
                            <?= $escape($displayLabel) ?>
                          </span>
                        </dd>
                      </dl>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-3 d-flex flex-wrap gap-2">
                <button class="btn btn-outline-danger" type="button" data-flight-popup-close>Đóng</button>
              </div>
            <?php endif; ?>

            <script>
              document.querySelectorAll('[data-flight-popup-open]').forEach(function (opener) {
                opener.addEventListener('click', function (event) {
                  event.preventDefault();
                  if (window.parent && typeof window.parent.openFlightPopup === 'function') {
                    return;
                  }
                });
              });
              document.querySelectorAll('[data-flight-popup-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                  if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'flight-popup-close' }, window.location.origin);
                  }
                });
              });
            </script>
<?php
$content = ob_get_clean();

if ($popup) {
    ?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $escape($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $escape(ADMINLTE_URL) ?>/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?= $escape(BASE_URL) ?>/assets/css/style.css" />
    <style>
      body.flight-detail-page { background: #f4f6f9; padding: 1rem; }
      body.flight-detail-page .card { margin-bottom: 0; }
      .departure-info dt { color: #6c757d; font-weight: 500; }
      .departure-info dd { margin-bottom: .5rem; font-weight: 600; }
    </style>
  </head>
  <body class="flight-detail-page">
<?= $content ?>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
