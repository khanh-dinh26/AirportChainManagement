<?php
/**
 * ============================================================
 * LAYOUT: HEADER (AdminLTE 4)
 * ------------------------------------------------------------
 * File này mở đầu trang: <head>, thanh navbar trên cùng và
 * sidebar. Nó KHÔNG đóng thẻ, phần thân trang nằm ở main.php
 * và phần kết thúc nằm ở footer.php
 *
 * Giao diện giữ nguyên phong cách AdminLTE 4 mặc định.
 * TOÀN BỘ GIAO DIỆN KHÔNG DÙNG JAVASCRIPT:
 *   - Thu gọn sidebar : checkbox ẩn + label (CSS)
 *   - Menu con        : <details>/<summary> (sidebar.php)
 *   - Dropdown user   : :hover / :focus-within (style.css)
 *
 * Biến có thể truyền vào (đều có giá trị mặc định):
 *   $pageTitle   : Tiêu đề tab trình duyệt
 *   $navActive   : Key menu đang mở (auth|ha_tang|chuyen_bay|bao_tri|ke_toan)
 *   $adminLteUrl : Đường dẫn tới thư mục dist của AdminLTE
 * ============================================================
 */

$pageTitle   = $pageTitle ?? 'Hệ thống quản lý chuỗi cảng hàng không';
$navActive   = $navActive ?? '';

// Nạp cấu hình dùng chung (BASE_URL, ADMINLTE_URL)
require_once __DIR__ . '/../../config/config.php';
$baseUrl     = $baseUrl ?? BASE_URL;
$adminLteUrl = $adminLteUrl ?? ADMINLTE_URL;

/* Thông tin người đang đăng nhập (do AuthController lưu vào session) */
$daDangNhap   = !empty($_SESSION['user_id']);
$tenHienThi   = $daDangNhap ? (string) $_SESSION['ho_ten'] : 'Chưa đăng nhập';
$nhanVaiTro   = [
    'Admin_Tong_Cong_Ty'   => 'Quản trị tổng công ty',
    'Quan_Ly_San_Bay'      => 'Quản lý sân bay',
    'Nhan_Vien_Dieu_Hanh'  => 'Nhân viên điều hành',
    'Nhan_Vien_Bao_Tri'    => 'Nhân viên bảo trì',
    'Ke_Toan'              => 'Kế toán',
];
$vaiTroRaw = $_SESSION['vai_tro'] ?? '';
$vaiTroHienThi = $daDangNhap
    ? ($nhanVaiTro[$vaiTroRaw] ?? (string) $vaiTroRaw)
    : 'Khách';
if ($daDangNhap && !empty($_SESSION['san_bay_iata'])) {
    $vaiTroHienThi .= ' (' . $_SESSION['san_bay_iata'] . ')';
} elseif ($daDangNhap && la_admin()) {
    $vaiTroHienThi .= ' (Toàn quốc)';
}

$avatarUrl = $adminLteUrl . '/assets/img/user2-160x160.jpg';
$avatarPos = 'center center';
if ($daDangNhap && !empty($_SESSION['anh_dai_dien'])) {
    $avatarParts = explode('|', $_SESSION['anh_dai_dien'], 2);
    $relFile = $avatarParts[0];
    if (file_exists(__DIR__ . '/../../' . $relFile)) {
        $avatarUrl = $baseUrl . '/' . $relFile;
        $avatarPos = $avatarParts[1] ?? 'center center';
    }
}
?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />
    <meta name="description" content="Hệ thống quản lý điều hành khai thác chuỗi cảng hàng không (AeroChain)" />

    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.3.0/index.css"
      crossorigin="anonymous"
    />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin-->

    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="<?= $adminLteUrl ?>/css/adminlte.css" />
    <!--end::Required Plugin-->

    <!--begin::CSS dùng chung của dự án (nạp sau AdminLTE)-->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/style.css') ?: '1' ?>" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard-sky.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/dashboard-sky.css') ?: '1' ?>" />
    <!--end::CSS dùng chung-->
  </head>

  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!-- Checkbox điều khiển sidebar, thuần CSS (không cần JS) -->
    <input type="checkbox" id="acv-sidebar-toggle" class="acv-sidebar-toggle" aria-label="Mở hoặc đóng menu" />

    <div class="app-wrapper">
      <!-- Lớp phủ đóng menu trên màn hình nhỏ (label cho checkbox) -->
      <label for="acv-sidebar-toggle" class="acv-sidebar-overlay" aria-hidden="true"></label>

      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
          <ul class="navbar-nav">
            <li class="nav-item">
              <label
                for="acv-sidebar-toggle"
                class="nav-link acv-sidebar-toggle-btn"
                role="button"
                tabindex="0"
                aria-label="Mở/đóng menu"
              >
                <i class="bi bi-list"></i>
              </label>
            </li>
            <li class="nav-item d-none d-md-block">
              <a href="<?= $baseUrl ?>/index.php" class="nav-link">
                <i class="bi bi-grid-1x2 me-1" aria-hidden="true"></i>
                Tổng quan
              </a>
            </li>
          </ul>

          <ul class="navbar-nav ms-auto">
            <!--begin::User Menu Dropdown (CSS :hover / :focus-within, không JS)-->
            <li class="nav-item dropdown user-menu">
              <a href="<?= $daDangNhap ? $baseUrl . '/index.php?page=ho_so' : '#user-menu' ?>" class="nav-link dropdown-toggle" title="Xem thông tin cá nhân & đổi mật khẩu" aria-haspopup="true">
                <img
                  src="<?= $avatarUrl ?>"
                  class="user-image rounded-circle shadow"
                  style="object-fit: cover; object-position: <?= htmlspecialchars($avatarPos, ENT_QUOTES, 'UTF-8') ?>;"
                  alt="Ảnh người dùng"
                />
                <span class="d-none d-xl-inline"><?= htmlspecialchars($tenHienThi, ENT_QUOTES, 'UTF-8') ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end" id="user-menu">
                <li class="user-header text-bg-primary">
                  <img
                    src="<?= $avatarUrl ?>"
                    class="rounded-circle shadow"
                    style="object-fit: cover; width: 90px; height: 90px; object-position: <?= htmlspecialchars($avatarPos, ENT_QUOTES, 'UTF-8') ?>;"
                    alt="Ảnh người dùng"
                  />
                  <p>
                    <?= htmlspecialchars($tenHienThi, ENT_QUOTES, 'UTF-8') ?>
                    <small><?= htmlspecialchars($vaiTroHienThi, ENT_QUOTES, 'UTF-8') ?></small>
                  </p>
                </li>
                <li class="user-footer d-flex justify-content-between align-items-center">
                  <?php if ($daDangNhap): ?>
                    <a href="<?= $baseUrl ?>/index.php?page=ho_so" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-person-circle me-1"></i>Hồ sơ & Đổi MK
                    </a>
                    <a href="<?= $baseUrl ?>/controllers/AuthController.php?action=logout" class="btn btn-outline-danger btn-sm">
                      <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                    </a>
                  <?php else: ?>
                    <a href="<?= $baseUrl ?>/index.php?page=login" class="btn btn-outline-primary btn-sm ms-auto">
                      Đăng nhập
                    </a>
                  <?php endif; ?>
                </li>
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
        </div>
      </nav>
      <!--end::Header-->

      <?php require __DIR__ . '/sidebar.php'; ?>
