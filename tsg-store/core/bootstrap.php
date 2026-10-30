<?php
declare(strict_types=1);

define('TSG_VERSION', '1.0.0-phase1');
define('ROOT', dirname(__DIR__));
$__dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE', $__dir === '.' ? '' : $__dir);

if (!is_file(ROOT . '/config/config.php')) {
    header('Location: ' . BASE . '/install/');
    exit;
}
$CONFIG = require ROOT . '/config/config.php';
date_default_timezone_set($CONFIG['timezone'] ?? 'Asia/Karachi');
if (!empty($CONFIG['debug'])) { ini_set('display_errors', '1'); error_reporting(E_ALL); } else { ini_set('display_errors', '0'); }

foreach (['helpers', 'defaults', 'auth', 'view', 'router', 'shop'] as $lib) require ROOT . "/core/lib/$lib.php";
foreach (glob(ROOT . '/core/controllers/*.php') as $c) require $c;

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('tsgstore');
session_set_cookie_params(['lifetime' => 0, 'path' => BASE ?: '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

try { settings_all(); } catch (Throwable $e) { http_response_code(500); exit('The store database is not reachable. Please contact support.'); }
if (setting('force_https') === '1' && !$https && PHP_SAPI !== 'cli-server') {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
define('LAYOUT_READY', true);

set_exception_handler(function (Throwable $e) {
    global $CONFIG;
    error_log($e);
    http_response_code(500);
    echo !empty($CONFIG['debug']) ? '<pre>' . e((string)$e) . '</pre>' : '<h1>Something went wrong</h1><p>Please try again shortly.</p>';
});

require ROOT . '/core/routes.php';
