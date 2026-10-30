<?php
declare(strict_types=1);

/* Theme + view system. Core views live in /core/views; a theme may override any view
   by providing the same file under /themes/<theme>/views. Client changes never touch /core. */
function theme_name(): string {
    $t = setting('theme', 'default');
    return is_dir(ROOT . '/themes/' . $t) ? $t : 'default';
}
function theme_asset(string $file): string {
    $t = theme_name();
    return url(is_file(ROOT . "/themes/$t/$file") ? "themes/$t/$file" : "themes/default/$file");
}
function view_file(string $tpl): string {
    $t = ROOT . '/themes/' . theme_name() . '/views/' . $tpl . '.php';
    return is_file($t) ? $t : ROOT . '/core/views/' . $tpl . '.php';
}
function render(string $tpl, array $vars = []): string {
    extract($vars, EXTR_SKIP);
    ob_start();
    include view_file($tpl);
    return (string)ob_get_clean();
}
function render_page(string $tpl, array $vars = [], string $layout = 'layout'): string {
    $content = render($tpl, $vars);
    return render($layout, $vars + ['content' => $content]);
}
function page(string $tpl, array $vars = []): never { echo render_page($tpl, $vars); exit; }
function admin_page(string $tpl, array $vars = []): never { echo render_page($tpl, $vars, 'admin/layout'); exit; }
function json_out($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
/* CSS variables built from settings: change a colour once and every page, email and invoice follows */
function theme_css_vars(): string {
    return ':root{--primary:' . e(setting('primary_color')) . ';--secondary:' . e(setting('secondary_color')) . ';--accent:' . e(setting('accent_color'))
        . ';--font-heading:' . e(setting('font_heading')) . ';--font-body:' . e(setting('font_body')) . ';}';
}
