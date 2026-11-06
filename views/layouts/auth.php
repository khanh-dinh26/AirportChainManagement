<?php

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
$thoiGianMoiAnh = 6.0; // Mỗi ảnh hiển thị 6 giây
$thoiGianFade = 1.2;  // Thời gian chuyển tiếp mượt mà giữa các ảnh (giây)
$tongThoiGianChuKy = max(6.0, $soAnhBg * $thoiGianMoiAnh);

// Tự động tính toán tỷ lệ % chuyển cảnh chính xác theo số lượng ảnh thực tế
$animFade = 3.0;
$animStay = 30.0;
$animFadeEnd = 35.0;
if ($soAnhBg > 1) {
    $p = 100 / $soAnhBg;
    $animFade = round(($thoiGianFade / $tongThoiGianChuKy) * 100, 2);
    $animStay = round($p, 2);
    $animFadeEnd = round($p + $animFade, 2);
}
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
        background-color: #0f172a;
      }

      /* Từng tấm ảnh nền có hiệu ứng crossfade mượt mà & zoom Ken Burns rõ nét */
      .login-bg-slide {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        opacity: 0;
        will-change: opacity, transform;
        transform-origin: center center;
        <?php if ($soAnhBg > 1): ?>
          animation-name: skyAuthKenBurns;
          animation-duration: <?= $tongThoiGianChuKy ?>s;
          animation-iteration-count: infinite;
          animation-timing-function: ease-in-out;
        <?php else: ?>
          opacity: 1 !important;
          animation: skyAuthSingleKenBurns 20s infinite ease-in-out;
        <?php endif; ?>
      }

      /* Góc ngắm camera Ken Burns so le tạo hiệu ứng điện ảnh sinh động */
      .login-bg-slide:nth-of-type(even) {
        transform-origin: 65% 45%;
      }
      .login-bg-slide:nth-of-type(odd) {
        transform-origin: 35% 55%;
      }

      /* Lớp phủ chuyển sắc nhẹ giữ trọn 100% màu sắc thực tế nhưng vẫn tôn bật form đăng nhập */
      .login-bg-overlay {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at center, rgba(15, 23, 42, 0.15) 0%, rgba(15, 23, 42, 0.40) 100%);
        z-index: 1;
      }

      <?php
      if ($soAnhBg > 1) {
          echo "@keyframes skyAuthKenBurns {\n";
          echo "  0% { opacity: 0; transform: scale(1.0); }\n";
          echo "  {$animFade}% { opacity: 1; transform: scale(1.015); }\n";
          echo "  {$animStay}% { opacity: 1; transform: scale(1.08); }\n";
          echo "  {$animFadeEnd}% { opacity: 0; transform: scale(1.095); }\n";
          echo "  100% { opacity: 0; transform: scale(1.0); }\n";
          echo "}\n";
      } else {
          echo "@keyframes skyAuthSingleKenBurns {\n";
          echo "  0% { transform: scale(1.0); }\n";
          echo "  50% { transform: scale(1.08); }\n";
          echo "  100% { transform: scale(1.0); }\n";
          echo "}\n";
      }
      ?>

      .login-box {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 420px;
        margin: 20px auto;
      }
      .login-box .card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        border-radius: 20px !important;
        box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.4) !important;
        overflow: hidden;
      }
      .login-card-body {
        padding: 32px 28px !important;
      }
      .auth-card-logo-wrap {
        display: flex;
        justify-content: center;
        margin-bottom: 16px;
      }
      .auth-card-logo {
        max-height: 85px;
        max-width: 220px;
        width: auto;
        display: block;
        user-select: none;
        pointer-events: none; /* Tĩnh, không thể hover hay click */
        filter: drop-shadow(0 6px 14px rgba(2, 132, 199, 0.22));
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

      /* ============================================================
       * CHUYỂN ĐỔI FORM 100% THUẦN CSS (KHÔNG DÙNG JAVASCRIPT)
       * ============================================================ */
      .auth-tab-radio {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 0;
        height: 0;
        margin: 0;
      }
      .auth-panes-wrapper {
        position: relative;
        width: 100%;
      }

      /* Chuyển đổi tiêu đề & phụ đề theo radio thuần CSS */
      .title-login, .title-forgot, .msg-login, .msg-forgot {
        transition: opacity 0.25s ease;
      }
      #tab-login:checked ~ .auth-title-container .title-login { display: block; }
      #tab-login:checked ~ .auth-title-container .title-forgot { display: none; }
      #tab-login:checked ~ .auth-msg-container .msg-login { display: block; }
      #tab-login:checked ~ .auth-msg-container .msg-forgot { display: none; }

      #tab-forgot:checked ~ .auth-title-container .title-login { display: none; }
      #tab-forgot:checked ~ .auth-title-container .title-forgot { display: block; }
      #tab-forgot:checked ~ .auth-msg-container .msg-login { display: none; }
      #tab-forgot:checked ~ .auth-msg-container .msg-forgot { display: block; }

      /* Khung chứa các pane */
      .auth-pane-box {
        width: 100%;
        transition: opacity 0.26s cubic-bezier(0.4, 0, 0.2, 1), transform 0.26s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: opacity, transform;
      }

      /* Khi Tab Đăng nhập được chọn */
      #tab-login:checked ~ .auth-pane-login {
        position: relative;
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
        z-index: 2;
      }
      #tab-login:checked ~ .auth-pane-forgot {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(10px);
        z-index: 1;
      }

      /* Khi Tab Quên mật khẩu được chọn */
      #tab-forgot:checked ~ .auth-pane-login {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(-10px);
        z-index: 1;
      }
      #tab-forgot:checked ~ .auth-pane-forgot {
        position: relative;
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
        z-index: 2;
      }

      /* Nút nhãn chuyển đổi dạng link / button thuần CSS */
      .btn-toggle-link {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        transition: color 0.15s ease;
      }
      .btn-toggle-link:hover {
        text-decoration: underline;
        color: #0369a1 !important;
      }
      label.btn {
        cursor: pointer;
        user-select: none;
      }
    </style>
    <!--end::Tùy chỉnh-->
  </head>

  <body class="login-page">
    <!--begin::Slideshow chuyển ảnh nền sân bay (Thuần CSS Animation)-->
    <div class="login-bg-slider" aria-hidden="true">
      <?php foreach ($bgImages as $idx => $bgUrl): ?>
        <div class="login-bg-slide" style="background-image: url('<?= htmlspecialchars($bgUrl, ENT_QUOTES, 'UTF-8') ?>'); animation-delay: <?= $idx * $thoiGianMoiAnh ?>s;"></div>
      <?php endforeach; ?>
      <div class="login-bg-overlay"></div>
    </div>
    <!--end::Slideshow chuyển ảnh nền-->

    <main class="login-box">
      <div class="card">
        <div class="card-body login-card-body">
          <!--begin::Logo đặt bên trong form (tĩnh, không có link trang chủ, không hover)-->
          <div class="auth-card-logo-wrap text-center">
            <img src="<?= $baseUrl ?>/assets/logo/logo.png" alt="AeroChain" class="auth-card-logo">
          </div>
          <!--end::Logo-->

          <?= $content ?>
        </div>
      </div>
    </main>
  </body>
</html>
