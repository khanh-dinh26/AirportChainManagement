<?php


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

$viTriCanChinhMap = [
    'center center' => 'Căn chính giữa (Mặc định)',
    'center top'    => 'Căn phía trên (Lấy khuôn mặt)',
    'center bottom' => 'Căn phía dưới',
    'left center'   => 'Căn bên trái',
    'right center'  => 'Căn bên phải',
];

$flashSuccess = $_SESSION['flash_ho_so_success'] ?? null;
$flashError   = $_SESSION['flash_ho_so_error'] ?? null;
unset($_SESSION['flash_ho_so_success'], $_SESSION['flash_ho_so_error']);

// Xử lý POST đổi mật khẩu, thông tin cá nhân & ảnh đại diện
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

    // Xử lý gỡ ảnh đại diện ĐỘC LẬP
    if ($action === 'xoa_avatar') {
        if (!empty($user['anh_dai_dien'])) {
            $oldRel = explode('|', $user['anh_dai_dien'])[0];
            $oldFile = __DIR__ . '/../../../' . $oldRel;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
        $stmt = $conn->prepare("UPDATE nguoi_dung SET anh_dai_dien = NULL WHERE id = ?");
        $stmt->bind_param('i', $currentUserId);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION['anh_dai_dien']);
        $_SESSION['flash_ho_so_success'] = 'Đã gỡ bỏ ảnh đại diện thành công!';

        header('Location: ' . BASE_URL . '/index.php?page=ho_so');
        exit;
    }

    // Xử lý cập nhật ảnh đại diện (Cropped Base64 hoặc File Upload)
    if ($action === 'cap_nhat_avatar') {
        $croppedBase64 = $_POST['avatar_cropped'] ?? '';
        if (!empty($croppedBase64) && preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $croppedBase64)) {
            $parts = explode(',', $croppedBase64);
            $decoded = base64_decode($parts[1] ?? '', true);
            if ($decoded !== false && strlen($decoded) > 0) {
                $dir = __DIR__ . '/../../../assets/img/avatars';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $newName = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
                $dest = $dir . '/' . $newName;
                if (file_put_contents($dest, $decoded) !== false) {
                    if (!empty($user['anh_dai_dien'])) {
                        $oldRel = explode('|', $user['anh_dai_dien'])[0];
                        $oldFile = __DIR__ . '/../../../' . $oldRel;
                        if (file_exists($oldFile)) {
                            @unlink($oldFile);
                        }
                    }
                    $relVal = 'assets/img/avatars/' . $newName;
                    $stmt = $conn->prepare("UPDATE nguoi_dung SET anh_dai_dien = ? WHERE id = ?");
                    $stmt->bind_param('si', $relVal, $currentUserId);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['anh_dai_dien'] = $relVal;
                    $_SESSION['flash_ho_so_success'] = 'Cắt và cập nhật ảnh đại diện thành công!';
                    header('Location: ' . BASE_URL . '/index.php?page=ho_so');
                    exit;
                }
            }
        }

        if (!empty($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['avatar_file'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $_SESSION['flash_ho_so_error'] = 'Định dạng ảnh không hợp lệ (chỉ hỗ trợ JPG, PNG, WEBP, GIF).';
            } elseif ($f['size'] > 5 * 1024 * 1024) {
                $_SESSION['flash_ho_so_error'] = 'Ảnh vượt quá dung lượng cho phép (tối đa 5MB).';
            } elseif (@getimagesize($f['tmp_name']) === false) {
                $_SESSION['flash_ho_so_error'] = 'Tập tin tải lên không phải là ảnh hợp lệ.';
            } else {
                $dir = __DIR__ . '/../../../assets/img/avatars';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $newName = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $newName)) {
                    if (!empty($user['anh_dai_dien'])) {
                        $oldRel = explode('|', $user['anh_dai_dien'])[0];
                        $oldFile = __DIR__ . '/../../../' . $oldRel;
                        if (file_exists($oldFile)) {
                            @unlink($oldFile);
                        }
                    }
                    $relVal = 'assets/img/avatars/' . $newName;
                    $stmt = $conn->prepare("UPDATE nguoi_dung SET anh_dai_dien = ? WHERE id = ?");
                    $stmt->bind_param('si', $relVal, $currentUserId);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['anh_dai_dien'] = $relVal;
                    $_SESSION['flash_ho_so_success'] = 'Cập nhật ảnh đại diện thành công!';
                } else {
                    $_SESSION['flash_ho_so_error'] = 'Không thể lưu file ảnh vào máy chủ.';
                }
            }
        } else {
            $_SESSION['flash_ho_so_error'] = 'Vui lòng chọn tập tin ảnh để tải lên.';
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

$rawUserAvatar = $user['anh_dai_dien'] ?? '';
$partsUserAvatar = explode('|', $rawUserAvatar, 2);
$userAvatarPath = $partsUserAvatar[0];
$userAvatarPos = $partsUserAvatar[1] ?? 'center center';

$coAnhUser = !empty($userAvatarPath) && file_exists(__DIR__ . '/../../../' . $userAvatarPath);
$anhUserUrl = $coAnhUser ? (BASE_URL . '/' . htmlspecialchars($userAvatarPath, ENT_QUOTES, 'UTF-8')) : '';

ob_start();
?>
<style>
  .crop-view-container {
    position: relative;
    width: 240px;
    height: 240px;
    margin: 0 auto;
    background: #0f172a;
    border-radius: 12px;
    overflow: hidden;
    cursor: grab;
    user-select: none;
    touch-action: none;
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.5);
  }
  .crop-view-container:active {
    cursor: grabbing;
  }
  .crop-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 240px;
    height: 240px;
  }
  .crop-circle-mask {
    position: absolute;
    top: 15px;
    left: 15px;
    width: 210px;
    height: 210px;
    border-radius: 50%;
    border: 2px dashed #38bdf8;
    pointer-events: none;
    box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.65), 0 0 12px rgba(0,0,0,0.6);
  }
</style>
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
    <!-- Cột trái: Tóm tắt thông tin tài khoản & Ảnh đại diện -->
    <div class="col-lg-4 col-md-5">
      <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-primary text-white text-center py-4">
          <div class="position-relative d-inline-block mb-2">
            <?php if ($coAnhUser): ?>
              <img src="<?= $anhUserUrl ?>" alt="<?= $escape($user['ho_ten']) ?>" class="rounded-circle shadow border border-3 border-white" style="width: 86px; height: 86px; object-fit: cover; object-position: <?= htmlspecialchars($userAvatarPos, ENT_QUOTES, 'UTF-8') ?>;">
            <?php else: ?>
              <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white text-primary fw-bold shadow" style="width: 86px; height: 86px; font-size: 2.2rem;">
                <?= mb_strtoupper(mb_substr($user['ho_ten'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8') ?>
              </div>
            <?php endif; ?>
          </div>
          <h4 class="mb-1 text-white fw-bold"><?= $escape($user['ho_ten']) ?></h4>
          <div class="small text-white-50">@<?= $escape($user['ten_dang_nhap']) ?></div>
        </div>

        <!-- Khối Đổi / Cắt & Crop / Gỡ ảnh đại diện -->
        <div class="p-3 bg-light-subtle border-bottom">
          <!-- Form 1: Cắt & Tải lên ảnh mới -->
          <form id="formAvatarUser" method="post" action="<?= BASE_URL ?>/index.php?page=ho_so" enctype="multipart/form-data">
            <input type="hidden" name="action" value="cap_nhat_avatar" />
            <input type="hidden" name="avatar_cropped" id="userCropData" />
            
            <label class="form-label small fw-bold text-dark mb-1" for="userAvatarInput">
              <i class="bi bi-crop me-1 text-primary"></i>Chọn ảnh để cắt (crop):
            </label>
            <div class="mb-2">
              <input type="file" class="form-control form-control-sm" id="userAvatarInput" name="avatar_file" accept="image/png, image/jpeg, image/jpg, image/webp" onchange="khoiTaoCropUser(this)" />
            </div>

            <!-- GIAO DIỆN CROP ẢNH (XUẤT HIỆN KHI CHỌN ẢNH) -->
            <div id="cropBoxUser" style="display: none;" class="mt-3 pt-2 border-top text-center">
              <div class="crop-view-container" id="cropContainerUser" title="Kéo giữ chuột để căn chỉnh vị trí khuôn mặt vào giữa vòng tròn">
                <canvas id="canvasCropUser" class="crop-canvas" width="240" height="240"></canvas>
                <div class="crop-circle-mask"></div>
              </div>

              <!-- THANH ĐIỀU KHIỂN PHÓNG TO / XOAY ẢNH -->
              <div class="d-flex align-items-center justify-content-between mt-2 px-1 small text-secondary">
                <span><i class="bi bi-arrows-move me-1"></i>Kéo chuột di chuyển</span>
                <span id="zoomLabelUser" class="fw-semibold text-primary">1.0x</span>
              </div>

              <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomCropUser(-0.1)" title="Thu nhỏ">
                  <i class="bi bi-zoom-out"></i>
                </button>
                <input type="range" class="form-range flex-grow-1" id="zoomRangeUser" min="0.2" max="3" step="0.05" value="1" oninput="setZoomCropUser(this.value)">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomCropUser(0.1)" title="Phóng to">
                  <i class="bi bi-zoom-in"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="xoayCropUser()" title="Xoay ảnh 90 độ">
                  <i class="bi bi-arrow-clockwise"></i>
                </button>
              </div>

              <!-- NÚT CẮT & LƯU CHỈ HIỂN THỊ KHI ĐÃ CHỌN ẢNH ĐĂNG TẢI -->
              <div class="mt-3">
                <button class="btn btn-primary btn-sm w-100 fw-semibold" id="btnSubmitCropUser" type="button" onclick="submitCropUser()">
                  <i class="bi bi-crop me-1"></i>Cắt &amp; Lưu ảnh đại diện
                </button>
              </div>
            </div>
          </form>

          <!-- Form 2: Gỡ bỏ ảnh hiện tại hoàn toàn độc lập -->
          <?php if ($coAnhUser): ?>
            <form method="post" action="<?= BASE_URL ?>/index.php?page=ho_so" class="mt-2" onsubmit="return confirm('Bạn có chắc chắn muốn gỡ ảnh đại diện và quay về mặc định?');">
              <input type="hidden" name="action" value="xoa_avatar" />
              <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                <i class="bi bi-trash3 me-1"></i>Gỡ bỏ ảnh hiện tại
              </button>
            </form>
          <?php endif; ?>
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

    <!-- Cột phải: Form đổi mật khẩu & Sửa thông tin -->
    <div class="col-lg-8 col-md-7">
      <!-- Card đổi mật khẩu -->
      <div class="card border-0 shadow-sm rounded-3 mb-4" id="doi-mat-khau">
        <div class="card-header bg-body border-bottom py-3">
          <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-key text-warning me-2"></i>Thay đổi mật khẩu
          </h5>
        </div>
        <div class="card-body p-4">
          <form method="post" action="<?= BASE_URL ?>/index.php?page=ho_so#doi-mat-khau">
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

<script>
  let cropUserInst = null;

  function khoiTaoCropUser(input) {
    const cropBox = document.getElementById('cropBoxUser');
    if (!input.files || !input.files[0]) {
      if (cropBox) cropBox.style.display = 'none';
      return;
    }
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        const cropBox = document.getElementById('cropBoxUser');
        if (cropBox) cropBox.style.display = 'block';

        const canvas = document.getElementById('canvasCropUser');
        const ctx = canvas.getContext('2d');
        const container = document.getElementById('cropContainerUser');

        const minDim = Math.min(img.width, img.height);
        const baseScale = 210 / (minDim > 0 ? minDim : 210);

        cropUserInst = {
          img: img,
          canvas: canvas,
          ctx: ctx,
          container: container,
          baseScale: baseScale,
          zoom: 1.0,
          rotation: 0,
          offsetX: 0,
          offsetY: 0,
          isDragging: false,
          startX: 0,
          startY: 0
        };

        const range = document.getElementById('zoomRangeUser');
        if (range) range.value = 1.0;
        const label = document.getElementById('zoomLabelUser');
        if (label) label.textContent = '1.0x';

        veCropUser();

        if (!container.dataset.hasEvents) {
          container.dataset.hasEvents = 'true';

          container.addEventListener('mousedown', function(ev) {
            if (!cropUserInst) return;
            cropUserInst.isDragging = true;
            cropUserInst.startX = ev.clientX - cropUserInst.offsetX;
            cropUserInst.startY = ev.clientY - cropUserInst.offsetY;
          });

          window.addEventListener('mousemove', function(ev) {
            if (!cropUserInst || !cropUserInst.isDragging) return;
            cropUserInst.offsetX = ev.clientX - cropUserInst.startX;
            cropUserInst.offsetY = ev.clientY - cropUserInst.startY;
            veCropUser();
          });

          window.addEventListener('mouseup', function() {
            if (cropUserInst) cropUserInst.isDragging = false;
          });

          container.addEventListener('touchstart', function(ev) {
            if (!cropUserInst || !ev.touches[0]) return;
            cropUserInst.isDragging = true;
            cropUserInst.startX = ev.touches[0].clientX - cropUserInst.offsetX;
            cropUserInst.startY = ev.touches[0].clientY - cropUserInst.offsetY;
          }, { passive: true });

          window.addEventListener('touchmove', function(ev) {
            if (!cropUserInst || !cropUserInst.isDragging || !ev.touches[0]) return;
            cropUserInst.offsetX = ev.touches[0].clientX - cropUserInst.startX;
            cropUserInst.offsetY = ev.touches[0].clientY - cropUserInst.startY;
            veCropUser();
          }, { passive: true });

          window.addEventListener('touchend', function() {
            if (cropUserInst) cropUserInst.isDragging = false;
          });

          container.addEventListener('wheel', function(ev) {
            if (!cropUserInst) return;
            ev.preventDefault();
            const delta = ev.deltaY < 0 ? 0.08 : -0.08;
            zoomCropUser(delta);
          }, { passive: false });
        }
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  function veCropUser() {
    if (!cropUserInst) return;
    const canvas = cropUserInst.canvas;
    const ctx = cropUserInst.ctx;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.save();
    ctx.translate(canvas.width / 2 + cropUserInst.offsetX, canvas.height / 2 + cropUserInst.offsetY);
    ctx.rotate((cropUserInst.rotation * Math.PI) / 180);

    const scale = (cropUserInst.baseScale || 1) * cropUserInst.zoom;
    const w = cropUserInst.img.width * scale;
    const h = cropUserInst.img.height * scale;
    ctx.drawImage(cropUserInst.img, -w / 2, -h / 2, w, h);
    ctx.restore();
  }

  function zoomCropUser(delta) {
    if (!cropUserInst) return;
    cropUserInst.zoom = Math.min(3, Math.max(0.2, Math.round((cropUserInst.zoom + delta) * 100) / 100));
    const range = document.getElementById('zoomRangeUser');
    if (range) range.value = cropUserInst.zoom;
    const label = document.getElementById('zoomLabelUser');
    if (label) label.textContent = cropUserInst.zoom.toFixed(1) + 'x';
    veCropUser();
  }

  function setZoomCropUser(val) {
    if (!cropUserInst) return;
    cropUserInst.zoom = parseFloat(val);
    const label = document.getElementById('zoomLabelUser');
    if (label) label.textContent = cropUserInst.zoom.toFixed(1) + 'x';
    veCropUser();
  }

  function xoayCropUser() {
    if (!cropUserInst) return;
    cropUserInst.rotation = (cropUserInst.rotation + 90) % 360;
    veCropUser();
  }

  function submitCropUser() {
    const form = document.getElementById('formAvatarUser');
    if (!form) return;

    if (cropUserInst && cropUserInst.img) {
      // Vùng hình tròn 210px tại x=15, y=15 xuất ra 300x300
      const outCanvas = document.createElement('canvas');
      outCanvas.width = 300;
      outCanvas.height = 300;
      const outCtx = outCanvas.getContext('2d');
      outCtx.drawImage(cropUserInst.canvas, 15, 15, 210, 210, 0, 0, 300, 300);

      const base64 = outCanvas.toDataURL('image/png', 0.95);
      document.getElementById('userCropData').value = base64;

      const fileInput = document.getElementById('userAvatarInput');
      if (fileInput) fileInput.value = '';

      form.submit();
    } else {
      const fileInput = document.getElementById('userAvatarInput');
      if (fileInput && fileInput.files && fileInput.files.length > 0) {
        form.submit();
      } else {
        alert('Vui lòng chọn hình ảnh trước khi lưu.');
      }
    }
  }
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
