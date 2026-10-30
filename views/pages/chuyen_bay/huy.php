<?php
/**
 * ============================================================
 * TRANG: HỦY CHUYẾN BAY  —  views/pages/chuyen_bay/huy.php
 * Toàn bộ phần xác nhận hủy (trước đây nằm trong modal ở index.php)
 * được đưa về đây. Nhận id qua query string: huy.php?id=123
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

// Trang này có thể mở trực tiếp (trang riêng) hoặc nhúng trong popup ở trang danh sách.
$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

// Hủy thành công từ popup: báo trang cha tải lại danh sách, không điều hướng trang mới.
if ($popup && isset($_GET['saved'])) {
    ?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <title>Đã hủy chuyến bay</title>
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
        'chuyen_bay_huy' => BASE_URL . '/views/pages/chuyen_bay/huy.php',
    ];
    $target = $paths[$page] ?? $paths['chuyen_bay'];

    return $params ? $target . '?' . http_build_query($params) : $target;
};
$flight = $data['canceling'];
$canCancel = $data['canCancel'];
$statusLabels = $data['statuses'];
$closeControl = $popup
    ? '<button type="button" class="btn btn-outline-danger" data-flight-popup-close>Đóng</button>'
    : '<a class="btn btn-outline-secondary" href="' . $e($url('chuyen_bay')) . '">Quay lại danh sách</a>';
$pageTitle = 'Hủy chuyến bay';
$pageHeader = 'Xác nhận hủy chuyến bay';
$navActive = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Chuyến bay', 'url' => $url('chuyen_bay')],
    ['label' => 'Hủy chuyến bay', 'url' => null],
];

ob_start();
?>
            <?php if ($data['flash']): ?>
              <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                <?= $e($data['flash']['message']) ?>
              </div>
            <?php endif; ?>

            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5">
                  <?= $flight ? 'Hủy chuyến ' . $e($flight['so_hieu']) : 'Hủy chuyến bay' ?>
                </h2>
              </div>
              <div class="card-body">
                <?php if (!$flight): ?>
                  <p class="mb-3">Không tìm thấy chuyến bay cần hủy.</p>
                  <?= $closeControl ?>
                <?php elseif (!$canCancel): ?>
                  <div class="alert alert-warning" role="alert">
                    Chuyến bay <?= $e($flight['so_hieu']) ?> hiện đang ở trạng thái
                    <strong><?= $e($statusLabels[$flight['trang_thai_tong']] ?? $flight['trang_thai_tong']) ?></strong>
                    nên không thể hủy. Chỉ được hủy chuyến bay đang mở hoặc trễ chuyến.
                  </div>
                  <?= $closeControl ?>
                <?php else: ?>
                  <p>Bạn xác nhận hủy chuyến <strong><?= $e($flight['so_hieu']) ?></strong>?</p>
                  <p class="text-secondary">Chuyến bay sẽ chuyển sang trạng thái Hủy. Dữ liệu vẫn được giữ lại! </p>
                  <form method="post" action="<?= $e($url('chuyen_bay_huy', ['id' => (int) $flight['id']] + ($popup ? ['popup' => 1] : []))) ?>">
                    <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <?php if ($popup): ?>
                      <input type="hidden" name="popup" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="return_page" value="chuyen_bay_cancel_modal">
                    <input type="hidden" name="id" value="<?= (int) $flight['id'] ?>">
                    <div class="mb-3">
                      <label class="form-label" for="ghi_chu">Lý do hủy chuyến</label>
                      <textarea class="form-control" id="ghi_chu" name="ghi_chu" rows="3" maxlength="255" required></textarea>
                    </div>
                    <button class="btn btn-danger" type="submit">Xác nhận hủy chuyến</button>
                    <?php if ($popup): ?>
                      <button class="btn btn-outline-danger" type="button" data-flight-popup-close>Hủy</button>
                    <?php else: ?>
                      <a class="btn btn-outline-danger" href="<?= $url('chuyen_bay') ?>">Hủy</a>
                    <?php endif; ?>
                  </form>
                <?php endif; ?>
              </div>
            </div>
            <script>
              // Nút "Đóng"/"Hủy" khi form được mở trong popup.
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
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
