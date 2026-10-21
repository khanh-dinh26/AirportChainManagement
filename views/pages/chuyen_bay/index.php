<?php
$pageTitle  = 'Quản lý chuyến bay';
$pageHeader = 'Quản lý chuyến bay';
$navActive  = 'chuyen_bay';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Chuyến bay', 'url' => null],
];
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$statusLabels = [
    'Len_Lich' => ['Chuyến bay đang mở', 'primary'],
    'Cat_Canh' => ['Cất cánh', 'info'],
    'Ha_Canh' => ['Hạ cánh', 'success'],
    'Tri_Hoan' => ['Trễ', 'warning'],
    'Huy' => ['Hủy', 'danger'],
];
$flashMessages = [
    'created' => 'Đã thêm chuyến bay.',
    'updated' => 'Đã cập nhật chuyến bay.',
    'cancelled' => 'Đã hủy chuyến bay.',
    'deleted' => 'Đã xóa chuyến bay.',
    'lich_mua_created' => 'Đã thêm lịch bay mùa.',
    'lich_mua_deleted' => 'Đã xóa lịch bay mùa.',
    'generated' => 'Đã tạo ' . (int) ($_GET['count'] ?? 0) . ' chuyến bay từ lịch mùa.',
];

ob_start();
?>
            <?php if (isset($flashMessages[$_GET['msg'] ?? ''])): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $escape($flashMessages[$_GET['msg']]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
              </div>
            <?php endif; ?>

            <div class="row">
              <?php
              $statCards = [
                  ['value' => $stats['total'] ?? 0, 'label' => 'Chuyến bay hôm nay', 'icon' => 'bi-airplane-engines', 'color' => 'primary'],
                  ['value' => $stats['dep'] ?? 0, 'label' => 'Chuyến khởi hành', 'icon' => 'bi-box-arrow-up-right', 'color' => 'info'],
                  ['value' => $stats['arr'] ?? 0, 'label' => 'Hạ cánh', 'icon' => 'bi-box-arrow-in-down', 'color' => 'success'],
                  ['value' => $stats['delayed'] ?? 0, 'label' => 'Chuyến bị chậm', 'icon' => 'bi-clock-history', 'color' => 'warning'],
              ];
              foreach ($statCards as $card):
              ?>
                <div class="col-lg-3 col-6">
                  <div class="small-box text-bg-<?= $card['color'] ?>">
                    <div class="inner">
                      <h3><?= (int) $card['value'] ?></h3>
                      <p><?= $escape($card['label']) ?></p>
                    </div>
                    <i class="small-box-icon bi <?= $card['icon'] ?>"></i>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách chuyến bay</h3>
                <div class="card-tools d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddFlight">
                    <i class="bi bi-plus-lg me-1"></i> Thêm chuyến bay
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalLichMua">
                    <i class="bi bi-calendar-range me-1"></i> Lịch bay mùa
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalBatchGenerate">
                    <i class="bi bi-cpu me-1"></i> Sinh chuyến bay
                  </button>
                </div>
              </div>
              <div class="card-body">
                <?php require __DIR__ . '/timkiem.php'; ?>

                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead>
                      <tr>
                        <th>Mã CB</th>
                        <th>Hãng</th>
                        <th>Điểm đi</th>
                        <th>Điểm đến</th>
                        <th>Giờ dự kiến</th>
                        <th>Giờ thực tế</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($chuyenBays)): ?>
                        <?php foreach ($chuyenBays as $cb): ?>
                          <?php
                          [$statusLabel, $statusColor] = $statusLabels[$cb['trang_thai_tong']] ?? [$cb['trang_thai_tong'], 'secondary'];
                          ?>
                          <tr>
                            <td class="fw-semibold"><?= $escape($cb['so_hieu']) ?></td>
                            <td><?= $escape($cb['ten_hang'] ?? '') ?></td>
                            <td><?= $escape($cb['ma_sb_di'] ?? '') ?></td>
                            <td><?= $escape($cb['ma_sb_den'] ?? '') ?></td>
                            <td><?= $escape(date('d/m/Y H:i', strtotime($cb['gio_du_kien']))) ?></td>
                            <td>
                              <?= !empty($cb['gio_thuc_te']) ? $escape(date('d/m/Y H:i', strtotime($cb['gio_thuc_te']))) : '—' ?>
                            </td>
                            <td><span class="badge text-bg-<?= $statusColor ?>"><?= $escape($statusLabel) ?></span></td>
                            <td class="text-end text-nowrap">
                              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit_<?= (int) $cb['id'] ?>">
                                Sửa
                              </button>
                              <?php if ($cb['trang_thai_tong'] !== 'Huy'): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancel_<?= (int) $cb['id'] ?>">
                                  Hủy
                                </button>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="8" class="text-center text-muted py-4">Không tìm thấy chuyến bay phù hợp.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer clearfix">
                <nav aria-label="Phân trang chuyến bay">
                  <ul class="pagination pagination-sm m-0 float-end">
                    <?php $previousPage = max(1, $page - 1); ?>
                    <?php $previousQuery = [
                        'controller' => 'chuyenbay',
                        'action' => 'index',
                        'keyword' => $keyword,
                        'date' => $date,
                        'status' => $status,
                        'page' => $previousPage,
                    ]; ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                      <?php if ($page <= 1): ?>
                        <span class="page-link">Trước</span>
                      <?php else: ?>
                        <a class="page-link" href="index.php?<?= $escape(http_build_query($previousQuery)) ?>">Trước</a>
                      <?php endif; ?>
                    </li>
                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                      <?php
                      $pageQuery = [
                          'controller' => 'chuyenbay',
                          'action' => 'index',
                          'keyword' => $keyword,
                          'date' => $date,
                          'status' => $status,
                          'page' => $pageNumber,
                      ];
                      ?>
                      <li class="page-item <?= $pageNumber === (int) $page ? 'active' : '' ?>">
                        <?php if ($pageNumber === (int) $page): ?>
                          <span class="page-link" aria-current="page"><?= $pageNumber ?></span>
                        <?php else: ?>
                          <a class="page-link" href="index.php?<?= $escape(http_build_query($pageQuery)) ?>"><?= $pageNumber ?></a>
                        <?php endif; ?>
                      </li>
                    <?php endfor; ?>
                    <?php $nextPage = min($totalPages, $page + 1); ?>
                    <?php $nextQuery = [
                        'controller' => 'chuyenbay',
                        'action' => 'index',
                        'keyword' => $keyword,
                        'date' => $date,
                        'status' => $status,
                        'page' => $nextPage,
                    ]; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                      <?php if ($page >= $totalPages): ?>
                        <span class="page-link">Sau</span>
                      <?php else: ?>
                        <a class="page-link" href="index.php?<?= $escape(http_build_query($nextQuery)) ?>">Sau</a>
                      <?php endif; ?>
                    </li>
                  </ul>
                </nav>
              </div>
            </div>

            <?php
            require __DIR__ . '/themmoi.php';
            require __DIR__ . '/sua.php';
            require __DIR__ . '/huy.php';
            require __DIR__ . '/lich_mua_modal.php';
            ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
