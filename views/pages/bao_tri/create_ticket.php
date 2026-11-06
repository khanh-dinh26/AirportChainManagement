<?php

require_once __DIR__ . '/../../../config/config.php';

$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
header('Location: ' . $base . '/views/pages/bao_tri/index.php?open=create#taoPhieuModal');
exit;
