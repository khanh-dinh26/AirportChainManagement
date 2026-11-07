<?php
/**
 * @var bool|null $airportAdminOnly
 * @var array $airports
 * @var array|null $selectedAirport
 * @var array|null $selectedTerminal
 * @var string $activeTab
 * @var string $tab
 * @var array $data
 * @var callable $url
 * @var callable $e
 * @var string|null $formMode
 * @var array|null $formRecord
 */

// Chế độ chỉ quản lý sân bay (mở từ nút "Quản lí sân bay" ở trang Hạ tầng):
// không có thanh tab, không có khối chọn sân bay / nhà ga, chỉ còn danh mục sân bay.
$airportAdminOnly = isset($airportAdminOnly) && $airportAdminOnly;

$tabMeta = [
    'nha_ga' => [
        'label' => 'Nhà ga',
        'icon' => 'bi-door-open',
        'desc' => 'Nhấn vào một nhà ga để chọn và xem cửa ra, quầy check-in, băng chuyền.',
        'add' => 'Thêm nhà ga',
    ],
    'cua_ra' => [
        'label' => 'Cửa ra máy bay',
        'icon' => 'bi-signpost',
        'desc' => 'Cửa ra thuộc nhà ga đang chọn.',
        'add' => 'Thêm cửa ra',
    ],
    'quay' => [
        'label' => 'Quầy check-in',
        'icon' => 'bi-person-check',
        'desc' => 'Quầy check-in thuộc nhà ga đang chọn.',
        'add' => 'Thêm quầy',
    ],
    'bang_chuyen' => [
        'label' => 'Băng chuyền',
        'icon' => 'bi-luggage',
        'desc' => 'Băng chuyền hành lý thuộc nhà ga đang chọn.',
        'add' => 'Thêm băng chuyền',
    ],
];

$sanBayMeta = [
    'label' => 'Sân bay',
    'icon' => 'bi-building',
    'desc' => 'Danh mục sân bay, mã nhận diện, phân cấp và trạng thái khai thác.',
    'add' => 'Thêm sân bay',
];

$airportPageUrl = $url(['quan_ly_san_bay' => '1']);

$returnTab = $airportAdminOnly ? 'san_bay' : $tab;
$returnSanBay = $airportAdminOnly || !$selectedAirport ? '' : $selectedAirport['ma_iata'];
$returnNhaGa = $airportAdminOnly || !$selectedTerminal ? 0 : (int) $selectedTerminal['id'];
$formMode = $management['formMode'];
$formRecord = $management['editRecord'];
$formType = $management['formType'];

$adminUrl = static function (array $overrides = []) use ($url, $returnTab, $returnSanBay, $returnNhaGa) {
    $params = ['quan_tri' => '1', 'tab' => $returnTab];
    if ($returnSanBay !== '') {
        $params['san_bay'] = $returnSanBay;
    }
    if ($returnNhaGa > 0) {
        $params['nha_ga'] = $returnNhaGa;
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return $url($params);
};
$listUrl = $airportAdminOnly ? $airportPageUrl : $adminUrl(['tab' => $returnTab]);
$currentMeta = $tabMeta[$tab] ?? ($airportAdminOnly ? $sanBayMeta : $tabMeta['nha_ga']);

$canUseTab = true;
if (!$airportAdminOnly) {
    if ($tab === 'nha_ga' && !$selectedAirport) {
        $canUseTab = false;
    }
    if (in_array($tab, ['cua_ra', 'quay', 'bang_chuyen'], true) && !$selectedTerminal) {
        $canUseTab = false;
    }
}

$tabParams = ['quan_tri' => '1', 'tab' => $airportAdminOnly ? 'san_bay' : $tab];
if (!$airportAdminOnly && $selectedAirport) {
    $tabParams['san_bay'] = $selectedAirport['ma_iata'];
}
if (!$airportAdminOnly && $selectedTerminal) {
    $tabParams['nha_ga'] = (int) $selectedTerminal['id'];
}
$addUrl = $adminUrl(array_merge($tabParams, ['them' => 1]));
?>
<?php if (!$airportAdminOnly): ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div class="d-flex flex-wrap align-items-center gap-2">
    <span class="badge text-bg-light border">
      <i class="bi bi-geo-alt me-1"></i>
      Sân bay:
      <strong><?= $selectedAirport ? $e($selectedAirport['ma_iata'] . ' — ' . $selectedAirport['ten_ngan']) : 'Chưa chọn' ?></strong>
    </span>
    <?php if ($selectedTerminal): ?>
      <span class="badge text-bg-light border">
        <i class="bi bi-door-open me-1"></i>
        Nhà ga: <strong><?= $e($selectedTerminal['ma_nha_ga'] . ' — ' . $selectedTerminal['ten_nha_ga']) ?></strong>
      </span>
    <?php endif; ?>
  </div>
</div>

<div class="ht-admin-tabs" id="ht-admin-tabs">
  <?php foreach ($tabMeta as $key => $meta): ?>
    <?php $isActive = $tab === $key; ?>
    <a href="<?= $e($adminUrl(['tab' => $key]) . '#ht-admin-tabs') ?>" class="<?= $isActive ? 'is-active' : '' ?>">
      <i class="bi <?= $e($meta['icon']) ?>"></i>
      <?= $e($meta['label']) ?>
      <?php if ($key === 'nha_ga'): ?>
        <span class="badge text-bg-secondary"><?= count($terminals) ?></span>
      <?php elseif ($key === 'cua_ra'): ?>
        <span class="badge text-bg-secondary"><?= count($data['gates']) ?></span>
      <?php elseif ($key === 'quay'): ?>
        <span class="badge text-bg-secondary"><?= count($data['checkins']) ?></span>
      <?php elseif ($key === 'bang_chuyen'): ?>
        <span class="badge text-bg-secondary"><?= count($data['belts']) ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card ht-panel">
  <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-2">
    <div>
      <h3><i class="bi <?= $e($currentMeta['icon']) ?> me-1"></i> <?= $e($currentMeta['label']) ?></h3>
      <p><?= $e($currentMeta['desc']) ?></p>
    </div>
    <?php if ($canUseTab): ?>
      <div class="d-flex flex-wrap gap-2 ms-auto">
        <a href="<?= $e($addUrl) ?>" class="btn btn-primary btn-sm js-ht-form-open" data-ht-form-add <?= $formMode ? 'hidden' : '' ?>>
          <i class="bi bi-plus-lg me-1"></i> <?= $e($currentMeta['add']) ?>
        </a>
        <a href="<?= $e($listUrl) ?>" class="btn btn-outline-secondary btn-sm js-ht-form-close" data-ht-form-close <?= $formMode ? '' : 'hidden' ?>>
          <i class="bi bi-x-lg me-1"></i> Đóng biểu mẫu
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$canUseTab): ?>
    <div class="card-body">
      <div class="ht-empty border-0">
        <i class="bi bi-signpost-split"></i>
        <?php if ($tab === 'nha_ga'): ?>
          <h3>Chưa chọn sân bay</h3>
          <p>Chọn một sân bay trên bản đồ phía trên để quản lý danh sách nhà ga trực thuộc.</p>
          <a href="<?= $e($airportPageUrl) ?>" class="btn btn-primary btn-sm mt-3">
            <i class="bi bi-building me-1"></i> Đi tới danh mục sân bay
          </a>
        <?php else: ?>
          <h3>Chưa chọn nhà ga</h3>
          <p>Chọn sân bay trên bản đồ, sau đó chọn một nhà ga để quản lý cửa ra, quầy check-in hoặc băng chuyền.</p>
          <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
            <a href="<?= $e($airportPageUrl) ?>" class="btn btn-outline-primary btn-sm">
              Chọn sân bay
            </a>
            <a href="<?= $e($adminUrl(['tab' => 'nha_ga'])) ?>" class="btn btn-primary btn-sm">
              Quản lý nhà ga
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <?php if ($tab === 'san_bay'): ?>
        <?php require __DIR__ . '/_table_san_bay.php'; ?>
      <?php elseif ($tab === 'nha_ga'): ?>
        <?php require __DIR__ . '/_table_nha_ga.php'; ?>
      <?php else: ?>
        <?php require __DIR__ . '/_table_resource.php'; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php
/* ------------------------------------------------------------
 * Hộp thoại biểu mẫu thêm/sửa: mở và đóng bằng JavaScript nên
 * không tải lại trang, vị trí cuộn của trang được giữ nguyên.
 * HTML được đưa ra ngoài .ht-page (xem index.php) để tránh bị
 * hiệu ứng transform của khung trang nhốt position: fixed.
 * ------------------------------------------------------------ */
ob_start();
?>
<div class="ht-modal" id="ht-form-modal"<?= $formMode ? '' : ' hidden' ?> aria-hidden="<?= $formMode ? 'false' : 'true' ?>">
  <div class="ht-modal__backdrop js-ht-form-close"></div>
  <div class="ht-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ht-form-modal-title">
    <div class="ht-modal__head">
      <h4 class="ht-modal__title" id="ht-form-modal-title">Biểu mẫu hạ tầng</h4>
      <button type="button" class="btn-close js-ht-form-close" aria-label="Đóng biểu mẫu"></button>
    </div>
    <div class="ht-modal__body" id="ht-form-body">
      <?php if ($formMode): ?>
        <?php if ($formMode === 'sua' && !$formRecord): ?>
          <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Không tìm thấy bản ghi cần sửa. Có thể bản ghi đã bị xóa hoặc đường dẫn không hợp lệ.
          </div>
        <?php elseif ($tab === 'san_bay'): ?>
          <?php require __DIR__ . '/_form_san_bay.php'; ?>
        <?php elseif ($tab === 'nha_ga'): ?>
          <?php require __DIR__ . '/_form_nha_ga.php'; ?>
        <?php else: ?>
          <?php require __DIR__ . '/_form_resource.php'; ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
  (function () {
    var modal = document.getElementById('ht-form-modal');
    var body = document.getElementById('ht-form-body');
    var titleEl = document.getElementById('ht-form-modal-title');
    if (!modal || !body) {
      return;
    }

    var addButtons = document.querySelectorAll('[data-ht-form-add]');
    var closeButtons = document.querySelectorAll('[data-ht-form-close]');
    var scrollLocked = false;

    function syncTitle() {
      var heading = body.querySelector('.ht-form-card h4');
      if (heading && titleEl) {
        titleEl.textContent = heading.textContent.trim();
      }
    }

    function setOpen(open) {
      modal.hidden = !open;
      modal.setAttribute('aria-hidden', open ? 'false' : 'true');

      for (var i = 0; i < addButtons.length; i++) {
        addButtons[i].hidden = open;
      }
      for (var j = 0; j < closeButtons.length; j++) {
        closeButtons[j].hidden = !open;
      }

      if (open) {
        if (!scrollLocked) {
          var scrollbar = window.innerWidth - document.documentElement.clientWidth;
          document.body.style.paddingRight = scrollbar > 0 ? scrollbar + 'px' : '';
          document.body.classList.add('ht-modal-open');
          scrollLocked = true;
        }
        return;
      }

      if (scrollLocked) {
        document.body.classList.remove('ht-modal-open');
        document.body.style.paddingRight = '';
        scrollLocked = false;
      }

      body.innerHTML = '';

      var url = new URL(window.location.href);
      url.searchParams.delete('them');
      url.searchParams.delete('sua');
      url.searchParams.delete('ht_form');
      window.history.replaceState(null, '', url.toString());
    }

    function openForm(url) {
      var target = new URL(url, window.location.href);
      target.searchParams.set('ht_form', '1');

      fetch(target.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('HTTP ' + response.status);
          }
          return response.text();
        })
        .then(function (html) {
          body.innerHTML = html;
          syncTitle();
          setOpen(true);
          body.scrollTop = 0;
        })
        .catch(function () {
          window.location.href = url;
        });
    }

    document.addEventListener('click', function (event) {
      var opener = event.target.closest('.js-ht-form-open');
      if (opener) {
        var href = opener.getAttribute('href');
        if (href) {
          event.preventDefault();
          openForm(href);
        }
        return;
      }

      var closer = event.target.closest('.js-ht-form-close');
      if (closer) {
        event.preventDefault();
        setOpen(false);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) {
        setOpen(false);
      }
    });

    /* Các hàm xem trước ảnh sân bay dùng trong _form_san_bay.php.
       Khai báo toàn cục để vẫn chạy khi biểu mẫu được nạp bằng fetch. */
    window.previewAirportImage = function (input) {
      var previewWrapper = document.getElementById('sb-image-preview-wrapper');
      var previewImg = document.getElementById('sb-image-preview');
      var previewLabel = document.getElementById('sb-preview-label');
      var previewSub = document.getElementById('sb-preview-sub');

      if (input.files && input.files[0]) {
        var file = input.files[0];
        var reader = new FileReader();
        reader.onload = function (e) {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
          previewLabel.textContent = 'Ảnh vừa chọn:';
          previewSub.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
          previewWrapper.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
      }
    };

    window.clearAirportImagePreview = function () {
      var fileInput = document.getElementById('sb-hinh-file');
      var hiddenInput = document.getElementById('sb-hinh');
      var previewWrapper = document.getElementById('sb-image-preview-wrapper');
      if (fileInput) fileInput.value = '';
      if (hiddenInput) hiddenInput.value = '';
      if (previewWrapper) previewWrapper.classList.add('d-none');
    };

    if (!modal.hidden) {
      setOpen(true);
      syncTitle();
    }
  })();
</script>
<?php
$htFormModalHtml = ob_get_clean();
?>
