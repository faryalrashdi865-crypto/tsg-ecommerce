<?php $u = admin_user(); $unread = (int)val("SELECT COUNT(*) FROM notifications WHERE audience='admin' AND is_read=0");
$nav = [
  ['admin', 'Dashboard', 'dashboard'], ['admin/orders', 'Orders', 'orders.manage'], ['admin/products', 'Products', 'products.view'], ['admin/categories', 'Categories and brands', 'products.manage'],
  ['admin/inventory', 'Inventory', 'inventory.view'], ['admin/customers', 'Customers', 'customers.view'], ['admin/pages', 'Pages', 'content.manage'], ['admin/banners', 'Banners', 'content.manage'],
  ['admin/shipping', 'Shipping', 'settings.manage'], ['admin/payments', 'Payments', 'settings.manage'], ['admin/settings', 'Settings', 'settings.manage'],
  ['admin/staff', 'Staff', 'staff.manage'], ['admin/activity', 'Activity log', 'staff.manage'],
]; $cur = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Admin') ?> | <?= e(setting('store_name')) ?> admin</title>
<style><?= theme_css_vars() ?></style><link rel="stylesheet" href="<?= e(url('assets/admin.css')) ?>"></head>
<body class="admin">
<aside class="side"><div class="side-brand"><?= e(setting('store_name')) ?><small>Admin panel</small></div>
  <nav><?php foreach ($nav as [$path, $label, $perm]): if (!can($perm)) continue; $on = ($path === 'admin') ? ($cur === ltrim(BASE . '/admin', '/')) : str_contains($cur, $path); ?>
    <a href="<?= e(url($path)) ?>"<?= $on ? ' class="on"' : '' ?>><?= e($label) ?></a><?php endforeach; ?></nav>
  <div class="side-foot"><a href="<?= e(url()) ?>" target="_blank">View store</a>
    <?php if (can('settings.manage')): ?><a href="<?= e(url('admin/backup')) ?>">Download backup</a><?php endif; ?>
    <form method="post" action="<?= e(url('admin/logout')) ?>"><?= csrf_field() ?><button type="submit"><?= e($u['name']) ?> - Log out</button></form></div></aside>
<div class="main"><header class="top"><h1><?= e($title ?? '') ?></h1><span class="muted"><?= e(role_labels()[$u['role']]) ?> &middot; v<?= e(TSG_VERSION) ?></span></header>
<?php foreach (flash() ?? [] as [$t, $m]): ?><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
<?php $lic = setting('licence_issued'); if ($lic && setting('licence_lifetime') !== '1' && can('settings.manage') && strtotime($lic) + 335 * 86400 < time()): ?><div class="alert alert-info">Your first-year updates and support period ends on <?= e(date('d M Y', strtotime($lic . ' +1 year'))) ?>. Contact Tech SG Studio to renew. Your store keeps working either way.</div><?php endif; ?>
<?php if (setting('test_mode') === '1' && can('settings.manage')): ?><div class="alert alert-info">The store is in test mode. Switch it off in Settings when you are ready to go live.</div><?php endif; ?>
<?= $content ?></div>
<script src="<?= e(url('assets/js/admin.js')) ?>" defer></script></body></html>
