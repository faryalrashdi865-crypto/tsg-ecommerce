<?php if ($just_placed): ?>
<div class="alert alert-success"><strong>Thank you! Your order <?= e($o['number']) ?> has been received.</strong> A confirmation was sent to <?= e($o['cust_email']) ?>.</div>
<?php if ($pm && (int)$pm['is_manual'] && ($payment['status'] ?? '') === 'Pending'): ?><div class="alert alert-info">We received your payment details. Our staff will verify the payment and confirm your order.</div>
<?php elseif ($o['payment_method'] === 'cod'): ?><div class="alert alert-info">Pay in cash when your order is delivered. We may contact you to confirm the order.</div><?php endif; ?>
<?php if ($wa): ?><p><a class="btn btn-wa" href="<?= e($wa) ?>" target="_blank" rel="noopener">Confirm on WhatsApp</a></p><?php endif; ?>
<p class="muted">Keep this page link to track your order: <a href="<?= e(order_link($o)) ?>"><?= e(order_link($o)) ?></a></p>
<?php endif; ?>
<?= render('shop/_order_detail', compact('o', 'items', 'log', 'pm', 'payment')) ?>
