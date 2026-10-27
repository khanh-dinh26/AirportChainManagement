<?php
if (!$selectedAirport) {
    return;
}

$airport = $selectedAirport;
$terminal = $selectedTerminal;
$terminalUrl = static function ($terminalId) use ($url, $airport) {
    return $url([
        'san_bay' => $airport['ma_iata'],
        'khu' => 'nha_ga',
        'nha_ga' => (int) $terminalId,
    ]);
};
$manageTerminalParams = ['quan_tri' => '1', 'tab' => 'nha_ga', 'san_bay' => $airport['ma_iata']];
if ($terminal) {
    $manageTerminalParams['nha_ga'] = (int) $terminal['id'];
}
$manageTerminalUrl = $url($manageTerminalParams);
$manageGatesUrl = $url(['quan_tri' => '1', 'tab' => 'cua_ra', 'san_bay' => $airport['ma_iata'], 'nha_ga' => $terminal['id'] ?? 0]);
$manageCheckinsUrl = $url(['quan_tri' => '1', 'tab' => 'quay', 'san_bay' => $airport['ma_iata'], 'nha_ga' => $terminal['id'] ?? 0]);
$manageBeltsUrl = $url(['quan_tri' => '1', 'tab' => 'bang_chuyen', 'san_bay' => $airport['ma_iata'], 'nha_ga' => $terminal['id'] ?? 0]);
$diagramImage = $terminal ? $imageUrl($terminal['so_do'] ?? '') : '';

$diagramCaptions = [
    'HAN-T1-01.jpg' => 'Tầng 1 · Sảnh E',
    'HAN-T1-02.jpg' => 'Tầng 2 · Sảnh E',
    'HAN-T1-03.jpg' => 'Tầng 1 · Toàn nhà ga',
    'HAN-T1-04.jpg' => 'Tầng 2 · Toàn nhà ga',
    'HAN-T1-05.jpg' => 'Tầng 3 · Phòng chờ',
    'HAN-T1-06.jpg' => 'Tầng 4 · Nhà hàng',
    'HAN-T2-01.jpg' => 'Tầng 1 · Quốc tế đến',
    'HAN-T2-02.jpg' => 'Tầng 2 · Mặt bằng tổng thể',
    'HAN-T2-03.jpg' => 'Tầng 3 · Quầy thủ tục',
    'HAN-T2-04.jpg' => 'Tầng 3 · Lối thoát hiểm',
    'HAN-T2-05.jpg' => 'Tầng 3 · Luồng thoát hiểm',
    'HAN-T2-06.jpg' => 'Tầng 3 · Cửa ra tàu bay',
    'HAN-T2-07.jpg' => 'Tầng 4 · Phòng chờ thương gia',
    'CXR-T1-01.jpg' => 'Sơ đồ các tầng nhà ga',
    'CXR-T2-01.jpg' => 'Mặt bằng hiện trạng tầng 1 & tầng 2',
    'VCA-T1-01.jpg' => 'Tầng 1 · Ga đi',
    'HPH-T1-01.jpg' => 'Sơ đồ tổng thể nhà ga hành khách',
    'PQC-T1-01.jpg' => 'Tầng trệt · Ga đến và trả hành lý',
    'PQC-T1-02.jpg' => 'Tầng 1 · Ga đi và check-in',
    'SGN-T1-01.jpg' => 'Ga đi quốc nội · Tầng 1 & tầng trệt',
    'SGN-T1-02.jpg' => 'Ga đến quốc nội · Tầng trệt',
    'SGN-T2-01.jpg' => 'Ga đi quốc tế · Tầng 1, 2 & 3',
    'SGN-T2-02.jpg' => 'Ga đến quốc tế · Tầng trệt & tầng 1',
    'SGN-T3-01.jpg' => 'Tầng 2 · Mặt bằng nhà ga',
    'SGN-T3-02.jpg' => 'Tầng 3 · Mặt bằng nhà ga',
    'SGN-T3-03.jpg' => 'Tầng 4 · Phòng chờ thương gia',
    'DAD-T1-01.jpg' => 'Ga đến · Tầng trệt',
    'DAD-T3-01.jpg' => 'Ga đi · Tầng trệt',
    'UIH-T1-01.jpg' => 'Tầng trệt · Mặt bằng nhà ga',
    'UIH-T1-02.jpg' => 'Tầng lửng · Phòng chờ',
    'PXU-T1-01.jpg' => 'Tầng 1 · Mặt bằng nhà ga hành khách',
    'PXU-T1-02.jpg' => 'Tầng 2 · Mặt bằng nhà ga hành khách',
    'THD-T1-01.jpg' => 'Tầng 1 · Ga đi và ga đến',
    'THD-T1-02.jpg' => 'Tầng 2 · Phòng chờ',
    'TBB-T1-01.jpg' => 'Tầng 1 · Khu vực công cộng và hạn chế',
    'TBB-T1-02.jpg' => 'Tầng 2 · Khu cách ly và phòng chờ',
    'DIN-T1-01.jpg' => 'Tầng 1 · Mặt bằng nhà ga hành khách',
    'DIN-T1-02.jpg' => 'Tầng 2 · Mặt bằng nhà ga hành khách',
];

$diagramSources = [
    'HAN-T1' => [
        'url' => 'https://noibaiairport.vn/Nha-ga-hanh-khach-quoc-noi-T1--nid15.html',
        'label' => 'Cảng HKQT Nội Bài',
    ],
    'HAN-T2' => [
        'url' => 'https://noibaiairport.vn/Nha-ga-hanh-khach-quoc-te-T2--nid13.html',
        'label' => 'Cảng HKQT Nội Bài',
    ],
    'VCA-T1' => [
        'url' => 'https://taxisanbayre.com/so-do-nha-ga-hanh-khach-san-bay-can-tho/',
        'label' => 'Taxi sân bay rẻ',
    ],
    'PQC-T1' => [
        'url' => 'https://vinwonders.com/vi/wonderpedia/news/san-bay-phu-quoc-o-dau/',
        'label' => 'VinWonders',
    ],
    'SGN-T1' => [
        'url' => 'https://dienmaycholon.com/kinh-nghiem-mua-sam/so-do-san-bay-tan-son-nhat-chi-tiet',
        'label' => 'Điện Máy Chợ Lớn',
    ],
    'SGN-T2' => [
        'url' => 'https://dienmaycholon.com/kinh-nghiem-mua-sam/so-do-san-bay-tan-son-nhat-chi-tiet',
        'label' => 'Điện Máy Chợ Lớn',
    ],
    'SGN-T3' => [
        'url' => 'https://dienmaycholon.com/kinh-nghiem-mua-sam/so-do-san-bay-tan-son-nhat-chi-tiet',
        'label' => 'Điện Máy Chợ Lớn',
    ],
    'HPH-T1' => [
        'url' => '',
        'label' => 'Người dùng cung cấp',
    ],
    'DAD-T1' => [
        'url' => '',
        'label' => 'Người dùng cung cấp',
    ],
    'DAD-T3' => [
        'url' => '',
        'label' => 'Người dùng cung cấp',
    ],
    'UIH-T1' => [
        'url' => 'https://vexere.com/vn/ve-may-bay/san-bay-quoc-te-phu-cat.vi',
        'label' => 'Vexere',
    ],
    'PXU-T1' => [
        'url' => 'https://acv.vn/vi/pxu/thong-tin-chung-pxu/so-do-nha-ga-hanh-khach-cang-hang-khong-pleiku',
        'label' => 'Cảng hàng không Pleiku',
    ],
    'THD-T1' => [
        'url' => 'https://acv.vn/vi/thd/gioi-thieu-thd/so-do-nha-ga-cang-hang-khong-tho-xuan',
        'label' => 'Cảng hàng không Thọ Xuân',
    ],
    'TBB-T1' => [
        'url' => 'https://acv.vn/vi/tbb/quan-trac-moi-truong-tbb/so-do-nha-ga-hanh-khach-cang-hang-khong-tuy-hoa',
        'label' => 'Cảng hàng không Tuy Hòa',
    ],
    'DIN-T1' => [
        'url' => 'https://acv.vn/vi/din/tin-tuc-din/nha-ga-hanh-khach',
        'label' => 'Cảng hàng không Điện Biên',
    ],
];

$diagramSourceUrl = '';
$diagramSourceLabel = '';
$diagramGallery = [];
if ($terminal) {
    $diagramSourceKey = $airport['ma_iata'] . '-' . $terminal['ma_nha_ga'];
    $diagramSource = $diagramSources[$diagramSourceKey] ?? [];
    $diagramSourceUrl = $diagramSource['url'] ?? '';
    $diagramSourceLabel = $diagramSource['label'] ?? '';
    $diagramDir = dirname(__DIR__, 3) . '/assets/img/so_do_nha_ga';
    $diagramFiles = glob($diagramDir . '/' . $diagramSourceKey . '-*.jpg') ?: [];
    sort($diagramFiles, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($diagramFiles as $diagramFile) {
        $diagramName = basename($diagramFile);
        if (strpos($diagramName, '-thumb') !== false) {
            continue;
        }

        $thumbName = preg_replace('/\.jpg$/i', '-thumb.jpg', $diagramName);
        $thumbPath = $diagramDir . '/' . $thumbName;
        $diagramGallery[] = [
            'name' => $diagramName,
            'caption' => $diagramCaptions[$diagramName] ?? $diagramName,
            'image' => $imageUrl('assets/img/so_do_nha_ga/' . $diagramName),
            'thumb' => $imageUrl('assets/img/so_do_nha_ga/' . (is_file($thumbPath) ? $thumbName : $diagramName)),
        ];
    }

    if ($diagramImage !== '') {
        $alreadyListed = false;
        foreach ($diagramGallery as $diagram) {
            if ($diagram['image'] === $diagramImage) {
                $alreadyListed = true;
                break;
            }
        }

        if (!$alreadyListed) {
            $diagramPath = parse_url($diagramImage, PHP_URL_PATH);
            $diagramName = $diagramPath ? basename($diagramPath) : basename($diagramImage);
            array_unshift($diagramGallery, [
                'name' => $diagramName,
                'caption' => 'Sơ đồ nhà ga',
                'image' => $diagramImage,
                'thumb' => $diagramImage,
            ]);
        }
    }
}
?>
<div class="card ht-panel">
  <div class="card-header">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
      <div>
        <h3><i class="bi bi-diagram-3 me-1"></i> Nhà ga tại <?= $e($airport['ten_ngan']) ?> (<?= $e($airport['ma_iata']) ?>)</h3>
        <p>Chọn nhà ga để xem thông tin và sơ đồ cửa ra, quầy check-in, băng chuyền hành lý.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= $e($manageTerminalUrl) ?>" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-pencil-square me-1"></i> Quản lý nhà ga
        </a>
      </div>
    </div>
  </div>

  <?php if (!$terminals): ?>
    <div class="card-body">
      <div class="ht-empty border-0">
        <i class="bi bi-door-closed"></i>
        <h3>Sân bay chưa có nhà ga</h3>
        <p>Thêm nhà ga đầu tiên trong khu vực quản trị để bắt đầu cấu hình cửa ra máy bay, quầy check-in và băng chuyền hành lý.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card-body">
      <div class="ht-terminal-strip">
        <?php foreach ($terminals as $item): ?>
          <?php $isActive = $terminal && (int) $terminal['id'] === (int) $item['id']; ?>
          <a href="<?= $e($terminalUrl($item['id'])) ?>" class="ht-terminal-card <?= $isActive ? 'is-active' : '' ?>">
            <div class="ht-terminal-card__top">
              <span class="ht-terminal-card__code"><?= $e($item['ma_nha_ga']) ?></span>
              <?= $statusBadge($item['trang_thai']) ?>
            </div>
            <div class="fw-bold mt-2"><?= $e($item['ten_nha_ga']) ?></div>
            <div class="mt-1"><?= $terminalTypeBadge($item['loai_nha_ga']) ?></div>
            <div class="ht-terminal-card__meta">
              <span><i class="bi bi-signpost me-1"></i><?= (int) $item['so_cua_ra'] ?> cửa</span>
              <span><i class="bi bi-person-check me-1"></i><?= (int) $item['so_quay_checkin'] ?> quầy</span>
              <span><i class="bi bi-luggage me-1"></i><?= (int) $item['so_bang_chuyen'] ?> băng</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($terminal): ?>
        <div class="row g-3 mt-1">
          <div class="col-xl-4">
            <div class="h-100 p-3 border rounded-3">
              <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <div>
                  <div class="ht-eyebrow text-secondary">Thông tin nhà ga</div>
                  <h4 class="h5 fw-bold mb-0"><?= $e($terminal['ten_nha_ga']) ?></h4>
                </div>
                <?= $terminalTypeBadge($terminal['loai_nha_ga']) ?>
              </div>

              <dl class="row mb-0 small">
                <dt class="col-5 text-secondary fw-normal">Mã nhà ga</dt>
                <dd class="col-7 fw-semibold mb-1"><?= $e($terminal['ma_nha_ga']) ?></dd>

                <dt class="col-5 text-secondary fw-normal">Trạng thái</dt>
                <dd class="col-7 mb-1"><?= $statusBadge($terminal['trang_thai']) ?></dd>

                <dt class="col-5 text-secondary fw-normal">Năm khai thác</dt>
                <dd class="col-7 fw-semibold mb-1"><?= $terminal['nam_khai_thac'] ? (int) $terminal['nam_khai_thac'] : 'Đang cập nhật' ?></dd>

                <dt class="col-5 text-secondary fw-normal">Diện tích</dt>
                <dd class="col-7 fw-semibold mb-1"><?= !empty($terminal['dien_tich']) ? $e($terminal['dien_tich']) : 'Đang cập nhật' ?></dd>

                <dt class="col-5 text-secondary fw-normal">Công suất</dt>
                <dd class="col-7 fw-semibold mb-1"><?= !empty($terminal['cong_suat']) ? $e($terminal['cong_suat']) : 'Đang cập nhật' ?></dd>

                <dt class="col-5 text-secondary fw-normal">Cửa ra / Quầy / Băng</dt>
                <dd class="col-7 fw-semibold mb-0">
                  <?= count($gates) ?> / <?= count($checkins) ?> / <?= count($belts) ?>
                </dd>
              </dl>

              <?php if (!empty($terminal['mo_ta'])): ?>
                <p class="small text-secondary mt-3 mb-0"><?= $e($terminal['mo_ta']) ?></p>
              <?php endif; ?>

              <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="<?= $e($manageGatesUrl) ?>" class="btn btn-outline-secondary btn-sm">Cửa ra</a>
                <a href="<?= $e($manageCheckinsUrl) ?>" class="btn btn-outline-secondary btn-sm">Check-in</a>
                <a href="<?= $e($manageBeltsUrl) ?>" class="btn btn-outline-secondary btn-sm">Băng chuyền</a>
              </div>
            </div>
          </div>

          <div class="col-xl-8">
            <div class="ht-diagram ht-diagram--combined">
              <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                  <div class="ht-eyebrow text-secondary">Sơ đồ khai thác</div>
                  <h4 class="h6 fw-bold mb-0"><?= $e($terminal['ma_nha_ga']) ?> · <?= $e($terminal['ten_nha_ga']) ?></h4>
                </div>
                <span class="badge text-bg-info">Sơ đồ minh họa</span>
              </div>

              <div class="ht-diagram__grid">
                <section class="ht-diagram__zone">
                  <h4>
                    <i class="bi bi-box-arrow-in-right"></i> Cửa ra máy bay
                    <span class="badge text-bg-primary ms-auto"><?= count($gates) ?></span>
                  </h4>
                  <div class="ht-gates">
                    <?php if (!$gates): ?>
                      <span class="ht-soft-label">Chưa có dữ liệu cửa ra.</span>
                    <?php else: ?>
                      <?php foreach ($gates as $gate): ?>
                        <span
                          class="ht-gate <?= $gate['trang_thai'] === 'Bao_Tri' ? 'is-maintenance' : '' ?>"
                          title="Cửa <?= $e($gate['so_cua']) ?><?= !empty($gate['khu_vuc']) ? ' · ' . $e($gate['khu_vuc']) : '' ?>"
                        >
                          <?= $e($gate['so_cua']) ?>
                          <?php if (!empty($gate['khu_vuc'])): ?><small><?= $e($gate['khu_vuc']) ?></small><?php endif; ?>
                        </span>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </section>

                <section class="ht-diagram__zone">
                  <h4>
                    <i class="bi bi-person-check"></i> Quầy check-in
                    <span class="badge text-bg-success ms-auto"><?= count($checkins) ?></span>
                  </h4>
                  <div class="ht-counters">
                    <?php if (!$checkins): ?>
                      <span class="ht-soft-label">Chưa có dữ liệu quầy check-in.</span>
                    <?php else: ?>
                      <?php foreach ($checkins as $checkin): ?>
                        <span
                          class="ht-counter <?= $checkin['trang_thai'] === 'Bao_Tri' ? 'is-maintenance' : '' ?>"
                          title="Quầy <?= $e($checkin['so_quay']) ?><?= !empty($checkin['khu_vuc']) ? ' · ' . $e($checkin['khu_vuc']) : '' ?>"
                        >
                          <i class="bi bi-person-check"></i><?= $e($checkin['so_quay']) ?>
                          <?php if (!empty($checkin['khu_vuc'])): ?><small><?= $e($checkin['khu_vuc']) ?></small><?php endif; ?>
                        </span>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </section>

                <section class="ht-diagram__zone">
                  <h4>
                    <i class="bi bi-luggage"></i> Băng chuyền hành lý
                    <span class="badge text-bg-secondary ms-auto"><?= count($belts) ?></span>
                  </h4>
                  <div class="ht-belts">
                    <?php if (!$belts): ?>
                      <span class="ht-soft-label">Chưa có dữ liệu băng chuyền.</span>
                    <?php else: ?>
                      <?php foreach ($belts as $belt): ?>
                        <span
                          class="ht-belt <?= $belt['trang_thai'] === 'Bao_Tri' ? 'is-maintenance' : '' ?>"
                          title="Băng chuyền <?= $e($belt['so_bang_chuyen']) ?><?= !empty($belt['khu_vuc']) ? ' · ' . $e($belt['khu_vuc']) : '' ?>"
                        >
                          <i class="bi bi-luggage"></i><?= $e($belt['so_bang_chuyen']) ?>
                          <?php if (!empty($belt['khu_vuc'])): ?><small><?= $e($belt['khu_vuc']) ?></small><?php endif; ?>
                        </span>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </section>
              </div>

              <div class="ht-diagram__legend">
                <span><i style="background: #2563eb"></i> Cửa ra</span>
                <span><i style="background: #0f9d76"></i> Quầy check-in</span>
                <span><i style="background: #7c3aed"></i> Băng chuyền</span>
                <span><i style="background: #f59e0b"></i> Đang bảo trì</span>
              </div>

              <div class="ht-diagram-gallery-block">
                <?php if ($diagramGallery): ?>
                  <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                    <div>
                      <div class="ht-eyebrow text-secondary">Ảnh sơ đồ nhà ga</div>
                      <div class="small text-secondary">Bấm ảnh để xem lớn; có thể thu phóng và chuyển ảnh.</div>
                    </div>
                    <span class="badge text-bg-light"><?= count($diagramGallery) ?> ảnh</span>
                  </div>
                  <div class="ht-diagram-gallery">
                    <?php foreach ($diagramGallery as $diagramIndex => $diagram): ?>
                      <?php $diagramCaption = $terminal['ma_nha_ga'] . ' · ' . $diagram['caption']; ?>
                      <a
                        href="<?= $e($diagram['image']) ?>"
                        class="ht-diagram-thumb"
                        data-ht-index="<?= (int) $diagramIndex ?>"
                        data-image="<?= $e($diagram['image']) ?>"
                        data-caption="<?= $e($diagramCaption) ?>"
                      >
                        <span class="ht-diagram-thumb__zoom" aria-hidden="true">
                          <i class="bi bi-arrows-fullscreen"></i>
                        </span>
                        <img
                          src="<?= $e($diagram['thumb']) ?>"
                          alt="<?= $e('Sơ đồ ' . $terminal['ten_nha_ga'] . ' - ' . $diagram['caption']) ?>"
                          loading="lazy"
                        />
                        <span class="ht-diagram-thumb__caption"><?= $e($diagram['caption']) ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <?php if ($diagramSourceLabel !== ''): ?>
                    <div class="ht-diagram-source">
                      Nguồn ảnh:
                      <?php if ($diagramSourceUrl !== ''): ?>
                        <a href="<?= $e($diagramSourceUrl) ?>" target="_blank" rel="noopener"><?= $e($diagramSourceLabel) ?></a>
                      <?php else: ?>
                        <?= $e($diagramSourceLabel) ?>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div> 
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

