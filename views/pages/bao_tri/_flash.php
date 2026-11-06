<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flashMessages = [
    'flash_success' => 'success',
    'flash_error'   => 'danger',
    'flash_info'    => 'info',
];

foreach ($flashMessages as $flashKey => $flashType):
    if (empty($_SESSION[$flashKey])) {
        continue;
    }
    $flashText = (string) $_SESSION[$flashKey];
    unset($_SESSION[$flashKey]);
?>
<div class="alert alert-<?= $flashType ?>" role="alert">
    <?= htmlspecialchars($flashText, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endforeach; ?>
