<?php
/**
 * ============================================================
 * LAYOUT: SIDEBAR (AdminLTE 4)
 * ------------------------------------------------------------
 * Menu chính của hệ thống, chia theo 5 phân hệ tương ứng 5
 * thành viên thực hiện. Biến $navActive dùng để tô đậm mục
 * đang mở (nhận 1 trong các giá trị: ha_tang, chuyen_bay,
 * bao_tri, ke_toan, auth).
 *
 * Giao diện giữ nguyên sidebar tối mặc định của AdminLTE 4.
 * Menu con dùng <details>/<summary> — mở/đóng bằng CSS,
 * KHÔNG cần JavaScript.
 * ============================================================
 */

$navActive    = $navActive ?? '';
$navSubActive = $navSubActive ?? '';

// Nạp cấu hình dùng chung (BASE_URL, ADMINLTE_URL)
require_once __DIR__ . '/../../config/config.php';
$baseUrl     = $baseUrl ?? BASE_URL;
$adminLteUrl = $adminLteUrl ?? ADMINLTE_URL;

/** Tiện ích: trả về ' active' nếu menu đang được chọn */
$isActive = static function (string $key) use ($navActive): string {
    return $navActive === $key ? ' active' : '';
};

/** Tiện ích: trả về ' active' nếu mục con đang được chọn */
$isSubActive = static function (string $key) use ($navSubActive): string {
    return $navSubActive === $key ? ' active' : '';
};
?>
<!--begin::Sidebar-->
<aside class="app-sidebar bg-white shadow-sm">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <a href="<?= $baseUrl ?>/index.php" class="brand-link">
      <img
        src="<?= $baseUrl ?>/assets/logo/logo.png"
        alt="AeroChain Logo"
        class="brand-image"
        style="max-height: 34px; width: auto; object-fit: contain;"
      />
      <span class="brand-text fw-bold">AeroChain</span>
    </a>
  </div>
  <!--end::Sidebar Brand-->

  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2" aria-label="Menu chính">
      <ul class="nav sidebar-menu flex-column" id="navigation">
        <li class="nav-item">
          <a href="<?= $baseUrl ?>/index.php" class="nav-link<?= $navActive === '' ? ' active' : '' ?>">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Tổng quan</p>
          </a>
        </li>

        <li class="nav-header">PHÂN HỆ CHỨC NĂNG</li>

        <?php if (co_quyen_nhan_su()): ?>
        <!--Thành viên 1 - Quản lý nhân sự-->
        <li class="nav-item">
          <a href="<?= $baseUrl ?>/index.php?page=nhan_su" class="nav-link<?= $isActive('nhan_su') ?>">
            <i class="nav-icon bi bi-people-fill"></i>
            <p>Quản lý nhân sự</p>
          </a>
        </li>
        <?php endif; ?>

        <?php if (co_quyen_ha_tang()): ?>
        <!--Thành viên 2 - Hạ tầng-->
        <li class="nav-item">
          <details class="nav-group<?= $navActive === 'ha_tang' ? ' is-active' : '' ?>"<?= $navActive === 'ha_tang' ? ' open' : '' ?>>
            <summary class="nav-link<?= $isActive('ha_tang') ?>">
              <i class="nav-icon bi bi-buildings"></i>
              <p>
                Quản lý hạ tầng
                <i class="nav-arrow bi bi-chevron-right"></i>
              </p>
            </summary>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/index.php?page=ha_tang" class="nav-link<?= $navActive === 'ha_tang' && ($navSubActive === 'san_bay' || empty($navSubActive)) ? ' active' : '' ?>">
                  <i class="nav-icon bi bi-geo-alt"></i>
                  <p>Quản lý sân bay</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/views/pages/bao_tri/hang_hang_khong.php" class="nav-link<?= $isSubActive('airlines') ?>">
                  <i class="nav-icon bi bi-building"></i>
                  <p>Quản lý hãng hàng không</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/views/pages/bao_tri/tau_bay.php" class="nav-link<?= $isSubActive('aircraft_types') ?>">
                  <i class="nav-icon bi bi-airplane"></i>
                  <p>Quản lý loại tàu bay</p>
                </a>
              </li>
            </ul>
          </details>
        </li>
        <?php endif; ?>

        <?php if (!la_bao_tri() && !la_ke_toan()): ?>
        <!--Thành viên 3 - Chuyến bay-->
        <li class="nav-item">
          <a href="<?= $baseUrl ?>/index.php?page=chuyen_bay" class="nav-link<?= $isActive('chuyen_bay') ?>">
            <i class="nav-icon bi bi-airplane-engines"></i>
            <p>Quản lý chuyến bay</p>
          </a>
        </li>
        <?php endif; ?>

        <?php if (co_quyen_bao_tri()): ?>
        <!--Thành viên 4 - Bảo trì-->
        <li class="nav-item">
          <details class="nav-group<?= $navActive === 'bao_tri' ? ' is-active' : '' ?>"<?= $navActive === 'bao_tri' ? ' open' : '' ?>>
            <summary class="nav-link<?= $isActive('bao_tri') ?>">
              <i class="nav-icon bi bi-tools"></i>
              <p>
                Kỹ thuật &amp; Bảo trì
                <i class="nav-arrow bi bi-chevron-right"></i>
              </p>
            </summary>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/index.php?page=bao_tri" class="nav-link<?= $isSubActive('tickets') ?>">
                  <i class="nav-icon bi bi-clipboard-check"></i>
                  <p>Danh sách phiếu bảo trì</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/views/pages/bao_tri/thiet_bi.php" class="nav-link<?= $isSubActive('equipment') ?>">
                  <i class="nav-icon bi bi-hdd-stack"></i>
                  <p>Quản lý trang thiết bị</p>
                </a>
              </li>
            </ul>
          </details>
        </li>
        <?php endif; ?>

        <?php if (co_quyen_ke_toan()): ?>
        <!--Thành viên 5 - Kế toán-->
        <li class="nav-item">
          <details class="nav-group<?= $navActive === 'ke_toan' ? ' is-active' : '' ?>"<?= $navActive === 'ke_toan' ? ' open' : '' ?>>
            <summary class="nav-link<?= $isActive('ke_toan') ?>">
              <i class="nav-icon bi bi-cash-coin"></i>
              <p>
                Kế toán &amp; doanh thu
                <i class="nav-arrow bi bi-chevron-right"></i>
              </p>
            </summary>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/index.php?page=ke_toan" class="nav-link<?= $navActive === 'ke_toan' && empty($_GET['view']) ? ' active' : '' ?>">
                  <i class="nav-icon bi bi-wallet2"></i>
                  <p>Thu phí &amp; Hóa đơn</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/index.php?page=ke_toan&view=lich_su" class="nav-link<?= $navActive === 'ke_toan' && (isset($_GET['view']) && $_GET['view'] === 'lich_su') ? ' active' : '' ?>">
                  <i class="nav-icon bi bi-clock-history"></i>
                  <p>Lịch sử &amp; Sản lượng</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= $baseUrl ?>/index.php?page=ke_toan&view=thong_ke" class="nav-link<?= $navActive === 'ke_toan' && (isset($_GET['view']) && $_GET['view'] === 'thong_ke') ? ' active' : '' ?>">
                  <i class="nav-icon bi bi-bar-chart-line"></i>
                  <p>Thống kê doanh thu</p>
                </a>
              </li>
            </ul>
          </details>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->
