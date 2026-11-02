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
            <?php if (!$popup): ?>
            <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
            <?php endif; ?>
            <div class="card">
              <?php if (!$popup): ?>
              <div class="card-header">
                <h2 class="card-title h5 mb-0"><i class="bi bi-airplane-engines me-2" aria-hidden="true"></i>Thông tin chuyến bay mới</h2>
              </div>
              <?php endif; ?>
              <div class="card-body">
                <?php if ($data['flash']): ?>
                  <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                    <?= $e($data['flash']['message']) ?>
                  </div>
                <?php endif; ?>
                <div class="flight-create-intro mb-3">
                  <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                  <span>Mã chuyến được tạo tự động. Hãng bay, tàu bay, tuyến và giờ bay là các thông tin bắt buộc.</span>
                </div>
                <form method="post" action="<?= $url('chuyen_bay_them') . ($popup ? '?popup=1' : '') ?>" class="flight-create-form">
                  <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                  <input type="hidden" name="action" value="create">
                  <input type="hidden" name="return_page" value="chuyen_bay_them">
                  <?php if ($popup): ?>
                    <input type="hidden" name="popup" value="1">
                  <?php endif; ?>
                  <section class="flight-form-section mb-3" aria-labelledby="flight-create-route-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-create-route-title">Hành trình và tàu bay</h3>
                        <p>Chọn hãng trước, sau đó chọn tàu bay phù hợp và xác định sân bay đi, đến.</p>
                      </div>
                    </div>
                    <div class="row g-3 flight-form-grid">
                  <?php foreach ([
                      'id_hang_hang_khong' => ['Hãng hàng không', $data['airlines']],
                      'id_loai_tau_bay' => ['Loại tàu bay', $data['aircraftTypes']],
                      'id_san_bay_di' => ['Sân bay đi', $data['airports']],
                      'id_san_bay_den' => ['Sân bay đến', $data['airports']],
                  ] as $field => $select): ?>
                    <div class="col-md-4">
                      <?php $isAircraftField = $field === 'id_loai_tau_bay'; ?>
                      <?php $isAirlineField = $field === 'id_hang_hang_khong'; ?>
                      <?php $isAirportField = in_array($field, ['id_san_bay_di', 'id_san_bay_den'], true); ?>
                      <?php if ($isAirportField || $isAirlineField || $isAircraftField): ?>
                        <?php
                          $optionListId = 'them_' . $field . '_options';
                          $optionQueryId = 'them_' . $field . '_search';
                          $selectedOptionText = '';
                          foreach ($select[1] as $listOption) {
                              if ((string) $addValue($field) === (string) $listOption['id']) {
                                  $selectedOptionText = $isAircraftField
                                      ? $listOption['label']
                                      : (($listOption['ma_iata'] ?? '')
                                          . ($isAirportField ? ' – ' : ' ')
                                          . $listOption['label']);
                                  break;
                              }
                          }
                        ?>
                        <label class="form-label" for="<?= $e($optionQueryId) ?>"><?= $e($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" class="form-control" id="<?= $e($optionQueryId) ?>"
                               list="<?= $e($optionListId) ?>" data-option-query
                               data-option-select-id="them_<?= $e($field) ?>"
                               <?= $isAircraftField ? 'data-aircraft-query' : '' ?>
                               placeholder="Nhập mã hoặc tên <?= $isAirlineField ? 'hãng hàng không' : ($isAircraftField ? 'loại tàu bay' : ($field === 'id_san_bay_di' ? 'sân bay đi' : 'sân bay đến')) ?>"
                               autocomplete="off" required <?= $isAircraftField && $selectedAirline === '' ? 'disabled' : '' ?> value="<?= $e($selectedOptionText) ?>">
                        <datalist id="<?= $e($optionListId) ?>">
                          <?php foreach ($select[1] as $listOption): ?>
                            <option value="<?= $e($isAircraftField ? $listOption['label'] : (($listOption['ma_iata'] ?? '') . ($isAirportField ? ' – ' : ' ') . $listOption['label'])) ?>" data-option-id="<?= (int) $listOption['id'] ?>"></option>
                          <?php endforeach; ?>
                        </datalist>
                      <?php else: ?>
                        <label class="form-label" for="them_<?= $e($field) ?>"><?= $e($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                      <?php endif; ?>
                      <select class="form-select" id="them_<?= $e($field) ?>" name="<?= $e($field) ?>" <?= $isAirportField || $isAirlineField || $isAircraftField ? 'hidden aria-hidden="true" tabindex="-1"' : 'required' ?> <?= $field === 'id_san_bay_di' ? 'data-origin-selector' : '' ?> <?= $isAircraftField ? 'data-aircraft-selector data-aircraft-placeholder="-- Chọn Loại tàu bay --" data-aircraft-locked-placeholder="-- Chọn hãng hàng không trước --"' : '' ?> <?= $isAirlineField ? 'data-airline-selector' : '' ?> <?= $isAircraftField && $selectedAirline === '' ? 'disabled' : '' ?>>
                        <option value=""><?= $isAircraftField && $selectedAirline === '' ? '-- Chọn hãng hàng không trước --' : '-- Chọn ' . $e($select[0]) . ' --' ?></option>
                        <?php foreach ($select[1] as $option): ?>
                          <option value="<?= (int) $option['id'] ?>" <?= $field === 'id_loai_tau_bay' ? 'data-airline-ids="' . $e($option['airline_ids'] ?? '') . '"' : '' ?> <?= $isAirportField ? 'data-airport-cap="' . $e($option['phan_cap'] ?? '') . '"' : '' ?> <?= (string) $addValue($field) === (string) $option['id'] ? 'selected' : '' ?>><?= $e(($option['ma_iata'] ?? '') . ($isAirportField ? ' – ' : ' ') . $option['label']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  <?php endforeach; ?>
                    </div>
                  </section>

                  <section class="flight-form-section mb-3" aria-labelledby="flight-create-schedule-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-create-schedule-title">Lịch trình</h3>
                        <p>Giờ hạ cánh phải sau giờ cất cánh; nếu bay qua nửa đêm, hãy chọn đúng ngày hôm sau.</p>
                      </div>
                    </div>
                    <div class="row g-3 flight-form-grid">
                  <div class="col-md-4">
                    <label class="form-label" for="them_gio_du_kien">Giờ cất cánh <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="datetime-local" class="form-control" id="them_gio_du_kien" name="gio_du_kien" lang="vi" step="60" required value="<?= $e($addValue('gio_du_kien')) ?>">
                    <small class="form-text text-secondary">Chọn ngày trên lịch và giờ theo định dạng 24 giờ (giờ, phút).</small>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="them_gio_thuc_te">
                      Giờ dự kiến hạ cánh <span class="text-danger" aria-hidden="true">*</span>
                      <span class="badge text-bg-warning ms-1" data-arrival-next-day title="Chuyến hạ cánh vào ngày hôm sau (qua nửa đêm)" hidden>+1 ngày</span>
                    </label>
                    <input type="datetime-local" class="form-control" id="them_gio_thuc_te" name="gio_thuc_te" lang="vi" step="60" required value="<?= $e($addValue('gio_thuc_te')) ?>">
                    <small class="form-text text-secondary">Chọn ngày trên lịch và giờ theo định dạng 24 giờ (giờ, phút).</small>
                    <small class="text-danger d-block" data-flight-time-feedback></small>
                    <small class="text-secondary d-block" data-arrival-next-day-note hidden></small>
                  </div>
                    </div>
                  </section>

                  <section class="flight-form-section mb-3" aria-labelledby="flight-create-services-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-building-check" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-create-services-title">Dịch vụ tại sân bay đi</h3>
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
                          $optionListId = 'them_' . $field . '_options';
                          $optionQueryId = 'them_' . $field . '_search';
                          $selectedOptionText = '';
                          foreach ($select[1] as $listOption) {
                              if ((string) $addValue($field) === (string) $listOption['id']) {
                                  $selectedOptionText = $listOption['label'];
                                  break;
                              }
                          }
                        ?>
                        <label class="form-label" for="<?= $e($optionQueryId) ?>"><?= $e($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" class="form-control" id="<?= $e($optionQueryId) ?>" list="<?= $e($optionListId) ?>"
                               data-option-query data-option-select-id="them_<?= $e($field) ?>"
                               placeholder="Nhập tên <?= $field === 'id_cua_ra_may_bay' ? 'cửa ra máy bay' : ($field === 'id_quay_checkin' ? 'quầy check-in' : 'băng chuyền hành lý') ?>" autocomplete="off"
                               required value="<?= $e($selectedOptionText) ?>">
                        <datalist id="<?= $e($optionListId) ?>">
                          <?php foreach ($select[1] as $option): ?>
                            <option value="<?= $e($option['label']) ?>" data-option-id="<?= (int) $option['id'] ?>"></option>
                          <?php endforeach; ?>
                        </datalist>
                      <?php else: ?>
                        <label class="form-label" for="them_<?= $e($field) ?>"><?= $e($select[0]) ?> <span class="text-danger" aria-hidden="true">*</span></label>
                      <?php endif; ?>
                      <select class="form-select" id="them_<?= $e($field) ?>" name="<?= $e($field) ?>" <?= $isAutocompleteResource ? 'hidden aria-hidden="true" tabindex="-1"' : 'required' ?> data-origin-filter>
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
                    </div>
                  </section>

                  <div class="flight-create-defaults mb-3">
                    <div><i class="bi bi-people-fill" aria-hidden="true"></i><span>Số hành khách được đặt theo sức chứa tàu bay.</span></div>
                    <div><i class="bi bi-box-seam-fill" aria-hidden="true"></i><span>Tải trọng hành lý được đặt theo giới hạn tàu bay.</span></div>
                    <div><i class="bi bi-unlock-fill" aria-hidden="true"></i><span>Chuyến mới sẽ ở trạng thái Đang mở.</span></div>
                  </div>
                  <div class="flight-form-actions d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="small text-secondary"><span class="text-danger">*</span> Thông tin bắt buộc</span>
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Tạo chuyến bay</button>
                    <?php if (!$popup): ?>
                      <a class="btn btn-outline-secondary" href="<?= $url('chuyen_bay') ?>">Danh sách chuyến bay</a>
                    <?php endif; ?>
                    </div>
                  </div>
                </form>
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

                  // Chưa chọn hãng hàng không => khóa ô loại tàu bay.
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
    <title><?= $e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $e(ADMINLTE_URL) ?>/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/assets/css/style.css" />
    <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
  </head>
  <body class="flight-popup-page">
<?= $content ?>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
