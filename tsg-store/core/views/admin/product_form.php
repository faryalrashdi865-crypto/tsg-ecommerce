<?php $v = fn($k, $d = '') => e($p[$k] ?? $d); $fields = array_values(array_filter(array_map('trim', explode(',', setting('variant_fields', 'Option'))))); $isVar = ($p['type'] ?? 'simple') === 'variable'; ?>
<form method="post" action="<?= e(url('admin/products/save')) ?>" enctype="multipart/form-data" class="form" id="product-form"><?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)($p['id'] ?? 0) ?>">
<div class="cols">
<section class="panel"><h2>Basics</h2>
  <label>Name<input name="name" value="<?= $v('name') ?>" required></label>
  <label>Short description<input name="short_description" maxlength="500" value="<?= $v('short_description') ?>"></label>
  <label>Description<textarea name="description" rows="6"><?= $v('description') ?></textarea></label>
  <div class="two"><label>Category<select name="category_id"><option value="">None</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"<?= ($p['category_id'] ?? 0) == $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
  <label>Brand<select name="brand_id"><option value="">None</option><?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>"<?= ($p['brand_id'] ?? 0) == $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></label></div>
  <?php if (module_on('specs')): ?><label>Specifications (one per line, "Label: value")<textarea name="specifications" rows="4"><?= $v('specifications') ?></textarea></label><?php endif; ?>
  <?php if (module_on('warranty')): ?><label>Warranty<input name="warranty" value="<?= $v('warranty') ?>" placeholder="e.g. 1 year brand warranty"></label><?php endif; ?>
  <?php if (module_on('ingredients')): ?><label>Ingredients<textarea name="ingredients" rows="3"><?= $v('ingredients') ?></textarea></label><?php endif; ?>
</section>
<section class="panel"><h2>Price and stock</h2>
  <label>Product type<select name="type" id="ptype"><option value="simple">Simple (one price, one stock)</option><?php if (module_on('variants')): ?><option value="variable"<?= $isVar ? ' selected' : '' ?>>Variable (<?= e(implode(', ', $fields)) ?>)</option><?php endif; ?></select></label>
  <div class="two"><label>Price<input type="number" step="0.01" min="0" name="price" value="<?= $v('price', '0') ?>" required></label><label>Sale price<input type="number" step="0.01" min="0" name="sale_price" value="<?= $v('sale_price') ?>"></label></div>
  <div class="two"><label>Sale starts<input type="date" name="sale_starts" value="<?= $v('sale_starts') ?>"></label><label>Sale ends<input type="date" name="sale_ends" value="<?= $v('sale_ends') ?>"></label></div>
  <div class="two"><label>Cost price (for profit reports)<input type="number" step="0.01" min="0" name="cost_price" value="<?= $v('cost_price') ?>"></label><label>SKU<input name="sku" value="<?= $v('sku') ?>"></label></div>
  <div class="two"><label>Stock quantity<input type="number" name="stock" min="0" value="<?= $v('stock', '0') ?>" data-simple-only></label><label>Low-stock alert at<input type="number" min="0" name="low_stock_level" value="<?= $v('low_stock_level', setting('low_stock_default', '5')) ?>"></label></div>
  <label class="check"><input type="checkbox" name="track_stock" value="1"<?= ($p['track_stock'] ?? 1) ? ' checked' : '' ?>> Track stock</label>
  <label class="check"><input type="checkbox" name="allow_backorder" value="1"<?= ($p['allow_backorder'] ?? setting('allow_backorders_default', '0')) ? ' checked' : '' ?>> Allow back-orders when out of stock</label>
  <div class="two"><label>Weight (kg)<input type="number" step="0.001" min="0" name="weight" value="<?= $v('weight', '0') ?>"></label>
  <?php if (module_on('units')): ?><label>Unit (kg, dozen, pack)<input name="unit" value="<?= $v('unit') ?>"></label><?php endif; ?></div>
</section></div>
<section class="panel" id="variants-panel"><h2>Variants</h2>
  <p class="muted">One row per combination. Leave price empty to use the product price.</p>
  <table class="table" id="variants"><thead><tr><?php foreach ($fields as $f): ?><th><?= e($f) ?></th><?php endforeach; ?><th>SKU</th><th>Price</th><th>Stock</th><th></th></tr></thead><tbody>
  <?php foreach (array_merge($variants, [['id' => 0, 'options' => '{}', 'sku' => '', 'price' => '', 'stock' => 0]]) as $vr): $o = json_decode($vr['options'], true) ?: []; ?>
    <tr>
    <?php foreach ($fields as $i => $f): ?><td><?php if ($i === 0): ?><input type="hidden" name="v_id[]" value="<?= (int)$vr['id'] ?>"><?php endif; ?><input name="v_opt<?= $i ?>[]" value="<?= e($o[$f] ?? '') ?>"></td><?php endforeach; ?>
    <td><input name="v_sku[]" value="<?= e($vr['sku']) ?>"></td><td><input type="number" step="0.01" min="0" name="v_price[]" value="<?= e($vr['price']) ?>"></td><td><input type="number" min="0" name="v_stock[]" value="<?= (int)$vr['stock'] ?>"></td>
    <td><button type="button" class="link err" data-remove-row>Remove</button></td></tr>
  <?php endforeach; ?></tbody></table>
  <button type="button" class="btn btn-outline" id="add-variant">Add variant row</button>
</section>
<div class="cols">
<section class="panel"><h2>Images</h2>
  <div class="imgs"><?php foreach ($images as $im): ?><div class="imgbox"><img src="<?= e(url($im['file'])) ?>" alt=""><button class="link err" type="submit" formaction="<?= e(url('admin/products/image/' . $im['id'] . '/delete')) ?>" formnovalidate>Remove</button></div><?php endforeach; ?></div>
  <label>Add images (JPG, PNG, WEBP; max 3 MB each)<input type="file" name="images[]" accept="image/*" multiple></label>
  <label>Video link<input name="video_url" value="<?= $v('video_url') ?>"></label>
</section>
<section class="panel"><h2>Publishing and SEO</h2>
  <label>Status<select name="status"><?php foreach (['published', 'draft', 'hidden'] as $s): ?><option<?= ($p['status'] ?? 'published') === $s ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
  <label class="check"><input type="checkbox" name="is_featured" value="1"<?= !empty($p['is_featured']) ? ' checked' : '' ?>> Show on the homepage as featured</label>
  <label>Tags (comma separated)<input name="tags" value="<?= $v('tags') ?>"></label>
  <label>SEO title<input name="seo_title" maxlength="160" value="<?= $v('seo_title') ?>"></label>
  <label>SEO description<textarea name="seo_description" rows="2" maxlength="300"><?= $v('seo_description') ?></textarea></label>
</section></div>
<div class="sticky-actions"><button class="btn" type="submit">Save product</button> <a class="btn btn-outline" href="<?= e(url('admin/products')) ?>">Back to products</a></div>
</form>
