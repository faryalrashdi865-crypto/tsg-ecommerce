<?php $addr = trim($o['ship_line1'] . ', ' . $o['ship_city'] . ', ' . $o['ship_province'] . ' ' . $o['ship_postal'], ' ,'); ?>
<div class="order-box">
  <div class="order-head"><div><h2>Order <?= e($o['number']) ?></h2><p class="muted">Placed <?= e(fmt_date($o['placed_at'])) ?></p></div>
    <div><span class="pill pill-<?= e(strtolower(str_replace(' ', '-', $o['status']))) ?>"><?= e($o['status']) ?></span> <span class="pill pill-pay"><?= e($o['payment_status']) ?></span></div></div>
  <?php if ($o['tracking_number']): ?><p><strong>Courier:</strong> <?= e($o['courier']) ?> &middot; <strong>Tracking number:</strong> <?= e($o['tracking_number']) ?></p><?php endif; ?>
  <ol class="timeline"><?php foreach ($log as $l): ?><li><strong><?= e($l['status']) ?></strong> <span class="muted"><?= e(fmt_date($l['created_at'])) ?><?= $l['note'] ? ' - ' . e($l['note']) : '' ?></span></li><?php endforeach; ?></ol>
  <table class="table"><thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($items as $it): ?><tr><td><?= e($it['name']) ?><?= $it['variant_label'] ? '<br><small class="muted">' . e($it['variant_label']) . '</small>' : '' ?></td><td><?= e(money($it['price'])) ?></td><td><?= (int)$it['qty'] ?></td><td><?= e(money($it['price'] * $it['qty'])) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <dl class="totals"><dt>Subtotal</dt><dd><?= e(money($o['subtotal'])) ?></dd><dt>Shipping (<?= e($o['shipping_method']) ?>)</dt><dd><?= e(money($o['shipping_cost'])) ?></dd>
    <?php if ((float)$o['tax_amount'] > 0): ?><dt><?= e(setting('tax_label')) ?></dt><dd><?= e(money($o['tax_amount'])) ?></dd><?php endif; ?>
    <?php if ((float)$o['fee_amount'] > 0): ?><dt>Payment fee</dt><dd><?= e(money($o['fee_amount'])) ?></dd><?php endif; ?>
    <dt class="grand">Total</dt><dd class="grand"><?= e(money($o['total'])) ?></dd></dl>
  <p><strong>Deliver to:</strong> <?= e($o['cust_name']) ?>, <?= e($o['cust_phone']) ?><br><?= e($addr) ?></p>
  <p><strong>Payment:</strong> <?= e($pm['label'] ?? $o['payment_method']) ?></p>
</div>
