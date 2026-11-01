<?php
/**
 * ============================================================
 * TRANG: BATCH JOB — SINH CHUYẾN BAY TỪ LỊCH BAY MÙA
 *        views/pages/chuyen_bay/tao_chuyen_bay.php
 * Quét bảng lich_bay_mua, sinh các dòng chuyen_bay_ngay theo
 * từng ngày trong khoảng ngày đã chọn (bỏ qua chuyến đã có).
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

$data = (new ChuyenBayController($conn))->handleGenerate();

// Trang này có thể mở trực tiếp (trang riêng) hoặc nhúng trong popup (iframe)
// ở trang danh sách chuyến bay — mở bằng nút "Tạo từ lịch".
$popup = isset($_GET['popup']) && (string) $_GET['popup'] !== '0';

$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$self = BASE_URL . '/views/pages/chuyen_bay/tao_chuyen_bay.php';
$listUrl = BASE_URL . '/index.php?page=chuyen_bay';
$scheduleUrl = BASE_URL . '/views/pages/chuyen_bay/lich_bay_mua.php';

$pageTitle = 'Tạo chuyến bay từ lịch mùa';
$pageHeader = 'Batch job: sinh chuyến bay từ lịch bay mùa';
$navActive = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Chuyến bay', 'url' => $listUrl],
    ['label' => 'Lịch bay mùa', 'url' => $scheduleUrl],
    ['label' => 'Tạo chuyến bay', 'url' => null],
];

ob_start();
?>
            <?php if ($data['flash']): ?>
              <div class="alert alert-<?= $e($data['flash']['type']) ?>" role="alert">
                <?= $e($data['flash']['message']) ?>
              </div>
            <?php endif; ?>

            <?php if ($data['result']): ?>
              <?php $result = $data['result']; ?>
              <div class="alert alert-<?= empty($result['errors']) ? 'success' : 'warning' ?>" role="alert">
                <strong>Kết quả batch job:</strong>
                đã tạo <strong><?= (int) $result['created'] ?></strong> chuyến bay,
                bỏ qua <strong><?= (int) $result['skipped'] ?></strong> chuyến đã tồn tại<?php if (!empty($result['updated'])): ?>,
                cập nhật <strong><?= (int) $result['updated'] ?></strong> chuyến đã có (ghi đè)<?php endif; ?><?php if (!empty($result['filled'])): ?>,
                bổ sung giờ hạ cánh cho <strong><?= (int) $result['filled'] ?></strong> chuyến<?php endif; ?>
                (khoảng <?= $e(date('d/m/Y', strtotime($result['from']))) ?> – <?= $e(date('d/m/Y', strtotime($result['to']))) ?>,
                cất cánh <?= $e($result['time']) ?> – hạ cánh dự kiến <?= $e($result['arrival']) ?><?= !empty($result['arrival_next_day']) ? ' (hôm sau)' : '' ?>).
                <?php if (!empty($result['errors'])): ?>
                  <ul class="mb-0 mt-2">
                    <?php foreach ($result['errors'] as $error): ?>
                      <li><?= $e($error) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
                <div class="mt-2">
                  <?php if ($popup): ?>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-generate-popup-close>Xem danh sách chuyến bay</button>
                  <?php else: ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= $listUrl ?>">Xem danh sách chuyến bay</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5">Tham số sinh chuyến bay</h2>
              </div>
              <div class="card-body">
                <form method="post" action="<?= $self ?><?= $popup ? '?popup=1' : '' ?>" class="row g-3">
                  <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>">
                  <?php if ($popup): ?>
                    <input type="hidden" name="popup" value="1">
                  <?php endif; ?>

                  <div class="col-md-3">
                    <label class="form-label" for="gen_tu_ngay">Từ ngày</label>
                    <input type="date" class="form-control" id="gen_tu_ngay" name="tu_ngay" required value="<?= $e($data['fromDate']) ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label" for="gen_den_ngay">Đến ngày</label>
                    <input type="date" class="form-control" id="gen_den_ngay" name="den_ngay" required value="<?= $e($data['toDate']) ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label" for="gen_gio">Giờ cất cánh</label>
                    <input type="time" class="form-control" id="gen_gio" name="gio_cat_canh" required value="<?= $e($data['timeOfDay']) ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label" for="gen_phut">Thời gian bay (phút)</label>
                    <input type="number" class="form-control" id="gen_phut" name="thoi_gian_bay" min="15" max="720" step="5" required value="<?= (int) $data['durationMinutes'] ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="gen_tau_bay">Loại tàu bay</label>
                    <select class="form-select" id="gen_tau_bay" name="id_loai_tau_bay">
                      <option value="0" <?= (int) $data['aircraftId'] === 0 ? 'selected' : '' ?>>Tự động theo hãng (loại dùng nhiều nhất)</option>
                      <?php foreach ($data['aircraftTypes'] as $aircraft): ?>
                        <option value="<?= (int) $aircraft['id'] ?>" <?= (int) $data['aircraftId'] === (int) $aircraft['id'] ? 'selected' : '' ?>>
                          <?= $e($aircraft['label']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-8 d-flex align-items-end">
                    <div class="mb-2">
                      <p class="mb-1 text-secondary">
                        Giờ dự kiến hạ cánh sẽ được tính tự động:
                        <strong id="gen_arrival"><?= $e($data['arrivalPreview'] ?? '—') ?></strong>
                        <span
                          class="badge text-bg-warning ms-1"
                          id="gen_arrival_next_day"
                          title="Chuyến hạ cánh vào ngày hôm sau (qua nửa đêm)"
                          <?= $data['arrivalPreview'] && $data['arrivalNextDay'] ? '' : 'hidden' ?>
                        >+1 ngày</span>
                      </p>
                      <small class="text-danger d-block" id="gen_arrival_warning" <?= $data['arrivalPreview'] && $data['arrivalNextDay'] ? '' : 'hidden' ?>>
                        Hạ cánh sang ngày hôm sau (qua nửa đêm) — ngày hạ cánh khác ngày cất cánh.
                      </small>
                    </div>
                  </div>

                  <div class="col-12">
                    <label class="form-label d-block">Khi chuyến đã tồn tại (cùng số hiệu + tuyến + ngày)</label>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="che_do_trung" id="gen_che_do_bo_qua" value="bo_qua" <?= $data['overwriteMode'] === 'ghi_de' ? '' : 'checked' ?>>
                      <label class="form-check-label" for="gen_che_do_bo_qua">Bỏ qua chuyến đã có</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="che_do_trung" id="gen_che_do_ghi_de" value="ghi_de" <?= $data['overwriteMode'] === 'ghi_de' ? 'checked' : '' ?>>
                      <label class="form-check-label" for="gen_che_do_ghi_de">Ghi đè / Cập nhật lại chuyến đã có</label>
                    </div>
                    <small class="text-secondary d-block">
                      “Ghi đè” chỉ cập nhật giờ cất cánh, giờ dự kiến hạ cánh và loại tàu bay;
                      trạng thái khai thác, số hành khách và hàng hoá của chuyến đã có được giữ nguyên.
                    </small>
                  </div>

                  <div class="col-12">
                    <button class="btn btn-primary" type="submit" name="action" value="generate">Tạo chuyến bay</button>
                    <button class="btn btn-outline-secondary" type="submit" name="action" value="preview">Xem trước</button>
                    <?php if ($popup): ?>
                      <button class="btn btn-outline-danger" type="button" data-generate-popup-close>Đóng</button>
                    <?php else: ?>
                      <a class="btn btn-link" href="<?= $scheduleUrl ?>">Quản lý lịch bay mùa</a>
                    <?php endif; ?>
                  </div>
                  <div class="col-12">
                    <small class="text-secondary">
                      Mỗi chuyến được sinh với trạng thái “Đang mở”, số hành khách và hàng hoá bằng 0.
                      Giờ dự kiến hạ cánh = giờ cất cánh + thời gian bay ở trên (có thể sang ngày hôm sau).
                      Chuyến đã tồn tại cùng mã + tuyến + ngày được xử lý theo lựa chọn bên trên.
                      Tối đa 62 ngày và 500 chuyến mỗi lần chạy.
                    </small>
                  </div>
                </form>
                <script>
                  (() => {
                    const timeInput = document.getElementById('gen_gio');
                    const minutesInput = document.getElementById('gen_phut');
                    const output = document.getElementById('gen_arrival');
                    const nextDayBadge = document.getElementById('gen_arrival_next_day');
                    const nextDayWarning = document.getElementById('gen_arrival_warning');
                    if (!timeInput || !minutesInput || !output) {
                      return;
                    }
                    const pad = (value) => String(value).padStart(2, '0');
                    const render = () => {
                      const match = /^(\d{2}):(\d{2})/.exec(timeInput.value || '');
                      const minutes = parseInt(minutesInput.value, 10);
                      if (!match || !Number.isFinite(minutes) || minutes < 1) {
                        output.textContent = '—';
                        if (nextDayBadge) nextDayBadge.hidden = true;
                        if (nextDayWarning) nextDayWarning.hidden = true;
                        return;
                      }
                      const total = parseInt(match[1], 10) * 60 + parseInt(match[2], 10) + minutes;
                      const nextDay = total >= 1440;
                      output.textContent = pad(Math.floor(total / 60) % 24) + ':' + pad(total % 60);
                      if (nextDayBadge) nextDayBadge.hidden = !nextDay;
                      if (nextDayWarning) nextDayWarning.hidden = !nextDay;
                    };
                    timeInput.addEventListener('input', render);
                    minutesInput.addEventListener('input', render);
                    render();
                  })();
                </script>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h2 class="card-title h5">Xem trước theo khoảng ngày</h2>
              </div>
              <div class="card-body">
                <?php if ($data['previewError']): ?>
                  <div class="alert alert-warning mb-0" role="alert"><?= $e($data['previewError']) ?></div>
                <?php elseif (!$data['preview']): ?>
                  <p class="mb-0 text-secondary">Không có lịch bay mùa nào <strong>đang mở</strong> và hiệu lực trong khoảng ngày này.</p>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                      <thead>
                        <tr>
                          <th scope="col">Mã CB</th>
                          <th scope="col">Hãng</th>
                          <th scope="col">Tuyến</th>
                          <th scope="col">Tần suất</th>
                          <th scope="col">Trạng thái lịch</th>
                          <th scope="col">Hiệu lực</th>
                          <th scope="col" class="text-end">Chuyến sẽ xử lý</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($data['preview'] as $row): ?>
                          <?php
                          $rowStatus = (string) ($row['trang_thai'] ?? 'Dang_Mo');
                          $rowStatusClass = [
                              'Dang_Mo' => 'text-bg-success',
                              'Tam_Dung' => 'text-bg-warning',
                              'Da_Dong' => 'text-bg-danger',
                          ][$rowStatus] ?? 'text-bg-light';
                          ?>
                          <tr>
                            <td class="fw-semibold"><?= $e($row['so_hieu']) ?></td>
                            <td><?= $e($row['hang']) ?></td>
                            <td><?= $e($row['tuyen']) ?></td>
                            <td>
                              <?= $e($row['tan_suat']) ?>
                              <?php if (!$row['hop_le']): ?>
                                <span class="badge text-bg-warning ms-1">Không đọc được tần suất</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <span class="badge <?= $e($rowStatusClass) ?>"><?= $e($row['trang_thai_nhan'] ?? $rowStatus) ?></span>
                            </td>
                            <td><?= $e($row['hieu_luc']) ?></td>
                            <td class="text-end">
                              <?= (int) ($row['so_tao_moi'] ?? $row['so_chuyen']) ?> tạo mới
                              <?php if (!empty($row['so_cap_nhat'])): ?>
                                <span class="d-block small text-primary">cập nhật <?= (int) $row['so_cap_nhat'] ?></span>
                              <?php endif; ?>
                              <?php if (!empty($row['so_bo_qua'])): ?>
                                <span class="d-block small text-secondary">bỏ qua <?= (int) $row['so_bo_qua'] ?> trùng</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                      <tfoot>
                        <tr>
                          <th scope="row" colspan="6" class="text-end">
                            Tổng cộng (<?= $data['overwriteMode'] === 'ghi_de' ? 'tạo mới + cập nhật' : 'chỉ tính chuyến tạo mới' ?>)
                          </th>
                          <th class="text-end"><?= (int) $data['totalPreview'] ?></th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                  <small class="text-secondary d-block mt-2">
                    Các chuyến sẽ được tạo với giờ cất cánh <?= $e($data['timeOfDay']) ?> và giờ dự kiến hạ cánh
                    <?= $e($data['arrivalPreview'] ?? '—') ?><?= $data['arrivalPreview'] && $data['arrivalNextDay'] ? ' (+1 ngày, hôm sau)' : '' ?>.
                    Chế độ đang chọn:
                    <strong><?= $e($data['generationModes'][$data['overwriteMode']] ?? $data['overwriteMode']) ?></strong>.
                    Chuyến đã tồn tại trùng số hiệu + tuyến + ngày sẽ
                    <?= $data['overwriteMode'] === 'ghi_de' ? 'được cập nhật lại giờ và loại tàu bay' : 'được bỏ qua, không tạo lại' ?>.
                  </small>
                <?php endif; ?>
              </div>
            </div>
            <script>
              (function () {
                const notifyParent = function (message) {
                  if (window.parent && window.parent !== window) {
                    window.parent.postMessage(message, window.location.origin);
                  }
                };
                // Nút "Đóng"/"Xem danh sách chuyến bay" khi trang được mở trong popup.
                document.querySelectorAll('[data-generate-popup-close]').forEach(function (button) {
                  button.addEventListener('click', function () {
                    notifyParent({ type: 'flight-popup-close' });
                  });
                });
                <?php if ($popup && $data['result']): ?>
                // Đã sinh chuyến: báo trang cha tải lại danh sách khi popup đóng.
                notifyParent({ type: 'flight-popup-generated' });
                <?php endif; ?>
              })();
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
      body.generate-popup-page { background: #f4f6f9; padding: 1rem; }
      body.generate-popup-page .card { margin-bottom: 1rem; }
      body.generate-popup-page .card:last-of-type { margin-bottom: 0; }
    </style>
  </head>
  <body class="generate-popup-page">
<?= $content ?>
  </body>
</html>
    <?php
    exit();
}

require __DIR__ . '/../../layouts/main.php';
