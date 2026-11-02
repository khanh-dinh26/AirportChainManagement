<?php
/**
 * ============================================================
 * TRANG: SỬA CHUYẾN BAY  —  views/pages/chuyen_bay/sua.php
 * Nhận id qua query string: sua.php?id=123
 * (vẫn chấp nhận sua.php?edit=123 để tương thích)
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

if (isset($_GET['id']) && !isset($_GET['edit'])) {
    $_GET['edit'] = $_GET['id'];
}

// Trang này có thể mở trực tiếp (trang riêng) hoặc nhúng trong popup ở trang danh sách.
$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

// Lưu thành công từ popup: báo trang cha tải lại danh sách, không điều hướng trang mới.
if ($popup && isset($_GET['saved'])) {
    ?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <title>Đã lưu chuyến bay</title>
  </head>
  <body>
    <script>
      (function () {
        if (window.parent && window.parent !== window) {
          window.parent.postMessage({ type: 'flight-popup-saved' }, window.location.origin);
        } else {
          window.location.replace(<?= json_encode(BASE_URL . '/index.php?page=chuyen_bay') ?>);
        }
      })();
    </script>
  </body>
</html>
    <?php
    exit();
}

$data = (new ChuyenBayController($conn))->handle();
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$formatDateTimeInput = static function ($value) {
    return $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
};
$url = static function ($page, array $params = []) {
    $paths = [
        'chuyen_bay' => BASE_URL . '/index.php?page=chuyen_bay',
        'chuyen_bay_sua' => BASE_URL . '/views/pages/chuyen_bay/sua.php',
    ];
    $target = $paths[$page] ?? $paths['chuyen_bay'];

    return $params ? $target . '?' . http_build_query($params) : $target;
};

// Chế độ chỉ xem nhật ký (?popup=1&log=1) — mở từ nút "Lịch sử" ở danh sách chuyến bay.
if ($popup && isset($_GET['log'])) {
    $logFlight = $data['editing'];
    $logFlightSummary = $data['editingSummary'] ?? [];
    $logActionLabels = $data['flightLogActions'];
    $logStatusLabels = $data['statuses'];
    $logRows = $data['editLogs'];
    ?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nhật ký chuyến bay</title>
    <link rel="stylesheet" href="<?= $escape(ADMINLTE_URL) ?>/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?= $escape(BASE_URL) ?>/assets/css/style.css" />
    <link rel="stylesheet" href="<?= $escape(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
  </head>
  <body class="flight-popup-page flight-log-page">
    <div class="card">
      <div class="card-body">
        <?php if ($logFlight === null): ?>
          <div class="alert alert-warning mb-3" role="status">
            <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i>Không tìm thấy chuyến bay cần xem nhật ký.
          </div>
        <?php else: ?>
          <section class="flight-summary-card flight-log-summary p-3 mb-3" aria-label="Thông tin chuyến bay">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
              <div>
                <span class="small text-uppercase text-secondary fw-semibold">Lịch sử hoạt động</span>
              </div>
              <span class="badge flight-status-badge" data-flight-status="<?= $escape($logFlight['trang_thai_tong'] ?? '') ?>">
                <?= $escape($logStatusLabels[$logFlight['trang_thai_tong'] ?? ''] ?? ($logFlight['trang_thai_tong'] ?? 'Chưa cập nhật')) ?>
              </span>
            </div>
            <div class="flight-summary-route mb-3">
              <div class="flight-summary-airport">
                <span class="flight-summary-airport-code"><?= $escape($logFlightSummary['ma_di'] ?? '—') ?></span>
                <span class="flight-summary-airport-name"><?= $escape($logFlightSummary['ten_di'] ?? 'Chưa có sân bay đi') ?></span>
              </div>
              <span class="flight-summary-route-icon" aria-hidden="true"><i class="bi bi-airplane"></i></span>
              <div class="flight-summary-airport">
                <span class="flight-summary-airport-code"><?= $escape($logFlightSummary['ma_den'] ?? '—') ?></span>
                <span class="flight-summary-airport-name"><?= $escape($logFlightSummary['ten_den'] ?? 'Chưa có sân bay đến') ?></span>
              </div>
            </div>
            <div class="flight-summary-facts">
              <div class="flight-summary-fact">
                <span class="flight-summary-fact-label">Hãng hàng không</span>
                <span class="flight-summary-fact-value"><?= $escape(trim(($logFlightSummary['hang_iata'] ?? '') . ' ' . ($logFlightSummary['ten_hang'] ?? 'Chưa cập nhật'))) ?></span>
              </div>
              <div class="flight-summary-fact">
                <span class="flight-summary-fact-label">Loại tàu bay</span>
                <span class="flight-summary-fact-value"><?= $escape($logFlightSummary['ma_loai'] ?? 'Chưa cập nhật') ?></span>
              </div>
              <div class="flight-summary-fact">
                <span class="flight-summary-fact-label">Giờ cất cánh</span>
                <span class="flight-summary-fact-value"><?= !empty($logFlight['gio_du_kien']) ? $escape(date('d/m/Y H:i', strtotime($logFlight['gio_du_kien']))) : 'Chưa cập nhật' ?></span>
              </div>
              <div class="flight-summary-fact">
                <span class="flight-summary-fact-label">Số hoạt động</span>
                <span class="flight-summary-fact-value"><?= number_format(count($logRows), 0, ',', '.') ?> bản ghi</span>
              </div>
            </div>
          </section>

          <section class="flight-log-section" aria-label="Các hoạt động chuyến bay">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <div>
                <h3 class="h6 mb-1">Diễn biến chuyến bay</h3>
                <p class="small text-secondary mb-0">Sắp xếp từ hoạt động mới nhất đến cũ nhất.</p>
              </div>
              <span class="badge rounded-pill text-bg-light border"><?= count($logRows) ?> hoạt động</span>
            </div>

            <?php if (!$logRows): ?>
              <div class="flight-log-empty text-center p-4" role="status">
                <i class="bi bi-journal-check" aria-hidden="true"></i>
                <strong class="d-block mt-2">Chưa có hoạt động nào</strong>
                <span class="small text-secondary">Nhật ký sẽ xuất hiện khi chuyến bay được thêm hoặc cập nhật.</span>
              </div>
            <?php else: ?>
              <div class="flight-log-list">
                <?php foreach ($logRows as $log): ?>
                  <?php
                    $noteLines = preg_split('/\s*;\s*|\R|,\s+|(?<=[.!?])\s+/u', trim((string) ($log['ghi_chu'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
                    $noteLines = array_values(array_filter(array_map(static function ($line) {
                        return preg_replace('/^\s*[-*•]\s*/u', '', trim((string) $line));
                    }, $noteLines), static function ($line) {
                        return $line !== '';
                    }));
                    $noteLines = array_map(static function ($line) {
                        return preg_replace_callback('/^Hành lý:\s*([\d.]+)\s*kg\s*->\s*([\d.]+)\s*kg$/u', static function ($matches) {
                            $formatTons = static function ($kilograms) {
                                return rtrim(rtrim(number_format((float) $kilograms / 1000, 5, ',', '.'), '0'), ',');
                            };

                            return 'Hành lý: ' . $formatTons($matches[1]) . ' tấn -> ' . $formatTons($matches[2]) . ' tấn';
                        }, $line);
                    }, $noteLines);
                    $logTimestamp = strtotime((string) ($log['thoi_gian_cap_nhat'] ?? ''));
                    $previousStatus = (string) ($log['trang_thai_cu'] ?? '');
                    $newStatus = (string) ($log['trang_thai'] ?? '');
                  ?>
                  <article class="flight-log-entry">
                    <span class="flight-log-marker" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                    <div class="flight-log-entry-card">
                      <div class="flight-log-entry-header">
                        <div class="flight-log-entry-meta">
                          <span class="flight-log-time">
                            <i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= $logTimestamp !== false ? $escape(date('d/m/Y', $logTimestamp)) : 'Không rõ ngày' ?>
                            <strong class="ms-1"><?= $logTimestamp !== false ? $escape(date('H:i', $logTimestamp)) : '—' ?></strong>
                          </span>
                          <span class="badge text-bg-light border flight-log-action">
                            <?= $escape($logActionLabels[$log['hanh_dong']] ?? $log['hanh_dong']) ?>
                          </span>
                        </div>
                        <span class="flight-log-actor">
                          <i class="bi <?= empty($log['id_nguoi_dung']) ? 'bi-gear' : 'bi-person-circle' ?> me-1" aria-hidden="true"></i>
                          <?= empty($log['id_nguoi_dung']) ? 'Hệ thống' : $escape($log['ho_ten'] ?? 'Người dùng') ?>
                        </span>
                      </div>

                      <?php if ($newStatus !== ''): ?>
                        <div class="flight-log-status-row">
                          <span class="small text-secondary">Trạng thái:</span>
                          <?php if ($previousStatus !== ''): ?>
                            <span class="badge flight-status-badge" data-flight-status="<?= $escape($previousStatus) ?>"><?= $escape($logStatusLabels[$previousStatus] ?? $previousStatus) ?></span>
                            <i class="bi bi-arrow-right text-secondary" aria-hidden="true"></i>
                          <?php endif; ?>
                          <span class="badge flight-status-badge" data-flight-status="<?= $escape($newStatus) ?>"><?= $escape($logStatusLabels[$newStatus] ?? $newStatus) ?></span>
                        </div>
                      <?php endif; ?>

                      <div class="flight-log-note">
                        <span class="flight-log-note-label"><i class="bi bi-card-text me-1" aria-hidden="true"></i>Nội dung</span>
                        <?php if (!$noteLines): ?>
                          <span class="text-secondary">Không có ghi chú bổ sung.</span>
                        <?php else: ?>
                          <ul class="flight-log-change-list mb-0">
                            <?php foreach ($noteLines as $noteLine): ?>
                              <li><strong><?= $escape($noteLine) ?></strong></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php endif; ?>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>
        <?php endif; ?>
          <?php if (!$popup): ?>
            <div class="mt-3 d-flex flex-wrap justify-content-end gap-2">
              <a class="btn btn-outline-secondary" href="<?= $escape($url('chuyen_bay')) ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại danh sách</a>
            </div>
          <?php endif; ?>
      </div>
    </div>
  </body>
</html>
    <?php
    exit();
}

$flight = $data['editing'];
$flightSummary = $data['editingSummary'] ?? [];
$statusLabels = $data['statuses'];
$formatFlightTime = static function ($value) {
    $timestamp = $value ? strtotime((string) $value) : false;
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : 'Chưa cập nhật';
};
$closeControl = $popup
    ? ''
    : '<a class="btn btn-outline-secondary" href="' . $escape($url('chuyen_bay')) . '"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại danh sách</a>';
$pageTitle = 'Sửa chuyến bay';
$pageHeader = $flight ? 'Sửa chuyến ' . $flight['so_hieu'] : 'Sửa chuyến bay';
$navActive = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Chuyến bay', 'url' => $url('chuyen_bay')],
    ['label' => 'Sửa chuyến bay', 'url' => null],
];

ob_start();
?>
            <?php if (!$popup): ?>
            <link rel="stylesheet" href="<?= $escape(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
            <?php endif; ?>
            <?php if ($data['flash']): ?>
              <div class="alert alert-<?= $escape($data['flash']['type']) ?>" role="alert">
                <?= $escape($data['flash']['message']) ?>
              </div>
            <?php endif; ?>

            <div class="card">
              <?php if (!$popup): ?>
              <div class="card-header">
                <h2 class="card-title h5">
                  <?= $flight ? 'Thông tin chuyến ' . $escape($flight['so_hieu']) : 'Thông tin chuyến bay' ?>
                </h2>
              </div>
              <?php endif; ?>
              <div class="card-body">
                <?php if (!$flight): ?>
                  <p class="mb-3">Không tìm thấy chuyến bay cần sửa.</p>
                  <?php if (!$popup): ?><div class="d-flex justify-content-end"><?= $closeControl ?></div><?php endif; ?>
                <?php else: ?>
                  <?php if (in_array($flight['trang_thai_tong'], ['Cat_Canh', 'Ha_Canh'], true)): ?>
                    <?php $isLanded = $flight['trang_thai_tong'] === 'Ha_Canh'; ?>
                    <div class="flight-readonly-banner mb-3">
                      <div class="d-flex align-items-start gap-3">
                        <i class="bi <?= $isLanded ? 'bi-check-circle-fill' : 'bi-airplane-engines-fill' ?>" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                          <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong><?= $isLanded ? 'Chuyến bay đã hoàn tất' : 'Chuyến bay đang khai thác' ?></strong>
                            <span class="badge flight-status-badge" data-flight-status="<?= $escape($flight['trang_thai_tong']) ?>">
                              <?= $escape($statusLabels[$flight['trang_thai_tong']] ?? $flight['trang_thai_tong']) ?>
                            </span>
                          </div>
                          <p class="mb-0 mt-1 text-secondary">
                            Thông tin chuyến <strong><?= $escape($flight['so_hieu']) ?></strong> được khóa sau khi máy bay cất cánh
                            để bảo toàn dữ liệu khai thác. Bạn vẫn có thể xem lại hành trình và thông tin bên dưới.
                          </p>
                        </div>
                      </div>
                    </div>
                    <section class="flight-summary-card p-3 mb-3" aria-label="Tóm tắt hành trình chuyến bay">
                      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                          <span class="small text-uppercase text-secondary fw-semibold">Tóm tắt chuyến bay</span>
                          <?php if (!$popup): ?><h3 class="h5 mb-0 mt-1"><?= $escape($flight['so_hieu']) ?></h3><?php endif; ?>
                        </div>
                        <span class="badge rounded-pill text-bg-light border">
                          <i class="bi bi-airplane me-1" aria-hidden="true"></i><?= $escape($flightSummary['hang_iata'] ?? '') ?>
                          <?= $escape($flightSummary['ten_hang'] ?? 'Chưa có hãng') ?>
                        </span>
                      </div>
                      <div class="flight-summary-route mb-3">
                        <div class="flight-summary-airport">
                          <span class="flight-summary-airport-code"><?= $escape($flightSummary['ma_di'] ?? '—') ?></span>
                          <span class="flight-summary-airport-name"><?= $escape($flightSummary['ten_di'] ?? 'Chưa có sân bay đi') ?></span>
                        </div>
                        <span class="flight-summary-route-icon" aria-hidden="true"><i class="bi bi-airplane"></i></span>
                        <div class="flight-summary-airport">
                          <span class="flight-summary-airport-code"><?= $escape($flightSummary['ma_den'] ?? '—') ?></span>
                          <span class="flight-summary-airport-name"><?= $escape($flightSummary['ten_den'] ?? 'Chưa có sân bay đến') ?></span>
                        </div>
                      </div>
                      <div class="flight-progress mb-3" aria-label="<?= $isLanded ? 'Chuyến bay đã hoàn thành' : 'Chuyến bay đang trên hành trình' ?>">
                        <div class="flight-progress-step is-complete">
                          <span class="flight-progress-dot"><i class="bi bi-check2" aria-hidden="true"></i></span>
                          <span>Đã lên lịch</span>
                        </div>
                        <div class="flight-progress-step is-complete">
                          <span class="flight-progress-dot"><i class="bi bi-check2" aria-hidden="true"></i></span>
                          <span>Đã cất cánh</span>
                        </div>
                        <div class="flight-progress-step <?= $isLanded ? 'is-complete' : 'is-current' ?>">
                          <span class="flight-progress-dot">
                            <i class="bi <?= $isLanded ? 'bi-check2' : 'bi-airplane' ?>" aria-hidden="true"></i>
                          </span>
                          <span><?= $isLanded ? 'Đã hạ cánh' : 'Đang bay' ?></span>
                        </div>
                      </div>
                      <div class="flight-summary-facts">
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Giờ cất cánh</span>
                          <span class="flight-summary-fact-value"><?= $escape($formatFlightTime($flight['gio_du_kien'])) ?></span>
                        </div>
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Giờ hạ cánh</span>
                          <span class="flight-summary-fact-value"><?= $escape($formatFlightTime($flight['gio_thuc_te'])) ?></span>
                        </div>
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Loại tàu bay</span>
                          <span class="flight-summary-fact-value"><?= $escape($flightSummary['ma_loai'] ?? 'Chưa cập nhật') ?></span>
                        </div>
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Hành khách</span>
                          <span class="flight-summary-fact-value"><?= number_format((int) ($flight['luong_hanh_khach'] ?? 0), 0, ',', '.') ?> khách</span>
                        </div>
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Hành lý</span>
                          <span class="flight-summary-fact-value"><?= number_format((float) ($flight['luong_hang_hoa_tan'] ?? 0), 2, ',', '.') ?> tấn</span>
                        </div>
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Cửa ra máy bay</span>
                          <span class="flight-summary-fact-value"><?= $escape($flightSummary['cua_ra'] ?? 'Chưa phân công') ?></span>
                        </div>
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Quầy check-in</span>
                          <span class="flight-summary-fact-value"><?= $escape($flightSummary['quay_checkin'] ?? 'Chưa phân công') ?></span>
                        </div>
                        <div class="flight-summary-fact">
                          <span class="flight-summary-fact-label">Băng chuyền</span>
                          <span class="flight-summary-fact-value"><?= $escape($flightSummary['bang_chuyen'] ?? 'Chưa phân công') ?></span>
                        </div>
                      </div>
                    </section>
                    <?php if (!$popup): ?><div class="d-flex flex-wrap justify-content-end"><?= $closeControl ?></div><?php endif; ?>
                  <?php else: ?>
                    <form method="post" action="<?= $url('chuyen_bay_sua', ['id' => (int) $flight['id']] + ($popup ? ['popup' => 1] : [])) ?>" class="flight-create-form">
                      <input type="hidden" name="csrf" value="<?= $escape($data['csrf']) ?>">
                      <input type="hidden" name="action" value="update">
                      <?php if ($popup): ?>
                        <input type="hidden" name="popup" value="1">
                      <?php endif; ?>
                      <input type="hidden" name="return_page" value="chuyen_bay_edit_modal">
                      <input type="hidden" name="return_id" value="<?= (int) $flight['id'] ?>">
                      <input type="hidden" name="id" value="<?= (int) $flight['id'] ?>">
                      <section class="flight-form-section mb-3" aria-labelledby="flight-edit-route-title">
                        <div class="flight-form-section-heading">
                          <span class="flight-form-section-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                          <div>
                            <h3 id="flight-edit-route-title">Hành trình và tàu bay</h3>
                            <p>Cập nhật mã chuyến, hãng khai thác, loại tàu bay và tuyến bay.</p>
                          </div>
                        </div>
                        <div class="row g-3 flight-form-grid">
                      <div class="col-md-4">
                        <label class="form-label" for="edit_so_hieu">Mã chuyến bay <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="form-control" id="edit_so_hieu" name="so_hieu" maxlength="20" required value="<?= $escape($flight['so_hieu']) ?>">
                      </div>
                      <?php foreach ([
                          'id_hang_hang_khong' => ['Hãng hàng không', $data['airlines']],
                          'id_loai_tau_bay' => ['Loại tàu bay', $data['aircraftTypes']],
                          'id_san_bay_di' => ['Sân bay đi', $data['airports']],
                          'id_san_bay_den' => ['Sân bay đến', $data['airports']],
                      ] as $field => $select): ?>
                        <div class="col-md-4">
                          <?php $isAirportField = in_array($field, ['id_san_bay_di', 'id_san_bay_den'], true); ?>
                          <?php $isAirlineField = $field === 'id_hang_hang_khong'; ?>
                          <?php $isAircraftField = $field === 'id_loai_tau_bay'; ?>
                          <?php if ($isAirportField || $isAirlineField || $isAircraftField): ?>
                            <?php
                              $optionListId = 'edit_' . $field . '_options';
                              $optionQueryId = 'edit_' . $field . '_search';
                              $selectedOptionText = '';
                              foreach ($select[1] as $listOption) {
                                  if ((int) $flight[$field] === (int) $listOption['id']) {
                                      $selectedOptionText = $isAircraftField
                                          ? $listOption['label']
                                          : (($listOption['ma_iata'] ?? '')
                                              . ($isAirportField ? ' – ' : ' ')
                                              . $listOption['label']);
                                      break;
                                  }
                              }
                            ?>
                            <label class="form-label" for="<?= $escape($optionQueryId) ?>"><?= $escape($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control" id="<?= $escape($optionQueryId) ?>"
                                   list="<?= $escape($optionListId) ?>" data-option-query
                                   data-option-select-id="edit_<?= $escape($field) ?>"
                                   <?= $isAircraftField ? 'data-aircraft-query' : '' ?>
                                   placeholder="Nhập mã hoặc tên <?= $isAirlineField ? 'hãng hàng không' : ($isAircraftField ? 'loại tàu bay' : ($field === 'id_san_bay_di' ? 'sân bay đi' : 'sân bay đến')) ?>"
                                   autocomplete="off" required value="<?= $escape($selectedOptionText) ?>">
                            <datalist id="<?= $escape($optionListId) ?>">
                              <?php foreach ($select[1] as $listOption): ?>
                                <option value="<?= $escape($isAircraftField ? $listOption['label'] : (($listOption['ma_iata'] ?? '') . ($isAirportField ? ' – ' : ' ') . $listOption['label'])) ?>" data-option-id="<?= (int) $listOption['id'] ?>"></option>
                              <?php endforeach; ?>
                            </datalist>
                          <?php else: ?>
                            <label class="form-label" for="edit_<?= $escape($field) ?>"><?= $escape($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                          <?php endif; ?>
                          <select class="form-select" id="edit_<?= $escape($field) ?>" name="<?= $escape($field) ?>" <?= $isAirportField || $isAirlineField || $isAircraftField ? 'hidden aria-hidden="true" tabindex="-1"' : 'required' ?> <?= $field === 'id_san_bay_di' ? 'data-origin-selector' : '' ?> <?= $field === 'id_hang_hang_khong' ? 'data-airline-selector' : '' ?> <?= $isAircraftField ? 'data-capacity-selector data-aircraft-selector data-aircraft-placeholder="-- Chọn Loại tàu bay --" data-aircraft-locked-placeholder="-- Chọn hãng hàng không trước --"' : '' ?>>
                            <?php if ($field === 'id_loai_tau_bay'): ?>
                              <option value="">-- Chọn Loại tàu bay --</option>
                            <?php endif; ?>
                            <?php foreach ($select[1] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" <?= $field === 'id_loai_tau_bay' ? 'data-airline-ids="' . $escape($option['airline_ids'] ?? '') . '" data-passenger-capacity="' . (int) $option['suc_chua_hanh_khach'] . '" data-baggage-capacity-ton="' . $escape((float) $option['tai_trong_cat_canh_mtow']) . '"' : '' ?> <?= (int) $flight[$field] === (int) $option['id'] ? 'selected' : '' ?>>
                                <?= $escape(($option['ma_iata'] ?? '') . ($isAirportField ? ' – ' : ' ') . $option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      <?php endforeach; ?>
                        </div>
                      </section>
                      <section class="flight-form-section mb-3" aria-labelledby="flight-edit-schedule-title">
                        <div class="flight-form-section-heading">
                          <span class="flight-form-section-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                          <div>
                            <h3 id="flight-edit-schedule-title">Lịch trình</h3>
                            <p>Cập nhật giờ cất cánh và giờ dự kiến hạ cánh.</p>
                          </div>
                        </div>
                        <div class="row g-3 flight-form-grid">
                      <div class="col-md-4">
                        <label class="form-label" for="edit_gio_du_kien">Giờ cất cánh <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="datetime-local" class="form-control" id="edit_gio_du_kien" name="gio_du_kien" lang="vi" step="60" required value="<?= $escape($formatDateTimeInput($flight['gio_du_kien'])) ?>">
                        <small class="form-text text-secondary">Chọn ngày trên lịch và giờ theo định dạng 24 giờ (giờ, phút).</small>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="edit_gio_thuc_te">
                          Giờ dự kiến hạ cánh <span class="text-danger" aria-hidden="true">*</span>
                          <span class="badge text-bg-warning ms-1" data-arrival-next-day title="Chuyến hạ cánh vào ngày hôm sau (qua nửa đêm)" hidden>+1 ngày</span>
                        </label>
                        <input type="datetime-local" class="form-control" id="edit_gio_thuc_te" name="gio_thuc_te" lang="vi" step="60" required value="<?= $escape($formatDateTimeInput($flight['gio_thuc_te'])) ?>">
                        <small class="form-text text-secondary">Chọn ngày trên lịch và giờ theo định dạng 24 giờ (giờ, phút).</small>
                        <small class="text-secondary d-block" data-arrival-next-day-note hidden></small>
                      </div>
                        </div>
                      </section>
                      <section class="flight-form-section mb-3" aria-labelledby="flight-edit-services-title">
                        <div class="flight-form-section-heading">
                          <span class="flight-form-section-icon"><i class="bi bi-building-check" aria-hidden="true"></i></span>
                          <div>
                            <h3 id="flight-edit-services-title">Dịch vụ tại sân bay đi</h3>
                            <p>Chọn cửa, quầy và băng chuyền thuộc sân bay đi đã chọn.</p>
                          </div>
                        </div>
                        <div class="row g-3 flight-form-grid">
                      <?php foreach ([
                          'id_cua_ra_may_bay' => ['Cửa ra máy bay', $data['gates']],
                          'id_quay_checkin' => ['Quầy check-in', $data['checkins']],
                          'id_bang_chuyen' => ['Băng chuyền hành lý', $data['belts']],
                      ] as $field => $select): ?>
                        <div class="col-md-4">
                          <?php $isAutocompleteResource = in_array($field, ['id_cua_ra_may_bay', 'id_quay_checkin', 'id_bang_chuyen'], true); ?>
                          <?php if ($isAutocompleteResource): ?>
                            <?php
                              $optionListId = 'edit_' . $field . '_options';
                              $optionQueryId = 'edit_' . $field . '_search';
                              $selectedOptionText = '';
                              foreach ($select[1] as $listOption) {
                                  if ((int) ($flight[$field] ?? 0) === (int) $listOption['id']) {
                                      $selectedOptionText = $listOption['label'];
                                      break;
                                  }
                              }
                            ?>
                            <label class="form-label" for="<?= $escape($optionQueryId) ?>"><?= $escape($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control" id="<?= $escape($optionQueryId) ?>" list="<?= $escape($optionListId) ?>"
                                   data-option-query data-option-select-id="edit_<?= $escape($field) ?>"
                                   placeholder="Nhập tên <?= $field === 'id_cua_ra_may_bay' ? 'cửa ra máy bay' : ($field === 'id_quay_checkin' ? 'quầy check-in' : 'băng chuyền hành lý') ?>"
                                   autocomplete="off" required value="<?= $escape($selectedOptionText) ?>">
                            <datalist id="<?= $escape($optionListId) ?>">
                              <?php foreach ($select[1] as $option): ?>
                                <option value="<?= $escape($option['label']) ?>" data-option-id="<?= (int) $option['id'] ?>"></option>
                              <?php endforeach; ?>
                            </datalist>
                          <?php else: ?>
                            <label class="form-label" for="edit_<?= $escape($field) ?>"><?= $escape($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                          <?php endif; ?>
                          <select class="form-select" id="edit_<?= $escape($field) ?>" name="<?= $escape($field) ?>" <?= $isAutocompleteResource ? 'hidden aria-hidden="true" tabindex="-1"' : 'required' ?> data-origin-filter>
                            <option value="">-- Chưa phân công --</option>
                            <?php foreach ($select[1] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" data-airport-id="<?= (int) $option['id_san_bay'] ?>" <?= (int) ($flight[$field] ?? 0) === (int) $option['id'] ? 'selected' : '' ?>>
                                <?= $escape($option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <?php if ($field === 'id_quay_checkin'): ?>
                            <small class="text-secondary d-block">Mở trước giờ cất cánh 2–3 tiếng, đóng trước 40 phút.</small>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                        </div>
                      </section>
                      <section class="flight-form-section mb-3" aria-labelledby="flight-edit-operations-title">
                        <div class="flight-form-section-heading">
                          <span class="flight-form-section-icon"><i class="bi bi-gear-wide-connected" aria-hidden="true"></i></span>
                          <div>
                            <h3 id="flight-edit-operations-title">Trạng thái và tải khai thác</h3>
                            <p>Cập nhật trạng thái chuyến bay, số hành khách và khối lượng hành lý.</p>
                          </div>
                        </div>
                        <div class="row g-3 flight-form-grid">
                      <div class="col-md-4">
                        <label class="form-label" for="edit_trang_thai">Trạng thái <span class="text-danger" aria-hidden="true">*</span></label>
                        <?php if (!in_array($flight['trang_thai_tong'], ['Dang_Mo', 'Delay', 'Huy'], true)): ?>
                          <input type="hidden" name="trang_thai_tong" value="<?= $escape($flight['trang_thai_tong']) ?>">
                          <input class="form-control" id="edit_trang_thai" value="<?= $escape($statusLabels[$flight['trang_thai_tong']] ?? $flight['trang_thai_tong']) ?>" readonly>
                        <?php else: ?>
                          <select class="form-select" id="edit_trang_thai" name="trang_thai_tong" required data-flight-status data-initial-status="<?= $escape($flight['trang_thai_tong']) ?>">
                            <?php foreach ($data['editableStatusTransitions'][$flight['trang_thai_tong']] ?? [] as $value): ?>
                              <option value="<?= $escape($value) ?>" <?= $flight['trang_thai_tong'] === $value ? 'selected' : '' ?>><?= $escape($data['statuses'][$value]) ?></option>
                            <?php endforeach; ?>
                          </select>
                        <?php endif; ?>
                      </div>
                      <div class="col-12" data-status-reason-container hidden>
                        <label class="form-label" for="edit_ghi_chu" data-status-reason-label>Lý do trễ chuyến <span class="text-danger" aria-hidden="true" data-status-reason-required hidden>*</span></label>
                        <textarea class="form-control" id="edit_ghi_chu" name="ghi_chu" rows="2" maxlength="255" data-status-reason placeholder="Nhập lý do trễ chuyến"></textarea>
                      </div>
                      <div class="col-md-4" data-delay-duration-container hidden>
                        <label class="form-label" for="edit_thoi_gian_tre">Khoảng thời gian dự kiến trễ (phút) <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="number" min="1" max="1440" step="1" class="form-control" id="edit_thoi_gian_tre"
                               name="thoi_gian_tre_phut" data-delay-duration placeholder="Ví dụ: 45"
                               value="<?= $escape((string) ($flight['thoi_gian_tre_phut'] ?? '')) ?>">
                        <small class="text-secondary d-block" data-delay-duration-note>
                          Thời gian dự kiến trễ so với giờ cất cánh (1–1440 phút).
                        </small>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="edit_hanh_khach">Số hành khách <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="number" min="0" class="form-control" id="edit_hanh_khach" name="luong_hanh_khach" required data-capacity-passengers value="<?= (int) $flight['luong_hanh_khach'] ?>">
                        <small class="text-secondary" data-passenger-capacity-label></small>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="edit_hang_hoa">Hành lý (tấn) <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="number" min="0" step="0.01" class="form-control" id="edit_hang_hoa" name="luong_hanh_ly_tan" required data-capacity-baggage value="<?= $escape((float) $flight['luong_hang_hoa_tan']) ?>">
                        <small class="text-secondary" data-baggage-capacity-label></small>
                      </div>
                        </div>
                      </section>
                        </div>
                      </section>
                      <div class="flight-form-actions d-flex flex-wrap justify-content-end gap-2">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Lưu thay đổi</button>
                        <?php if (!$popup): ?>
                          <a class="btn btn-outline-danger" href="<?= $url('chuyen_bay') ?>">Đóng</a>
                        <?php endif; ?>
                      </div>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>

            <script>
              const autocompleteInputFor = (select) => {
                return select.closest('form').querySelector('[data-option-select-id="' + select.id + '"]');
              };
              const clearAutocomplete = (select) => {
                const input = autocompleteInputFor(select);
                select.value = '';
                if (input) {
                  input.value = '';
                  input.setCustomValidity('');
                }
              };
              const refreshAutocompleteOptions = (select) => {
                const input = autocompleteInputFor(select);
                const optionList = input ? document.getElementById(input.getAttribute('list')) : null;
                if (!optionList) {
                  return;
                }
                optionList.replaceChildren();
                if (select.disabled) {
                  return;
                }
                Array.from(select.options).forEach((option) => {
                  if (!option.value || option.disabled || option.hidden) {
                    return;
                  }
                  const listOption = document.createElement('option');
                  listOption.value = option.textContent.trim();
                  listOption.dataset.optionId = option.value;
                  optionList.appendChild(listOption);
                });
              };

              document.querySelectorAll('[data-airline-selector]').forEach((airlineSelect) => {
                const form = airlineSelect.closest('form');
                const aircraftSelect = form.querySelector('[data-aircraft-selector]');
                const aircraftQuery = form.querySelector('[data-aircraft-query]');
                if (!aircraftSelect) {
                  return;
                }
                const placeholderOption = aircraftSelect.querySelector('option[value=""]');
                const unlockedPlaceholder = aircraftSelect.dataset.aircraftPlaceholder || '-- Chọn Loại tàu bay --';
                const lockedPlaceholder = aircraftSelect.dataset.aircraftLockedPlaceholder || '-- Chọn hãng hàng không trước --';
                const filterAircraft = () => {
                  const airlineId = airlineSelect.value;
                  const locked = airlineId === '';
                  aircraftSelect.disabled = locked;
                  if (aircraftQuery) {
                    aircraftQuery.disabled = locked;
                  }
                  if (placeholderOption) {
                    placeholderOption.textContent = locked ? lockedPlaceholder : unlockedPlaceholder;
                  }
                  Array.from(aircraftSelect.options).forEach((option) => {
                    if (!option.value) {
                      option.hidden = false;
                      option.disabled = false;
                      return;
                    }
                    const airlineIds = (option.dataset.airlineIds || '').split(',').filter(Boolean);
                    const matches = !locked && (airlineIds.length === 0 || airlineIds.includes(airlineId));
                    option.hidden = !matches;
                    option.disabled = !matches;
                  });
                  const selected = aircraftSelect.selectedOptions[0];
                  if (selected && selected.value) {
                    const airlineIds = (selected.dataset.airlineIds || '').split(',').filter(Boolean);
                    if (locked || (airlineIds.length > 0 && !airlineIds.includes(airlineId))) {
                      clearAutocomplete(aircraftSelect);
                      aircraftSelect.dispatchEvent(new Event('change'));
                    }
                  }
                  refreshAutocompleteOptions(aircraftSelect);
                };
                airlineSelect.addEventListener('change', filterAircraft);
                filterAircraft();
              });

              document.querySelectorAll('[data-option-query]').forEach((queryInput) => {
                const form = queryInput.closest('form');
                const optionSelect = form.querySelector('#' + queryInput.dataset.optionSelectId);
                const optionList = document.getElementById(queryInput.getAttribute('list'));
                if (!optionSelect || !optionList) {
                  return;
                }
                const syncOption = () => {
                  const match = Array.from(optionList.options).find((option) => option.value === queryInput.value.trim());
                  const selectedOption = match && Array.from(optionSelect.options).find((option) => (
                    option.value === match.dataset.optionId && !option.disabled && !option.hidden
                  ));
                  const validMatch = Boolean(selectedOption && !optionSelect.disabled);
                  optionSelect.value = validMatch ? match.dataset.optionId : '';
                  queryInput.setCustomValidity(validMatch || queryInput.value.trim() === '' ? '' : 'Vui lòng chọn mục trong danh sách gợi ý.');
                  optionSelect.dispatchEvent(new Event('change'));
                };
                queryInput.addEventListener('input', syncOption);
                queryInput.addEventListener('change', syncOption);
                syncOption();
              });

              document.querySelectorAll('[data-capacity-selector]').forEach((aircraftSelect) => {
                const form = aircraftSelect.closest('form');
                const passengerInput = form.querySelector('[data-capacity-passengers]');
                const baggageInput = form.querySelector('[data-capacity-baggage]');
                const passengerLabel = form.querySelector('[data-passenger-capacity-label]');
                const baggageLabel = form.querySelector('[data-baggage-capacity-label]');
                const updateCapacities = () => {
                  const aircraft = aircraftSelect.selectedOptions[0];
                  const passengerCapacity = aircraft?.dataset.passengerCapacity || '';
                  const baggageCapacity = aircraft?.dataset.baggageCapacityTon || '';
                  passengerInput.max = passengerCapacity;
                  baggageInput.max = baggageCapacity;
                  passengerLabel.textContent = passengerCapacity
                    ? `Sức chứa tối đa: ${passengerCapacity} khách.`
                    : 'Chọn loại tàu bay để xem sức chứa tối đa.';
                  baggageLabel.textContent = baggageCapacity
                    ? `Tải trọng cất cánh tối đa: ${Number(baggageCapacity).toLocaleString('vi-VN')} tấn.`
                    : 'Chọn loại tàu bay để xem tải trọng tối đa.';
                };
                aircraftSelect.addEventListener('change', updateCapacities);
                updateCapacities();
              });

              document.querySelectorAll('select[data-flight-status]').forEach((statusSelect) => {
                const form = statusSelect.closest('form');
                const reasonContainer = form.querySelector('[data-status-reason-container]');
                const reasonLabel = reasonContainer.querySelector('[data-status-reason-label]');
                const reasonInput = reasonContainer.querySelector('[data-status-reason]');
                const reasonRequiredMark = reasonLabel.querySelector('[data-status-reason-required]');
                const durationContainer = form.querySelector('[data-delay-duration-container]');
                const durationInput = form.querySelector('[data-delay-duration]');
                const initialStatus = statusSelect.dataset.initialStatus;
                const updateReasonField = () => {
                  const departureInput = form.querySelector('input[name="gio_du_kien"]');
                  const flightDate = departureInput ? departureInput.value.slice(0, 10) : '';
                  const today = <?= json_encode(substr($data['serverNow'], 0, 10)) ?>;
                  if (statusSelect.value !== initialStatus
                    && statusSelect.value === 'Delay'
                    && flightDate !== ''
                    && flightDate > today) {
                    window.alert('Không chỉnh được trạng thái Trễ chuyến vì chuyến bay chưa tới ngày bay.');
                    statusSelect.value = initialStatus;
                  }
                  const needsReason = statusSelect.value !== initialStatus
                    && (statusSelect.value === 'Delay' || statusSelect.value === 'Huy');
                  const isDelay = statusSelect.value === 'Delay';
                  const needsDuration = statusSelect.value !== initialStatus && isDelay;
                  reasonContainer.hidden = !needsReason;
                  reasonInput.required = needsReason;
                  if (reasonRequiredMark) {
                    reasonRequiredMark.hidden = !needsReason;
                  }
                  reasonLabel.textContent = isDelay ? 'Lý do trễ chuyến' : 'Lý do hủy chuyến';
                  reasonInput.placeholder = isDelay ? 'Nhập lý do trễ chuyến' : 'Nhập lý do hủy chuyến';
                  if (durationContainer) {
                    durationContainer.hidden = !needsDuration;
                  }
                  if (durationInput) {
                    durationInput.required = needsDuration;
                    if (!needsDuration) {
                      durationInput.value = '';
                    }
                  }
                };
                statusSelect.addEventListener('change', updateReasonField);
                updateReasonField();
              });

              // Nhãn "+1 ngày" khi giờ hạ cánh rơi sang ngày hôm sau.
              document.querySelectorAll('[data-arrival-next-day]').forEach((badge) => {
                const form = badge.closest('form');
                const departure = form.querySelector('input[name="gio_du_kien"]');
                const arrival = form.querySelector('input[name="gio_thuc_te"]');
                const note = form.querySelector('[data-arrival-next-day-note]');
                if (!departure || !arrival) {
                  return;
                }
                const syncNextDay = () => {
                  const departureDate = departure.value.slice(0, 10);
                  const arrivalDate = arrival.value.slice(0, 10);
                  const nextDay = departureDate !== '' && arrivalDate !== '' && arrivalDate > departureDate;
                  badge.hidden = !nextDay;
                  if (note) {
                    note.hidden = !nextDay;
                    note.textContent = nextDay
                      ? 'Hạ cánh ngày ' + arrivalDate.split('-').reverse().join('/') + ' — sang ngày hôm sau.'
                      : '';
                  }
                };
                departure.addEventListener('input', syncNextDay);
                arrival.addEventListener('input', syncNextDay);
                syncNextDay();
              });

              document.querySelectorAll('[data-origin-selector]').forEach((originSelect) => {
                const form = originSelect.closest('form');
                const resourceSelects = form.querySelectorAll('[data-origin-filter]');
                const filterResources = () => {
                  resourceSelects.forEach((select) => {
                    Array.from(select.options).forEach((option) => {
                      if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                      }
                      const matchesAirport = originSelect.value !== ''
                        && option.dataset.airportId === originSelect.value;
                      option.hidden = !matchesAirport;
                      option.disabled = !matchesAirport;
                      if (!matchesAirport && option.selected) {
                        clearAutocomplete(select);
                      }
                    });
                    select.disabled = originSelect.value === '';
                    const queryInput = autocompleteInputFor(select);
                    if (queryInput) {
                      queryInput.disabled = select.disabled;
                    }
                    refreshAutocompleteOptions(select);
                  });
                };
                originSelect.addEventListener('change', filterResources);
                filterResources();
              });
            </script>
<?php
$content = ob_get_clean();

// Khi nhúng trong popup: chỉ xuất HTML gọn (không sidebar/navbar) để iframe hiển thị đẹp.
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
  <body class="flight-popup-page">
<?= $content ?>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
