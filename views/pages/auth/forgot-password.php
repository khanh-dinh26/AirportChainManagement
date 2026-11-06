<?php
/**
 * ============================================================
 * TRANG: HƯỚNG DẪN CẤP LẠI MẬT KHẨU
 * File: views/pages/auth/forgot-password.php
 * Phân hệ: Xác thực & Tài khoản (Thành viên 1)
 * ============================================================
 */

require_once __DIR__ . '/../../../config/config.php';

$pageTitle = 'Quên mật khẩu — An ninh ACV';
$authTitle = 'Khôi phục tài khoản';
$authMsg   = 'Quy định bảo mật & Cấp lại mật khẩu nhân viên';

ob_start();
?>
<div class="text-start">
  <!-- Cảnh báo an ninh hàng không -->
  <div class="alert alert-warning border border-warning-subtle d-flex align-items-start gap-2 py-3 px-3 mb-3 rounded-3" role="alert">
    <i class="bi bi-shield-exclamation text-warning-emphasis fs-4 mt-0"></i>
    <div class="small text-warning-emphasis">
      <strong>Chính sách an toàn thông tin ACV:</strong> Hệ thống điều hành chuỗi cảng hàng không không áp dụng cơ chế tự đặt lại mật khẩu trực tuyến để phòng chống nguy cơ mạo danh và chiếm đoạt tài khoản khai thác bay.
    </div>
  </div>

  <!-- Quy trình cấp lại mật khẩu -->
  <h6 class="fw-bold text-dark mb-2">
    <i class="bi bi-journal-check text-primary me-1"></i>Quy trình cấp lại mật khẩu nội bộ:
  </h6>

  <div class="list-group list-group-flush border rounded-3 mb-3 small">
    <div class="list-group-item d-flex gap-2 py-2">
      <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 22px; height: 22px;">1</span>
      <div>
        <strong>Xuất trình thẻ công tác:</strong> Nhân viên mang Thẻ ngành / Thẻ nhân viên ACV hoặc CCCD liên hệ trực tiếp <strong>Bộ phận CNTT sân bay</strong> hoặc <strong>Quản lý ca trực</strong>.
      </div>
    </div>
    <div class="list-group-item d-flex gap-2 py-2">
      <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 22px; height: 22px;">2</span>
      <div>
        <strong>Xác minh & cấp mật khẩu tạm:</strong> Quản trị viên đối soát thông tin hồ sơ nhân sự và cấp lại mật khẩu tạm thời cho nhân viên.
      </div>
    </div>
    <div class="list-group-item d-flex gap-2 py-2">
      <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 22px; height: 22px;">3</span>
      <div>
        <strong>Đăng nhập & tự đổi mật khẩu:</strong> Nhân viên đăng nhập bằng mật khẩu tạm, nhấp vào <strong>Ảnh đại diện</strong> ở góc trên bên phải để đặt lại mật khẩu bảo mật riêng của mình.
      </div>
    </div>
  </div>

  <!-- Thông tin liên hệ hỗ trợ ca trực -->
  <div class="bg-body-tertiary p-3 rounded-3 border small mb-3">
    <div class="fw-semibold text-secondary mb-1">
      <i class="bi bi-telephone-inbound me-1"></i>Hỗ trợ kỹ thuật khẩn cấp (24/7):
    </div>
    <div>Hotline IT Helpdesk: <strong class="text-primary">024 3886 5000</strong> (Máy lẻ 101)</div>
    <div>Email nội bộ: <span class="text-secondary">it-support@vietnamairport.vn</span></div>
  </div>

  <!-- Nút quay lại đăng nhập -->
  <div class="d-grid gap-2">
    <a href="<?= BASE_URL ?>/index.php?page=login" class="btn btn-primary fw-semibold">
      <i class="bi bi-arrow-left me-1"></i>Quay lại trang đăng nhập
    </a>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/auth.php';
