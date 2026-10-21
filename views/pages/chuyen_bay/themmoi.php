<?php
?>
<div class="modal fade" id="modalAddFlight" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="index.php?controller=chuyenbay&action=create" method="post">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="bi bi-airplane-engines me-1"></i> Thêm chuyến bay ngày mới</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Số hiệu chuyến bay(*):</label>
              <input type="text" class="form-control" name="so_hieu" placeholder="VD: VN210, VJ777" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Lịch bay mùa:</label>
              <select class="form-select" name="id_lich_mua">
                <option value="">-- Không chọn --</option>
                <?php foreach ($lichMuas as$lm): ?>
                  <option value="<?= $lm['id'] ?>"><?= htmlspecialchars($lm['so_hieu']) ?> (<?=$lm['tan_suat_tuan'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Hãng hàng không(*):</label>
              <select class="form-select" name="id_hang_hang_khong" required>
                <option value="">-- Chọn hãng --</option>
                <?php foreach ($hangs as $h): ?>
                  <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['ten_hang']) ?> (<?= $h['ma_iata'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Loại tàu bay(*):</label>
              <select class="form-select" name="id_loai_tau_bay" required>
                <option value="">-- Chọn tàu bay --</option>
                <?php foreach ($tauBays as $tb): ?>
                  <option value="<?= $tb['id'] ?>"><?= htmlspecialchars($tb['ma_loai']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Sân bay đi(*):</label>
              <select class="form-select" name="id_san_bay_di" required>
                <option value="">-- Chọn sân bay đi --</option>
                <?php foreach ($sanBays as $sb): ?>
                  <option value="<?= $sb['id'] ?>"><?= htmlspecialchars($sb['ten_san_bay']) ?> (<?= $sb['ma_iata'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Sân bay đến (*):</label>
              <select class="form-select" name="id_san_bay_den" required>
                <option value="">-- Chọn sân bay đến --</option>
                <?php foreach ($sanBays as $sb): ?>
                  <option value="<?= $sb['id'] ?>"><?= htmlspecialchars($sb['ten_san_bay']) ?> (<?= $sb['ma_iata'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Giờ cất/hạ cánh dự kiến (*):</label>
              <input type="datetime-local" class="form-control" name="gio_du_kien" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Giờ cất/hạ cánh thực tế:</label>
              <input type="datetime-local" class="form-control" name="gio_thuc_te" />
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Cửa ra máy bay:</label>
              <select class="form-select" name="id_cua_ra_may_bay">
                <option value="">-- Chưa gán --</option>
                <?php foreach ($cuaRas as $gate): ?>
                  <option value="<?= $gate['id'] ?>"><?= $gate['so_cua'] ?> (<?= $gate['ten_nha_ga'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Quầy check-in:</label>
              <select class="form-select" name="id_quay_checkin">
                <option value="">-- Chưa gán --</option>
                <?php foreach ($quays as $quay): ?>
                  <option value="<?= $quay['id'] ?>"><?= $quay['so_quay'] ?> (<?= $quay['ten_nha_ga'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Băng chuyền:</label>
              <select class="form-select" name="id_bang_chuyen">
                <option value="">-- Chưa gán --</option>
                <?php foreach ($bangChuyens as $belt): ?>
                  <option value="<?= $belt['id'] ?>"><?= $belt['so_bang_chuyen'] ?> (<?= $belt['ten_nha_ga'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Lượng Hành Khách:</label>
              <input type="number" class="form-control" name="luong_hanh_khach" value="0" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Hàng hóa Tấn:</label>
              <input type="number" step="0.01" class="form-control" name="luong_hang_hoa_tan" value="0.00" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Lưu chuyến bay mới</button>
        </div>
      </form>
    </div>
  </div>
</div>