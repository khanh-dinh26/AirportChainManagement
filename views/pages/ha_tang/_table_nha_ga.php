<?php
/**
 * @var array|null $selectedAirport
 * @var array $data
 * @var callable $e
 * @var string $addUrl
 * @var callable $url
 */

$airport = $selectedAirport;
$items = $data['terminals'];
?>
<table class="table ht-table align-middle">
  <thead>
    <tr>
      <th>Mã</th>
      <th>Nhà ga</th>
      <th>Loại</th>
      <th>Khai thác</th>
      <th>Diện tích / Công suất</th>
      <th>Tài nguyên</th>
      <th>Trạng thái</th>
      <th class="text-end">Thao tác</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$items): ?>
      <tr>
        <td colspan="8">
          <div class="ht-empty border-0">
            <i class="bi bi-door-closed"></i>
            <h3>Sân bay chưa có nhà ga</h3>
            <p>Thêm nhà ga đầu tiên cho <?= $e($airport['ten_san_bay']) ?> để cấu hình tài nguyên khai thác.</p>
            <a href="<?= $e($addUrl) ?>" class="btn btn-primary btn-sm mt-2 js-ht-form-open">Thêm nhà ga</a>
          </div>
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
        <?php
        $isSelected = $selectedTerminal && (int) $selectedTerminal['id'] === (int) $item['id'];
        $resourceParams = ['san_bay' => $airport['ma_iata'], 'nha_ga' => (int) $item['id']];
        $selectUrl = $adminUrl(array_merge(['tab' => 'nha_ga'], $resourceParams));
        $gatesUrl = $adminUrl(array_merge(['tab' => 'cua_ra'], $resourceParams));
        $checkinsUrl = $adminUrl(array_merge(['tab' => 'quay'], $resourceParams));
        $beltsUrl = $adminUrl(array_merge(['tab' => 'bang_chuyen'], $resourceParams));
        ?>
        <tr class="<?= $isSelected ? 'table-active ' : '' ?>ht-row-link">
          <td><span class="badge text-bg-secondary"><?= $e($item['ma_nha_ga']) ?></span></td>
          <td>
            <a
              href="<?= $e($selectUrl) ?>"
              class="ht-terminal-link fw-semibold"
              title="Chọn nhà ga <?= $e($item['ten_nha_ga']) ?> để xem tài nguyên"
              <?= $isSelected ? 'aria-current="true"' : '' ?>
            >
              <?= $e($item['ten_nha_ga']) ?>
            </a>
            <?php if ($isSelected): ?>
              <span class="badge text-bg-primary ms-1">Đang chọn</span>
            <?php endif; ?>
            <?php if (!empty($item['mo_ta'])): ?>
              <span class="ht-soft-label"><?= $e($item['mo_ta']) ?></span>
            <?php endif; ?>
          </td>
          <td><?= $terminalTypeBadge($item['loai_nha_ga']) ?></td>
          <td>
            <span class="ht-soft-label"><?= $item['nam_khai_thac'] ? (int) $item['nam_khai_thac'] : '—' ?></span>
          </td>
          <td>
            <span class="ht-soft-label">DT: <strong><?= !empty($item['dien_tich']) ? $e($item['dien_tich']) : '—' ?></strong></span>
            <span class="ht-soft-label">CS: <strong><?= !empty($item['cong_suat']) ? $e($item['cong_suat']) : '—' ?></strong></span>
          </td>
          <td>
            <span class="ht-soft-label">
              <a class="ht-resource-count" href="<?= $e($gatesUrl) ?>" title="Xem cửa ra của <?= $e($item['ten_nha_ga']) ?>">
                <strong><?= (int) $item['so_cua_ra'] ?></strong> cửa
              </a>
              ·
              <a class="ht-resource-count" href="<?= $e($checkinsUrl) ?>" title="Xem quầy check-in của <?= $e($item['ten_nha_ga']) ?>">
                <strong><?= (int) $item['so_quay_checkin'] ?></strong> quầy
              </a>
            </span>
            <span class="ht-soft-label">
              <a class="ht-resource-count" href="<?= $e($beltsUrl) ?>" title="Xem băng chuyền của <?= $e($item['ten_nha_ga']) ?>">
                <strong><?= (int) $item['so_bang_chuyen'] ?></strong> băng chuyền
              </a>
            </span>
          </td>
          <td><?= $statusBadge($item['trang_thai']) ?></td>
          <td>
            <div class="ht-actions">
              <a
                href="<?= $e($adminUrl(['tab' => 'nha_ga', 'san_bay' => $airport['ma_iata'], 'nha_ga' => (int) $item['id'], 'sua' => (int) $item['id']])) ?>"
                class="btn btn-sm btn-outline-secondary js-ht-form-open"
              >
                <i class="bi bi-pencil"></i> Sửa
              </a>
              <form method="post" action="<?= $e(BASE_URL . '/index.php?page=ha_tang') ?>" class="d-inline">
                <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>" />
                <input type="hidden" name="action" value="xoa_nha_ga" />
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>" />
                <input type="hidden" name="return_tab" value="nha_ga" />
                <input type="hidden" name="return_san_bay" value="<?= $e($airport['ma_iata']) ?>" />
                <input type="hidden" name="return_nha_ga" value="<?= (int) $returnNhaGa ?>" />
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
