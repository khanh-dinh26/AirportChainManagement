<?php
?>
<?php if (!empty($chuyenBays)): ?>
  <?php foreach ($chuyenBays as $cb): ?>
    <?php if ($cb['trang_thai_tong'] !== 'Huy'): ?>
      <div class="modal fade" id="modalCancel_<?= $cb['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form action="index.php?controller=chuyenbay&action=cancel" method="post">
              <input type="hidden" name="id" value="<?= $cb['id'] ?>" />
              <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle me-1"></i> Hủy chuyến bay: <?= $cb['so_hieu'] ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <p>Bạn có chắc chắn muốn chuyển trạng thái chuyến bay <strong><?= $cb['so_hieu'] ?></strong> sang <span class="badge text-bg-danger">Hủy</span>?</p>
                <div class="mb-3">
                  <label class="form-label text-danger">Lý do hủy chuyến:</label>
                  <textarea class="form-control" name="ly_do_huy" rows="3" placeholder="Nhập lý do..." required></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Quay lại</button>
                <button type="submit" class="btn btn-danger">Xác nhận Hủy chuyến</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>