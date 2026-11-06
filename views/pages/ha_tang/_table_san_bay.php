<?php
$airports = $data['airports'];
?>
<table class="table ht-table align-middle">
  <thead>
    <tr>
      <th>Mã</th>
      <th>Sân bay</th>
      <th>Phân cấp</th>
      <th>Vị trí</th>
      <th>Tài nguyên</th>
      <th>Trạng thái</th>
      <th class="text-end">Thao tác</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$airports): ?>
      <tr>
        <td colspan="7">
          <div class="ht-empty border-0">
            <i class="bi bi-building"></i>
            <h3>Chưa có sân bay</h3>
            <p>Thêm sân bay đầu tiên để bắt đầu cấu hình chuỗi cảng hàng không.</p>
            <a href="<?= $e($addUrl) ?>" class="btn btn-primary btn-sm mt-2">Thêm sân bay</a>
          </div>
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($airports as $airport): ?>
        <?php $isSelected = $selectedAirport && (int) $selectedAirport['id'] === (int) $airport['id']; ?>
        <tr class="<?= $isSelected ? 'table-active' : '' ?>">
          <td>
            <span class="badge text-bg-secondary"><?= $e($airport['ma_iata']) ?></span>
            <?php if (!empty($airport['ma_icao'])): ?>
              <span class="ht-soft-label"><?= $e($airport['ma_icao']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-semibold"><?= $e($airport['ten_san_bay']) ?></div>
            <span class="ht-soft-label"><?= $e($airport['ten_ngan']) ?></span>
          </td>
          <td>
            <span class="badge text-bg-<?= $airport['phan_cap'] === 'Quoc_Te' ? 'primary' : 'success' ?>">
              <?= $e($airport['phan_cap'] === 'Quoc_Te' ? 'Quốc tế' : 'Quốc nội') ?>
            </span>
          </td>
          <td>
            <span class="ht-soft-label"><?= $e($airport['vi_tri']) ?></span>
          </td>
          <td>
            <span class="ht-soft-label"><strong><?= (int) $airport['so_nha_ga'] ?></strong> nhà ga</span>
            <span class="ht-soft-label">
              <?= (int) $airport['so_cua_ra'] ?> cửa ·
              <?= (int) $airport['so_quay_checkin'] ?> quầy ·
              <?= (int) $airport['so_bang_chuyen'] ?> băng
            </span>
          </td>
          <td><?= $statusBadge($airport['trang_thai']) ?></td>
          <td>
            <div class="ht-actions">
              <?php if (la_admin()): ?>
                <a
                  href="<?= $e($adminUrl(['tab' => 'san_bay', 'san_bay' => $airport['ma_iata'], 'sua' => (int) $airport['id']])) ?>"
                  class="btn btn-sm btn-outline-secondary"
                >
                  <i class="bi bi-pencil"></i> Sửa
                </a>
                <form method="post" action="<?= $e(BASE_URL . '/index.php?page=ha_tang') ?>" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>" />
                  <input type="hidden" name="action" value="xoa_san_bay" />
                  <input type="hidden" name="id" value="<?= (int) $airport['id'] ?>" />
                  <input type="hidden" name="return_tab" value="san_bay" />
                  <input type="hidden" name="return_san_bay" value="<?= $e($returnSanBay) ?>" />
                  <input type="hidden" name="return_nha_ga" value="<?= (int) $returnNhaGa ?>" />
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Xóa
                  </button>
                </form>
              <?php else: ?>
                <span class="badge text-bg-light border text-secondary"><i class="bi bi-lock me-1"></i>Chỉ xem</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>
