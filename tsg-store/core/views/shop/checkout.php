<h1>Checkout</h1>
<?php if (!empty($errors['cart'])): ?><div class="alert alert-error"><?= e($errors['cart']) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-error">Please fix the highlighted fields.<?= !empty($errors['_proof_note']) ? ' ' . e($errors['_proof_note']) : '' ?></div><?php endif; ?>
<form id="checkout-form" class="checkout" method="post" action="<?= e(url('checkout')) ?>" enctype="multipart/form-data" data-quote="<?= e(url('checkout/quote')) ?>">
  <?= csrf_field() ?>
  <div class="co-main">
    <?php if (!customer()): ?><p class="muted">Checking out as a guest. <a href="<?= e(url('account/login')) ?>">Log in</a> to use saved addresses.</p><?php endif; ?>
    <fieldset><legend>Contact</legend>
      <?php foreach (['name' => ['Full name', 'text'], 'email' => ['Email', 'email'], 'phone' => ['Phone / WhatsApp', 'tel']] as $k => [$lab, $type]): ?>
      <label><?= e($lab) ?><input type="<?= $type ?>" name="<?= $k ?>" value="<?= e($f[$k]) ?>" required><?php if (!empty($errors[$k])): ?><span class="err"><?= e($errors[$k]) ?></span><?php endif; ?></label>
      <?php endforeach; ?>
    </fieldset>
    <fieldset><legend>Delivery address</legend>
      <label>Address<input type="text" name="line1" value="<?= e($f['line1']) ?>" required><?php if (!empty($errors['line1'])): ?><span class="err"><?= e($errors['line1']) ?></span><?php endif; ?></label>
      <div class="two">
        <label>City<input type="text" name="city" value="<?= e($f['city']) ?>" required><?php if (!empty($errors['city'])): ?><span class="err"><?= e($errors['city']) ?></span><?php endif; ?></label>
        <label>Province<select name="province" required><option value="">Select</option><?php foreach (provinces() as $pv): ?><option<?= $f['province'] === $pv ? ' selected' : '' ?>><?= e($pv) ?></option><?php endforeach; ?></select><?php if (!empty($errors['province'])): ?><span class="err"><?= e($errors['province']) ?></span><?php endif; ?></label>
      </div>
      <label>Postal code (optional)<input type="text" name="postal" value="<?= e($f['postal']) ?>"></label>
    </fieldset>
    <fieldset><legend>Shipping method</legend>
      <div id="rates">
      <?php if (!$rates): ?><p class="muted" id="rates-empty">Enter your city and province to see shipping options.</p><?php endif; ?>
      <?php foreach ($rates as $r): ?>
        <label class="opt"><input type="radio" name="rate" value="<?= $r['id'] ?>" data-cost="<?= $r['cost'] ?>"<?= (string)$f['rate'] === (string)$r['id'] ? ' checked' : '' ?>> <?= e($r['name']) ?> <span class="muted"><?= $r['est_days'] ? '(' . e($r['est_days']) . ')' : '' ?></span> <strong><?= $r['cost'] > 0 ? e(money($r['cost'])) : 'Free' ?></strong></label>
      <?php endforeach; ?>
      </div>
      <button class="btn btn-outline" type="submit" name="action" value="update" id="update-rates">Update shipping options</button>
      <?php if (!empty($errors['rate'])): ?><span class="err"><?= e($errors['rate']) ?></span><?php endif; ?>
    </fieldset>
    <fieldset><legend>Payment method</legend>
      <?php foreach ($pms as $m): $sel = $f['pm'] === $m['code']; ?>
      <div class="pm<?= $sel ? ' sel' : '' ?>">
        <label class="opt"><input type="radio" name="pm" value="<?= e($m['code']) ?>" data-fee="<?= e($m['extra_fee']) ?>"<?= $sel ? ' checked' : '' ?>> <?= e($m['label']) ?><?php if ((float)$m['extra_fee'] > 0): ?> <span class="muted">(+ <?= e(money($m['extra_fee'])) ?> fee)</span><?php endif; ?></label>
        <div class="pm-detail">
          <p><?= nl2br(e($m['instructions'])) ?></p>
          <?php if ($m['account_details']): ?><pre class="acct"><?= e($m['account_details']) ?></pre><?php endif; ?>
          <?php if ((int)$m['is_manual']): ?>
            <?php if ($m['code'] !== 'bank'): ?><label>Transaction ID<input type="text" name="txn_id" value="<?= e(post('txn_id')) ?>"><?php if (!empty($errors['txn_id'])): ?><span class="err"><?= e($errors['txn_id']) ?></span><?php endif; ?></label><?php endif; ?>
            <label>Payment screenshot or receipt (image or PDF, max 3 MB)<input type="file" name="proof" accept="image/*,application/pdf"><?php if (!empty($errors['proof'])): ?><span class="err"><?= e($errors['proof']) ?></span><?php endif; ?></label>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!empty($errors['pm'])): ?><span class="err"><?= e($errors['pm']) ?></span><?php endif; ?>
    </fieldset>
    <label>Order note (optional)<textarea name="note" rows="2"><?= e($f['note']) ?></textarea></label>
  </div>
  <aside class="co-side">
    <h2>Order summary</h2>
    <ul class="mini"><?php foreach ($lines as $l): ?><li><span><?= e($l['product']['name']) ?><?= $l['label'] ? ' (' . e($l['label']) . ')' : '' ?> &times; <?= $l['qty'] ?></span><span><?= e(money($l['total'])) ?></span></li><?php endforeach; ?></ul>
    <dl class="totals">
      <dt>Subtotal</dt><dd id="t-subtotal"><?= e(money($totals['subtotal'])) ?></dd>
      <dt>Shipping</dt><dd id="t-shipping"><?= e(money($totals['shipping'])) ?></dd>
      <?php if ((float)setting('tax_percent') > 0): ?><dt id="t-tax-label"><?= e(setting('tax_label')) ?><?= $totals['tax_added'] ? '' : ' (included)' ?></dt><dd id="t-tax"><?= e(money($totals['tax'])) ?></dd><?php endif; ?>
      <dt id="t-fee-label"<?= $totals['fee'] > 0 ? '' : ' hidden' ?>>Payment fee</dt><dd id="t-fee"<?= $totals['fee'] > 0 ? '' : ' hidden' ?>><?= e(money($totals['fee'])) ?></dd>
      <dt class="grand">Total</dt><dd class="grand" id="t-total"><?= e(money($totals['total'])) ?></dd>
    </dl>
    <button class="btn btn-block" type="submit" name="action" value="place">Place order</button>
    <p class="muted small">By placing the order you agree to our terms and return policy.</p>
  </aside>
</form>
