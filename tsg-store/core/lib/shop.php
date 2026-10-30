<?php
declare(strict_types=1);

/* ---------- Pricing and stock ---------- */
function sale_active(array $p): bool {
    if ($p['sale_price'] === null || $p['sale_price'] === '') return false;
    $today = date('Y-m-d');
    if (!empty($p['sale_starts']) && $today < $p['sale_starts']) return false;
    if (!empty($p['sale_ends']) && $today > $p['sale_ends']) return false;
    return (float)$p['sale_price'] < (float)$p['price'];
}
function product_price(array $p): float { return sale_active($p) ? (float)$p['sale_price'] : (float)$p['price']; }
function unit_price(array $p, ?array $v): float {
    return ($v && $v['price'] !== null && $v['price'] !== '') ? (float)$v['price'] : product_price($p);
}
function variant_label(?array $v): string {
    if (!$v) return '';
    $o = json_decode($v['options'], true) ?: [];
    return implode(', ', array_map(fn($k, $x) => "$k: $x", array_keys($o), $o));
}
/* null = unlimited */
function available_qty(array $p, ?array $v): ?int {
    if (!(int)$p['track_stock'] || (int)$p['allow_backorder']) return null;
    return $v ? (int)$v['stock'] : (int)$p['stock'];
}
function product_in_stock(array $p, array $variants = []): bool {
    if (!(int)$p['track_stock'] || (int)$p['allow_backorder']) return true;
    if ($p['type'] === 'variable') { foreach ($variants as $v) if ((int)$v['stock'] > 0) return true; return false; }
    return (int)$p['stock'] > 0;
}
function product_main_image(int $pid): ?string { return val('SELECT file FROM product_images WHERE product_id=? ORDER BY position,id LIMIT 1', [$pid]); }

function adjust_stock(int $pid, ?int $vid, int $delta, string $reason, ?string $by = null): void {
    $p = row('SELECT id,name,track_stock,stock,low_stock_level FROM products WHERE id=?', [$pid]);
    if (!$p || !(int)$p['track_stock']) return;
    if ($vid) { q('UPDATE product_variants SET stock=stock+? WHERE id=? AND product_id=?', [$delta, $vid, $pid]); $bal = (int)val('SELECT stock FROM product_variants WHERE id=?', [$vid]); }
    else { q('UPDATE products SET stock=stock+? WHERE id=?', [$delta, $pid]); $bal = (int)val('SELECT stock FROM products WHERE id=?', [$pid]); }
    insert('inventory_log', ['product_id' => $pid, 'variant_id' => $vid, 'change' => $delta, 'balance' => $bal, 'reason' => $reason, 'user_name' => $by ?? (admin_user()['name'] ?? 'System')]);
    $level = (int)$p['low_stock_level'];
    if ($delta < 0 && $bal <= $level && $bal - $delta > $level) admin_alert('Low stock: ' . $p['name'], "Stock is down to $bal.", 'admin/inventory');
}

/* ---------- Cart (saved in the database; session token for visitors) ---------- */
function cart_id(bool $create = false): ?int {
    $c = customer();
    if ($c) {
        $id = val('SELECT id FROM carts WHERE customer_id=? ORDER BY id LIMIT 1', [$c['id']]);
        if (!$id && $create) $id = insert('carts', ['customer_id' => $c['id']]);
        return $id ? (int)$id : null;
    }
    $tok = $_SESSION['cart_token'] ?? null;
    $id = $tok ? val('SELECT id FROM carts WHERE token=? AND customer_id IS NULL', [$tok]) : null;
    if (!$id && $create) { $tok = bin2hex(random_bytes(16)); $_SESSION['cart_token'] = $tok; $id = insert('carts', ['token' => $tok]); }
    return $id ? (int)$id : null;
}
function cart_merge_after_login(int $customerId): void {
    $tok = $_SESSION['cart_token'] ?? null;
    if (!$tok) return;
    $guest = val('SELECT id FROM carts WHERE token=? AND customer_id IS NULL', [$tok]);
    if (!$guest) return;
    $mine = val('SELECT id FROM carts WHERE customer_id=? ORDER BY id LIMIT 1', [$customerId]);
    if (!$mine) { update('carts', ['customer_id' => $customerId, 'token' => null], 'id=?', [$guest]); }
    else {
        foreach (rows('SELECT * FROM cart_items WHERE cart_id=?', [$guest]) as $it) cart_put((int)$mine, (int)$it['product_id'], $it['variant_id'] ? (int)$it['variant_id'] : null, (int)$it['qty']);
        q('DELETE FROM carts WHERE id=?', [$guest]);
    }
    unset($_SESSION['cart_token']);
}
function cart_put(int $cartId, int $pid, ?int $vid, int $qty): void {
    $ex = row('SELECT id,qty FROM cart_items WHERE cart_id=? AND product_id=? AND ((variant_id IS NULL AND ? IS NULL) OR variant_id=?)', [$cartId, $pid, $vid, $vid]);
    if ($ex) q('UPDATE cart_items SET qty=qty+? WHERE id=?', [$qty, $ex['id']]);
    else insert('cart_items', ['cart_id' => $cartId, 'product_id' => $pid, 'variant_id' => $vid, 'qty' => $qty]);
}
/** @return string|null error message */
function cart_add(int $pid, ?int $vid, int $qty): ?string {
    $p = row("SELECT * FROM products WHERE id=? AND status='published'", [$pid]);
    if (!$p) return 'This product is not available.';
    $v = null;
    if ($p['type'] === 'variable') {
        $v = $vid ? row('SELECT * FROM product_variants WHERE id=? AND product_id=?', [$vid, $pid]) : null;
        if (!$v) return 'Please choose an option first.';
    } else $vid = null;
    $qty = max(1, min(99, $qty));
    $cid = cart_id(true);
    $have = (int)val('SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE cart_id=? AND product_id=? AND ((variant_id IS NULL AND ? IS NULL) OR variant_id=?)', [$cid, $pid, $vid, $vid]);
    $avail = available_qty($p, $v);
    if ($avail !== null && $have + $qty > $avail) return $avail > 0 ? "Only $avail in stock." : 'Sorry, this item is out of stock.';
    cart_put($cid, $pid, $vid, $qty);
    return null;
}
function cart_lines(): array {
    $cid = cart_id();
    if (!$cid) return [];
    $lines = [];
    foreach (rows('SELECT * FROM cart_items WHERE cart_id=? ORDER BY id', [$cid]) as $it) {
        $p = row("SELECT * FROM products WHERE id=? AND status='published'", [$it['product_id']]);
        if (!$p) { q('DELETE FROM cart_items WHERE id=?', [$it['id']]); continue; }
        $v = $it['variant_id'] ? row('SELECT * FROM product_variants WHERE id=?', [$it['variant_id']]) : null;
        if ($p['type'] === 'variable' && !$v) { q('DELETE FROM cart_items WHERE id=?', [$it['id']]); continue; }
        $price = unit_price($p, $v);
        $avail = available_qty($p, $v);
        $lines[] = ['id' => (int)$it['id'], 'product' => $p, 'variant' => $v, 'qty' => (int)$it['qty'], 'price' => $price,
            'total' => $price * (int)$it['qty'], 'label' => variant_label($v), 'image' => product_main_image((int)$p['id']),
            'avail' => $avail, 'ok' => $avail === null || (int)$it['qty'] <= $avail, 'weight' => (float)$p['weight'] * (int)$it['qty']];
    }
    return $lines;
}
function cart_count(): int {
    $cid = cart_id();
    return $cid ? (int)val('SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE cart_id=?', [$cid]) : 0;
}
function cart_subtotal(array $lines): float { return array_sum(array_column($lines, 'total')); }

/* ---------- Shipping and totals ---------- */
function shipping_options(string $city, string $province, float $subtotal, float $weight): array {
    $zone = null; $fallback = null;
    foreach (rows('SELECT * FROM shipping_zones WHERE active=1 ORDER BY position,id') as $z) {
        $regs = array_map(fn($r) => mb_strtolower(trim($r)), explode(',', $z['regions']));
        if (in_array('*', $regs, true)) { $fallback ??= $z; continue; }
        if (($city !== '' && in_array(mb_strtolower($city), $regs, true)) || ($province !== '' && in_array(mb_strtolower($province), $regs, true))) { $zone = $z; break; }
    }
    $zone ??= $fallback;
    if (!$zone) return [];
    $out = [];
    foreach (rows('SELECT * FROM shipping_rates WHERE zone_id=? ORDER BY rate,id', [$zone['id']]) as $r) {
        $v = $r['type'] === 'weight' ? $weight : ($r['type'] === 'price' ? $subtotal : null);
        if ($v !== null && ($v < (float)$r['min_value'] || ($r['max_value'] !== null && $v > (float)$r['max_value']))) continue;
        $cost = (float)$r['rate'];
        if (!(int)$r['is_pickup'] && $zone['free_over'] !== null && $subtotal >= (float)$zone['free_over']) $cost = 0.0;
        $out[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'cost' => $cost, 'est_days' => $r['est_days'], 'is_pickup' => (int)$r['is_pickup'], 'zone' => $zone['name']];
    }
    return $out;
}
function order_totals(float $subtotal, float $shipping, ?array $pm): array {
    $pct = (float)setting('tax_percent', '0');
    $added = setting('tax_mode', 'added') === 'added';
    $tax = $pct > 0 ? ($added ? $subtotal * $pct / 100 : $subtotal - $subtotal / (1 + $pct / 100)) : 0.0;
    $fee = $pm ? (float)$pm['extra_fee'] : 0.0;
    $total = $subtotal + $shipping + $fee + ($added ? $tax : 0);
    return ['subtotal' => round($subtotal, 2), 'shipping' => round($shipping, 2), 'tax' => round($tax, 2), 'tax_added' => $added, 'fee' => round($fee, 2), 'total' => round($total, 2)];
}
function enabled_payment_methods(): array { return rows('SELECT * FROM payment_methods WHERE enabled=1 ORDER BY position'); }

/* ---------- Orders ---------- */
function new_order_number(): string {
    do { $n = 'TSG-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)); } while (val('SELECT COUNT(*) FROM orders WHERE number=?', [$n]));
    return $n;
}
function order_link(array $o): string { return site_url('order/' . $o['number'] . '?t=' . $o['access_token']); }

/** @return array{0:?array,1:?string} [order, error] */
function place_order(array $d, array $lines, array $rate, array $pm, ?array $proof): array {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($lines as $l) {
            if (!$l['ok']) throw new RuntimeException('"' . $l['product']['name'] . '" does not have enough stock. Please update your cart.');
        }
        $sub = cart_subtotal($lines);
        $t = order_totals($sub, $rate['cost'], $pm);
        $number = new_order_number();
        $oid = insert('orders', [
            'number' => $number, 'access_token' => bin2hex(random_bytes(16)), 'customer_id' => customer()['id'] ?? null,
            'cust_name' => $d['name'], 'cust_email' => strtolower($d['email']), 'cust_phone' => $d['phone'],
            'ship_line1' => $d['line1'], 'ship_city' => $d['city'], 'ship_province' => $d['province'], 'ship_postal' => $d['postal'],
            'subtotal' => $t['subtotal'], 'shipping_cost' => $t['shipping'], 'tax_amount' => $t['tax'], 'fee_amount' => $t['fee'], 'total' => $t['total'],
            'shipping_method' => $rate['name'], 'payment_method' => $pm['code'], 'note' => $d['note'] ?: null,
        ]);
        foreach ($lines as $l) {
            insert('order_items', ['order_id' => $oid, 'product_id' => $l['product']['id'], 'variant_id' => $l['variant']['id'] ?? null,
                'name' => $l['product']['name'], 'variant_label' => $l['label'] ?: null, 'sku' => ($l['variant']['sku'] ?? null) ?: $l['product']['sku'],
                'price' => $l['price'], 'cost_price' => $l['product']['cost_price'], 'qty' => $l['qty']]);
        }
        insert('payments', ['order_id' => $oid, 'method' => $pm['code'], 'amount' => $t['total'], 'txn_id' => $proof['txn'] ?? null, 'proof_file' => $proof['file'] ?? null]);
        insert('order_status_log', ['order_id' => $oid, 'status' => 'Pending', 'note' => 'Order placed', 'changed_by' => 'Customer']);
        if ($cid = cart_id()) q('DELETE FROM cart_items WHERE cart_id=?', [$cid]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return [null, $e instanceof RuntimeException ? $e->getMessage() : 'Could not place the order. Please try again.'];
    }
    $o = row('SELECT * FROM orders WHERE id=?', [$oid]);
    $vars = ['name' => $o['cust_name'], 'order_number' => $o['number'], 'total' => money($o['total']), 'store_name' => setting('store_name'), 'link' => order_link($o)];
    send_mail($o['cust_email'], tpl(setting('tpl_order_placed_subject'), $vars), tpl(setting('tpl_order_placed_body'), $vars));
    admin_alert('New order ' . $o['number'], $o['cust_name'] . ' - ' . money($o['total']), 'admin/orders/' . $o['id']);
    if ($pm['is_manual'] && !empty($proof['file'])) admin_alert('Payment proof uploaded for ' . $o['number'], $pm['label'], 'admin/orders/' . $o['id']);
    return [$o, null];
}

/** Moves an order to a new status, handling stock, payment status and notifications. Returns an error message or null. */
function set_order_status(int $oid, string $new, string $note = '', ?string $by = null): ?string {
    $o = row('SELECT * FROM orders WHERE id=?', [$oid]);
    if (!$o) return 'Order not found.';
    if (!in_array($new, order_statuses(), true)) return 'Unknown status.';
    if ($o['status'] === $new) return null;
    $by ??= admin_user()['name'] ?? 'System';
    $items = rows('SELECT * FROM order_items WHERE order_id=?', [$oid]);
    $upd = ['status' => $new];

    $deductOn = ['Confirmed', 'Packed', 'Shipped', 'Delivered', 'Completed'];
    if (in_array($new, $deductOn, true) && !(int)$o['stock_deducted']) {
        foreach ($items as $it) {
            if (!$it['product_id']) continue;
            $p = row('SELECT * FROM products WHERE id=?', [$it['product_id']]);
            $v = $it['variant_id'] ? row('SELECT * FROM product_variants WHERE id=?', [$it['variant_id']]) : null;
            if ($p) { $av = available_qty($p, $v); if ($av !== null && $av < (int)$it['qty']) return 'Not enough stock for "' . $it['name'] . '" (' . $av . ' left). Update the inventory first.'; }
        }
        foreach ($items as $it) if ($it['product_id']) adjust_stock((int)$it['product_id'], $it['variant_id'] ? (int)$it['variant_id'] : null, -(int)$it['qty'], 'Order ' . $o['number'] . ' confirmed', $by);
        $upd['stock_deducted'] = 1;
    }
    if (in_array($new, ['Cancelled', 'Returned'], true) && (int)$o['stock_deducted']) {
        foreach ($items as $it) if ($it['product_id']) adjust_stock((int)$it['product_id'], $it['variant_id'] ? (int)$it['variant_id'] : null, (int)$it['qty'], 'Order ' . $o['number'] . ' ' . strtolower($new), $by);
        $upd['stock_deducted'] = 0;
    }
    if ($new === 'Delivered') {
        $upd['delivered_at'] = now();
        if ($o['payment_method'] === 'cod' && $o['payment_status'] === 'Unpaid') { $upd['payment_status'] = 'COD Collected'; q("UPDATE payments SET status='Collected', verified_by=? WHERE order_id=?", [$by, $oid]); }
    }
    if ($new === 'Returned' && in_array($o['payment_status'], ['Paid', 'COD Collected'], true)) { $upd['payment_status'] = 'Refunded'; q("UPDATE payments SET status='Refunded' WHERE order_id=?", [$oid]); }
    if ($new === 'Cancelled' && $o['payment_status'] === 'Paid') { $upd['payment_status'] = 'Refunded'; q("UPDATE payments SET status='Refunded' WHERE order_id=?", [$oid]); }
    update('orders', $upd, 'id=?', [$oid]);
    insert('order_status_log', ['order_id' => $oid, 'status' => $new, 'note' => $note ?: null, 'changed_by' => $by]);

    $o = row('SELECT * FROM orders WHERE id=?', [$oid]);
    if (in_array($new, ['Confirmed', 'Shipped', 'Delivered', 'Cancelled', 'Returned'], true)) {
        $trk = $o['tracking_number'] ? 'Courier: ' . $o['courier'] . ', tracking number: ' . $o['tracking_number'] : '';
        $vars = ['name' => $o['cust_name'], 'order_number' => $o['number'], 'status' => $new, 'tracking' => $trk, 'store_name' => setting('store_name'), 'link' => order_link($o)];
        send_mail($o['cust_email'], tpl(setting('tpl_order_status_subject'), $vars), tpl(setting('tpl_order_status_body'), $vars));
    }
    return null;
}
