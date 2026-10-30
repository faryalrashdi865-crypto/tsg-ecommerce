<?php
declare(strict_types=1);

function account_login(): void {
    if (customer()) redirect('account');
    $err = '';
    if (is_post()) {
        [$u, $err] = try_login('customers', (string)post('email'), (string)post('password'));
        if ($u) {
            session_regenerate_id(true);
            $_SESSION['customer_id'] = (int)$u['id'];
            cart_merge_after_login((int)$u['id']);
            $to = $_SESSION['after_login'] ?? ''; unset($_SESSION['after_login']);
            redirect($to && str_starts_with($to, BASE . '/') ? $to : 'account');
        }
    }
    page('account/login', ['title' => 'Log in', 'err' => $err]);
}
function account_register(): void {
    if (customer()) redirect('account');
    $errors = [];
    if (is_post()) {
        $name = (string)post('name'); $email = strtolower((string)post('email')); $phone = (string)post('phone'); $pw = (string)post('password');
        if ($name === '') $errors['name'] = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
        elseif (val('SELECT COUNT(*) FROM customers WHERE email=?', [$email])) $errors['email'] = 'An account with this email already exists.';
        if ($pw !== (string)post('password2')) $errors['password'] = 'Passwords do not match.';
        elseif ($m = password_problem($pw)) $errors['password'] = $m;
        if (!$errors) {
            $id = insert('customers', ['name' => $name, 'email' => $email, 'phone' => $phone ?: null, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['customer_id'] = $id;
            cart_merge_after_login($id);
            $vars = ['name' => $name, 'store_name' => setting('store_name')];
            send_mail($email, tpl('Welcome to {store_name}', $vars), tpl("Hello {name},\n\nYour account at {store_name} is ready.", $vars));
            flash('success', 'Your account has been created.');
            redirect('account');
        }
    }
    page('account/register', ['title' => 'Create account', 'errors' => $errors]);
}
function account_logout(): void { unset($_SESSION['customer_id']); session_regenerate_id(true); redirect(''); }

function account_home(): void {
    $c = require_customer();
    page('account/home', ['title' => 'My account', 'c' => $c, 'orders' => rows('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC', [$c['id']]), 'addresses' => rows('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC,id', [$c['id']])]);
}
function account_order(string $number): void {
    $c = require_customer();
    $o = row('SELECT * FROM orders WHERE number=? AND customer_id=?', [$number, $c['id']]);
    if (!$o) abort(404, 'Order not found.');
    $days = (int)setting('return_days', '7');
    $canReturn = $days > 0 && in_array($o['status'], ['Delivered', 'Completed'], true) && $o['delivered_at'] && strtotime($o['delivered_at']) + $days * 86400 >= time();
    page('account/order', order_context($o) + ['title' => 'Order ' . $o['number'], 'canReturn' => $canReturn, 'days' => $days]);
}
function account_return(string $number): void {
    $c = require_customer();
    $o = row('SELECT * FROM orders WHERE number=? AND customer_id=?', [$number, $c['id']]);
    $days = (int)setting('return_days', '7');
    if (!$o || !in_array($o['status'], ['Delivered', 'Completed'], true) || !$o['delivered_at'] || strtotime($o['delivered_at']) + $days * 86400 < time()) { flash('error', 'This order can no longer be returned.'); redirect('account'); }
    $reason = (string)post('reason');
    if ($reason === '') { flash('error', 'Please tell us why you want to return it.'); redirect('account/orders/' . $number); }
    update('orders', ['return_reason' => $reason], 'id=?', [$o['id']]);
    set_order_status((int)$o['id'], 'Return Requested', $reason, $c['name']);
    admin_alert('Return requested for ' . $o['number'], $reason, 'admin/orders/' . $o['id']);
    flash('success', 'Your return request was sent. We will contact you soon.');
    redirect('account/orders/' . $number);
}
function account_profile(): void {
    $c = require_customer();
    if (is_post()) {
        $name = (string)post('name');
        if ($name === '') { flash('error', 'Name is required.'); redirect('account/profile'); }
        update('customers', ['name' => $name, 'phone' => (string)post('phone') ?: null], 'id=?', [$c['id']]);
        if ((string)post('new_password') !== '') {
            $full = row('SELECT password_hash FROM customers WHERE id=?', [$c['id']]);
            if (!password_verify((string)post('current_password'), $full['password_hash'])) { flash('error', 'Your current password is wrong.'); redirect('account/profile'); }
            if ($m = password_problem((string)post('new_password'))) { flash('error', $m); redirect('account/profile'); }
            update('customers', ['password_hash' => password_hash((string)post('new_password'), PASSWORD_DEFAULT)], 'id=?', [$c['id']]);
        }
        flash('success', 'Profile updated.');
        redirect('account/profile');
    }
    page('account/profile', ['title' => 'My profile', 'c' => $c]);
}
function account_address_add(): void {
    $c = require_customer();
    $d = []; foreach (['label', 'name', 'phone', 'line1', 'city', 'province', 'postal'] as $k) $d[$k] = (string)post($k);
    if ($d['name'] === '' || $d['phone'] === '' || $d['line1'] === '' || $d['city'] === '' || $d['province'] === '') { flash('error', 'Please fill in all address fields.'); redirect('account'); }
    $first = !val('SELECT COUNT(*) FROM addresses WHERE customer_id=?', [$c['id']]);
    if (post('is_default') || $first) q('UPDATE addresses SET is_default=0 WHERE customer_id=?', [$c['id']]);
    insert('addresses', $d + ['customer_id' => $c['id'], 'is_default' => (post('is_default') || $first) ? 1 : 0]);
    flash('success', 'Address saved.');
    redirect('account');
}
function account_address_delete(string $id): void {
    $c = require_customer();
    q('DELETE FROM addresses WHERE id=? AND customer_id=?', [(int)$id, $c['id']]);
    redirect('account');
}
