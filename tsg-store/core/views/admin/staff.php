<?php foreach (array_merge($list, [['id' => 0, 'name' => '', 'email' => '', 'role' => 'manager', 'status' => 'active', 'last_login' => null]]) as $u): ?>
<section class="panel"><h2><?= $u['id'] ? e($u['name']) . ' <small class="muted">' . e(role_labels()[$u['role']]) . ($u['last_login'] ? ' - last login ' . e(fmt_date($u['last_login'])) : '') . '</small>' : 'Add staff account' ?></h2>
<form method="post" action="<?= e(url('admin/staff/save')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
<input name="name" value="<?= e($u['name']) ?>" placeholder="Name" required><input type="email" name="email" value="<?= e($u['email']) ?>" placeholder="Email" required>
<?php if ($u['role'] === 'owner'): ?><input type="hidden" name="role" value="owner"><?php else: ?><select name="role"><?php foreach (role_labels() as $k => $l): if ($k === 'owner') continue; ?><option value="<?= $k ?>"<?= $u['role'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
<select name="status"><option>active</option><option<?= $u['status'] === 'disabled' ? ' selected' : '' ?>>disabled</option></select><?php endif; ?>
<input type="password" name="password" placeholder="<?= $u['id'] ? 'New password (optional)' : 'Password' ?>" autocomplete="new-password"><button class="btn btn-sm" type="submit">Save</button></form></section>
<?php endforeach; ?>
<section class="panel"><h2>What each role can do</h2><table class="table"><thead><tr><th>Area</th><th>Owner</th><th>Manager</th><th>Order Handler</th><th>Content Editor</th><th>Inventory Clerk</th></tr></thead><tbody>
<tr><td>Products and categories</td><td>Yes</td><td>Yes</td><td>No</td><td>Yes</td><td>View</td></tr><tr><td>Inventory</td><td>Yes</td><td>Yes</td><td>View</td><td>No</td><td>Yes</td></tr>
<tr><td>Orders</td><td>Yes</td><td>Yes</td><td>Yes</td><td>No</td><td>No</td></tr><tr><td>Customers</td><td>Yes</td><td>Yes</td><td>View</td><td>No</td><td>No</td></tr>
<tr><td>Pages and banners</td><td>Yes</td><td>Yes</td><td>No</td><td>Yes</td><td>No</td></tr><tr><td>Payments, shipping, settings</td><td>Yes</td><td>No</td><td>No</td><td>No</td><td>No</td></tr>
<tr><td>Staff accounts</td><td>Yes</td><td>No</td><td>No</td><td>No</td><td>No</td></tr></tbody></table></section>
