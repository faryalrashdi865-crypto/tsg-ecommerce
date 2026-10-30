<table class="table"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($list as $a): ?><tr><td><?= e(fmt_date($a['created_at'])) ?></td><td><?= e($a['user_name']) ?></td><td><?= e($a['action']) ?></td><td><?= e($a['target']) ?></td><td><?= e($a['detail']) ?></td></tr><?php endforeach; ?></tbody></table>
