<section class="narrow"><h1>Track your order</h1>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="stack"><?= csrf_field() ?>
  <label>Order number<input type="text" name="number" value="<?= e(post('number')) ?>" placeholder="TSG-250101-ABCDE" required></label>
  <label>Email used for the order<input type="email" name="email" value="<?= e(post('email')) ?>" required></label>
  <button class="btn" type="submit">Track order</button>
</form></section>
<?php if (!empty($o)) echo render('shop/_order_detail', compact('o', 'items', 'log', 'pm', 'payment')); ?>
