<?php

?>
<form class="row g-2 mb-3" action="index.php" method="get">
  <input type="hidden" name="controller" value="chuyenbay" />
  <input type="hidden" name="action" value="index" />
  
  <div class="col-md-4">
    <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword ?? '') ?>" placeholder="Mã chuyến bay / hãng..." />
  </div>
  <div class="col-md-3">
    <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date ?? '') ?>" />
  </div>
  <div class="col-md-3">
    <select class="form-select" name="status">
      <option value="">-- Tất cả trạng thái --</option>
      <option value="Len_Lich" <?= ($status == 'Len_Lich') ? 'selected' : '' ?>>Chuyến bay đang mở</option>
      <option value="Cat_Canh" <?= ($status == 'Cat_Canh') ? 'selected' : '' ?>>Cất cánh</option>
      <option value="Ha_Canh" <?= ($status == 'Ha_Canh') ? 'selected' : '' ?>>Hạ cánh</option>
      <option value="Tri_Hoan" <?= ($status == 'Tri_Hoan') ? 'selected' : '' ?>>Trễ</option>
      <option value="Huy" <?= ($status == 'Huy') ? 'selected' : '' ?>>Hủy</option>
    </select>
  </div>
  <div class="col-md-2 d-grid">
    <button type="submit" class="btn btn-outline-primary">Tìm kiếm</button>
  </div>
</form>