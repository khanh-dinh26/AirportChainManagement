<?php
/**
 * ============================================================
 * TRANG: QUẢN LÝ LỊCH BAY MÙA  —  views/pages/chuyen_bay/lich_bay_mua.php
 * CRUD bảng lich_bay_mua (lịch bay theo đợt của các hãng).
 *
 * Toàn bộ form thêm/sửa mở trong POPUP (iframe) ngay trên trang này,
 * không điều hướng sang trang mới:
 *   ?popup=1&new=1   : nội dung popup thêm lịch bay mùa
 *   ?popup=1&edit=ID : nội dung popup sửa lịch bay mùa
 *   ?popup=1&saved=1 : trang nhỏ báo trang cha tải lại danh sách
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

// Trang nhỏ sau khi lưu thành công: báo trang cha tải lại danh sách (flash còn trong session).
if (isset($_GET['popup'], $_GET['saved'])) {
    ?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <title>Đã lưu lịch bay mùa</title>
  </head>
  <body>
    <script>
      (function () {
        if (window.parent && window.parent !== window) {
          window.parent.postMessage({ type: 'schedule-popup-saved' }, window.location.origin);
        } else {
          window.location.replace(<?= json_encode(BASE_URL . '/views/pages/chuyen_bay/lich_bay_mua.php') ?>);
        }
      })();
    </script>
  </body>
</html>
    <?php
    exit();
}

$controller = new ChuyenBayController($conn);
$data = $controller->handleSchedule();

$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$self = BASE_URL . '/views/pages/chuyen_bay/lich_bay_mua.php';
$listUrl = BASE_URL . '/index.php?page=chuyen_bay';
// Nút "Tạo chuyến bay từ lịch" đã có sẵn ở trang danh sách chuyến bay (index.php)
// nên không lặp lại ở đây nữa.

// Chế độ popup: chỉ xuất nội dung popup (không sidebar/navbar, không danh sách).
$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

// Popup hủy lịch (?popup=1&cancel=ID) và popup nhật ký (?popup=1&log=ID).
$cancelId = max(0, (int) ($_GET['cancel'] ?? 0));
$logId = max(0, (int) ($_GET['log'] ?? 0));
$scheduleRows = [];
foreach ($data['schedules'] as $scheduleRow) {
    $scheduleRows[(int) $scheduleRow['id']] = $scheduleRow;
}
$cancelSchedule = $cancelId > 0 ? ($scheduleRows[$cancelId] ?? $controller->getSchedule($cancelId)) : null;
$logSchedule = $logId > 0 ? ($scheduleRows[$logId] ?? $controller->getSchedule($logId)) : null;
$scheduleLogs = $logId > 0 ? $controller->getScheduleLogs($logId) : [];
$cancelError = '';
if ($cancelId > 0 && $cancelSchedule === null) {
    $cancelError = 'Không tìm thấy lịch bay mùa cần hủy.';
}
if ($cancelSchedule !== null && (string) ($cancelSchedule['trang_thai'] ?? '') === 'Da_Dong') {
    $cancelError = 'Lịch bay mùa này đã đóng nên không thể hủy lại.';
}

$editing = $data['editing'];
$old = $data['oldInput'];
$formValue = static function ($field, $default = '') use ($old, $editing) {
    if (array_key_exists($field, $old)) {
        return $old[$field];
    }
    if ($editing !== null && array_key_exists($field, $editing)) {
        return $editing[$field];
    }

    return $default;
};

// Ngày bay đang tick: ưu tiên dữ liệu vừa POST, sau đó tới tần suất đã lưu.
$checkedDays = [];
if (isset($old['ngay_bay'])) {
    $checkedDays = array_map('intval', (array) $old['ngay_bay']);
} elseif ($editing !== null) {
    $checkedDays = ChuyenBayController::parseFrequency($editing['tan_suat_tuan'] ?? '');
}

$pageTitle = 'Lịch bay mùa';
$pageHeader = 'Quản lý lịch bay mùa';
$navActive = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Chuyến bay', 'url' => $listUrl],
    ['label' => 'Lịch bay mùa', 'url' => null],
];

ob_start();
?>
            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5"><?= $editing ? 'Sửa lịch bay mùa' : 'Thêm lịch bay mùa' ?></h2>
              </div>
              <div class="card-body">
                <?php if ($data['flash']): ?>
                  <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                    <?= $e($data['flash']['message']) ?>
                  </div>
                <?php endif; ?>
                <form method="post" action="<?= $self ?><?= $popup ? '?popup=1' : '' ?>" class="row g-3">
                  <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                  <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                  <?php if ($popup): ?>
                    <input type="hidden" name="popup" value="1">
                  <?php endif; ?>
                  <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                  <?php endif; ?>

                  <div class="col-md-4">
                    <label class="form-label" for="lich_so_hieu">Mã chuyến bay</label>
                    <input class="form-control" id="lich_so_hieu" name="so_hieu" maxlength="20" required autocomplete="off" placeholder="VN210" value="<?= $e($formValue('so_hieu')) ?>">
                    <small class="text-secondary">Chữ in hoa, số hoặc dấu gạch ngang.</small>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="lich_hang">Hãng hàng không</label>
                    <select class="form-select" id="lich_hang" name="id_hang_hang_khong" required>
                      <option value="">-- Chọn hãng hàng không --</option>
                      <?php foreach ($data['airlines'] as $airline): ?>
                        <option value="<?= (int) $airline['id'] ?>" <?= (string) $formValue('id_hang_hang_khong') === (string) $airline['id'] ? 'selected' : '' ?>>
                          <?= $e(($airline['ma_iata'] ?? '') . ' ' . $airline['label']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="lich_tu_ngay">Hiệu lực từ ngày</label>
                    <input type="date" class="form-control" id="lich_tu_ngay" name="tu_ngay" required value="<?= $e($formValue('tu_ngay')) ?>">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label" for="lich_san_bay_di">Sân bay đi</label>
                    <select class="form-select" id="lich_san_bay_di" name="id_san_bay_di" required>
                      <option value="">-- Chọn sân bay đi --</option>
                      <?php foreach ($data['airports'] as $airport): ?>
                        <option value="<?= (int) $airport['id'] ?>" <?= (string) $formValue('id_san_bay_di') === (string) $airport['id'] ? 'selected' : '' ?>>
                          <?= $e(($airport['ma_iata'] ?? '') . ' – ' . $airport['label']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="lich_san_bay_den">Sân bay đến</label>
                    <select class="form-select" id="lich_san_bay_den" name="id_san_bay_den" required>
                      <option value="">-- Chọn sân bay đến --</option>
                      <?php foreach ($data['airports'] as $airport): ?>
                        <option value="<?= (int) $airport['id'] ?>" <?= (string) $formValue('id_san_bay_den') === (string) $airport['id'] ? 'selected' : '' ?>>
                          <?= $e(($airport['ma_iata'] ?? '') . ' – ' . $airport['label']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="lich_den_ngay">Hiệu lực đến ngày</label>
                    <input type="date" class="form-control" id="lich_den_ngay" name="den_ngay" required value="<?= $e($formValue('den_ngay')) ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="lich_trang_thai">Trạng thái</label>
                    <?php $currentStatus = (string) $formValue('trang_thai', ChuyenBayController::SCHEDULE_STATUS_ACTIVE); ?>
                    <select class="form-select" id="lich_trang_thai" name="trang_thai" required>
                      <?php foreach ($data['scheduleStatuses'] as $statusValue => $statusLabel): ?>
                        <option value="<?= $e($statusValue) ?>" <?= $currentStatus === $statusValue ? 'selected' : '' ?>>
                          <?= $e($statusLabel) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <small class="text-secondary">Chỉ lịch “Đang mở” mới được dùng để tạo chuyến bay.</small>
                  </div>

                  <div class="col-12" data-schedule-reason-wrap hidden>
                    <label class="form-label" for="lich_ly_do">
                      Lý do <span class="text-danger" data-schedule-reason-required hidden>*</span>
                    </label>
                    <textarea class="form-control" id="lich_ly_do" name="ly_do" rows="2" maxlength="255" placeholder="Nhập lý do tạm dừng / đóng lịch bay mùa..."><?= $e($formValue('ly_do')) ?></textarea>
                    <small class="text-secondary">Bắt buộc khi chuyển sang “Tạm dừng” hoặc “Đã đóng”; lý do và người thực hiện được lưu vào nhật ký.</small>
                  </div>

                  <div class="col-12">
                    <span class="form-label d-block">Ngày bay trong tuần</span>
                    <?php foreach ($data['weekdays'] as $dayNumber => $dayLabel): ?>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="lich_ngay_<?= (int) $dayNumber ?>" name="ngay_bay[]" value="<?= (int) $dayNumber ?>" <?= in_array((int) $dayNumber, $checkedDays, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="lich_ngay_<?= (int) $dayNumber ?>"><?= $e($dayLabel) ?></label>
                      </div>
                    <?php endforeach; ?>
                    <small class="text-secondary d-block">Chọn đủ 7 ngày sẽ được lưu là “Hằng ngày”.</small>
                  </div>

                  <div class="col-12">
                    <button class="btn btn-primary" type="submit"><?= $editing ? 'Lưu thay đổi' : 'Thêm lịch bay mùa' ?></button>
                    <?php if ($popup): ?>
                      <button class="btn btn-outline-danger" type="button" data-schedule-popup-close>Đóng</button>
                    <?php else: ?>
                      <a class="btn btn-outline-danger" href="<?= $self ?>">Đóng</a>
                    <?php endif; ?>
                  </div>
                </form>
              </div>
            </div>
            <script>
              document.querySelectorAll('[data-schedule-popup-close]').forEach((button) => {
                button.addEventListener('click', () => {
                  if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'schedule-popup-close' }, window.location.origin);
                  }
                });
              });

              // Hiện ô lý do khi chọn Tạm dừng / Đã đóng (bắt buộc nhập).
              (function () {
                const status = document.getElementById('lich_trang_thai');
                const wrap = document.querySelector('[data-schedule-reason-wrap]');
                const reason = document.getElementById('lich_ly_do');
                const requiredMark = document.querySelector('[data-schedule-reason-required]');
                if (!status || !wrap || !reason) {
                  return;
                }
                const sync = function () {
                  const needReason = status.value === 'Tam_Dung' || status.value === 'Da_Dong';
                  wrap.hidden = !needReason;
                  reason.required = needReason;
                  if (requiredMark) {
                    requiredMark.hidden = !needReason;
                  }
                };
                status.addEventListener('change', sync);
                sync();
              })();
            </script>
<?php
$formContent = ob_get_clean();

// ===== Nội dung popup HỦY lịch bay mùa (?popup=1&cancel=ID) =====
ob_start();
?>
            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5">Hủy lịch bay mùa <?= $e($cancelSchedule['so_hieu'] ?? '') ?></h2>
              </div>
              <div class="card-body">
                <?php if ($data['flash']): ?>
                  <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                    <?= $e($data['flash']['message']) ?>
                  </div>
                <?php endif; ?>
                <?php if ($cancelError !== ''): ?>
                  <div class="alert alert-warning mb-3" role="alert"><?= $e($cancelError) ?></div>
                  <button class="btn btn-outline-danger" type="button" data-schedule-popup-close>Đóng</button>
                <?php else: ?>
                  <p>
                    Bạn xác nhận hủy lịch bay mùa
                    <strong><?= $e($cancelSchedule['so_hieu']) ?></strong>
                    <?php if (!empty($cancelSchedule['di_iata']) || !empty($cancelSchedule['den_iata'])): ?>
                      (<?= $e(($cancelSchedule['di_iata'] ?? '') . ' → ' . ($cancelSchedule['den_iata'] ?? '')) ?>)
                    <?php endif; ?>
                    ?
                  </p>
                  <p class="text-secondary">
                    Lịch sẽ chuyển sang trạng thái <strong>Đã đóng</strong> (không xóa dữ liệu);
                    các chuyến đã sinh trước đó vẫn được giữ lại.
                  </p>
                  <form method="post" action="<?= $self ?><?= $popup ? '?popup=1' : '' ?>">
                    <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <?php if ($popup): ?>
                      <input type="hidden" name="popup" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="id" value="<?= (int) $cancelSchedule['id'] ?>">
                    <div class="mb-3">
                      <label class="form-label" for="cancel_ly_do">Lý do hủy <span class="text-danger">*</span></label>
                      <textarea class="form-control" id="cancel_ly_do" name="ly_do" rows="3" maxlength="255" required placeholder="Nhập lý do hủy lịch bay mùa..."></textarea>
                      <small class="text-secondary">Lý do và người hủy được lưu vào nhật ký.</small>
                    </div>
                    <button class="btn btn-danger" type="submit">Xác nhận hủy</button>
                    <?php if ($popup): ?>
                      <button class="btn btn-outline-danger" type="button" data-schedule-popup-close>Đóng</button>
                    <?php else: ?>
                      <a class="btn btn-outline-danger" href="<?= $self ?>">Đóng</a>
                    <?php endif; ?>
                  </form>
                <?php endif; ?>
              </div>
            </div>
            <script>
              document.querySelectorAll('[data-schedule-popup-close]').forEach((button) => {
                button.addEventListener('click', () => {
                  if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'schedule-popup-close' }, window.location.origin);
                  }
                });
              });
            </script>
<?php
$cancelContent = ob_get_clean();

// ===== Nội dung popup NHẬT KÝ lịch bay mùa (?popup=1&log=ID) =====
$statusLabelOf = static function ($status) use ($data) {
    if ($status === null || $status === '') {
        return '—';
    }

    return $data['scheduleStatuses'][$status] ?? $status;
};
ob_start();
?>
            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5">
                  Nhật ký trạng thái — <?= $e($logSchedule['so_hieu'] ?? '') ?>
                  <?php if ($logSchedule): ?>
                    <span class="text-secondary fw-normal small">
                      (<?= $e(($logSchedule['di_iata'] ?? '') . ' → ' . ($logSchedule['den_iata'] ?? '')) ?>)
                    </span>
                  <?php endif; ?>
                </h2>
              </div>
              <div class="card-body">
                <?php if ($logSchedule === null): ?>
                  <p class="mb-3">Không tìm thấy lịch bay mùa.</p>
                <?php elseif (!$scheduleLogs): ?>
                  <p class="mb-3 text-secondary">Chưa có thay đổi trạng thái nào được ghi nhận.</p>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                      <thead>
                        <tr>
                          <th scope="col">Thời gian</th>
                          <th scope="col">Người thực hiện</th>
                          <th scope="col">Trạng thái</th>
                          <th scope="col">Lý do</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($scheduleLogs as $log): ?>
                          <tr>
                            <td class="text-nowrap"><?= $e(date('d/m/Y H:i', strtotime($log['thoi_gian']))) ?></td>
                            <td>
                              <?php if (empty($log['id_nguoi_dung'])): ?>
                                <span class="badge text-bg-secondary">Hệ thống</span>
                              <?php else: ?>
                                <?= $e($log['ho_ten'] ?: $log['ten_dang_nhap']) ?>
                              <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                              <?= $e($statusLabelOf($log['trang_thai_cu'] ?? null)) ?>
                              →
                              <strong><?= $e($statusLabelOf($log['trang_thai_moi'] ?? null)) ?></strong>
                            </td>
                            <td><?= $e($log['ly_do'] ?? '—') ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
                <div class="mt-3">
                  <button class="btn btn-outline-danger" type="button" data-schedule-popup-close>Đóng</button>
                </div>
              </div>
            </div>
            <script>
              document.querySelectorAll('[data-schedule-popup-close]').forEach((button) => {
                button.addEventListener('click', () => {
                  if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'schedule-popup-close' }, window.location.origin);
                  }
                });
              });
            </script>
<?php
$logContent = ob_get_clean();

// ===== Chế độ popup: chỉ xuất nội dung popup tương ứng =====
if ($popup) {
    $popupContent = $logId > 0 ? $logContent : ($cancelId > 0 ? $cancelContent : $formContent);
    $popupHeading = $logId > 0
        ? 'Nhật ký lịch bay mùa'
        : ($cancelId > 0 ? 'Hủy lịch bay mùa' : $pageTitle);
    ?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $e($popupHeading) ?></title>
    <link rel="stylesheet" href="<?= $e(ADMINLTE_URL) ?>/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/assets/css/style.css" />
    <style>
      body.schedule-popup-page { background: #f4f6f9; padding: 1rem; }
      body.schedule-popup-page .card { margin-bottom: 0; }
    </style>
  </head>
  <body class="schedule-popup-page">
<?= $popupContent ?>
  </body>
</html>
    <?php
    exit();
}

// ===== Trang danh sách =====
ob_start();
?>
            <div class="mb-3">
              <a class="btn btn-sm btn-outline-primary" href="<?= $listUrl ?>">
                <i class="bi bi-arrow-left me-1"></i> Quay lại trang chuyến bay
              </a>
            </div>

            <?php if ($data['flash']): ?>
              <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                <?= $e($data['flash']['message']) ?>
              </div>
            <?php endif; ?>

            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5">Danh sách lịch bay mùa</h2>
                <div class="card-tools">
                  <a class="btn btn-sm btn-primary" href="<?= $self ?>?new=1" data-schedule-popup-open data-schedule-popup-url="<?= $self ?>?popup=1&amp;new=1" data-schedule-popup-title="Thêm lịch bay mùa">Thêm lịch bay mùa</a>
                </div>
              </div>
              <div class="card-body">
                <?php if (!$data['schedules']): ?>
                  <p class="mb-0 text-secondary">Chưa có lịch bay mùa nào. Bấm “Thêm lịch bay mùa” để bắt đầu.</p>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                      <thead>
                        <tr>
                          <th scope="col">Mã CB</th>
                          <th scope="col">Hãng</th>
                          <th scope="col">Tuyến</th>
                          <th scope="col">Tần suất</th>
                          <th scope="col">Hiệu lực</th>
                          <th scope="col">Trạng thái</th>
                          <th scope="col" class="text-end">Đã sinh</th>
                          <th scope="col" class="text-end">Thao tác</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($data['schedules'] as $schedule): ?>
                          <tr>
                            <td class="fw-semibold"><?= $e($schedule['so_hieu']) ?></td>
                            <td><?= $e(trim($schedule['hang_iata'] . ' ' . $schedule['ten_hang'])) ?></td>
                            <td><?= $e($schedule['di_iata'] . ' → ' . $schedule['den_iata']) ?></td>
                            <td><?= $e($schedule['tan_suat_tuan']) ?></td>
                            <td>
                              <?= $e(date('d/m/Y', strtotime($schedule['tu_ngay']))) ?>
                              –
                              <?= $e(date('d/m/Y', strtotime($schedule['den_ngay']))) ?>
                            </td>
                            <td>
                              <?php
                              $statusKey = (string) ($schedule['trang_thai'] ?? 'Dang_Mo');
                              $statusLabel = $data['scheduleStatuses'][$statusKey] ?? $statusKey;
                              $statusClass = [
                                  'Dang_Mo' => 'text-bg-success',
                                  'Tam_Dung' => 'text-bg-warning',
                                  'Da_Dong' => 'text-bg-danger',
                              ][$statusKey] ?? 'text-bg-light';
                              ?>
                              <span class="badge <?= $e($statusClass) ?>"><?= $e($statusLabel) ?></span>
                            </td>
                            <td class="text-end"><?= (int) $schedule['so_chuyen'] ?></td>
                            <td class="text-end text-nowrap">
                              <a class="btn btn-sm btn-outline-primary" href="<?= $self ?>?edit=<?= (int) $schedule['id'] ?>" data-schedule-popup-open data-schedule-popup-url="<?= $self ?>?popup=1&amp;edit=<?= (int) $schedule['id'] ?>" data-schedule-popup-title="Sửa lịch bay mùa <?= $e($schedule['so_hieu']) ?>">Sửa</a>
                              <a class="btn btn-sm btn-outline-info" href="<?= $self ?>?log=<?= (int) $schedule['id'] ?>" data-schedule-popup-open data-schedule-popup-url="<?= $self ?>?popup=1&amp;log=<?= (int) $schedule['id'] ?>" data-schedule-popup-title="Nhật ký lịch bay mùa <?= $e($schedule['so_hieu']) ?>">
                                <i class="bi bi-clock-history me-1"></i>Lịch sử
                              </a>
                              <?php if ($statusKey !== 'Da_Dong'): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?= $self ?>?cancel=<?= (int) $schedule['id'] ?>" data-schedule-popup-open data-schedule-popup-url="<?= $self ?>?popup=1&amp;cancel=<?= (int) $schedule['id'] ?>" data-schedule-popup-title="Hủy lịch bay mùa <?= $e($schedule['so_hieu']) ?>">Hủy</a>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!--begin::Popup thêm / sửa lịch bay mùa-->
            <div class="schedule-popup" id="schedule-popup" hidden>
              <div class="schedule-popup-backdrop" data-schedule-popup-close></div>
              <div class="schedule-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="schedule-popup-title" tabindex="-1">
                <div class="schedule-popup-header">
                  <h2 class="schedule-popup-title" id="schedule-popup-title">Lịch bay mùa</h2>
                </div>
                <div class="schedule-popup-body">
                  <iframe class="schedule-popup-frame" title="Form lịch bay mùa" data-schedule-popup-frame></iframe>
                </div>
              </div>
            </div>
            <style>
              .schedule-popup { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: center; justify-content: center; padding: 1.25rem; }
              .schedule-popup[hidden] { display: none; }
              .schedule-popup-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .55); }
              .schedule-popup-dialog { position: relative; display: flex; flex-direction: column; width: min(1040px, 100%); max-height: 92vh; background: #fff; border-radius: .75rem; box-shadow: 0 24px 60px rgba(15, 23, 42, .35); overflow: hidden; }
              .schedule-popup-dialog:focus { outline: none; }
              .schedule-popup-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1.25rem; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
              .schedule-popup-title { margin: 0; font-size: 1.05rem; font-weight: 600; }
              .schedule-popup-body { flex: 1 1 auto; min-height: 0; background: #f4f6f9; }
              .schedule-popup-frame { display: block; width: 100%; height: min(78vh, 760px); border: 0; background: #f4f6f9; }
              body.schedule-popup-open { overflow: hidden; }
              @media (max-width: 575.98px) {
                .schedule-popup { padding: .5rem; }
                .schedule-popup-frame { height: 80vh; }
              }
            </style>
            <script>
              (function () {
                const popup = document.getElementById('schedule-popup');
                if (!popup) {
                  return;
                }
                const frame = popup.querySelector('[data-schedule-popup-frame]');
                const title = popup.querySelector('.schedule-popup-title');

                const closePopup = function () {
                  popup.hidden = true;
                  frame.removeAttribute('src');
                  title.textContent = 'Lịch bay mùa';
                  document.body.classList.remove('schedule-popup-open');
                };

                const openPopup = function (url, popupTitle) {
                  title.textContent = popupTitle || 'Lịch bay mùa';
                  frame.title = popupTitle || 'Form lịch bay mùa';
                  frame.src = url;
                  popup.hidden = false;
                  document.body.classList.add('schedule-popup-open');
                  const dialog = popup.querySelector('.schedule-popup-dialog');
                  if (dialog) {
                    dialog.focus();
                  }
                };

                document.querySelectorAll('[data-schedule-popup-open]').forEach(function (opener) {
                  opener.addEventListener('click', function (event) {
                    event.preventDefault();
                    openPopup(
                      opener.getAttribute('data-schedule-popup-url') || opener.getAttribute('href'),
                      opener.getAttribute('data-schedule-popup-title')
                    );
                  });
                });

                popup.querySelectorAll('[data-schedule-popup-close]').forEach(function (closer) {
                  closer.addEventListener('click', closePopup);
                });

                document.addEventListener('keydown', function (event) {
                  if (event.key === 'Escape' && !popup.hidden) {
                    closePopup();
                  }
                });

                window.addEventListener('message', function (event) {
                  if (event.origin !== window.location.origin || !event.data) {
                    return;
                  }
                  if (event.data.type === 'schedule-popup-close') {
                    closePopup();
                  } else if (event.data.type === 'schedule-popup-saved') {
                    window.location.reload();
                  }
                });

                // Liên kết cũ (?new=1 / ?edit=ID / ?cancel=ID / ?log=ID): mở popup ngay tại trang này.
                const baseUrl = <?= json_encode($self) ?>;
                const params = new URLSearchParams(window.location.search);
                const editId = parseInt(params.get('edit') || '0', 10);
                const cancelId = parseInt(params.get('cancel') || '0', 10);
                const logId = parseInt(params.get('log') || '0', 10);
                if (editId > 0) {
                  openPopup(baseUrl + '?popup=1&edit=' + editId, 'Sửa lịch bay mùa');
                } else if (cancelId > 0) {
                  openPopup(baseUrl + '?popup=1&cancel=' + cancelId, 'Hủy lịch bay mùa');
                } else if (logId > 0) {
                  openPopup(baseUrl + '?popup=1&log=' + logId, 'Nhật ký lịch bay mùa');
                } else if (params.has('new')) {
                  openPopup(baseUrl + '?popup=1&new=1', 'Thêm lịch bay mùa');
                }
                if (editId > 0 || cancelId > 0 || logId > 0 || params.has('new')) {
                  params.delete('edit');
                  params.delete('cancel');
                  params.delete('log');
                  params.delete('new');
                  const query = params.toString();
                  history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
                }
              })();
            </script>
            <!--end::Popup thêm / sửa lịch bay mùa-->
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
