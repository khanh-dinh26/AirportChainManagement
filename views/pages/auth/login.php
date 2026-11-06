<?php


// Nạp cấu hình dùng chung (BASE_URL, session) — an toàn khi mở trực tiếp file này
require_once __DIR__ . '/../../../config/config.php';

$initialPane = $initialPane ?? (isset($_GET['page']) && $_GET['page'] === 'forgot' ? 'forgot' : 'login');
$pageTitle = ($initialPane === 'forgot') ? 'Khôi phục tài khoản — AeroChain' : 'Đăng nhập — AeroChain';
$authTitle = ($initialPane === 'forgot') ? 'Khôi phục tài khoản' : 'Đăng nhập hệ thống';
$authMsg   = ($initialPane === 'forgot') ? 'Quy định bảo mật & Cấp lại mật khẩu nhân viên' : 'Dùng tài khoản nội bộ để bắt đầu phiên làm việc';

$error    = $_SESSION['error'] ?? '';
$thongBao = $_SESSION['thong_bao'] ?? '';
$daDangXuat = isset($_GET['logged_out']);

// Đọc xong thì xóa để không hiện lại khi tải lại trang
unset($_SESSION['error'], $_SESSION['thong_bao']);

ob_start();
?>
<div class="auth-panes-wrapper">
  <!-- Radio chuyển đổi trạng thái 100% thuần CSS (hoàn toàn không dùng JavaScript) -->
  <input type="radio" name="auth_tab" id="tab-login" class="auth-tab-radio" <?= $initialPane === 'login' ? 'checked' : '' ?> />
  <input type="radio" name="auth_tab" id="tab-forgot" class="auth-tab-radio" <?= $initialPane === 'forgot' ? 'checked' : '' ?> />

  <!-- Tiêu đề chuyển đổi theo tab thuần CSS -->
  <div class="auth-title-container text-center mb-1">
    <div class="login-box-msg fw-bold fs-5 mb-0 text-dark title-login">Đăng nhập hệ thống</div>
    <div class="login-box-msg fw-bold fs-5 mb-0 text-dark title-forgot">Khôi phục tài khoản</div>
  </div>
  <div class="auth-msg-container text-center mb-3">
    <div class="text-secondary small msg-login">Dùng tài khoản nội bộ để bắt đầu phiên làm việc</div>
    <div class="text-secondary small msg-forgot">Quy định bảo mật & Cấp lại mật khẩu nhân viên</div>
  </div>

  <!-- Pane 1: Form Đăng nhập -->
  <div class="auth-pane-box auth-pane-login">
    <?php if ($daDangXuat): ?>
      <div class="alert alert-success py-2" role="alert">Bạn đã đăng xuất khỏi hệ thống.</div>
    <?php endif; ?>

    <?php if ($thongBao !== ''): ?>
      <div class="alert alert-success py-2" role="alert"><?= htmlspecialchars($thongBao, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['user_id'])): ?>
      <div class="alert alert-info py-2" role="alert">
        Bạn đang đăng nhập với tài khoản <strong><?= htmlspecialchars((string) $_SESSION['ho_ten'], ENT_QUOTES, 'UTF-8') ?></strong>.
      </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/controllers/AuthController.php" method="post" novalidate>
      <label class="visually-hidden" for="loginUsername">Tên đăng nhập</label>
      <div class="input-group mb-3">
        <input
          id="loginUsername"
          name="username"
          type="text"
          class="form-control"
          placeholder="Tên đăng nhập"
          autocomplete="username"
          required
        />
        <div class="input-group-text"><span class="bi bi-person"></span></div>
      </div>

      <label class="visually-hidden" for="loginPassword">Mật khẩu</label>
      <div class="input-group mb-3">
        <input
          id="loginPassword"
          name="password"
          type="password"
          class="form-control"
          placeholder="Mật khẩu"
          autocomplete="current-password"
          required
        />
        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
      </div>

      <div class="row align-items-center">
        <div class="col-7">
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="remember" value="1" id="rememberMe" />
            <label class="form-check-label small" for="rememberMe">Ghi nhớ đăng nhập</label>
          </div>
        </div>
        <div class="col-5">
          <div class="d-grid">
            <input type="submit" name="btn_login" class="btn btn-primary" value="Đăng nhập" />
          </div>
        </div>
      </div>
    </form>

    <div class="mt-3 text-center">
      <label for="tab-forgot" class="btn-toggle-link text-primary fw-semibold small" role="button">
        <i class="bi bi-shield-lock me-1"></i>Quên mật khẩu?
      </label>
    </div>
  </div>

  <!-- Pane 2: Quy trình Quên mật khẩu (Rút gọn) -->
  <div class="auth-pane-box auth-pane-forgot">
    <div class="text-start">
      <div class="alert alert-warning border border-warning-subtle py-2 px-3 mb-3 rounded-3 small d-flex align-items-center gap-2">
        <i class="bi bi-shield-exclamation text-warning-emphasis fs-5 flex-shrink-0"></i>
        <div class="text-warning-emphasis">
          Để đảm bảo an ninh hàng không, hệ thống <strong>không cấp lại mật khẩu online</strong>.
        </div>
      </div>

      <div class="border rounded-3 p-3 bg-light-subtle mb-3">
        <div class="fw-bold small text-dark mb-2">
          <i class="bi bi-journal-check text-primary me-1"></i>Quy trình cấp lại mật khẩu:
        </div>
        <div class="d-flex flex-column gap-2 small text-secondary">
          <div class="d-flex align-items-start gap-2">
            <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 20px; height: 20px; font-size: 0.72rem;">1</span>
            <span>Mang <strong>Thẻ ngành / CCCD</strong> gặp Quản lý ca trực hoặc IT sân bay.</span>
          </div>
          <div class="d-flex align-items-start gap-2">
            <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 20px; height: 20px; font-size: 0.72rem;">2</span>
            <span>Nhận <strong>mật khẩu tạm</strong> sau khi đối soát hồ sơ nhân sự.</span>
          </div>
          <div class="d-flex align-items-start gap-2">
            <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 20px; height: 20px; font-size: 0.72rem;">3</span>
            <span>Đăng nhập và đổi lại mật khẩu cá nhân tại trang <strong>Hồ sơ</strong>.</span>
          </div>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-3 border bg-white small mb-3">
        <span class="text-secondary"><i class="bi bi-headset text-primary me-1"></i>Hotline IT Helpdesk:</span>
        <strong class="text-primary">024 3886 5000</strong>
      </div>

      <div class="d-grid">
        <label for="tab-login" class="btn btn-outline-primary fw-semibold" role="button">
          <i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập
        </label>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/auth.php';
