<?php
// Development only: php -S localhost:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && !preg_match('#^/(core|config|database|storage|tools|docs)/#', $path) && !str_ends_with($file, '.php')) return false;
if (preg_match('#^/install(/|$)#', $path)) { $_SERVER['SCRIPT_NAME'] = '/install/index.php'; require __DIR__ . '/install/index.php'; return true; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
