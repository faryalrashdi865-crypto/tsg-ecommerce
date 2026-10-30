<div class="toolbar"><span></span><a class="btn" href="<?= e(url('admin/pages/new')) ?>">New page</a></div>
<table class="table"><thead><tr><th>Title</th><th>URL</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($list as $pg): ?><tr><td><?= e($pg['title']) ?></td><td>/page/<?= e($pg['slug']) ?></td><td><?= e($pg['status']) ?></td><td class="actions"><a href="<?= e(url('admin/pages/' . $pg['id'] . '/edit')) ?>">Edit</a>
<form method="post" action="<?= e(url('admin/pages/' . $pg['id'] . '/delete')) ?>" data-confirm="Delete this page?"><?= csrf_field() ?><button class="link err" type="submit">Delete</button></form></td></tr><?php endforeach; ?></tbody></table>
