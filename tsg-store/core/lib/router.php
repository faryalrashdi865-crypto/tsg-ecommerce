<?php
declare(strict_types=1);

function route(string $method, string $pattern, string $handler): void {
    $GLOBALS['ROUTES'][] = [$method, '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '/?$#', $handler];
}
function dispatch(): void {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (BASE !== '' && str_starts_with($path, BASE)) $path = substr($path, strlen(BASE));
    $path = '/' . trim(rawurldecode($path), '/');
    $method = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'POST' : 'GET';
    foreach ($GLOBALS['ROUTES'] ?? [] as [$m, $re, $h]) {
        if ($m === $method && preg_match($re, $path, $mm)) {
            if ($method === 'POST') csrf_check();
            $args = array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY);
            $h(...array_values($args));
            return;
        }
    }
    abort(404, 'We could not find that page.');
}
