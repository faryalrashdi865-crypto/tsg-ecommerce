<?php $price = product_price($p); $onSale = sale_active($p); ?>
<nav class="crumbs"><a href="<?= e(url()) ?>">Home</a> / <a href="<?= e(url('shop')) ?>">Shop</a><?php if ($p['category_name']): ?> / <a href="<?= e(url('category/' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a><?php endif; ?></nav>
<div class="product">
  <div class="gallery">
    <div class="gallery-main"><img id="mainImg" src="<?= e(product_image_url($images[0]['file'] ?? null)) ?>" alt="<?= e($p['name']) ?>" data-zoom></div>
    <?php if (count($images) > 1): ?><div class="thumbs"><?php foreach ($images as $im): ?><img src="<?= e(url($im['file'])) ?>" alt="" data-thumb><?php endforeach; ?></div><?php endif; ?>
  </div>
  <div class="info">
    <h1><?= e($p['name']) ?></h1>
    <?php if ($p['brand_name']): ?><p class="muted">Brand: <?= e($p['brand_name']) ?></p><?php endif; ?>
    <div class="price big"><?php if ($onSale): ?><s><?= e(money($p['price'])) ?></s> <?php endif; ?><strong><?= e(money($price)) ?></strong><?php if (module_on('units') && $p['unit']): ?> <small>/ <?= e($p['unit']) ?></small><?php endif; ?></div>
    <p class="stock <?= $in_stock ? 'in' : 'out' ?>"><?= $in_stock ? 'In stock' : 'Out of stock' ?><?php if ($p['sku']): ?> <span class="muted">&middot; SKU <?= e($p['sku']) ?></span><?php endif; ?></p>
    <?php if ($p['short_description']): ?><p><?= e($p['short_description']) ?></p><?php endif; ?>
    <?php if (module_on('warranty') && $p['warranty']): ?><p><strong>Warranty:</strong> <?= e($p['warranty']) ?></p><?php endif; ?>
    <?php if ($in_stock): ?>
    <form method="post" action="<?= e(url('cart/add')) ?>" class="buy">
      <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="back" value="product/<?= e($p['slug']) ?>">
      <?php if ($variants): ?>
      <label>Choose option
        <select name="variant_id" required><option value="">Select</option>
          <?php foreach ($variants as $v): $vp = unit_price($p, $v); $out = $p['track_stock'] && !$p['allow_backorder'] && (int)$v['stock'] <= 0; ?>
            <option value="<?= (int)$v['id'] ?>"<?= $out ? ' disabled' : '' ?>><?= e(variant_label($v)) ?> - <?= e(money($vp)) ?><?= $out ? ' (out of stock)' : '' ?></option>
          <?php endforeach; ?>
        </select></label>
      <?php endif; ?>
      <label>Quantity<input type="number" name="qty" value="1" min="1" max="99" class="qty"></label>
      <div class="btn-row"><button class="btn" type="submit">Add to cart</button><button class="btn btn-accent" type="submit" name="buy_now" value="1">Buy now</button></div>
    </form>
    <?php endif; ?>
    <?php if ($wa): ?><a class="btn btn-wa" href="<?= e($wa) ?>" target="_blank" rel="noopener">Order on WhatsApp</a><?php endif; ?>
    <?php if (module_on('size_guide') && $p['type'] === 'variable'): ?><details class="sizeguide"><summary>Size guide</summary><pre><?= e(setting('size_guide_text')) ?></pre></details><?php endif; ?>
  </div>
</div>
<section class="tabs">
  <?php if ($p['description']): ?><h2>Description</h2><div class="prose"><?= nl2br(e($p['description'])) ?></div><?php endif; ?>
  <?php if (module_on('specs') && $specs): ?><h2>Specifications</h2><table class="spec"><?php foreach ($specs as $k => $v): ?><tr><th><?= e($k) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
  <?php if (module_on('ingredients') && $p['ingredients']): ?><h2>Ingredients</h2><div class="prose"><?= nl2br(e($p['ingredients'])) ?></div><?php endif; ?>
</section>
<?php if ($related): ?><section><h2>Related products</h2><div class="grid"><?php foreach ($related as $r) echo render('shop/_card', ['p' => $r]); ?></div></section><?php endif; ?>
