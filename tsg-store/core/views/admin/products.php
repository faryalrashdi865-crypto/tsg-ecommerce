<div class="toolbar"><form method="get"><input type="search" name="q" placeholder="Search name or SKU" value="<?= e($term) ?>"><select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach (['published', 'draft', 'hidden'] as $s): ?><option<?= get('status') === $s ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select><button class="btn btn-outline">Search</button></form>
<?php if (can('products.manage')): ?><a class="btn" href="<?= e(url('admin/products/new')) ?>">Add product</a><?php endif; ?></div>
<table class="table"><thead><tr><th></th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($list as $p): ?><tr><td><img class="thumb" src="<?= e(product_image_url($p['image'])) ?>" alt=""></td>
<td><?= e($p['name']) ?><br><small class="muted"><?= e($p['sku']) ?> <?= $p['type'] === 'variable' ? '&middot; variants' : '' ?></small></td><td><?= e($p['category_name']) ?></td>
<td><?= e(money($p['price'])) ?><?= sale_active($p) ? '<br><small class="ok">Sale ' . e(money($p['sale_price'])) . '</small>' : '' ?></td>
<td><?= $p['track_stock'] ? (int)$p['total_stock'] : 'n/a' ?></td><td><?= e($p['status']) ?></td>
<td class="actions"><?php if (can('products.manage')): ?><a href="<?= e(url('admin/products/' . $p['id'] . '/edit')) ?>">Edit</a>
<form method="post" action="<?= e(url('admin/products/' . $p['id'] . '/delete')) ?>" data-confirm="Delete this product?"><?= csrf_field() ?><button class="link err" type="submit">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="7" class="muted">No products yet.</td></tr><?php endif; ?></tbody></table>
<?php if ($pg['pages'] > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pg['pages']; $i++): ?><a<?= $i === $pg['page'] ? ' class="on"' : '' ?> href="<?= e(pager_url($i)) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
