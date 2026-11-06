<?php
/**
 * @var string|null $formMode
 * @var array|null $formRecord
 * @var array|null $selectedAirport
 * @var callable $e
 * @var string $listUrl
 * @var array $data
 */

if (!$formMode) {
    return;
}

$formData = [
    'id' => 0,
    'id_san_bay' => $selectedAirport['id'] ?? 0,
    'ma_nha_ga' => '',
    'ten_nha_ga' => '',
    'loai_nha_ga' => 'Hon_Hop',
    'nam_khai_thac' => '',
    'dien_tich' => '',
    'cong_suat' => '',
    'so_do' => '',
    'mo_ta' => '',
    'trang_thai' => 'Hoat_Dong',
];
if ($formRecord) {
    $formData = array_merge($formData, $formRecord);
}
$isEdit = $formMode === 'sua';
$actionName = $isEdit ? 'sua_nha_ga' : 'them_nha_ga';

$soDoPath = trim((string) ($formData['so_do'] ?? ''));
$soDoPreview = '';
if ($soDoPath !== '') {
    $soDoPreview = preg_match('~^https?://~i', $soDoPath)
        ? $soDoPath
        : BASE_URL . '/' . ltrim($soDoPath, '/');
}

$airportGroups = ['Quoc_Te' => [], 'Quoc_Noi' => []];
foreach ($data['airports'] as $airport) {
    $airportGroups[$airport['phan_cap']][] = $airport;
}
$airportGroupLabels = ['Quoc_Te' => 'Cảng hàng không quốc tế', 'Quoc_Noi' => 'Cảng hàng không quốc nội'];
?>
<div class="ht-form-card">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h4 class="mb-0">
      <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-1"></i>
      <?= $isEdit ? 'Cập nhật nhà ga' : 'Thêm nhà ga mới' ?>
    </h4>
    <span class="badge text-bg-light border"><?= $isEdit ? 'Mã: ' . $e($formData['ma_nha_ga']) : 'Nhà ga trực thuộc sân bay' ?></span>
  </div>

  <form method="post" action="<?= $e(BASE_URL . '/index.php?page=ha_tang') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>" />
    <input type="hidden" name="action" value="<?= $e($actionName) ?>" />
    <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>" />
    <input type="hidden" name="return_tab" value="<?= $e($returnTab) ?>" />
    <input type="hidden" name="return_san_bay" value="<?= $e($returnSanBay) ?>" />
    <input type="hidden" name="return_nha_ga" value="<?= (int) $returnNhaGa ?>" />

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="ng-san-bay">Sân bay <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm" id="ng-san-bay" name="id_san_bay" required>
          <option value="">— Chọn sân bay —</option>
          <?php foreach ($airportGroups as $groupKey => $group): ?>
            <?php if (!$group) continue; ?>
            <optgroup label="<?= $e($airportGroupLabels[$groupKey]) ?>">
              <?php foreach ($group as $airport): ?>
                <option value="<?= (int) $airport['id'] ?>" <?= (int) $formData['id_san_bay'] === (int) $airport['id'] ? 'selected' : '' ?>>
                  <?= $e($airport['ma_iata'] . ' — ' . $airport['ten_san_bay']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label" for="ng-ten">Tên nhà ga <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm" id="ng-ten" name="ten_nha_ga" value="<?= $e($formData['ten_nha_ga']) ?>" maxlength="100" placeholder="Nhà ga hành khách T1" required />
      </div>

      <div class="col-md-3">
        <label class="form-label" for="ng-ma">Mã nhà ga <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm text-uppercase" id="ng-ma" name="ma_nha_ga" value="<?= $e($formData['ma_nha_ga']) ?>" maxlength="10" placeholder="T1" required />
      </div>

      <div class="col-md-3">
        <label class="form-label" for="ng-loai">Loại nhà ga <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm" id="ng-loai" name="loai_nha_ga" required>
          <option value="Quoc_Noi" <?= $formData['loai_nha_ga'] === 'Quoc_Noi' ? 'selected' : '' ?>>Quốc nội</option>
          <option value="Quoc_Te" <?= $formData['loai_nha_ga'] === 'Quoc_Te' ? 'selected' : '' ?>>Quốc tế</option>
          <option value="Hon_Hop" <?= $formData['loai_nha_ga'] === 'Hon_Hop' ? 'selected' : '' ?>>Hỗn hợp</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="ng-nam">Năm khai thác</label>
        <input type="number" class="form-control form-control-sm" id="ng-nam" name="nam_khai_thac" value="<?= $e($formData['nam_khai_thac']) ?>" min="1900" max="2100" step="1" placeholder="2020" />
      </div>

      <div class="col-md-3">
        <label class="form-label" for="ng-trang-thai">Trạng thái</label>
        <select class="form-select form-select-sm" id="ng-trang-thai" name="trang_thai">
          <option value="Hoat_Dong" <?= $formData['trang_thai'] === 'Hoat_Dong' ? 'selected' : '' ?>>Hoạt động</option>
          <option value="Bao_Tri" <?= $formData['trang_thai'] === 'Bao_Tri' ? 'selected' : '' ?>>Bảo trì</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="ng-dien-tich">Diện tích</label>
        <input type="text" class="form-control form-control-sm" id="ng-dien-tich" name="dien_tich" value="<?= $e($formData['dien_tich']) ?>" maxlength="50" placeholder="VD: 115.000 m²" />
      </div>

      <div class="col-md-3">
        <label class="form-label" for="ng-cong-suat">Công suất</label>
        <input type="text" class="form-control form-control-sm" id="ng-cong-suat" name="cong_suat" value="<?= $e($formData['cong_suat']) ?>" maxlength="50" placeholder="VD: 15 triệu khách/năm" />
      </div>

      <div class="col-md-6">
        <label class="form-label" for="ng-so-do">Ảnh sơ đồ nhà ga <?= $soDoPreview === '' ? '<span class="text-danger">*</span>' : '' ?></label>
        <input type="file" class="form-control form-control-sm" id="ng-so-do" name="so_do_file" accept="image/jpeg,image/png,image/webp" <?= $soDoPreview === '' ? 'required' : '' ?> />
        <div class="form-text">
          Chọn tệp JPG, PNG hoặc WebP, tối đa 5 MB.
          <?= $isEdit && $soDoPreview !== '' ? 'Để trống nếu muốn giữ ảnh hiện tại.' : '<span class="text-danger fw-semibold">Bắt buộc tải lên ảnh sơ đồ nhà ga.</span>' ?>
        </div>
        <?php if ($soDoPreview !== ''): ?>
          <div class="ht-upload-preview mt-2">
            <img src="<?= $e($soDoPreview) ?>" alt="Ảnh sơ đồ hiện tại của <?= $e($formData['ten_nha_ga']) ?>" />
            <span>Ảnh hiện tại</span>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-12">
        <label class="form-label" for="ng-mo-ta">Mô tả</label>
        <textarea class="form-control form-control-sm" id="ng-mo-ta" name="mo_ta" rows="2" maxlength="500" placeholder="Thông tin ngắn về nhà ga..."><?= $e($formData['mo_ta']) ?></textarea>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-check2 me-1"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Thêm nhà ga' ?>
      </button>
      <a href="<?= $e($listUrl) ?>" class="btn btn-outline-secondary btn-sm">Hủy</a>
    </div>
  </form>
</div>
