<p><a href="<?= e(url('account')) ?>">&larr; My account</a></p>
<?= render('shop/_order_detail', compact('o', 'items', 'log', 'pm', 'payment')) ?>
<?php if ($canReturn): ?>
<form method="post" action="<?= e(url('account/orders/' . $o['number'] . '/return')) ?>" class="stack narrow"><?= csrf_field() ?>
  <h3>Request a return</h3><p class="muted">Returns are accepted within <?= (int)$days ?> days of delivery.</p>
  <label>Reason<textarea name="reason" rows="3" required></textarea></label><button class="btn btn-outline" type="submit">Send return request</button></form>
<?php elseif ($o['status'] === 'Return Requested'): ?><div class="alert alert-info">Your return request is being reviewed.</div><?php endif; ?>
