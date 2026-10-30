<?php
/**
 * Form tạo phiếu bảo trì đã được chuyển thành modal trên trang danh sách.
 * Giữ file này để các liên kết cũ vẫn hoạt động: chuyển về danh sách và mở modal.
 */
require_once __DIR__ . '/../../../config/config.php';

$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
header('Location: ' . $base . '/views/pages/bao_tri/index.php?open=create#taoPhieuModal');
exit;
