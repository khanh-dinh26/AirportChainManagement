<?php


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

$pagination = $data['pagination'] ?? null;
$pageUrl = static function (int $p) use ($data): string {
    $params = ['page' => 'nhan_su'];
    if (!empty($data['filters']['tu_khoa'])) {
        $params['q'] = $data['filters']['tu_khoa'];
    }
    if (!empty($data['filters']['vai_tro'])) {
        $params['role'] = $data['filters']['vai_tro'];
    }
    if (isset($data['filters']['id_san_bay']) && $data['filters']['id_san_bay'] !== null) {
        $params['airport'] = $data['filters']['id_san_bay'];
    }
    if ($p > 1) {
        $params['p'] = $p;
    }
    return BASE_URL . '/index.php?' . http_build_query($params);
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
  /* Style nút thao tác */
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
    text-decoration: none !important;
  }
  .ns-action-btn:hover {
    transform: translateY(-1.5px);
  }
  .ns-action-btn:active {
    transform: translateY(0);
  }
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
  .ns-btn-disabled {
    background: #f8fafc;
    color: #94a3b8;
    border-color: #e2e8f0;
    cursor: not-allowed;
    opacity: 0.6;
  }

  /* MODAL THUẦN CSS BẰNG :TARGET */
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
</style>

<!-- THÔNG BÁO FLASH -->
<?php if ($data['flash']): ?>
  <div class="alert alert-<?= $escape($data['flash']['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center">
      <i class="bi <?= $data['flash']['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5 me-2"></i>
      <div><?= $data['flash']['message'] ?></div>
    </div>
    <a href="<?= BASE_URL ?>/index.php?page=nhan_su" class="btn-close" aria-label="Đóng"></a>
  </div>
<?php endif; ?>

<!-- THỐNG KÊ NHÂN SỰ -->
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
            Quản lý tài khoản, phân quyền vai trò và nhân sự toàn hệ thống.
          <?php elseif ($data['isQuanLySanBay']): ?>
            <span class="badge text-bg-primary me-1"><i class="bi bi-person-badge me-1"></i>Quản lý sân bay</span>
            Quản lý và thêm tài khoản nhân viên thuộc sân bay phụ trách (<strong><?= $escape($data['tenSanBayPhuTrach']) ?></strong>).
          <?php else: ?>
            <span class="badge text-bg-secondary me-1"><i class="bi bi-person-check me-1"></i>Xem danh sách</span>
            Xem thông tin nhân sự thuộc phạm vi phụ trách.
          <?php endif; ?>
        </span>
      </div>

      <?php if ($data['coQuyenThem']): ?>
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

  <!-- BẢNG NHÂN VIÊN: CÓ CỘT ẢNH ĐẠI DIỆN RIÊNG -->
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-secondary text-uppercase small">
        <tr>
          <th style="width: 45px;" class="text-center">#</th>
          <th style="width: 80px;" class="text-center">Ảnh</th>
          <th>Họ và tên</th>
          <th>Tên đăng nhập</th>
          <th>Vai trò</th>
          <th>Sân bay công tác</th>
          <th style="width: 185px;" class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($data['danhSach'])): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-secondary">
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

              $rawAvatar = $nv['anh_dai_dien'] ?? '';
              $avatarRelPath = explode('|', $rawAvatar)[0];
              $coAnh = !empty($avatarRelPath) && file_exists(__DIR__ . '/../../../' . $avatarRelPath);
              $anhUrl = $coAnh ? (BASE_URL . '/' . htmlspecialchars($avatarRelPath, ENT_QUOTES, 'UTF-8')) : '';
            ?>
            <tr>
              <td class="text-center text-muted small"><?= (!empty($pagination) ? $pagination['startIndex'] : 1) + $idx ?></td>
              
              <!-- CỘT ẢNH ĐẠI DIỆN (CHỈ HIỂN THỊ TĨNH) -->
              <td class="text-center">
                <div class="d-inline-block">
                  <?php if ($coAnh): ?>
                    <img src="<?= $anhUrl ?>" alt="<?= $escape($nv['ho_ten']) ?>"
                         class="rounded-circle shadow-sm border border-2 border-primary-subtle"
                         style="width: 44px; height: 44px; object-fit: cover;">
                  <?php else: ?>
                    <div class="<?= $avatarClass ?>" style="width: 44px; height: 44px; font-size: 1.1rem;">
                      <?= $escape($initials) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </td>

              <!-- HỌ VÀ TÊN -->
              <td>
                <strong class="d-block text-dark"><?= $escape($nv['ho_ten']) ?></strong>
                <?php if ($isSelf): ?>
                  <span class="badge bg-secondary-subtle text-secondary border small">Bạn</span>
                <?php endif; ?>
              </td>

              <!-- TÊN ĐĂNG NHẬP -->
              <td>
                <code class="fw-semibold text-primary"><?= $escape($nv['ten_dang_nhap']) ?></code>
              </td>

              <!-- VAI TRÒ -->
              <td>
                <span class="<?= $roleBadgeClass[$nv['vai_tro']] ?? 'badge bg-light text-dark' ?>">
                  <?= $escape($data['vaiTroMap'][$nv['vai_tro']] ?? $nv['vai_tro']) ?>
                </span>
              </td>

              <!-- SÂN BAY CÔNG TÁC -->
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

              <!-- THAO TÁC -->
              <td class="text-center">
                <?php
                  $canManageThis = $data['isAdminTong'] || ($data['isQuanLySanBay'] && (int)($nv['id_san_bay'] ?? 0) === $data['phamViSanBay'] && $nv['vai_tro'] !== 'Admin_Tong_Cong_Ty');
                ?>
                <div class="d-flex align-items-center justify-content-center gap-1">
                  <!-- XEM CHI TIẾT -->
                  <a href="#modalXem_<?= $nv['id'] ?>" class="ns-action-btn ns-btn-view" title="Xem chi tiết nhân viên">
                    <i class="bi bi-eye"></i>
                  </a>

                  <!-- ĐỔI MẬT KHẨU -->
                  <?php if ($canManageThis || $isSelf): ?>
                    <a href="#modalPass_<?= $nv['id'] ?>" class="ns-action-btn ns-btn-key" title="Đổi mật khẩu cho nhân viên">
                      <i class="bi bi-key-fill"></i>
                    </a>
                  <?php endif; ?>

                  <!-- SỬA THÔNG TIN -->
                  <?php if ($canManageThis): ?>
                    <a href="#modalSua_<?= $nv['id'] ?>" class="ns-action-btn ns-btn-edit" title="Sửa thông tin nhân viên">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                  <?php endif; ?>

                  <!-- XÓA NHÂN VIÊN -->
                  <?php if ($canManageThis): ?>
                    <?php if (!$isSelf): ?>
                      <a href="#modalXoa_<?= $nv['id'] ?>" class="ns-action-btn ns-btn-delete" title="Xóa tài khoản nhân viên">
                        <i class="bi bi-trash3"></i>
                      </a>
                    <?php else: ?>
                      <span class="ns-action-btn ns-btn-disabled" title="Không thể tự xóa tài khoản của chính mình">
                        <i class="bi bi-trash3"></i>
                      </span>
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

  <!-- PHÂN TRANG NHÂN SỰ -->
  <?php if (!empty($pagination) && $pagination['totalItems'] > 0): ?>
    <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-3">
      <div class="small text-secondary">
        Hiển thị <strong><?= $pagination['startIndex'] ?></strong> – <strong><?= $pagination['endIndex'] ?></strong> trong tổng số <strong><?= $pagination['totalItems'] ?></strong> nhân viên
      </div>
      <?php if ($pagination['totalPages'] > 1): ?>
        <nav aria-label="Phân trang nhân sự">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $pagination['page'] <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $pageUrl($pagination['page'] - 1) ?>" aria-label="Trang trước">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>
            <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
              <?php if ($p === 1 || $p === $pagination['totalPages'] || abs($p - $pagination['page']) <= 2): ?>
                <li class="page-item <?= $p === $pagination['page'] ? 'active' : '' ?>">
                  <a class="page-link" href="<?= $pageUrl($p) ?>"><?= $p ?></a>
                </li>
              <?php elseif (abs($p - $pagination['page']) === 3): ?>
                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
              <?php endif; ?>
            <?php endfor; ?>
            <li class="page-item <?= $pagination['page'] >= $pagination['totalPages'] ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $pageUrl($pagination['page'] + 1) ?>" aria-label="Trang sau">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- MODAL THÊM NHÂN VIÊN MỚI (ADMIN HOẶC QUẢN LÝ SÂN BAY) -->
<!-- ============================================================ -->
<?php if ($data['coQuyenThem']): ?>
<div id="modalThemNhanVien" class="custom-modal-backdrop">
  <div class="custom-modal-content">
    <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
      <input type="hidden" name="action" value="them_nhan_vien">

      <div class="modal-header bg-primary text-white px-4 py-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold mb-0 text-white">
          <i class="bi bi-person-plus-fill me-2"></i>Thêm nhân viên mới
          <?php if (!$data['isAdminTong'] && !empty($data['tenSanBayPhuTrach'])): ?>
            <span class="fs-6 fw-normal d-block opacity-75 mt-1"><?= $escape($data['tenSanBayPhuTrach']) ?></span>
          <?php endif; ?>
        </h5>
        <a href="#" class="btn-close btn-close-white" aria-label="Đóng"></a>
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
          <?php
            $vaiTroThemList = $data['isAdminTong'] ? $data['vaiTroMap'] : [
              'Nhan_Vien_Dieu_Hanh' => 'Nhân viên điều hành',
              'Nhan_Vien_Bao_Tri'   => 'Nhân viên bảo trì',
              'Ke_Toan'             => 'Kế toán',
              'Quan_Ly_San_Bay'     => 'Quản lý sân bay',
            ];
          ?>
          <select class="form-select" id="themVaiTro" name="vai_tro" required>
            <option value="">-- Chọn vai trò nhân sự --</option>
            <?php foreach ($vaiTroThemList as $key => $label): ?>
              <option value="<?= $escape($key) ?>"><?= $escape($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($data['isAdminTong']): ?>
          <div class="mb-3" id="themSanBayWrapper">
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
          </div>
        <?php else: ?>
          <div class="mb-3">
            <label class="form-label fw-bold">Sân bay trực thuộc</label>
            <input type="hidden" name="id_san_bay" value="<?= (int) $data['phamViSanBay'] ?>">
            <input type="text" class="form-control bg-light" value="<?= $escape($data['tenSanBayPhuTrach']) ?>" readonly>
            <small class="text-secondary">Tài khoản nhân sự mới sẽ tự động trực thuộc cảng hàng không bạn đang quản lý.</small>
          </div>
        <?php endif; ?>
      </div>

      <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between">
        <a href="#" class="btn btn-outline-secondary">Hủy</a>
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-plus-circle me-1"></i>Tạo tài khoản
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- CÁC MODAL THEO TỪNG NHÂN VIÊN (XEM, ĐỔI MK, SỬA, XÓA) -->
<!-- ============================================================ -->
<?php foreach ($data['danhSach'] as $nv): ?>
  <?php
    $nvId = (int) $nv['id'];
    $isSelf = $nvId === $data['currentUserId'];
    $canManageThis = $data['isAdminTong'] || ($data['isQuanLySanBay'] && (int)($nv['id_san_bay'] ?? 0) === $data['phamViSanBay'] && $nv['vai_tro'] !== 'Admin_Tong_Cong_Ty');

    $rawAvatar = $nv['anh_dai_dien'] ?? '';
    $avatarRelPath = explode('|', $rawAvatar)[0];
    $coAnh = !empty($avatarRelPath) && file_exists(__DIR__ . '/../../../' . $avatarRelPath);
    $anhUrl = $coAnh ? (BASE_URL . '/' . htmlspecialchars($avatarRelPath, ENT_QUOTES, 'UTF-8')) : '';
    $initials = mb_substr(trim($nv['ho_ten']), 0, 1, 'UTF-8');
    $tenSbHienThi = !empty($nv['san_bay_iata']) ? $nv['san_bay_iata'] . ' – ' . ($nv['ten_san_bay'] ?: $nv['san_bay_ten_ngan']) : 'Toàn quốc / Trụ sở chính';
    $vaiTroHienThi = $data['vaiTroMap'][$nv['vai_tro']] ?? $nv['vai_tro'];
  ?>

  <!-- 1. MODAL XEM CHI TIẾT -->
  <div id="modalXem_<?= $nvId ?>" class="custom-modal-backdrop">
    <div class="custom-modal-content">
      <div class="modal-header bg-light border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold text-primary mb-0">
          <i class="bi bi-person-bounding-box me-2"></i>Hồ sơ nhân viên #<?= $nvId ?>
        </h5>
        <a href="#" class="btn-close" aria-label="Đóng"></a>
      </div>
      <div class="modal-body p-4">
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
          <div class="me-3">
            <?php if ($coAnh): ?>
              <img src="<?= $anhUrl ?>" class="rounded-circle shadow-sm border border-2 border-primary-subtle" style="width: 64px; height: 64px; object-fit: cover;" alt="Avatar">
            <?php else: ?>
              <div class="ns-avatar-circle" style="width: 64px; height: 64px; font-size: 1.6rem;">
                <?= $escape($initials) ?>
              </div>
            <?php endif; ?>
          </div>
          <div>
            <h4 class="fw-bold mb-1 text-dark"><?= $escape($nv['ho_ten']) ?></h4>
            <span class="text-secondary small">Tên đăng nhập: <code class="text-primary fw-bold"><?= $escape($nv['ten_dang_nhap']) ?></code></span>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-6">
            <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Mã ID nhân sự</label>
            <span class="fw-bold text-dark">#<?= $nvId ?></span>
          </div>
          <div class="col-6">
            <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Vai trò hệ thống</label>
            <span class="badge text-bg-primary"><?= $escape($vaiTroHienThi) ?></span>
          </div>
          <div class="col-12">
            <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Cảng hàng không trực thuộc</label>
            <div class="fw-semibold text-dark"><?= $escape($tenSbHienThi) ?></div>
          </div>
          <?php if (!empty($nv['san_bay_dia_diem'])): ?>
            <div class="col-12">
              <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Địa điểm / Khu vực</label>
              <div class="text-secondary"><?= $escape($nv['san_bay_dia_diem']) ?></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-end">
        <a href="#" class="btn btn-secondary px-4">Đóng</a>
      </div>
    </div>
  </div>

  <!-- 2. MODAL ĐỔI MẬT KHẨU -->
  <?php if ($canManageThis || $isSelf): ?>
  <div id="modalPass_<?= $nvId ?>" class="custom-modal-backdrop">
    <div class="custom-modal-content">
      <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
        <input type="hidden" name="action" value="doi_mat_khau">
        <input type="hidden" name="id_nguoi_dung" value="<?= $nvId ?>">

        <div class="modal-header bg-warning-subtle border-bottom border-warning-subtle px-4 py-3 d-flex justify-content-between align-items-center">
          <h5 class="modal-title fw-bold text-dark mb-0">
            <i class="bi bi-key-fill text-warning me-2"></i>Đổi mật khẩu: <?= $escape($nv['ho_ten']) ?>
          </h5>
          <a href="#" class="btn-close" aria-label="Đóng"></a>
        </div>

        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 small mb-3">
            Tài khoản: <code class="text-primary fw-bold"><?= $escape($nv['ten_dang_nhap']) ?></code>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="mat_khau_moi" required minlength="6" placeholder="Nhập tối thiểu 6 ký tự">
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="xac_nhan_mat_khau" required minlength="6" placeholder="Nhập lại mật khẩu mới">
          </div>
        </div>

        <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between">
          <a href="#" class="btn btn-outline-secondary">Hủy</a>
          <button type="submit" class="btn btn-warning fw-bold text-dark px-4">
            <i class="bi bi-check2-circle me-1"></i>Lưu mật khẩu mới
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- 3. MODAL SỬA THÔNG TIN NHÂN VIÊN -->
  <?php if ($canManageThis): ?>
  <div id="modalSua_<?= $nvId ?>" class="custom-modal-backdrop">
    <div class="custom-modal-content">
      <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
        <input type="hidden" name="action" value="cap_nhat_thong_tin">
        <input type="hidden" name="id_nguoi_dung" value="<?= $nvId ?>">

        <div class="modal-header bg-light border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
          <h5 class="modal-title fw-bold text-primary mb-0">
            <i class="bi bi-pencil-square me-2"></i>Chỉnh sửa: <?= $escape($nv['ho_ten']) ?>
          </h5>
          <a href="#" class="btn-close" aria-label="Đóng"></a>
        </div>

        <div class="modal-body p-4">
          <div class="alert alert-light border py-2 px-3 small mb-3">
            Tài khoản: <code class="fw-bold text-primary"><?= $escape($nv['ten_dang_nhap']) ?></code> (Cố định).
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Họ và tên nhân viên <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="ho_ten" value="<?= $escape($nv['ho_ten']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Vai trò phân quyền <span class="text-danger">*</span></label>
            <?php
              $vaiTroSuaList = $data['isAdminTong'] ? $data['vaiTroMap'] : [
                'Nhan_Vien_Dieu_Hanh' => 'Nhân viên điều hành',
                'Nhan_Vien_Bao_Tri'   => 'Nhân viên bảo trì',
                'Ke_Toan'             => 'Kế toán',
                'Quan_Ly_San_Bay'     => 'Quản lý sân bay',
              ];
            ?>
            <select class="form-select" name="vai_tro" required>
              <?php foreach ($vaiTroSuaList as $key => $label): ?>
                <option value="<?= $escape($key) ?>" <?= $nv['vai_tro'] === $key ? 'selected' : '' ?>>
                  <?= $escape($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if ($data['isAdminTong']): ?>
            <div class="mb-3">
              <label class="form-label fw-bold">Sân bay làm việc / phụ trách</label>
              <select class="form-select" name="id_san_bay">
                <option value="">-- Toàn quốc / Trụ sở chính --</option>
                <?php foreach ($data['danhSachSanBay'] as $sb): ?>
                  <option value="<?= (int) $sb['id'] ?>" <?= ((int) ($nv['id_san_bay'] ?? 0) === (int) $sb['id']) ? 'selected' : '' ?>>
                    <?= $escape($sb['ma_iata'] . ' – ' . $sb['ten_san_bay']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php else: ?>
            <div class="mb-3">
              <label class="form-label fw-bold">Sân bay trực thuộc</label>
              <input type="hidden" name="id_san_bay" value="<?= (int) $data['phamViSanBay'] ?>">
              <input type="text" class="form-control bg-light" value="<?= $escape($data['tenSanBayPhuTrach']) ?>" readonly>
              <small class="text-secondary">Cố định theo phạm vi quản lý của bạn.</small>
            </div>
          <?php endif; ?>
        </div>

        <div class="modal-footer bg-light border-top px-4 py-2 d-flex justify-content-between">
          <a href="#" class="btn btn-outline-secondary">Hủy</a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i>Lưu thay đổi
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- 4. MODAL XÁC NHẬN XÓA NHÂN VIÊN -->
  <?php if ($canManageThis && !$isSelf): ?>
  <div id="modalXoa_<?= $nvId ?>" class="custom-modal-backdrop">
    <div class="custom-modal-content" style="max-width: 450px;">
      <form method="post" action="<?= BASE_URL ?>/index.php?page=nhan_su">
        <input type="hidden" name="action" value="xoa_nhan_vien">
        <input type="hidden" name="id_nguoi_dung" value="<?= $nvId ?>">

        <div class="modal-body text-center p-4">
          <div class="text-danger mb-3">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 3.5rem;"></i>
          </div>
          <h4 class="fw-bold mb-2">Xóa tài khoản nhân viên?</h4>
          <p class="text-secondary mb-3">
            Bạn có chắc chắn muốn xóa tài khoản của <strong><?= $escape($nv['ho_ten']) ?></strong> (<code><?= $escape($nv['ten_dang_nhap']) ?></code>)?
          </p>
          <div class="alert alert-warning py-2 small mb-0 text-start">
            <i class="bi bi-info-circle me-1"></i>Hành động này không thể hoàn tác.
          </div>
        </div>

        <div class="modal-footer bg-light border-top px-4 py-3 d-flex justify-content-between">
          <a href="#" class="btn btn-outline-secondary">Hủy bỏ</a>
          <button type="submit" class="btn btn-danger px-4 fw-bold">
            <i class="bi bi-trash-fill me-1"></i>Xác nhận xóa
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
<?php endforeach; ?>

<script>
  (function () {
    var vaiTro = document.getElementById('themVaiTro');
    var sanBayWrapper = document.getElementById('themSanBayWrapper');
    var sanBaySelect = document.getElementById('themIdSanBay');
    if (!vaiTro || !sanBayWrapper || !sanBaySelect) {
      return;
    }

    function capNhatSanBay() {
      var laAdminTong = vaiTro.value === 'Admin_Tong_Cong_Ty';
      sanBayWrapper.style.display = laAdminTong ? 'none' : '';
      sanBaySelect.disabled = laAdminTong;
      if (laAdminTong) {
        sanBaySelect.value = '';
      }
    }

    vaiTro.addEventListener('change', capNhatSanBay);
    capNhatSanBay();
  })();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
