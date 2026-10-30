<?php $s = fn($k) => e(setting($k)); $chk = fn($k) => setting($k) === '1' ? ' checked' : ''; ?>
<form method="post" action="<?= e(url('admin/settings')) ?>" enctype="multipart/form-data" class="form"><?= csrf_field() ?>
<p class="muted">Change a setting here and every page, email and invoice updates. No code is edited.</p>
<div class="cols">
<section class="panel"><h2>Store details</h2>
  <label>Store name<input name="store_name" value="<?= $s('store_name') ?>" required></label><label>Tagline<input name="tagline" value="<?= $s('tagline') ?>"></label>
  <label>Logo<input type="file" name="logo" accept="image/*"></label><?php if (setting('logo')): ?><img class="thumb wide-thumb" src="<?= e(url(setting('logo'))) ?>" alt=""><label class="check"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label><?php endif; ?>
  <label>Favicon<input type="file" name="favicon" accept="image/*"></label>
  <div class="two"><label>Contact email<input type="email" name="contact_email" value="<?= $s('contact_email') ?>"></label><label>Phone<input name="phone" value="<?= $s('phone') ?>"></label></div>
  <div class="two"><label>WhatsApp number (with country code)<input name="whatsapp_number" value="<?= $s('whatsapp_number') ?>" placeholder="923001234567"></label><label>Country<input name="country" value="<?= $s('country') ?>"></label></div>
  <label>Address<textarea name="address" rows="2"><?= $s('address') ?></textarea></label>
  <div class="two"><label>Facebook link<input name="facebook" value="<?= $s('facebook') ?>"></label><label>Instagram link<input name="instagram" value="<?= $s('instagram') ?>"></label></div>
  <label>Footer text<input name="footer_text" value="<?= $s('footer_text') ?>"></label>
</section>
<section class="panel"><h2>Theme</h2>
  <label>Theme<select name="theme"><?php foreach ($themes as $k => $t): ?><option value="<?= e($k) ?>"<?= setting('theme') === $k ? ' selected' : '' ?>><?= e($t['label'] ?? $k) ?></option><?php endforeach; ?></select></label>
  <div class="three"><label>Primary colour<input type="color" name="primary_color" value="<?= $s('primary_color') ?>"></label><label>Secondary colour<input type="color" name="secondary_color" value="<?= $s('secondary_color') ?>"></label><label>Accent colour<input type="color" name="accent_color" value="<?= $s('accent_color') ?>"></label></div>
  <label>Heading font<input name="font_heading" value="<?= $s('font_heading') ?>"></label><label>Body font<input name="font_body" value="<?= $s('font_body') ?>"></label>
  <label>Homepage sections, in order (banner, featured, categories, new)<input name="home_sections" value="<?= $s('home_sections') ?>"></label>
  <label>Products per page<input type="number" min="4" name="products_per_page" value="<?= $s('products_per_page') ?>"></label>
</section></div>
<div class="cols">
<section class="panel"><h2>Currency and tax</h2>
  <div class="two"><label>Currency code<input name="currency_code" value="<?= $s('currency_code') ?>"></label><label>Symbol<input name="currency_symbol" value="<?= $s('currency_symbol') ?>"></label></div>
  <div class="two"><label>Symbol position<select name="currency_position"><option value="before">Before the amount</option><option value="after"<?= setting('currency_position') === 'after' ? ' selected' : '' ?>>After the amount</option></select></label><label>Decimals<input type="number" min="0" max="3" name="currency_decimals" value="<?= $s('currency_decimals') ?>"></label></div>
  <div class="three"><label>Tax %<input type="number" step="0.01" min="0" name="tax_percent" value="<?= $s('tax_percent') ?>"></label><label>Tax label<input name="tax_label" value="<?= $s('tax_label') ?>"></label>
  <label>Tax is<select name="tax_mode"><option value="added">Added to prices</option><option value="included"<?= setting('tax_mode') === 'included' ? ' selected' : '' ?>>Included in prices</option></select></label></div>
  <div class="two"><label>Minimum order amount (0 = none)<input type="number" min="0" name="min_order_amount" value="<?= $s('min_order_amount') ?>"></label><label>Return period (days, 0 = no returns)<input type="number" min="0" name="return_days" value="<?= $s('return_days') ?>"></label></div>
</section>
<section class="panel"><h2>Modules</h2>
  <p class="muted">Business preset: <strong><?= e($presets[setting('preset')]['label'] ?? setting('preset')) ?></strong>. The preset only chose the starting switches below, change any you like.</p>
  <?php foreach (['variants' => 'Product variants (size, colour, shade)', 'specs' => 'Specification table', 'units' => 'Units (kg, dozen, pack)', 'ingredients' => 'Ingredients tab', 'warranty' => 'Warranty field', 'size_guide' => 'Size guide', 'brand_filter' => 'Brand filter in the catalogue', 'whatsapp_button' => 'WhatsApp order button and floating chat button', 'guest_checkout' => 'Guest checkout'] as $k => $l): ?>
  <label class="check"><input type="checkbox" name="mod_<?= $k ?>" value="1"<?= $chk('mod_' . $k) ?>> <?= e($l) ?></label><?php endforeach; ?>
  <label>Variant fields (comma separated, e.g. Size,Colour)<input name="variant_fields" value="<?= $s('variant_fields') ?>"></label>
  <label>Size guide text<textarea name="size_guide_text" rows="3"><?= $s('size_guide_text') ?></textarea></label>
  <label class="check"><input type="checkbox" name="test_mode" value="1"<?= $chk('test_mode') ?>> Test mode (shows a notice to visitors)</label>
  <label class="check"><input type="checkbox" name="force_https" value="1"<?= $chk('force_https') ?>> Force HTTPS (turn on only when the SSL certificate works)</label>
</section></div>
<section class="panel"><h2>Email and WhatsApp templates</h2>
  <p class="muted">Placeholders: {name} {order_number} {total} {status} {tracking} {link} {store_name}</p>
  <label>Order placed - subject<input name="tpl_order_placed_subject" value="<?= $s('tpl_order_placed_subject') ?>"></label><label>Order placed - message<textarea name="tpl_order_placed_body" rows="4"><?= $s('tpl_order_placed_body') ?></textarea></label>
  <label>Status change - subject<input name="tpl_order_status_subject" value="<?= $s('tpl_order_status_subject') ?>"></label><label>Status change - message<textarea name="tpl_order_status_body" rows="4"><?= $s('tpl_order_status_body') ?></textarea></label>
  <label>WhatsApp confirmation text<input name="wa_order_text" value="<?= $s('wa_order_text') ?>"></label>
</section>
<div class="sticky-actions"><button class="btn" type="submit">Save settings</button></div></form>
