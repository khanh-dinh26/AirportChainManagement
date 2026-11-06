<?php
/**
 * @var array|null $selectedAirport
 * @var array|null $flights
 * @var array $filters
 * @var callable $url
 * @var callable $e
 * @var array $data
 */

if (!$selectedAirport || !$flights) {
    return;
}

$airport = $selectedAirport;
$f = $flights;
$filterParams = [
    'san_bay' => $airport['ma_iata'],
    'khu' => 'chuyen_bay',
    'ngay' => $filters['ngay'],
    'hang' => $filters['hang'],
    'so_hieu' => $filters['so_hieu'],
    'huong' => $filters['huong'],
];
$filterUrl = static function (array $overrides = []) use ($url, $filterParams) {
    $params = array_merge($filterParams, $overrides);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === 0 || $value === '0') {
            unset($params[$key]);
        }
    }

    return $url($params);
};
$pageLink = static function ($page) use ($filterUrl) {
    return $filterUrl(['trang' => (int) $page]);
};
$allCount = $f['departures_count'] + $f['arrivals_count'];
$start = max(1, $f['page'] - 2);
$end = min($f['pages'], $start + 4);
$start = max(1, $end - 4);
?>
<div class="card ht-panel">
  <div class="card-header">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
      <div>
        <h3><i class="bi bi-airplane-engines me-1"></i> Chuyến bay tại <?= $e($airport['ten_ngan']) ?> (<?= $e($airport['ma_iata']) ?>)</h3>
        <p>Tra cứu chuyến đi và chuyến đến theo ngày, hãng hàng không và số hiệu.</p>
      </div>
      <span class="badge text-bg-primary"><?= (int) $f['total'] ?> chuyến phù hợp</span>
    </div>
  </div>
  <div class="card-body pb-2">
    <form method="get" action="<?= $e(BASE_URL . '/index.php') ?>" class="row g-2 align-items-end ht-filter">
      <input type="hidden" name="page" value="ha_tang" />
      <input type="hidden" name="san_bay" value="<?= $e($airport['ma_iata']) ?>" />
      <input type="hidden" name="khu" value="chuyen_bay" />

      <div class="col-6 col-lg-2">
        <label class="form-label" for="ht-ngay">Ngày</label>
        <input type="date" class="form-control form-control-sm" id="ht-ngay" name="ngay" value="<?= $e($filters['ngay']) ?>" />
      </div>

      <div class="col-6 col-lg-3">
        <label class="form-label" for="ht-hang">Hãng hàng không</label>
        <select class="form-select form-select-sm" id="ht-hang" name="hang">
          <option value="0">Tất cả hãng</option>
          <?php foreach ($airlines as $airline): ?>
            <option value="<?= (int) $airline['id'] ?>" <?= (int) $filters['hang'] === (int) $airline['id'] ? 'selected' : '' ?>>
              <?= $e($airline['ten_hang']) ?> (<?= $e($airline['ma_iata']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label" for="ht-so-hieu">Số hiệu</label>
        <input type="text" class="form-control form-control-sm" id="ht-so-hieu" name="so_hieu" value="<?= $e($filters['so_hieu']) ?>" placeholder="VD: VN210" />
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label" for="ht-huong">Chiều bay</label>
        <select class="form-select form-select-sm" id="ht-huong" name="huong">
          <option value="tat_ca" <?= $filters['huong'] === 'tat_ca' ? 'selected' : '' ?>>Tất cả</option>
          <option value="di" <?= $filters['huong'] === 'di' ? 'selected' : '' ?>>Chuyến đi</option>
          <option value="den" <?= $filters['huong'] === 'den' ? 'selected' : '' ?>>Chuyến đến</option>
        </select>
      </div>

      <div class="col-12 col-lg-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
          <i class="bi bi-funnel me-1"></i> Lọc chuyến bay
        </button>
        <a href="<?= $e($filterUrl(['ngay' => '', 'hang' => 0, 'so_hieu' => '', 'huong' => 'tat_ca', 'trang' => ''])) ?>" class="btn btn-outline-secondary btn-sm">
          Đặt lại
        </a>
      </div>
    </form>

    <div class="d-flex flex-wrap gap-2 mt-3">
      <a href="<?= $e($filterUrl(['huong' => 'tat_ca', 'trang' => ''])) ?>" class="ht-chip <?= $filters['huong'] === 'tat_ca' ? 'is-active' : '' ?>">
        <i class="bi bi-grid"></i> Tất cả <span><?= (int) $allCount ?></span>
      </a>
      <a href="<?= $e($filterUrl(['huong' => 'di', 'trang' => ''])) ?>" class="ht-chip <?= $filters['huong'] === 'di' ? 'is-active' : '' ?>">
        <i class="bi bi-airplane"></i> Chuyến đi <span><?= (int) $f['departures_count'] ?></span>
      </a>
      <a href="<?= $e($filterUrl(['huong' => 'den', 'trang' => ''])) ?>" class="ht-chip <?= $filters['huong'] === 'den' ? 'is-active' : '' ?>">
        <i class="bi bi-airplane-engines"></i> Chuyến đến <span><?= (int) $f['arrivals_count'] ?></span>
      </a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table ht-table align-middle">
      <thead>
        <tr>
          <th>Chuyến bay</th>
          <th>Hãng</th>
          <th>Hành trình</th>
          <th>Giờ dự kiến</th>
          <th>Trạng thái</th>
          <th>Vị trí khai thác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$f['items']): ?>
          <tr>
            <td colspan="6">
              <div class="ht-empty border-0">
                <i class="bi bi-radar"></i>
                <h3>Không có chuyến bay phù hợp</h3>
                <p>Thử đổi ngày, hãng hàng không, số hiệu hoặc bỏ bộ lọc để xem toàn bộ chuyến bay tại sân bay này.</p>
                <a href="<?= $e($filterUrl(['ngay' => '', 'hang' => 0, 'so_hieu' => '', 'huong' => 'tat_ca', 'trang' => ''])) ?>" class="btn btn-outline-primary btn-sm mt-2">
                  Xóa bộ lọc
                </a>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($f['items'] as $flight): ?>
            <tr>
              <td>
                <div class="fw-bold"><?= $e($flight['so_hieu']) ?></div>
                <span class="badge text-bg-<?= $flight['huong'] === 'di' ? 'info' : 'primary' ?> mt-1">
                  <?= $flight['huong'] === 'di' ? 'Đi' : 'Đến' ?>
                </span>
              </td>
              <td>
                <div class="fw-semibold"><?= $e($flight['ten_hang']) ?></div>
                <span class="ht-soft-label"><?= $e($flight['ma_hang']) ?></span>
              </td>
              <td>
                <div class="ht-flight-route">
                  <span class="<?= $flight['huong'] === 'di' ? 'text-primary' : '' ?>"><?= $e($flight['ma_di']) ?></span>
                  <i class="bi bi-arrow-right"></i>
                  <span class="<?= $flight['huong'] === 'den' ? 'text-primary' : '' ?>"><?= $e($flight['ma_den']) ?></span>
                </div>
                <span class="ht-flight-sub"><?= $e($flight['ten_di']) ?> → <?= $e($flight['ten_den']) ?></span>
              </td>
              <td>
                <div class="fw-semibold"><?= $e($formatTime($flight['gio_du_kien'])) ?></div>
                <span class="ht-soft-label"><?= $e($formatDate($flight['gio_du_kien'])) ?></span>
                <?php if (!empty($flight['gio_thuc_te'])): ?>
                  <span class="ht-soft-label">Thực tế: <?= $e($formatDateTime($flight['gio_thuc_te'])) ?></span>
                <?php endif; ?>
              </td>
              <td><?= $statusBadge($flight['trang_thai_tong']) ?></td>
              <td>
                <?php if ($flight['huong'] === 'di'): ?>
                  <span class="ht-soft-label">Cửa ra: <strong><?= $e($flight['so_cua'] ?: '—') ?></strong></span>
                  <span class="ht-soft-label">Check-in: <strong><?= $e($flight['so_quay'] ?: '—') ?></strong></span>
                <?php else: ?>
                  <span class="ht-soft-label">Băng chuyền: <strong><?= $e($flight['so_bang_chuyen'] ?: '—') ?></strong></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card-footer bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <span class="text-secondary small">
        Hiển thị <?= count($f['items']) ?> / <?= (int) $f['total'] ?> chuyến
        · Trang <?= (int) $f['page'] ?> / <?= (int) $f['pages'] ?>
      </span>
      <?php if ($f['pages'] > 1): ?>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $f['page'] <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $f['page'] <= 1 ? '#' : $e($pageLink($f['page'] - 1)) ?>">Trước</a>
          </li>
          <?php for ($page = $start; $page <= $end; $page++): ?>
            <li class="page-item <?= $page === (int) $f['page'] ? 'active' : '' ?>">
              <a class="page-link" href="<?= $e($pageLink($page)) ?>"><?= $page ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $f['page'] >= $f['pages'] ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $f['page'] >= $f['pages'] ? '#' : $e($pageLink($f['page'] + 1)) ?>">Sau</a>
          </li>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
