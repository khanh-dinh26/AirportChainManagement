<?php
/**
 * ============================================================
 * TRANG: THÊM CHUYẾN BAY  —  views/pages/chuyen_bay/them.php
 * Toàn bộ form thêm chuyến bay (trước đây nằm trong modal ở index.php)
 * được đưa về đây. Hỗ trợ điền lại dữ liệu khi validate thất bại.
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

// Endpoint nhỏ phục vụ kiểm tra trùng chuyến ngay khi nhập.
// Trùng = cùng số hiệu + sân bay đi + sân bay đến + ngày cất cánh (không xét giờ).
if (isset($_GET['check_so_hieu'])) {
    $flightNumber = strtoupper(trim((string) $_GET['check_so_hieu']));
    $checkOrigin = (int) ($_GET['origin'] ?? 0);
    $checkDestination = (int) ($_GET['destination'] ?? 0);
    $checkDate = trim((string) ($_GET['date'] ?? ''));
    $hasFullRoute = $checkOrigin > 0 && $checkDestination > 0
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkDate) === 1
        && strtotime($checkDate) !== false;
    $exists = $hasFullRoute
        && preg_match('/^[A-Z0-9-]{2,20}$/', $flightNumber) === 1
        && (new ChuyenBayController($conn))->flightExistsOnDate(
            $flightNumber,
            $checkOrigin,
            $checkDestination,
            $checkDate
        );
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'exists' => $exists,
        'message' => $exists
            ? 'Chuyến bay ' . $flightNumber . ' theo tuyến này ngày '
                . date('d/m/Y', strtotime($checkDate)) . ' đã tồn tại. Vui lòng chọn ngày hoặc tuyến khác.'
            : '',
    ]);
    exit();
}

// Trang này có thể được mở trực tiếp (trang riêng) hoặc nhúng trong popup ở trang danh sách.
$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

// Thêm thành công từ popup: trang nhỏ báo cho trang cha đóng popup và tải lại danh sách.
// Cố ý KHÔNG gọi handle() ở đây để flash thành công còn nguyên trong session cho trang danh sách.
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
$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$url = static function ($page, array $params = []) {
    $paths = [
        'chuyen_bay' => BASE_URL . '/index.php?page=chuyen_bay',
        'chuyen_bay_them' => BASE_URL . '/views/pages/chuyen_bay/them.php',
    ];
    $target = $paths[$page] ?? $paths['chuyen_bay'];

    return $params ? $target . '?' . http_build_query($params) : $target;
};
$addValue = static function ($field, $default = '') use ($data) {
    return $data['oldInput'][$field] ?? $default;
};
// Chỉ khi đã chọn hãng hàng không thì mới cho chọn loại tàu bay.
$selectedAirline = (string) $addValue('id_hang_hang_khong');

$pageTitle = 'Thêm chuyến bay';
$pageHeader = 'Thêm chuyến bay ngày';
$navActive = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Chuyến bay', 'url' => $url('chuyen_bay')],
    ['label' => 'Thêm chuyến bay', 'url' => null],
];

ob_start();
?>
            <div class="card">
              <div class="card-header"><h3 class="card-title">Thông tin chuyến bay</h3></div>
              <div class="card-body">
                <?php if ($data['flash']): ?>
                  <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                    <?= $e($data['flash']['message']) ?>
                  </div>
                <?php endif; ?>
                <form method="post" action="<?= $url('chuyen_bay_them') . ($popup ? '?popup=1' : '') ?>" class="row g-3">
                  <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                  <input type="hidden" name="action" value="create">
                  <input type="hidden" name="return_page" value="chuyen_bay_them">
                  <?php if ($popup): ?>
                    <input type="hidden" name="popup" value="1">
                  <?php endif; ?>
                  <div class="col-md-4">
                    <label class="form-label" for="them_so_hieu">Mã chuyến bay</label>
                    <input class="form-control" id="them_so_hieu" name="so_hieu" maxlength="20" required autocomplete="off" placeholder="" value="<?= $e($addValue('so_hieu')) ?>">
                    <small class="text-danger d-block" data-flight-number-feedback></small>
                  </div>
                  <?php foreach ([
                      'id_hang_hang_khong' => ['Hãng hàng không', $data['airlines']],
                      'id_loai_tau_bay' => ['Loại tàu bay', $data['aircraftTypes']],
                      'id_san_bay_di' => ['Sân bay đi', $data['airports']],
                      'id_san_bay_den' => ['Sân bay đến', $data['airports']],
                  ] as $field => $select): ?>
                    <div class="col-md-4">
                      <label class="form-label" for="them_<?= $e($field) ?>"><?= $e($select[0]) ?></label>
                      <?php $isAircraftField = $field === 'id_loai_tau_bay'; ?>
                      <select class="form-select" id="them_<?= $e($field) ?>" name="<?= $e($field) ?>" required <?= $field === 'id_san_bay_di' ? 'data-origin-selector' : '' ?> <?= $isAircraftField ? 'data-capacity-selector data-aircraft-selector data-aircraft-placeholder="-- Chọn Loại tàu bay --" data-aircraft-locked-placeholder="-- Chọn hãng hàng không trước --"' : '' ?> <?= $field === 'id_hang_hang_khong' ? 'data-airline-selector' : '' ?> <?= $isAircraftField && $selectedAirline === '' ? 'disabled' : '' ?>>
                        <option value=""><?= $isAircraftField && $selectedAirline === '' ? '-- Chọn hãng hàng không trước --' : '-- Chọn ' . $e($select[0]) . ' --' ?></option>
                        <?php foreach ($select[1] as $option): ?>
                          <option value="<?= (int) $option['id'] ?>" <?= $field === 'id_loai_tau_bay' ? 'data-passenger-capacity="' . (int) $option['suc_chua_hanh_khach'] . '" data-baggage-capacity-ton="' . $e((float) $option['tai_trong_cat_canh_mtow']) . '" data-airline-ids="' . $e($option['airline_ids'] ?? '') . '"' : '' ?> <?= in_array($field, ['id_san_bay_di', 'id_san_bay_den'], true) ? 'data-airport-cap="' . $e($option['phan_cap'] ?? '') . '"' : '' ?> <?= (string) $addValue($field) === (string) $option['id'] ? 'selected' : '' ?>><?= $e(($option['ma_iata'] ?? '') . (in_array($field, ['id_san_bay_di', 'id_san_bay_den'], true) ? ' – ' : ' ') . $option['label']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  <?php endforeach; ?>
                  <div class="col-md-4">
                    <label class="form-label" for="them_gio_du_kien">Giờ cất cánh</label>
                    <input type="datetime-local" class="form-control" id="them_gio_du_kien" name="gio_du_kien" required value="<?= $e($addValue('gio_du_kien')) ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="them_gio_thuc_te">
                      Giờ dự kiến hạ cánh
                      <span class="badge text-bg-warning ms-1" data-arrival-next-day title="Chuyến hạ cánh vào ngày hôm sau (qua nửa đêm)" hidden>+1 ngày</span>
                    </label>
                    <input type="datetime-local" class="form-control" id="them_gio_thuc_te" name="gio_thuc_te" value="<?= $e($addValue('gio_thuc_te')) ?>">
                    <small class="text-danger d-block" data-flight-time-feedback></small>
                    <small class="text-secondary d-block" data-arrival-next-day-note hidden></small>
                  </div>
                  <?php foreach ([
                      'id_cua_ra_may_bay' => ['Cửa ra máy bay', $data['gates']],
                      'id_quay_checkin' => ['Quầy check-in', $data['checkins']],
                      'id_bang_chuyen' => ['Băng chuyền hành lý', $data['belts']],
                  ] as $field => $select): ?>
                    <div class="col-md-4">
                      <label class="form-label" for="them_<?= $e($field) ?>"><?= $e($select[0]) ?></label>
                      <select class="form-select" id="them_<?= $e($field) ?>" name="<?= $e($field) ?>" data-origin-filter>
                        <option value="">-- Chưa phân công --</option>
                        <?php foreach ($select[1] as $option): ?>
                          <option value="<?= (int) $option['id'] ?>" data-airport-id="<?= (int) $option['id_san_bay'] ?>" data-terminal-type="<?= $e($option['terminal_type'] ?? '') ?>" <?= (string) $addValue($field) === (string) $option['id'] ? 'selected' : '' ?>><?= $e($option['label']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <?php if ($field === 'id_quay_checkin'): ?>
                        <small class="text-secondary d-block">Mở trước giờ cất cánh 2–3 tiếng, đóng trước 40 phút.</small>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                  <div class="col-md-4">
                    <label class="form-label" for="them_trang_thai">Trạng thái</label>
                    <input type="hidden" name="trang_thai_tong" value="Dang_Mo">
                    <input class="form-control" id="them_trang_thai" value="Đang mở" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="them_hanh_khach">Số hành khách</label>
                    <input type="number" min="0" class="form-control" id="them_hanh_khach" name="luong_hanh_khach" data-capacity-passengers value="<?= $e($addValue('luong_hanh_khach', '')) ?>">
                    <small class="text-secondary" data-passenger-capacity-label></small>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="them_hang_hoa">Hành lý (tấn)</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="them_hang_hoa" name="luong_hanh_ly_tan" data-capacity-baggage value="<?= $e($addValue('luong_hanh_ly_tan', '')) ?>">
                    <small class="text-secondary" data-baggage-capacity-label></small>
                  </div>
                  <div class="col-12">
                    <button class="btn btn-primary" type="submit">Thêm chuyến bay</button>
                    <?php if ($popup): ?>
                      <button class="btn btn-outline-danger" type="button" data-flight-popup-close>Đóng</button>
                    <?php else: ?>
                      <a class="btn btn-outline-secondary" href="<?= $url('chuyen_bay') ?>">Danh sách chuyến bay</a>
                    <?php endif; ?>
                  </div>
                </form>
              </div>
            </div>

            <script>
              const flightNumberInput = document.getElementById('them_so_hieu');
              const flightNumberFeedback = document.querySelector('[data-flight-number-feedback]');
              if (flightNumberInput && flightNumberFeedback) {
                let checkTimer = null;
                let lastChecked = '';
                const originSelect = document.getElementById('them_id_san_bay_di');
                const destinationSelect = document.getElementById('them_id_san_bay_den');
                const departureField = document.getElementById('them_gio_du_kien');
                const routeParts = () => ({
                  number: flightNumberInput.value.trim().toUpperCase(),
                  origin: originSelect ? originSelect.value : '',
                  destination: destinationSelect ? destinationSelect.value : '',
                  date: departureField ? departureField.value.slice(0, 10) : '',
                });
                const setDuplicateState = (isDuplicate, message) => {
                  flightNumberInput.classList.toggle('is-invalid', isDuplicate);
                  flightNumberInput.setCustomValidity(isDuplicate ? message : '');
                  flightNumberFeedback.textContent = isDuplicate ? message : '';
                };
                const checkFlightNumber = () => {
                  const parts = routeParts();
                  flightNumberInput.value = parts.number;
                  // Chỉ kiểm tra khi đã đủ số hiệu + tuyến + ngày (trùng = cùng cả 3, không xét giờ).
                  if (!/^[A-Z0-9-]{2,20}$/.test(parts.number)
                    || parts.origin === ''
                    || parts.destination === ''
                    || parts.date === '') {
                    lastChecked = '';
                    setDuplicateState(false, '');
                    return;
                  }
                  const key = [parts.number, parts.origin, parts.destination, parts.date].join('|');
                  if (key === lastChecked) {
                    return;
                  }
                  lastChecked = key;
                  fetch(`${window.location.pathname}?check_so_hieu=${encodeURIComponent(parts.number)}`
                    + `&origin=${encodeURIComponent(parts.origin)}`
                    + `&destination=${encodeURIComponent(parts.destination)}`
                    + `&date=${encodeURIComponent(parts.date)}`, {
                    headers: { Accept: 'application/json' },
                  })
                    .then((response) => response.ok ? response.json() : null)
                    .then((result) => {
                      const current = routeParts();
                      const currentKey = [current.number, current.origin, current.destination, current.date].join('|');
                      if (!result || currentKey !== key) {
                        return;
                      }
                      setDuplicateState(
                        result.exists === true,
                        result.message || 'Chuyến bay này đã tồn tại trong ngày đã chọn. Vui lòng chọn ngày hoặc tuyến khác.'
                      );
                    })
                    .catch(() => {});
                };
                const scheduleCheck = () => {
                  clearTimeout(checkTimer);
                  setDuplicateState(false, '');
                  checkTimer = setTimeout(checkFlightNumber, 400);
                };
                flightNumberInput.addEventListener('input', scheduleCheck);
                flightNumberInput.addEventListener('blur', () => {
                  clearTimeout(checkTimer);
                  checkFlightNumber();
                });
                [originSelect, destinationSelect, departureField].forEach((field) => {
                  if (field) {
                    field.addEventListener('change', scheduleCheck);
                  }
                });
              }

              document.querySelectorAll('[data-airline-selector]').forEach((airlineSelect) => {
                const form = airlineSelect.closest('form');
                const aircraftSelect = form.querySelector('[data-aircraft-selector]');
                if (!aircraftSelect) {
                  return;
                }
                const placeholderOption = aircraftSelect.querySelector('option[value=""]');
                const unlockedPlaceholder = aircraftSelect.dataset.aircraftPlaceholder || '-- Chọn Loại tàu bay --';
                const lockedPlaceholder = aircraftSelect.dataset.aircraftLockedPlaceholder || '-- Chọn hãng hàng không trước --';
                const filterAircraft = () => {
                  const airlineId = airlineSelect.value;
                  const locked = airlineId === '';

                  // Chưa chọn hãng hàng không => khóa ô loại tàu bay.
                  aircraftSelect.disabled = locked;
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
                    const matches = !locked && (airlineIds.length === 0
                      || airlineIds.includes(airlineId));
                    option.hidden = !matches;
                    option.disabled = !matches;
                  });
                  const selected = aircraftSelect.selectedOptions[0];
                  if (selected && selected.value) {
                    const airlineIds = (selected.dataset.airlineIds || '').split(',').filter(Boolean);
                    const stillValid = !locked && (airlineIds.length === 0
                      || airlineIds.includes(airlineId));
                    if (!stillValid) {
                      aircraftSelect.value = '';
                      aircraftSelect.dispatchEvent(new Event('change'));
                    }
                  }
                };
                airlineSelect.addEventListener('change', filterAircraft);
                filterAircraft();
              });

              const departureInput = document.getElementById('them_gio_du_kien');
              const arrivalInput = document.getElementById('them_gio_thuc_te');
              const timeFeedback = document.querySelector('[data-flight-time-feedback]');
              if (departureInput && arrivalInput) {
                const form = departureInput.closest('form');
                const nextDayBadge = form ? form.querySelector('[data-arrival-next-day]') : null;
                const nextDayNote = form ? form.querySelector('[data-arrival-next-day-note]') : null;
                const validateFlightTimes = () => {
                  const isInvalid = departureInput.value !== ''
                    && arrivalInput.value !== ''
                    && arrivalInput.value <= departureInput.value;
                  const message = 'Giờ dự kiến hạ cánh phải sau giờ cất cánh.';
                  arrivalInput.classList.toggle('is-invalid', isInvalid);
                  arrivalInput.setCustomValidity(isInvalid ? message : '');
                  if (timeFeedback) {
                    timeFeedback.textContent = isInvalid ? message : '';
                  }

                  // Hạ cánh qua nửa đêm: ngày hạ cánh lớn hơn ngày cất cánh -> hiện nhãn "+1 ngày".
                  const departureDate = departureInput.value.slice(0, 10);
                  const arrivalDate = arrivalInput.value.slice(0, 10);
                  const nextDay = !isInvalid && departureDate !== '' && arrivalDate !== '' && arrivalDate > departureDate;
                  if (nextDayBadge) {
                    nextDayBadge.hidden = !nextDay;
                  }
                  if (nextDayNote) {
                    nextDayNote.hidden = !nextDay;
                    nextDayNote.textContent = nextDay
                      ? 'Hạ cánh ngày ' + arrivalDate.split('-').reverse().join('/') + ' — sang ngày hôm sau.'
                      : '';
                  }
                };
                departureInput.addEventListener('input', validateFlightTimes);
                arrivalInput.addEventListener('input', validateFlightTimes);
                validateFlightTimes();
              }

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

              document.querySelectorAll('[data-origin-selector]').forEach((originSelect) => {
                const form = originSelect.closest('form');
                const resourceSelects = form.querySelectorAll('[data-origin-filter]');
                // Sân bay đi quốc tế -> chỉ hiện cửa/quầy/băng chuyền của nhà ga quốc tế,
                // ẩn các nhà ga quốc nội (và ngược lại). Nhà ga hỗn hợp luôn hiện.
                const terminalAllowed = (terminalType, airportCap) => {
                  if (!terminalType) {
                    return true;
                  }
                  // Nhà ga hỗn hợp phục vụ cả quốc nội và quốc tế -> luôn hiển thị cả 2 khu.
                  if (terminalType === 'Hon_Hop') {
                    return true;
                  }
                  if (airportCap === 'Quoc_Te') {
                    return terminalType !== 'Quoc_Noi';
                  }
                  if (airportCap === 'Quoc_Noi') {
                    return terminalType !== 'Quoc_Te';
                  }
                  return true;
                };
                const filterResources = () => {
                  const originOption = originSelect.selectedOptions[0];
                  const originCap = originSelect.value !== '' && originOption
                    ? (originOption.dataset.airportCap || '')
                    : '';
                  resourceSelects.forEach((select) => {
                    Array.from(select.options).forEach((option) => {
                      if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                      }
                      const matchesAirport = originSelect.value !== ''
                        && option.dataset.airportId === originSelect.value;
                      const matchesZone = terminalAllowed(option.dataset.terminalType || '', originCap);
                      const matches = matchesAirport && matchesZone;
                      option.hidden = !matches;
                      option.disabled = !matches;
                      if (!matches && option.selected) {
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
    <title><?= $e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $e(ADMINLTE_URL) ?>/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/assets/css/style.css" />
    <style>
      body.flight-popup-page { background: #f4f6f9; padding: 1rem; }
      body.flight-popup-page .card { margin-bottom: 0; }
    </style>
  </head>
  <body class="flight-popup-page">
<?= $content ?>
    <script>
      // Mở trực tiếp (không nhúng) thì không cần nút đóng popup.
      if (window.parent === window) {
        document.querySelectorAll('[data-flight-popup-close]').forEach(function (button) {
          button.hidden = true;
        });
      }
    </script>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
