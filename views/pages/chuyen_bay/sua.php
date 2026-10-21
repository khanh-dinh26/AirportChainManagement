<?php

?>
<?php if (!empty($chuyenBays)): ?>
  <?php foreach ($chuyenBays as $cb): ?>
    <div class="modal fade" id="modalEdit_<?= $cb['id'] ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form action="index.php?controller=chuyenbay&action=edit" method="post">
            <input type="hidden" name="id" value="<?= $cb['id'] ?>" />
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title"><i class="bi bi-gear-fill me-1"></i> Điều hành chuyến bay: <strong><?= $cb['so_hieu'] ?></strong></h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Số hiệu chuyến bay(*):</label>
                  <input type="text" class="form-control" name="so_hieu" value="<?= htmlspecialchars($cb['so_hieu']) ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Lịch bay mùa:</label>
                  <select class="form-select" name="id_lich_mua">
                    <option value="">-- Không chọn --</option>
                    <?php foreach ($lichMuas as $lm): ?>
                      <option value="<?= $lm['id'] ?>" <?= ($cb['id_lich_mua'] == $lm['id']) ? 'selected' : '' ?>><?= htmlspecialchars($lm['so_hieu']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Hãng hàng không(*):</label>
                  <select class="form-select" name="id_hang_hang_khong" required>
                    <?php foreach ($hangs as $h): ?>
                      <option value="<?= $h['id'] ?>" <?= ($cb['id_hang_hang_khong'] == $h['id']) ? 'selected' : '' ?>><?= htmlspecialchars($h['ten_hang']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Loại tàu bay(*):</label>
                  <select class="form-select" name="id_loai_tau_bay" required>
                    <?php foreach ($tauBays as $tb): ?>
                      <option value="<?= $tb['id'] ?>" <?= ($cb['id_loai_tau_bay'] == $tb['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tb['ma_loai']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Sân bay đi:</label>
                  <select class="form-select" name="id_san_bay_di" required>
                    <?php foreach ($sanBays as $sb): ?>
                      <option value="<?= $sb['id'] ?>" <?= ($cb['id_san_bay_di'] == $sb['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sb['ten_san_bay']) ?> (<?= $sb['ma_iata'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Sân bay đến:</label>
                  <select class="form-select" name="id_san_bay_den" required>
                    <?php foreach ($sanBays as $sb): ?>
                      <option value="<?= $sb['id'] ?>" <?= ($cb['id_san_bay_den'] == $sb['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sb['ten_san_bay']) ?> (<?= $sb['ma_iata'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Giờ cất/hạ cánh dự kiến:</label>
                  <input type="datetime-local" class="form-control" name="gio_du_kien" value="<?= date('Y-m-d\TH:i', strtotime($cb['gio_du_kien'])) ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label text-primary">Giờ cất/hạ cánh thực tế:</label>
                  <input type="datetime-local" class="form-control" name="gio_thuc_te" value="<?= $cb['gio_thuc_te'] ? date('Y-m-d\TH:i', strtotime($cb['gio_thuc_te'])) : '' ?>" />
                </div>
              </div>

              <h6 class="text-secondary border-bottom pb-2 mb-3"><i class="bi bi-pin-angle-fill me-1"></i> Gán tài nguyên mặt đất</h6>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Cửa ra máy bay:</label>
                  <select class="form-select" name="id_cua_ra_may_bay">
                    <option value="">-- Chưa gán --</option>
                    <?php foreach ($cuaRas as $gate): ?>
                      <option value="<?= $gate['id'] ?>" <?= ($cb['id_cua_ra_may_bay'] == $gate['id']) ? 'selected' : '' ?>><?= $gate['so_cua'] ?> (<?= $gate['ten_nha_ga'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Quầy check-in:</label>
                  <select class="form-select" name="id_quay_checkin">
                    <option value="">-- Chưa gán --</option>
                    <?php foreach ($quays as $quay): ?>
                      <option value="<?= $quay['id'] ?>" <?= ($cb['id_quay_checkin'] == $quay['id']) ? 'selected' : '' ?>><?= $quay['so_quay'] ?> (<?= $quay['ten_nha_ga'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Băng chuyền:</label>
                  <select class="form-select" name="id_bang_chuyen">
                    <option value="">-- Chưa gán --</option>
                    <?php foreach ($bangChuyens as $belt): ?>
                      <option value="<?= $belt['id'] ?>" <?= ($cb['id_bang_chuyen'] == $belt['id']) ? 'selected' : '' ?>><?= $belt['so_bang_chuyen'] ?> (<?= $belt['ten_nha_ga'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Lượng Hành Khách:</label>
                  <input type="number" class="form-control" name="luong_hanh_khach" value="<?= $cb['luong_hanh_khach'] ?>" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Hàng hóa Tấn:</label>
                  <input type="number" step="0.01" class="form-control" name="luong_hang_hoa_tan" value="<?= $cb['luong_hang_hoa_tan'] ?>" />
                </div>
                <div class="col-md-4">
                  <label class="form-label text-danger">Trạng thái:</label>
                  <select class="form-select fw-bold text-primary" name="trang_thai_tong">
                    <option value="Len_Lich" <?= ($cb['trang_thai_tong'] == 'Len_Lich') ? 'selected' : '' ?>>Chuyến bay đang mở</option>
                    <option value="Cat_Canh" <?= ($cb['trang_thai_tong'] == 'Cat_Canh') ? 'selected' : '' ?>>Cất cánh</option>
                    <option value="Ha_Canh" <?= ($cb['trang_thai_tong'] == 'Ha_Canh') ? 'selected' : '' ?>>Hạ cánh</option>
                    <option value="Tri_Hoan" <?= ($cb['trang_thai_tong'] == 'Tri_Hoan') ? 'selected' : '' ?>>Trễ</option>
                    <option value="Huy" <?= ($cb['trang_thai_tong'] == 'Huy') ? 'selected' : '' ?>>Hủy</option>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Ghi chú thay đổi:</label>
                <input type="text" class="form-control" name="ghi_chu_log" placeholder="Nhập ghi chú thay đổi trạng thái..." />
              </div>
              <h6 class="text-secondary border-bottom pb-2 mt-4 mb-2"><i class="bi bi-clock-history me-1"></i> Nhật ký trạng thái</h6>
              <div class="bg-light p-2 rounded" style="max-height: 150px; overflow-y: auto;">
                <?php if (!empty($allLogs[$cb['id']])): ?>
                  <ul class="list-unstyled mb-0 fs-7">
                    <?php foreach ($allLogs[$cb['id']] as $lg): ?>
                      <li class="border-bottom py-1">
                        <small class="text-muted"><?= date('H:i d/m/Y', strtotime($lg['thoi_gian_cap_nhat'])) ?>:</small>
                        <span class="badge text-bg-success"><?= htmlspecialchars($statusLabels[$lg['trang_thai']][0] ?? $lg['trang_thai']) ?></span>
                        <small class="text-dark">(<?= htmlspecialchars($lg['ghi_chu'] ?: 'Không ghi chú') ?> - Người đổi: <?= htmlspecialchars($lg['ten_nguoi_cap_nhat'] ?? 'Hệ thống') ?>)</small>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <small class="text-muted">Chưa có nhật ký nào.</small>
                <?php endif; ?>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-info text-white"><i class="bi bi-save me-1"></i> Lưu thông tin điều hành</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>