<?php
declare(strict_types=1);

const MAX_FAILED = 5;
const LOCK_MINUTES = 15;

/* ---------- Staff (admin panel) ---------- */
function admin_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['admin_id'])) {
            $u = row("SELECT id,name,email,role FROM users WHERE id=? AND status='active'", [$_SESSION['admin_id']]);
            if (!$u) unset($_SESSION['admin_id']);
        }
    }
    return $u;
}
function can(string $perm): bool {
    $u = admin_user();
    if (!$u) return false;
    $p = role_permissions()[$u['role']] ?? [];
    return in_array('*', $p, true) || in_array($perm, $p, true);
}
function require_admin(string $perm = 'dashboard'): array {
    $u = admin_user();
    if (!$u) redirect('admin/login');
    if (!can($perm)) abort(403, 'Your role does not have access to this area.');
    return $u;
}

/* ---------- generic login with lockout ---------- */
function try_login(string $table, string $email, string $password): array {
    $u = row("SELECT * FROM `$table` WHERE email=?", [strtolower($email)]);
    if (!$u) { password_verify($password, '$2y$10$usesomesillystringforsalt$'); return [null, 'Wrong email or password.']; }
    if ($u['locked_until'] && strtotime($u['locked_until']) > time()) return [null, 'Too many failed attempts. Try again in ' . LOCK_MINUTES . ' minutes.'];
    $blocked = ($table === 'users' && $u['status'] !== 'active') || ($table === 'customers' && $u['status'] !== 'active');
    if (!password_verify($password, $u['password_hash'])) {
        $n = (int)$u['failed_attempts'] + 1;
        $lock = $n >= MAX_FAILED ? date('Y-m-d H:i:s', time() + LOCK_MINUTES * 60) : null;
        update($table, ['failed_attempts' => $lock ? 0 : $n, 'locked_until' => $lock], 'id=?', [$u['id']]);
        return [null, 'Wrong email or password.'];
    }
    if ($blocked) return [null, 'This account is not active. Please contact the store.'];
    update($table, ['failed_attempts' => 0, 'locked_until' => null], 'id=?', [$u['id']]);
    return [$u, null];
}
function password_problem(string $pw): ?string {
    if (strlen($pw) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Password must contain letters and numbers.';
    return null;
}

/* ---------- Customers ---------- */
function customer(): ?array {
    static $c = false;
    if ($c === false) {
        $c = null;
        if (!empty($_SESSION['customer_id'])) {
            $c = row("SELECT id,name,email,phone FROM customers WHERE id=? AND status='active'", [$_SESSION['customer_id']]);
            if (!$c) unset($_SESSION['customer_id']);
        }
    }
    return $c;
}
function require_customer(): array {
    $c = customer();
    if (!$c) { $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? ''; redirect('account/login'); }
    return $c;
}
