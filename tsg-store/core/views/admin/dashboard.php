<?php $pct = function (float $now, float $prev): string { if ($prev <= 0) return $now > 0 ? 'new' : '-'; $d = ($now - $prev) / $prev * 100; return ($d >= 0 ? '+' : '') . round($d) . '%'; }; ?>
<div class="shortcuts"><?php if (can('products.manage')): ?><a class="btn" href="<?= e(url('admin/products/new')) ?>">Add product</a><?php endif; ?><?php if (can('orders.manage')): ?><a class="btn btn-outline" href="<?= e(url('admin/orders?status=Pending')) ?>">View new orders</a><?php endif; ?></div>
<?php if (isset($sales)): ?><div class="cards"><?php foreach ($sales as $label => [$now, $prev]): ?>
  <div class="stat"><span><?= e($label) ?></span><strong><?= e(money($now)) ?></strong><small>vs previous period: <?= e($pct($now, $prev)) ?></small></div><?php endforeach; ?></div><?php endif; ?>
<?php if (isset($statusCounts)): ?>
<div class="cards">
  <div class="stat"><span>Pending orders</span><strong><?= (int)($statusCounts['Pending'] ?? 0) ?></strong></div>
  <div class="stat"><span>Processing</span><strong><?= (int)(($statusCounts['Confirmed'] ?? 0) + ($statusCounts['Packed'] ?? 0)) ?></strong></div>
  <div class="stat"><span>Shipped</span><strong><?= (int)($statusCounts['Shipped'] ?? 0) ?></strong></div>
  <div class="stat"><span>Delivered</span><strong><?= (int)(($statusCounts['Delivered'] ?? 0) + ($statusCounts['Completed'] ?? 0)) ?></strong></div>
  <div class="stat"><span>Cancelled</span><strong><?= (int)($statusCounts['Cancelled'] ?? 0) ?></strong></div>
  <div class="stat"><span>Payments to verify</span><strong><?= (int)$pendingVerify ?></strong></div>
  <div class="stat"><span>COD to collect</span><strong><?= e(money($codToCollect)) ?></strong></div>
</div><?php endif; ?>
<div class="cols">
<?php if (isset($recent)): ?><section class="panel"><h2>Recent orders</h2><table class="table"><tbody><?php foreach ($recent as $o): ?><tr><td><a href="<?= e(url('admin/orders/' . $o['id'])) ?>"><?= e($o['number']) ?></a></td><td><?= e($o['cust_name']) ?></td><td><?= e($o['status']) ?></td><td><?= e(money($o['total'])) ?></td></tr><?php endforeach; ?><?php if (!$recent): ?><tr><td class="muted">No orders yet.</td></tr><?php endif; ?></tbody></table></section>
<section class="panel"><h2>Top products</h2><table class="table"><tbody><?php foreach ($top as $t): ?><tr><td><?= e($t['name']) ?></td><td><?= (int)$t['qty'] ?> sold</td><td><?= e(money($t['revenue'])) ?></td></tr><?php endforeach; ?><?php if (!$top): ?><tr><td class="muted">No sales yet.</td></tr><?php endif; ?></tbody></table></section><?php endif; ?>
<?php if (isset($lowStock)): ?><section class="panel"><h2>Stock alerts</h2><table class="table"><tbody><?php foreach ($lowStock as $s): ?><tr><td><?= e($s['name']) ?><?= $s['variant'] ? ' <small class="muted">' . e(variant_label(['options' => $s['variant']])) . '</small>' : '' ?></td><td class="<?= (int)$s['stock'] <= 0 ? 'err' : '' ?>"><?= (int)$s['stock'] <= 0 ? 'Out of stock' : (int)$s['stock'] . ' left' ?></td></tr><?php endforeach; ?><?php if (!$lowStock): ?><tr><td class="muted">All stock levels are fine.</td></tr><?php endif; ?></tbody></table></section><?php endif; ?>
<?php if (isset($newCustomers)): ?><section class="panel"><h2>Customers</h2><p><strong><?= (int)$newCustomers ?></strong> new accounts in the last 30 days.<br><strong><?= (int)$repeat ?></strong> repeat buyers.</p></section><?php endif; ?>
<section class="panel"><h2>Alerts</h2><ul class="alerts"><?php foreach ($alerts as $a): ?><li><?php if ($a['link']): ?><a href="<?= e(url($a['link'])) ?>"><?= e($a['subject']) ?></a><?php else: ?><?= e($a['subject']) ?><?php endif; ?><br><small class="muted"><?= e(fmt_date($a['created_at'])) ?></small></li><?php endforeach; ?><?php if (!$alerts): ?><li class="muted">Nothing new.</li><?php endif; ?></ul></section>
</div>
