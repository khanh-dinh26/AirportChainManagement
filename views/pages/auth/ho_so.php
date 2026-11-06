<?php
/**
 * ============================================================
 * TRANG: HỒ SƠ CÁ NHÂN & ĐỔI MẬT KHẨU
 * File: views/pages/auth/ho_so.php
 * ============================================================
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/NguoiDung.php';

yeu_cau_dang_nhap();

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$nguoiDungModel = new NguoiDung($conn);

$user = $nguoiDungModel->layTheoId($currentUserId);
if (!$user) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$nhanVaiTro = [
    'Admin_Tong_Cong_Ty'  => 'Quản trị viên Tổng công ty',
    'Quan_Ly_San_Bay'     => 'Quản lý Cảng hàng không',
    'Nhan_Vien_Dieu_Hanh' => 'Nhân viên Điều hành bay',
    'Nhan_Vien_Bao_Tri'   => 'Nhân viên Kỹ thuật & Bảo trì',
    'Ke_Toan'             => 'Kế toán & Doanh thu',
];

$roleBadgeClass = [
    'Admin_Tong_Cong_Ty'  => 'badge bg-danger-subtle text-danger border border-danger-subtle fs-6',
    'Quan_Ly_San_Bay'     => 'badge bg-primary-subtle text-primary border border-primary-subtle fs-6',
    'Nhan_Vien_Dieu_Hanh' => 'badge bg-info-subtle text-info-emphasis border border-info-subtle fs-6',
    'Nhan_Vien_Bao_Tri'   => 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6',
    'Ke_Toan'             => 'badge bg-success-subtle text-success border border-success-subtle fs-6',
];

$flashSuccess = $_SESSION['flash_ho_so_success'] ?? null;
$flashError   = $_SESSION['flash_ho_so_error'] ?? null;
unset($_SESSION['flash_ho_so_success'], $_SESSION['flash_ho_so_error']);

// Xử lý POST đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'doi_mat_khau') {
        $matKhauCu   = (string) ($_POST['mat_khau_cu'] ?? '');
        $matKhauMoi  = (string) ($_POST['mat_khau_moi'] ?? '');
        $xacNhanPass = (string) ($_POST['xac_nhan_mat_khau'] ?? '');

        if ($matKhauCu === '' || $matKhauMoi === '' || $xacNhanPass === '') {
            $_SESSION['flash_ho_so_error'] = 'Vui lòng điền đầy đủ mật khẩu hiện tại và mật khẩu mới.';
        } elseif (!password_verify($matKhauCu, $user['mat_khau_hash'])) {
            $_SESSION['flash_ho_so_error'] = 'Mật khẩu hiện tại không chính xác. Vui lòng kiểm tra lại.';
        } elseif (mb_strlen($matKhauMoi, 'UTF-8') < 6) {
            $_SESSION['flash_ho_so_error'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($matKhauMoi !== $xacNhanPass) {
            $_SESSION['flash_ho_so_error'] = 'Xác nhận mật khẩu mới không trùng khớp.';
        } else {
            // Cập nhật mật khẩu mới
            $newHash = password_hash($matKhauMoi, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE nguoi_dung SET mat_khau_hash = ? WHERE id = ?");
            $stmt->bind_param('si', $newHash, $currentUserId);
            if ($stmt->execute()) {
                $_SESSION['flash_ho_so_success'] = 'Đổi mật khẩu thành công! Mật khẩu mới của bạn đã có hiệu lực.';
            } else {
                $_SESSION['flash_ho_so_error'] = 'Lỗi hệ thống khi cập nhật mật khẩu. Vui lòng thử lại.';
            }
            $stmt->close();
        }

        header('Location: ' . BASE_URL . '/index.php?page=ho_so#doi-mat-khau');
        exit;
    }

    if ($action === 'cap_nhat_ho_ten') {
        $hoTenMoi = trim((string) ($_POST['ho_ten'] ?? ''));
        if ($hoTenMoi === '') {
            $_SESSION['flash_ho_so_error'] = 'Họ và tên không được để trống.';
        } else {
            $stmt = $conn->prepare("UPDATE nguoi_dung SET ho_ten = ? WHERE id = ?");
            $stmt->bind_param('si', $hoTenMoi, $currentUserId);
            if ($stmt->execute()) {
                $_SESSION['ho_ten'] = $hoTenMoi;
                $_SESSION['flash_ho_so_success'] = 'Cập nhật họ và tên thành công!';
            } else {
                $_SESSION['flash_ho_so_error'] = 'Không thể cập nhật họ tên. Vui lòng thử lại.';
            }
            $stmt->close();
        }

        header('Location: ' . BASE_URL . '/index.php?page=ho_so');
        exit;
    }
}

$pageTitle  = 'Thông tin cá nhân & Đổi mật khẩu';
$pageHeader = 'Hồ sơ người dùng';
$navActive  = '';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Thông tin cá nhân', 'url' => null],
];

// Lấy thông tin sân bay trực thuộc
$tenSanBay = 'Toàn quốc (Tổng công ty ACV)';
if (!empty($user['id_san_bay'])) {
    $stmtSb = $conn->prepare("SELECT ma_iata, ten_san_bay FROM san_bay WHERE id = ?");
    $stmtSb->bind_param('i', $user['id_san_bay']);
    $stmtSb->execute();
    $sbRow = $stmtSb->get_result()->fetch_assoc();
    $stmtSb->close();
    if ($sbRow) {
        $tenSanBay = $sbRow['ma_iata'] . ' — ' . $sbRow['ten_san_bay'];
    }
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

ob_start();
?>
<div class="container-fluid px-0">
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <div><?= $escape($flashSuccess) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <div><?= $escape($flashError) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Cột trái: Tóm tắt thông tin tài khoản -->
    <div class="col-lg-4 col-md-5">
      <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white text-center py-4">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-primary fw-bold shadow mb-2" style="width: 72px; height: 72px; font-size: 1.8rem;">
            <?= mb_strtoupper(mb_substr($user['ho_ten'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8') ?>
          </div>
          <h4 class="mb-1 text-white fw-bold"><?= $escape($user['ho_ten']) ?></h4>
          <div class="small text-white-50">@<?= $escape($user['ten_dang_nhap']) ?></div>
        </div>
        <div class="card-body p-3">
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
              <span class="text-secondary"><i class="bi bi-shield-lock me-2 text-primary"></i>Vai trò công tác</span>
              <span class="<?= $roleBadgeClass[$user['vai_tro']] ?? 'badge bg-secondary' ?>">
                <?= $escape($nhanVaiTro[$user['vai_tro']] ?? $user['vai_tro']) ?>
              </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
              <span class="text-secondary"><i class="bi bi-geo-alt me-2 text-danger"></i>Sân bay trực thuộc</span>
              <span class="fw-semibold text-end text-break ms-2"><?= $escape($tenSanBay) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
              <span class="text-secondary"><i class="bi bi-calendar3 me-2 text-info"></i>Trạng thái tài khoản</span>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-check2 me-1"></i>Đang hoạt động
              </span>
            </li>
          </ul>

          <div class="mt-3 p-3 bg-body-tertiary rounded-3 border">
            <div class="small fw-semibold text-primary mb-1">
              <i class="bi bi-info-circle me-1"></i>Chính sách bảo mật
            </div>
            <div class="text-secondary small">
              Nhân viên nên định kỳ thay đổi mật khẩu 3–6 tháng/lần và không chia sẻ mật khẩu tài khoản cho người khác.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cột phải: Form cập nhật họ tên & Đổi mật khẩu -->
    <div class="col-lg-8 col-md-7">
      <!-- Card đổi mật khẩu -->
      <div class="card border-0 shadow-sm rounded-3 mb-4" id="doi-mat-khau">
        <div class="card-header bg-body border-bottom py-3">
          <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-key-fill text-warning me-2"></i>Đổi mật khẩu tài khoản
          </h5>
        </div>
        <div class="card-body p-4">
          <form method="post" action="<?= BASE_URL ?>/index.php?page=ho_so">
            <input type="hidden" name="action" value="doi_mat_khau" />

            <div class="mb-3">
              <label for="mat_khau_cu" class="form-label fw-semibold">
                Mật khẩu hiện tại <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-body-tertiary"><i class="bi bi-lock"></i></span>
                <input
                  type="password"
                  class="form-control"
                  id="mat_khau_cu"
                  name="mat_khau_cu"
                  placeholder="Nhập mật khẩu bạn đang sử dụng"
                  required
                  autocomplete="current-password"
                />
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="mat_khau_moi" class="form-label fw-semibold">
                  Mật khẩu mới <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-body-tertiary"><i class="bi bi-shield-lock"></i></span>
                  <input
                    type="password"
                    class="form-control"
                    id="mat_khau_moi"
                    name="mat_khau_moi"
                    placeholder="Tối thiểu 6 ký tự"
                    minlength="6"
                    required
                    autocomplete="new-password"
                  />
                </div>
              </div>

              <div class="col-md-6">
                <label for="xac_nhan_mat_khau" class="form-label fw-semibold">
                  Xác nhận mật khẩu mới <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-body-tertiary"><i class="bi bi-shield-check"></i></span>
                  <input
                    type="password"
                    class="form-control"
                    id="xac_nhan_mat_khau"
                    name="xac_nhan_mat_khau"
                    placeholder="Nhập lại mật khẩu mới"
                    minlength="6"
                    required
                    autocomplete="new-password"
                  />
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold">
                <i class="bi bi-arrow-repeat me-1"></i>Cập nhật mật khẩu
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Card cập nhật thông tin cá nhân -->
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-body border-bottom py-3">
          <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-person-lines-fill text-primary me-2"></i>Thông tin cá nhân
          </h5>
        </div>
        <div class="card-body p-4">
          <form method="post" action="<?= BASE_URL ?>/index.php?page=ho_so">
            <input type="hidden" name="action" value="cap_nhat_ho_ten" />

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="ten_dang_nhap" class="form-label fw-semibold">Tên đăng nhập</label>
                <div class="input-group">
                  <span class="input-group-text bg-body-tertiary"><i class="bi bi-person"></i></span>
                  <input
                    type="text"
                    class="form-control bg-body-tertiary"
                    id="ten_dang_nhap"
                    value="<?= $escape($user['ten_dang_nhap']) ?>"
                    disabled
                  />
                </div>
                <div class="form-text text-muted">Tên đăng nhập được cấp cố định bởi quản trị viên.</div>
              </div>

              <div class="col-md-6">
                <label for="ho_ten" class="form-label fw-semibold">
                  Họ và tên hiển thị <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-body-tertiary"><i class="bi bi-card-text"></i></span>
                  <input
                    type="text"
                    class="form-control"
                    id="ho_ten"
                    name="ho_ten"
                    value="<?= $escape($user['ho_ten']) ?>"
                    maxlength="100"
                    required
                  />
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2 me-1"></i>Lưu họ tên
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
