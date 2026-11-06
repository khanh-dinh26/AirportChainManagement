<?php
/**
 * ============================================================
 * TRANG: QUẢN LÝ NHÂN SỰ  —  views/pages/nhan_su/index.php
 * Phân hệ: Quản lý nhân sự & Phân quyền (Thành viên 1)
 * ============================================================
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/NhanSuController.php';

$controller = new NhanSuController($conn);
$data = $controller->handle();

$pageTitle = 'Quản lý nhân sự';
$pageHeader = 'Quản lý nhân sự & Phân quyền';
$navActive = 'nhan_su';
$navSubActive = 'danh_sach';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Quản lý nhân sự', 'url' => null],
];

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$roleBadgeClass = [
    'Admin_Tong_Cong_Ty'  => 'badge bg-danger-subtle text-danger border border-danger-subtle',
    'Quan_Ly_San_Bay'     => 'badge bg-primary-subtle text-primary border border-primary-subtle',
    'Nhan_Vien_Dieu_Hanh' => 'badge bg-info-subtle text-info-emphasis border border-info-subtle',
    'Nhan_Vien_Bao_Tri'   => 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle',
    'Ke_Toan'             => 'badge bg-success-subtle text-success border border-success-subtle',
];

ob_start();
?>
<style>
  /* Tinh chỉnh giao diện trang quản lý nhân sự */
  .ns-stat-card {
    border-radius: 0.85rem;
    border: 1px solid #e2e8f0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .ns-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.06);
  }
  .ns-avatar-circle {
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    background: #e0f2fe;
    color: #0284c7;
    flex-shrink: 0;
  }
  .ns-avatar-circle.admin {
    background: #fee2e2;
    color: #dc2626;
  }
  .ns-avatar-circle.manager {
    background: #e0e7ff;
    color: #4338ca;
  }
  .ns-avatar-circle.accountant {
    background: #dcfce7;
    color: #16a34a;
  }
  /* Style nút thao tác mềm mại, hiện đại */
  .ns-action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    font-size: 0.9rem;
    transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
  }
  .ns-action-btn:hover {
    transform: translateY(-1.5px);
  }
  .ns-action-btn:active {
    transform: translateY(0);
  }
  /* Nút xem chi tiết */
  .ns-btn-view {
    background: #eff6ff;
    color: #2563eb;
    border-color: #dbeafe;
  }
  .ns-btn-view:hover {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.25);
  }
  /* Nút đổi mật khẩu */
  .ns-btn-key {
    background: #fefce8;
    color: #ca8a04;
    border-color: #fef08a;
  }
  .ns-btn-key:hover {
    background: #eab308;
    color: #ffffff;
    border-color: #eab308;
    box-shadow: 0 3px 8px rgba(234, 179, 8, 0.28);
  }
  /* Nút sửa thông tin */
  .ns-btn-edit {
    background: #eef2ff;
    color: #4f46e5;
    border-color: #e0e7ff;
  }
  .ns-btn-edit:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 3px 8px rgba(79, 70, 229, 0.25);
  }
  /* Nút xóa nhân viên */
  .ns-btn-delete {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fee2e2;
  }
  .ns-btn-delete:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
    box-shadow: 0 3px 8px rgba(220, 38, 38, 0.25);
  }
  /* Nút xóa bị vô hiệu hóa (tài khoản chính mình) */
  .ns-btn-disabled {
    background: #f8fafc;
    color: #94a3b8;
    border-color: #e2e8f0;
    cursor: not-allowed;
    opacity: 0.6;
  }
  .ns-btn-disabled:hover {
    transform: none;
    box-shadow: none;
    background: #f8fafc;
    color: #94a3b8;
  }
  /* Modal CSS độc lập không phụ thuộc JS bên ngoài */
  .custom-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(3px);
    z-index: 1060;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    overflow-y: auto;
  }
  .custom-modal-backdrop.is-open,
  .custom-modal-backdrop:target {
    display: flex !important;
  }
  .custom-modal-content {
    background: #ffffff;
    border-radius: 1rem;
    max-width: 580px;
    width: 100%;
    box-shadow: 0 1.25rem 2.5rem rgba(0, 0, 0, 0.2);
    border: 1px solid #cbd5e1;
    overflow: hidden;
    animation: modalSlideIn 0.2s ease-out;
  }
  @keyframes modalSlideIn {
    from {
      opacity: 0;
      transform: translateY(-20px) scale(0.97);
    }
    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }
  .ns-filter-card {
    border-radius: 0.85rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
  }
</style>

<!-- THÔNG BÁO FLASH -->
<?php if ($data['flash']): ?>
  <div class="alert alert-<?= $escape($data['flash']['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center">
      <i class="bi <?= $data['flash']['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5 me-2"></i>
      <div><?= $data['flash']['message'] ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng" onclick="this.closest('.alert').remove();"></button>
  </div>
<?php endif; ?>

<!-- THỐNG KÊ NHÂN SỰ (4 STAT CARDS) -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card ns-stat-card shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="ns-avatar-circle me-3" style="width: 3.2rem; height: 3.2rem; background: #e0f2fe; color: #0284c7;">
          <i class="bi bi-people-fill fs-4"></i>
        </div>
        <div>
          <span class="text-secondary small fw-semibold text-uppercase">Tổng số nhân sự</span>
          <h3 class="fw-bold mb-0 text-dark"><?= number_format($data['thongKe']['tong']) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card ns-stat-card shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="ns-avatar-circle admin me-3" style="width: 3.2rem; height: 3.2rem;">
          <i class="bi bi-shield-lock-fill fs-4"></i>
        </div>
        <div>
          <span class="text-secondary small fw-semibold text-uppercase">Admin Tổng công ty</span>
          <h3 class="fw-bold mb-0 text-danger"><?= number_format($data['thongKe']['Admin_Tong_Cong_Ty']) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card ns-stat-card shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="ns-avatar-circle manager me-3" style="width: 3.2rem; height: 3.2rem;">
          <i class="bi bi-person-badge-fill fs-4"></i>
        </div>
        <div>
          <span class="text-secondary small fw-semibold text-uppercase">Quản lý sân bay</span>
          <h3 class="fw-bold mb-0 text-primary"><?= number_format($data['thongKe']['Quan_Ly_San_Bay']) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card ns-stat-card shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <div class="ns-avatar-circle accountant me-3" style="width: 3.2rem; height: 3.2rem;">
          <i class="bi bi-airplane-engines-fill fs-4"></i>
        </div>
        <div>
          <span class="text-secondary small fw-semibold text-uppercase">Điều hành &amp; Kế toán</span>
          <h3 class="fw-bold mb-0 text-success"><?= number_format($data['thongKe']['Nhan_Vien_Dieu_Hanh'] + $data['thongKe']['Ke_Toan']) ?></h3>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- KHỐI CHÍNH: BỘ LỌC VÀ BẢNG DANH SÁCH -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white py-3 border-bottom">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h2 class="h5 mb-0 fw-bold text-primary">
          <i class="bi bi-person-lines-fill me-2" aria-hidden="true"></i>Danh sách tài khoản nhân sự
        </h2>
        <span class="small text-secondary">
          <?php if ($data['isAdminTong']): ?>
            <span class="badge text-bg-danger me-1"><i class="bi bi-shield-check me-1"></i>Toàn quyền quản trị</span>
            Bạn có thể quản lý, xem thông tin và đổi mật khẩu của toàn bộ nhân viên trong chuỗi cảng hàng không.
          <?php else: ?>
            <span class="badge text-bg-primary me-1"><i class="bi bi-person-check me-1"></i>Xem danh sách</span>
            Xem thông tin nhân sự thuộc phạm vi phụ trách.
          <?php endif; ?>
        </span>
      </div>

      <?php if ($data['isAdminTong']): ?>
        <div>
          <a href="#modalThemNhanVien" class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm px-3">
            <i class="bi bi-person-plus-fill fs-6"></i>
            <span>Thêm nhân viên mới</span>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- BỘ LỌC & TÌM KIẾM -->
  <div class="card-body bg-light-subtle border-bottom py-3">
    <form method="get" action="<?= BASE_URL ?>/index.php" class="row g-2 align-items-center">
      <input type="hidden" name="page" value="nhan_su">

      <div class="col-12 col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
          <input type="text" name="q" class="form-control bg-white" placeholder="Tìm kiếm họ tên, tên đăng nhập..." value="<?= $escape($data['filters']['tu_khoa']) ?>">
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <select name="role" class="form-select bg-white">
          <option value="">-- Tất cả vai trò --</option>
          <?php foreach ($data['vaiTroMap'] as $key => $label): ?>
            <option value="<?= $escape($key) ?>" <?= $data['filters']['vai_tro'] === $key ? 'selected' : '' ?>>
              <?= $escape($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($data['isAdminTong']): ?>
      <div class="col-12 col-sm-6 col-md-3">
        <select name="airport" class="form-select bg-white">
          <option value="">-- Tất cả sân bay công tác --</option>
          <option value="0" <?= $data['filters']['id_san_bay'] === 0 ? 'selected' : '' ?>>Trụ sở chính / Toàn quốc</option>
          <?php foreach ($data['danhSachSanBay'] as $sb): ?>
            <option value="<?= (int) $sb['id'] ?>" <?= (string) $data['filters']['id_san_bay'] === (string) $sb['id'] ? 'selected' : '' ?>>
              <?= $escape($sb['ma_iata'] . ' – ' . $sb['ten_san_bay']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="col-auto d-flex gap-2">
        <button type="submit" class="btn btn-primary px-3">
          <i class="bi bi-filter me-1"></i>Lọc
        </button>
        <?php if (!empty($data['filters']['tu_khoa']) || !empty($data['filters']['vai_tro']) || (isset($data['filters']['id_san_bay']) && $data['filters']['id_san_bay'] !== null)): ?>
          <a href="<?= BASE_URL ?>/index.php?page=nhan_su" class="btn btn-outline-secondary" title="Xóa bộ lọc">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- BẢNG NHÂN VIÊN -->
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-secondary text-uppercase small">
        <tr>
          <th style="width: 50px;" class="text-center">#</th>
          <th>Nhân sự</th>
          <th>Tên đăng nhập</th>
          <th>Vai trò</th>
          <th>Sân bay công tác</th>
          <th style="width: 175px;" class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($data['danhSach'])): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-secondary">
              <i class="bi bi-person-x fs-1 d-block mb-2 text-muted"></i>
              Không tìm thấy nhân viên nào phù hợp với điều kiện tìm kiếm.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($data['danhSach'] as $idx => $nv): ?>
            <?php
              $initials = mb_substr(trim($nv['ho_ten']), 0, 1, 'UTF-8');
              $avatarClass = 'ns-avatar-circle';
              if ($nv['vai_tro'] === 'Admin_Tong_Cong_Ty') $avatarClass .= ' admin';
              elseif ($nv['vai_tro'] === 'Quan_Ly_San_Bay') $avatarClass .= ' manager';
              elseif ($nv['vai_tro'] === 'Ke_Toan') $avatarClass .= ' accountant';

              $isSelf = (int) $nv['id'] === $data['currentUserId'];
            ?>
            <tr>
              <td class="text-center text-muted small"><?= $idx + 1 ?></td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="<?= $avatarClass ?> me-2">
                    <?= $escape($initials) ?>
                  </div>
                  <div>
                    <strong class="d-block text-dark"><?= $escape($nv['ho_ten']) ?></strong>
                    <?php if ($isSelf): ?>
                      <span class="badge bg-secondary-subtle text-secondary border small">Bạn</span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td>
                <code class="fw-semibold text-primary"><?= $escape($nv['ten_dang_nhap']) ?></code>
              </td>
              <td>
                <span class="<?= $roleBadgeClass[$nv['vai_tro']] ?? 'badge bg-light text-dark' ?>">
                  <?= $escape($data['vaiTroMap'][$nv['vai_tro']] ?? $nv['vai_tro']) ?>
                </span>
              </td>
              <td>
                <?php if (!empty($nv['san_bay_iata'])): ?>
                  <span class="fw-semibold text-dark"><?= $escape($nv['san_bay_iata']) ?></span>
                  <span class="text-secondary small">– <?= $escape($nv['san_bay_ten_ngan'] ?: $nv['ten_san_bay']) ?></span>
                <?php else: ?>
                  <span class="badge text-bg-light border text-secondary">
                    <i class="bi bi-building me-1"></i>Toàn quốc / Trụ sở chính
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-1">
                  <!-- XEM CHI TIẾT -->
                  <button type="button" class="ns-action-btn ns-btn-view" title="Xem chi tiết nhân viên"
                          onclick="moModalXem(<?= htmlspecialchars(json_encode($nv), ENT_QUOTES, 'UTF-8') ?>)">
                    <i class="bi bi-eye"></i>
                  </button>

                  <!-- ĐỔI MẬT KHẨU (ADMIN TỔNG HOẶC CHÍNH BẢN THÂN) -->
                  <?php if ($data['isAdminTong'] || $isSelf): ?>
                    <button type="button" class="ns-action-btn ns-btn-key" title="Đổi mật khẩu cho nhân viên"
                            onclick="moModalDoiMatKhau(<?= (int) $nv['id'] ?>, '<?= $escape(addslashes($nv['ho_ten'])) ?>', '<?= $escape(addslashes($nv['ten_dang_nhap'])) ?>')">
                      <i class="bi bi-key-fill"></i>
                    </button>
                  <?php endif; ?>

                  <!-- SỬA THÔNG TIN (CHỈ ADMIN TỔNG) -->
                  <?php if ($data['isAdminTong']): ?>
                    <button type="button" class="ns-action-btn ns-btn-edit" title="Sửa thông tin nhân viên"
                            onclick="moModalSua(<?= htmlspecialchars(json_encode($nv), ENT_QUOTES, 'UTF-8') ?>)">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                  <?php endif; ?>

                  <!-- XÓA NHÂN VIÊN (CHỈ ADMIN TỔNG, KHÔNG XÓA CHÍNH MÌNH) -->
                  <?php if ($data['isAdminTong']): ?>
                    <?php if (!$isSelf): ?>
                      <button type="button" class="ns-action-btn ns-btn-delete" title="Xóa tài khoản nhân viên"
                              onclick="moModalXoa(<?= (int) $nv['id'] ?>, '<?= $escape(addslashes($nv['ho_ten'])) ?>', '<?= $escape(addslashes($nv['ten_dang_nhap'])) ?>')">
                        <i class="bi bi-trash3"></i>
                      </button>
                    <?php else: ?>
                      <button type="button" class="ns-action-btn ns-btn-disabled" disabled title="Không thể tự xóa tài khoản của chính mình">
                        <i class="bi bi-trash3"></i>
                      </button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 1: XEM CHI TIẾT THÔNG TIN NHÂN VIÊN -->
<!-- ============================================================ -->
<div id="modalXemNhanVien" class="custom-modal-backdrop">
  <div class="custom-modal-content">
    <div class="modal-header bg-light border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
      <h5 class="modal-title fw-bold text-primary mb-0">
        <i class="bi bi-person-bounding-box me-2"></i>Hồ sơ nhân viên
      </h5>
      <button type="button" class="btn-close" onclick="dongModal('modalXemNhanVien')"></button>
    </div>
    <div class="modal-body p-4">
      <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
        <div id="xemAvatar" class="ns-avatar-circle me-3" style="width: 3.5rem; height: 3.5rem; font-size: 1.4rem;"></div>
        <div>
          <h4 id="xemHoTen" class="fw-bold mb-1 text-dark"></h4>
          <span class="text-secondary small">Tên đăng nhập: <code id="xemTenDangNhap" class="text-primary fw-bold"></code></span>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-6">
          <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Mã ID nhân sự</label>
          <span id="xemId" class="fw-bold text-dark"></span>
        </div>
        <div class="col-6">
          <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Vai trò hệ thống</label>
          <span id="xemVaiTro"></span>
        </div>
        <div class="col-12">
          <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Cảng hàng không trực thuộc</label>
          <div id="xemSanBay" class="fw-semibold text-dark"></div>
        </div>
        <div class="col-12" id="xemDiaDiemRow">
          <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Địa điểm / Khu vực</label>
          <div id="xemDiaDiem" class="text-secondary"></div>
        </div>
      </div>
    </div>
    <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-end">
      <button type="button" class="btn btn-secondary px-4" onclick="dongModal('modalXemNhanVien')">Đóng</button>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 2: ĐỔI MẬT KHẨU NHÂN VIÊN (DÀNH CHO ADMIN TỔNG) -->
<!-- ============================================================ -->
<div id="modalDoiMatKhau" class="custom-modal-backdrop">
  <div class="custom-modal-content">
    <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
      <input type="hidden" name="action" value="doi_mat_khau">
      <input type="hidden" name="id_nguoi_dung" id="doiMatKhauId">

      <div class="modal-header bg-warning-subtle border-bottom border-warning-subtle px-4 py-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold text-dark mb-0">
          <i class="bi bi-key-fill text-warning me-2"></i>Thay đổi mật khẩu nhân viên
        </h5>
        <button type="button" class="btn-close" onclick="dongModal('modalDoiMatKhau')"></button>
      </div>

      <div class="modal-body p-4">
        <div class="alert alert-info py-2 px-3 small mb-3">
          <i class="bi bi-info-circle me-1"></i>
          Đổi mật khẩu cho: <strong id="doiMatKhauTen" class="text-dark"></strong>
          (Tài khoản: <code id="doiMatKhauUser" class="text-primary fw-bold"></code>)
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold" for="matKhauMoi">
            Mật khẩu mới <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" class="form-control" id="matKhauMoi" name="mat_khau_moi" required minlength="6" placeholder="Nhập tối thiểu 6 ký tự">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('matKhauMoi', this)">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <small class="text-secondary">Nên kết hợp chữ cái và số để đảm bảo tính an toàn.</small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold" for="xacNhanMatKhau">
            Xác nhận mật khẩu mới <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" class="form-control" id="xacNhanMatKhau" name="xac_nhan_mat_khau" required minlength="6" placeholder="Nhập lại mật khẩu mới">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('xacNhanMatKhau', this)">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-text text-muted small">
          <i class="bi bi-shield-lock me-1"></i>Mật khẩu sẽ được mã hóa an toàn và có hiệu lực ngay lập tức cho lần đăng nhập tiếp theo của nhân viên.
        </div>
      </div>

      <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" onclick="dongModal('modalDoiMatKhau')">Hủy</button>
        <button type="submit" class="btn btn-warning fw-bold text-dark px-4">
          <i class="bi bi-check2-circle me-1"></i>Lưu mật khẩu mới
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 3: THÊM NHÂN VIÊN MỚI (CHỈ ADMIN TỔNG) -->
<!-- ============================================================ -->
<?php if ($data['isAdminTong']): ?>
<div id="modalThemNhanVien" class="custom-modal-backdrop">
  <div class="custom-modal-content">
    <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
      <input type="hidden" name="action" value="them_nhan_vien">

      <div class="modal-header bg-primary text-white px-4 py-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold mb-0 text-white">
          <i class="bi bi-person-plus-fill me-2"></i>Thêm nhân viên mới
        </h5>
        <a href="#" class="btn-close btn-close-white" onclick="dongModal('modalThemNhanVien')"></a>
      </div>

      <div class="modal-body p-4">
        <div class="mb-3">
          <label class="form-label fw-bold" for="themHoTen">
            Họ và tên nhân viên <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" id="themHoTen" name="ho_ten" required placeholder="Ví dụ: Nguyễn Văn A">
        </div>

        <div class="row g-2 mb-3">
          <div class="col-12 col-md-6">
            <label class="form-label fw-bold" for="themTenDangNhap">
              Tên đăng nhập <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="themTenDangNhap" name="ten_dang_nhap" required pattern="^[a-zA-Z0-9_]{3,30}$" placeholder="Ví dụ: nva_sgn">
            <small class="text-secondary">Chỉ gồm chữ, số, dấu gạch dưới.</small>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-bold" for="themMatKhau">
              Mật khẩu khởi tạo <span class="text-danger">*</span>
            </label>
            <input type="password" class="form-control" id="themMatKhau" name="mat_khau" required minlength="6" placeholder="Tối thiểu 6 ký tự">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold" for="themVaiTro">
            Vai trò phân quyền <span class="text-danger">*</span>
          </label>
          <select class="form-select" id="themVaiTro" name="vai_tro" required onchange="xuLyDoiVaiTro(this, 'themSanBayContainer')">
            <option value="">-- Chọn vai trò nhân sự --</option>
            <?php foreach ($data['vaiTroMap'] as $key => $label): ?>
              <option value="<?= $escape($key) ?>"><?= $escape($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3" id="themSanBayContainer">
          <label class="form-label fw-bold" for="themIdSanBay">
            Sân bay làm việc / phụ trách
          </label>
          <select class="form-select" id="themIdSanBay" name="id_san_bay">
            <option value="">-- Toàn quốc / Trụ sở chính --</option>
            <?php foreach ($data['danhSachSanBay'] as $sb): ?>
              <option value="<?= (int) $sb['id'] ?>">
                <?= $escape($sb['ma_iata'] . ' – ' . $sb['ten_san_bay']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-secondary">Admin Tổng không cần gắn sân bay; Quản lý/Điều hành/Kế toán cần chọn sân bay công tác.</small>
        </div>
      </div>

      <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" onclick="dongModal('modalThemNhanVien')">Hủy</button>
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-plus-circle me-1"></i>Tạo tài khoản
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 4: SỬA THÔNG TIN NHÂN VIÊN (CHỈ ADMIN TỔNG) -->
<!-- ============================================================ -->
<div id="modalSuaNhanVien" class="custom-modal-backdrop">
  <div class="custom-modal-content">
    <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
      <input type="hidden" name="action" value="cap_nhat_thong_tin">
      <input type="hidden" name="id_nguoi_dung" id="suaId">

      <div class="modal-header bg-light border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold text-primary mb-0">
          <i class="bi bi-pencil-square me-2"></i>Chỉnh sửa thông tin nhân viên
        </h5>
        <button type="button" class="btn-close" onclick="dongModal('modalSuaNhanVien')"></button>
      </div>

      <div class="modal-body p-4">
        <div class="alert alert-light border py-2 px-3 small mb-3">
          Tài khoản: <code id="suaTenDangNhap" class="fw-bold text-primary"></code> (Tên đăng nhập không thể thay đổi).
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold" for="suaHoTen">
            Họ và tên nhân viên <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" id="suaHoTen" name="ho_ten" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold" for="suaVaiTro">
            Vai trò phân quyền <span class="text-danger">*</span>
          </label>
          <select class="form-select" id="suaVaiTro" name="vai_tro" required onchange="xuLyDoiVaiTro(this, 'suaSanBayContainer')">
            <?php foreach ($data['vaiTroMap'] as $key => $label): ?>
              <option value="<?= $escape($key) ?>"><?= $escape($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3" id="suaSanBayContainer">
          <label class="form-label fw-bold" for="suaIdSanBay">
            Sân bay làm việc / phụ trách
          </label>
          <select class="form-select" id="suaIdSanBay" name="id_san_bay">
            <option value="">-- Toàn quốc / Trụ sở chính --</option>
            <?php foreach ($data['danhSachSanBay'] as $sb): ?>
              <option value="<?= (int) $sb['id'] ?>">
                <?= $escape($sb['ma_iata'] . ' – ' . $sb['ten_san_bay']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" onclick="dongModal('modalSuaNhanVien')">Hủy</button>
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-save me-1"></i>Lưu thay đổi
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 5: XÁC NHẬN XÓA NHÂN VIÊN (CHỈ ADMIN TỔNG) -->
<!-- ============================================================ -->
<div id="modalXoaNhanVien" class="custom-modal-backdrop">
  <div class="custom-modal-content" style="max-width: 450px;">
    <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
      <input type="hidden" name="action" value="xoa_nhan_vien">
      <input type="hidden" name="id_nguoi_dung" id="xoaId">

      <div class="modal-body text-center p-4">
        <div class="text-danger mb-3">
          <i class="bi bi-exclamation-triangle-fill" style="font-size: 3.5rem;"></i>
        </div>
        <h4 class="fw-bold mb-2">Xóa tài khoản nhân viên?</h4>
        <p class="text-secondary mb-3">
          Bạn có chắc chắn muốn xóa tài khoản của <strong id="xoaHoTen" class="text-dark"></strong> (<code id="xoaTenDangNhap"></code>)?
        </p>
        <div class="alert alert-warning py-2 small mb-0 text-start">
          <i class="bi bi-info-circle me-1"></i>Hành động này không thể hoàn tác. Các dữ liệu lịch sử nhật ký sẽ lưu giữ lại dưới tên người dùng cũ.
        </div>
      </div>

      <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" onclick="dongModal('modalXoaNhanVien')">Hủy bỏ</button>
        <button type="submit" class="btn btn-danger px-4 fw-bold">
          <i class="bi bi-trash-fill me-1"></i>Xác nhận xóa
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- SCRIPT ĐIỀU KHIỂN MODAL & THAO TÁC -->
<script>
  function moModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
      el.classList.add('is-open');
    }
  }

  function dongModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
      el.classList.remove('is-open');
    }
    // Xóa hash trên URL nếu có
    if (window.location.hash) {
      history.replaceState(null, null, ' ');
    }
  }

  // Đóng modal khi bấm ra ngoài vùng nội dung modal
  document.querySelectorAll('.custom-modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.remove('is-open');
        if (window.location.hash) {
          history.replaceState(null, null, ' ');
        }
      }
    });
  });

  // Bấm Escape để đóng modal đang mở
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.custom-modal-backdrop.is-open').forEach(m => m.classList.remove('is-open'));
      if (window.location.hash) {
        history.replaceState(null, null, ' ');
      }
    }
  });

  // 1. Mở modal Xem chi tiết nhân viên
  function moModalXem(nv) {
    document.getElementById('xemId').textContent = '#' + nv.id;
    document.getElementById('xemHoTen').textContent = nv.ho_ten;
    document.getElementById('xemTenDangNhap').textContent = nv.ten_dang_nhap;
    document.getElementById('xemAvatar').textContent = (nv.ho_ten || 'A').trim().charAt(0);

    const vaiTroMap = <?= json_encode($data['vaiTroMap'], JSON_UNESCAPED_UNICODE) ?>;
    document.getElementById('xemVaiTro').innerHTML = `<span class="badge text-bg-primary">${vaiTroMap[nv.vai_tro] || nv.vai_tro}</span>`;

    if (nv.san_bay_iata) {
      document.getElementById('xemSanBay').textContent = nv.san_bay_iata + ' – ' + (nv.ten_san_bay || nv.san_bay_ten_ngan);
      document.getElementById('xemDiaDiemRow').style.display = '';
      document.getElementById('xemDiaDiem').textContent = nv.san_bay_dia_diem || 'Chưa cập nhật';
    } else {
      document.getElementById('xemSanBay').innerHTML = '<span class="text-secondary"><i class="bi bi-building me-1"></i>Toàn quốc / Trụ sở chính</span>';
      document.getElementById('xemDiaDiemRow').style.display = 'none';
    }

    moModal('modalXemNhanVien');
  }

  // 2. Mở modal Đổi mật khẩu nhân viên
  function moModalDoiMatKhau(id, hoTen, tenDangNhap) {
    document.getElementById('doiMatKhauId').value = id;
    document.getElementById('doiMatKhauTen').textContent = hoTen;
    document.getElementById('doiMatKhauUser').textContent = tenDangNhap;
    document.getElementById('matKhauMoi').value = '';
    document.getElementById('xacNhanMatKhau').value = '';

    moModal('modalDoiMatKhau');
  }

  // 3. Mở modal Sửa thông tin nhân viên
  function moModalSua(nv) {
    document.getElementById('suaId').value = nv.id;
    document.getElementById('suaTenDangNhap').textContent = nv.ten_dang_nhap;
    document.getElementById('suaHoTen').value = nv.ho_ten;
    document.getElementById('suaVaiTro').value = nv.vai_tro;
    document.getElementById('suaIdSanBay').value = nv.id_san_bay || '';

    xuLyDoiVaiTro(document.getElementById('suaVaiTro'), 'suaSanBayContainer');
    moModal('modalSuaNhanVien');
  }

  // 4. Mở modal Xác nhận xóa nhân viên
  function moModalXoa(id, hoTen, tenDangNhap) {
    document.getElementById('xoaId').value = id;
    document.getElementById('xoaHoTen').textContent = hoTen;
    document.getElementById('xoaTenDangNhap').textContent = tenDangNhap;

    moModal('modalXoaNhanVien');
  }

  // Ẩn/hiện mật khẩu
  function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = `<i class="bi bi-eye${isPass ? '-slash' : ''}"></i>`;
  }

  // Ẩn chọn sân bay nếu chọn vai trò Admin Tổng công ty
  function xuLyDoiVaiTro(selectEl, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (selectEl.value === 'Admin_Tong_Cong_Ty') {
      container.style.opacity = '0.5';
      const sbSelect = container.querySelector('select');
      if (sbSelect) sbSelect.value = '';
    } else {
      container.style.opacity = '1';
    }
  }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
