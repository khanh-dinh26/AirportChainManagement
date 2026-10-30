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
    'Tot'     => 'text-success-emphasis bg-success-subtle border-success-subtle',
    'Bao_Tri' => 'text-warning-emphasis bg-warning-subtle border-warning-subtle',
    'Hong'    => 'text-danger-emphasis bg-danger-subtle border-danger-subtle',
];

$statusDotClasses = [
    'Tot'     => 'bg-success',
    'Bao_Tri' => 'bg-warning',
    'Hong'    => 'bg-danger',
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
    'Thap'       => 'text-success-emphasis bg-success-subtle border-success-subtle',
    'Trung_Binh' => 'text-warning-emphasis bg-warning-subtle border-warning-subtle',
    'Khan_Cap'   => 'text-danger-emphasis bg-danger-subtle border-danger-subtle',
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
    'Moi'        => 'text-info-emphasis bg-info-subtle border-info-subtle',
    'Dang_Xu_Ly' => 'text-primary-emphasis bg-primary-subtle border-primary-subtle',
    'Hoan_Thanh' => 'text-success-emphasis bg-success-subtle border-success-subtle',
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
$statusClass = $statusBadgeClasses[$thietBi['trang_thai']] ?? 'text-secondary bg-body-tertiary border-secondary-subtle';
$statusDot   = $statusDotClasses[$thietBi['trang_thai']] ?? 'bg-secondary';
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
$tyLeHoanThanh = $soPhieu > 0 ? min(100, (int) round(($soHoanThanh / $soPhieu) * 100)) : 0;
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
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left me-1"></i>Danh sách thiết bị
            </a>
            <h2 class="h4 mb-1"><?= htmlspecialchars($thietBi['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="small text-secondary maint-code">TB-<?= sprintf('%03d', (int) $thietBi['id']) ?> · <?= $loaiLabel ?></div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
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
            <div class="card maint-list-card maint-detail-card maint-fade-up h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="maint-avatar <?= $loaiAvatarClass ?> maint-detail-avatar">
                            <i class="bi <?= $loaiIcon ?>"></i>
                        </span>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="small text-secondary maint-code">TB-<?= sprintf('%03d', (int) $thietBi['id']) ?></div>
                            <h3 class="h5 mb-1 text-break"><?= htmlspecialchars($thietBi['ten_thiet_bi'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="badge rounded-pill border <?= $statusClass ?>">
                                <span class="d-inline-block rounded-circle <?= $statusDot ?><?= $thietBi['trang_thai'] === 'Bao_Tri' ? ' maint-pulse' : '' ?> me-1" style="width: 6px; height: 6px;"></span>
                                <?= $statusLabel ?>
                            </span>
                        </div>
                    </div>

                    <div class="list-group list-group-flush small">
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-geo-alt me-1"></i>Sân bay</span>
                            <strong class="text-end"><?= htmlspecialchars($thietBi['ten_san_bay'] ?? 'Chưa xác định', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-tags me-1"></i>Phân loại</span>
                            <strong class="text-end"><?= $loaiLabel ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-clipboard-check me-1"></i>Tổng phiếu</span>
                            <strong><?= $soPhieu ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-funnel me-1"></i>Đang mở</span>
                            <strong class="<?= $soPhieuMo > 0 ? 'text-danger' : '' ?>"><?= $soPhieuMo ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-check2-circle me-1"></i>Hoàn thành</span>
                            <strong class="text-success"><?= $soHoanThanh ?></strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 px-0">
                            <span class="text-secondary"><i class="bi bi-clock-history me-1"></i>Gần nhất</span>
                            <strong class="text-end"><?= $lastDate ?></strong>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center small text-secondary mb-1">
                            <span>Tỷ lệ hoàn thành bảo trì</span>
                            <strong><?= $tyLeHoanThanh ?>%</strong>
                        </div>
                        <div class="maint-progress">
                            <div class="maint-progress-bar" style="width: <?= $tyLeHoanThanh ?>%"></div>
                        </div>
                    </div>

                    <div class="alert alert-light border small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Trạng thái thiết bị được cập nhật tự động theo tiến độ các phiếu bảo trì.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card maint-list-card maint-fade-up h-100">
                <div class="card-header border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="maint-section-icon">
                            <i class="bi bi-clock-history"></i>
                        </span>
                        <div>
                            <h3 class="card-title mb-0 float-none">Lịch sử bảo trì</h3>
                            <div class="small text-secondary mt-1">
                                <?= $soPhieu > 0 ? $soPhieu . ' phiếu bảo trì gắn với thiết bị này' : 'Các phiếu bảo trì gắn với thiết bị này' ?>
                            </div>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($ticketListUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-list-ul me-1"></i>Xem trong danh sách phiếu
                    </a>
                </div>

                <div class="card-body">
                    <?php if (empty($lichSu)): ?>
                        <div class="text-center py-5">
                            <div class="maint-empty-icon"><i class="bi bi-clipboard-x"></i></div>
                            <div class="fw-semibold">Chưa có lịch sử bảo trì</div>
                            <div class="small text-secondary mb-3">Tạo phiếu đầu tiên cho thiết bị này để theo dõi quá trình xử lý.</div>
                            <a href="<?= htmlspecialchars($createTicketUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>Tạo phiếu bảo trì
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="maint-history-summary">
                            <div class="maint-history-summary__item maint-history-summary__item--info">
                                <span class="maint-history-summary__icon"><i class="bi bi-hourglass-split"></i></span>
                                <div>
                                    <div class="maint-history-summary__value"><?= $soPhieuMoi ?></div>
                                    <div class="maint-history-summary__label">Mới</div>
                                </div>
                            </div>
                            <div class="maint-history-summary__item maint-history-summary__item--warning">
                                <span class="maint-history-summary__icon"><i class="bi bi-gear-wide-connected"></i></span>
                                <div>
                                    <div class="maint-history-summary__value"><?= $soDangXuLy ?></div>
                                    <div class="maint-history-summary__label">Đang xử lý</div>
                                </div>
                            </div>
                            <div class="maint-history-summary__item maint-history-summary__item--success">
                                <span class="maint-history-summary__icon"><i class="bi bi-check2-circle"></i></span>
                                <div>
                                    <div class="maint-history-summary__value"><?= $soHoanThanh ?></div>
                                    <div class="maint-history-summary__label">Hoàn thành</div>
                                </div>
                            </div>
                        </div>

                        <div class="maint-timeline">
                            <?php foreach ($lichSu as $ticket): ?>
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
                                                <span class="badge rounded-pill border <?= $ticketStatusClass ?>"><?= $ticketStatusLabel ?></span>
                                            </div>
                                            <div class="small text-secondary text-nowrap">
                                                <i class="bi bi-clock me-1"></i><?= $ngayTao ?>
                                            </div>
                                        </div>

                                        <div class="maint-history-card__body">
                                            <p class="mb-2 text-break"><?= nl2br(htmlspecialchars($ticket['mo_ta_su_co'], ENT_QUOTES, 'UTF-8')) ?></p>

                                            <?php if ($anhUrl !== ''): ?>
                                                <a href="<?= $anhUrl ?>" target="_blank" rel="noopener" class="d-inline-block mb-1" title="Xem ảnh minh họa">
                                                    <img src="<?= $anhUrl ?>" alt="Ảnh sự cố" class="rounded border maint-history-thumb maint-thumb">
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <div class="maint-history-card__footer">
                                            <span class="badge rounded-pill border <?= $priorityClass ?>">
                                                <i class="bi <?= $priorityIcon ?> me-1"></i><?= $priorityLabel ?>
                                            </span>
                                            <a href="<?= htmlspecialchars(baoTriUrl('index.php', ['q' => $maPhieu]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Mở phiếu
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
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
