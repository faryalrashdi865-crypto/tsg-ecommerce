<?php
declare(strict_types=1);
/* Tech SG Studio Reusable E-commerce Script - Setup Wizard. Runs once on a fresh installation. */
define('ROOT', dirname(__DIR__));
$self = $_SERVER['SCRIPT_NAME'];
$base = rtrim(str_replace('/install/index.php', '', $self), '/');
require ROOT . '/core/lib/defaults.php';
require __DIR__ . '/licence.php';
require __DIR__ . '/sample.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function slug(string $s): string { $s = strtolower(trim(preg_replace('~[^\pL\d]+~u', '-', $s), '-')); return preg_replace('~[^-\w]+~', '', $s) ?: 'item'; }

session_name('tsginstall'); session_start();
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

if (is_file(ROOT . '/config/config.php')) {
    page('Already installed', '<p>This store is already installed. For security, delete the <code>/install</code> folder from the server.</p><p><a class="btn" href="' . h($base) . '/admin">Go to the admin panel</a></p>', 0);
    exit;
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$W = &$_SESSION['wiz'];
$W ??= [];
$steps = ['Requirements', 'Licence and database', 'Owner and store', 'Business preset', 'Branding', 'Currency and tax', 'Payments', 'Shipping', 'Finish'];
$step = max(1, min(count($steps), (int)($_GET['step'] ?? 1)));
$errors = [];

function page(string $title, string $body, int $step = 0, array $steps = []): void {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Setup wizard</title><style>
    *{box-sizing:border-box}body{margin:0;font:16px/1.5 system-ui,sans-serif;background:#f3f5f8;color:#1c2430}.box{max-width:760px;margin:30px auto;background:#fff;border-radius:14px;padding:26px;box-shadow:0 8px 30px rgba(20,30,50,.1)}
    h1{margin:0 0 4px;font-size:1.5rem}.sub{color:#6b7684;margin:0 0 18px}.prog{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:18px}.prog span{font-size:.75rem;padding:3px 9px;border-radius:99px;background:#e8edf3}.prog .on{background:#1f6feb;color:#fff}.prog .done{background:#cfe3ff}
    label{display:block;font-weight:600;font-size:.9rem;margin-bottom:12px}input,select,textarea{display:block;width:100%;font:inherit;padding:9px 11px;border:1px solid #cfd6df;border-radius:8px;margin-top:4px;font-weight:400}input[type=checkbox],input[type=radio]{display:inline;width:auto;margin:0 6px 0 0}
    .two{display:grid;grid-template-columns:1fr 1fr;gap:12px}.btn{background:#1f6feb;color:#fff;border:0;padding:10px 22px;border-radius:8px;font:600 1rem system-ui;cursor:pointer;text-decoration:none;display:inline-block}.btn.alt{background:#e8edf3;color:#1c2430}
    .err{background:#fdecec;color:#7f1d1d;padding:9px 12px;border-radius:8px;margin-bottom:10px}.ok{color:#1a7f45}.bad{color:#c0262d}.muted{color:#6b7684;font-size:.88rem}table{width:100%;border-collapse:collapse}td{padding:7px;border-bottom:1px solid #eef1f5}
    .preset{display:block;border:2px solid #e1e6ec;border-radius:10px;padding:10px 12px;margin-bottom:8px;font-weight:600}.preset small{display:block;font-weight:400;color:#6b7684}.nav{display:flex;justify-content:space-between;margin-top:16px}
    </style></head><body><div class="box"><h1>' . h($title) . '</h1><p class="sub">Tech SG Studio Reusable E-commerce Script - version 1.0.0-phase1</p>';
    if ($steps) { echo '<div class="prog">'; foreach ($steps as $i => $s) echo '<span class="' . (($i + 1) === $step ? 'on' : (($i + 1) < $step ? 'done' : '')) . '">' . ($i + 1) . '. ' . h($s) . '</span>'; echo '</div>'; }
    echo $body . '</div></body></html>';
}
function field(string $name, string $label, $val = '', string $type = 'text', string $extra = ''): string {
    return '<label>' . h($label) . '<input type="' . $type . '" name="' . $name . '" value="' . h($val) . '" ' . $extra . '></label>';
}
function requirements(): array {
    $r = [
        ['PHP 8.0 or newer (found ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.0.0', '>='), true],
        ['PDO MySQL extension', extension_loaded('pdo_mysql'), true],
        ['mbstring extension', extension_loaded('mbstring'), true],
        ['fileinfo extension', extension_loaded('fileinfo'), true],
        ['GD extension (automatic image resizing)', extension_loaded('gd'), false],
        ['/config folder writable', is_writable(ROOT . '/config'), true],
        ['/uploads folder writable', is_writable(ROOT . '/uploads'), true],
        ['/storage folder writable', is_writable(ROOT . '/storage'), true],
    ];
    return $r;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['_csrf'] ?? ''))) { http_response_code(419); exit('Session expired. Go back and refresh.'); }
    $P = array_map(fn($v) => is_string($v) ? trim($v) : $v, $_POST);
    switch ($step) {
        case 1:
            foreach (requirements() as [$l, $ok, $must]) if ($must && !$ok) $errors[] = 'Fix this first: ' . $l;
            if (!$errors) { header('Location: ?step=2'); exit; }
            break;
        case 2:
            $W['licence'] = strtoupper($P['licence'] ?? '');
            $W['db'] = ['host' => $P['db_host'] ?? 'localhost', 'port' => (int)($P['db_port'] ?? 3306) ?: 3306, 'name' => $P['db_name'] ?? '', 'user' => $P['db_user'] ?? '', 'pass' => $_POST['db_pass'] ?? ''];
            if (!preg_match('/^TSG(-[A-Z0-9]{4}){4}$/', $W['licence'])) $errors[] = 'The licence key must look like TSG-XXXX-XXXX-XXXX-XXXX.';
            try {
                $d = $W['db'];
                $pdo = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                if ($pdo->query("SHOW TABLES LIKE 'settings'")->fetch()) $errors[] = 'This database already contains a store. Use an empty database.';
            } catch (Throwable $e) { $errors[] = 'Could not connect to the database. Check host, name, user and password.'; }
            if (!$errors) { header('Location: ?step=3'); exit; }
            break;
        case 3:
            $W['store'] = ['store_name' => $P['store_name'] ?? '', 'tagline' => $P['tagline'] ?? '', 'contact_email' => $P['contact_email'] ?? '', 'phone' => $P['phone'] ?? '', 'whatsapp_number' => $P['whatsapp_number'] ?? '', 'address' => $P['address'] ?? '', 'city' => $P['city'] ?? ''];
            $W['owner'] = ['name' => $P['owner_name'] ?? '', 'email' => strtolower($P['owner_email'] ?? ''), 'password' => $_POST['owner_password'] ?? ''];
            if ($W['store']['store_name'] === '') $errors[] = 'Please enter the store name.';
            if (!filter_var($W['owner']['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid owner email.';
            if ($W['owner']['name'] === '') $errors[] = 'Please enter the owner name.';
            if (strlen($W['owner']['password']) < 8 || !preg_match('/[A-Za-z]/', $W['owner']['password']) || !preg_match('/\d/', $W['owner']['password'])) $errors[] = 'The owner password needs at least 8 characters with letters and numbers.';
            if ($W['owner']['password'] !== ($_POST['owner_password2'] ?? '')) $errors[] = 'The two passwords do not match.';
            if (in_array(strtolower($W['owner']['password']), ['password1', 'admin123', '12345678a', 'changeme1'], true)) $errors[] = 'Please choose a stronger password (default passwords are not allowed).';
            if (!$errors) { header('Location: ?step=4'); exit; }
            break;
        case 4:
            $p = $P['preset'] ?? 'general';
            if (!isset(presets()[$p]) || !empty(presets()[$p]['disabled'])) $errors[] = 'Please choose one of the available presets.'; else $W['preset'] = $p;
            if (!$errors) { header('Location: ?step=5'); exit; }
            break;
        case 5:
            $W['brand'] = ['primary_color' => $P['primary_color'] ?? '#1f6feb', 'secondary_color' => $P['secondary_color'] ?? '#0b2545', 'accent_color' => $P['accent_color'] ?? '#f5a524', 'font_heading' => $P['font_heading'] ?? '', 'font_body' => $P['font_body'] ?? ''];
            foreach (['primary_color', 'secondary_color', 'accent_color'] as $c) if (!preg_match('/^#[0-9a-fA-F]{6}$/', $W['brand'][$c])) $errors[] = 'Colours must look like #1f6feb.';
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $info = @getimagesize($_FILES['logo']['tmp_name']);
                $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
                if (!$ext || $_FILES['logo']['size'] > 2 * 1024 * 1024) $errors[] = 'The logo must be a JPG, PNG or WEBP image under 2 MB.';
                else { $name = 'uploads/branding/logo-' . bin2hex(random_bytes(4)) . '.' . $ext; if (move_uploaded_file($_FILES['logo']['tmp_name'], ROOT . '/' . $name)) $W['brand']['logo'] = $name; }
            }
            if (!$errors) { header('Location: ?step=6'); exit; }
            break;
        case 6:
            $W['money'] = ['currency_code' => $P['currency_code'] ?? 'PKR', 'currency_symbol' => $P['currency_symbol'] ?? 'Rs.', 'currency_position' => ($P['currency_position'] ?? 'before') === 'after' ? 'after' : 'before', 'currency_decimals' => (string)max(0, min(3, (int)($P['currency_decimals'] ?? 0))),
                'tax_percent' => (string)max(0, (float)($P['tax_percent'] ?? 0)), 'tax_mode' => ($P['tax_mode'] ?? 'added') === 'included' ? 'included' : 'added', 'tax_label' => $P['tax_label'] ?? 'GST'];
            header('Location: ?step=7'); exit;
        case 7:
            $W['pay'] = [];
            foreach (['cod', 'jazzcash', 'easypaisa', 'bank'] as $c) $W['pay'][$c] = ['enabled' => isset($P['en_' . $c]) ? 1 : 0, 'details' => $P['det_' . $c] ?? '', 'fee' => max(0, (float)($P['fee_' . $c] ?? 0))];
            if (!array_sum(array_column($W['pay'], 'enabled'))) $errors[] = 'Enable at least one payment method.';
            if (!$errors) { header('Location: ?step=8'); exit; }
            break;
        case 8:
            $W['ship'] = ['local_city' => $P['local_city'] ?? '', 'local_rate' => max(0, (float)($P['local_rate'] ?? 0)), 'national_rate' => max(0, (float)($P['national_rate'] ?? 0)), 'free_over' => ($P['free_over'] ?? '') !== '' ? max(0, (float)$P['free_over']) : null, 'pickup' => isset($P['pickup']) ? 1 : 0];
            header('Location: ?step=9'); exit;
        case 9:
            $W['sample'] = isset($P['sample']) ? 1 : 0;
            $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
            $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
            $demo = $isLocal && ($W['licence'] ?? '') === 'TSG-DEMO-DEMO-DEMO-DEMO';
            if (!$demo && !licence_valid($W['licence'] ?? '', $W['store']['store_name'] ?? '', $host)) $errors[] = 'This licence key is not valid for the business name "' . h($W['store']['store_name'] ?? '') . '" on the domain "' . h($host) . '". Check with Tech SG Studio.';
            if (!$errors) { try { run_install($W, $base, $secure); } catch (Throwable $e) { $errors[] = 'Installation failed: ' . $e->getMessage(); } }
            break;
    }
}

function run_install(array $W, string $base, bool $secure): void {
    $d = $W['db'];
    $pdo = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    foreach (array_filter(array_map('trim', explode(";\n", (string)file_get_contents(ROOT . '/database/schema.sql')))) as $stmt) {
        if (preg_match('/^(--[^\n]*\n)*\s*$/', $stmt)) continue;
        $pdo->exec($stmt);
    }
    $preset = $W['preset'] ?? 'general';
    $s = default_settings($preset);
    $s = array_merge($s, $W['store'], $W['brand'] ?? [], $W['money'] ?? []);
    unset($s['city']);
    $s['logo'] = $W['brand']['logo'] ?? '';
    $s['licence_key'] = $W['licence'];
    $s['licence_issued'] = date('Y-m-d');
    $s['licence_lifetime'] = '0';
    $s['installed_version'] = '1.0.0-phase1';
    $s['test_mode'] = '1';
    $s['country'] = 'Pakistan';
    $ins = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?)');
    foreach ($s as $k => $v) $ins->execute([$k, (string)$v]);

    $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?, 'owner')")->execute([$W['owner']['name'], $W['owner']['email'], password_hash($W['owner']['password'], PASSWORD_DEFAULT)]);

    $pm = $pdo->prepare('INSERT INTO payment_methods (code,label,enabled,instructions,account_details,extra_fee,is_manual,position) VALUES (?,?,?,?,?,?,?,?)');
    foreach (default_payment_methods() as [$code, $label, $en, $inst, $acct, $fee, $manual, $pos]) {
        $c = $W['pay'][$code] ?? ['enabled' => $en, 'details' => $acct, 'fee' => $fee];
        $pm->execute([$code, $label, $c['enabled'], $inst, $manual ? ($c['details'] !== '' ? $c['details'] : $acct) : '', $c['fee'], $manual, $pos]);
    }

    $sh = $W['ship'] ?? ['local_city' => '', 'local_rate' => 200, 'national_rate' => 350, 'free_over' => 5000, 'pickup' => 0];
    $zone = $pdo->prepare('INSERT INTO shipping_zones (name,regions,free_over,position) VALUES (?,?,?,?)');
    $rate = $pdo->prepare('INSERT INTO shipping_rates (zone_id,name,type,rate,est_days,is_pickup) VALUES (?,?,?,?,?,?)');
    if ($sh['local_city'] !== '') {
        $zone->execute(['Local - ' . $sh['local_city'], $sh['local_city'], $sh['free_over'], 1]); $zid = (int)$pdo->lastInsertId();
        $rate->execute([$zid, 'Standard delivery', 'flat', $sh['local_rate'], '1-2 days', 0]);
        if ($sh['pickup']) $rate->execute([$zid, 'Store pickup', 'flat', 0, 'Same day', 1]);
    }
    $zone->execute(['Rest of Pakistan', '*', $sh['free_over'], 2]); $zid = (int)$pdo->lastInsertId();
    $rate->execute([$zid, 'Standard delivery', 'flat', $sh['national_rate'], '3-5 days', 0]);

    $store = $W['store']['store_name'];
    $pg = $pdo->prepare('INSERT INTO pages (title,slug,body,position) VALUES (?,?,?,?)');
    foreach ([
        ['About us', "Welcome to $store. Tell your customers who you are, what you sell and why they can trust you."],
        ['Contact', "Reach us by phone, email or WhatsApp. Our details are in the footer of every page."],
        ['Shipping policy', "We deliver across Pakistan. Delivery time and charges are shown at checkout before you pay."],
        ['Returns', "You can request a return from your account within the return period after delivery. Items must be unused and in original packaging."],
        ['Privacy policy', "We use your details only to process your order and support you. We share them only with couriers and payment providers needed for your order."],
        ['Terms and conditions', "By placing an order you agree to our prices, delivery, payment and return terms."],
        ['FAQ', "How do I track my order? Use the Track order link at the top of the site.\nHow can I pay? See the payment methods at checkout."],
    ] as $i => [$t, $b]) $pg->execute([$t, slug($t), $b, $i]);

    if (!empty($W['sample'])) {
        $sd = sample_data($preset);
        $cat = $pdo->prepare('INSERT INTO categories (name,slug,position) VALUES (?,?,?)'); $cids = [];
        foreach ($sd['cats'] as $i => $c) { $cat->execute([$c, slug($c), $i]); $cids[$i] = (int)$pdo->lastInsertId(); }
        $pr = $pdo->prepare('INSERT INTO products (name,slug,short_description,description,category_id,type,price,sale_price,stock,unit,is_featured,sku) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $vr = $pdo->prepare('INSERT INTO product_variants (product_id,options,sku,stock) VALUES (?,?,?,?)');
        $lg = $pdo->prepare('INSERT INTO inventory_log (product_id,variant_id,`change`,balance,reason,user_name) VALUES (?,?,?,?,?,?)');
        foreach ($sd['items'] as $i => [$name, $ci, $price, $sale, $stock, $desc, $vars]) {
            $pr->execute([$name, slug($name), $desc, $desc . ' (Sample product - replace or delete it in the admin panel.)', $cids[$ci], $vars ? 'variable' : 'simple', $price, $sale, $stock, $sd['unit'][$i] ?? null, $i < 4 ? 1 : 0, 'SKU-' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT)]);
            $pid = (int)$pdo->lastInsertId();
            if ($stock > 0) $lg->execute([$pid, null, $stock, $stock, 'Opening stock (sample data)', 'Setup wizard']);
            foreach ($vars as $vi => [$vals, $vs]) {
                $opts = array_combine(array_slice($sd['fields'], 0, count($vals)), $vals);
                $vr->execute([$pid, json_encode($opts), 'SKU-' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT) . '-' . ($vi + 1), $vs]);
                if ($vs > 0) $lg->execute([$pid, (int)$pdo->lastInsertId(), $vs, $vs, 'Opening stock (sample data)', 'Setup wizard']);
            }
        }
    }
    $pdo->prepare('INSERT INTO activity_log (user_name,action,target,detail) VALUES (?,?,?,?)')->execute(['Setup wizard', 'store installed', $store, 'version 1.0.0-phase1, preset ' . $preset]);

    $cfg = ['db' => $d, 'base_url' => '', 'timezone' => 'Asia/Karachi', 'debug' => false, 'mail_enabled' => true, 'version' => '1.0.0-phase1', 'installed_at' => date('c')];
    $php = "<?php\n// Generated by the setup wizard. Installation values only; everything the owner can change lives in the settings table.\nreturn " . var_export($cfg, true) . ";\n";
    if (file_put_contents(ROOT . '/config/config.php', $php) === false) throw new RuntimeException('Could not write config/config.php');
    @chmod(ROOT . '/config/config.php', 0640);
    session_destroy();
    page('Store installed', '<p class="ok"><strong>Your store is ready and running in test mode.</strong></p><ol><li>Log in to the admin panel with the owner account you just created.</li><li><strong>Delete the /install folder</strong> from the server now (security).</li><li>Add products, finish payment and shipping details, run test orders, then switch off test mode in Settings.</li></ol><p><a class="btn" href="' . h($base) . '/admin">Open the admin panel</a> <a class="btn alt" href="' . h($base) . '/">View the store</a></p>');
    exit;
}

/* ---------- Render the current step ---------- */
$csrf = '<input type="hidden" name="_csrf" value="' . h($_SESSION['csrf']) . '">';
$err = $errors ? '<div class="err">' . implode('<br>', array_map('h', array_map(fn($e) => html_entity_decode($e), $errors))) . '</div>' : '';
$nav = fn(bool $back = true, string $next = 'Continue') => '<div class="nav">' . ($back ? '<a class="btn alt" href="?step=' . ($GLOBALS['step'] - 1) . '">Back</a>' : '<span></span>') . '<button class="btn" type="submit">' . $next . '</button></div>';
$S = $W['store'] ?? []; $B = $W['brand'] ?? []; $M = $W['money'] ?? []; $D = $W['db'] ?? [];
$body = '';
switch ($step) {
    case 1:
        $body = '<h2>Server requirements</h2><table>';
        foreach (requirements() as [$l, $ok, $must]) $body .= '<tr><td>' . h($l) . '</td><td class="' . ($ok ? 'ok' : 'bad') . '">' . ($ok ? 'OK' : ($must ? 'Missing' : 'Optional')) . '</td></tr>';
        $body .= '</table><p class="muted">Also needed on the live server: SSL certificate (HTTPS), cron enabled and a scheduled backup. See the hosting checklist in the installation guide.</p>';
        $body = $err . '<form method="post">' . $csrf . $body . $nav(false) . '</form>'; break;
    case 2:
        $body = $err . '<form method="post">' . $csrf . '<h2>Licence key</h2>' . field('licence', 'Licence key issued by Tech SG Studio', $W['licence'] ?? '', 'text', 'placeholder="TSG-XXXX-XXXX-XXXX-XXXX" required')
            . '<h2>Database</h2><p class="muted">Create an empty MySQL database and user in cPanel first.</p><div class="two">' . field('db_host', 'Host', $D['host'] ?? 'localhost') . field('db_port', 'Port', (string)($D['port'] ?? 3306), 'number') . '</div>'
            . field('db_name', 'Database name', $D['name'] ?? '', 'text', 'required') . '<div class="two">' . field('db_user', 'Database user', $D['user'] ?? '', 'text', 'required') . field('db_pass', 'Database password', '', 'password') . '</div>' . $nav() . '</form>'; break;
    case 3:
        $body = $err . '<form method="post" autocomplete="off">' . $csrf . '<h2>Store details</h2>' . field('store_name', 'Store (business) name', $S['store_name'] ?? '', 'text', 'required') . field('tagline', 'Tagline', $S['tagline'] ?? '')
            . '<div class="two">' . field('contact_email', 'Store email', $S['contact_email'] ?? '', 'email') . field('phone', 'Phone', $S['phone'] ?? '') . '</div>'
            . '<div class="two">' . field('whatsapp_number', 'WhatsApp number (923001234567)', $S['whatsapp_number'] ?? '') . field('city', 'Your city', $S['city'] ?? '') . '</div>' . field('address', 'Address', $S['address'] ?? '')
            . '<h2>Store owner account</h2>' . field('owner_name', 'Owner name', $W['owner']['name'] ?? '', 'text', 'required') . field('owner_email', 'Owner email (login)', $W['owner']['email'] ?? '', 'email', 'required')
            . '<div class="two">' . field('owner_password', 'Password (8+ characters, letters and numbers)', '', 'password', 'required autocomplete="new-password"') . field('owner_password2', 'Repeat password', '', 'password', 'required autocomplete="new-password"') . '</div>' . $nav() . '</form>'; break;
    case 4:
        $body = $err . '<form method="post">' . $csrf . '<h2>Business preset</h2><p class="muted">A preset only switches modules and labels on or off. The owner can change everything later in Settings.</p>';
        foreach (presets() as $k => $p) { $dis = !empty($p['disabled']); $body .= '<label class="preset" style="' . ($dis ? 'opacity:.5' : '') . '"><input type="radio" name="preset" value="' . $k . '"' . ((($W['preset'] ?? 'general') === $k) ? ' checked' : '') . ($dis ? ' disabled' : '') . '>' . h($p['label']) . '<small>' . h($p['desc']) . '</small></label>'; }
        $body .= $nav() . '</form>'; break;
    case 5:
        $d = default_settings($W['preset'] ?? 'general');
        $body = $err . '<form method="post" enctype="multipart/form-data">' . $csrf . '<h2>Branding</h2><div class="two">' . field('primary_color', 'Primary colour', $B['primary_color'] ?? $d['primary_color'], 'color') . field('secondary_color', 'Secondary colour', $B['secondary_color'] ?? $d['secondary_color'], 'color') . '</div>'
            . field('accent_color', 'Accent colour', $B['accent_color'] ?? $d['accent_color'], 'color') . '<div class="two">' . field('font_heading', 'Heading font stack', $B['font_heading'] ?? $d['font_heading']) . field('font_body', 'Body font stack', $B['font_body'] ?? $d['font_body']) . '</div>'
            . '<label>Logo (optional, JPG/PNG/WEBP)<input type="file" name="logo" accept="image/*"></label>' . $nav() . '</form>'; break;
    case 6:
        $body = '<form method="post">' . $csrf . '<h2>Currency and tax</h2><div class="two">' . field('currency_code', 'Currency code', $M['currency_code'] ?? 'PKR') . field('currency_symbol', 'Symbol', $M['currency_symbol'] ?? 'Rs.') . '</div>'
            . '<div class="two"><label>Symbol position<select name="currency_position"><option value="before">Before the amount</option><option value="after"' . (($M['currency_position'] ?? '') === 'after' ? ' selected' : '') . '>After the amount</option></select></label>' . field('currency_decimals', 'Decimals', $M['currency_decimals'] ?? '0', 'number', 'min="0" max="3"') . '</div>'
            . '<div class="two">' . field('tax_percent', 'Tax percentage (0 for none)', $M['tax_percent'] ?? '0', 'number', 'step="0.01" min="0"') . field('tax_label', 'Tax label', $M['tax_label'] ?? 'GST') . '</div>'
            . '<label>Tax is<select name="tax_mode"><option value="added">Added on top of prices</option><option value="included"' . (($M['tax_mode'] ?? '') === 'included' ? ' selected' : '') . '>Already included in prices</option></select></label>' . $nav() . '</form>'; break;
    case 7:
        $body = $err . '<form method="post">' . $csrf . '<h2>Payment methods</h2><p class="muted">Card gateway arrives in Phase 3. For JazzCash, Easypaisa and bank transfer the customer uploads proof and staff verify it.</p>';
        foreach (default_payment_methods() as [$code, $label, $en, $inst, $acct, $fee, $manual]) {
            $c = $W['pay'][$code] ?? ['enabled' => $en, 'details' => '', 'fee' => 0];
            $body .= '<div class="preset"><label style="margin:0"><input type="checkbox" name="en_' . $code . '" value="1"' . ($c['enabled'] ? ' checked' : '') . '>' . h($label) . '</label>'
                . ($manual ? '<label>Account details shown to customers<textarea name="det_' . $code . '" rows="2" placeholder="' . h($acct) . '">' . h($c['details']) . '</textarea></label>' : '')
                . '<label>Extra fee (optional)<input type="number" step="0.01" min="0" name="fee_' . $code . '" value="' . h($c['fee']) . '"></label></div>';
        }
        $body .= $nav() . '</form>'; break;
    case 8:
        $sh = $W['ship'] ?? ['local_city' => $S['city'] ?? '', 'local_rate' => 200, 'national_rate' => 350, 'free_over' => 5000, 'pickup' => 0];
        $body = '<form method="post">' . $csrf . '<h2>Shipping</h2><p class="muted">Two starter zones are created: your local city and the rest of Pakistan. Add more zones, weight or price based rates later in the admin panel.</p>'
            . field('local_city', 'Local delivery city (leave empty to skip)', $sh['local_city']) . '<div class="two">' . field('local_rate', 'Local delivery charge', (string)$sh['local_rate'], 'number', 'min="0"') . field('national_rate', 'Rest of Pakistan charge', (string)$sh['national_rate'], 'number', 'min="0"') . '</div>'
            . field('free_over', 'Free shipping on orders from (empty = never)', (string)($sh['free_over'] ?? ''), 'number', 'min="0"') . '<label><input type="checkbox" name="pickup" value="1"' . (!empty($sh['pickup']) ? ' checked' : '') . '>Offer store pickup in the local zone</label>' . $nav() . '</form>'; break;
    case 9:
        $body = $err . '<form method="post">' . $csrf . '<h2>Ready to install</h2><table><tr><td>Store</td><td>' . h($S['store_name'] ?? '') . '</td></tr><tr><td>Owner</td><td>' . h($W['owner']['email'] ?? '') . '</td></tr><tr><td>Preset</td><td>' . h(presets()[$W['preset'] ?? 'general']['label']) . '</td></tr><tr><td>Database</td><td>' . h($D['name'] ?? '') . '</td></tr></table>'
            . '<p><label><input type="checkbox" name="sample" value="1"' . (($W['sample'] ?? 1) ? ' checked' : '') . '>Add sample products for this preset (recommended for the demo store; delete them before going live)</label></p>' . $nav(true, 'Install the store') . '</form>'; break;
}
page('Setup wizard - ' . $steps[$step - 1], $body, $step, $steps);
