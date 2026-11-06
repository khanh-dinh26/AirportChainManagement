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

yeu_cau_dang_nhap();

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
          window.location.replace('<?= BASE_URL ?>/index.php?page=chuyen_bay');
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
            <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css?v=<?= time() ?>" />
            <style>
              /* Tinh chỉnh form to ra và các item cách đều, thoáng đãng */
              body.flight-popup-page {
                padding: 1.25rem 1.5rem !important;
                background-color: #f4f6f9;
              }
              .flight-create-form-container {
                max-width: 1240px;
                margin: 0 auto;
              }
              .flight-form-section {
                padding: 1.35rem 1.5rem !important;
                margin-bottom: 1.5rem !important;
                border: 1px solid #dce8f4 !important;
                border-radius: 0.95rem !important;
                background: #ffffff !important;
                box-shadow: 0 0.125rem 0.5rem rgba(30, 60, 100, 0.04) !important;
              }
              .flight-form-section-heading {
                margin-bottom: 1.15rem !important;
              }
              .flight-form-section-heading h3 {
                font-size: 1.08rem !important;
                font-weight: 700 !important;
                color: #1e293b !important;
              }
              .flight-form-section-heading p {
                font-size: 0.88rem !important;
                color: #64748b !important;
              }
              .flight-form-grid {
                --bs-gutter-x: 1.25rem !important;
                --bs-gutter-y: 1.25rem !important;
              }
              .flight-form-grid > [class*="col-"] {
                padding-right: calc(var(--bs-gutter-x) * 0.5) !important;
                padding-left: calc(var(--bs-gutter-x) * 0.5) !important;
                border: none !important;
                background: transparent !important;
              }
              .flight-form-card-item {
                height: 100%;
                padding: 1.1rem 1.25rem !important;
                border: 1px solid #d8e6f5 !important;
                border-radius: 0.8rem !important;
                background: #f8fbff !important;
                box-shadow: 0 1px 4px rgba(30, 60, 100, 0.03) !important;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, background-color 0.15s ease-in-out;
              }
              .flight-form-card-item:focus-within {
                background: #ffffff !important;
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
              }
              .flight-form-card-item .form-label {
                display: block;
                font-size: 0.95rem !important;
                font-weight: 650 !important;
                color: #1e293b !important;
                margin-bottom: 0.5rem !important;
                min-height: 1.5rem;
              }
              .flight-form-card-item .form-select,
              .flight-form-card-item .form-control {
                min-height: 2.85rem !important;
                padding: 0.5rem 0.85rem !important;
                font-size: 0.95rem !important;
                border-radius: 0.55rem !important;
                border-color: #cbd5e1;
              }
              .flight-form-card-item .form-select:focus,
              .flight-form-card-item .form-control:focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
              }
              .flight-form-card-item small,
              .flight-form-card-item .form-text {
                font-size: 0.84rem !important;
                margin-top: 0.45rem !important;
                line-height: 1.45;
              }
              .flight-create-defaults {
                padding: 1rem 1.25rem !important;
                font-size: 0.9rem !important;
                border-radius: 0.8rem !important;
              }
              .flight-create-submit {
                min-height: 2.85rem;
                padding: 0.65rem 1.85rem !important;
                font-size: 1rem !important;
                font-weight: 600 !important;
                border-radius: 0.6rem !important;
              }
            </style>
            <div class="card flight-create-form-container border-0 shadow-sm">
              <?php if (!$popup): ?>
              <div class="card-header bg-white py-3">
                <h2 class="card-title h5 mb-0 text-primary fw-bold"><i class="bi bi-airplane-engines me-2" aria-hidden="true"></i>Thông tin chuyến bay mới</h2>
              </div>
              <?php endif; ?>
              <div class="card-body p-3 p-md-4">
                <?php if ($data['flash']): ?>
                  <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                    <?= $e($data['flash']['message']) ?>
                  </div>
                <?php endif; ?>
                <div class="flight-create-intro mb-4">
                  <i class="bi bi-info-circle-fill me-1" aria-hidden="true"></i>
                  <span>Mã chuyến được tạo tự động. Hãng bay, tàu bay, tuyến và giờ bay là các thông tin bắt buộc.</span>
                </div>
                <form method="post" action="<?= $url('chuyen_bay_them') . ($popup ? '?popup=1' : '') ?>" class="flight-create-form">
                  <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                  <input type="hidden" name="action" value="create">
                  <input type="hidden" name="return_page" value="chuyen_bay_them">
                  <?php if ($popup): ?>
                    <input type="hidden" name="popup" value="1">
                  <?php endif; ?>

                  <!-- PHẦN 1: HÀNH TRÌNH VÀ TÀU BAY (DROPDOWN CHỌN DANH SÁCH) -->
                  <section class="flight-form-section mb-4" aria-labelledby="flight-create-route-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-create-route-title">Hành trình và tàu bay</h3>
                        <p>Chọn hãng trước, sau đó chọn loại tàu bay phù hợp và xác định sân bay đi, đến.</p>
                      </div>
                    </div>
                    <div class="row g-3 flight-form-grid">
                      <!-- 1. HÃNG HÀNG KHÔNG -->
                      <div class="col-12 col-md-6">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_id_hang_hang_khong">
                            Hãng hàng không <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <select class="form-select" id="them_id_hang_hang_khong" name="id_hang_hang_khong" required data-airline-selector>
                            <option value="">-- Chọn Hãng hàng không --</option>
                            <?php foreach ($data['airlines'] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" <?= (string) $addValue('id_hang_hang_khong') === (string) $option['id'] ? 'selected' : '' ?>>
                                <?= $e(($option['ma_iata'] ? $option['ma_iata'] . ' – ' : '') . $option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="form-text text-secondary">Hãng hàng không khai thác chuyến bay.</small>
                        </div>
                      </div>

                      <!-- 2. LOẠI TÀU BAY -->
                      <div class="col-12 col-md-6">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_id_loai_tau_bay">
                            Loại tàu bay <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <select class="form-select" id="them_id_loai_tau_bay" name="id_loai_tau_bay" required 
                                  data-aircraft-selector 
                                  data-aircraft-placeholder="-- Chọn Loại tàu bay --" 
                                  data-aircraft-locked-placeholder="-- Chọn hãng trước --"
                                  <?= $selectedAirline === '' ? 'disabled' : '' ?>>
                            <option value=""><?= $selectedAirline === '' ? '-- Chọn hãng trước --' : '-- Chọn Loại tàu bay --' ?></option>
                            <?php foreach ($data['aircraftTypes'] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" 
                                      data-airline-ids="<?= $e($option['airline_ids'] ?? '') ?>" 
                                      <?= (string) $addValue('id_loai_tau_bay') === (string) $option['id'] ? 'selected' : '' ?>>
                                <?= $e($option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="form-text text-secondary">Dòng tàu bay trong đội bay của hãng.</small>
                        </div>
                      </div>

                      <!-- 3. SÂN BAY ĐI -->
                      <div class="col-12 col-md-6">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_id_san_bay_di">
                            Sân bay đi <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <select class="form-select" id="them_id_san_bay_di" name="id_san_bay_di" required data-origin-selector>
                            <option value="">-- Chọn Sân bay đi --</option>
                            <?php foreach ($data['airports'] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" 
                                      data-airport-cap="<?= $e($option['phan_cap'] ?? '') ?>" 
                                      <?= (string) $addValue('id_san_bay_di') === (string) $option['id'] ? 'selected' : '' ?>>
                                <?= $e(($option['ma_iata'] ?? '') . ' – ' . $option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="form-text text-secondary">Điểm xuất phát của chuyến bay.</small>
                        </div>
                      </div>

                      <!-- 4. SÂN BAY ĐẾN -->
                      <div class="col-12 col-md-6">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_id_san_bay_den">
                            Sân bay đến <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <select class="form-select" id="them_id_san_bay_den" name="id_san_bay_den" required>
                            <option value="">-- Chọn Sân bay đến --</option>
                            <?php foreach ($data['airports'] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" 
                                      data-airport-cap="<?= $e($option['phan_cap'] ?? '') ?>" 
                                      <?= (string) $addValue('id_san_bay_den') === (string) $option['id'] ? 'selected' : '' ?>>
                                <?= $e(($option['ma_iata'] ?? '') . ' – ' . $option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="form-text text-secondary">Điểm đến theo lộ trình bay.</small>
                        </div>
                      </div>
                    </div>
                  </section>

                  <!-- PHẦN 2: LỊCH TRÌNH (CHIA ĐỀU 2 CỘT RỘNG RÃI) -->
                  <section class="flight-form-section mb-4" aria-labelledby="flight-create-schedule-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-create-schedule-title">Lịch trình</h3>
                        <p>Giờ hạ cánh phải sau giờ cất cánh; nếu bay qua nửa đêm, hãy chọn đúng ngày hôm sau.</p>
                      </div>
                    </div>
                    <div class="row g-3 flight-form-grid">
                      <!-- GIỜ CẤT CÁNH -->
                      <div class="col-12 col-md-6">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_gio_du_kien">
                            Giờ cất cánh <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <input type="datetime-local" class="form-control" id="them_gio_du_kien" name="gio_du_kien" lang="vi" step="60" required value="<?= $e($addValue('gio_du_kien')) ?>">
                          <small class="form-text text-secondary">Chọn ngày trên lịch và giờ theo định dạng 24 giờ (giờ, phút).</small>
                        </div>
                      </div>

                      <!-- GIỜ HẠ CÁNH -->
                      <div class="col-12 col-md-6">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_gio_thuc_te">
                            Giờ dự kiến hạ cánh <span class="text-danger" aria-hidden="true">*</span>
                            <span class="badge text-bg-warning ms-1" data-arrival-next-day title="Chuyến hạ cánh vào ngày hôm sau (qua nửa đêm)" hidden>+1 ngày</span>
                          </label>
                          <input type="datetime-local" class="form-control" id="them_gio_thuc_te" name="gio_thuc_te" lang="vi" step="60" required value="<?= $e($addValue('gio_thuc_te')) ?>">
                          <small class="form-text text-secondary">Chọn ngày trên lịch và giờ theo định dạng 24 giờ (giờ, phút).</small>
                          <small class="text-danger d-block fw-semibold" data-flight-time-feedback></small>
                          <small class="text-secondary d-block" data-arrival-next-day-note hidden></small>
                        </div>
                      </div>
                    </div>
                  </section>

                  <!-- PHẦN 3: DỊCH VỤ TẠI SÂN BAY ĐI (DROPDOWN CHỌN DANH SÁCH) -->
                  <section class="flight-form-section mb-4" aria-labelledby="flight-create-services-title">
                    <div class="flight-form-section-heading">
                      <span class="flight-form-section-icon"><i class="bi bi-building-check" aria-hidden="true"></i></span>
                      <div>
                        <h3 id="flight-create-services-title">Dịch vụ tại sân bay đi</h3>
                        <p>Chọn cửa, quầy và băng chuyền thuộc sân bay đi đã chọn.</p>
                      </div>
                    </div>
                    <div class="row g-3 flight-form-grid">
                      <!-- CỬA RA MÁY BAY -->
                      <div class="col-12 col-md-4">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_id_cua_ra_may_bay">
                            Cửa ra máy bay <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <select class="form-select" id="them_id_cua_ra_may_bay" name="id_cua_ra_may_bay" required data-origin-filter>
                            <option value="">-- Chưa phân công --</option>
                            <?php foreach ($data['gates'] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" 
                                      data-airport-id="<?= (int) $option['id_san_bay'] ?>" 
                                      data-terminal-type="<?= $e($option['terminal_type'] ?? '') ?>" 
                                      <?= (string) $addValue('id_cua_ra_may_bay') === (string) $option['id'] ? 'selected' : '' ?>>
                                <?= $e($option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="form-text text-secondary">Cửa khởi hành tương thích nhà ga.</small>
                        </div>
                      </div>

                      <!-- QUẦY CHECK-IN -->
                      <div class="col-12 col-md-4">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_id_quay_checkin">
                            Quầy check-in <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <select class="form-select" id="them_id_quay_checkin" name="id_quay_checkin" required data-origin-filter>
                            <option value="">-- Chưa phân công --</option>
                            <?php foreach ($data['checkins'] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" 
                                      data-airport-id="<?= (int) $option['id_san_bay'] ?>" 
                                      data-terminal-type="<?= $e($option['terminal_type'] ?? '') ?>" 
                                      <?= (string) $addValue('id_quay_checkin') === (string) $option['id'] ? 'selected' : '' ?>>
                                <?= $e($option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="form-text text-secondary">Mở trước giờ bay 2–3 tiếng, đóng trước 40 phút.</small>
                        </div>
                      </div>

                      <!-- BĂNG CHUYỀN HÀNH LÝ -->
                      <div class="col-12 col-md-4">
                        <div class="flight-form-card-item">
                          <label class="form-label fw-bold" for="them_id_bang_chuyen">
                            Băng chuyền hành lý <span class="text-danger" aria-hidden="true">*</span>
                          </label>
                          <select class="form-select" id="them_id_bang_chuyen" name="id_bang_chuyen" required data-origin-filter>
                            <option value="">-- Chưa phân công --</option>
                            <?php foreach ($data['belts'] as $option): ?>
                              <option value="<?= (int) $option['id'] ?>" 
                                      data-airport-id="<?= (int) $option['id_san_bay'] ?>" 
                                      data-terminal-type="<?= $e($option['terminal_type'] ?? '') ?>" 
                                      <?= (string) $addValue('id_bang_chuyen') === (string) $option['id'] ? 'selected' : '' ?>>
                                <?= $e($option['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <small class="form-text text-secondary">Băng chuyền tiếp nhận và xử lý hành lý.</small>
                        </div>
                      </div>
                    </div>
                  </section>

                  <div class="flight-create-defaults mb-4">
                    <div><i class="bi bi-people-fill me-1" aria-hidden="true"></i><span>Số hành khách được đặt theo sức chứa tàu bay.</span></div>
                    <div><i class="bi bi-box-seam-fill me-1" aria-hidden="true"></i><span>Tải trọng hành lý được đặt theo giới hạn tàu bay.</span></div>
                    <div><i class="bi bi-unlock-fill me-1" aria-hidden="true"></i><span>Chuyến mới sẽ ở trạng thái Đang mở.</span></div>
                  </div>
                  <div class="flight-form-actions d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                    <span class="small text-secondary"><span class="text-danger">*</span> Thông tin bắt buộc</span>
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                      <button class="btn btn-primary flight-create-submit" type="submit">
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Tạo chuyến bay
                      </button>
                      <?php if (!$popup): ?>
                        <a class="btn btn-outline-secondary px-3 py-2" href="<?= $url('chuyen_bay') ?>">Danh sách chuyến bay</a>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary px-3 py-2" onclick="if(window.parent && window.parent !== window){ window.parent.postMessage({ type: 'flight-popup-close' }, window.location.origin); }">
                          Đóng
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                </form>
              </div>
            </div>

            <script>
              // 1. Xử lý phụ thuộc Hãng hàng không -> Loại tàu bay
              document.querySelectorAll('[data-airline-selector]').forEach((airlineSelect) => {
                const form = airlineSelect.closest('form');
                const aircraftSelect = form.querySelector('[data-aircraft-selector]');
                if (!aircraftSelect) {
                  return;
                }
                const placeholderOption = aircraftSelect.querySelector('option[value=""]');
                const unlockedPlaceholder = aircraftSelect.dataset.aircraftPlaceholder || '-- Chọn Loại tàu bay --';
                const lockedPlaceholder = aircraftSelect.dataset.aircraftLockedPlaceholder || '-- Chọn hãng trước --';

                const filterAircraft = () => {
                  const airlineId = airlineSelect.value;
                  const locked = airlineId === '';

                  aircraftSelect.disabled = locked;
                  if (placeholderOption) {
                    placeholderOption.textContent = locked ? lockedPlaceholder : unlockedPlaceholder;
                  }

                  let currentSelectedStillValid = false;
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
                    if (matches && option.selected) {
                      currentSelectedStillValid = true;
                    }
                  });

                  if (!locked && aircraftSelect.value && !currentSelectedStillValid) {
                    aircraftSelect.value = '';
                  }
                };

                airlineSelect.addEventListener('change', filterAircraft);
                filterAircraft();
              });

              // 2. Validate giờ cất cánh và hạ cánh
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

              // 3. Lọc Cửa, Quầy, Băng chuyền theo Sân bay đi đã chọn
              document.querySelectorAll('[data-origin-selector]').forEach((originSelect) => {
                const form = originSelect.closest('form');
                const resourceSelects = form.querySelectorAll('[data-origin-filter]');

                const terminalAllowed = (terminalType, airportCap) => {
                  if (!terminalType) return true;
                  if (terminalType === 'Hon_Hop') return true;
                  if (airportCap === 'Quoc_Te') return terminalType !== 'Quoc_Noi';
                  if (airportCap === 'Quoc_Noi') return terminalType !== 'Quoc_Te';
                  return true;
                };

                const filterResources = () => {
                  const originOption = originSelect.selectedOptions[0];
                  const originCap = originSelect.value !== '' && originOption ? (originOption.dataset.airportCap || '') : '';
                  const hasOrigin = originSelect.value !== '';

                  resourceSelects.forEach((select) => {
                    select.disabled = !hasOrigin;
                    Array.from(select.options).forEach((option) => {
                      if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                      }
                      const matchesAirport = hasOrigin && option.dataset.airportId === originSelect.value;
                      const matchesZone = terminalAllowed(option.dataset.terminalType || '', originCap);
                      const matches = matchesAirport && matchesZone;
                      option.hidden = !matches;
                      option.disabled = !matches;
                      if (!matches && option.selected) {
                        select.value = '';
                      }
                    });
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
    <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css?v=<?= time() ?>" />
  </head>
  <body class="flight-popup-page">
<?= $content ?>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
