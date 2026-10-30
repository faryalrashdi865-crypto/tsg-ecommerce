<?php foreach (array_merge($list, [['id' => 0, 'title' => '', 'subtitle' => '', 'image' => '', 'link' => '', 'button_text' => '', 'position' => 0, 'active' => 1]]) as $b): ?>
<section class="panel"><h2><?= $b['id'] ? e($b['title'] ?: 'Banner #' . $b['id']) : 'Add banner' ?></h2>
<form method="post" action="<?= e(url('admin/banners/save')) ?>" enctype="multipart/form-data" class="form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
<div class="two"><label>Title<input name="title" value="<?= e($b['title']) ?>"></label><label>Subtitle<input name="subtitle" value="<?= e($b['subtitle']) ?>"></label></div>
<div class="two"><label>Link (e.g. shop or category/shoes)<input name="link" value="<?= e($b['link']) ?>"></label><label>Button text<input name="button_text" value="<?= e($b['button_text']) ?>"></label></div>
<div class="two"><label>Image (wide, JPG/PNG/WEBP)<input type="file" name="image" accept="image/*"></label><label>Order<input type="number" name="position" value="<?= (int)$b['position'] ?>"></label></div>
<?php if ($b['image']): ?><img class="thumb wide-thumb" src="<?= e(url($b['image'])) ?>" alt=""><?php endif; ?>
<label class="check"><input type="checkbox" name="active" value="1"<?= $b['active'] ? ' checked' : '' ?>> Show on the homepage</label>
<button class="btn btn-sm" type="submit">Save</button><?php if ($b['id']): ?> <button class="link err" type="submit" formaction="<?= e(url('admin/banners/' . $b['id'] . '/delete')) ?>" data-confirm="Delete this banner?">Delete</button><?php endif; ?></form></section>
<?php endforeach; ?>
