<div class="cols">
<section class="panel"><h2>Categories</h2>
<table class="table"><thead><tr><th>Name</th><th>Parent</th><th>Products</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($cats as $c): ?><tr>
<td><input form="cf<?= $c['id'] ?>" name="name" value="<?= e($c['name']) ?>"></td>
<td><select form="cf<?= $c['id'] ?>" name="parent_id"><option value="">None</option><?php foreach ($tops as $t): if ($t['id'] == $c['id']) continue; ?><option value="<?= $t['id'] ?>"<?= $c['parent_id'] == $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></td>
<td><?= (int)$c['n'] ?></td><td><select form="cf<?= $c['id'] ?>" name="status"><option<?= $c['status'] === 'active' ? ' selected' : '' ?>>active</option><option<?= $c['status'] === 'hidden' ? ' selected' : '' ?>>hidden</option></select></td>
<td class="actions"><button form="cf<?= $c['id'] ?>" class="btn btn-sm" type="submit">Save</button><button form="cf<?= $c['id'] ?>" class="link err" type="submit" formaction="<?= e(url('admin/categories/' . $c['id'] . '/delete')) ?>" data-confirm="Delete this category? Products stay but lose the category.">Delete</button></td></tr><?php endforeach; ?>
</tbody></table>
<?php foreach ($cats as $c): ?><form id="cf<?= $c['id'] ?>" method="post" action="<?= e(url('admin/categories/save')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="position" value="<?= (int)$c['position'] ?>"></form><?php endforeach; ?>
<h3>Add category</h3>
<form method="post" action="<?= e(url('admin/categories/save')) ?>" class="inline-form"><?= csrf_field() ?><input name="name" placeholder="Category name" required>
<select name="parent_id"><option value="">No parent</option><?php foreach ($tops as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select><button class="btn" type="submit">Add</button></form>
</section>
<section class="panel"><h2>Brands</h2>
<ul class="plain"><?php foreach ($brands as $b): ?><li><?= e($b['name']) ?> <form method="post" action="<?= e(url('admin/brands/' . $b['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this brand?"><?= csrf_field() ?><button class="link err" type="submit">Delete</button></form></li><?php endforeach; ?></ul>
<form method="post" action="<?= e(url('admin/brands/save')) ?>" class="inline-form"><?= csrf_field() ?><input name="name" placeholder="Brand name" required><button class="btn" type="submit">Add brand</button></form>
</section></div>
