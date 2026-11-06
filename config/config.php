<?php

if (!defined('BASE_URL')) {
    define('BASE_URL', '/AirportChainManagement');
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ADMINLTE_CDN')) {
    define('ADMINLTE_CDN', 'https://cdn.jsdelivr.net/npm/admin-lte@4.10.0/dist');
}
if (!defined('ADMINLTE_URL')) {
    $adminLteLocalDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'admin-lte-v4.10.1' . DIRECTORY_SEPARATOR . 'dist';
    define('ADMINLTE_URL', is_dir($adminLteLocalDir) ? BASE_URL . '/admin-lte-v4.10.1/dist' : ADMINLTE_CDN);
    unset($adminLteLocalDir);
}

require_once __DIR__ . '/auth.php';
