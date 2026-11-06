<?php
/**
 * ============================================================
 * LAYOUT XÁC THỰC (dùng cho các trang trong views/pages/auth/)
 * ------------------------------------------------------------
 * Khung trang riêng cho màn hình đăng nhập / đăng ký / quên
 * mật khẩu: không có navbar và sidebar, chỉ có form ở giữa.
 *
 * Giữ nguyên diện mạo AdminLTE 4 mặc định: thẻ trắng bo góc
 * có bóng đổ, đặt giữa nền xám (login-page + login-box).
 *
 * KHÔNG nạp bất kỳ JavaScript nào (Bootstrap JS, AdminLTE JS
 * đều đã được gỡ bỏ khỏi dự án).
 *
 * CÁCH SỬ DỤNG:
 *   $pageTitle = 'Đăng nhập';
 *   $authTitle = 'Đăng nhập hệ thống';
 *   $authMsg   = 'Nhập tài khoản để bắt đầu phiên làm việc';
 *   ob_start();
 *   ?>
 *   ... HTML của form ...
 *   <?php
 *   $content = ob_get_clean();
 *   require __DIR__ . '/../../layouts/auth.php';
 * ============================================================
 */

$pageTitle = $pageTitle ?? 'Xác thực';
$authTitle = $authTitle ?? 'AeroChain';
$authMsg   = $authMsg ?? '';
$content   = $content ?? '';

// Nạp cấu hình dùng chung (BASE_URL, ADMINLTE_URL)
require_once __DIR__ . '/../../config/config.php';
$baseUrl     = $baseUrl ?? BASE_URL;
$adminLteUrl = $adminLteUrl ?? ADMINLTE_URL;

// Tự động quét toàn bộ ảnh nền trong thư mục assets/img/bg/ để tạo slideshow đổi ảnh
$bgDir = __DIR__ . '/../../assets/img/bg';
$bgImages = [];
if (is_dir($bgDir)) {
    $scannedFiles = scandir($bgDir);
    foreach ($scannedFiles as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $bgImages[] = $baseUrl . '/assets/img/bg/' . rawurlencode($file);
        }
    }
}
if (empty($bgImages)) {
    $bgImages[] = $baseUrl . '/assets/logo/logo.png';
}
$soAnhBg = count($bgImages);
$thoiGianMoiAnh = 6; // Mỗi ảnh hiển thị 6 giây
$tongThoiGianChuKy = max(6, $soAnhBg * $thoiGianMoiAnh);
?>
<!doctype html>
<html lang="vi" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />

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
    <!--end::CSS dùng chung-->

    <!--begin::Slideshow đổi ảnh nền sân bay mượt mà & Form kính mờ-->
    <style>
      body.login-page {
        min-height: 100vh;
        margin: 0;
        padding: 0;
        background-color: #0f172a !important;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow-x: hidden;
      }

      /* Khung Slideshow nền cố định toàn màn hình */
      .login-bg-slider {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
      }

      /* Từng tấm ảnh nền có hiệu ứng crossfade & zoom nhẹ (Ken Burns) */
      .login-bg-slide {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        opacity: 0;
        <?php if ($soAnhBg > 1): ?>
          animation: skyCrossfade <?= $tongThoiGianChuKy ?>s infinite ease-in-out;
          will-change: opacity, transform;
        <?php else: ?>
          opacity: 1 !important;
        <?php endif; ?>
      }

      /* Lớp phủ chuyển sắc xanh mây sẫm bảo vệ độ tương phản */
      .login-bg-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.48) 0%, rgba(2, 132, 199, 0.32) 100%);
        z-index: 1;
      }

      <?php if ($soAnhBg > 1): ?>
      @keyframes skyCrossfade {
        0% {
          opacity: 0;
          transform: scale(1);
        }
        <?= number_format(100 / $soAnhBg * 0.16, 2) ?>% {
          opacity: 1;
        }
        <?= number_format(100 / $soAnhBg * 0.85, 2) ?>% {
          opacity: 1;
          transform: scale(1.03);
        }
        <?= number_format(100 / $soAnhBg * 1.05, 2) ?>% {
          opacity: 0;
          transform: scale(1.05);
        }
        100% {
          opacity: 0;
          transform: scale(1);
        }
      }
      <?php endif; ?>

      .login-box {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 420px;
        margin: 20px auto;
      }
      .login-logo {
        margin-bottom: 24px;
      }
      .login-logo img {
        max-height: 90px;
        max-width: 240px;
        width: auto;
        display: block;
        margin: 0 auto;
        filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.35));
        transition: transform 0.25s ease;
      }
      .login-logo img:hover {
        transform: scale(1.04);
      }
      .login-box .card {
        background: rgba(255, 255, 255, 0.94) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        border-radius: 18px !important;
        box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.4) !important;
        overflow: hidden;
      }
      .login-card-body {
        padding: 30px 28px !important;
      }
      .btn-primary {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        border-color: #0284c7 !important;
        border-radius: 10px !important;
        padding: 10px 16px !important;
        font-weight: 600 !important;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3) !important;
      }
      .btn-primary:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%) !important;
        border-color: #0369a1 !important;
      }
    </style>
    <!--end::Tùy chỉnh-->
  </head>

  <body class="login-page">
    <!--begin::Slideshow chuyển ảnh nền sân bay (Thuần CSS Animation)-->
    <div class="login-bg-slider" aria-hidden="true">
      <?php foreach ($bgImages as $idx => $bgUrl): ?>
        <div class="login-bg-slide" style="background-image: url('<?= $bgUrl ?>'); animation-delay: <?= $idx * $thoiGianMoiAnh ?>s;"></div>
      <?php endforeach; ?>
      <div class="login-bg-overlay"></div>
    </div>
    <!--end::Slideshow chuyển ảnh nền-->

    <main class="login-box">
      <!--begin::Logo (Chỉ có logo to ở giữa, đã xóa chữ AeroChain)-->
      <div class="login-logo text-center">
        <a href="<?= $baseUrl ?>/index.php" class="d-inline-block text-decoration-none" title="Trang chủ">
          <img src="<?= $baseUrl ?>/assets/logo/logo.png" alt="Logo">
        </a>
      </div>
      <!--end::Logo-->

      <div class="card">
        <div class="card-body login-card-body">
          <p class="login-box-msg fw-semibold"><?= htmlspecialchars($authTitle, ENT_QUOTES, 'UTF-8') ?></p>
          <?php if ($authMsg !== ''): ?>
            <p class="text-secondary small text-center"><?= htmlspecialchars($authMsg, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <?= $content ?>
        </div>
      </div>
    </main>
  </body>
</html>
