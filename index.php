<?php
/**
 * ============================================================
 * ĐIỂM VÀO (ENTRY POINT) CỦA HỆ THỐNG
 * File: index.php (nằm ở gốc dự án)
 * ------------------------------------------------------------
 * CÁCH TRUY CẬP:
 *   http://localhost/AirportChainManagement/            -> Trang tổng quan
 *   http://localhost/AirportChainManagement/index.php?page=ha_tang
 *   http://localhost/AirportChainManagement/index.php?page=chuyen_bay
 *   http://localhost/AirportChainManagement/index.php?page=bao_tri
 *   http://localhost/AirportChainManagement/index.php?page=ke_toan
 *   http://localhost/AirportChainManagement/index.php?page=login
 *   http://localhost/AirportChainManagement/index.php?page=register
 *   http://localhost/AirportChainManagement/index.php?page=forgot
 *
 * LƯU Ý QUAN TRỌNG: KHÔNG mở file .php bằng cách nháy đúp chuột
 * (địa chỉ file:///... sẽ chỉ hiện mã nguồn). Luôn mở qua
 * http://localhost/... để Apache chạy PHP.
 * ============================================================
 */

/* ---------- Nạp cấu hình dùng chung ---------- */
require_once __DIR__ . '/config/config.php';

/* ---------- Bảng điều hướng: ?page=key  ->  file view ---------- */
$routes = [
    // Thành viên 1 - Quản lý nhân sự & Xác thực
    'nhan_su'  => 'views/pages/nhan_su/index.php',
    'login'    => 'views/pages/auth/login.php',
    'forgot'   => 'views/pages/auth/forgot-password.php',
    'ho_so'    => 'views/pages/auth/ho_so.php',
    // Thành viên 2 - Hạ tầng
    'ha_tang'  => 'views/pages/ha_tang/index.php',
    // Thành viên 3 - Chuyến bay
    'chuyen_bay' => 'views/pages/chuyen_bay/index.php',
    // Thành viên 4 - Bảo trì
    'bao_tri'  => 'views/pages/bao_tri/index.php',
    // Thành viên 5 - Kế toán
    'ke_toan'  => 'views/pages/ke_toan/index.php',
];

$page = isset($_GET['page']) ? strtolower(trim((string) $_GET['page'])) : '';

/* ---------- Chặn đăng nhập (Middleware auth) ---------- */
$publicPages = ['login', 'forgot'];
if (!in_array($page, $publicPages, true)) {
    yeu_cau_dang_nhap();
}

/* Kiểm tra quyền truy cập theo vai trò (Role-based access control) */
if ($page !== '' && !in_array($page, $publicPages, true)) {
    if ($page === 'nhan_su' && !co_quyen_nhan_su()) {
        $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Quản lý nhân sự.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    if ($page === 'ke_toan' && !co_quyen_ke_toan()) {
        $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Kế toán & Doanh thu.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    if ($page === 'bao_tri' && !co_quyen_bao_tri()) {
        $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Kỹ thuật & Bảo trì.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/* Nếu có ?page=... và tồn tại trong bảng điều hướng thì nạp trang đó.
   Bản thân các file trong views/pages/ đã tự gọi layout nên chỉ cần require. */
if ($page !== '' && isset($routes[$page])) {
    $viewFile = __DIR__ . '/' . $routes[$page];
    if (is_file($viewFile)) {
        require $viewFile;
        exit;
    }
}

/* ---------- Mặc định: trang tổng quan ---------- */
$pageTitle  = 'Tổng quan hệ thống';
$pageHeader = 'Tổng quan hệ thống';
$navActive  = '';
$breadcrumb = [
    ['label' => 'Trang chủ', 'url' => null],
];

/* Danh sách phân hệ để in ra thành các thẻ điều hướng (lọc theo quyền) */
$rawModules = [
    [
        'key'   => 'ha_tang',
        'name'  => 'Quản lý hạ tầng',
        'desc'  => 'Nhà ga, đường băng, sân đỗ và trang thiết bị cố định.',
        'icon'  => 'bi-buildings',
        'color' => 'text-bg-primary',
        'owner' => 'Thành viên 2',
    ],
    [
        'key'   => 'chuyen_bay',
        'name'  => 'Quản lý chuyến bay',
        'desc'  => 'Lịch bay, giờ cất/hạ cánh, hãng hàng không, trạng thái chuyến.',
        'icon'  => 'bi-airplane-engines',
        'color' => 'text-bg-info',
        'owner' => 'Thành viên 3',
    ],
    [
        'key'   => 'bao_tri',
        'name'  => 'Bảo trì & khắc phục',
        'desc'  => 'Phiếu bảo trì, bảo dưỡng định kỳ và xử lý sự cố thiết bị.',
        'icon'  => 'bi-tools',
        'color' => 'text-bg-warning',
        'owner' => 'Thành viên 4',
    ],
    [
        'key'   => 'ke_toan',
        'name'  => 'Kế toán & doanh thu',
        'desc'  => 'Hóa đơn dịch vụ, doanh thu theo hãng bay và công nợ.',
        'icon'  => 'bi-cash-coin',
        'color' => 'text-bg-success',
        'owner' => 'Thành viên 5',
    ],
    [
        'key'   => 'nhan_su',
        'name'  => 'Quản lý nhân sự',
        'desc'  => 'Quản lý danh sách nhân sự, phân quyền vai trò, thay đổi mật khẩu và thông tin nhân viên.',
        'icon'  => 'bi-people-fill',
        'color' => 'text-bg-danger',
        'owner' => 'Thành viên 1',
    ],
];

$modules = [];
foreach ($rawModules as $m) {
    if ($m['key'] === 'nhan_su' && !co_quyen_nhan_su()) continue;
    if ($m['key'] === 'ke_toan' && !co_quyen_ke_toan()) continue;
    if ($m['key'] === 'bao_tri' && !co_quyen_bao_tri()) continue;
    if ($m['key'] === 'chuyen_bay' && (la_bao_tri() || la_ke_toan())) continue;
    $modules[] = $m;
}

ob_start();
?>
            <!--begin::Giới thiệu-->
            <div class="card mb-3">
              <div class="card-body">
                <h2 class="h4 mb-2">Hệ thống quản lý điều hành khai thác chuỗi cảng hàng không</h2>
                <p class="mb-0 text-secondary">
                  Ứng dụng web mô phỏng mô hình hoạt động của Tổng công ty Cảng hàng không Việt Nam (ACV).
                  Chọn một phân hệ bên dưới để bắt đầu. Giao diện sử dụng AdminLTE 4.
                </p>
              </div>
            </div>
            <!--end::Giới thiệu-->

            <!--begin::Thẻ phân hệ-->
            <div class="row">
              <?php foreach ($modules as $m): ?>
                <div class="col-lg-4 col-md-6 mb-3">
                  <div class="card h-100">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle <?= $m['color'] ?>" style="width: 48px; height: 48px">
                          <i class="bi <?= $m['icon'] ?> fs-4"></i>
                        </span>
                        <div class="ms-3">
                          <h3 class="card-title h6 mb-0"><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                          <span class="badge text-bg-secondary"><?= htmlspecialchars($m['owner'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </div>
                      <p class="text-secondary mb-3"><?= htmlspecialchars($m['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                      <a href="index.php?page=<?= $m['key'] ?>" class="btn btn-outline-primary btn-sm">
                        Mở phân hệ <i class="bi bi-arrow-right ms-1"></i>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <!--end::Thẻ phân hệ-->

            <!--begin::Hướng dẫn chạy-->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Hướng dẫn chạy dự án</h3>
              </div>
              <div class="card-body">
                <ol class="mb-0">
                  <li>Bật <strong>Apache</strong> trong XAMPP Control Panel.</li>
                  <li>
                    Mở trình duyệt tới
                    <code>http://localhost/AirportChainManagement/</code> (không nháy đúp vào file .php,
                    vì như vậy trình duyệt chỉ hiển thị mã nguồn).
                  </li>
                  <li>Dùng menu bên trái để đi tới từng phân hệ.</li>
                </ol>
              </div>
            </div>
            <!--end::Hướng dẫn chạy-->
<?php
$content = ob_get_clean();
require __DIR__ . '/views/layouts/main.php';