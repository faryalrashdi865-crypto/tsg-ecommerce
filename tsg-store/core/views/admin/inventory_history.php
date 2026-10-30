<p><a href="<?= e(url('admin/inventory')) ?>">&larr; Inventory</a></p>
<table class="table"><thead><tr><th>When</th><th>Product</th><th>Change</th><th>Balance</th><th>Reason</th><th>By</th></tr></thead><tbody>
<?php foreach ($log as $l): ?><tr><td><?= e(fmt_date($l['created_at'])) ?></td><td><?= e($l['name']) ?><?= $l['options'] ? ' <small class="muted">' . e(variant_label(['options' => $l['options']])) . '</small>' : '' ?></td><td class="<?= $l['change'] < 0 ? 'err' : 'ok' ?>"><?= ($l['change'] > 0 ? '+' : '') . (int)$l['change'] ?></td><td><?= (int)$l['balance'] ?></td><td><?= e($l['reason']) ?></td><td><?= e($l['user_name']) ?></td></tr><?php endforeach; ?>
<?php if (!$log): ?><tr><td colspan="6" class="muted">No stock movement yet.</td></tr><?php endif; ?></tbody></table>
