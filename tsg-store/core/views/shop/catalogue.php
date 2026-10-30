<div class="page-head"><h1><?= e($category['name'] ?? ($term !== '' ? 'Search results for "' . $term . '"' : 'All products')) ?></h1><p class="muted"><?= (int)$pg['total'] ?> products</p></div>
<div class="catalogue">
  <aside class="filters">
    <form method="get">
      <?php if ($term !== ''): ?><input type="hidden" name="q" value="<?= e($term) ?>"><?php endif; ?>
      <label>Sort by
        <select name="sort" onchange="this.form.submit()">
          <?php foreach (['new' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name'] as $k => $l): ?><option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
      <?php if ($brands): ?><label>Brand
        <select name="brand"><option value="">All brands</option><?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>"<?= (int)get('brand') === (int)$b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
      <div class="two"><label>Min price<input type="number" min="0" name="min" value="<?= e(get('min')) ?>"></label><label>Max price<input type="number" min="0" name="max" value="<?= e(get('max')) ?>"></label></div>
      <button class="btn btn-outline" type="submit">Apply filters</button>
    </form>
    <h3>Categories</h3>
    <ul class="catlist"><li><a href="<?= e(url('shop')) ?>">All products</a></li>
      <?php foreach ($categories as $c): ?><li><a href="<?= e(url('category/' . $c['slug'])) ?>"<?= ($category['id'] ?? 0) == $c['id'] ? ' class="on"' : '' ?>><?= e($c['name']) ?></a></li><?php endforeach; ?></ul>
  </aside>
  <section>
    <?php if (!$products): ?><p class="empty">No products found. Try a different search or filter.</p><?php endif; ?>
    <div class="grid"><?php foreach ($products as $p) echo render('shop/_card', ['p' => $p]); ?></div>
    <?php if ($pg['pages'] > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><a<?= $i === $pg['page'] ? ' class="on"' : '' ?> href="<?= e(pager_url($i)) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
  </section>
</div>
