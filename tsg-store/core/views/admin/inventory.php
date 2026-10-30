<p class="muted">Stock reduces when an order is confirmed and returns when an order is cancelled or returned. <a href="<?= e(url('admin/inventory/history')) ?>">View full stock history</a></p>
<table class="table"><thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Alert at</th><?php if (can('inventory.manage')): ?><th>Adjust</th><?php endif; ?><th></th></tr></thead><tbody>
<?php foreach ($items as $i): $low = $i['track_stock'] && (int)$i['stock'] <= (int)$i['low_stock_level']; ?>
<tr><td><?= e($i['name']) ?><?= $i['variant'] ? '<br><small class="muted">' . e(variant_label(['options' => $i['variant']])) . '</small>' : '' ?></td><td><?= e($i['sku']) ?></td>
<td class="<?= $low ? 'err' : '' ?>"><?= $i['track_stock'] ? (int)$i['stock'] : 'not tracked' ?></td><td><?= (int)$i['low_stock_level'] ?></td>
<?php if (can('inventory.manage')): ?><td><?php if ($i['track_stock']): ?><form method="post" action="<?= e(url('admin/inventory/adjust')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $i['id'] ?>"><input type="hidden" name="variant_id" value="<?= (int)$i['variant_id'] ?>">
<input type="number" name="delta" class="qty" placeholder="+/-"><input name="reason" placeholder="Reason"><button class="btn btn-sm" type="submit">Apply</button></form><?php endif; ?></td><?php endif; ?>
<td><a href="<?= e(url('admin/inventory/history?product_id=' . $i['id'])) ?>">History</a></td></tr><?php endforeach; ?></tbody></table>
