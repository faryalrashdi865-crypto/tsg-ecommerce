<p class="muted">The store picks the first zone whose regions list contains the customer's city or province. A zone with <code>*</code> catches everything else. Free shipping applies to non-pickup rates when the order reaches the zone's free-shipping amount.</p>
<?php foreach ($zones as $z): ?>
<section class="panel"><h2><?= e($z['name']) ?> <small class="muted"><?= $z['active'] ? '' : '(off)' ?></small></h2>
<form method="post" action="<?= e(url('admin/shipping/zone')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $z['id'] ?>">
  <label>Name<input name="name" value="<?= e($z['name']) ?>" required></label><label>Regions (cities or provinces, comma separated, or *)<input name="regions" value="<?= e($z['regions']) ?>" required></label>
  <label>Free shipping from<input type="number" step="0.01" min="0" name="free_over" value="<?= e($z['free_over']) ?>"></label><label>Order<input type="number" name="position" class="qty" value="<?= (int)$z['position'] ?>"></label>
  <label class="check"><input type="checkbox" name="active" value="1"<?= $z['active'] ? ' checked' : '' ?>> Active</label><button class="btn btn-sm" type="submit">Save zone</button>
  <button class="link err" type="submit" formaction="<?= e(url('admin/shipping/zone/' . $z['id'] . '/delete')) ?>" data-confirm="Delete this zone and its rates?">Delete zone</button></form>
<table class="table"><thead><tr><th>Rate</th><th>Type</th><th>From</th><th>To</th><th>Cost</th><th>Delivery</th><th>Pickup</th><th></th></tr></thead><tbody>
<?php foreach ($z['rates'] as $r): ?><tr><td colspan="8"><form method="post" action="<?= e(url('admin/shipping/rate')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="zone_id" value="<?= $z['id'] ?>">
<input name="name" value="<?= e($r['name']) ?>" required><select name="type"><?php foreach (['flat', 'weight', 'price'] as $t): ?><option<?= $r['type'] === $t ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
<input type="number" step="0.001" min="0" name="min_value" class="qty" value="<?= e($r['min_value']) ?>"><input type="number" step="0.001" min="0" name="max_value" class="qty" value="<?= e($r['max_value']) ?>" placeholder="no limit"><input type="number" step="0.01" min="0" name="rate" class="qty" value="<?= e($r['rate']) ?>">
<input name="est_days" value="<?= e($r['est_days']) ?>" placeholder="2-4 days"><label class="check"><input type="checkbox" name="is_pickup" value="1"<?= $r['is_pickup'] ? ' checked' : '' ?>> Pickup</label>
<button class="btn btn-sm" type="submit">Save</button><button class="link err" type="submit" formaction="<?= e(url('admin/shipping/rate/' . $r['id'] . '/delete')) ?>">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table>
<details><summary>Add a rate to <?= e($z['name']) ?></summary><form method="post" action="<?= e(url('admin/shipping/rate')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="zone_id" value="<?= $z['id'] ?>">
<input name="name" placeholder="Rate name" required><select name="type"><option>flat</option><option>weight</option><option>price</option></select>
<input type="number" step="0.001" min="0" name="min_value" class="qty" placeholder="from"><input type="number" step="0.001" min="0" name="max_value" class="qty" placeholder="to"><input type="number" step="0.01" min="0" name="rate" class="qty" placeholder="cost" required>
<input name="est_days" placeholder="2-4 days"><label class="check"><input type="checkbox" name="is_pickup" value="1"> Pickup</label><button class="btn btn-sm" type="submit">Add rate</button></form>
<p class="muted small">Type flat: always applies. Type weight: applies when the cart weight (kg) is between From and To. Type price: applies when the cart subtotal is between From and To.</p></details>
</section>
<?php endforeach; ?>
<section class="panel"><h2>Add a zone</h2><form method="post" action="<?= e(url('admin/shipping/zone')) ?>" class="inline-form"><?= csrf_field() ?>
<input name="name" placeholder="Zone name" required><input name="regions" placeholder="Karachi, Lahore  or  Punjab  or  *" required><input type="number" step="0.01" name="free_over" placeholder="Free shipping from"><label class="check"><input type="checkbox" name="active" value="1" checked> Active</label><button class="btn" type="submit">Add zone</button></form></section>
