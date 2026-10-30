<?php
declare(strict_types=1);

const PRICE_SQL = "(CASE WHEN p.sale_price IS NOT NULL AND p.sale_price < p.price AND (p.sale_starts IS NULL OR p.sale_starts <= CURDATE()) AND (p.sale_ends IS NULL OR p.sale_ends >= CURDATE()) THEN p.sale_price ELSE p.price END)";

function shop_cards(string $where = '1=1', array $params = [], string $order = 'p.id DESC', int $limit = 12, int $offset = 0): array {
    $sql = "SELECT p.*, " . PRICE_SQL . " AS final_price,
        (SELECT file FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.position,pi.id LIMIT 1) AS image,
        (CASE WHEN p.track_stock=0 OR p.allow_backorder=1 THEN 1
              WHEN p.type='variable' THEN (SELECT COALESCE(SUM(stock),0)>0 FROM product_variants WHERE product_id=p.id)
              ELSE p.stock>0 END) AS in_stock
        FROM products p WHERE p.status='published' AND ($where) ORDER BY $order LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    return rows($sql, $params);
}
function shop_count(string $where, array $params): int { return (int)val("SELECT COUNT(*) FROM products p WHERE p.status='published' AND ($where)", $params); }
function category_ids(int $id): array {
    $ids = [$id];
    foreach (rows('SELECT id FROM categories WHERE parent_id=?', [$id]) as $c) $ids[] = (int)$c['id'];
    return $ids;
}

function shop_home(): void {
    $sections = array_filter(array_map('trim', explode(',', setting('home_sections', 'banner,featured,categories,new'))));
    $data = [
        'title' => setting('store_name') . (setting('tagline') ? ' - ' . setting('tagline') : ''),
        'sections' => $sections,
        'banners' => rows('SELECT * FROM banners WHERE active=1 ORDER BY position,id'),
        'featured' => shop_cards('p.is_featured=1', [], 'p.id DESC', 8),
        'new' => shop_cards('1=1', [], 'p.id DESC', 8),
        'categories' => rows("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.status='published') AS n FROM categories c WHERE c.parent_id IS NULL AND c.status='active' ORDER BY c.position,c.name"),
    ];
    page('shop/home', $data);
}

function shop_catalogue(?string $categorySlug = null): void {
    $where = ['1=1']; $params = []; $cat = null;
    if ($categorySlug !== null) {
        $cat = row("SELECT * FROM categories WHERE slug=? AND status='active'", [$categorySlug]);
        if (!$cat) abort(404, 'Category not found.');
        $ids = category_ids((int)$cat['id']);
        $where[] = 'p.category_id IN (' . implode(',', $ids) . ')';
    }
    $term = get('q');
    if ($term !== '') {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.tags LIKE ? OR SOUNDEX(p.name)=SOUNDEX(?))';
        array_push($params, $like, $like, $like, $term);
    }
    if (($b = (int)get('brand')) > 0) { $where[] = 'p.brand_id=?'; $params[] = $b; }
    if (get('min') !== '' && is_numeric(get('min'))) { $where[] = PRICE_SQL . ' >= ?'; $params[] = (float)get('min'); }
    if (get('max') !== '' && is_numeric(get('max'))) { $where[] = PRICE_SQL . ' <= ?'; $params[] = (float)get('max'); }
    $sorts = ['new' => 'p.id DESC', 'price_asc' => 'final_price ASC', 'price_desc' => 'final_price DESC', 'name' => 'p.name ASC'];
    $sort = array_key_exists(get('sort'), $sorts) ? get('sort') : 'new';
    $w = implode(' AND ', $where);
    $per = max(4, (int)setting('products_per_page', '12'));
    $pg = paginate(shop_count($w, $params), $per, (int)get('page', '1'));
    page('shop/catalogue', [
        'title' => $cat ? $cat['name'] : ($term !== '' ? 'Search: ' . $term : 'Shop'),
        'category' => $cat, 'term' => $term, 'sort' => $sort, 'pg' => $pg,
        'products' => shop_cards($w, $params, $sorts[$sort], $per, $pg['offset']),
        'brands' => module_on('brand_filter') ? rows('SELECT * FROM brands ORDER BY name') : [],
        'categories' => rows("SELECT * FROM categories WHERE parent_id IS NULL AND status='active' ORDER BY position,name"),
    ]);
}
function shop_category(string $slug): void { shop_catalogue($slug); }

function shop_product(string $slug): void {
    $p = row("SELECT p.*, b.name AS brand_name, c.name AS category_name, c.slug AS category_slug FROM products p
              LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published'", [$slug]);
    if (!$p) abort(404, 'Product not found.');
    q('UPDATE products SET views=views+1 WHERE id=?', [$p['id']]);
    $variants = $p['type'] === 'variable' ? rows('SELECT * FROM product_variants WHERE product_id=? ORDER BY id', [$p['id']]) : [];
    $related = $p['category_id'] ? shop_cards('p.category_id=? AND p.id<>?', [$p['category_id'], $p['id']], 'p.id DESC', 4) : [];
    $specs = [];
    foreach (preg_split('/\R/', (string)$p['specifications']) as $line) {
        if (str_contains($line, ':')) { [$k, $v] = array_map('trim', explode(':', $line, 2)); if ($k !== '') $specs[$k] = $v; }
    }
    $wa = '';
    if (module_on('whatsapp_button') && setting('whatsapp_number')) {
        $wa = whatsapp_link(setting('whatsapp_number'), "Hello, I want to order: {$p['name']} (" . money(product_price($p)) . ")\n" . site_url('product/' . $p['slug']));
    }
    page('shop/product', [
        'title' => $p['seo_title'] ?: $p['name'], 'meta_description' => $p['seo_description'] ?: ($p['short_description'] ?? ''),
        'p' => $p, 'variants' => $variants, 'images' => rows('SELECT * FROM product_images WHERE product_id=? ORDER BY position,id', [$p['id']]),
        'related' => $related, 'specs' => $specs, 'wa' => $wa, 'in_stock' => product_in_stock($p, $variants),
    ]);
}

function shop_page(string $slug): void {
    $pg = row("SELECT * FROM pages WHERE slug=? AND status='published'", [$slug]);
    if (!$pg) abort(404, 'Page not found.');
    page('shop/page', ['title' => $pg['title'], 'pg' => $pg]);
}

/* ---------- Cart ---------- */
function cart_view(): void {
    $lines = cart_lines();
    page('shop/cart', ['title' => 'Your cart', 'lines' => $lines, 'subtotal' => cart_subtotal($lines)]);
}
function cart_add_action(): void {
    $err = cart_add((int)post('product_id'), post('variant_id') !== '' ? (int)post('variant_id') : null, max(1, (int)post('qty', '1')));
    if ($err) { flash('error', $err); redirect(post('back') ?: 'cart'); }
    flash('success', 'Added to your cart.');
    redirect(post('buy_now') ? 'checkout' : 'cart');
}
function cart_update_action(): void {
    $cid = cart_id();
    foreach ((array)post('qty', []) as $itemId => $qty) {
        $it = $cid ? row('SELECT * FROM cart_items WHERE id=? AND cart_id=?', [(int)$itemId, $cid]) : null;
        if (!$it) continue;
        $qty = (int)$qty;
        if ($qty <= 0) { q('DELETE FROM cart_items WHERE id=?', [$it['id']]); continue; }
        $p = row('SELECT * FROM products WHERE id=?', [$it['product_id']]);
        $v = $it['variant_id'] ? row('SELECT * FROM product_variants WHERE id=?', [$it['variant_id']]) : null;
        $av = $p ? available_qty($p, $v) : 0;
        if ($av !== null && $qty > $av) { $qty = max(0, $av); flash('error', 'Quantity reduced to available stock for ' . $p['name'] . '.'); }
        $qty > 0 ? q('UPDATE cart_items SET qty=? WHERE id=?', [min($qty, 99), $it['id']]) : q('DELETE FROM cart_items WHERE id=?', [$it['id']]);
    }
    redirect('cart');
}
function cart_remove_action(): void {
    if ($cid = cart_id()) q('DELETE FROM cart_items WHERE id=? AND cart_id=?', [(int)post('item_id'), $cid]);
    redirect('cart');
}

/* ---------- Checkout ---------- */
function checkout_prefill(): array {
    $c = customer();
    $f = ['name' => '', 'email' => '', 'phone' => '', 'line1' => '', 'city' => '', 'province' => '', 'postal' => '', 'note' => '', 'rate' => '', 'pm' => ''];
    if ($c) {
        $f['name'] = $c['name']; $f['email'] = $c['email']; $f['phone'] = (string)$c['phone'];
        if ($a = row('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC,id LIMIT 1', [$c['id']])) {
            $f = array_merge($f, ['name' => $a['name'], 'phone' => $a['phone'], 'line1' => $a['line1'], 'city' => $a['city'], 'province' => $a['province'], 'postal' => (string)$a['postal']]);
        }
    }
    return $f;
}
function checkout_render(array $f, array $errors = []): never {
    $lines = cart_lines();
    if (!$lines) redirect('cart');
    $sub = cart_subtotal($lines); $weight = array_sum(array_column($lines, 'weight'));
    $rates = ($f['city'] !== '' || $f['province'] !== '') ? shipping_options($f['city'], $f['province'], $sub, $weight) : [];
    $pms = enabled_payment_methods();
    $rate = null; foreach ($rates as $r) if ((string)$r['id'] === (string)$f['rate']) $rate = $r;
    $pm = null; foreach ($pms as $x) if ($x['code'] === $f['pm']) $pm = $x;
    page('shop/checkout', ['title' => 'Checkout', 'f' => $f, 'errors' => $errors, 'lines' => $lines, 'subtotal' => $sub, 'rates' => $rates,
        'pms' => $pms, 'totals' => order_totals($sub, $rate['cost'] ?? 0, $pm), 'weight' => $weight]);
}
function checkout_form(): void {
    if (!customer() && !module_on('guest_checkout')) { $_SESSION['after_login'] = BASE . '/checkout'; flash('info', 'Please log in to check out.'); redirect('account/login'); }
    checkout_render(checkout_prefill());
}
function checkout_quote(): void {
    $lines = cart_lines();
    $sub = cart_subtotal($lines); $weight = array_sum(array_column($lines, 'weight'));
    $rates = shipping_options(get('city'), get('province'), $sub, $weight);
    $rate = null; foreach ($rates as $r) if ((string)$r['id'] === get('rate')) $rate = $r;
    $rate ??= $rates[0] ?? null;
    $pm = row('SELECT * FROM payment_methods WHERE code=? AND enabled=1', [get('pm')]);
    $t = order_totals($sub, $rate['cost'] ?? 0, $pm);
    json_out([
        'rates' => array_map(fn($r) => $r + ['cost_fmt' => $r['cost'] > 0 ? money($r['cost']) : 'Free'], $rates),
        'selected' => $rate['id'] ?? null,
        'totals' => ['subtotal' => money($t['subtotal']), 'shipping' => money($t['shipping']), 'tax' => money($t['tax']), 'tax_added' => $t['tax_added'], 'fee' => money($t['fee']), 'total' => money($t['total'])],
    ]);
}
function checkout_submit(): void {
    if (!customer() && !module_on('guest_checkout')) redirect('account/login');
    $f = [];
    foreach (['name', 'email', 'phone', 'line1', 'city', 'province', 'postal', 'note', 'rate', 'pm'] as $k) $f[$k] = (string)post($k);
    $lines = cart_lines();
    if (!$lines) redirect('cart');
    if (post('action') === 'update') checkout_render($f);

    $err = [];
    if ($f['name'] === '') $err['name'] = 'Please enter your name.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Please enter a valid email address.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $f['phone'])) $err['phone'] = 'Please enter a valid phone number.';
    if ($f['line1'] === '') $err['line1'] = 'Please enter your address.';
    if ($f['city'] === '') $err['city'] = 'Please enter your city.';
    if ($f['province'] === '') $err['province'] = 'Please choose your province.';
    $sub = cart_subtotal($lines); $weight = array_sum(array_column($lines, 'weight'));
    $min = (float)setting('min_order_amount', '0');
    if ($min > 0 && $sub < $min) $err['cart'] = 'The minimum order amount is ' . money($min) . '.';
    foreach ($lines as $l) if (!$l['ok']) $err['cart'] = '"' . $l['product']['name'] . '" does not have enough stock. Please update your cart.';
    $rate = null; foreach (shipping_options($f['city'], $f['province'], $sub, $weight) as $r) if ((string)$r['id'] === $f['rate']) $rate = $r;
    if (!$rate) $err['rate'] = 'Please choose a shipping method.';
    $pm = row('SELECT * FROM payment_methods WHERE code=? AND enabled=1', [$f['pm']]);
    if (!$pm) $err['pm'] = 'Please choose a payment method.';
    $proof = null;
    if ($pm && (int)$pm['is_manual']) {
        $txn = (string)post('txn_id');
        [$ok, $res] = save_proof($_FILES['proof'] ?? []);
        if ($pm['code'] !== 'bank' && $txn === '') $err['txn_id'] = 'Please enter the transaction ID of your payment.';
        if (!$ok) $err['proof'] = $res === 'No file' ? 'Please upload your payment screenshot or receipt.' : $res;
        else $proof = ['txn' => $txn ?: null, 'file' => $res];
    }
    if ($err) {
        if ($proof) @unlink(ROOT . '/storage/proofs/' . $proof['file']);
        if ($pm && (int)$pm['is_manual']) $err['_proof_note'] = 'Please choose your payment file again.';
        checkout_render($f, $err);
    }
    [$order, $e] = place_order($f, $lines, $rate, $pm, $proof);
    if (!$order) { if ($proof) @unlink(ROOT . '/storage/proofs/' . $proof['file']); checkout_render($f, ['cart' => $e]); }
    redirect('order/' . $order['number'] . '?t=' . $order['access_token']);
}

/* ---------- Order confirmation and tracking ---------- */
function order_context(array $o): array {
    return [
        'o' => $o, 'items' => rows('SELECT * FROM order_items WHERE order_id=?', [$o['id']]),
        'log' => rows('SELECT * FROM order_status_log WHERE order_id=? ORDER BY id', [$o['id']]),
        'pm' => row('SELECT * FROM payment_methods WHERE code=?', [$o['payment_method']]),
        'payment' => row('SELECT * FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1', [$o['id']]),
    ];
}
function order_confirmation(string $number): void {
    $o = row('SELECT * FROM orders WHERE number=?', [$number]);
    $okToken = $o && hash_equals($o['access_token'], (string)get('t'));
    $okOwner = $o && customer() && (int)$o['customer_id'] === (int)customer()['id'];
    if (!$o || (!$okToken && !$okOwner)) abort(404, 'Order not found.');
    $wa = setting('whatsapp_number') ? whatsapp_link(setting('whatsapp_number'), tpl(setting('wa_order_text'), ['store_name' => setting('store_name'), 'order_number' => $o['number'], 'total' => money($o['total'])])) : '';
    page('shop/order', order_context($o) + ['title' => 'Order ' . $o['number'], 'wa' => $wa, 'just_placed' => $o['status'] === 'Pending']);
}
function order_track(): void {
    $o = null; $err = '';
    if (is_post()) {
        $o = row('SELECT * FROM orders WHERE number=? AND cust_email=?', [strtoupper(post('number')), strtolower(post('email'))]);
        if (!$o) $err = 'We could not find an order with that number and email.';
    }
    page('shop/track', ['title' => 'Track your order', 'err' => $err] + ($o ? order_context($o) : ['o' => null]));
}
