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
$listUrl = BASE_URL . '/index.php?page=chuyen_bay';
$filterUrl = static function (array $params = []) use ($selfUrl, $data) {
    return $selfUrl . '?' . http_build_query(array_merge($data['filters'], $params));
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
              <div class="card-header">
                <h3 class="card-title">Chuyến bay đã hạ cánh</h3>
              </div>
              <div class="card-body">
                <form class="row g-2 mb-3" method="get" action="<?= $e($selfUrl) ?>">
                  <div class="col-md-5">
                    <label class="form-label" for="history_keyword">Tìm kiếm</label>
                    <input class="form-control" id="history_keyword" name="keyword" placeholder="Mã chuyến, hãng, sân bay đi/đến"
                           value="<?= $e($data['filters']['keyword']) ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="history_date">Ngày hạ cánh</label>
                    <input type="date" class="form-control" id="history_date" name="date" value="<?= $e($data['filters']['date']) ?>">
                  </div>
                  <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Tìm</button>
                    <a class="btn btn-outline-secondary" href="<?= $e($selfUrl) ?>">Xóa lọc</a>
                  </div>
                </form>

                <?php if (!$data['rows']): ?>
                  <p class="text-secondary mb-0">
                    Chưa có chuyến bay nào được lưu vào lịch sử<?= $data['filters']['keyword'] !== '' || $data['filters']['date'] !== '' ? ' với bộ lọc hiện tại' : '' ?>.
                  </p>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover align-middle mb-0">
                      <thead>
                        <tr>
                          <th>Mã chuyến</th>
                          <th>Hãng hàng không</th>
                          <th>Bay từ → đến</th>
                          <th class="text-nowrap">Cất cánh</th>
                          <th class="text-nowrap">Hạ cánh</th>
                          <th class="text-end">Tổng giờ bay</th>
                          <th class="text-end">Hành khách</th>
                          <th class="text-end">Hàng hóa (tấn)</th>
                          <th class="text-nowrap">Lưu lúc</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($data['rows'] as $row): ?>
                          <tr>
                            <td class="fw-semibold"><?= $e($row['so_hieu']) ?></td>
                            <td><?= $e($row['ten_hang'] ?? '—') ?></td>
                            <td>
                              <span class="fw-semibold"><?= $e($row['ma_di'] ?? '') ?></span>
                              <i class="bi bi-arrow-right mx-1 text-secondary"></i>
                              <span class="fw-semibold"><?= $e($row['ma_den'] ?? '') ?></span>
                              <small class="d-block text-secondary">
                                <?= $e($row['ten_san_bay_di'] ?? '') ?> → <?= $e($row['ten_san_bay_den'] ?? '') ?>
                              </small>
                            </td>
                            <td class="text-nowrap"><?= $e($dateTime($row['gio_cat_canh'])) ?></td>
                            <td class="text-nowrap"><?= $e($dateTime($row['gio_ha_canh'])) ?></td>
                            <td class="text-end text-nowrap"><?= $number($row['tong_gio_bay'], 2) ?> giờ</td>
                            <td class="text-end"><?= $number($row['so_hanh_khach']) ?></td>
                            <td class="text-end"><?= $number($row['khoi_luong_hang_kg'] / 1000, 2) ?></td>
                            <td class="text-nowrap"><?= $e($dateTime($row['thoi_diem_luu'])) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
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
