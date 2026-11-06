<?php
/**
 * ============================================================
 * TRANG: HỦY / XÓA CHUYẾN BAY  —  views/pages/chuyen_bay/huy.php
 * Nhận id qua query string: huy.php?id=123
 * (vẫn chấp nhận huy.php?edit=123 để tương thích)
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

if (!isset($_GET['cancel'])) {
    if (isset($_GET['id'])) {
        $_GET['cancel'] = $_GET['id'];
    } elseif (isset($_GET['edit'])) {
        $_GET['cancel'] = $_GET['edit'];
    }
}
$deleteMode = isset($_GET['delete']) && (string) $_GET['delete'] !== '0';

// Trang này có thể mở trực tiếp (trang riêng) hoặc nhúng trong popup ở trang danh sách.
$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

// Thao tác thành công từ popup: báo trang cha tải lại danh sách.
if ($popup && isset($_GET['saved'])) {
    ?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <title><?= $deleteMode ? 'Đã xóa chuyến bay' : 'Đã hủy chuyến bay' ?></title>
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
        'chuyen_bay_huy' => BASE_URL . '/views/pages/chuyen_bay/huy.php',
    ];
    $target = $paths[$page] ?? $paths['chuyen_bay'];

    return $params ? $target . '?' . http_build_query($params) : $target;
};
$flight = $data['canceling'];
$flightSummary = $data['cancelingSummary'] ?? [];
$canCancel = $data['canCancel'];
$formatFlightTime = static function ($value) {
    $timestamp = $value ? strtotime((string) $value) : false;
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : 'Chưa cập nhật';
};
$canDelete = $flight !== null
    && strtotime((string) $flight['gio_du_kien']) > strtotime((string) $data['serverNow'])
    && in_array($flight['trang_thai_tong'], ['Dang_Mo', 'Delay', 'Huy'], true);
$statusLabels = $data['statuses'];
$closeControl = $popup
    ? ''
    : '<a class="btn btn-outline-secondary" href="' . $e($url('chuyen_bay')) . '"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại danh sách</a>';
$pageTitle = $deleteMode ? 'Xóa chuyến bay' : 'Hủy chuyến bay';
$pageHeader = $deleteMode ? 'Xác nhận xóa chuyến bay' : 'Xác nhận hủy chuyến bay';
$navActive = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Chuyến bay', 'url' => $url('chuyen_bay')],
    ['label' => $pageTitle, 'url' => null],
];

ob_start();
?>
            <?php if (!$popup): ?>
            <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
            <?php endif; ?>
            <?php if ($data['flash']): ?>
              <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                <?= $e($data['flash']['message']) ?>
              </div>
            <?php endif; ?>

                        <div class="card">
              <?php if (!$popup): ?>
              <div class="card-header">
                <h2 class="card-title h5">
                  <?= $flight ? ($deleteMode ? 'Xóa chuyến ' : 'Hủy chuyến ') . $e($flight['so_hieu']) : $pageTitle ?>
                </h2>
              </div>
              <?php endif; ?>
              <div class="card-body">
                <?php if ($flight): ?>
                  <?php $currentStatus = (string) ($flight['trang_thai_tong'] ?? ''); ?>
                  <?php if ($deleteMode && !$canDelete): ?>
                    <div class="flight-readonly-banner flight-action-locked-banner mb-3" role="status">
                      <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                        <div>
                          <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong>Không thể xóa chuyến bay này</strong>
                            <span class="badge flight-status-badge" data-flight-status="<?= $e($currentStatus) ?>"><?= $e($statusLabels[$currentStatus] ?? $currentStatus) ?></span>
                          </div>
                          <p class="mb-0 mt-1 text-secondary">Chỉ được xóa chuyến chưa tới giờ cất cánh và chưa cất cánh/hạ cánh.</p>
                        </div>
                      </div>
                    </div>
                  <?php elseif (!$deleteMode && !$canCancel): ?>
                    <div class="flight-readonly-banner flight-action-locked-banner mb-3" role="status">
                      <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
                        <div>
                          <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong>Chuyến bay chưa thể hủy</strong>
                            <span class="badge flight-status-badge" data-flight-status="<?= $e($currentStatus) ?>"><?= $e($statusLabels[$currentStatus] ?? $currentStatus) ?></span>
                          </div>
                          <p class="mb-0 mt-1 text-secondary">
                            Chuyến <strong><?= $e($flight['so_hieu']) ?></strong> đang ở trạng thái
                            <strong><?= $e($statusLabels[$currentStatus] ?? $currentStatus) ?></strong>.
                            Chỉ chuyến đang mở hoặc trễ chuyến mới được hủy.
                          </p>
                          <?php if ($currentStatus === 'Ha_Canh'): ?>
                            <small class="d-block mt-2 text-secondary">Máy bay đã hoàn tất hành trình; thao tác hủy bị khóa để bảo toàn lịch sử khai thác.</small>
                          <?php elseif ($currentStatus === 'Cat_Canh'): ?>
                            <small class="d-block mt-2 text-secondary">Máy bay đã cất cánh; vui lòng không thay đổi trạng thái chuyến tại đây.</small>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                  <section class="flight-summary-card p-3 mb-3" aria-label="Thông tin chuyến bay cần xử lý">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                      <div>
                        <span class="small text-uppercase text-secondary fw-semibold"><?= $deleteMode ? 'Xác nhận xóa chuyến' : 'Xác nhận hủy chuyến' ?></span>
                        <?php if (!$popup): ?><h3 class="h5 mb-0 mt-1"><?= $e($flight['so_hieu']) ?></h3><?php endif; ?>
                      </div>
                      <span class="badge flight-status-badge" data-flight-status="<?= $e($currentStatus) ?>">
                        <?= $e($statusLabels[$currentStatus] ?? $currentStatus) ?>
                      </span>
                    </div>
                    <div class="flight-summary-route mb-3">
                      <div class="flight-summary-airport">
                        <span class="flight-summary-airport-code"><?= $e($flightSummary['ma_di'] ?? '—') ?></span>
                        <span class="flight-summary-airport-name"><?= $e($flightSummary['ten_di'] ?? 'Chưa có sân bay đi') ?></span>
                      </div>
                      <span class="flight-summary-route-icon" aria-hidden="true"><i class="bi bi-airplane"></i></span>
                      <div class="flight-summary-airport">
                        <span class="flight-summary-airport-code"><?= $e($flightSummary['ma_den'] ?? '—') ?></span>
                        <span class="flight-summary-airport-name"><?= $e($flightSummary['ten_den'] ?? 'Chưa có sân bay đến') ?></span>
                      </div>
                    </div>
                    <div class="flight-summary-facts">
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Hãng hàng không</span>
                        <span class="flight-summary-fact-value"><?= $e(trim(($flightSummary['hang_iata'] ?? '') . ' ' . ($flightSummary['ten_hang'] ?? 'Chưa cập nhật'))) ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Loại tàu bay</span>
                        <span class="flight-summary-fact-value"><?= $e($flightSummary['ma_loai'] ?? 'Chưa cập nhật') ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Giờ cất cánh</span>
                        <span class="flight-summary-fact-value"><?= $e($formatFlightTime($flight['gio_du_kien'] ?? null)) ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Giờ hạ cánh dự kiến</span>
                        <span class="flight-summary-fact-value"><?= $e($formatFlightTime($flight['gio_thuc_te'] ?? null)) ?></span>
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
                        <span class="flight-summary-fact-value"><?= $e($flightSummary['cua_ra'] ?? 'Chưa phân công') ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Quầy check-in</span>
                        <span class="flight-summary-fact-value"><?= $e($flightSummary['quay_checkin'] ?? 'Chưa phân công') ?></span>
                      </div>
                      <div class="flight-summary-fact">
                        <span class="flight-summary-fact-label">Băng chuyền</span>
                        <span class="flight-summary-fact-value"><?= $e($flightSummary['bang_chuyen'] ?? 'Chưa phân công') ?></span>
                      </div>
                    </div>
                  </section>
                <?php endif; ?>
                <?php if (!$flight): ?>
                  <p class="mb-3">Không tìm thấy chuyến bay cần xử lý.</p>
                  <?php if (!$popup): ?><div class="d-flex justify-content-end"><?= $closeControl ?></div><?php endif; ?>
                <?php elseif ($flight && $deleteMode && !$canDelete): ?>
                  <?php if (!$popup): ?><div class="d-flex justify-content-end"><?= $closeControl ?></div><?php endif; ?>
                <?php elseif ($flight && !$deleteMode && !$canCancel): ?>
                  <?php if (!$popup): ?><div class="d-flex justify-content-end"><?= $closeControl ?></div><?php endif; ?>
                <?php elseif ($flight && $deleteMode): ?>
                  <p>Bạn xác nhận xóa vĩnh viễn chuyến <strong><?= $e($flight['so_hieu']) ?></strong>?</p>
                  <div class="alert alert-danger" role="alert">
                    Thao tác không thể hoàn tác. Lịch sử, nhật ký và hóa đơn liên quan cũng sẽ bị xóa.
                  </div>
                  <form method="post" action="<?= $e($url('chuyen_bay_huy', ['id' => (int) $flight['id'], 'delete' => 1] + ($popup ? ['popup' => 1] : []))) ?>">
                    <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                    <input type="hidden" name="action" value="delete">
                    <?php if ($popup): ?>
                      <input type="hidden" name="popup" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="id" value="<?= (int) $flight['id'] ?>">
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                      <button class="btn btn-danger" type="submit"><i class="bi bi-trash3 me-1" aria-hidden="true"></i>Xóa vĩnh viễn</button>
                      <?php if (!$popup): ?><?= $closeControl ?><?php endif; ?>
                    </div>
                  </form>
                <?php elseif ($flight): ?>
                  <div class="flight-cancel-banner mb-3">
                    <div class="d-flex align-items-start gap-3">
                      <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                      <div>
                        <strong>Hủy chuyến sẽ thông báo sự cố khai thác</strong>
                        <p class="mb-0 mt-1 text-secondary">Chuyến sẽ chuyển sang trạng thái Hủy. Dữ liệu và nhật ký vẫn được giữ lại để tra cứu.</p>
                      </div>
                    </div>
                  </div>
                  <form method="post" action="<?= $e($url('chuyen_bay_huy', ['id' => (int) $flight['id']] + ($popup ? ['popup' => 1] : []))) ?>">
                    <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <?php if ($popup): ?>
                      <input type="hidden" name="popup" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="return_page" value="chuyen_bay_cancel_modal">
                    <input type="hidden" name="id" value="<?= (int) $flight['id'] ?>">
                    <div class="flight-cancel-reason p-3 mb-3">
                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <label class="form-label mb-2" for="ghi_chu">Lý do hủy chuyến <span class="text-danger" aria-hidden="true">*</span></label>
                        <span class="small text-secondary" data-reason-count>0/255</span>
                      </div>
                      <textarea class="form-control" id="ghi_chu" name="ghi_chu" rows="3" maxlength="255" required
                                placeholder="Mô tả ngắn gọn lý do hủy để lưu vào nhật ký chuyến bay..."></textarea>
                      <small class="form-text text-secondary">Ghi rõ nguyên nhân để nhân viên điều hành và khách hàng dễ nắm thông tin.</small>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-2">
                      <button class="btn btn-danger" type="submit"><i class="bi bi-x-octagon me-1" aria-hidden="true"></i>Hủy chuyến <?= $e($flight['so_hieu']) ?></button>
                      <?php if (!$popup): ?>
                        <a class="btn btn-outline-danger" href="<?= $url('chuyen_bay') ?>">Hủy</a>
                      <?php endif; ?>
                    </div>
                  </form>
                <?php endif; ?>
              </div>
            </div>
            <script>
              const cancelReason = document.getElementById('ghi_chu');
              const reasonCount = document.querySelector('[data-reason-count]');
              if (cancelReason && reasonCount) {
                const updateReasonCount = () => {
                  reasonCount.textContent = cancelReason.value.length + '/255';
                };
                cancelReason.addEventListener('input', updateReasonCount);
                updateReasonCount();
              }

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
