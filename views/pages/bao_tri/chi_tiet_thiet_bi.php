<?php
require_once __DIR__ . '/_bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Thiết bị cần xem không hợp lệ.';
    header('Location: ' . baoTriUrl('thiet_bi.php'));
    exit();
}

$thietBi = $baoTriController->getThietBiById($id);
if ($thietBi === null) {
    $_SESSION['flash_error'] = 'Không tìm thấy thiết bị cần xem.';
    header('Location: ' . baoTriUrl('thiet_bi.php'));
    exit();
}

$lichSu = $baoTriController->getLichSuBaoTri($id);

$statusLabels = [
    'Tot'     => 'Tốt',
    'Bao_Tri' => 'Đang bảo trì',
    'Hong'    => 'Hỏng',
];

$statusBadgeClasses = [
    'Tot'     => 'text-bg-success',
    'Bao_Tri' => 'text-bg-warning',
    'Hong'    => 'text-bg-danger',
];

$loaiThietBiLabels = [
    'Den_Tin_Hieu'       => 'Đèn tín hiệu',
    'Thiet_Bi_Dan_Duong' => 'Thiết bị dẫn đường',
    'Xe_Thang'           => 'Xe thang',
    'Xe_Keo'             => 'Xe kéo',
    'Khac'               => 'Khác',
];

$loaiThietBiIcons = [
    'Den_Tin_Hieu'       => 'bi-lightbulb',
    'Thiet_Bi_Dan_Duong' => 'bi-broadcast-pin',
    'Xe_Thang'           => 'bi-truck',
    'Xe_Keo'             => 'bi-truck',
    'Khac'               => 'bi-box-seam',
];

$loaiAvatarClasses = [
    'Den_Tin_Hieu'       => 'maint-avatar--warning',
    'Thiet_Bi_Dan_Duong' => 'maint-avatar--info',
    'Xe_Thang'           => 'maint-avatar--primary',
    'Xe_Keo'             => 'maint-avatar--success',
    'Khac'               => 'maint-avatar--secondary',
];

$priorityLabels = [
    'Thap'       => 'Thấp',
    'Trung_Binh' => 'Trung bình',
    'Khan_Cap'   => 'Khẩn cấp',
];

$priorityBadgeClasses = [
    'Thap'       => 'text-bg-success',
    'Trung_Binh' => 'text-bg-warning',
    'Khan_Cap'   => 'text-bg-danger',
];

$priorityIcons = [
    'Thap'       => 'bi-check-circle',
    'Trung_Binh' => 'bi-exclamation-circle',
    'Khan_Cap'   => 'bi-exclamation-octagon',
];

$ticketStatusLabels = [
    'Moi'        => 'Mới',
    'Dang_Xu_Ly' => 'Đang xử lý',
    'Hoan_Thanh' => 'Hoàn thành',
];

$ticketStatusBadgeClasses = [
    'Moi'        => 'text-bg-info',
    'Dang_Xu_Ly' => 'text-bg-primary',
    'Hoan_Thanh' => 'text-bg-success',
];

$statusIcons = [
    'Moi'        => 'bi-hourglass-split',
    'Dang_Xu_Ly' => 'bi-gear-wide-connected',
    'Hoan_Thanh' => 'bi-check2-circle',
];

$timelineDotClasses = [
    'Moi'        => 'maint-timeline-dot--info',
    'Dang_Xu_Ly' => 'maint-timeline-dot--warning',
    'Hoan_Thanh' => 'maint-timeline-dot--success',
];

$timelineCardClasses = [
    'Moi'        => 'maint-history-card--info',
    'Dang_Xu_Ly' => 'maint-history-card--warning',
    'Hoan_Thanh' => 'maint-history-card--success',
];

$pageTitle  = 'Chi tiết thiết bị';
$pageHeader = 'Chi tiết thiết bị';
$navActive    = 'bao_tri';
$navSubActive = 'equipment';
$breadcrumb = [
    ['label' => 'Tổng quan', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Bảo trì', 'url' => baoTriUrl('index.php')],
    ['label' => 'Trang thiết bị', 'url' => baoTriUrl('thiet_bi.php')],
    ['label' => 'Chi tiết', 'url' => null],
];

$statusLabel = $statusLabels[$thietBi['trang_thai']] ?? htmlspecialchars((string) $thietBi['trang_thai'], ENT_QUOTES, 'UTF-8');
$statusClass = $statusBadgeClasses[$thietBi['trang_thai']] ?? 'text-bg-secondary';
$loaiLabel   = $loaiThietBiLabels[$thietBi['loai_thiet_bi']] ?? htmlspecialchars(str_replace('_', ' ', (string) $thietBi['loai_thiet_bi']), ENT_QUOTES, 'UTF-8');
$loaiIcon    = $loaiThietBiIcons[$thietBi['loai_thiet_bi']] ?? 'bi-hdd-stack';
$loaiAvatarClass = $loaiAvatarClasses[$thietBi['loai_thiet_bi']] ?? 'maint-avatar--secondary';

$soPhieu       = count($lichSu);
$soPhieuMoi    = 0;
$soDangXuLy    = 0;
$soHoanThanh   = 0;
foreach ($lichSu as $ticketItem) {
    $trangThai = (string) ($ticketItem['trang_thai_xu_ly'] ?? '');
    if ($trangThai === 'Moi') {
        $soPhieuMoi++;
    } elseif ($trangThai === 'Dang_Xu_Ly') {
        $soDangXuLy++;
    } elseif ($trangThai === 'Hoan_Thanh') {
        $soHoanThanh++;
    }
}
$soPhieuMo = $soPhieuMoi + $soDangXuLy;
$lichSuHoanThanh = array_values(array_filter(
    $lichSu,
    static fn (array $ticket): bool => ($ticket['trang_thai_xu_ly'] ?? '') === 'Hoan_Thanh'
));
$soLichSuHoanThanh = count($lichSuHoanThanh);
$lastTicket = $lichSu[0] ?? null;
$lastDate = $lastTicket !== null ? date('d/m/Y H:i', strtotime($lastTicket['ngay_tao'])) : 'Chưa có';

$createTicketUrl = baoTriUrl('index.php', ['open' => 'create', 'id_thiet_bi' => $id]) . '#taoPhieuModal';
$editUrl         = baoTriUrl('thiet_bi.php', ['sua' => $id]);
$deleteUrl       = baoTriUrl('thiet_bi.php', ['xac_nhan_xoa' => $id]);
$ticketListUrl   = baoTriUrl('index.php', ['thiet_bi' => $id]);
$backUrl         = baoTriUrl('thiet_bi.php');

ob_start();
?>
<?php require __DIR__ . '/_flash.php'; ?>
<div class="maint-page maint-page--equipment maint-detail-page">
    <div class="maint-equipment-detail__header">
        <div class="maint-equipment-detail__identity">
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary align-self-start">
                <i class="bi bi-arrow-left me-1"></i>Danh sách thiết bị
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="maint-avatar <?= $loaiAvatarClass ?> maint-detail-avatar">
                    <i class="bi <?= $loaiIcon ?>"></i>
                </span>
                <div class="maint-equipment-detail__title">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="small text-secondary maint-code">TB-<?= sprintf('%03d', (int) $thietBi['id']) ?></span>
                        <span class="badge text-bg-light border"><?= $loaiLabel ?></span>
                    </div>
                    <h2 class="h4 mb-2 text-break"><?= htmlspecialchars($thietBi['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <span class="badge <?= $statusClass ?>">
                        <i class="bi <?= $thietBi['trang_thai'] === 'Tot' ? 'bi-check-circle' : ($thietBi['trang_thai'] === 'Bao_Tri' ? 'bi-tools' : 'bi-exclamation-triangle') ?> me-1"></i><?= $statusLabel ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="maint-equipment-detail__actions">
            <a href="<?= htmlspecialchars($createTicketUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Tạo phiếu bảo trì
            </a>
            <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-pencil-square me-1"></i>Sửa thiết bị
            </a>
            <?php if ($soPhieu === 0): ?>
                <a href="<?= htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash3 me-1"></i>Xóa thiết bị
                </a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary disabled" title="Không thể xóa vì thiết bị đã có phiếu bảo trì">
                    <i class="bi bi-lock me-1"></i>Đã có phiếu
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card h-100 maint-equipment-detail__card">
                <div class="card-header maint-equipment-detail__card-header">
                    <h3 class="card-title"><i class="bi bi-info-circle me-2"></i>Thông tin thiết bị</h3>
                </div>
                <div class="card-body">
                    <div class="maint-equipment-detail__location">
                        <span class="maint-equipment-detail__location-icon"><i class="bi bi-geo-alt"></i></span>
                        <div>
                            <div class="small text-secondary">Sân bay</div>
                            <strong><?= htmlspecialchars($thietBi['ten_san_bay'] ?? 'Chưa xác định', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <div class="list-group list-group-flush small">
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-tags me-1"></i>Phân loại</span>
                            <strong class="text-end"><?= $loaiLabel ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-clipboard-check me-1"></i>Tổng phiếu</span>
                            <strong><?= $soPhieu ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-funnel me-1"></i>Đang mở</span>
                            <strong class="<?= $soPhieuMo > 0 ? 'text-danger' : '' ?>"><?= $soPhieuMo ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-check2-circle me-1"></i>Hoàn thành</span>
                            <strong class="text-success"><?= $soHoanThanh ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-clock-history me-1"></i>Gần nhất</span>
                            <strong class="text-end"><?= $lastDate ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card h-100 maint-equipment-detail__card">
                <div class="card-header maint-equipment-detail__card-header">
                    <h3 class="card-title">
                        <i class="bi bi-clock-history me-2"></i>Lịch sử bảo trì
                        <span class="badge text-bg-secondary ms-1"><?= $soLichSuHoanThanh ?></span>
                    </h3>
                    <div class="card-tools">
                        <a href="<?= htmlspecialchars($ticketListUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list-ul me-1"></i>Xem trong danh sách phiếu
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (empty($lichSuHoanThanh)): ?>
                        <div class="text-center py-5">
                            <div class="maint-empty-icon"><i class="bi bi-clipboard-x"></i></div>
                            <div class="fw-semibold">Chưa có phiếu bảo trì hoàn thành</div>
                            <div class="small text-secondary mb-3">Các phiếu sẽ xuất hiện tại đây sau khi hoàn tất xử lý.</div>
                            <a href="<?= htmlspecialchars($createTicketUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>Tạo phiếu bảo trì
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="maint-timeline">
                            <?php foreach ($lichSuHoanThanh as $ticket): ?>
                                <?php
                                $maPhieu = '#BT-' . sprintf('%03d', (int) $ticket['id']);
                                $ngayTao = date('d/m/Y H:i', strtotime($ticket['ngay_tao']));
                                $mucDo = (string) $ticket['muc_do'];
                                $trangThai = (string) $ticket['trang_thai_xu_ly'];
                                $priorityLabel = $priorityLabels[$mucDo] ?? htmlspecialchars($mucDo, ENT_QUOTES, 'UTF-8');
                                $priorityClass = $priorityBadgeClasses[$mucDo] ?? 'text-secondary bg-body-tertiary border-secondary-subtle';
                                $priorityIcon = $priorityIcons[$mucDo] ?? 'bi-circle';
                                $ticketStatusLabel = $ticketStatusLabels[$trangThai] ?? htmlspecialchars($trangThai, ENT_QUOTES, 'UTF-8');
                                $ticketStatusClass = $ticketStatusBadgeClasses[$trangThai] ?? 'text-secondary bg-body-tertiary border-secondary-subtle';
                                $ticketStatusIcon = $statusIcons[$trangThai] ?? 'bi-circle';
                                $timelineDotClass = $timelineDotClasses[$trangThai] ?? 'maint-timeline-dot--secondary';
                                $historyCardClass = $timelineCardClasses[$trangThai] ?? 'maint-history-card--secondary';
                                $anhUrl = baoTriImageUrl($ticket['hinh_anh_minh_hoa'] ?? null, $ticket['mo_ta_su_co'] ?? '');
                                ?>
                                <div class="maint-timeline-item">
                                    <span class="maint-timeline-dot <?= $timelineDotClass ?>">
                                        <i class="bi <?= $ticketStatusIcon ?>"></i>
                                    </span>
                                    <div class="maint-timeline-content maint-history-card <?= $historyCardClass ?>">
                                        <div class="maint-history-card__header">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="maint-history-code maint-code"><?= $maPhieu ?></span>
                                                <span class="badge <?= $ticketStatusClass ?>"><?= $ticketStatusLabel ?></span>
                                            </div>
                                            <div class="small text-secondary text-nowrap">
                                                <i class="bi bi-clock me-1"></i><?= $ngayTao ?>
                                            </div>
                                        </div>

                                        <div class="maint-history-card__body">
                                            <p class="mb-2 text-break"><?= nl2br(htmlspecialchars($ticket['mo_ta_su_co'], ENT_QUOTES, 'UTF-8')) ?></p>

                                            <?php if ($anhUrl !== ''): ?>
                                                <a href="#anh-su-co-<?= (int) $ticket['id'] ?>" class="d-inline-block mb-1" title="Xem ảnh minh họa">
                                                    <img src="<?= htmlspecialchars($anhUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Ảnh sự cố của phiếu <?= htmlspecialchars($maPhieu, ENT_QUOTES, 'UTF-8') ?>" class="rounded border maint-history-thumb maint-thumb">
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <div class="maint-history-card__footer">
                                            <span class="badge <?= $priorityClass ?>">
                                                <i class="bi <?= $priorityIcon ?> me-1"></i><?= $priorityLabel ?>
                                            </span>
                                            <a href="<?= htmlspecialchars(baoTriUrl('index.php', ['q' => $maPhieu, 'trang_thai' => 'Hoan_Thanh']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i>Xem phiếu
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Lightbox cho ảnh sự cố trong lịch sử bảo trì (mở bằng :target, đóng bằng nút X).
$lightboxImages = [];
foreach (($lichSu ?? []) as $ticketAnh) {
    $urlAnh = baoTriImageUrl($ticketAnh['hinh_anh_minh_hoa'] ?? null, $ticketAnh['mo_ta_su_co'] ?? '');
    if ($urlAnh === '') {
        continue;
    }
    $lightboxImages[] = [
        'id'  => 'anh-su-co-' . (int) $ticketAnh['id'],
        'url' => $urlAnh,
        'alt' => 'Ảnh sự cố của phiếu #BT-' . sprintf('%03d', (int) $ticketAnh['id']),
    ];
}
require __DIR__ . '/_lightbox.php';
?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
