<form method="post" action="<?= e(url('admin/payments/save')) ?>" class="form"><?= csrf_field() ?>
<?php foreach ($methods as $m): $c = $m['code']; ?>
<section class="panel"><h2><?= e($m['label']) ?></h2>
  <label class="check"><input type="checkbox" name="enabled[<?= e($c) ?>]" value="1"<?= $m['enabled'] ? ' checked' : '' ?>> Enabled at checkout</label>
  <div class="two"><label>Label shown to customers<input name="label[<?= e($c) ?>]" value="<?= e($m['label']) ?>"></label><label>Extra charge (fee)<input type="number" step="0.01" min="0" name="extra_fee[<?= e($c) ?>]" value="<?= e($m['extra_fee']) ?>"></label></div>
  <label>Instructions for the customer<textarea name="instructions[<?= e($c) ?>]" rows="2"><?= e($m['instructions']) ?></textarea></label>
  <?php if ((int)$m['is_manual']): ?><label>Account details shown at checkout<textarea name="account_details[<?= e($c) ?>]" rows="3"><?= e($m['account_details']) ?></textarea></label><?php else: ?><input type="hidden" name="account_details[<?= e($c) ?>]" value=""><?php endif; ?>
</section><?php endforeach; ?>
<p class="muted">Card payments through a gateway arrive in Phase 3. For manual methods the customer uploads proof and staff verify it on the order page.</p>
<div class="sticky-actions"><button class="btn" type="submit">Save payment methods</button></div></form>
