<?php
/**
 * Lightbox xem ảnh sự cố — thuần CSS (:target), không dùng JavaScript.
 *
 * Đầu vào: $lightboxImages = [
 *     ['id' => 'anh-su-co-1', 'url' => 'assets/img/su_co/abc.jpg', 'alt' => 'Mô tả ảnh'],
 *     ...
 * ];
 * Mỗi ảnh được mở bằng liên kết href="#<id>" và đóng bằng nút &times; (href="#!").
 */
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
