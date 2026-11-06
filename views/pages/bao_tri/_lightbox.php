<?php

if (empty($lightboxImages)) {
    return;
}
?>
<?php foreach ($lightboxImages as $lightboxImage): ?>
    <?php
    if (empty($lightboxImage['url'])) {
        continue;
    }
    $lightboxId  = (string) ($lightboxImage['id'] ?? '');
    $lightboxUrl = (string) $lightboxImage['url'];
    $lightboxAlt = (string) ($lightboxImage['alt'] ?? 'Ảnh sự cố');
    ?>
    <div class="maint-lightbox" id="<?= htmlspecialchars($lightboxId, ENT_QUOTES, 'UTF-8') ?>"
         role="dialog" aria-modal="true" aria-label="<?= htmlspecialchars($lightboxAlt, ENT_QUOTES, 'UTF-8') ?>">
        <a href="#!" class="maint-lightbox__backdrop" tabindex="-1" aria-hidden="true"></a>
        <a href="#!" class="maint-lightbox__close" aria-label="Đóng ảnh">&times;</a>
        <img src="<?= htmlspecialchars($lightboxUrl, ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($lightboxAlt, ENT_QUOTES, 'UTF-8') ?>"
             class="maint-lightbox__img">
    </div>
<?php endforeach; ?>
