<?php
$tabMeta = [
    'san_bay' => [
        'label' => 'Sân bay',
        'icon' => 'bi-building',
        'desc' => 'Danh mục sân bay, mã nhận diện, phân cấp và trạng thái khai thác.',
        'add' => 'Thêm sân bay',
    ],
    'nha_ga' => [
        'label' => 'Nhà ga',
        'icon' => 'bi-door-open',
        'desc' => 'Nhấn vào một nhà ga để chọn và xem cửa ra, quầy check-in, băng chuyền.',
        'add' => 'Thêm nhà ga',
    ],
    'cua_ra' => [
        'label' => 'Cửa ra máy bay',
        'icon' => 'bi-signpost',
        'desc' => 'Cửa ra thuộc nhà ga đang chọn.',
        'add' => 'Thêm cửa ra',
    ],
    'quay' => [
        'label' => 'Quầy check-in',
        'icon' => 'bi-person-check',
        'desc' => 'Quầy check-in thuộc nhà ga đang chọn.',
        'add' => 'Thêm quầy',
    ],
    'bang_chuyen' => [
        'label' => 'Băng chuyền',
        'icon' => 'bi-luggage',
        'desc' => 'Băng chuyền hành lý thuộc nhà ga đang chọn.',
        'add' => 'Thêm băng chuyền',
    ],
];

$returnTab = $tab;
$returnSanBay = $selectedAirport ? $selectedAirport['ma_iata'] : '';
$returnNhaGa = $selectedTerminal ? (int) $selectedTerminal['id'] : 0;
$formMode = $management['formMode'];
$formRecord = $management['editRecord'];
$formType = $management['formType'];

$adminUrl = static function (array $overrides = []) use ($url, $returnTab, $returnSanBay, $returnNhaGa) {
    $params = ['quan_tri' => '1', 'tab' => $returnTab];
    if ($returnSanBay !== '') {
        $params['san_bay'] = $returnSanBay;
    }
    if ($returnNhaGa > 0) {
        $params['nha_ga'] = $returnNhaGa;
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return $url($params);
};
$listUrl = $adminUrl(['tab' => $returnTab]);
$currentMeta = $tabMeta[$tab] ?? $tabMeta['san_bay'];

$canUseTab = true;
if ($tab === 'nha_ga' && !$selectedAirport) {
    $canUseTab = false;
}
if (in_array($tab, ['cua_ra', 'quay', 'bang_chuyen'], true) && !$selectedTerminal) {
    $canUseTab = false;
}

$tabParams = ['quan_tri' => '1', 'tab' => $tab];
if ($selectedAirport) {
    $tabParams['san_bay'] = $selectedAirport['ma_iata'];
}
if ($selectedTerminal) {
    $tabParams['nha_ga'] = (int) $selectedTerminal['id'];
}
$addUrl = $adminUrl(array_merge($tabParams, ['them' => 1]));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div class="d-flex flex-wrap align-items-center gap-2">
    <span class="badge text-bg-light border">
      <i class="bi bi-geo-alt me-1"></i>
      Sân bay:
      <strong><?= $selectedAirport ? $e($selectedAirport['ma_iata'] . ' — ' . $selectedAirport['ten_ngan']) : 'Chưa chọn' ?></strong>
    </span>
    <?php if ($selectedTerminal): ?>
      <span class="badge text-bg-light border">
        <i class="bi bi-door-open me-1"></i>
        Nhà ga: <strong><?= $e($selectedTerminal['ma_nha_ga'] . ' — ' . $selectedTerminal['ten_nha_ga']) ?></strong>
      </span>
    <?php endif; ?>
  </div>
</div>

<div class="ht-admin-tabs" id="ht-admin-tabs">
  <?php foreach ($tabMeta as $key => $meta): ?>
    <?php $isActive = $tab === $key; ?>
    <a href="<?= $e($adminUrl(['tab' => $key]) . '#ht-admin-tabs') ?>" class="<?= $isActive ? 'is-active' : '' ?>">
      <i class="bi <?= $e($meta['icon']) ?>"></i>
      <?= $e($meta['label']) ?>
      <?php if ($key === 'san_bay'): ?>
        <span class="badge text-bg-secondary"><?= count($data['airports']) ?></span>
      <?php elseif ($key === 'nha_ga'): ?>
        <span class="badge text-bg-secondary"><?= count($terminals) ?></span>
      <?php elseif ($key === 'cua_ra'): ?>
        <span class="badge text-bg-secondary"><?= count($data['gates']) ?></span>
      <?php elseif ($key === 'quay'): ?>
        <span class="badge text-bg-secondary"><?= count($data['checkins']) ?></span>
      <?php elseif ($key === 'bang_chuyen'): ?>
        <span class="badge text-bg-secondary"><?= count($data['belts']) ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card ht-panel">
  <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-2">
    <div>
      <h3><i class="bi <?= $e($currentMeta['icon']) ?> me-1"></i> <?= $e($currentMeta['label']) ?></h3>
      <p><?= $e($currentMeta['desc']) ?></p>
    </div>
    <?php if ($canUseTab): ?>
      <?php if ($formMode): ?>
        <a href="<?= $e($listUrl) ?>" class="btn btn-outline-secondary btn-sm ms-auto">
          <i class="bi bi-x-lg me-1"></i> Đóng biểu mẫu
        </a>
      <?php else: ?>
        <a href="<?= $e($addUrl) ?>" class="btn btn-primary btn-sm ms-auto">
          <i class="bi bi-plus-lg me-1"></i> <?= $e($currentMeta['add']) ?>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if (!$canUseTab): ?>
    <div class="card-body">
      <div class="ht-empty border-0">
        <i class="bi bi-signpost-split"></i>
        <?php if ($tab === 'nha_ga'): ?>
          <h3>Chưa chọn sân bay</h3>
          <p>Chọn một sân bay trên bản đồ phía trên để quản lý danh sách nhà ga trực thuộc.</p>
          <a href="<?= $e($adminUrl(['tab' => 'san_bay'])) ?>" class="btn btn-primary btn-sm mt-3">
            <i class="bi bi-building me-1"></i> Đi tới danh mục sân bay
          </a>
        <?php else: ?>
          <h3>Chưa chọn nhà ga</h3>
          <p>Chọn sân bay trên bản đồ, sau đó chọn một nhà ga để quản lý cửa ra, quầy check-in hoặc băng chuyền.</p>
          <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
            <a href="<?= $e($adminUrl(['tab' => 'san_bay'])) ?>" class="btn btn-outline-primary btn-sm">
              Chọn sân bay
            </a>
            <a href="<?= $e($adminUrl(['tab' => 'nha_ga'])) ?>" class="btn btn-primary btn-sm">
              Quản lý nhà ga
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <?php if ($formMode): ?>
      <div class="card-body pb-0">
        <?php if ($formMode === 'sua' && !$formRecord): ?>
          <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Không tìm thấy bản ghi cần sửa. Có thể bản ghi đã bị xóa hoặc đường dẫn không hợp lệ.
          </div>
        <?php elseif ($tab === 'san_bay'): ?>
          <?php require __DIR__ . '/_form_san_bay.php'; ?>
        <?php elseif ($tab === 'nha_ga'): ?>
          <?php require __DIR__ . '/_form_nha_ga.php'; ?>
        <?php else: ?>
          <?php require __DIR__ . '/_form_resource.php'; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <?php if ($tab === 'san_bay'): ?>
        <?php require __DIR__ . '/_table_san_bay.php'; ?>
      <?php elseif ($tab === 'nha_ga'): ?>
        <?php require __DIR__ . '/_table_nha_ga.php'; ?>
      <?php else: ?>
        <?php require __DIR__ . '/_table_resource.php'; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
