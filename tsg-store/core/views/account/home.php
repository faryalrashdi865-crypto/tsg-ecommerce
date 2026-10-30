<div class="page-head"><h1>Hello, <?= e($c['name']) ?></h1>
  <div class="btn-row"><a class="btn btn-outline" href="<?= e(url('account/profile')) ?>">Profile and password</a>
  <form method="post" action="<?= e(url('account/logout')) ?>"><?= csrf_field() ?><button class="btn btn-outline" type="submit">Log out</button></form></div></div>
<h2>My orders</h2>
<?php if (!$orders): ?><p class="empty">You have no orders yet.</p><?php else: ?>
<table class="table"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th></tr></thead><tbody>
<?php foreach ($orders as $o): ?><tr><td><a href="<?= e(url('account/orders/' . $o['number'])) ?>"><?= e($o['number']) ?></a></td><td><?= e(fmt_date($o['placed_at'], false)) ?></td><td><?= e($o['status']) ?></td><td><?= e(money($o['total'])) ?></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?>
<h2>Saved addresses</h2>
<?php foreach ($addresses as $a): ?>
<div class="addr"><div><strong><?= e($a['label'] ?: 'Address') ?><?= $a['is_default'] ? ' (default)' : '' ?></strong><br><?= e($a['name']) ?>, <?= e($a['phone']) ?><br><?= e($a['line1']) ?>, <?= e($a['city']) ?>, <?= e($a['province']) ?></div>
  <form method="post" action="<?= e(url('account/addresses/' . $a['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="link" type="submit">Delete</button></form></div>
<?php endforeach; ?>
<details><summary>Add an address</summary>
<form method="post" action="<?= e(url('account/addresses/add')) ?>" class="stack"><?= csrf_field() ?>
  <label>Label (Home, Office)<input name="label"></label><label>Full name<input name="name" value="<?= e($c['name']) ?>" required></label><label>Phone<input name="phone" value="<?= e((string)$c['phone']) ?>" required></label>
  <label>Address<input name="line1" required></label><div class="two"><label>City<input name="city" required></label>
  <label>Province<select name="province" required><option value="">Select</option><?php foreach (provinces() as $pv): ?><option><?= e($pv) ?></option><?php endforeach; ?></select></label></div>
  <label>Postal code<input name="postal"></label><label class="check"><input type="checkbox" name="is_default" value="1"> Make default</label>
  <button class="btn" type="submit">Save address</button></form></details>
