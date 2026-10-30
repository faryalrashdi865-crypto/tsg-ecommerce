<h1>Your cart</h1>
<?php if (!$lines): ?><p class="empty">Your cart is empty.</p><p><a class="btn" href="<?= e(url('shop')) ?>">Continue shopping</a></p>
<?php else: ?>
<form method="post" action="<?= e(url('cart/update')) ?>"><?= csrf_field() ?>
<table class="table cart-table"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead><tbody>
<?php foreach ($lines as $l): ?>
<tr<?= $l['ok'] ? '' : ' class="bad"' ?>>
  <td class="prod"><img src="<?= e(product_image_url($l['image'])) ?>" alt=""><div><a href="<?= e(url('product/' . $l['product']['slug'])) ?>"><?= e($l['product']['name']) ?></a><?php if ($l['label']): ?><br><small class="muted"><?= e($l['label']) ?></small><?php endif; ?><?php if (!$l['ok']): ?><br><small class="err">Only <?= (int)$l['avail'] ?> available</small><?php endif; ?></div></td>
  <td><?= e(money($l['price'])) ?></td>
  <td><input class="qty" type="number" min="0" max="99" name="qty[<?= $l['id'] ?>]" value="<?= $l['qty'] ?>"></td>
  <td><?= e(money($l['total'])) ?></td>
  <td><button class="link" type="submit" form="rm<?= $l['id'] ?>">Remove</button></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<div class="cart-foot"><button class="btn btn-outline" type="submit">Update cart</button>
  <div class="sum"><p>Subtotal: <strong><?= e(money($subtotal)) ?></strong></p><p class="muted">Shipping and tax are calculated at checkout.</p><a class="btn" href="<?= e(url('checkout')) ?>">Proceed to checkout</a></div></div>
</form>
<?php foreach ($lines as $l): ?><form id="rm<?= $l['id'] ?>" method="post" action="<?= e(url('cart/remove')) ?>"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= $l['id'] ?>"></form><?php endforeach; ?>
<?php endif; ?>
