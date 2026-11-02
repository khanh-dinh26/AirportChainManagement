<?php
/**
 * ============================================================
 * TRANG: LỊCH SỬ BAY  —  views/pages/chuyen_bay/lich_su_bay.php
 * Lưu lại thông tin các chuyến đã hạ cánh (bảng lich_su_chuyen_bay):
 * hãng hàng không, số hiệu, tuyến đi/đến, giờ cất cánh, giờ hạ cánh,
 * tổng giờ bay, số hành khách, khối lượng hàng hóa (kg).
 * ============================================================
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/ChuyenBayController.php';

$controller = new ChuyenBayController($conn);
$data = $controller->handleFlightHistory();

$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$number = static function ($value, $decimals = 0) {
    return number_format((float) $value, $decimals, ',', '.');
};
$dateTime = static function ($value) {
    if ($value === null || $value === '') {
        return '—';
    }
    $parsed = DateTime::createFromFormat('Y-m-d H:i:s', (string) $value);

    return $parsed ? $parsed->format('d/m/Y H:i') : (string) $value;
};

$selfUrl = BASE_URL . '/views/pages/chuyen_bay/lich_su_bay.php';
$listUrl = BASE_URL . '/index.php?' . http_build_query(array_filter([
    'page' => 'chuyen_bay',
    'keyword' => $data['filters']['keyword'],
    'id_san_bay_di' => $data['filters']['id_san_bay_di'],
    'id_san_bay_den' => $data['filters']['id_san_bay_den'],
    'date' => $data['filters']['date'],
], static function ($value) {
    return $value !== '' && $value !== 0;
}));
$filterUrl = static function (array $params = []) use ($selfUrl, $data) {
    return $selfUrl . '?' . http_build_query(array_merge($data['filters'], $params));
};
$airportFilterText = static function ($airportId) use ($data) {
    foreach ($data['airports'] as $airport) {
        if ((int) $airport['id'] === (int) $airportId) {
            return trim(($airport['ma_iata'] ?? '') . ' – ' . ($airport['label'] ?? ''));
        }
    }

    return '';
};

$pageTitle = 'Lịch sử bay';
$pageHeader = 'Lịch sử bay';
$navActive = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => '/AirportChainManagement/index.php'],
    ['label' => 'Chuyến bay', 'url' => '/AirportChainManagement/index.php?page=chuyen_bay'],
    ['label' => 'Lịch sử bay', 'url' => null],
];

ob_start();
?>
            <link rel="stylesheet" href="<?= $e(BASE_URL) ?>/views/pages/chuyen_bay/chuyen_bay-ui.css" />
            <div class="mb-3 d-flex flex-wrap gap-2">
              <a class="btn btn-sm btn-primary" href="<?= $e($listUrl) ?>">
                <i class="bi bi-arrow-left me-1"></i> Quay lại trang chuyến bay
              </a>
            </div>

            <!--begin::Số liệu tổng hợp-->
            <div class="row">
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-primary">
                  <div class="inner">
                    <h3><?= $number($data['summary']['tong_chuyen']) ?></h3>
                    <p>Chuyến đã hạ cánh</p>
                  </div>
                  <i class="small-box-icon bi bi-airplane-engines"></i>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-success">
                  <div class="inner">
                    <h3><?= $number($data['summary']['tong_hanh_khach']) ?></h3>
                    <p>Tổng hành khách</p>
                  </div>
                  <i class="small-box-icon bi bi-people"></i>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-info">
                  <div class="inner">
                    <h3><?= $number($data['summary']['tong_hang_kg'] / 1000, 2) ?></h3>
                    <p>Tổng hàng hóa (tấn)</p>
                  </div>
                  <i class="small-box-icon bi bi-box-seam"></i>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-warning">
                  <div class="inner">
                    <h3><?= $number($data['summary']['tong_gio_bay'], 2) ?></h3>
                    <p>Tổng giờ bay</p>
                  </div>
                  <i class="small-box-icon bi bi-clock-history"></i>
                </div>
              </div>
            </div>
            <!--end::Số liệu tổng hợp-->

            <div class="card">
              <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h3 class="card-title mb-0"><i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>Chuyến bay đã hạ cánh</h3>
                <span class="badge rounded-pill text-bg-success ms-auto"><?= $number($data['total']) ?> chuyến</span>
              </div>
              <div class="card-body">
                <form class="flight-filter-form flight-history-filter mb-3" method="get" action="<?= $e($selfUrl) ?>">
                  <div class="flight-history-filter-field">
                    <label class="form-label" for="history_keyword">Tìm kiếm</label>
                    <input class="form-control" id="history_keyword" name="keyword" placeholder="Mã chuyến, hãng, sân bay đi/đến"
                           value="<?= $e($data['filters']['keyword']) ?>">
                  </div>
                  <div class="flight-history-filter-field">
                    <label class="form-label" for="history_origin_search">Sân bay đi</label>
                    <input class="form-control" id="history_origin_search" type="text" list="history_origin_options"
                           data-history-filter-query data-filter-select-id="history_origin_id" data-empty-value="0"
                           placeholder="Tất cả sân bay đi" aria-label="Nhập mã hoặc tên sân bay đi"
                           autocomplete="off" value="<?= $e($airportFilterText($data['filters']['id_san_bay_di'])) ?>">
                    <datalist id="history_origin_options">
                      <?php foreach ($data['airports'] as $airport): ?>
                        <?php $airportLabel = trim(($airport['ma_iata'] ?? '') . ' – ' . ($airport['label'] ?? '')); ?>
                        <option value="<?= $e($airportLabel) ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                        <option value="<?= $e($airport['ma_iata'] ?? '') ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                        <option value="<?= $e($airport['label'] ?? '') ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                      <?php endforeach; ?>
                    </datalist>
                    <select class="form-select" id="history_origin_id" name="id_san_bay_di" data-history-filter-select hidden aria-hidden="true" tabindex="-1">
                      <option value="0">Tất cả sân bay đi</option>
                      <?php foreach ($data['airports'] as $airport): ?>
                        <option value="<?= (int) $airport['id'] ?>" <?= $data['filters']['id_san_bay_di'] === (int) $airport['id'] ? 'selected' : '' ?>>
                          <?= $e(($airport['ma_iata'] ?? '') . ' – ' . ($airport['label'] ?? '')) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="flight-history-filter-field">
                    <label class="form-label" for="history_destination_search">Sân bay đến</label>
                    <input class="form-control" id="history_destination_search" type="text" list="history_destination_options"
                           data-history-filter-query data-filter-select-id="history_destination_id" data-empty-value="0"
                           placeholder="Tất cả sân bay đến" aria-label="Nhập mã hoặc tên sân bay đến"
                           autocomplete="off" value="<?= $e($airportFilterText($data['filters']['id_san_bay_den'])) ?>">
                    <datalist id="history_destination_options">
                      <?php foreach ($data['airports'] as $airport): ?>
                        <?php $airportLabel = trim(($airport['ma_iata'] ?? '') . ' – ' . ($airport['label'] ?? '')); ?>
                        <option value="<?= $e($airportLabel) ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                        <option value="<?= $e($airport['ma_iata'] ?? '') ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                        <option value="<?= $e($airport['label'] ?? '') ?>" data-filter-value="<?= (int) $airport['id'] ?>"></option>
                      <?php endforeach; ?>
                    </datalist>
                    <select class="form-select" id="history_destination_id" name="id_san_bay_den" data-history-filter-select hidden aria-hidden="true" tabindex="-1">
                      <option value="0">Tất cả sân bay đến</option>
                      <?php foreach ($data['airports'] as $airport): ?>
                        <option value="<?= (int) $airport['id'] ?>" <?= $data['filters']['id_san_bay_den'] === (int) $airport['id'] ? 'selected' : '' ?>>
                          <?= $e(($airport['ma_iata'] ?? '') . ' – ' . ($airport['label'] ?? '')) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="flight-history-filter-field">
                    <label class="form-label" for="history_date">Ngày hạ cánh</label>
                    <input type="date" class="form-control" id="history_date" name="date" lang="vi" value="<?= $e($data['filters']['date']) ?>">
                  </div>
                  <div class="d-flex align-items-end gap-2 flight-filter-actions">
                    <button class="btn btn-outline-primary flight-filter-action" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Tìm kiếm</button>
                    <a class="btn btn-outline-primary flight-filter-action flight-filter-reset" href="<?= $e($selfUrl) ?>">Xóa lọc</a>
                  </div>
                </form>
                <script>
                  (function () {
                    const form = document.querySelector('.flight-history-filter');
                    if (!form) {
                      return;
                    }

                    form.querySelectorAll('[data-history-filter-query]').forEach(function (input) {
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
                      form.querySelectorAll('[data-history-filter-query]').forEach(function (input) {
                        input.reportValidity();
                        if (!input.checkValidity()) {
                          event.preventDefault();
                        }
                      });
                    });
                  })();
                </script>

                <?php if (!$data['rows']): ?>
                  <p class="text-secondary mb-0">
                    Chưa có chuyến bay nào được lưu vào lịch sử<?= $data['filters']['keyword'] !== '' || $data['filters']['id_san_bay_di'] > 0 || $data['filters']['id_san_bay_den'] > 0 || $data['filters']['date'] !== '' ? ' với bộ lọc hiện tại' : '' ?>.
                  </p>
                <?php else: ?>
                  <div class="flight-landed-list">
                    <?php foreach ($data['rows'] as $row): ?>
                      <?php
                        $departureTime = $row['gio_cat_canh'] ? strtotime((string) $row['gio_cat_canh']) : false;
                        $arrivalTime = $row['gio_ha_canh'] ? strtotime((string) $row['gio_ha_canh']) : false;
                      ?>
                      <article class="flight-landed-card flight-history-card">
                        <div class="flight-landed-card-header">
                          <div class="flight-landed-identity">
                            <span class="flight-landed-icon" aria-hidden="true"><i class="bi bi-airplane-engines-fill"></i></span>
                            <div>
                              <strong class="flight-landed-code"><?= $e($row['so_hieu']) ?></strong>
                              <span class="flight-landed-airline"><?= $e($row['ten_hang'] ?? 'Chưa cập nhật hãng bay') ?></span>
                            </div>
                          </div>
                          <span class="badge flight-status-badge" data-flight-status="Ha_Canh">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Đã hạ cánh
                          </span>
                        </div>

                        <div class="flight-landed-route" aria-label="Tuyến bay">
                          <div class="flight-landed-airport">
                            <span class="flight-landed-airport-code"><?= $e($row['ma_di'] ?? '—') ?></span>
                            <span class="flight-landed-airport-name"><?= $e($row['ten_san_bay_di'] ?? 'Sân bay đi chưa cập nhật') ?></span>
                          </div>
                          <div class="flight-landed-route-line" aria-hidden="true"><i class="bi bi-airplane"></i></div>
                          <div class="flight-landed-airport flight-landed-airport-arrival">
                            <span class="flight-landed-airport-code"><?= $e($row['ma_den'] ?? '—') ?></span>
                            <span class="flight-landed-airport-name"><?= $e($row['ten_san_bay_den'] ?? 'Sân bay đến chưa cập nhật') ?></span>
                          </div>
                        </div>

                        <div class="flight-landed-times">
                          <div class="flight-landed-time">
                            <span><i class="bi bi-airplane-engines me-1" aria-hidden="true"></i>Giờ cất cánh</span>
                            <strong><?= $e($dateTime($row['gio_cat_canh'])) ?></strong>
                          </div>
                          <div class="flight-landed-time flight-landed-time-arrival">
                            <span><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Giờ hạ cánh</span>
                            <strong><?= $e($dateTime($row['gio_ha_canh'])) ?></strong>
                          </div>
                        </div>

                        <div class="flight-landed-metrics">
                          <div class="flight-landed-metric">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <span>Thời lượng bay</span>
                            <strong><?= $number($row['tong_gio_bay'], 2) ?> giờ</strong>
                          </div>
                          <div class="flight-landed-metric">
                            <i class="bi bi-people-fill" aria-hidden="true"></i>
                            <span>Hành khách</span>
                            <strong><?= $number($row['so_hanh_khach']) ?> khách</strong>
                          </div>
                          <div class="flight-landed-metric">
                            <i class="bi bi-box-seam-fill" aria-hidden="true"></i>
                            <span>Hàng hóa</span>
                            <strong><?= $number($row['khoi_luong_hang_kg'] / 1000, 2) ?> tấn</strong>
                          </div>
                        </div>

                        <div class="flight-landed-card-footer">
                          <span><i class="bi bi-archive me-1" aria-hidden="true"></i>Lưu lịch sử: <?= $e($dateTime($row['thoi_diem_luu'])) ?></span>
                          <?php if ($departureTime !== false && $arrivalTime !== false && date('Y-m-d', $departureTime) !== date('Y-m-d', $arrivalTime)): ?>
                            <span class="flight-landed-next-day"><i class="bi bi-moon-stars me-1" aria-hidden="true"></i>Hạ cánh khác ngày khởi hành</span>
                          <?php endif; ?>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <?php if ($data['total'] > 0): ?>
                <div class="card-footer clearfix">
                  <?php
                    // Phân trang giống danh sách chuyến bay: tối đa 5 số trang, hai bên là dấu "…" để nhập số trang.
                    $totalPages = max(1, (int) $data['totalPages']);
                    $currentPage = min(max(1, (int) $data['page']), $totalPages);
                    $windowSize = min(5, $totalPages);
                    $windowStart = $currentPage - (int) floor($windowSize / 2);
                    $windowStart = max(1, min($windowStart, $totalPages - $windowSize + 1));
                    $windowEnd = $windowStart + $windowSize - 1;
                    $pageNumbers = range($windowStart, $windowEnd);
                    $pageJumpTemplate = $filterUrl(['p' => '__PAGE__']);
                  ?>
                  <ul class="pagination pagination-sm m-0 float-end flight-pagination">
                    <?php if ($currentPage > 1): ?>
                      <li class="page-item"><a class="page-link" href="<?= $e($filterUrl(['p' => $currentPage - 1])) ?>">&laquo;</a></li>
                    <?php else: ?>
                      <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                    <?php endif; ?>
                    <?php if ($windowStart > 1): ?>
                      <li class="page-item">
                        <a class="page-link" href="<?= $e($filterUrl(['p' => 1])) ?>"
                           data-page-jump
                           data-page-url="<?= $e($pageJumpTemplate) ?>"
                           data-total-pages="<?= (int) $totalPages ?>"
                           title="Nhập số trang muốn đến">…</a>
                      </li>
                    <?php endif; ?>
                    <?php foreach ($pageNumbers as $pageNumber): ?>
                      <li class="page-item <?= $pageNumber === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $e($filterUrl(['p' => $pageNumber])) ?>"><?= $pageNumber ?></a>
                      </li>
                    <?php endforeach; ?>
                    <?php if ($windowEnd < $totalPages): ?>
                      <li class="page-item">
                        <a class="page-link" href="<?= $e($filterUrl(['p' => $totalPages])) ?>"
                           data-page-jump
                           data-page-url="<?= $e($pageJumpTemplate) ?>"
                           data-total-pages="<?= (int) $totalPages ?>"
                           title="Nhập số trang muốn đến">…</a>
                      </li>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPages): ?>
                      <li class="page-item"><a class="page-link" href="<?= $e($filterUrl(['p' => $currentPage + 1])) ?>">&raquo;</a></li>
                    <?php else: ?>
                      <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                    <?php endif; ?>
                  </ul>
                  <span class="small text-secondary">
                    Trang <?= (int) $currentPage ?>/<?= (int) $totalPages ?> — <?= $number($data['total']) ?> chuyến
                  </span>
                </div>
              <?php endif; ?>
            </div>

            <script>
              // Phân trang: bấm dấu "…" để nhập số trang muốn đến (Enter để đi, Esc để hủy).
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
