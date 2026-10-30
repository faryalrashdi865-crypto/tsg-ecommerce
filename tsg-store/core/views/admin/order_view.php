<p><a href="<?= e(url('admin/orders')) ?>">&larr; All orders</a></p>
<div class="cols">
<section class="panel wide"><?= render('shop/_order_detail', compact('o', 'items', 'log', 'pm', 'payment')) ?>
  <p><a class="btn btn-outline" target="_blank" href="<?= e(url('admin/orders/' . $o['id'] . '/invoice')) ?>">Print invoice</a>
  <a class="btn btn-outline" target="_blank" href="<?= e(url('admin/orders/' . $o['id'] . '/invoice?slip=1')) ?>">Print packing slip</a>
  <a class="btn btn-wa" target="_blank" rel="noopener" href="<?= e($wa) ?>">Message on WhatsApp</a></p>
  <?php if ($o['note']): ?><p><strong>Customer note:</strong> <?= e($o['note']) ?></p><?php endif; ?>
  <?php if ($o['return_reason']): ?><p><strong>Return reason:</strong> <?= e($o['return_reason']) ?></p><?php endif; ?>
</section>
<div>
<section class="panel"><h2>Update status</h2>
  <form method="post" action="<?= e(url('admin/orders/' . $o['id'] . '/status')) ?>" class="stack"><?= csrf_field() ?>
    <label>Status<select name="status"><?php foreach (order_statuses() as $s): ?><option<?= $o['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
    <label>Note (optional)<input name="note" maxlength="250"></label><button class="btn" type="submit">Update</button></form>
  <p class="muted small">Confirming an order reduces stock. Cancelling or returning puts it back. A return request is approved by choosing Returned, or rejected by choosing Delivered.</p>
</section>
<section class="panel"><h2>Payment</h2>
  <p>Method: <strong><?= e($pm['label'] ?? $o['payment_method']) ?></strong><br>Status: <strong><?= e($o['payment_status']) ?></strong>
  <?php if (!empty($payment['txn_id'])): ?><br>Transaction ID: <strong><?= e($payment['txn_id']) ?></strong><?php endif; ?></p>
  <?php if (!empty($payment['proof_file'])): ?><p><a target="_blank" href="<?= e(url('admin/proof/' . $payment['proof_file'])) ?>">View payment proof</a></p><?php endif; ?>
  <form method="post" action="<?= e(url('admin/orders/' . $o['id'] . '/payment')) ?>" class="btn-row"><?= csrf_field() ?>
    <?php if ($o['payment_method'] !== 'cod'): ?><button class="btn btn-sm" name="action" value="verify">Mark verified</button><button class="btn btn-outline btn-sm" name="action" value="reject">Reject</button>
    <?php else: ?><button class="btn btn-sm" name="action" value="collected">Mark COD collected</button><?php endif; ?></form>
</section>
<section class="panel"><h2>Courier and notes</h2>
  <form method="post" action="<?= e(url('admin/orders/' . $o['id'] . '/tracking')) ?>" class="stack"><?= csrf_field() ?>
    <label>Courier<input name="courier" value="<?= e($o['courier']) ?>"></label><label>Tracking number<input name="tracking_number" value="<?= e($o['tracking_number']) ?>"></label>
    <label>Internal note<textarea name="admin_note" rows="2"><?= e($o['admin_note']) ?></textarea></label><button class="btn" type="submit">Save</button></form>
</section></div></div>
