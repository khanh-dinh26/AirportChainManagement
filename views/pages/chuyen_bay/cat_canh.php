<?php
/**
 * ============================================================
 * POPUP: XÁC NHẬN ĐIỀU KIỆN CẤT CÁNH — views/pages/chuyen_bay/cat_canh.php
 * Mở từ 2 mục trên trang danh sách chuyến bay:
 *   - "Các chuyến đã đến giờ bay" (Đang mở, đã tới giờ cất cánh).
 *   - "Các chuyến bay trễ chuyến đã đến giờ bay" (Trễ chuyến, đã tới giờ cất cánh).
 * Nội dung:
 *   - Thông tin đầy đủ của chuyến bay.
 *   - Lý do trễ trước đó (đọc từ nhật ký chuyến bay, chỉ chuyến Trễ chuyến mới có).
 *   - Danh sách chuyến trùng lịch + giờ sân bay trống gợi ý.
 *   - Đủ điều kiện / đủ yêu cầu an toàn -> Cất cánh (server kiểm tra trùng lịch).
 *   - Chưa đủ -> trễ tiếp: nhập lý do + số phút hoặc giờ cất cánh mới.
 * Không thêm bảng mới: lý do trễ được ghi vào nhật ký chuyến bay.
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

// Sau khi cất cánh / trễ tiếp: báo trang cha tải lại danh sách rồi đóng popup.
if ($popup && isset($_GET['done'])) {
    ?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8" />
    <title>Đã cập nhật chuyến bay</title>
  </head>
  <body>
    <script>
      (function () {
        if (window.parent && window.parent !== window) {
          window.parent.postMessage({ type: 'flight-departure-done' }, window.location.origin);
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

$data = (new ChuyenBayController($conn))->handleDepartureCheck();
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
$selfUrl = BASE_URL . '/views/pages/chuyen_bay/cat_canh.php?popup=1&id=' . (int) ($flight['id'] ?? 0);
$delayHistory = $data['delayHistory'] ?? [];
$conflicts = $data['conflicts'] ?? [];
$suggestedSlot = $data['suggestedSlot'] ?? null;
$suggestedInput = $suggestedSlot ? substr(str_replace(' ', 'T', (string) $suggestedSlot), 0, 16) : '';
$suggestedText = $suggestedSlot
    ? (DateTime::createFromFormat('Y-m-d H:i:s', (string) $suggestedSlot)?->format('H:i d/m/Y') ?? $suggestedSlot)
    : null;
$isDelayed = !empty($data['isDelayed']);
$actionTitle = $isDelayed ? 'Điều kiện an toàn để cất cánh' : 'Điều kiện cất cánh';
$confirmLabel = $isDelayed ? 'Đã đủ điều kiện an toàn' : 'Đã đủ điều kiện bay';
$delayToggleLabel = $isDelayed
    ? 'Chưa đủ yêu cầu an toàn — Delay tiếp'
    : 'Chưa đủ điều kiện — Trễ chuyến';
?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Xác nhận điều kiện cất cánh</title>
    <link rel="stylesheet" href="<?= $escape(ADMINLTE_URL) ?>/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?= $escape(BASE_URL) ?>/assets/css/style.css" />
    <style>
      body.departure-page { background: #f4f6f9; padding: 1rem; }
      body.departure-page .card { margin-bottom: .75rem; }
      .departure-info dt { color: #6c757d; font-weight: 500; }
      .departure-info dd { margin-bottom: .5rem; font-weight: 600; }
      .delay-note { white-space: pre-line; }
    </style>
  </head>
  <body class="departure-page">
    <?php if ($flight === null): ?>
      <div class="card">
        <div class="card-body">
          <p class="mb-3">Không tìm thấy chuyến bay cần xác nhận.</p>
          <button type="button" class="btn btn-outline-danger" data-flight-popup-close>Đóng</button>
        </div>
      </div>
      <script>
        document.querySelectorAll('[data-flight-popup-close]').forEach(function (button) {
          button.addEventListener('click', function () {
            if (window.parent && window.parent !== window) {
              window.parent.postMessage({ type: 'flight-popup-close' }, window.location.origin);
            }
          });
        });
      </script>
      <?php exit(); ?>
    <?php endif; ?>

    <?php if (!empty($data['flash'])): ?>
      <div class="alert alert-<?= $escape($data['flash']['type']) ?> py-2" role="alert" data-flash>
        <?= $escape($data['flash']['message']) ?>
      </div>
    <?php endif; ?>

    <?php if ($flight['trang_thai_tong'] === 'Huy'): ?>
      <div class="alert alert-danger" role="alert">
        <strong>Chuyến bay đã bị hủy.</strong>
        Chuyến này không còn đủ điều kiện cất cánh.
      </div>
    <?php elseif (!$data['isPending']): ?>
      <div class="alert alert-info" role="alert">
        Chuyến bay không ở trạng thái chờ cất cánh (trạng thái hiện tại:
        <strong><?= $escape($statusLabels[$flight['trang_thai_tong']] ?? $flight['trang_thai_tong']) ?></strong>).
      </div>
    <?php endif; ?>

    <?php if (!empty($data['isEarly'])): ?>
      <div class="alert alert-info py-2" role="alert">
        <i class="bi bi-info-circle me-1"></i>
        Chuyến đang trễ và giờ cất cánh dự kiến còn ở tương lai. Nếu lý do trễ đã được khắc phục xong,
        bấm <strong>“<?= $escape($confirmLabel) ?>”</strong> để cho cất cánh sớm — giờ cất cánh sẽ ghi nhận
        theo thời điểm hiện tại.
      </div>
    <?php endif; ?>

    <!--begin::Thông tin chuyến bay-->
    <div class="card border-primary-subtle">
      <div class="card-header">
        <h2 class="card-title h5 mb-0">
          <i class="bi bi-airplane-engines me-1"></i>
          Chuyến <?= $escape($flight['so_hieu']) ?> — <?= $escape($flight['ten_hang'] ?? '') ?>
          <span class="badge text-bg-<?= $escape($statusClasses[$flight['trang_thai_tong']] ?? 'secondary') ?> ms-1">
            <?= $escape($statusLabels[$flight['trang_thai_tong']] ?? $flight['trang_thai_tong']) ?>
          </span>
        </h2>
      </div>
      <div class="card-body">
        <dl class="row departure-info mb-0">
          <dt class="col-sm-3">Số hiệu chuyến</dt>
          <dd class="col-sm-3"><?= $escape($flight['so_hieu']) ?></dd>
          <dt class="col-sm-3">Hãng hàng không</dt>
          <dd class="col-sm-3"><?= $escape($flight['ten_hang'] ?? '—') ?></dd>

          <dt class="col-sm-3">Loại tàu bay</dt>
          <dd class="col-sm-3"><?= $escape($flight['ma_loai'] ?? '—') ?></dd>
          <dt class="col-sm-3">Số hành khách</dt>
          <dd class="col-sm-3">
            <?= number_format((int) $flight['luong_hanh_khach'], 0, ',', '.') ?> /
            <?= number_format((int) ($flight['suc_chua_hanh_khach'] ?? 0), 0, ',', '.') ?> khách
          </dd>

          <dt class="col-sm-3">Bay từ</dt>
          <dd class="col-sm-3"><?= $escape($flight['ma_di']) ?> — <?= $escape($flight['ten_di']) ?></dd>
          <dt class="col-sm-3">Bay đến</dt>
          <dd class="col-sm-3"><?= $escape($flight['ma_den']) ?> — <?= $escape($flight['ten_den']) ?></dd>

          <dt class="col-sm-3">Giờ cất cánh</dt>
          <dd class="col-sm-3"><?= $dateTime($flight['gio_du_kien']) ?></dd>
          <dt class="col-sm-3">Giờ dự kiến hạ cánh</dt>
          <dd class="col-sm-3">
            <?php
              // Giờ hạ cánh dự kiến = gio_thuc_te (đã gồm delay) + số phút đã quá hạn chờ xác nhận.
              $catEta = $flight['gio_thuc_te'];
              if ($isDelayed && !empty($flight['gio_thuc_te'])) {
                  $catOverdue = max(
                      0,
                      (int) floor((strtotime((string) $data['serverNow']) - strtotime((string) $flight['gio_du_kien'])) / 60)
                  );
                  $catEta = date('Y-m-d H:i:s', strtotime((string) $flight['gio_thuc_te']) + ($catOverdue * 60));
              }
            ?>
            <?= $dateTime($catEta) ?>
          </dd>

          <dt class="col-sm-3">Cửa ra máy bay</dt>
          <dd class="col-sm-3"><?= $escape($flight['cua_ra']) ?></dd>
          <dt class="col-sm-3">Quầy check-in</dt>
          <dd class="col-sm-3"><?= $escape($flight['quay_checkin']) ?></dd>

          <dt class="col-sm-3">Băng chuyền hành lý</dt>
          <dd class="col-sm-3"><?= $escape($flight['bang_chuyen']) ?></dd>
          <dt class="col-sm-3">Hành lý / hàng hóa</dt>
          <dd class="col-sm-3">
            <?= number_format(round((float) $flight['luong_hang_hoa_tan'] * 1000), 0, ',', '.') ?> kg
          </dd>

          <dt class="col-sm-3">Giờ hiện tại</dt>
          <dd class="col-sm-3 mb-0"><?= $dateTime($data['serverNow']) ?></dd>
        </dl>
      </div>
    </div>
    <!--end::Thông tin chuyến bay-->

    <!--begin::Lý do trễ trước đó-->
    <div class="card <?= $isDelayed ? 'border-warning' : 'border-secondary-subtle' ?>">
      <div class="card-header d-flex align-items-center gap-2">
        <h3 class="card-title h6 mb-0">
          <i class="bi bi-clock-history me-1"></i>
          Lý do trễ trước đó
        </h3>
        <?php if ($delayHistory): ?>
          <span class="badge text-bg-warning"><?= count($delayHistory) ?> lần ghi nhận</span>
        <?php endif; ?>
      </div>
      <div class="card-body py-2">
        <?php if ($delayHistory): ?>
          <details>
            <summary class="text-secondary" style="cursor:pointer;">Xem chi tiết lý do trễ (<?= count($delayHistory) ?> lần)</summary>
            <div class="table-responsive mt-2">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Thời gian</th>
                    <th>Người ghi nhận</th>
                    <th>Lý do trễ</th>
                    <th>Số phút</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (array_reverse($delayHistory) as $entry): ?>
                    <tr>
                      <td class="text-nowrap"><?= $dateTime($entry['thoi_gian']) ?></td>
                      <td><?= $escape($entry['nguoi_thuc_hien']) ?></td>
                      <td class="delay-note"><?= $escape($entry['ly_do'] ?? $entry['ghi_chu']) ?></td>
                      <td class="text-nowrap">
                        <?= $entry['so_phut'] !== null ? $escape($entry['so_phut']) . ' phút' : '—' ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </details>
        <?php elseif ($isDelayed): ?>
          <p class="small text-secondary mb-0">Chưa có ghi nhận lý do trễ trong nhật ký chuyến bay.</p>
        <?php else: ?>
          <p class="small text-secondary mb-0">Chuyến bay đang mở, chưa từng bị trễ.</p>
        <?php endif; ?>
      </div>
    </div>
    <!--end::Lý do trễ trước đó-->

    <!--begin::Chuyến trùng lịch + giờ sân bay trống-->
    <?php if ($conflicts): ?>
      <div class="card border-danger-subtle" data-conflict-card>
        <div class="card-header d-flex align-items-center gap-2">
          <h3 class="card-title h6 mb-0 text-danger">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Trùng lịch cất cánh (<?= count($conflicts) ?> chuyến)
          </h3>
          <span class="small text-secondary ms-auto">Cùng sân bay đi, cùng ngày, lệch dưới 10 phút.</span>
        </div>
        <div class="card-body py-2">
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-2">
              <thead>
                <tr>
                  <th>Mã chuyến</th>
                  <th>Sân bay đi</th>
                  <th>Giờ cất cánh</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($conflicts as $conflict): ?>
                  <tr>
                    <td class="fw-semibold"><?= $escape($conflict['so_hieu']) ?></td>
                    <td><?= $escape($conflict['ma_iata']) ?></td>
                    <td class="text-nowrap"><?= $dateTime($conflict['gio_du_kien']) ?></td>
                    <td><?= $escape($statusLabels[$conflict['trang_thai_tong']] ?? $conflict['trang_thai_tong']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($suggestedText !== null): ?>
            <div class="alert alert-warning py-2 d-flex flex-wrap align-items-center gap-2 mb-0">
              <i class="bi bi-calendar-check"></i>
              <span>
                Sân bay đang trống gần nhất lúc <strong><?= $escape($suggestedText) ?></strong>.
                Hãy trễ tới giờ này rồi mới cho cất cánh.
              </span>
              <button type="button" class="btn btn-sm btn-warning ms-auto"
                      data-use-suggested-slot
                      data-suggested-slot="<?= $escape($suggestedInput) ?>">
                <i class="bi bi-arrow-down-circle me-1"></i> Dùng giờ gợi ý
              </button>
            </div>
          <?php else: ?>
            <div class="alert alert-danger py-2 mb-0">
              Không tìm được giờ trống trong 24 giờ tới — vui lòng kiểm tra lại lịch bay.
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <!--end::Chuyến trùng lịch + giờ sân bay trống-->

    <!--begin::Xác nhận điều kiện cất cánh-->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title h6 mb-0">
          <i class="bi bi-clipboard2-check me-1"></i>
          <?= $escape($actionTitle) ?>
        </h3>
      </div>
      <div class="card-body">
        <?php if ($data['isPending'] && $flight['trang_thai_tong'] !== 'Huy'): ?>
          <form id="departure-form" method="post" action="<?= $escape($selfUrl) ?>" novalidate>
            <input type="hidden" name="csrf" value="<?= $escape($data['csrf']) ?>" />
            <input type="hidden" name="id" value="<?= (int) $flight['id'] ?>" />
            <input type="hidden" name="action" value="" data-departure-action />

            <p class="small text-secondary mb-3">
              <?php if ($isDelayed): ?>
                Kiểm tra lại lý do trễ ở trên. Nếu chuyến đã đáp ứng đủ yêu cầu an toàn thì cho cất cánh;
                nếu chưa đủ thì delay tiếp kèm lý do.
              <?php else: ?>
              <?php endif; ?>
            </p>

            <div class="alert alert-danger py-2 d-none" data-confirm-warning></div>

            <div class="d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-success" data-departure-confirm data-action="departure_confirm">
                <i class="bi bi-check2-circle me-1"></i> <?= $escape($confirmLabel) ?>
              </button>
              <button type="button" class="btn btn-warning" data-delay-toggle>
                <i class="bi bi-clock-history me-1"></i> <?= $escape($delayToggleLabel) ?>
              </button>
              <button type="button" class="btn btn-outline-danger" data-flight-popup-close>Đóng</button>
            </div>

            <div class="border rounded p-3 mt-3 d-none" data-delay-panel>
              <div class="row g-2">
                <div class="col-12">
                  <label class="form-label" for="delay_reason">Lý do trễ / chưa đủ điều kiện bay</label>
                  <textarea class="form-control" id="delay_reason" name="ly_do" rows="2" maxlength="255"
                            placeholder="Ví dụ: Hỏng hệ thống thủy lực, đang chờ kỹ thuật" data-delay-reason></textarea>
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="delay_minutes">Số phút trễ tiếp</label>
                  <input type="number" class="form-control" id="delay_minutes" name="thoi_gian_tre_phut"
                         min="1" max="1440" step="1" placeholder="Ví dụ: 45" data-delay-minutes />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="delay_new_time">Hoặc giờ cất cánh mới</label>
                  <input type="datetime-local" class="form-control" id="delay_new_time" name="gio_cat_canh_moi"
                         data-delay-new-time />
                  <div class="form-text">Nhập một trong hai: số phút hoặc giờ cất cánh mới.</div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                  <button type="submit" class="btn btn-warning" data-action="departure_delay">
                    Xác nhận trễ
                  </button>
                  <button type="button" class="btn btn-outline-secondary" data-delay-cancel>Hủy</button>
                </div>
              </div>
            </div>
          </form>
        <?php else: ?>
          <button type="button" class="btn btn-outline-danger" data-flight-popup-close>Đóng</button>
        <?php endif; ?>
      </div>
    </div>
    <!--end::Xác nhận điều kiện cất cánh-->

    <script>
      (function () {
        const form = document.getElementById('departure-form');
        const notifyParent = function (type) {
          if (window.parent && window.parent !== window) {
            window.parent.postMessage({ type: type }, window.location.origin);
          }
        };
        document.querySelectorAll('[data-flight-popup-close]').forEach(function (button) {
          button.addEventListener('click', function () {
            notifyParent('flight-popup-close');
          });
        });
        if (!form) {
          return;
        }

        const delayPanel = form.querySelector('[data-delay-panel]');
        const delayReason = form.querySelector('[data-delay-reason]');
        const delayMinutes = form.querySelector('[data-delay-minutes]');
        const delayNewTime = form.querySelector('[data-delay-new-time]');
        const confirmWarning = form.querySelector('[data-confirm-warning]');
        const hasConflicts = <?= $conflicts ? 'true' : 'false' ?>;
        const suggestedSlot = <?= json_encode($suggestedInput) ?>;
        const suggestedText = <?= json_encode((string) $suggestedText) ?>;

        const openDelayPanel = function () {
          delayPanel.classList.remove('d-none');
        };

        const useSuggestedSlot = function () {
          openDelayPanel();
          if (suggestedSlot) {
            delayNewTime.value = suggestedSlot;
            delayMinutes.value = '';
          }
          delayReason.focus();
        };

        form.querySelector('[data-delay-toggle]').addEventListener('click', function () {
          openDelayPanel();
          delayReason.focus();
        });
        form.querySelector('[data-delay-cancel]').addEventListener('click', function () {
          delayPanel.classList.add('d-none');
        });

        // Chỉ dùng một trong hai cách nhập: số phút hoặc giờ cất cánh mới.
        delayMinutes.addEventListener('input', function () {
          if (delayMinutes.value !== '') {
            delayNewTime.value = '';
          }
        });
        delayNewTime.addEventListener('input', function () {
          if (delayNewTime.value !== '') {
            delayMinutes.value = '';
          }
        });

        document.querySelectorAll('[data-use-suggested-slot]').forEach(function (button) {
          button.addEventListener('click', function () {
            useSuggestedSlot();
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          });
        });

        form.addEventListener('submit', function (event) {
          const submitter = event.submitter || document.activeElement;
          const action = submitter && submitter.getAttribute('data-action');
          form.querySelector('[data-departure-action]').value = action || '';

          if (action === 'departure_delay') {
            if (delayReason.value.trim() === '') {
              event.preventDefault();
              delayReason.focus();
              window.alert('Vui lòng nhập lý do trễ / chưa đủ điều kiện bay.');
              return;
            }
            const hasMinutes = delayMinutes.value.trim() !== '';
            const hasNewTime = delayNewTime.value.trim() !== '';
            if (!hasMinutes && !hasNewTime) {
              event.preventDefault();
              delayMinutes.focus();
              window.alert('Vui lòng nhập số phút trễ tiếp hoặc giờ cất cánh mới.');
              return;
            }
            if (hasMinutes) {
              const minutes = parseInt(delayMinutes.value, 10);
              if (!minutes || minutes < 1 || minutes > 1440) {
                event.preventDefault();
                delayMinutes.focus();
                window.alert('Số phút trễ tiếp phải từ 1 đến 1440.');
                return;
              }
            }
            return;
          }

          if (action !== 'departure_confirm') {
            return;
          }
          if (hasConflicts) {
            event.preventDefault();
            confirmWarning.classList.remove('d-none');
            confirmWarning.textContent = suggestedText
              ? 'Chuyến bay đang trùng lịch cất cánh. Sân bay trống gần nhất lúc ' + suggestedText
                + ' — hãy trễ tới giờ này rồi mới cất cánh.'
              : 'Chuyến bay đang trùng lịch cất cánh, chưa thể cất cánh. Vui lòng kiểm tra lại lịch bay.';
            useSuggestedSlot();
          }
        });

        // Server báo lỗi (ví dụ trùng lịch): mở sẵn phần trễ + điền giờ sân bay trống gợi ý.
        const flash = document.querySelector('[data-flash]');
        if (flash && flash.classList.contains('alert-danger') && hasConflicts) {
          useSuggestedSlot();
        }
      })();
    </script>
  </body>
</html>
