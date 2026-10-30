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
    <style>
      body.flight-log-page { background: #f4f6f9; padding: 1rem; }
      body.flight-log-page .card { margin-bottom: 0; }
    </style>
  </head>
  <body class="flight-log-page">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title h5">
          Nhật ký chuyến bay <?= $escape($logFlight['so_hieu'] ?? '') ?>
          <?php if ($logFlight && !empty($logFlight['gio_du_kien'])): ?>
            <span class="text-secondary fw-normal small">
              (<?= $escape(date('d/m/Y H:i', strtotime($logFlight['gio_du_kien']))) ?>)
            </span>
          <?php endif; ?>
        </h2>
      </div>
      <div class="card-body">
        <?php if ($logFlight === null): ?>
          <p class="mb-3">Không tìm thấy chuyến bay.</p>
        <?php elseif (!$logRows): ?>
          <p class="mb-3 text-secondary">Chưa có nhật ký cho chuyến bay này.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th>Thời gian</th>
                  <th>Người thực hiện</th>
                  <th>Hành động</th>
                  <th>Trạng thái</th>
                  <th>Nội dung</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logRows as $log): ?>
                  <tr>
                    <td class="text-nowrap"><?= $escape(date('d/m/Y H:i', strtotime($log['thoi_gian_cap_nhat']))) ?></td>
                    <td>
                      <?php if (empty($log['id_nguoi_dung'])): ?>
                        <span class="badge text-bg-secondary">Hệ thống</span>
                      <?php else: ?>
                        <?= $escape($log['ho_ten'] ?? '') ?>
                      <?php endif; ?>
                    </td>
                    <td><?= $escape($logActionLabels[$log['hanh_dong']] ?? $log['hanh_dong']) ?></td>
                    <td class="text-nowrap">
                      <?php if (!empty($log['trang_thai_cu'])): ?>
                        <?= $escape($logStatusLabels[$log['trang_thai_cu']] ?? $log['trang_thai_cu']) ?> →
                      <?php endif; ?>
                      <strong><?= $escape($logStatusLabels[$log['trang_thai']] ?? $log['trang_thai']) ?></strong>
                    </td>
                    <td><?= $escape($log['ghi_chu'] ?? '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
        <div class="mt-3">
          <button class="btn btn-outline-danger" type="button" data-flight-popup-close>Đóng</button>
        </div>
      </div>
    </div>
    <script>
      document.querySelectorAll('[data-flight-popup-close]').forEach((button) => {
        button.addEventListener('click', () => {
          if (window.parent && window.parent !== window) {
            window.parent.postMessage({ type: 'flight-popup-close' }, window.location.origin);
          }
        });
      });
    </script>
  </body>
</html>
    <?php
    exit();
}

$flight = $data['editing'];
$statusLabels = $data['statuses'];
$closeControl = $popup
    ? '<button type="button" class="btn btn-outline-danger" data-flight-popup-close>Đóng</button>'
    : '<a class="btn btn-outline-secondary" href="' . $escape($url('chuyen_bay')) . '">Quay lại danh sách</a>';
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
            <?php if ($data['flash']): ?>
              <div class="alert alert-<?= $escape($data['flash']['type']) ?>" role="alert">
                <?= $escape($data['flash']['message']) ?>
              </div>
            <?php endif; ?>

            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5">
                  <?= $flight ? 'Thông tin chuyến ' . $escape($flight['so_hieu']) : 'Thông tin chuyến bay' ?>
                </h2>
              </div>
              <div class="card-body">
                <?php if (!$flight): ?>
                  <p class="mb-3">Không tìm thấy chuyến bay cần sửa.</p>
                  <?= $closeControl ?>
                <?php else: ?>
                  <?php if (in_array($flight['trang_thai_tong'], ['Cat_Canh', 'Ha_Canh'], true)): ?>
                    <div class="alert alert-warning" role="alert">
                      Chuyến bay số hiệu <strong><?= $escape($flight['so_hieu']) ?></strong> đang ở trạng thái
                      <strong><?= $escape($statusLabels[$flight['trang_thai_tong']] ?? $flight['trang_thai_tong']) ?></strong>,
                      nên không thể thay đổi thông tin. Xin cảm ơn.
                    </div>
                    <?= $closeControl ?>
                  <?php else: ?>
                    <form method="post" action="<?= $url('chuyen_bay_sua', ['id' => (int) $flight['id']] + ($popup ? ['popup' => 1] : [])) ?>" class="row g-3">
                      <input type="hidden" name="csrf" value="<?= $escape($data['csrf']) ?>">
                      <input type="hidden" name="action" value="update">
                      <?php if ($popup): ?>
                        <input type="hidden" name="popup" value="1">
                      <?php endif; ?>
                      <input type="hidden" name="return_page" value="chuyen_bay_edit_modal">
                      <input type="hidden" name="return_id" value="<?= (int) $flight['id'] ?>">
                      <input type="hidden" name="id" value="<?= (int) $flight['id'] ?>">
                      <div class="col-md-4">
                        <label class="form-label" for="edit_so_hieu">Mã chuyến bay</label>
                        <input class="form-control" id="edit_so_hieu" name="so_hieu" maxlength="20" required value="<?= $escape($flight['so_hieu']) ?>">
                      </div>
                      <?php foreach ([
                          'id_hang_hang_khong' => ['Hãng hàng không', $data['airlines']],
                          'id_loai_tau_bay' => ['Loại tàu bay', $data['aircraftTypes']],
                          'id_san_bay_di' => ['Sân bay đi', $data['airports']],
                          'id_san_bay_den' => ['Sân bay đến', $data['airports']],
                      ] as $field => $select): ?>
                        <div class="col-md-4">
                          <label class="form-label" for="edit_<?= $escape($field) ?>"><?= $escape($select[0]) ?></label>
                          <select class="form-select" id="edit_<?= $escape($field) ?>" name="<?= $escape($field) ?>" required <?= $field === 'id_san_bay_di' ? 'data-origin-selector' : '' ?> <?= $field === 'id_loai_tau_bay' ? 'data-capacity-selector' : '' ?>>
                            <?php foreach ($select[1] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" <?= $field === 'id_loai_tau_bay' ? 'data-passenger-capacity="' . (int) $option['suc_chua_hanh_khach'] . '" data-baggage-capacity-ton="' . $escape((float) $option['tai_trong_cat_canh_mtow']) . '"' : '' ?> <?= (int) $flight[$field] === (int) $option['id'] ? 'selected' : '' ?>>
                                <?= $escape(($option['ma_iata'] ?? '') . (in_array($field, ['id_san_bay_di', 'id_san_bay_den'], true) ? ' – ' : ' ') . $option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      <?php endforeach; ?>
                      <div class="col-md-4">
                        <label class="form-label" for="edit_gio_du_kien">Giờ cất cánh</label>
                        <input type="datetime-local" class="form-control" id="edit_gio_du_kien" name="gio_du_kien" required value="<?= $escape($formatDateTimeInput($flight['gio_du_kien'])) ?>">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="edit_gio_thuc_te">
                          Giờ dự kiến hạ cánh
                          <span class="badge text-bg-warning ms-1" data-arrival-next-day title="Chuyến hạ cánh vào ngày hôm sau (qua nửa đêm)" hidden>+1 ngày</span>
                        </label>
                        <input type="datetime-local" class="form-control" id="edit_gio_thuc_te" name="gio_thuc_te" value="<?= $escape($formatDateTimeInput($flight['gio_thuc_te'])) ?>">
                        <small class="text-secondary d-block" data-arrival-next-day-note hidden></small>
                      </div>
                      <?php foreach ([
                          'id_cua_ra_may_bay' => ['Cửa ra máy bay', $data['gates']],
                          'id_quay_checkin' => ['Quầy check-in', $data['checkins']],
                          'id_bang_chuyen' => ['Băng chuyền hành lý', $data['belts']],
                      ] as $field => $select): ?>
                        <div class="col-md-4">
                          <label class="form-label" for="edit_<?= $escape($field) ?>"><?= $escape($select[0]) ?></label>
                          <select class="form-select" id="edit_<?= $escape($field) ?>" name="<?= $escape($field) ?>" data-origin-filter>
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
                      <div class="col-md-4">
                        <label class="form-label" for="edit_trang_thai">Trạng thái</label>
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
                        <label class="form-label" for="edit_ghi_chu" data-status-reason-label>Lý do trễ chuyến</label>
                        <textarea class="form-control" id="edit_ghi_chu" name="ghi_chu" rows="2" maxlength="255" data-status-reason placeholder="Nhập lý do trễ chuyến"></textarea>
                      </div>
                      <div class="col-md-4" data-delay-duration-container hidden>
                        <label class="form-label" for="edit_thoi_gian_tre">Khoảng thời gian dự kiến trễ (phút)</label>
                        <input type="number" min="1" max="1440" step="1" class="form-control" id="edit_thoi_gian_tre"
                               name="thoi_gian_tre_phut" data-delay-duration placeholder="Ví dụ: 45"
                               value="<?= $escape((string) ($flight['thoi_gian_tre_phut'] ?? '')) ?>">
                        <small class="text-secondary d-block" data-delay-duration-note>
                          Thời gian dự kiến trễ so với giờ cất cánh (1–1440 phút).
                        </small>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="edit_hanh_khach">Số hành khách</label>
                        <input type="number" min="0" class="form-control" id="edit_hanh_khach" name="luong_hanh_khach" data-capacity-passengers value="<?= (int) $flight['luong_hanh_khach'] ?>">
                        <small class="text-secondary" data-passenger-capacity-label></small>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="edit_hang_hoa">Hành lý (tấn)</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="edit_hang_hoa" name="luong_hanh_ly_tan" data-capacity-baggage value="<?= $escape((float) $flight['luong_hang_hoa_tan']) ?>">
                        <small class="text-secondary" data-baggage-capacity-label></small>
                      </div>
                      <div class="col-12">
                        <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
                        <?php if ($popup): ?>
                          <button class="btn btn-outline-danger" type="button" data-flight-popup-close>Đóng</button>
                        <?php else: ?>
                          <a class="btn btn-outline-danger" href="<?= $url('chuyen_bay') ?>">Đóng</a>
                        <?php endif; ?>
                      </div>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>

            <script>
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
                    ? `Tải trọng cất cánh tối đa: ${Number(baggageCapacity).toLocaleString('vi-VN')} kg.`
                    : 'Chọn loại tàu bay để xem tải trọng tối đa.';
                };
                aircraftSelect.addEventListener('change', updateCapacities);
                updateCapacities();
              });

              document.querySelectorAll('[data-flight-status]').forEach((statusSelect) => {
                const form = statusSelect.closest('form');
                const reasonContainer = form.querySelector('[data-status-reason-container]');
                const reasonLabel = reasonContainer.querySelector('[data-status-reason-label]');
                const reasonInput = reasonContainer.querySelector('[data-status-reason]');
                const durationContainer = form.querySelector('[data-delay-duration-container]');
                const durationInput = form.querySelector('[data-delay-duration]');
                const initialStatus = statusSelect.dataset.initialStatus;
                const updateReasonField = () => {
                  const departureInput = form.querySelector('input[name="gio_du_kien"]');
                  const flightDate = departureInput ? departureInput.value.slice(0, 10) : '';
                  const today = <?= json_encode(substr($data['serverNow'], 0, 10)) ?>;
                  if (statusSelect.value === 'Delay' && flightDate !== '' && flightDate > today) {
                    window.alert('Không chỉnh được trạng thái Trễ chuyến vì chuyến bay chưa tới ngày bay.');
                    statusSelect.value = initialStatus;
                  }
                  const needsReason = statusSelect.value !== initialStatus
                    && (statusSelect.value === 'Delay' || statusSelect.value === 'Huy');
                  const isDelay = statusSelect.value === 'Delay';
                  const needsDuration = statusSelect.value !== initialStatus && isDelay;
                  reasonContainer.hidden = !needsReason;
                  reasonInput.required = needsReason;
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
                        select.value = '';
                      }
                    });
                    select.disabled = originSelect.value === '';
                  });
                };
                originSelect.addEventListener('change', filterResources);
                filterResources();
              });
              // Nút "Đóng" khi form được mở trong popup.
              document.querySelectorAll('[data-flight-popup-close]').forEach((button) => {
                button.addEventListener('click', () => {
                  if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'flight-popup-close' }, window.location.origin);
                  }
                });
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
    <style>
      body.flight-popup-page { background: #f4f6f9; padding: 1rem; }
      body.flight-popup-page .card { margin-bottom: 0; }
    </style>
  </head>
  <body class="flight-popup-page">
<?= $content ?>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
