<?php
/**
 * @var string $tab
 * @var array $data
 * @var callable $e
 * @var string $addUrl
 * @var callable $url
 */

$tableMeta = [
    'cua_ra' => [
        'items' => $data['gates'],
        'field' => 'so_cua',
        'label' => 'cửa ra máy bay',
        'icon' => 'bi-signpost',
        'delete_action' => 'xoa_cua_ra',
        'empty' => 'Chưa có cửa ra máy bay',
    ],
    'quay' => [
        'items' => $data['checkins'],
        'field' => 'so_quay',
        'label' => 'quầy check-in',
        'icon' => 'bi-person-check',
        'delete_action' => 'xoa_quay',
        'empty' => 'Chưa có quầy check-in',
    ],
    'bang_chuyen' => [
        'items' => $data['belts'],
        'field' => 'so_bang_chuyen',
        'label' => 'băng chuyền hành lý',
        'icon' => 'bi-luggage',
        'delete_action' => 'xoa_bang_chuyen',
        'empty' => 'Chưa có băng chuyền hành lý',
    ],
];

$config = $tableMeta[$tab] ?? null;
if (!$config) {
    return;
}

$items = $config['items'];
$terminal = $selectedTerminal;
?>
<table class="table ht-table align-middle">
  <thead>
    <tr>
      <th>Số hiệu</th>
      <th>Khu vực</th>
      <th>Nhà ga</th>
      <th>Trạng thái</th>
      <th class="text-end">Thao tác</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$items): ?>
      <tr>
        <td colspan="5">
          <div class="ht-empty border-0">
            <i class="bi <?= $e($config['icon']) ?>"></i>
            <h3><?= $e($config['empty']) ?></h3>
            <p>Thêm <?= $e($config['label']) ?> cho nhà ga <?= $e($terminal['ten_nha_ga'] ?? '') ?> để hoàn thiện sơ đồ khai thác.</p>
            <a href="<?= $e($addUrl) ?>" class="btn btn-primary btn-sm mt-2 js-ht-form-open">Thêm mới</a>
          </div>
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <span class="badge text-bg-secondary"><?= $e($item[$config['field']]) ?></span>
          </td>
          <td>
            <span class="ht-soft-label"><?= !empty($item['khu_vuc']) ? $e($item['khu_vuc']) : '—' ?></span>
          </td>
          <td>
            <span class="ht-soft-label">
              <strong><?= $e($terminal['ma_nha_ga'] ?? '') ?></strong>
              · <?= $e($terminal['ten_nha_ga'] ?? '') ?>
            </span>
          </td>
          <td>
            <?= $statusBadge($item['trang_thai']) ?>
            <?php if ($tab === 'bang_chuyen' && $item['trang_thai'] === 'Bao_Tri'): ?>
              <a href="<?= $e(BASE_URL . '/index.php?page=bao_tri') ?>" class="badge text-bg-warning text-decoration-none ms-1" title="Xem phiếu bảo trì bên phân hệ Kỹ thuật & Bảo trì">
                <i class="bi bi-tools"></i> Phiếu bảo trì
              </a>
            <?php endif; ?>
          </td>
          <td>
            <div class="ht-actions">
              <a
                href="<?= $e($adminUrl(['tab' => $tab, 'san_bay' => $selectedAirport['ma_iata'], 'nha_ga' => (int) $terminal['id'], 'sua' => (int) $item['id']])) ?>"
                class="btn btn-sm btn-outline-secondary js-ht-form-open"
              >
                <i class="bi bi-pencil"></i> Sửa
              </a>
              <form method="post" action="<?= $e(BASE_URL . '/index.php?page=ha_tang') ?>" class="d-inline">
                <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>" />
                <input type="hidden" name="action" value="<?= $e($config['delete_action']) ?>" />
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>" />
                <input type="hidden" name="return_tab" value="<?= $e($tab) ?>" />
                <input type="hidden" name="return_san_bay" value="<?= $e($selectedAirport['ma_iata']) ?>" />
                <input type="hidden" name="return_nha_ga" value="<?= (int) $terminal['id'] ?>" />
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i> Xóa
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
