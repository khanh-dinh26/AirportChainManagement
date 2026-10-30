<?php
/**
 * ============================================================
 * TRANG: QUẢN LÝ CHUYẾN BAY  —  views/pages/chuyen_bay/index.php
 * Phân hệ: Điều hành chuyến bay (Thành viên 3)
 * ------------------------------------------------------------
 * Phạm vi gợi ý: lịch bay, giờ cất/hạ cánh, hãng hàng không, trạng thái.
 * Ghi chú cho Thành viên 3:
 *   - Dữ liệu trong bảng là MẪU, thay bằng dữ liệu do
 *     ChuyenBayController truyền vào.
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

// Endpoint JSON cho đồng hồ thời gian thực: tự chuyển trạng thái chuyến bay theo giờ thực tế.
// Trang danh sách gọi định kỳ để cập nhật mà không cần tải lại toàn bộ trang.
if (isset($_GET['sync_status'])) {
    $syncController = new ChuyenBayController($conn);
    $syncResult = $syncController->syncRealtimeStatuses();
    $pendingDate = trim((string) ($_GET['pending_date'] ?? ''));
    $pendingDateValid = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $pendingDate, $pendingParts)
        && checkdate((int) $pendingParts[2], (int) $pendingParts[3], (int) $pendingParts[1]);
    $pending = $syncController->getPendingDepartures($pendingDateValid ? $pendingDate : null);
    $effectivePendingDate = $pendingDateValid
        ? $pendingDate
        : substr($syncController->getCurrentDateTime(), 0, 10);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'server_time' => $syncController->getCurrentDateTime(),
        'departed' => (int) ($syncResult['departed'] ?? 0),
        'landed' => (int) ($syncResult['landed'] ?? 0),
        'overdue' => (int) ($syncResult['overdue'] ?? 0),
        'cancelled' => (int) ($syncResult['cancelled'] ?? 0),
        'pending_date' => $effectivePendingDate,
        'pending' => array_map(
            static function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'so_hieu' => $row['so_hieu'],
                    'trang_thai' => $row['trang_thai_tong'],
                ];
            },
            $pending
        ),
    ]);
    exit();
}

// Các liên kết cũ dạng ?page=chuyen_bay&edit=ID / &cancel=ID: mở popup ngay trên trang này
// thay vì chuyển sang trang riêng (xem đoạn script cuối trang).
$legacyEditId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$legacyCancelId = isset($_GET['cancel']) ? (int) $_GET['cancel'] : 0;

$flightData = (new ChuyenBayController($conn))->handle();
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$filters = $flightData['filters'];
$pendingDateValue = DateTime::createFromFormat('Y-m-d', (string) $flightData['pendingDate']);
$pendingDateLabel = $pendingDateValue ? $pendingDateValue->format('d/m/Y') : $flightData['pendingDate'];
// Giữ nguyên ngày đang xem khi phân trang / tìm kiếm danh sách chuyến bay.
$filters['pending_date'] = $flightData['pendingDate'];
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
$searchUrl = BASE_URL . '/index.php';
$flightUrl = static function (array $params = []) {
    return BASE_URL . '/index.php?' . http_build_query(array_merge(['page' => 'chuyen_bay'], $params));
};
$editUrl = static function ($id) {
    return BASE_URL . '/views/pages/chuyen_bay/sua.php?id=' . (int) $id;
};

$pageTitle  = 'Quản lý chuyến bay';
$pageHeader = 'Quản lý chuyến bay';
$navActive  = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => '/AirportChainManagement/index.php'],
    ['label' => 'Chuyến bay', 'url' => null],
];

ob_start();
?>
            <!--begin::Thống kê nhanh-->
            <div class="row">
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-primary">
                  <div class="inner">
                    <h3>128</h3>
                    <p>Chuyến bay hôm nay</p>
                  </div>
                  <i class="small-box-icon bi bi-airplane-engines"></i>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-info">
                  <div class="inner">
                    <h3>64</h3>
                    <p>Chuyến khởi hành</p>
                  </div>
                  <i class="small-box-icon bi bi-box-arrow-up-right"></i>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-success">
                  <div class="inner">
                    <h3>64</h3>
                    <p>Chuyến đến</p>
                  </div>
                  <i class="small-box-icon bi bi-box-arrow-in-down"></i>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-warning">
                  <div class="inner">
                    <h3>6</h3>
                    <p>Chuyến bị chậm</p>
                  </div>
                  <i class="small-box-icon bi bi-clock-history"></i>
                </div>
              </div>
            </div>
            <!--end::Thống kê nhanh-->

            <!--begin::Đồng hồ thời gian-->
            <style>
              /* Chớp đỏ cho chuyến đã qua giờ cất cánh mà chưa được xác nhận. */
              @keyframes flight-overdue-blink {
                0%, 49.9% { background-color: rgba(220, 53, 69, 0.20); }
                50%, 100% { background-color: rgba(220, 53, 69, 0.02); }
              }
              tr.flight-overdue-row > td {
                animation: flight-overdue-blink 1s steps(1, end) infinite;
              }
              @media (prefers-reduced-motion: reduce) {
                tr.flight-overdue-row > td {
                  animation: none;
                  background-color: rgba(220, 53, 69, 0.16);
                }
              }
            </style>
            <div class="card mb-3 border-primary-subtle">
              <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                <span class="badge text-bg-primary"><i class="bi bi-clock"></i></span>
                <span class="text-secondary">Thời gian:</span>
                <span class="fs-5 fw-semibold font-monospace" data-live-clock
                      data-server-time="<?= $escape($flightData['serverNow']) ?>"><?= $escape(DateTime::createFromFormat('Y-m-d H:i:s', $flightData['serverNow'])->format('d/m/Y H:i:s')) ?></span>

                <!--begin::Bộ lọc ngày cho 2 mục chuyến cần xác nhận-->
                <form class="ms-auto d-flex flex-wrap align-items-center gap-2" method="get"
                      action="<?= $escape($searchUrl) ?>">
                  <input type="hidden" name="page" value="chuyen_bay" />
                  <input type="hidden" name="keyword" value="<?= $escape($filters['keyword']) ?>" />
                  <input type="hidden" name="date" value="<?= $escape($filters['date']) ?>" />
                  <input type="hidden" name="status" value="<?= $escape($filters['status']) ?>" />
                  <label class="small text-secondary mb-0" for="pending_date">
                    <i class="bi bi-calendar-event me-1"></i>Ngày xem chuyến:
                  </label>
                  <input type="date" class="form-control form-control-sm w-auto" id="pending_date"
                         name="pending_date" value="<?= $escape($flightData['pendingDate']) ?>"
                         data-pending-date />
                  <noscript>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Xem</button>
                  </noscript>
                  <?php if ($flightData['pendingDate'] !== $flightData['pendingToday']): ?>
                    <a class="btn btn-sm btn-danger"
                       href="<?= $escape($flightUrl([
                           'pending_date' => $flightData['pendingToday'],
                           'keyword' => $filters['keyword'],
                           'date' => $filters['date'],
                           'status' => $filters['status'],
                       ])) ?>">
                      <i class="bi bi-calendar-check me-1"></i>Quay lại ngày hôm nay
                    </a>
                  <?php endif; ?>
                </form>
                <!--end::Bộ lọc ngày cho 2 mục chuyến cần xác nhận-->
              </div>
            </div>
            <!--end::Đồng hồ thời gian-->

            <?php if (!empty($flightData['dueDepartures'])): ?>
              <!--begin::Các chuyến đã đến giờ bay-->
              <div class="card mb-3 border-warning">
                <div class="card-header bg-warning-subtle d-flex align-items-center gap-2">
                  <h3 class="card-title h6 mb-0">
                    <i class="bi bi-airplane-engines me-1"></i>
                    Các chuyến đã đến giờ bay
                  </h3>
                  <span class="badge text-bg-warning"><?= count($flightData['dueDepartures']) ?></span>
                  <span class="small text-secondary ms-auto text-end">
                    Ngày <?= $escape($pendingDateLabel) ?> — đã tới giờ cất cánh, cần xác nhận điều kiện bay.
                    <span class="d-block text-danger-emphasis">
                      Quá <?= (int) $flightData['overdueLimit'] ?> phút chưa xác nhận → tự động chuyển Trễ chuyến.
                    </span>
                  </span>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                      <thead>
                        <tr>
                          <th class="ps-3">Mã CB</th>
                          <th>Hãng</th>
                          <th>Tuyến</th>
                          <th>Giờ cất cánh</th>
                          <th>Trạng thái</th>
                          <th class="text-end pe-3">Thao tác</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($flightData['dueDepartures'] as $due): ?>
                          <?php
                            $dueTime = DateTime::createFromFormat('Y-m-d H:i:s', (string) $due['gio_du_kien']);
                            $dueUrl = BASE_URL . '/views/pages/chuyen_bay/cat_canh.php?popup=1&id=' . (int) $due['id'];
                            $overdue = (int) ($due['overdue_minutes'] ?? 0);
                          ?>
                          <tr class="<?= $overdue >= 1 ? 'flight-overdue-row' : '' ?>"
                              data-overdue-since="<?= $escape($due['gio_du_kien']) ?>">
                            <td class="ps-3 fw-semibold"><?= $escape($due['so_hieu']) ?></td>
                            <td><?= $escape($due['ten_hang']) ?></td>
                            <td><?= $escape($due['ma_di']) ?> → <?= $escape($due['ma_den']) ?></td>
                            <td class="text-nowrap">
                              <?= $escape(DateTime::createFromFormat('Y-m-d H:i:s', (string) $due['gio_cat_canh_goc'])?->format('d/m/Y H:i') ?: $due['gio_du_kien']) ?>
                              <span class="badge text-bg-danger ms-1<?= $overdue >= 1 ? '' : ' d-none' ?>"
                                    data-overdue-badge>Quá hạn +<?= $overdue ?> phút</span>
                            </td>
                            <td>
                              <?php if ($due['trang_thai_tong'] === 'Delay'): ?>
                                <span class="badge text-bg-warning">Trễ chuyến</span>
                              <?php else: ?>
                                <span class="badge text-bg-success">Đang mở</span>
                              <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                              <?php if ($due['trang_thai_tong'] === 'Delay'): ?>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="<?= $escape($dueUrl) ?>"
                                   data-flight-popup-open
                                   data-flight-popup-url="<?= $escape($dueUrl) ?>"
                                   data-flight-popup-title="Kiểm tra yêu cầu an toàn — <?= $escape($due['so_hieu']) ?>">
                                  <i class="bi bi-shield-check me-1"></i> Đã đủ điều kiện an toàn
                                </a>
                              <?php else: ?>
                                <a class="btn btn-sm btn-success"
                                   href="<?= $escape($dueUrl) ?>"
                                   data-flight-popup-open
                                   data-flight-popup-url="<?= $escape($dueUrl) ?>"
                                   data-flight-popup-title="Xác nhận điều kiện bay — <?= $escape($due['so_hieu']) ?>">
                                  <i class="bi bi-check2-circle me-1"></i> Đã đủ điều kiện bay
                                </a>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <!--end::Các chuyến đã đến giờ bay-->
            <?php endif; ?>

            <?php if (!empty($flightData['delayedDueDepartures'])): ?>
              <!--begin::Các chuyến bay trễ chuyến-->
              <div class="card mb-3 border-danger-subtle">
                <div class="card-header bg-danger-subtle d-flex align-items-center gap-2">
                  <h3 class="card-title h6 mb-0">
                    <i class="bi bi-clock-history me-1"></i>
                    Các chuyến bay trễ chuyến
                  </h3>
                  <span class="badge text-bg-danger"><?= count($flightData['delayedDueDepartures']) ?></span>
                  <span class="small text-secondary ms-auto">
                    Ngày <?= $escape($pendingDateLabel) ?> — đang trễ chuyến, cần xác nhận điều kiện bay hoặc delay tiếp.
                    Tổng thời gian trễ quá <?= (int) ($flightData['cancelDelayLimitHours'] ?? 5) ?> giờ sẽ tự động hủy chuyến.
                  </span>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                      <thead>
                        <tr>
                          <th class="ps-3">Mã CB</th>
                          <th>Hãng</th>
                          <th>Tuyến</th>
                          <th>Giờ cất cánh</th>
                          <th>Giờ cất cánh sau delay</th>
                          <th>Trạng thái</th>
                          <th>Lý do</th>
                          <th>Thời gian trễ dự kiến</th>
                          <th class="text-end pe-3">Thao tác</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($flightData['delayedDueDepartures'] as $delayed): ?>
                          <?php
                            $delayedTime = DateTime::createFromFormat('Y-m-d H:i:s', (string) $delayed['gio_du_kien']);
                            $delayedUrl = BASE_URL . '/views/pages/chuyen_bay/cat_canh.php?popup=1&id=' . (int) $delayed['id'];
                          ?>
                          <tr>
                            <td class="ps-3 fw-semibold"><?= $escape($delayed['so_hieu']) ?></td>
                            <td><?= $escape($delayed['ten_hang']) ?></td>
                            <td><?= $escape($delayed['ma_di']) ?> → <?= $escape($delayed['ma_den']) ?></td>
                            <td class="text-nowrap"><?= $escape(DateTime::createFromFormat('Y-m-d H:i:s', (string) $delayed['gio_cat_canh_goc'])?->format('d/m/Y H:i') ?: $delayed['gio_du_kien']) ?></td>
                            <td class="text-nowrap">
                              <?= $escape(DateTime::createFromFormat('Y-m-d H:i:s', (string) ($delayed['gio_cat_canh_du_kien'] ?? $delayed['gio_du_kien']))?->format('d/m/Y H:i') ?: $delayed['gio_du_kien']) ?>
                            </td>
                            <td><span class="badge text-bg-warning">Trễ chuyến</span></td>
                            <td>
                              <?= $escape($delayed['ly_do_tre'] ?? '') ?>
                            </td>
                            <td>
                              <?php if (!empty($delayed['tong_tre_phut'])): ?>
                                <span class="badge bg-warning text-dark ms-1"><?= (int) $delayed['tong_tre_phut'] ?> phút</span>
                              <?php else: ?>
                                —
                              <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                              <a class="btn btn-sm btn-outline-danger"
                                 href="<?= $escape($delayedUrl) ?>"
                                 data-flight-popup-open
                                 data-flight-popup-url="<?= $escape($delayedUrl) ?>"
                                 data-flight-popup-title="Kiểm tra yêu cầu an toàn — <?= $escape($delayed['so_hieu']) ?>">
                                <i class="bi bi-shield-check me-1"></i> Đã đủ điều kiện an toàn
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <!--end::Các chuyến bay trễ chuyến-->
            <?php endif; ?>

            <?php if (empty($flightData['dueDepartures']) && empty($flightData['delayedDueDepartures'])): ?>
              <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 mb-3" role="status">
                <i class="bi bi-check2-circle text-success"></i>
                <span>
                  Không có chuyến nào cần xác nhận cất cánh trong ngày
                  <strong><?= $escape($pendingDateLabel) ?></strong>.
                </span>
              </div>
            <?php endif; ?>

            <div class="row">
              <?php if ($flightData['flash']): ?>
                <div class="col-12">
                  <div class="alert alert-<?= $escape($flightData['flash']['type']) ?>" role="alert">
                    <?= $escape($flightData['flash']['message']) ?>
                  </div>
                </div>
              <?php endif; ?>
              <!--begin::Danh sách chuyến bay-->
              <div class="col-12">
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Danh sách chuyến bay</h3>
                    <div class="card-tools d-flex flex-wrap gap-1">
                      <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/lich_su_bay.php" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-journal-text me-1"></i> Lịch sử bay
                      </a>
                      <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/lich_bay_mua.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-calendar3 me-1"></i> Lịch bay mùa
                      </a>
                      <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/tao_chuyen_bay.php" class="btn btn-sm btn-outline-success" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/tao_chuyen_bay.php?popup=1" data-flight-popup-title="Tạo chuyến bay từ lịch mùa">
                        <i class="bi bi-lightning-charge me-1"></i> Tạo từ lịch
                      </a>
                      <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/them.php" class="btn btn-sm btn-primary" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/them.php?popup=1" data-flight-popup-title="Thêm chuyến bay">
                        <i class="bi bi-plus-lg me-1"></i> Thêm chuyến bay
                      </a>
                    </div>
                  </div>
                  <div class="card-body">
                    <form class="row g-2 mb-3" action="<?= $searchUrl ?>" method="get">
                      <input type="hidden" name="page" value="chuyen_bay" />
                      <input type="hidden" name="pending_date" value="<?= $escape($flightData['pendingDate']) ?>" />
                      <div class="col-md-3">
                        <input type="text" class="form-control" name="keyword" value="<?= $escape($filters['keyword']) ?>" placeholder="Mã chuyến bay / hãng" />
                      </div>
                      <div class="col-md-2">
                        <input type="date" class="form-control" name="date" value="<?= $escape($filters['date']) ?>" />
                      </div>
                      <div class="col-md-3">
                        <select class="form-select" name="status">
                          <option value="" <?= $filters['status'] === '' ? 'selected' : '' ?>>-- Tất cả trạng thái --</option>
                          <option value="Dang_Mo" <?= $filters['status'] === 'Dang_Mo' ? 'selected' : '' ?>>Đang mở</option>
                          <option value="Cat_Canh" <?= $filters['status'] === 'Cat_Canh' ? 'selected' : '' ?>>Cất cánh</option>
                          <option value="Delay" <?= $filters['status'] === 'Delay' ? 'selected' : '' ?>>Trễ chuyến</option>
                          <option value="Ha_Canh" <?= $filters['status'] === 'Ha_Canh' ? 'selected' : '' ?>>Đã hạ cánh</option>
                          <option value="Huy" <?= $filters['status'] === 'Huy' ? 'selected' : '' ?>>Hủy chuyến</option>
                        </select>
                      </div>
                      <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-outline-primary">Tìm kiếm</button>
                      </div>
                      <div class="col-md-2 d-grid">
                        <a class="btn btn-danger" style="background-color:#dc3545;color:#fff;border-color:#dc3545;" href="<?= $escape($searchUrl) ?>?page=chuyen_bay">Xóa lọc</a>
                      </div>
                    </form>

                    <div class="table-responsive p-0">
                      <table class="table table-hover align-middle">
                        <thead>
                          <tr>
                            <th>Mã CB</th>
                            <th>Hãng</th>
                            <th>Điểm đi</th>
                            <th>Điểm đến</th>
                            <th>Ngày bay</th>
                            <th>Cất cánh</th>
                            <th>Hạ cánh</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (!$flightData['flights']): ?>
                            <tr>
                              <td colspan="9" class="text-center text-secondary py-4">Không tìm thấy chuyến bay phù hợp.</td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($flightData['flights'] as $flight): ?>
                              <?php
                                $departureTs = strtotime($flight['gio_du_kien']);
                                $arrivalTs = !empty($flight['gio_ha_canh_hien_thi']) ? strtotime($flight['gio_ha_canh_hien_thi']) : null;
                                // Hạ cánh rơi sang ngày hôm sau (bay qua nửa đêm): +1 ngày, +2 ngày...
                                $arrivalDayOffset = 0;
                                if ($arrivalTs !== null) {
                                    $arrivalDayOffset = (int) round(
                                        (strtotime(date('Y-m-d', $arrivalTs)) - strtotime(date('Y-m-d', $departureTs))) / 86400
                                    );
                                }
                              ?>
                              <tr>
                                <td><?= $escape($flight['so_hieu']) ?></td>
                                <td><?= $escape($flight['ten_hang']) ?></td>
                                <td>
                                  <div class="fw-semibold">
                                    <?= $escape($flight['san_bay_di']) ?>
                                    <?php if (!empty($flight['ten_ngan_di'])): ?>
                                      <span class="text-secondary fw-normal small">· <?= $escape($flight['ten_ngan_di']) ?></span>
                                    <?php endif; ?>
                                  </div>
                                  <?php if (!empty($flight['vi_tri_di'])): ?>
                                    <div class="text-secondary small">
                                      <i class="bi bi-geo-alt me-1"></i><?= $escape($flight['vi_tri_di']) ?>
                                    </div>
                                  <?php endif; ?>
                                </td>
                                <td>
                                  <div class="fw-semibold">
                                    <?= $escape($flight['san_bay_den']) ?>
                                    <?php if (!empty($flight['ten_ngan_den'])): ?>
                                      <span class="text-secondary fw-normal small">· <?= $escape($flight['ten_ngan_den']) ?></span>
                                    <?php endif; ?>
                                  </div>
                                  <?php if (!empty($flight['vi_tri_den'])): ?>
                                    <div class="text-secondary small">
                                      <i class="bi bi-geo-alt me-1"></i><?= $escape($flight['vi_tri_den']) ?>
                                    </div>
                                  <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= $escape(date('d/m/Y', $departureTs)) ?></td>
                                <td class="text-nowrap">
                                  <?= $escape(date('h:i A', $departureTs)) ?>
                                  <?php if (($flight['trang_thai_tong'] ?? '') === 'Delay' && !empty($flight['gio_cat_canh_goc'])): ?>
                                    <span class="text-secondary small d-block">
                                      Trước delay: <?= $escape(date('H:i', strtotime((string) $flight['gio_cat_canh_goc']))) ?>
                                    </span>
                                  <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                  <?php if ($arrivalTs === null): ?>
                                    —
                                  <?php else: ?>
                                    <?= $escape(date('h:i A', $arrivalTs)) ?>
                                    <?php if ($arrivalDayOffset > 0): ?>
                                      <span class="badge text-bg-warning ms-1">+<?= (int) $arrivalDayOffset ?> ngày</span>
                                    <?php endif; ?>
                                    <?php if (($flight['trang_thai_tong'] ?? '') === 'Delay' && !empty($flight['gio_ha_canh_goc'])): ?>
                                      <span class="text-secondary small d-block">
                                        Trước delay: <?= $escape(date('H:i', strtotime((string) $flight['gio_ha_canh_goc']))) ?>
                                      </span>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </td>
                                <td>
                                  <span class="badge text-bg-<?= $escape($statusClasses[$flight['trang_thai_tong']] ?? 'secondary') ?>"<?= ($flight['trang_thai_tong'] ?? '') === 'Ha_Canh' ? ' style="background-color:#33FFFF;color:#000;"' : '' ?>>
                                    <?= $escape($statusLabels[$flight['trang_thai_tong']] ?? $flight['trang_thai_tong']) ?>
                                  </span>
                                </td>
                                <td class="text-end text-nowrap">
                                  <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/chi_tiet.php?id=<?= (int) $flight['id'] ?>" class="btn btn-sm btn-info" style="color:#fff;" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/chi_tiet.php?popup=1&amp;id=<?= (int) $flight['id'] ?>" data-flight-popup-title="Chi tiết chuyến <?= $escape($flight['so_hieu']) ?>">Xem chi tiết</a>
                                  <a href="<?= $editUrl($flight['id']) ?>" class="btn btn-sm btn-outline-primary" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/sua.php?popup=1&amp;id=<?= (int) $flight['id'] ?>" data-flight-popup-title="Sửa chuyến <?= $escape($flight['so_hieu']) ?>">Sửa</a>
                                  <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/sua.php?log=1&amp;id=<?= (int) $flight['id'] ?>" class="btn btn-sm btn-outline-info" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/sua.php?popup=1&amp;id=<?= (int) $flight['id'] ?>&amp;log=1" data-flight-popup-title="Nhật ký chuyến <?= $escape($flight['so_hieu']) ?>">
                                    <i class="bi bi-clock-history me-1"></i>Lịch sử
                                  </a>
                                  <?php if ($flight['trang_thai_tong'] !== 'Huy'): ?>
                                    <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/huy.php?id=<?= (int) $flight['id'] ?>" class="btn btn-sm btn-outline-danger" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/huy.php?popup=1&amp;id=<?= (int) $flight['id'] ?>" data-flight-popup-title="Hủy chuyến <?= $escape($flight['so_hieu']) ?>">Hủy</a>
                                  <?php endif; ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="card-footer clearfix">
                    <?php
                      // Phân trang gọn: tối đa 5 số trang, hai bên là dấu "…" bấm vào để nhập số trang muốn đến.
                      $totalPages = max(1, (int) $flightData['totalPages']);
                      $currentPage = min(max(1, (int) $flightData['page']), $totalPages);
                      $windowSize = min(5, $totalPages);
                      $windowStart = $currentPage - (int) floor($windowSize / 2);
                      $windowStart = max(1, min($windowStart, $totalPages - $windowSize + 1));
                      $windowEnd = $windowStart + $windowSize - 1;
                      $pageNumbers = range($windowStart, $windowEnd);
                      // URL mẫu để nhảy tới trang bất kỳ (JS thay __PAGE__ bằng số người dùng nhập).
                      $pageJumpTemplate = $flightUrl(array_merge($filters, ['p' => '__PAGE__']));
                    ?>
                    <ul class="pagination pagination-sm m-0 float-end flight-pagination">
                      <?php if ($currentPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= $flightUrl(array_merge($filters, ['p' => $currentPage - 1])) ?>">&laquo;</a></li>
                      <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                      <?php endif; ?>
                      <?php if ($windowStart > 1): ?>
                        <li class="page-item">
                          <a class="page-link" href="<?= $flightUrl(array_merge($filters, ['p' => 1])) ?>"
                             data-page-jump
                             data-page-url="<?= $escape($pageJumpTemplate) ?>"
                             data-total-pages="<?= (int) $totalPages ?>"
                             title="Nhập số trang muốn đến">…</a>
                        </li>
                      <?php endif; ?>
                      <?php foreach ($pageNumbers as $pageNumber): ?>
                        <li class="page-item <?= $pageNumber === $currentPage ? 'active' : '' ?>">
                          <a class="page-link" href="<?= $flightUrl(array_merge($filters, ['p' => $pageNumber])) ?>"><?= $pageNumber ?></a>
                        </li>
                      <?php endforeach; ?>
                      <?php if ($windowEnd < $totalPages): ?>
                        <li class="page-item">
                          <a class="page-link" href="<?= $flightUrl(array_merge($filters, ['p' => $totalPages])) ?>"
                             data-page-jump
                             data-page-url="<?= $escape($pageJumpTemplate) ?>"
                             data-total-pages="<?= (int) $totalPages ?>"
                             title="Nhập số trang muốn đến">…</a>
                        </li>
                      <?php endif; ?>
                      <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="<?= $flightUrl(array_merge($filters, ['p' => $currentPage + 1])) ?>">&raquo;</a></li>
                      <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </div>
              </div>
              <!--end::Danh sách chuyến bay-->
            </div>

            <!--begin::Popup dùng chung cho thêm / sửa / hủy chuyến bay-->
            <div class="flight-popup" id="flight-popup" hidden>
              <div class="flight-popup-backdrop" data-flight-popup-close></div>
              <div class="flight-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="flight-popup-title" tabindex="-1">
                <div class="flight-popup-header">
                  <h2 class="flight-popup-title" id="flight-popup-title">Thêm chuyến bay</h2>
                </div>
                <div class="flight-popup-body">
                  <iframe class="flight-popup-frame" title="Form chuyến bay" data-flight-popup-frame></iframe>
                </div>
              </div>
            </div>
            <style>
              .flight-popup { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: center; justify-content: center; padding: 1.25rem; }
              .flight-popup[hidden] { display: none; }
              .flight-popup-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .55); }
              .flight-popup-dialog { position: relative; display: flex; flex-direction: column; width: min(1040px, 100%); max-height: 92vh; background: #fff; border-radius: .75rem; box-shadow: 0 24px 60px rgba(15, 23, 42, .35); overflow: hidden; }
              .flight-popup-dialog:focus { outline: none; }
              .flight-popup-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1.25rem; border-bottom: 1px solid var(--bs-border-color, #dee2e6); }
              .flight-popup-title { margin: 0; font-size: 1.05rem; font-weight: 600; }
              .flight-popup-body { flex: 1 1 auto; min-height: 0; background: #f4f6f9; }
              .flight-popup-frame { display: block; width: 100%; height: min(78vh, 760px); border: 0; background: #f4f6f9; }
              body.flight-popup-open { overflow: hidden; }
              @media (max-width: 575.98px) {
                .flight-popup { padding: .5rem; }
                .flight-popup-frame { height: 80vh; }
              }
            </style>
            <script>
              (function () {
                const popup = document.getElementById('flight-popup');
                if (!popup) {
                  return;
                }
                const frame = popup.querySelector('[data-flight-popup-frame]');
                const title = popup.querySelector('.flight-popup-title');
                // Popup "Tạo từ lịch" đã sinh chuyến: đóng popup thì tải lại danh sách.
                let reloadAfterClose = false;

                const closePopup = function () {
                  popup.hidden = true;
                  frame.removeAttribute('src');
                  title.textContent = 'Thêm chuyến bay';
                  document.body.classList.remove('flight-popup-open');
                  if (reloadAfterClose) {
                    reloadAfterClose = false;
                    window.location.reload();
                  }
                };

                const openPopup = function (url, popupTitle) {
                  title.textContent = popupTitle || 'Chuyến bay';
                  frame.title = popupTitle || 'Form chuyến bay';
                  frame.src = url;
                  popup.hidden = false;
                  document.body.classList.add('flight-popup-open');
                  const dialog = popup.querySelector('.flight-popup-dialog');
                  if (dialog) {
                    dialog.focus();
                  }
                };

                document.querySelectorAll('[data-flight-popup-open]').forEach(function (opener) {
                  opener.addEventListener('click', function (event) {
                    event.preventDefault();
                    openPopup(
                      opener.getAttribute('data-flight-popup-url') || opener.getAttribute('href'),
                      opener.getAttribute('data-flight-popup-title')
                    );
                  });
                });

                popup.querySelectorAll('[data-flight-popup-close]').forEach(function (closer) {
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
                  if (event.data.type === 'flight-popup-close' || event.data.type === 'flight-add-close') {
                    closePopup();
                  } else if (event.data.type === 'flight-popup-generated') {
                    // Popup "Tạo từ lịch" vừa sinh chuyến: đánh dấu để tải lại danh sách khi đóng.
                    reloadAfterClose = true;
                  } else if (event.data.type === 'flight-popup-saved' || event.data.type === 'flight-add-saved') {
                    // Tải lại danh sách để nhận flash thành công từ session.
                    window.location.reload();
                  } else if (event.data.type === 'flight-departure-done') {
                    // Đã xác nhận cất cánh / trễ tiếp: tải lại để cập nhật trạng thái và popup kế tiếp.
                    window.location.reload();
                  }
                });

                // Liên kết cũ (?edit=ID / ?cancel=ID): mở popup ngay tại trang này.
                const baseUrl = <?= json_encode(BASE_URL) ?>;
                const params = new URLSearchParams(window.location.search);
                const legacyEditId = parseInt(params.get('edit') || '<?= (int) $legacyEditId ?>', 10);
                const legacyCancelId = parseInt(params.get('cancel') || '<?= (int) $legacyCancelId ?>', 10);
                if (legacyEditId > 0) {
                  openPopup(baseUrl + '/views/pages/chuyen_bay/sua.php?popup=1&id=' + legacyEditId, 'Sửa chuyến bay');
                } else if (legacyCancelId > 0) {
                  openPopup(baseUrl + '/views/pages/chuyen_bay/huy.php?popup=1&id=' + legacyCancelId, 'Hủy chuyến bay');
                }
                if (legacyEditId > 0 || legacyCancelId > 0) {
                  params.delete('edit');
                  params.delete('cancel');
                  const query = params.toString();
                  history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
                }
              })();
            </script>
            <!--end::Popup thêm / sửa / hủy chuyến bay-->
            <!--end::Popup thêm chuyến bay-->
            <script>
              // Phân trang: bấm dấu "…" để nhập số trang muốn đến.
              (function () {
                const pagination = document.querySelector('.flight-pagination');
                if (!pagination) {
                  return;
                }

                const buildUrl = function (link, page) {
                  const template = link.getAttribute('data-page-url') || '';
                  return template.replace('__PAGE__', encodeURIComponent(page));
                };

                pagination.addEventListener('click', function (event) {
                  const link = event.target.closest('[data-page-jump]');
                  if (!link) {
                    return;
                  }
                  event.preventDefault();
                  if (link.dataset.jumping === '1') {
                    return;
                  }
                  link.dataset.jumping = '1';

                  const total = Math.max(1, parseInt(link.getAttribute('data-total-pages') || '1', 10));
                  const item = link.parentElement;
                  const input = document.createElement('input');
                  input.type = 'number';
                  input.className = 'form-control form-control-sm page-jump-input';
                  input.min = '1';
                  input.max = String(total);
                  input.placeholder = 'Trang';
                  input.setAttribute('aria-label', 'Nhập số trang muốn đến');
                  input.style.width = '5.5rem';

                  let done = false;
                  const restore = function () {
                    input.remove();
                    link.style.display = '';
                    link.dataset.jumping = '0';
                  };
                  const submit = function () {
                    if (done) {
                      return;
                    }
                    const page = parseInt(input.value, 10);
                    if (!page || page < 1 || page > total) {
                      // Số không hợp lệ: trả lại dấu "…" để nhập lại.
                      done = true;
                      restore();
                      return;
                    }
                    done = true;
                    window.location.href = buildUrl(link, Math.min(Math.max(page, 1), total));
                  };

                  link.style.display = 'none';
                  item.appendChild(input);
                  input.focus();
                  input.select();
                  input.addEventListener('keydown', function (keyEvent) {
                    if (keyEvent.key === 'Enter') {
                      keyEvent.preventDefault();
                      submit();
                    } else if (keyEvent.key === 'Escape') {
                      keyEvent.preventDefault();
                      done = true;
                      restore();
                    }
                  });
                  input.addEventListener('blur', function () {
                    if (!done) {
                      submit();
                    }
                  });
                });
              })();
            </script>
            <script>
              // Đồng hồ thời gian + tự động cập nhật trạng thái chuyến bay theo giờ thực tế.
              (function () {
                const clockEl = document.querySelector('[data-live-clock]');
                if (!clockEl) {
                  return;
                }
                const pad = function (value) {
                  return String(value).padStart(2, '0');
                };
                // Mốc giờ lấy từ server để đồng hồ không lệch theo giờ máy khách.
                const parseServerTime = function (value) {
                  const m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/.exec(value || '');
                  if (!m) {
                    return Date.now();
                  }
                  return new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]).getTime();
                };
                let clockOffset = parseServerTime(clockEl.getAttribute('data-server-time')) - Date.now();
                const renderClock = function () {
                  const d = new Date(Date.now() + clockOffset);
                  clockEl.textContent = pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear()
                    + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
                };
                renderClock();
                window.setInterval(renderClock, 1000);

                // Cảnh báo quá hạn: cập nhật số phút quá hạn + chớp đỏ theo đồng hồ server mỗi giây.
                const overdueRows = Array.from(document.querySelectorAll('[data-overdue-since]'));
                const renderOverdue = function () {
                  const nowTs = Date.now() + clockOffset;
                  overdueRows.forEach(function (row) {
                    const since = parseServerTime(row.getAttribute('data-overdue-since'));
                    const minutes = Math.max(0, Math.floor((nowTs - since) / 60000));
                    const badge = row.querySelector('[data-overdue-badge]');
                    if (minutes >= 1) {
                      row.classList.add('flight-overdue-row');
                      if (badge) {
                        badge.classList.remove('d-none');
                        badge.textContent = 'Quá hạn +' + minutes + ' phút';
                      }
                    } else {
                      row.classList.remove('flight-overdue-row');
                      if (badge) {
                        badge.classList.add('d-none');
                      }
                    }
                  });
                };
                renderOverdue();
                window.setInterval(renderOverdue, 1000);

                // Bộ lọc ngày: chọn ngày xong tự tải lại 2 mục chuyến cần xác nhận.
                document.querySelectorAll('[data-pending-date]').forEach(function (input) {
                  input.addEventListener('change', function () {
                    if (input.form) {
                      input.form.submit();
                    }
                  });
                });

                const pendingDate = <?= json_encode($flightData['pendingDate']) ?>;
                const syncUrl = <?= json_encode(BASE_URL . '/index.php?page=chuyen_bay&sync_status=1') ?>
                  + '&pending_date=' + encodeURIComponent(pendingDate);
                const popup = document.getElementById('flight-popup');
                const popupOpen = function () {
                  return popup && !popup.hidden;
                };
                let syncing = false;
                // id => trạng thái hiện tại: phát hiện chuyến mới hoặc chuyến vừa bị đổi trạng thái.
                const knownPending = <?= json_encode((object) array_column($flightData['pendingDepartures'], 'trang_thai_tong', 'id')) ?>;
                const knownPendingCount = Object.keys(knownPending).length;
                const syncStatuses = function () {
                  // Không tải lại trang khi đang mở form trong popup (tránh mất dữ liệu đang nhập).
                  if (syncing || document.hidden || popupOpen()) {
                    return;
                  }
                  syncing = true;
                  fetch(syncUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
                    .then(function (response) {
                      return response.ok ? response.json() : null;
                    })
                    .then(function (data) {
                      if (!data) {
                        return;
                      }
                      if (typeof data.server_time === 'string') {
                        clockOffset = parseServerTime(data.server_time) - Date.now();
                        renderClock();
                        renderOverdue();
                      }
                      const departed = Number(data.departed) || 0;
                      const landed = Number(data.landed) || 0;
                      const overdue = Number(data.overdue) || 0;
                      const cancelled = Number(data.cancelled) || 0;
                      const pendingList = Array.isArray(data.pending) ? data.pending : [];
                      const pendingChanged = pendingList.length !== knownPendingCount
                        || pendingList.some(function (item) {
                          return knownPending[String(item.id)] !== item.trang_thai;
                        });
                      if (departed + landed + overdue + cancelled === 0 && !pendingChanged) {
                        return;
                      }
                      window.setTimeout(function () {
                        window.location.reload();
                      }, 1200);
                    })
                    .catch(function () {})
                    .then(function () {
                      syncing = false;
                    });
                };
                window.setInterval(syncStatuses, 15000);
                syncStatuses();
              })();
            </script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
