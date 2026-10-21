<?php
?>
<div class="modal fade" id="modalLichMua" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="bi bi-calendar-range me-1"></i> Quản lý Lịch bay mùa</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="index.php?controller=chuyenbay&action=createLichMua" method="post" class="card card-body bg-light mb-4">
          <h6 class="fw-bold mb-3">Thêm lịch bay đợt/mùa từ Hãng hàng không</h6>
          <div class="row g-2">
            <div class="col-md-2">
              <input type="text" class="form-control" name="so_hieu" placeholder="Số hiệu (VD: VN210)" required />
            </div>
            <div class="col-md-2">
              <select class="form-select" name="id_hang_hang_khong" required>
                <option value="">-- Hãng --</option>
                <?php foreach ($hangs as $h): ?>
                  <option value="<?= $h['id'] ?>"><?= $h['ma_iata'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" name="id_san_bay_di" required>
                <option value="">-- Đi --</option>
                <?php foreach ($sanBays as $sb): ?>
                  <option value="<?= $sb['id'] ?>"><?= $sb['ma_iata'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" name="id_san_bay_den" required>
                <option value="">-- Đến --</option>
                <?php foreach ($sanBays as $sb): ?>
                  <option value="<?= $sb['id'] ?>"><?= $sb['ma_iata'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control" name="tan_suat_tuan" placeholder="Thứ 2,4,6..." required />
            </div>
            <div class="col-md-2">
              <input type="date" class="form-control" name="tu_ngay" required />
            </div>
            <div class="col-md-2 mt-2">
              <input type="date" class="form-control" name="den_ngay" required />
            </div>
            <div class="col-md-2 mt-2 d-grid">
              <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Thêm Lịch Mùa</button>
            </div>
          </div>
        </form>
        <h6 class="fw-bold mb-2">Danh sách Lịch bay mùa hiện tại</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
              <tr>
                <th>Số hiệu</th>
                <th>Hãng</th>
                <th>Tuyến bay</th>
                <th>Tần suất</th>
                <th>Từ ngày</th>
                <th>Đến ngày</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($lichMuas)): ?>
                <?php foreach ($lichMuas as $lm): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($lm['so_hieu']) ?></strong></td>
                    <td><?= htmlspecialchars($lm['ten_hang']) ?></td>
                    <td><?= $lm['ma_sb_di'] ?> &rarr; <?= $lm['ma_sb_den'] ?></td>
                    <td><span class="badge text-bg-info"><?= htmlspecialchars($lm['tan_suat_tuan']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($lm['tu_ngay'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($lm['den_ngay'])) ?></td>
                    <td>
                      <a href="index.php?controller=chuyenbay&action=deleteLichMua&id=<?= $lm['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa lịch mùa này?');">Xóa</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-2">Chưa có lịch bay mùa nào.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="modalBatchGenerate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="index.php?controller=chuyenbay&action=generateDaily" method="post">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title"><i class="bi bi-cpu-fill me-1"></i> Batch Job: Tự động sinh Chuyến bay</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Hệ thống sẽ quét bảng <code>lich_bay_mua</code> và tự động sinh ra các dòng chuyến bay thực tế vào bảng <code>chuyen_bay_ngay</code> cho ngày được chọn.</p>
          <div class="mb-3">
            <label class="form-label">Chọn ngày sinh chuyến bay (*):</label>
            <input type="date" class="form-control" name="target_date" value="<?= date('Y-m-d') ?>" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-warning"><i class="bi bi-gear-wide-connected me-1"></i> Chạy Auto-Generate</button>
        </div>
      </form>
    </div>
  </div>
</div>