<?php
declare(strict_types=1);

/* ---------- Database ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        global $CONFIG;
        $c = $CONFIG['db'];
        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
            $c['user'], $c['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function row(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r === false ? null : $r; }
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }
function insert(string $table, array $data): int {
    $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($data)));
    $ph = implode(',', array_fill(0, count($data), '?'));
    q("INSERT INTO `$table` ($cols) VALUES ($ph)", array_values($data));
    return (int)db()->lastInsertId();
}
function update(string $table, array $data, string $where, array $wp = []): void {
    $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
    q("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $wp));
}

/* ---------- Settings (one place, whole site) ---------- */
function settings_all(?array $replace = null): array {
    static $cache = null;
    if ($replace !== null) $cache = $replace;
    if ($cache === null) {
        $cache = [];
        foreach (rows('SELECT `key`,`value` FROM settings') as $r) $cache[$r['key']] = $r['value'];
    }
    return $cache;
}
function setting(string $key, $default = '') { $a = settings_all(); return array_key_exists($key, $a) && $a[$key] !== null ? $a[$key] : $default; }
function set_setting(string $key, $value): void {
    q('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)', [$key, (string)$value]);
    $a = settings_all(); $a[$key] = (string)$value; settings_all($a);
}
function module_on(string $name): bool { return setting('mod_' . $name, '0') === '1'; }

/* ---------- Output / URLs ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $p = ''): string { return BASE . '/' . ltrim($p, '/'); }
function site_url(string $p = ''): string {
    global $CONFIG;
    $root = rtrim($CONFIG['base_url'] ?? '', '/');
    if ($root === '') $root = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE;
    return $root . '/' . ltrim($p, '/');
}
function redirect(string $p): never {
    header('Location: ' . (preg_match('#^https?://#', $p) ? $p : url($p)));
    exit;
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function post(string $k, $d = '') { if (!isset($_POST[$k])) return $d; return is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k]; }
function get(string $k, $d = '') { if (!isset($_GET[$k])) return $d; return is_string($_GET[$k]) ? trim($_GET[$k]) : $d; }
function abort(int $code, string $msg = ''): never {
    http_response_code($code);
    $titles = [403 => 'Access denied', 404 => 'Page not found', 419 => 'Session expired', 500 => 'Something went wrong'];
    $t = $titles[$code] ?? 'Error';
    if (function_exists('render_page') && defined('LAYOUT_READY')) { echo render_page('shop/error', ['code' => $code, 'title' => $t, 'msg' => $msg]); }
    else { echo "<h1>" . e($t) . "</h1><p>" . e($msg) . "</p>"; }
    exit;
}
function money($n): string {
    $d = (int)setting('currency_decimals', '0');
    $s = number_format((float)$n, $d, '.', ',');
    $sym = setting('currency_symbol', 'Rs.');
    return setting('currency_position', 'before') === 'after' ? "$s $sym" : "$sym $s";
}
function slugify(string $s): string {
    $s = strtolower(trim(preg_replace('~[^\pL\d]+~u', '-', $s), '-'));
    $s = preg_replace('~[^-\w]+~', '', $s);
    return $s !== '' ? $s : 'item';
}
function unique_slug(string $table, string $name, int $ignoreId = 0): string {
    $base = slugify($name); $slug = $base; $i = 2;
    while (val("SELECT COUNT(*) FROM `$table` WHERE slug=? AND id<>?", [$slug, $ignoreId])) $slug = $base . '-' . $i++;
    return $slug;
}
function now(): string { return date('Y-m-d H:i:s'); }
function fmt_date(?string $d, bool $time = true): string { return $d ? date($time ? 'd M Y, H:i' : 'd M Y', strtotime($d)) : ''; }

/* ---------- Flash + CSRF ---------- */
function flash(?string $type = null, ?string $msg = null): ?array {
    if ($type !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) abort(419, 'Your session expired. Please go back, refresh the page and try again.');
}
function old(string $k, $d = '') { return $_POST[$k] ?? $d; }

/* ---------- Pagination ---------- */
function paginate(int $total, int $per, int $page): array {
    $pages = max(1, (int)ceil($total / $per));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per, 'per' => $per, 'total' => $total];
}
function pager_url(int $p): string { $q = $_GET; $q['page'] = $p; return strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($q); }

/* ---------- Activity log, notifications, mail ---------- */
function log_activity(string $action, string $target = '', string $detail = ''): void {
    $u = function_exists('admin_user') ? admin_user() : null;
    insert('activity_log', ['user_name' => $u['name'] ?? 'System', 'action' => $action, 'target' => mb_substr($target, 0, 190), 'detail' => mb_substr($detail, 0, 500)]);
}
function admin_alert(string $subject, string $body = '', string $link = ''): void {
    insert('notifications', ['audience' => 'admin', 'subject' => $subject, 'body' => $body, 'link' => $link]);
}
function tpl(string $text, array $vars): string {
    foreach ($vars as $k => $v) $text = str_replace('{' . $k . '}', (string)$v, $text);
    return $text;
}
function send_mail(string $to, string $subject, string $body): bool {
    global $CONFIG;
    insert('notifications', ['audience' => 'customer', 'recipient' => $to, 'subject' => $subject, 'body' => $body]);
    if (empty($CONFIG['mail_enabled'])) return false;
    $from = setting('contact_email', 'noreply@localhost');
    $headers = "From: " . mb_encode_mimeheader(setting('store_name', 'Store')) . " <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, mb_encode_mimeheader($subject), $body, $headers);
}
function whatsapp_link(string $phone, string $text): string {
    $n = preg_replace('/\D+/', '', $phone);
    if (str_starts_with($n, '0')) $n = '92' . substr($n, 1);
    return 'https://wa.me/' . $n . '?text=' . rawurlencode($text);
}

/* ---------- Image upload (type check, size limit, resize) ---------- */
function save_image(array $f, string $subdir, int $maxW = 1200): array {
    if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) return [false, 'No file'];
    if ($f['error'] !== UPLOAD_ERR_OK) return [false, 'Upload failed'];
    if ($f['size'] > 3 * 1024 * 1024) return [false, 'Image is larger than 3 MB'];
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($types[$info[2]])) return [false, 'Only JPG, PNG, WEBP or GIF images are allowed'];
    $ext = $types[$info[2]];
    $dir = ROOT . '/uploads/' . $subdir;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = "$dir/$name";
    $done = false;
    if ($info[0] > $maxW && function_exists('imagecreatetruecolor') && $ext !== 'gif') {
        $src = match ($ext) { 'jpg' => @imagecreatefromjpeg($f['tmp_name']), 'png' => @imagecreatefrompng($f['tmp_name']), 'webp' => @imagecreatefromwebp($f['tmp_name']), default => false };
        if ($src) {
            $h = (int)round($info[1] * $maxW / $info[0]);
            $dst = imagecreatetruecolor($maxW, $h);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $h, $info[0], $info[1]);
            $done = match ($ext) { 'jpg' => imagejpeg($dst, $dest, 85), 'png' => imagepng($dst, $dest, 6), 'webp' => imagewebp($dst, $dest, 85), default => false };
        }
    }
    if (!$done && !move_uploaded_file($f['tmp_name'], $dest)) return [false, 'Could not save the image'];
    return [true, "uploads/$subdir/$name"];
}
function save_proof(array $f): array {
    if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) return [false, 'No file'];
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 3 * 1024 * 1024) return [false, 'Proof file must be an image or PDF under 3 MB'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime] ?? null;
    if (!$ext) return [false, 'Proof file must be an image or PDF'];
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!is_dir(ROOT . '/storage/proofs')) @mkdir(ROOT . '/storage/proofs', 0750, true);
    if (!move_uploaded_file($f['tmp_name'], ROOT . '/storage/proofs/' . $name)) return [false, 'Could not save the file'];
    return [true, $name];
}
function product_image_url(?string $file): string { return $file ? url($file) : theme_asset('placeholder.svg'); }
