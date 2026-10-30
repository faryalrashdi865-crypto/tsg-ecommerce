<form method="post" action="<?= e(url('admin/pages/save')) ?>" class="form panel"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($pg['id'] ?? 0) ?>">
<label>Title<input name="title" value="<?= e($pg['title'] ?? '') ?>" required></label>
<label>Content<textarea name="body" rows="14"><?= e($pg['body'] ?? '') ?></textarea></label>
<div class="two"><label>Status<select name="status"><option>published</option><option<?= ($pg['status'] ?? '') === 'draft' ? ' selected' : '' ?>>draft</option></select></label><label>Order<input type="number" name="position" value="<?= (int)($pg['position'] ?? 0) ?>"></label></div>
<label class="check"><input type="checkbox" name="show_in_footer" value="1"<?= ($pg['show_in_footer'] ?? 1) ? ' checked' : '' ?>> Show link in the footer</label>
<button class="btn" type="submit">Save page</button> <a class="btn btn-outline" href="<?= e(url('admin/pages')) ?>">Cancel</a></form>
