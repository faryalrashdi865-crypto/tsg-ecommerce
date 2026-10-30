<?php
declare(strict_types=1);

/* ---------- Login / dashboard ---------- */
function admin_login(): void {
    if (admin_user()) redirect('admin');
    $err = '';
    if (is_post()) {
        [$u, $err] = try_login('users', (string)post('email'), (string)post('password'));
        if ($u) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$u['id'];
            update('users', ['last_login' => now()], 'id=?', [$u['id']]);
            redirect('admin');
        }
    }
    echo render('admin/login', ['err' => $err]);
    exit;
}
function admin_logout(): void { unset($_SESSION['admin_id']); session_regenerate_id(true); redirect('admin/login'); }

function admin_dashboard(): void {
    require_admin('dashboard');
    $d = ['title' => 'Dashboard'];
    $sumSince = fn(string $from, ?string $to = null) => (float)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('Cancelled','Returned') AND placed_at >= ?" . ($to ? ' AND placed_at < ?' : ''), $to ? [$from, $to] : [$from]);
    if (can('reports.view')) {
        $today = date('Y-m-d'); $wk = date('Y-m-d', strtotime('monday this week')); $mo = date('Y-m-01');
        $d['sales'] = [
            'Today' => [$sumSince($today), $sumSince(date('Y-m-d', strtotime('-1 day')), $today)],
            'This week' => [$sumSince($wk), $sumSince(date('Y-m-d', strtotime($wk . ' -7 days')), $wk)],
            'This month' => [$sumSince($mo), $sumSince(date('Y-m-01', strtotime('-1 month')), $mo)],
        ];
    }
    if (can('orders.manage')) {
        $d['statusCounts'] = array_column(rows('SELECT status, COUNT(*) n FROM orders GROUP BY status'), 'n', 'status');
        $d['pendingVerify'] = (int)val("SELECT COUNT(*) FROM payments pa JOIN orders o ON o.id=pa.order_id WHERE pa.status='Pending' AND pa.method<>'cod' AND o.status NOT IN ('Cancelled','Returned')");
        $d['codToCollect'] = (float)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_method='cod' AND payment_status='Unpaid' AND status IN ('Shipped')");
        $d['top'] = rows("SELECT oi.name, SUM(oi.qty) qty, SUM(oi.qty*oi.price) revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status NOT IN ('Cancelled','Returned') GROUP BY oi.name ORDER BY qty DESC LIMIT 5");
        $d['recent'] = rows('SELECT * FROM orders ORDER BY id DESC LIMIT 6');
    }
    if (can('customers.view')) {
        $d['newCustomers'] = (int)val("SELECT COUNT(*) FROM customers WHERE created_at >= ?", [date('Y-m-d', strtotime('-30 days'))]);
        $d['repeat'] = (int)val('SELECT COUNT(*) FROM (SELECT cust_email FROM orders GROUP BY cust_email HAVING COUNT(*)>1) t');
    }
    if (can('inventory.view')) $d['lowStock'] = low_stock_items(8);
    $d['alerts'] = rows("SELECT * FROM notifications WHERE audience='admin' ORDER BY id DESC LIMIT 6");
    admin_page('admin/dashboard', $d);
}
function low_stock_items(int $limit = 100): array {
    return rows("SELECT * FROM (
        SELECT p.id, p.name, NULL AS variant_id, '' AS variant, p.stock, p.low_stock_level, p.sku FROM products p WHERE p.track_stock=1 AND p.type<>'variable' AND p.status<>'draft'
        UNION ALL
        SELECT p.id, p.name, v.id, v.options, v.stock, p.low_stock_level, COALESCE(v.sku,p.sku) FROM products p JOIN product_variants v ON v.product_id=p.id WHERE p.track_stock=1 AND p.status<>'draft'
    ) t WHERE stock <= low_stock_level ORDER BY stock ASC, name LIMIT " . (int)$limit);
}

/* ---------- Products ---------- */
function admin_products(): void {
    require_admin('products.view');
    $term = get('q'); $w = '1=1'; $p = [];
    if ($term !== '') { $w = '(p.name LIKE ? OR p.sku LIKE ?)'; $p = ["%$term%", "%$term%"]; }
    if (in_array(get('status'), ['draft', 'published', 'hidden'], true)) { $w .= ' AND p.status=?'; $p[] = get('status'); }
    $pg = paginate((int)val("SELECT COUNT(*) FROM products p WHERE $w", $p), 20, (int)get('page', '1'));
    $list = rows("SELECT p.*, c.name AS category_name, (SELECT file FROM product_images WHERE product_id=p.id ORDER BY position,id LIMIT 1) AS image,
        (CASE WHEN p.type='variable' THEN (SELECT COALESCE(SUM(stock),0) FROM product_variants WHERE product_id=p.id) ELSE p.stock END) AS total_stock
        FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE $w ORDER BY p.id DESC LIMIT 20 OFFSET " . $pg['offset'], $p);
    admin_page('admin/products', ['title' => 'Products', 'list' => $list, 'pg' => $pg, 'term' => $term]);
}
function admin_product_form(?string $id = null): void {
    require_admin('products.manage');
    $p = $id ? row('SELECT * FROM products WHERE id=?', [(int)$id]) : null;
    if ($id && !$p) abort(404, 'Product not found.');
    admin_page('admin/product_form', [
        'title' => $p ? 'Edit product' : 'Add product', 'p' => $p,
        'variants' => $p ? rows('SELECT * FROM product_variants WHERE product_id=? ORDER BY id', [$p['id']]) : [],
        'images' => $p ? rows('SELECT * FROM product_images WHERE product_id=? ORDER BY position,id', [$p['id']]) : [],
        'categories' => rows('SELECT * FROM categories ORDER BY name'), 'brands' => rows('SELECT * FROM brands ORDER BY name'),
    ]);
}
function nullable_num($v): ?float { return ($v === '' || $v === null || !is_numeric($v)) ? null : (float)$v; }
function admin_product_save(): void {
    require_admin('products.manage');
    $id = (int)post('id');
    $name = (string)post('name');
    if ($name === '') { flash('error', 'Product name is required.'); redirect($id ? "admin/products/$id/edit" : 'admin/products/new'); }
    $type = in_array(post('type'), ['simple', 'variable'], true) ? post('type') : 'simple';
    $price = max(0, (float)post('price'));
    $sale = nullable_num(post('sale_price'));
    $d = [
        'name' => $name, 'short_description' => (string)post('short_description') ?: null, 'description' => (string)post('description') ?: null,
        'specifications' => (string)post('specifications') ?: null, 'warranty' => (string)post('warranty') ?: null, 'ingredients' => (string)post('ingredients') ?: null,
        'category_id' => (int)post('category_id') ?: null, 'brand_id' => (int)post('brand_id') ?: null, 'type' => $type, 'sku' => (string)post('sku') ?: null,
        'price' => $price, 'sale_price' => ($sale !== null && $sale < $price) ? $sale : null,
        'sale_starts' => (string)post('sale_starts') ?: null, 'sale_ends' => (string)post('sale_ends') ?: null, 'cost_price' => nullable_num(post('cost_price')),
        'track_stock' => post('track_stock') ? 1 : 0, 'low_stock_level' => max(0, (int)post('low_stock_level', '5')), 'allow_backorder' => post('allow_backorder') ? 1 : 0,
        'weight' => max(0, (float)post('weight')), 'unit' => (string)post('unit') ?: null, 'tags' => (string)post('tags') ?: null,
        'seo_title' => (string)post('seo_title') ?: null, 'seo_description' => (string)post('seo_description') ?: null, 'video_url' => (string)post('video_url') ?: null,
        'is_featured' => post('is_featured') ? 1 : 0, 'status' => in_array(post('status'), ['draft', 'published', 'hidden'], true) ? post('status') : 'published',
    ];
    if ($id) {
        $old = row('SELECT * FROM products WHERE id=?', [$id]);
        if (!$old) abort(404);
        $d['slug'] = unique_slug('products', $name, $id);
        update('products', $d, 'id=?', [$id]);
        $oldStock = (int)$old['stock']; $newStock = (int)post('stock');
        if ($type === 'simple' && $d['track_stock'] && $newStock !== $oldStock) adjust_stock($id, null, $newStock - $oldStock, 'Manual edit on product page');
        if ((float)$old['price'] !== $price || ($old['sale_price'] !== null ? (float)$old['sale_price'] : null) !== $d['sale_price']) log_activity('price changed', $name, "Price {$old['price']} -> $price" . ($d['sale_price'] !== null ? ", sale {$d['sale_price']}" : ''));
        else log_activity('product updated', $name);
    } else {
        $d['slug'] = unique_slug('products', $name);
        $d['stock'] = $type === 'simple' ? max(0, (int)post('stock')) : 0;
        $id = insert('products', $d);
        if ($d['stock'] > 0 && $d['track_stock']) insert('inventory_log', ['product_id' => $id, 'change' => $d['stock'], 'balance' => $d['stock'], 'reason' => 'Opening stock', 'user_name' => admin_user()['name']]);
        log_activity('product created', $name);
    }
    /* variants: parallel arrays */
    if ($type === 'variable') {
        $keep = [];
        $fields = array_values(array_filter(array_map('trim', explode(',', setting('variant_fields', 'Option')))));
        foreach ((array)post('v_id', []) as $i => $vid) {
            $opts = [];
            foreach ($fields as $fi => $fname) { $val = trim((string)($_POST['v_opt' . $fi][$i] ?? '')); if ($val !== '') $opts[$fname] = $val; }
            if (!$opts) continue;
            $vd = ['options' => json_encode($opts, JSON_UNESCAPED_UNICODE), 'sku' => trim((string)($_POST['v_sku'][$i] ?? '')) ?: null, 'price' => nullable_num($_POST['v_price'][$i] ?? '')];
            $newStock = max(0, (int)($_POST['v_stock'][$i] ?? 0));
            if ((int)$vid > 0 && row('SELECT id FROM product_variants WHERE id=? AND product_id=?', [(int)$vid, $id])) {
                $oldS = (int)val('SELECT stock FROM product_variants WHERE id=?', [(int)$vid]);
                update('product_variants', $vd, 'id=?', [(int)$vid]);
                if ($newStock !== $oldS) adjust_stock($id, (int)$vid, $newStock - $oldS, 'Manual edit on product page');
                $keep[] = (int)$vid;
            } else {
                $nv = insert('product_variants', $vd + ['product_id' => $id, 'stock' => $newStock]);
                if ($newStock > 0) insert('inventory_log', ['product_id' => $id, 'variant_id' => $nv, 'change' => $newStock, 'balance' => $newStock, 'reason' => 'Opening stock', 'user_name' => admin_user()['name']]);
                $keep[] = $nv;
            }
        }
        if ($keep) q('DELETE FROM product_variants WHERE product_id=? AND id NOT IN (' . implode(',', $keep) . ')', [$id]);
        else q('DELETE FROM product_variants WHERE product_id=?', [$id]);
    }
    /* images */
    $files = $_FILES['images'] ?? null; $msgs = [];
    if ($files && is_array($files['name'])) {
        $pos = (int)val('SELECT COALESCE(MAX(position),0) FROM product_images WHERE product_id=?', [$id]);
        foreach ($files['name'] as $i => $n) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            [$ok, $res] = save_image(['name' => $n, 'tmp_name' => $files['tmp_name'][$i], 'size' => $files['size'][$i], 'error' => $files['error'][$i]], 'products');
            if ($ok) insert('product_images', ['product_id' => $id, 'file' => $res, 'position' => ++$pos]); else $msgs[] = "$n: $res";
        }
    }
    flash('success', 'Product saved.');
    foreach ($msgs as $m) flash('error', $m);
    redirect("admin/products/$id/edit");
}
function admin_product_delete(string $id): void {
    require_admin('products.manage');
    $p = row('SELECT name FROM products WHERE id=?', [(int)$id]);
    if ($p) {
        foreach (rows('SELECT file FROM product_images WHERE product_id=?', [(int)$id]) as $im) @unlink(ROOT . '/' . $im['file']);
        q('DELETE FROM products WHERE id=?', [(int)$id]);
        log_activity('product deleted', $p['name']);
        flash('success', 'Product deleted.');
    }
    redirect('admin/products');
}
function admin_product_image_delete(string $id): void {
    require_admin('products.manage');
    $im = row('SELECT * FROM product_images WHERE id=?', [(int)$id]);
    if ($im) { @unlink(ROOT . '/' . $im['file']); q('DELETE FROM product_images WHERE id=?', [$im['id']]); }
    redirect('admin/products/' . ($im['product_id'] ?? 0) . '/edit');
}

/* ---------- Categories and brands ---------- */
function admin_categories(): void {
    require_admin('products.manage');
    admin_page('admin/categories', ['title' => 'Categories and brands',
        'cats' => rows('SELECT c.*, p.name AS parent_name, (SELECT COUNT(*) FROM products WHERE category_id=c.id) AS n FROM categories c LEFT JOIN categories p ON p.id=c.parent_id ORDER BY COALESCE(p.name,c.name), c.parent_id IS NOT NULL, c.position, c.name'),
        'tops' => rows('SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name'), 'brands' => rows('SELECT * FROM brands ORDER BY name')]);
}
function admin_category_save(): void {
    require_admin('products.manage');
    $id = (int)post('id'); $name = (string)post('name');
    if ($name === '') { flash('error', 'Category name is required.'); redirect('admin/categories'); }
    $parent = (int)post('parent_id') ?: null;
    if ($parent === $id) $parent = null;
    $d = ['name' => $name, 'parent_id' => $parent, 'position' => (int)post('position'), 'status' => post('status') === 'hidden' ? 'hidden' : 'active'];
    if ($id) { $d['slug'] = unique_slug('categories', $name, $id); update('categories', $d, 'id=?', [$id]); }
    else { $d['slug'] = unique_slug('categories', $name); insert('categories', $d); }
    log_activity($id ? 'category updated' : 'category created', $name);
    flash('success', 'Category saved.');
    redirect('admin/categories');
}
function admin_category_delete(string $id): void {
    require_admin('products.manage');
    q('DELETE FROM categories WHERE id=?', [(int)$id]);
    log_activity('category deleted', "#$id");
    redirect('admin/categories');
}
function admin_brand_save(): void {
    require_admin('products.manage');
    $name = (string)post('name');
    if ($name !== '') { insert('brands', ['name' => $name, 'slug' => unique_slug('brands', $name)]); flash('success', 'Brand added.'); }
    redirect('admin/categories');
}
function admin_brand_delete(string $id): void { require_admin('products.manage'); q('DELETE FROM brands WHERE id=?', [(int)$id]); redirect('admin/categories'); }

/* ---------- Inventory ---------- */
function admin_inventory(): void {
    require_admin('inventory.view');
    $rowsAll = rows("SELECT * FROM (
        SELECT p.id, p.name, NULL AS variant_id, '' AS variant, p.stock, p.low_stock_level, p.sku, p.track_stock FROM products p WHERE p.type<>'variable'
        UNION ALL SELECT p.id, p.name, v.id, v.options, v.stock, p.low_stock_level, COALESCE(v.sku,p.sku), p.track_stock FROM products p JOIN product_variants v ON v.product_id=p.id
    ) t ORDER BY name, variant");
    admin_page('admin/inventory', ['title' => 'Inventory', 'items' => $rowsAll]);
}
function admin_inventory_adjust(): void {
    require_admin('inventory.manage');
    $pid = (int)post('product_id'); $vid = (int)post('variant_id') ?: null; $delta = (int)post('delta');
    if ($delta === 0) { flash('error', 'Enter a quantity to add or remove.'); redirect('admin/inventory'); }
    $reason = (string)post('reason') ?: ($delta > 0 ? 'Stock added' : 'Stock removed');
    adjust_stock($pid, $vid, $delta, $reason);
    log_activity('stock adjusted', (string)val('SELECT name FROM products WHERE id=?', [$pid]), "$delta ($reason)");
    flash('success', 'Stock updated.');
    redirect('admin/inventory');
}
function admin_inventory_history(): void {
    require_admin('inventory.view');
    $pid = (int)get('product_id');
    $w = $pid ? 'WHERE l.product_id=' . $pid : '';
    admin_page('admin/inventory_history', ['title' => 'Stock history', 'log' => rows("SELECT l.*, p.name, v.options FROM inventory_log l JOIN products p ON p.id=l.product_id LEFT JOIN product_variants v ON v.id=l.variant_id $w ORDER BY l.id DESC LIMIT 200")]);
}

/* ---------- Orders ---------- */
function admin_orders(): void {
    require_admin('orders.manage');
    $w = ['1=1']; $p = [];
    if (in_array(get('status'), order_statuses(), true)) { $w[] = 'status=?'; $p[] = get('status'); }
    if (get('q') !== '') { $w[] = '(number LIKE ? OR cust_name LIKE ? OR cust_email LIKE ? OR cust_phone LIKE ?)'; $l = '%' . get('q') . '%'; array_push($p, $l, $l, $l, $l); }
    $ws = implode(' AND ', $w);
    $pg = paginate((int)val("SELECT COUNT(*) FROM orders WHERE $ws", $p), 25, (int)get('page', '1'));
    admin_page('admin/orders', ['title' => 'Orders', 'list' => rows("SELECT * FROM orders WHERE $ws ORDER BY id DESC LIMIT 25 OFFSET " . $pg['offset'], $p), 'pg' => $pg]);
}
function admin_order_view(string $id): void {
    require_admin('orders.manage');
    $o = row('SELECT * FROM orders WHERE id=?', [(int)$id]);
    if (!$o) abort(404, 'Order not found.');
    $wa = whatsapp_link($o['cust_phone'], 'Hello ' . $o['cust_name'] . ', this is ' . setting('store_name') . ' about your order ' . $o['number'] . ' (' . money($o['total']) . ').');
    admin_page('admin/order_view', order_context($o) + ['title' => 'Order ' . $o['number'], 'wa' => $wa]);
}
function admin_order_invoice(string $id): void {
    require_admin('orders.manage');
    $o = row('SELECT * FROM orders WHERE id=?', [(int)$id]);
    if (!$o) abort(404);
    echo render('admin/invoice', order_context($o) + ['slip' => get('slip') === '1']);
    exit;
}
function admin_order_status(string $id): void {
    $u = require_admin('orders.manage');
    $err = set_order_status((int)$id, (string)post('status'), (string)post('note'), $u['name']);
    if ($err) flash('error', $err); else { log_activity('order status', '#' . $id, (string)post('status')); flash('success', 'Order updated.'); }
    redirect('admin/orders/' . (int)$id);
}
function admin_order_tracking(string $id): void {
    require_admin('orders.manage');
    update('orders', ['courier' => (string)post('courier') ?: null, 'tracking_number' => (string)post('tracking_number') ?: null, 'admin_note' => (string)post('admin_note') ?: null], 'id=?', [(int)$id]);
    log_activity('tracking saved', '#' . $id);
    flash('success', 'Courier and notes saved.');
    redirect('admin/orders/' . (int)$id);
}
function admin_order_payment(string $id): void {
    $u = require_admin('orders.manage');
    $o = row('SELECT * FROM orders WHERE id=?', [(int)$id]);
    if (!$o) abort(404);
    $act = (string)post('action');
    if ($act === 'verify') { update('orders', ['payment_status' => 'Paid'], 'id=?', [$o['id']]); q("UPDATE payments SET status='Verified', verified_by=? WHERE order_id=?", [$u['name'], $o['id']]); }
    elseif ($act === 'reject') { update('orders', ['payment_status' => 'Failed'], 'id=?', [$o['id']]); q("UPDATE payments SET status='Rejected', verified_by=? WHERE order_id=?", [$u['name'], $o['id']]); }
    elseif ($act === 'collected') { update('orders', ['payment_status' => 'COD Collected'], 'id=?', [$o['id']]); q("UPDATE payments SET status='Collected', verified_by=? WHERE order_id=?", [$u['name'], $o['id']]); }
    else redirect('admin/orders/' . $o['id']);
    insert('order_status_log', ['order_id' => $o['id'], 'status' => $o['status'], 'note' => 'Payment ' . $act, 'changed_by' => $u['name']]);
    log_activity('payment ' . $act, $o['number']);
    flash('success', 'Payment updated.');
    redirect('admin/orders/' . $o['id']);
}
function admin_proof(string $file): void {
    require_admin('orders.manage');
    $file = basename($file);
    $path = ROOT . '/storage/proofs/' . $file;
    if (!is_file($path)) abort(404);
    header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
    header('Content-Disposition: inline; filename="' . $file . '"');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

/* ---------- Customers ---------- */
function admin_customers(): void {
    require_admin('customers.view');
    $term = get('q'); $w = '1=1'; $p = [];
    if ($term !== '') { $w = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)'; $p = ["%$term%", "%$term%", "%$term%"]; }
    admin_page('admin/customers', ['title' => 'Customers', 'term' => $term, 'list' => rows("SELECT c.*, (SELECT COUNT(*) FROM orders WHERE customer_id=c.id) AS orders, (SELECT COALESCE(SUM(total),0) FROM orders WHERE customer_id=c.id AND status NOT IN ('Cancelled','Returned')) AS spent FROM customers c WHERE $w ORDER BY c.id DESC LIMIT 200", $p)]);
}
function admin_customer_toggle(string $id): void {
    require_admin('customers.manage');
    $c = row('SELECT * FROM customers WHERE id=?', [(int)$id]);
    if ($c) { $new = $c['status'] === 'active' ? 'blocked' : 'active'; update('customers', ['status' => $new], 'id=?', [$c['id']]); log_activity('customer ' . $new, $c['email']); }
    redirect('admin/customers');
}

/* ---------- Shipping and payments (owner only) ---------- */
function admin_shipping(): void {
    require_admin('settings.manage');
    $zones = rows('SELECT * FROM shipping_zones ORDER BY position,id');
    foreach ($zones as &$z) $z['rates'] = rows('SELECT * FROM shipping_rates WHERE zone_id=? ORDER BY id', [$z['id']]);
    admin_page('admin/shipping', ['title' => 'Shipping', 'zones' => $zones]);
}
function admin_shipping_zone_save(): void {
    require_admin('settings.manage');
    $id = (int)post('id');
    $d = ['name' => (string)post('name'), 'regions' => (string)post('regions') ?: '*', 'free_over' => nullable_num(post('free_over')), 'active' => post('active') ? 1 : 0, 'position' => (int)post('position')];
    if ($d['name'] === '') { flash('error', 'Zone name is required.'); redirect('admin/shipping'); }
    $id ? update('shipping_zones', $d, 'id=?', [$id]) : insert('shipping_zones', $d);
    log_activity('shipping zone saved', $d['name']);
    flash('success', 'Zone saved.');
    redirect('admin/shipping');
}
function admin_shipping_zone_delete(string $id): void { require_admin('settings.manage'); q('DELETE FROM shipping_zones WHERE id=?', [(int)$id]); log_activity('shipping zone deleted', "#$id"); redirect('admin/shipping'); }
function admin_shipping_rate_save(): void {
    require_admin('settings.manage');
    $id = (int)post('id');
    $type = in_array(post('type'), ['flat', 'weight', 'price'], true) ? post('type') : 'flat';
    $d = ['zone_id' => (int)post('zone_id'), 'name' => (string)post('name'), 'type' => $type, 'min_value' => max(0, (float)post('min_value')), 'max_value' => nullable_num(post('max_value')),
        'rate' => max(0, (float)post('rate')), 'est_days' => (string)post('est_days') ?: null, 'is_pickup' => post('is_pickup') ? 1 : 0];
    if ($d['name'] === '' || !val('SELECT COUNT(*) FROM shipping_zones WHERE id=?', [$d['zone_id']])) { flash('error', 'Rate name and zone are required.'); redirect('admin/shipping'); }
    if ($id) { unset($d['zone_id']); update('shipping_rates', $d, 'id=?', [$id]); } else insert('shipping_rates', $d);
    log_activity('shipping rate saved', $d['name']);
    flash('success', 'Rate saved.');
    redirect('admin/shipping');
}
function admin_shipping_rate_delete(string $id): void { require_admin('settings.manage'); q('DELETE FROM shipping_rates WHERE id=?', [(int)$id]); redirect('admin/shipping'); }

function admin_payments(): void { require_admin('settings.manage'); admin_page('admin/payments', ['title' => 'Payment methods', 'methods' => rows('SELECT * FROM payment_methods ORDER BY position')]); }
function admin_payments_save(): void {
    require_admin('settings.manage');
    foreach (rows('SELECT code FROM payment_methods') as $m) {
        $c = $m['code'];
        update('payment_methods', ['label' => (string)($_POST['label'][$c] ?? $c), 'enabled' => isset($_POST['enabled'][$c]) ? 1 : 0, 'instructions' => (string)($_POST['instructions'][$c] ?? ''),
            'account_details' => (string)($_POST['account_details'][$c] ?? ''), 'extra_fee' => max(0, (float)($_POST['extra_fee'][$c] ?? 0))], 'code=?', [$c]);
    }
    if (!val('SELECT COUNT(*) FROM payment_methods WHERE enabled=1')) { q("UPDATE payment_methods SET enabled=1 WHERE code='cod'"); flash('info', 'At least one method must stay on, so Cash on Delivery was kept enabled.'); }
    log_activity('payment methods saved');
    flash('success', 'Payment methods saved.');
    redirect('admin/payments');
}

/* ---------- Content ---------- */
function admin_pages(): void { require_admin('content.manage'); admin_page('admin/pages', ['title' => 'Pages', 'list' => rows('SELECT * FROM pages ORDER BY position,id')]); }
function admin_page_form(?string $id = null): void {
    require_admin('content.manage');
    $pg = $id ? row('SELECT * FROM pages WHERE id=?', [(int)$id]) : null;
    if ($id && !$pg) abort(404);
    admin_page('admin/page_form', ['title' => $pg ? 'Edit page' : 'New page', 'pg' => $pg]);
}
function admin_page_save(): void {
    require_admin('content.manage');
    $id = (int)post('id'); $title = (string)post('title');
    if ($title === '') { flash('error', 'Title is required.'); redirect('admin/pages'); }
    $d = ['title' => $title, 'body' => (string)post('body'), 'show_in_footer' => post('show_in_footer') ? 1 : 0, 'status' => post('status') === 'draft' ? 'draft' : 'published', 'position' => (int)post('position')];
    if ($id) { $d['slug'] = unique_slug('pages', $title, $id); update('pages', $d, 'id=?', [$id]); } else { $d['slug'] = unique_slug('pages', $title); insert('pages', $d); }
    log_activity('page saved', $title);
    flash('success', 'Page saved.');
    redirect('admin/pages');
}
function admin_page_delete(string $id): void { require_admin('content.manage'); q('DELETE FROM pages WHERE id=?', [(int)$id]); redirect('admin/pages'); }
function admin_banners(): void { require_admin('content.manage'); admin_page('admin/banners', ['title' => 'Homepage banners', 'list' => rows('SELECT * FROM banners ORDER BY position,id')]); }
function admin_banner_save(): void {
    require_admin('content.manage');
    $id = (int)post('id');
    $d = ['title' => (string)post('title') ?: null, 'subtitle' => (string)post('subtitle') ?: null, 'link' => (string)post('link') ?: null, 'button_text' => (string)post('button_text') ?: null, 'position' => (int)post('position'), 'active' => post('active') ? 1 : 0];
    if (!empty($_FILES['image']['name'])) { [$ok, $res] = save_image($_FILES['image'], 'banners', 1800); if ($ok) $d['image'] = $res; else flash('error', $res); }
    $id ? update('banners', $d, 'id=?', [$id]) : insert('banners', $d);
    log_activity('banner saved', (string)$d['title']);
    flash('success', 'Banner saved.');
    redirect('admin/banners');
}
function admin_banner_delete(string $id): void {
    require_admin('content.manage');
    if ($b = row('SELECT image FROM banners WHERE id=?', [(int)$id])) { if ($b['image']) @unlink(ROOT . '/' . $b['image']); q('DELETE FROM banners WHERE id=?', [(int)$id]); }
    redirect('admin/banners');
}

/* ---------- Settings: one place, whole website ---------- */
function themes_available(): array {
    $out = [];
    foreach (glob(ROOT . '/themes/*/theme.json') as $f) { $j = json_decode((string)file_get_contents($f), true); if ($j) $out[basename(dirname($f))] = $j; }
    return $out;
}
function admin_settings(): void {
    require_admin('settings.manage');
    admin_page('admin/settings', ['title' => 'Settings', 'themes' => themes_available(), 'presets' => presets()]);
}
function admin_settings_save(): void {
    require_admin('settings.manage');
    $text = ['store_name', 'tagline', 'contact_email', 'phone', 'whatsapp_number', 'address', 'country', 'facebook', 'instagram', 'footer_text', 'currency_code', 'currency_symbol',
        'tax_label', 'font_heading', 'font_body', 'size_guide_text', 'tpl_order_placed_subject', 'tpl_order_placed_body', 'tpl_order_status_subject', 'tpl_order_status_body', 'wa_order_text', 'variant_fields'];
    foreach ($text as $k) if (isset($_POST[$k])) set_setting($k, trim((string)$_POST[$k]));
    foreach (['primary_color', 'secondary_color', 'accent_color'] as $k) if (preg_match('/^#[0-9a-fA-F]{6}$/', (string)post($k))) set_setting($k, post($k));
    foreach (['tax_percent', 'min_order_amount'] as $k) set_setting($k, (string)max(0, (float)post($k)));
    foreach (['return_days', 'products_per_page', 'currency_decimals'] as $k) set_setting($k, (string)max(0, (int)post($k)));
    set_setting('currency_position', post('currency_position') === 'after' ? 'after' : 'before');
    set_setting('tax_mode', post('tax_mode') === 'included' ? 'included' : 'added');
    $themes = themes_available();
    if (isset($themes[post('theme')])) set_setting('theme', post('theme'));
    foreach (['variants', 'specs', 'units', 'ingredients', 'warranty', 'size_guide', 'brand_filter', 'whatsapp_button', 'guest_checkout'] as $m) set_setting('mod_' . $m, post('mod_' . $m) ? '1' : '0');
    set_setting('test_mode', post('test_mode') ? '1' : '0');
    set_setting('force_https', post('force_https') ? '1' : '0');
    $sections = array_values(array_intersect(array_map('trim', explode(',', (string)post('home_sections'))), ['banner', 'featured', 'categories', 'new']));
    set_setting('home_sections', implode(',', $sections));
    foreach (['logo' => 'logo', 'favicon' => 'favicon'] as $field => $key) {
        if (!empty($_FILES[$field]['name'])) { [$ok, $res] = save_image($_FILES[$field], 'branding', 600); if ($ok) set_setting($key, $res); else flash('error', ucfirst($field) . ': ' . $res); }
    }
    if (post('remove_logo')) set_setting('logo', '');
    log_activity('settings saved');
    flash('success', 'Settings saved. Every page, email and invoice now uses them.');
    redirect('admin/settings');
}

/* ---------- Staff, activity, backup ---------- */
function admin_staff(): void { require_admin('staff.manage'); admin_page('admin/staff', ['title' => 'Staff accounts', 'list' => rows('SELECT * FROM users ORDER BY id')]); }
function admin_staff_save(): void {
    $me = require_admin('staff.manage');
    $id = (int)post('id'); $role = (string)post('role');
    if (!isset(role_labels()[$role]) || ($role === 'owner' && $id !== (int)$me['id'] && !val("SELECT COUNT(*) FROM users WHERE id=? AND role='owner'", [$id]))) $role = 'manager';
    $name = (string)post('name'); $email = strtolower((string)post('email')); $pw = (string)post('password');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Name and a valid email are required.'); redirect('admin/staff'); }
    if ($pw !== '' && ($m = password_problem($pw))) { flash('error', $m); redirect('admin/staff'); }
    if (val('SELECT COUNT(*) FROM users WHERE email=? AND id<>?', [$email, $id])) { flash('error', 'That email is already used.'); redirect('admin/staff'); }
    $status = post('status') === 'disabled' ? 'disabled' : 'active';
    if ($id) {
        $target = row('SELECT * FROM users WHERE id=?', [$id]);
        if (!$target) abort(404);
        if ($target['role'] === 'owner') { $role = 'owner'; $status = 'active'; }
        $d = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status];
        if ($pw !== '') $d['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
        update('users', $d, 'id=?', [$id]);
    } else {
        if ($pw === '') { flash('error', 'A password is required for new staff.'); redirect('admin/staff'); }
        if ($role === 'owner') $role = 'manager';
        insert('users', ['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT)]);
    }
    log_activity('staff saved', $email, $role);
    flash('success', 'Staff account saved.');
    redirect('admin/staff');
}
function admin_activity(): void {
    require_admin('staff.manage');
    admin_page('admin/activity', ['title' => 'Activity log', 'list' => rows('SELECT * FROM activity_log ORDER BY id DESC LIMIT 300')]);
}
function admin_backup(): void {
    require_admin('settings.manage');
    $pdo = db(); $out = "-- Backup of " . setting('store_name') . " (" . TSG_VERSION . ") " . now() . "\nSET FOREIGN_KEY_CHECKS=0;\n";
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $out .= "\nDROP TABLE IF EXISTS `$t`;\n" . $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1] . ";\n";
        foreach ($pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_NUM) as $r) {
            $out .= "INSERT INTO `$t` VALUES (" . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $r)) . ");\n";
        }
    }
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    log_activity('backup downloaded');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="backup-' . date('Ymd-His') . '.sql"');
    echo $out;
    exit;
}
