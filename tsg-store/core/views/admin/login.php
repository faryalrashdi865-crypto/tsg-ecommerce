<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin login | <?= e(setting('store_name')) ?></title>
<style><?= theme_css_vars() ?></style><link rel="stylesheet" href="<?= e(url('assets/admin.css')) ?>"></head>
<body class="login"><form method="post" class="login-box"><?= csrf_field() ?>
<h1><?= e(setting('store_name')) ?></h1><p class="muted">Admin panel</p>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
<label>Email<input type="email" name="email" value="<?= e(post('email')) ?>" required autofocus></label>
<label>Password<input type="password" name="password" required></label>
<button class="btn" type="submit">Log in</button></form></body></html>
