<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title><?= $slip ? 'Packing slip' : 'Invoice' ?> <?= e($o['number']) ?></title>
<style><?= theme_css_vars() ?>
body{font:14px/1.5 var(--font-body);color:#111;margin:0;padding:32px;max-width:800px;margin:auto}h1{font-family:var(--font-heading);color:var(--primary);margin:0}
table{width:100%;border-collapse:collapse;margin:16px 0}th,td{padding:8px;border-bottom:1px solid #ddd;text-align:left}th{background:#f4f4f4}.r{text-align:right}
.head{display:flex;justify-content:space-between;gap:20px;border-bottom:3px solid var(--primary);padding-bottom:12px;margin-bottom:16px}.head img{max-height:60px}
@media print{.noprint{display:none}body{padding:0}}</style></head><body>
<p class="noprint"><button onclick="window.print()">Print</button></p>
<div class="head"><div><?php if (setting('logo')): ?><img src="<?= e(url(setting('logo'))) ?>" alt=""><?php else: ?><h1><?= e(setting('store_name')) ?></h1><?php endif; ?>
<div><?= nl2br(e(setting('address'))) ?><br><?= e(setting('phone')) ?> <?= e(setting('contact_email')) ?></div></div>
<div class="r"><h1><?= $slip ? 'Packing slip' : 'Invoice' ?></h1><strong><?= e($o['number']) ?></strong><br><?= e(fmt_date($o['placed_at'], false)) ?></div></div>
<p><strong>Bill / ship to</strong><br><?= e($o['cust_name']) ?><br><?= e($o['cust_phone']) ?><br><?= e($o['ship_line1']) ?>, <?= e($o['ship_city']) ?>, <?= e($o['ship_province']) ?> <?= e($o['ship_postal']) ?></p>
<table><thead><tr><th>Item</th><th>SKU</th><?php if (!$slip): ?><th class="r">Price</th><?php endif; ?><th class="r">Qty</th><?php if (!$slip): ?><th class="r">Total</th><?php endif; ?></tr></thead><tbody>
<?php foreach ($items as $it): ?><tr><td><?= e($it['name']) ?><?= $it['variant_label'] ? '<br><small>' . e($it['variant_label']) . '</small>' : '' ?></td><td><?= e($it['sku']) ?></td><?php if (!$slip): ?><td class="r"><?= e(money($it['price'])) ?></td><?php endif; ?><td class="r"><?= (int)$it['qty'] ?></td><?php if (!$slip): ?><td class="r"><?= e(money($it['price'] * $it['qty'])) ?></td><?php endif; ?></tr><?php endforeach; ?></tbody></table>
<?php if (!$slip): ?><table style="width:300px;margin-left:auto"><tr><td>Subtotal</td><td class="r"><?= e(money($o['subtotal'])) ?></td></tr><tr><td>Shipping</td><td class="r"><?= e(money($o['shipping_cost'])) ?></td></tr>
<?php if ((float)$o['tax_amount'] > 0): ?><tr><td><?= e(setting('tax_label')) ?></td><td class="r"><?= e(money($o['tax_amount'])) ?></td></tr><?php endif; ?>
<?php if ((float)$o['fee_amount'] > 0): ?><tr><td>Payment fee</td><td class="r"><?= e(money($o['fee_amount'])) ?></td></tr><?php endif; ?>
<tr><th>Total</th><th class="r"><?= e(money($o['total'])) ?></th></tr></table><p>Payment: <?= e($pm['label'] ?? $o['payment_method']) ?> (<?= e($o['payment_status']) ?>)</p><?php endif; ?>
<?php if ($o['note']): ?><p><strong>Note:</strong> <?= e($o['note']) ?></p><?php endif; ?>
<p style="margin-top:40px;color:#666"><?= e(setting('footer_text')) ?> Thank you for shopping with <?= e(setting('store_name')) ?>.</p></body></html>
