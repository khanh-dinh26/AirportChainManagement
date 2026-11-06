<?php
/**
 * @var string|null $formMode
 * @var string $tab
 * @var array|null $formRecord
 * @var array|null $selectedAirport
 * @var array|null $selectedTerminal
 * @var callable $e
 * @var string $listUrl
 * @var array $data
 */

if (!$formMode) {
    return;
}

$formMeta = [
    'cua_ra' => [
        'action_add' => 'them_cua_ra',
        'action_edit' => 'sua_cua_ra',
        'title' => 'cửa ra máy bay',
        'field' => 'so_cua',
        'field_label' => 'Số cửa',
        'placeholder' => 'VD: 12 hoặc A01',
        'icon' => 'bi-signpost',
    ],
    'quay' => [
        'action_add' => 'them_quay',
        'action_edit' => 'sua_quay',
        'title' => 'quầy check-in',
        'field' => 'so_quay',
        'field_label' => 'Số quầy',
        'placeholder' => 'VD: C01-C15',
        'icon' => 'bi-person-check',
    ],
    'bang_chuyen' => [
        'action_add' => 'them_bang_chuyen',
        'action_edit' => 'sua_bang_chuyen',
        'title' => 'băng chuyền hành lý',
        'field' => 'so_bang_chuyen',
        'field_label' => 'Số băng chuyền',
        'placeholder' => 'VD: 01',
        'icon' => 'bi-luggage',
    ],
];

$config = $formMeta[$tab] ?? null;
if (!$config) {
    return;
}

$formData = [
    'id' => 0,
    'id_nha_ga' => $selectedTerminal['id'] ?? 0,
    'so' => '',
    'khu_vuc' => '',
    'trang_thai' => 'Hoat_Dong',
];
if ($formRecord) {
    $formData = array_merge($formData, $formRecord);
    $formData['so'] = $formRecord[$config['field']] ?? '';
}

$isEdit = $formMode === 'sua';
$actionName = $isEdit ? $config['action_edit'] : $config['action_add'];
?>
<div class="ht-form-card">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h4 class="mb-0">
      <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-1"></i>
      <?= $isEdit ? 'Cập nhật ' : 'Thêm ' ?><?= $e($config['title']) ?>
    </h4>
    <span class="badge text-bg-light border">
      <?= $isEdit ? $e($config['field_label'] . ': ' . $formData['so']) : 'Tài nguyên nhà ga' ?>
    </span>
  </div>

  <form method="post" action="<?= $e(BASE_URL . '/index.php?page=ha_tang') ?>">
    <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>" />
    <input type="hidden" name="action" value="<?= $e($actionName) ?>" />
    <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>" />
    <input type="hidden" name="return_tab" value="<?= $e($returnTab) ?>" />
    <input type="hidden" name="return_san_bay" value="<?= $e($returnSanBay) ?>" />
    <input type="hidden" name="return_nha_ga" value="<?= (int) $returnNhaGa ?>" />

    <div class="row g-3">
      <div class="col-md-5">
        <label class="form-label" for="r-nha-ga">Nhà ga <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm" id="r-nha-ga" name="id_nha_ga" required>
          <?php foreach ($terminals as $terminalItem): ?>
            <option value="<?= (int) $terminalItem['id'] ?>" <?= (int) $formData['id_nha_ga'] === (int) $terminalItem['id'] ? 'selected' : '' ?>>
              <?= $e($terminalItem['ma_nha_ga'] . ' — ' . $terminalItem['ten_nha_ga']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="r-so"><?= $e($config['field_label']) ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm text-uppercase" id="r-so" name="so" value="<?= $e($formData['so']) ?>" maxlength="20" placeholder="<?= $e($config['placeholder']) ?>" required />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="r-khu-vuc">Khu vực</label>
        <input type="text" class="form-control form-control-sm" id="r-khu-vuc" name="khu_vuc" value="<?= $e($formData['khu_vuc']) ?>" maxlength="50" placeholder="VD: Tầng 1 — Cánh A" />
      </div>

      <div class="col-md-3">
        <label class="form-label" for="r-trang-thai">Trạng thái</label>
        <select class="form-select form-select-sm" id="r-trang-thai" name="trang_thai">
          <option value="Hoat_Dong" <?= $formData['trang_thai'] === 'Hoat_Dong' ? 'selected' : '' ?>>Hoạt động</option>
          <option value="Bao_Tri" <?= $formData['trang_thai'] === 'Bao_Tri' ? 'selected' : '' ?>>Bảo trì</option>
        </select>
        <?php if ($tab === 'bang_chuyen'): ?>
          <div class="form-text text-muted small mt-1">
            <i class="bi bi-arrow-repeat me-1"></i> Tự động đồng bộ với phân hệ Kỹ thuật & Bảo trì thiết bị.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-check2 me-1"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Thêm mới' ?>
      </button>
      <a href="<?= $e($listUrl) ?>" class="btn btn-outline-secondary btn-sm">Hủy</a>
    </div>
  </form>
</div>
