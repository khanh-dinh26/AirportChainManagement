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
$flight = $data['flight'] ?? null;
$delayHistory = $data['delayHistory'] ?? [];
$statusLabels = [
    'Dang_Mo' => 'Đang mở',
    'Cat_Canh' => 'Cất cánh',
    'Ha_Canh' => 'Hạ cánh',
    'Delay' => 'Trễ chuyến',
    'Huy' => 'Hủy chuyến',
];
// Trễ chuyến chỉ hiển thị cho chuyến sát ngày; chuyến tương lai xa coi là điều chỉnh lịch bay.
$displayStatus = $flight['trang_thai_tong'] ?? '';
$displayLabel = $statusLabels[$displayStatus] ?? $displayStatus;
if ($displayStatus === 'Delay' && $flight && substr((string) $flight['gio_du_kien'], 0, 10) > substr((string) $data['serverNow'], 0, 10)) {
    $displayLabel = 'Điều chỉnh lịch bay';
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
            <?php if (!$popup): ?>
              <link rel="stylesheet" href="<?= $escape(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
            <?php endif; ?>
            <?php if ($flight === null): ?>
              <div class="card">
                <div class="card-body">
                  <p class="mb-3">Không tìm thấy chuyến bay cần xem.</p>
                </div>
              </div>
            <?php else: ?>
              <div class="card flight-detail-content">
                <?php if (!$popup): ?>
                <div class="card-header">
                  <h2 class="card-title h5 mb-0">Thông tin chuyến <?= $escape($flight['so_hieu']) ?></h2>
                </div>
                <?php endif; ?>
                <div class="card-body">
                  <?php if (in_array($displayStatus, ['Cat_Canh', 'Ha_Canh'], true)): ?>
                    <?php $isLanded = $displayStatus === 'Ha_Canh'; ?>
                    <div class="flight-readonly-banner mb-3">
                      <div class="d-flex align-items-start gap-3">
                        <i class="bi <?= $isLanded ? 'bi-check-circle-fill' : 'bi-airplane-engines-fill' ?>" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                          <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong><?= $isLanded ? 'Chuyến bay đã hoàn tất' : 'Chuyến bay đang khai thác' ?></strong>
                            <span class="badge flight-status-badge" data-flight-status="<?= $escape($displayStatus) ?>"><?= $escape($displayLabel) ?></span>
                          </div>
                          <p class="mb-0 mt-1 text-secondary">Thông tin chuyến bay chỉ xem được sau khi máy bay cất cánh để bảo toàn dữ liệu khai thác.</p>
                        </div>
                      </div>
                    </div>
                  <?php elseif ($displayStatus === 'Huy'): ?>
                    <div class="flight-cancel-banner mb-3" role="status">
                      <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-x-octagon-fill" aria-hidden="true"></i>
                        <div>
                          <strong>Chuyến bay đã bị hủy</strong>
                          <p class="mb-0 mt-1 text-secondary">Thông tin và lịch sử chuyến được giữ lại để tra cứu.</p>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <section class="flight-form-section mb-3" aria-labelledby="flight-detail-route-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-detail-route-title">Hành trình và tàu bay</h3>
                        <p><?= $escape($flight['so_hieu']) ?> · <?= $escape($flight['ten_hang'] ?? 'Chưa có hãng') ?></p>
                      </div>
                    </div>
                    <div class="flight-summary-route mb-3">
                      <div class="flight-summary-airport">
                        <span class="flight-summary-airport-code"><?= $escape($flight['ma_di'] ?? '—') ?></span>
                        <span class="flight-summary-airport-name"><?= $escape($flight['ten_di'] ?? 'Chưa có sân bay đi') ?></span>
                      </div>
                      <span class="flight-summary-route-icon" aria-hidden="true"><i class="bi bi-airplane"></i></span>
                      <div class="flight-summary-airport">
                        <span class="flight-summary-airport-code"><?= $escape($flight['ma_den'] ?? '—') ?></span>
                        <span class="flight-summary-airport-name"><?= $escape($flight['ten_den'] ?? 'Chưa có sân bay đến') ?></span>
                      </div>
                    </div>
                    <div class="flight-summary-facts">
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Loại tàu bay</span>
                        <span class="flight-summary-fact-value"><?= $escape($flight['ma_loai'] ?? 'Chưa cập nhật') ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Nhà ga đi</span>
                        <span class="flight-summary-fact-value"><?= $escape($terminalText) ?></span>
                      </div>
                    </div>
                  </section>

                  <section class="flight-form-section mb-3" aria-labelledby="flight-detail-schedule-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-detail-schedule-title">Lịch trình</h3>
                        <p>Thời gian dự kiến và tiến trình chuyến bay.</p>
                      </div>
                    </div>
                    <div class="flight-progress mb-3" aria-label="Tiến trình chuyến bay">
                      <div class="flight-progress-step is-complete">
                        <span class="flight-progress-dot"><i class="bi bi-check2" aria-hidden="true"></i></span>
                        <span>Đã lên lịch</span>
                      </div>
                      <div class="flight-progress-step <?= in_array($displayStatus, ['Cat_Canh', 'Ha_Canh'], true) ? 'is-complete' : ($displayStatus === 'Huy' ? '' : 'is-current') ?>">
                        <span class="flight-progress-dot"><i class="bi <?= in_array($displayStatus, ['Cat_Canh', 'Ha_Canh'], true) ? 'bi-check2' : ($displayStatus === 'Huy' ? 'bi-x' : 'bi-clock') ?>" aria-hidden="true"></i></span>
                        <span><?= in_array($displayStatus, ['Cat_Canh', 'Ha_Canh'], true) ? 'Đã cất cánh' : ($displayStatus === 'Huy' ? 'Đã hủy' : 'Chờ cất cánh') ?></span>
                      </div>
                      <div class="flight-progress-step <?= $displayStatus === 'Ha_Canh' ? 'is-complete' : ($displayStatus === 'Cat_Canh' ? 'is-current' : '') ?>">
                        <span class="flight-progress-dot"><i class="bi <?= $displayStatus === 'Ha_Canh' ? 'bi-check2' : ($displayStatus === 'Cat_Canh' ? 'bi-airplane' : 'bi-geo-alt') ?>" aria-hidden="true"></i></span>
                        <span><?= $displayStatus === 'Ha_Canh' ? 'Đã hạ cánh' : ($displayStatus === 'Cat_Canh' ? 'Đang bay' : 'Điểm đến') ?></span>
                      </div>
                    </div>
                    <div class="flight-summary-facts">
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Giờ cất cánh</span>
                        <span class="flight-summary-fact-value"><?= $escape($stdText) ?></span>
                        <?php if ($displayStatus === 'Delay'): ?><small class="text-secondary">Dự kiến sau trễ: <?= $escape($etdText) ?></small><?php endif; ?>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Giờ hạ cánh dự kiến</span>
                        <span class="flight-summary-fact-value"><?= $escape($arrivalText) ?></span>
                        <?php if ($displayStatus === 'Delay'): ?><small class="text-secondary">Sau trễ: <?= $escape($delayedArrivalText) ?></small><?php endif; ?>
                      </div>
                    </div>
                  </section>

                  <section class="flight-form-section mb-3" aria-labelledby="flight-detail-services-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-building-check" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-detail-services-title">Dịch vụ tại sân bay đi</h3>
                        <p>Cửa ra máy bay, quầy làm thủ tục và băng chuyền hành lý.</p>
                      </div>
                    </div>
                    <div class="flight-summary-facts">
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Cửa ra máy bay</span>
                        <span class="flight-summary-fact-value"><?= $escape($flight['cua_ra'] ?? 'Chưa phân công') ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Quầy check-in</span>
                        <span class="flight-summary-fact-value"><?= $escape($flight['quay_checkin'] ?? 'Chưa phân công') ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Băng chuyền hành lý</span>
                        <span class="flight-summary-fact-value"><?= $escape($flight['bang_chuyen'] ?? 'Chưa phân công') ?></span>
                      </div>
                    </div>
                  </section>

                  <section class="flight-form-section mb-3" aria-labelledby="flight-detail-operations-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-gear-wide-connected" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-detail-operations-title">Trạng thái và tải khai thác</h3>
                        <p>Trạng thái hiện tại, số hành khách và khối lượng hành lý.</p>
                      </div>
                    </div>
                    <div class="flight-summary-facts">
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Hành khách</span>
                        <span class="flight-summary-fact-value">
                          <?= (int) ($flight['luong_hanh_khach'] ?? 0) ?> khách<?php if (!empty($flight['suc_chua_hanh_khach'])): ?> / <?= (int) $flight['suc_chua_hanh_khach'] ?> khách<?php endif; ?>
                        </span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Hành lý</span>
                        <span class="flight-summary-fact-value"><?= number_format((float) ($flight['luong_hang_hoa_tan'] ?? 0), 2, ',', '.') ?> tấn</span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Trạng thái</span>
                        <span class="flight-summary-fact-value">
                          <span class="badge flight-status-badge" data-flight-status="<?= $escape($displayStatus === 'Delay' && $displayLabel !== 'Trễ chuyến' ? 'Dang_Mo' : $displayStatus) ?>"><?= $escape($displayLabel) ?></span>
                        </span>
                      </div>
                    </div>
                  </section>

                  <?php if ($displayStatus === 'Delay'): ?>
                    <section class="flight-delay-panel mb-3" role="status" aria-labelledby="flight-delay-title">
                      <div class="flight-delay-heading">
                        <span class="flight-delay-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                        <div>
                          <h3 id="flight-delay-title">Thông tin trễ chuyến</h3>
                          <p>Chi tiết nguyên nhân và thời gian điều chỉnh lịch bay.</p>
                        </div>
                      </div>
                      <div class="flight-delay-reason">
                        <span class="flight-delay-label">Lý do trễ gần nhất</span>
                        <strong><?= $escape($latestDelay['ly_do'] ?? 'Chưa có lý do') ?></strong>
                      </div>
                      <div class="flight-delay-metrics">
                        <div class="flight-delay-metric">
                          <span class="flight-delay-label">Lần trễ gần nhất</span>
                          <strong><?= (int) ($latestDelay['so_phut'] ?? $totalDelayMinutes) ?> <small>phút</small></strong>
                        </div>
                        <div class="flight-delay-metric">
                          <span class="flight-delay-label">Tổng thời gian trễ</span>
                          <strong><?= (int) ($totalDelayMinutes + $overdueMinutes) ?> <small>phút</small></strong>
                          <?php if ($overdueMinutes > 0): ?>
                            <span class="flight-delay-overdue">Bao gồm <?= (int) $overdueMinutes ?> phút quá hạn chờ xác nhận</span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <?php if ($delayHistory): ?>
                        <details class="flight-delay-history">
                          <summary>
                            <span><i class="bi bi-list-ul me-2" aria-hidden="true"></i>Lịch sử các lần trễ chuyến</span>
                            <span class="badge rounded-pill text-bg-warning"><?= count($delayHistory) ?> lần</span>
                          </summary>
                          <ul class="flight-delay-timeline">
                            <?php foreach ($delayHistory as $entry): ?>
                              <?php if (!empty($entry['ly_do'])): ?>
                                <?php
                                  $delayTimestamp = DateTime::createFromFormat('Y-m-d H:i:s', (string) ($entry['thoi_gian'] ?? ''));
                                  $delayTimeLabel = $delayTimestamp ? $delayTimestamp->format('d/m/Y · H:i') : (string) ($entry['thoi_gian'] ?? 'Thời gian chưa cập nhật');
                                ?>
                                <li>
                                  <span class="flight-delay-timeline-time"><?= $escape($delayTimeLabel) ?></span>
                                  <span class="flight-delay-timeline-reason"><?= $escape($entry['ly_do']) ?></span>
                                  <?php if (!empty($entry['so_phut'])): ?>
                                    <span class="flight-delay-timeline-duration"><?= (int) $entry['so_phut'] ?> phút</span>
                                  <?php endif; ?>
                                </li>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          </ul>
                        </details>
                      <?php endif; ?>
                    </section>
                  <?php endif; ?>

                </div>
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
    <link rel="stylesheet" href="<?= $escape(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
  </head>
  <body class="flight-detail-page">
<?= $content ?>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
