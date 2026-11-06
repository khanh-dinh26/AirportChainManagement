<?php
if (!$formMode) {
    return;
}

$formData = [
    'id' => 0,
    'ma_iata' => '',
    'ma_icao' => '',
    'ten_san_bay' => '',
    'ten_ngan' => '',
    'phan_cap' => 'Quoc_Noi',
    'vi_tri' => '',
    'hinh_anh' => '',
    'mo_ta' => '',
    'trang_thai' => 'Hoat_Dong',
];
if ($formRecord) {
    $formData = array_merge($formData, $formRecord);
}
$isEdit = $formMode === 'sua';
$actionName = $isEdit ? 'sua_san_bay' : 'them_san_bay';
?>
<div class="ht-form-card">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h4 class="mb-0">
      <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-1"></i>
      <?= $isEdit ? 'Cập nhật sân bay' : 'Thêm sân bay mới' ?>
    </h4>
    <span class="badge text-bg-light border"><?= $isEdit ? 'Mã: ' . $e($formData['ma_iata']) : 'Danh mục sân bay' ?></span>
  </div>

  <form method="post" action="<?= $e(BASE_URL . '/index.php?page=ha_tang') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= $e($data['csrf']) ?>" />
    <input type="hidden" name="action" value="<?= $e($actionName) ?>" />
    <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>" />
    <input type="hidden" name="return_tab" value="<?= $e($returnTab) ?>" />
    <input type="hidden" name="return_san_bay" value="<?= $e($returnSanBay) ?>" />
    <input type="hidden" name="return_nha_ga" value="<?= (int) $returnNhaGa ?>" />
    <input type="hidden" id="sb-hinh" name="hinh_anh" value="<?= $e($formData['hinh_anh']) ?>" />

    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label" for="sb-iata">Mã IATA <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm text-uppercase" id="sb-iata" name="ma_iata" value="<?= $e($formData['ma_iata']) ?>" maxlength="3" pattern="[A-Za-z]{3}" placeholder="HAN" required />
        <div class="form-text">3 chữ cái, ví dụ HAN.</div>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="sb-icao">Mã ICAO</label>
        <input type="text" class="form-control form-control-sm text-uppercase" id="sb-icao" name="ma_icao" value="<?= $e($formData['ma_icao']) ?>" maxlength="4" pattern="[A-Za-z]{4}" placeholder="VVNB" />
        <div class="form-text">Không bắt buộc, 4 chữ cái.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="sb-ten">Tên sân bay <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm" id="sb-ten" name="ten_san_bay" value="<?= $e($formData['ten_san_bay']) ?>" maxlength="120" placeholder="Sân bay quốc tế Nội Bài" required />
      </div>

      <div class="col-md-4">
        <label class="form-label" for="sb-ten-ngan">Tên ngắn <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm" id="sb-ten-ngan" name="ten_ngan" value="<?= $e($formData['ten_ngan']) ?>" maxlength="50" placeholder="Nội Bài" required />
      </div>
      <div class="col-md-4">
        <label class="form-label" for="sb-phan-cap">Phân cấp <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm" id="sb-phan-cap" name="phan_cap" required>
          <option value="Quoc_Noi" <?= $formData['phan_cap'] === 'Quoc_Noi' ? 'selected' : '' ?>>Quốc nội</option>
          <option value="Quoc_Te" <?= $formData['phan_cap'] === 'Quoc_Te' ? 'selected' : '' ?>>Quốc tế</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="sb-vi-tri">Vị trí <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-sm" id="sb-vi-tri" name="vi_tri" value="<?= $e($formData['vi_tri']) ?>" maxlength="255" placeholder="Huyện Sóc Sơn, Hà Nội" required />
      </div>

      <!-- KHU VỰC CHỌN TỆP ẢNH SÂN BAY (CHỈ DÙNG CHOOSE FILE) -->
      <div class="col-md-9">
        <label class="form-label fw-semibold" for="sb-hinh-file">
          <i class="bi bi-image me-1 text-primary"></i>Ảnh sân bay <?= empty($formData['hinh_anh']) ? '<span class="text-danger">*</span>' : '' ?>
        </label>
        <input type="file" class="form-control form-control-sm" id="sb-hinh-file" name="hinh_anh_file" accept="image/jpeg,image/png,image/webp" onchange="previewAirportImage(this)" <?= empty($formData['hinh_anh']) ? 'required' : '' ?> />
        <div class="form-text">
          Chọn tệp ảnh từ thiết bị của bạn (hỗ trợ JPG, PNG, WebP tối đa 5MB). <?= empty($formData['hinh_anh']) ? '<span class="text-danger fw-semibold">Bắt buộc chọn ảnh khi thêm sân bay mới.</span>' : 'Để trống nếu muốn giữ ảnh hiện tại.' ?>
        </div>

        <!-- Khung xem trước ảnh -->
        <div id="sb-image-preview-wrapper" class="mt-2 <?= empty($formData['hinh_anh']) ? 'd-none' : '' ?>">
          <div class="d-flex align-items-center gap-2 p-2 border rounded bg-white shadow-sm" style="max-width: 440px;">
            <img id="sb-image-preview" src="<?= !empty($formData['hinh_anh']) ? $e(BASE_URL . '/' . ltrim($formData['hinh_anh'], '/')) : '' ?>" alt="Xem trước ảnh sân bay" class="rounded object-fit-cover border" style="width: 80px; height: 50px; background: #f8fafc;" onerror="this.style.display='none'" onload="this.style.display='block'" />
            <div class="small flex-grow-1">
              <strong id="sb-preview-label" class="d-block text-dark"><?= $isEdit ? 'Ảnh hiện tại' : 'Ảnh đã chọn' ?></strong>
              <span id="sb-preview-sub" class="text-muted d-block" style="font-size: 0.78rem; word-break: break-all;"><?= $e($formData['hinh_anh'] ?: '') ?></span>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" title="Hủy chọn ảnh" onclick="clearAirportImagePreview()">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="sb-trang-thai">Trạng thái</label>
        <select class="form-select form-select-sm" id="sb-trang-thai" name="trang_thai">
          <option value="Hoat_Dong" <?= $formData['trang_thai'] === 'Hoat_Dong' ? 'selected' : '' ?>>Hoạt động</option>
          <option value="Bao_Tri" <?= $formData['trang_thai'] === 'Bao_Tri' ? 'selected' : '' ?>>Bảo trì</option>
          <option value="Dong_Cua_Khan_Cap" <?= $formData['trang_thai'] === 'Dong_Cua_Khan_Cap' ? 'selected' : '' ?>>Đóng cửa khẩn cấp</option>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label" for="sb-mo-ta">Mô tả</label>
        <textarea class="form-control form-control-sm" id="sb-mo-ta" name="mo_ta" rows="2" maxlength="500" placeholder="Thông tin ngắn về sân bay..."><?= $e($formData['mo_ta']) ?></textarea>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-check2 me-1"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Thêm sân bay' ?>
      </button>
      <a href="<?= $e($listUrl) ?>" class="btn btn-outline-secondary btn-sm">Hủy</a>
    </div>
  </form>
</div>

<script>
  function previewAirportImage(input) {
    const previewWrapper = document.getElementById('sb-image-preview-wrapper');
    const previewImg = document.getElementById('sb-image-preview');
    const previewLabel = document.getElementById('sb-preview-label');
    const previewSub = document.getElementById('sb-preview-sub');

    if (input.files && input.files[0]) {
      const file = input.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
        previewLabel.textContent = 'Ảnh vừa chọn:';
        previewSub.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        previewWrapper.classList.remove('d-none');
      };
      reader.readAsDataURL(file);
    }
  }

  function clearAirportImagePreview() {
    const fileInput = document.getElementById('sb-hinh-file');
    const hiddenInput = document.getElementById('sb-hinh');
    const previewWrapper = document.getElementById('sb-image-preview-wrapper');
    if (fileInput) fileInput.value = '';
    if (hiddenInput) hiddenInput.value = '';
    if (previewWrapper) previewWrapper.classList.add('d-none');
  }
</script>
