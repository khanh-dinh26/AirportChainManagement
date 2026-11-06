<?php


require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';


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
$airportFilterText = static function ($airportId) use ($flightData) {
    foreach ($flightData['airports'] as $airport) {
        if ((int) $airport['id'] === (int) $airportId) {
            return trim(($airport['ma_iata'] ?? '') . ' – ' . $airport['label']);
        }
    }

    return '';
};
$searchUrl = BASE_URL . '/index.php';
$historyFilters = array_filter([
    'keyword' => $filters['keyword'],
    'id_san_bay_di' => $filters['id_san_bay_di'],
    'id_san_bay_den' => $filters['id_san_bay_den'],
    'date' => $filters['date'],
], static function ($value) {
    return $value !== '' && $value !== 0;
});
$historyUrl = BASE_URL . '/views/pages/chuyen_bay/lich_su_bay.php'
    . ($historyFilters ? '?' . http_build_query($historyFilters) : '');
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
            <link rel="stylesheet" href="<?= BASE_URL ?>/views/pages/chuyen_bay/chuyen_bay-ui.css?v=13" />
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
                0%, 49.9% {
                  border-color: #ef4444;
                  box-shadow: 0 0 0 .25rem rgba(220, 53, 69, .28);
                }
                50%, 100% {
                  border-color: #dce8f4;
                  box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
                }
              }
              .flight-overdue-card.flight-overdue-row {
                animation: flight-overdue-blink 1s steps(1, end) infinite;
              }
              @media (prefers-reduced-motion: reduce) {
                .flight-overdue-card.flight-overdue-row {
                  animation: none;
                  border-color: #ef4444;
                  box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .2);
                }
              }
            </style>
            <div class="card mb-3 border-primary-subtle">
              <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                <span class="badge text-bg-primary"><i class="bi bi-clock"></i></span>
                <span class="text-secondary">Thời gian:</span>
                <span class="fs-2 fw-semibold font-monospace" data-live-clock
                      data-server-time="<?= $escape($flightData['serverNow']) ?>"><?= $escape(DateTime::createFromFormat('Y-m-d H:i:s', $flightData['serverNow'])->format('d/m/Y H:i:s')) ?></span>

                <!--begin::Bộ lọc ngày cho 2 mục chuyến cần xác nhận-->
                <form class="ms-auto d-flex flex-wrap align-items-center gap-2" method="get"
                      action="<?= $escape($searchUrl) ?>">
                  <input type="hidden" name="page" value="chuyen_bay" />
                  <input type="hidden" name="id_san_bay_di" value="<?= (int) $filters['id_san_bay_di'] ?>" />
                  <input type="hidden" name="id_san_bay_den" value="<?= (int) $filters['id_san_bay_den'] ?>" />
                  <input type="hidden" name="date" value="<?= $escape($filters['date']) ?>" />
                  <input type="hidden" name="status" value="<?= $escape($filters['status']) ?>" />
                  <label class="small text-secondary mb-0" for="pending_date">
                    <i class="bi bi-calendar-event me-1"></i>Ngày xem chuyến:
                  </label>
                  <input type="date" class="form-control form-control-sm w-auto" id="pending_date"
                         name="pending_date" lang="vi" value="<?= $escape($flightData['pendingDate']) ?>"
                         data-pending-date />
                  <noscript>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Xem</button>
                  </noscript>
                  <?php if ($flightData['pendingDate'] !== $flightData['pendingToday']): ?>
                    <a class="btn btn-sm btn-danger"
                       href="<?= $escape($flightUrl([
                           'pending_date' => $flightData['pendingToday'],
                           'id_san_bay_di' => $filters['id_san_bay_di'],
                           'id_san_bay_den' => $filters['id_san_bay_den'],
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
              <div class="card mb-3 border-info flight-due-section">
                <div class="card-header d-flex align-items-center gap-2">
                  <h3 class="card-title h6 mb-0">
                    <i class="bi bi-airplane-engines me-1"></i>
                    Các chuyến đã đến giờ bay
                  </h3>
                  <span class="badge text-bg-success"><?= count($flightData['dueDepartures']) ?></span>
                  <span class="small text-secondary ms-auto text-end">
                    Ngày <?= $escape($pendingDateLabel) ?> — đã tới giờ cất cánh, cần xác nhận điều kiện bay.
                    <span class="d-block text-danger-emphasis">
                      Quá <?= (int) $flightData['overdueLimit'] ?> phút chưa xác nhận → tự động chuyển Trễ chuyến.
                    </span>
                  </span>
                </div>
                <div class="card-body">
                  <div class="flight-landed-list flight-schedule-list">
                    <?php foreach ($flightData['dueDepartures'] as $due): ?>
                      <?php
                        $dueUrl = BASE_URL . '/views/pages/chuyen_bay/cat_canh.php?popup=1&id=' . (int) $due['id'];
                        $overdue = (int) ($due['overdue_minutes'] ?? 0);
                        $dueStatus = (string) ($due['trang_thai_tong'] ?? '');
                        $dueDepartureTime = DateTime::createFromFormat('Y-m-d H:i:s', (string) $due['gio_cat_canh_goc']);
                      ?>
                      <article class="flight-landed-card flight-schedule-card flight-pending-card flight-overdue-card<?= $overdue >= 1 ? ' flight-overdue-row' : '' ?>"
                               data-overdue-since="<?= $escape($due['gio_du_kien']) ?>">
                        <div class="flight-landed-card-header">
                          <div class="flight-landed-identity">
                            <span class="flight-landed-icon" aria-hidden="true"><i class="bi bi-airplane-engines-fill"></i></span>
                            <div>
                              <strong class="flight-landed-code"><?= $escape($due['so_hieu']) ?></strong>
                              <span class="flight-landed-airline"><?= $escape($due['ten_hang']) ?></span>
                            </div>
                          </div>
                          <div class="flight-pending-badges">
                            <?php if ($dueStatus === 'Delay'): ?>
                              <span class="badge flight-status-badge" data-flight-status="Delay">Trễ chuyến</span>
                            <?php else: ?>
                              <span class="badge flight-status-badge" data-flight-status="Dang_Mo">Đang mở</span>
                            <?php endif; ?>
                            <span class="badge text-bg-danger<?= $overdue >= 1 ? '' : ' d-none' ?>"
                                  data-overdue-badge>Quá hạn +<?= $overdue ?> phút</span>
                          </div>
                        </div>
                        <div class="flight-landed-route" aria-label="Tuyến bay">
                          <div class="flight-landed-airport">
                            <span class="flight-landed-airport-code"><?= $escape($due['ma_di']) ?></span>
                            <span class="flight-landed-airport-name">Sân bay đi</span>
                          </div>
                          <div class="flight-landed-route-line" aria-hidden="true"><i class="bi bi-airplane"></i></div>
                          <div class="flight-landed-airport flight-landed-airport-arrival">
                            <span class="flight-landed-airport-code"><?= $escape($due['ma_den']) ?></span>
                            <span class="flight-landed-airport-name">Sân bay đến</span>
                          </div>
                        </div>
                        <div class="flight-pending-details">
                          <div class="flight-landed-time">
                            <span><i class="bi bi-clock me-1" aria-hidden="true"></i>Giờ cất cánh dự kiến</span>
                            <strong><?= $escape($dueDepartureTime ? $dueDepartureTime->format('d/m/Y · H:i') : $due['gio_du_kien']) ?></strong>
                          </div>
                          <div class="flight-pending-overdue-copy">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <span>Chưa xác nhận điều kiện bay sau <?= (int) $flightData['overdueLimit'] ?> phút sẽ tự chuyển sang trễ chuyến.</span>
                          </div>
                        </div>
                        <div class="flight-schedule-actions">
                          <?php if ($dueStatus === 'Delay'): ?>
                            <a class="btn btn-sm btn-outline-danger"
                               href="<?= $escape($dueUrl) ?>"
                               data-flight-popup-open
                               data-flight-popup-url="<?= $escape($dueUrl) ?>"
                               data-flight-popup-title="Kiểm tra yêu cầu an toàn — <?= $escape($due['so_hieu']) ?>">
                              <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Đã đủ điều kiện an toàn
                            </a>
                          <?php else: ?>
                            <a class="btn btn-sm btn-success"
                               href="<?= $escape($dueUrl) ?>"
                               data-flight-popup-open
                               data-flight-popup-url="<?= $escape($dueUrl) ?>"
                               data-flight-popup-title="Xác nhận điều kiện bay — <?= $escape($due['so_hieu']) ?>">
                              <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Đã đủ điều kiện bay
                            </a>
                          <?php endif; ?>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              <!--end::Các chuyến đã đến giờ bay-->
            <?php endif; ?>

            <?php if (!empty($flightData['delayedDueDepartures'])): ?>
              <!--begin::Các chuyến bay trễ chuyến-->
              <div class="card mb-3 border-warning flight-delay-section">
                <div class="card-header bg-warning-subtle d-flex align-items-center gap-2">
                  <h3 class="card-title h6 mb-0">
                    <i class="bi bi-clock-history me-1"></i>
                    Các chuyến bay trễ chuyến
                  </h3>
                  <span class="badge text-bg-warning"><?= count($flightData['delayedDueDepartures']) ?></span>
                  <span class="small text-secondary ms-auto">
                    Ngày <?= $escape($pendingDateLabel) ?> — đang trễ chuyến, cần xác nhận điều kiện bay hoặc delay tiếp.
                    Tổng thời gian trễ quá <?= (int) ($flightData['cancelDelayLimitHours'] ?? 5) ?> giờ sẽ tự động hủy chuyến.
                  </span>
                </div>
                <div class="card-body">
                  <div class="flight-landed-list flight-schedule-list">
                    <?php foreach ($flightData['delayedDueDepartures'] as $delayed): ?>
                      <?php
                        $delayedUrl = BASE_URL . '/views/pages/chuyen_bay/cat_canh.php?popup=1&id=' . (int) $delayed['id'];
                        $originalDeparture = DateTime::createFromFormat('Y-m-d H:i:s', (string) $delayed['gio_cat_canh_goc']);
                        $updatedDeparture = DateTime::createFromFormat(
                            'Y-m-d H:i:s',
                            (string) ($delayed['gio_cat_canh_du_kien'] ?? $delayed['gio_du_kien'])
                        );
                      ?>
                      <article class="flight-landed-card flight-schedule-card flight-delay-card">
                        <div class="flight-landed-card-header">
                          <div class="flight-landed-identity">
                            <span class="flight-landed-icon" aria-hidden="true"><i class="bi bi-airplane-engines-fill"></i></span>
                            <div>
                              <strong class="flight-landed-code"><?= $escape($delayed['so_hieu']) ?></strong>
                              <span class="flight-landed-airline"><?= $escape($delayed['ten_hang']) ?></span>
                            </div>
                          </div>
                          <div class="flight-pending-badges">
                            <span class="badge flight-status-badge" data-flight-status="Delay">Trễ chuyến</span>
                            <?php if (!empty($delayed['tong_tre_phut'])): ?>
                              <span class="badge bg-warning text-dark"><?= (int) $delayed['tong_tre_phut'] ?> phút trễ</span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="flight-landed-route" aria-label="Tuyến bay">
                          <div class="flight-landed-airport">
                            <span class="flight-landed-airport-code"><?= $escape($delayed['ma_di']) ?></span>
                            <span class="flight-landed-airport-name">Sân bay đi</span>
                          </div>
                          <div class="flight-landed-route-line" aria-hidden="true"><i class="bi bi-airplane"></i></div>
                          <div class="flight-landed-airport flight-landed-airport-arrival">
                            <span class="flight-landed-airport-code"><?= $escape($delayed['ma_den']) ?></span>
                            <span class="flight-landed-airport-name">Sân bay đến</span>
                          </div>
                        </div>
                        <div class="flight-landed-times">
                          <div class="flight-landed-time">
                            <span><i class="bi bi-clock-history me-1" aria-hidden="true"></i>Giờ cất cánh ban đầu</span>
                            <strong><?= $escape($originalDeparture ? $originalDeparture->format('d/m/Y · H:i') : $delayed['gio_du_kien']) ?></strong>
                          </div>
                          <div class="flight-landed-time flight-delay-time-updated">
                            <span><i class="bi bi-clock me-1" aria-hidden="true"></i>Giờ cất cánh sau delay</span>
                            <strong><?= $escape($updatedDeparture ? $updatedDeparture->format('d/m/Y · H:i') : $delayed['gio_du_kien']) ?></strong>
                          </div>
                        </div>
                        <div class="flight-delay-reason-row">
                          <span><i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>Lý do trễ</span>
                          <strong><?= $escape($delayed['ly_do_tre'] ?? 'Chưa cập nhật lý do') ?></strong>
                        </div>
                        <div class="flight-schedule-actions">
                          <a class="btn btn-sm btn-outline-danger"
                             href="<?= $escape($delayedUrl) ?>"
                             data-flight-popup-open
                             data-flight-popup-url="<?= $escape($delayedUrl) ?>"
                             data-flight-popup-title="Kiểm tra yêu cầu an toàn — <?= $escape($delayed['so_hieu']) ?>">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Đã đủ điều kiện an toàn
                          </a>
                        </div>
                      </article>
                    <?php endforeach; ?>
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
                  <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <h3 class="card-title mb-0">Danh sách chuyến bay</h3>
                    <div class="card-tools d-flex flex-wrap align-items-center gap-1 ms-auto">
                      <span class="badge rounded-pill text-bg-info me-1"><?= (int) $flightData['total'] ?> chuyến</span>
                      <a href="<?= $escape($historyUrl) ?>" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-journal-text me-1" aria-hidden="true"></i> Lịch sử bay
                      </a>
                      <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/them.php" class="btn btn-sm flight-add-button" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/them.php?popup=1" data-flight-popup-title="Thêm chuyến bay">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Thêm chuyến bay
                      </a>
                    </div>
                  </div>
                  <div class="card-body">
                    <form class="flight-filter-form mb-3" action="<?= $searchUrl ?>" method="get" data-flight-search-form>
                      <input type="hidden" name="page" value="chuyen_bay" />
                      <input type="hidden" name="pending_date" value="<?= $escape($flightData['pendingDate']) ?>" />
                      <div class="flight-filter-row">
                        <div class="flight-filter-field">
                          <label class="form-label" for="filter_origin_search">Sân bay đi</label>
                          <input class="form-control" id="filter_origin_search" type="text" list="filter_origin_options"
                               data-filter-query data-filter-select-id="filter_origin_id" data-empty-value="0"
                               placeholder="Sân bay đi" aria-label="Nhập mã hoặc tên sân bay đi"
                               autocomplete="off" value="<?= $escape($airportFilterText($filters['id_san_bay_di'])) ?>">
                          <datalist id="filter_origin_options">
                            <?php foreach ($flightData['airports'] as $airport): ?>
                              <?php $airportLabel = trim(($airport['ma_iata'] ?? '') . ' – ' . $airport['label']); ?>
                              <option value="<?= $escape($airportLabel) ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                              <option value="<?= $escape($airport['ma_iata'] ?? '') ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                              <option value="<?= $escape($airport['label']) ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                            <?php endforeach; ?>
                          </datalist>
                          <select class="form-select" id="filter_origin_id" name="id_san_bay_di" data-filter-select hidden aria-hidden="true" tabindex="-1">
                            <option value="0">Tất cả sân bay đi</option>
                            <?php foreach ($flightData['airports'] as $airport): ?>
                              <option value="<?= (int) $airport['id'] ?>" <?= $filters['id_san_bay_di'] === (int) $airport['id'] ? 'selected' : '' ?>>
                                <?= $escape(($airport['ma_iata'] ?? '') . ' – ' . $airport['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="flight-filter-field">
                          <label class="form-label" for="filter_destination_search">Sân bay đến</label>
                          <input class="form-control" id="filter_destination_search" type="text" list="filter_destination_options"
                               data-filter-query data-filter-select-id="filter_destination_id" data-empty-value="0"
                               placeholder="Sân bay đến" aria-label="Nhập mã hoặc tên sân bay đến"
                               autocomplete="off" value="<?= $escape($airportFilterText($filters['id_san_bay_den'])) ?>">
                          <datalist id="filter_destination_options">
                            <?php foreach ($flightData['airports'] as $airport): ?>
                              <?php $airportLabel = trim(($airport['ma_iata'] ?? '') . ' – ' . $airport['label']); ?>
                              <option value="<?= $escape($airportLabel) ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                              <option value="<?= $escape($airport['ma_iata'] ?? '') ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                              <option value="<?= $escape($airport['label']) ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                            <?php endforeach; ?>
                          </datalist>
                          <select class="form-select" id="filter_destination_id" name="id_san_bay_den" data-filter-select hidden aria-hidden="true" tabindex="-1">
                            <option value="0">Tất cả sân bay đến</option>
                            <?php foreach ($flightData['airports'] as $airport): ?>
                              <option value="<?= (int) $airport['id'] ?>" <?= $filters['id_san_bay_den'] === (int) $airport['id'] ? 'selected' : '' ?>>
                                <?= $escape(($airport['ma_iata'] ?? '') . ' – ' . $airport['label']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="flight-filter-field">
                          <label class="form-label" for="flight_date">Ngày bay</label>
                          <input type="date" class="form-control" id="flight_date" name="date" lang="vi" value="<?= $escape($filters['date']) ?>" />
                        </div>
                        <div class="flight-filter-field">
                          <label class="form-label" for="filter_status_search">Trạng thái</label>
                          <input class="form-control" id="filter_status_search" type="text" list="filter_status_options"
                               data-filter-query data-filter-select-id="filter_status_value" data-empty-value=""
                               placeholder="Tất cả trạng thái" aria-label="Nhập trạng thái chuyến bay"
                               autocomplete="off" value="<?= $escape($statusLabels[$filters['status']] ?? '') ?>">
                          <datalist id="filter_status_options">
                            <option value="Đang mở" data-filter-value="Dang_Mo"></option>
                            <option value="Cất cánh" data-filter-value="Cat_Canh"></option>
                            <option value="Trễ chuyến" data-filter-value="Delay"></option>
                            <option value="Đã hạ cánh" data-filter-value="Ha_Canh"></option>
                            <option value="Hạ cánh" data-filter-value="Ha_Canh"></option>
                            <option value="Hủy chuyến" data-filter-value="Huy"></option>
                          </datalist>
                          <select class="form-select" id="filter_status_value" name="status" data-filter-select hidden aria-hidden="true" tabindex="-1">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                              <option value="<?= $escape($statusValue) ?>" <?= $filters['status'] === $statusValue ? 'selected' : '' ?>><?= $escape($statusLabel) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="flight-filter-actions">
                          <button class="btn btn-outline-primary flight-filter-action" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Tìm kiếm</button>
                          <a class="btn btn-outline-primary flight-filter-action flight-filter-reset" href="<?= $escape($searchUrl) ?>?page=chuyen_bay">Xóa lọc</a>
                        </div>
                      </div>
                    </form>
                    <script>
                      (function () {
                        const form = document.querySelector('[data-flight-search-form]');
                        if (!form) {
                          return;
                        }

                        form.querySelectorAll('[data-filter-query]').forEach(function (input) {
                          const list = document.getElementById(input.getAttribute('list'));
                          const select = document.getElementById(input.dataset.filterSelectId);
                          if (!list || !select) {
                            return;
                          }

                          const syncFilter = function () {
                            const query = input.value.trim().toLocaleLowerCase();
                            const match = Array.from(list.options).find(function (option) {
                              return option.value.trim().toLocaleLowerCase() === query;
                            });
                            const selectedValue = match ? match.dataset.filterValue : input.dataset.emptyValue;
                            select.value = Array.from(select.options).some(function (option) {
                              return option.value === selectedValue;
                            }) ? selectedValue : input.dataset.emptyValue;
                            input.setCustomValidity(
                              query === '' || match ? '' : 'Vui lòng chọn một gợi ý trong danh sách.'
                            );
                          };

                          input.addEventListener('input', syncFilter);
                          input.addEventListener('change', syncFilter);
                          syncFilter();
                        });

                        form.addEventListener('submit', function (event) {
                          form.querySelectorAll('[data-filter-query]').forEach(function (input) {
                            input.reportValidity();
                            if (!input.checkValidity()) {
                              event.preventDefault();
                            }
                          });
                        });
                      })();
                    </script>

                    <?php if (!$flightData['flights']): ?>
                      <p class="text-center text-secondary py-4 mb-0">Không tìm thấy chuyến bay phù hợp.</p>
                    <?php else: ?>
                      <div class="flight-landed-list flight-schedule-list">
                        <?php foreach ($flightData['flights'] as $flight): ?>
                          <?php
                            $departureTs = strtotime((string) $flight['gio_du_kien']);
                            $arrivalTs = !empty($flight['gio_ha_canh_hien_thi'])
                                ? strtotime((string) $flight['gio_ha_canh_hien_thi'])
                                : false;
                            $arrivalDayOffset = 0;
                            if ($arrivalTs !== false && $departureTs !== false) {
                                $arrivalDayOffset = (int) round(
                                    (strtotime(date('Y-m-d', $arrivalTs)) - strtotime(date('Y-m-d', $departureTs))) / 86400
                                );
                            }
                            $flightStatus = (string) ($flight['trang_thai_tong'] ?? '');
                          ?>
                          <article class="flight-landed-card flight-schedule-card flight-index-card">
                            <div class="flight-landed-card-header">
                              <div class="flight-landed-identity">
                                <span class="flight-landed-icon" aria-hidden="true"><i class="bi bi-airplane-engines-fill"></i></span>
                                <div>
                                  <strong class="flight-landed-code"><?= $escape($flight['so_hieu']) ?></strong>
                                  <span class="flight-landed-airline"><?= $escape($flight['ten_hang'] ?? 'Chưa cập nhật hãng bay') ?></span>
                                </div>
                              </div>
                              <span class="badge flight-status-badge" data-flight-status="<?= $escape($flightStatus) ?>">
                                <?= $escape($statusLabels[$flightStatus] ?? $flightStatus) ?>
                              </span>
                            </div>

                            <div class="flight-landed-route" aria-label="Tuyến bay">
                              <div class="flight-landed-airport">
                                <span class="flight-landed-airport-code"><?= $escape($flight['san_bay_di'] ?? '—') ?></span>
                                <span class="flight-landed-airport-name">
                                  <?= $escape($flight['ten_ngan_di'] ?? 'Sân bay đi chưa cập nhật') ?>
                                  <?php if (!empty($flight['vi_tri_di'])): ?>
                                    <span class="flight-schedule-location"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= $escape($flight['vi_tri_di']) ?></span>
                                  <?php endif; ?>
                                </span>
                              </div>
                              <div class="flight-landed-route-line" aria-hidden="true"><i class="bi bi-airplane"></i></div>
                              <div class="flight-landed-airport flight-landed-airport-arrival">
                                <span class="flight-landed-airport-code"><?= $escape($flight['san_bay_den'] ?? '—') ?></span>
                                <span class="flight-landed-airport-name">
                                  <?= $escape($flight['ten_ngan_den'] ?? 'Sân bay đến chưa cập nhật') ?>
                                  <?php if (!empty($flight['vi_tri_den'])): ?>
                                    <span class="flight-schedule-location"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= $escape($flight['vi_tri_den']) ?></span>
                                  <?php endif; ?>
                                </span>
                              </div>
                            </div>

                            <div class="flight-landed-times">
                              <div class="flight-landed-time">
                                <span><i class="bi bi-airplane-engines me-1" aria-hidden="true"></i>Ngày và giờ cất cánh</span>
                                <strong>
                                  <?php if ($departureTs !== false): ?>
                                    <?= $escape(date('d/m/Y · H:i', $departureTs)) ?>
                                  <?php else: ?>
                                    Chưa cập nhật
                                  <?php endif; ?>
                                </strong>
                                <?php if ($flightStatus === 'Delay' && !empty($flight['gio_cat_canh_goc'])): ?>
                                  <small>Giờ ban đầu: <?= $escape(date('d/m/Y · H:i', strtotime((string) $flight['gio_cat_canh_goc']))) ?></small>
                                <?php endif; ?>
                              </div>
                              <div class="flight-landed-time flight-landed-time-arrival">
                                <span><i class="bi bi-flag me-1" aria-hidden="true"></i>Giờ hạ cánh dự kiến</span>
                                <strong>
                                  <?php if ($arrivalTs === false): ?>
                                    Chưa cập nhật
                                  <?php else: ?>
                                    <?= $escape(date('d/m/Y · H:i', $arrivalTs)) ?>
                                    <?php if ($arrivalDayOffset > 0): ?>
                                      <span class="badge text-bg-warning ms-1">+<?= (int) $arrivalDayOffset ?> ngày</span>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </strong>
                                <?php if ($flightStatus === 'Delay' && !empty($flight['gio_ha_canh_goc'])): ?>
                                  <small>Giờ ban đầu: <?= $escape(date('d/m/Y · H:i', strtotime((string) $flight['gio_ha_canh_goc']))) ?></small>
                                <?php endif; ?>
                              </div>
                            </div>

                            <div class="flight-schedule-actions" aria-label="Thao tác chuyến bay">
                              <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/chi_tiet.php?id=<?= (int) $flight['id'] ?>" class="btn btn-sm btn-outline-info flight-details-button" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/chi_tiet.php?popup=1&amp;id=<?= (int) $flight['id'] ?>" data-flight-popup-title="Chi tiết chuyến <?= $escape($flight['so_hieu']) ?>">
                                <i class="bi bi-eye me-1" aria-hidden="true"></i>Chi tiết
                              </a>
                              <a href="<?= $editUrl($flight['id']) ?>" class="btn btn-sm btn-outline-primary" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/sua.php?popup=1&amp;id=<?= (int) $flight['id'] ?>" data-flight-popup-title="Sửa chuyến <?= $escape($flight['so_hieu']) ?>">
                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Sửa
                              </a>
                              <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/sua.php?log=1&amp;id=<?= (int) $flight['id'] ?>" class="btn btn-sm btn-outline-info" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/sua.php?popup=1&amp;id=<?= (int) $flight['id'] ?>&amp;log=1" data-flight-popup-title="Nhật ký chuyến <?= $escape($flight['so_hieu']) ?>">
                                <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Nhật ký
                              </a>
                              <?php if ($flightStatus !== 'Huy'): ?>
                                <a href="<?= BASE_URL ?>/views/pages/chuyen_bay/huy.php?id=<?= (int) $flight['id'] ?>" class="btn btn-sm btn-outline-danger" data-flight-popup-open data-flight-popup-url="<?= BASE_URL ?>/views/pages/chuyen_bay/huy.php?popup=1&amp;id=<?= (int) $flight['id'] ?>" data-flight-popup-title="Hủy chuyến <?= $escape($flight['so_hieu']) ?>">
                                  <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Hủy
                                </a>
                              <?php endif; ?>
                            </div>
                          </article>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
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
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-flight-popup-close>Đóng</button>
                </div>
                <div class="flight-popup-body">
                  <iframe class="flight-popup-frame" title="Form chuyến bay" data-flight-popup-frame></iframe>
                </div>
              </div>
            </div>
            <script>
              (function () {
                const popup = document.getElementById('flight-popup');
                if (!popup) {
                  return;
                }
                const frame = popup.querySelector('[data-flight-popup-frame]');
                const title = popup.querySelector('.flight-popup-title');

                const closePopup = function () {
                  popup.hidden = true;
                  frame.removeAttribute('src');
                  title.textContent = 'Thêm chuyến bay';
                  document.body.classList.remove('flight-popup-open');
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
                  } else if (event.data.type === 'flight-popup-saved' || event.data.type === 'flight-add-saved') {
                    // Tải lại danh sách để nhận flash thành công từ session.
                    window.location.reload();
                  } else if (event.data.type === 'flight-departure-done') {
                    // Đã xác nhận cất cánh / trễ tiếp: tải lại để cập nhật trạng thái và popup kế tiếp.
                    window.location.reload();
                  }
                });

                // Liên kết cũ (?edit=ID / ?cancel=ID): mở popup ngay tại trang này.
                const baseUrl = '<?= BASE_URL ?>';
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

                
              })();
            </script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
