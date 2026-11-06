<?php
/**
 * ============================================================
 * TRANG: ĐĂNG NHẬP  —  views/pages/auth/login.php
 * Phân hệ: Xác thực & tài khoản (Thành viên 1)
 * ------------------------------------------------------------
 * Form gửi POST sang controllers/AuthController.php với
 * name="btn_login". Controller xử lý và quay về đây kèm
 * thông báo trong $_SESSION['error'] / $_SESSION['thong_bao'].
 * ============================================================
 */

// Nạp cấu hình dùng chung (BASE_URL, session) — an toàn khi mở trực tiếp file này
require_once __DIR__ . '/../../../config/config.php';

$pageTitle = 'Đăng nhập';
$authTitle = 'Đăng nhập hệ thống';
$authMsg   = 'Dùng tài khoản nội bộ để bắt đầu phiên làm việc';

$error    = $_SESSION['error'] ?? '';
$thongBao = $_SESSION['thong_bao'] ?? '';
$daDangXuat = isset($_GET['logged_out']);

// Đọc xong thì xóa để không hiện lại khi tải lại trang
unset($_SESSION['error'], $_SESSION['thong_bao']);

ob_start();
?>
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

            <div class="row">
              <div class="col-7">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remember" value="1" id="rememberMe" />
                  <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                </div>
              </div>
              <div class="col-5">
                <div class="d-grid gap-2">
                  <input type="submit" name="btn_login" class="btn btn-primary" value="Đăng nhập" />
                </div>
              </div>
            </div>
          </form>

          <p class="mb-0 mt-3 text-center">
            <a href="<?= BASE_URL ?>/index.php?page=forgot" class="text-decoration-none">
              <i class="bi bi-shield-lock me-1"></i>Quên mật khẩu?
            </a>
          </p>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/auth.php';
